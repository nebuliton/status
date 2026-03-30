#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_HOST="${PHP_HOST:-127.0.0.1}"
PHP_PORT="${PHP_PORT:-8000}"
VITE_HOST="${VITE_HOST:-127.0.0.1}"
START_SCHEDULER="${START_SCHEDULER:-1}"
OPEN_BROWSER="${OPEN_BROWSER:-0}"

PHP_PID=""
VITE_PID=""
SCHEDULER_PID=""

supports_color() {
    [[ -t 1 ]] && [[ "${TERM:-}" != "dumb" ]]
}

if supports_color; then
    C_RESET=$'\033[0m'
    C_DIM=$'\033[2m'
    C_BLUE=$'\033[38;5;75m'
    C_GREEN=$'\033[38;5;42m'
    C_YELLOW=$'\033[38;5;221m'
    C_RED=$'\033[38;5;203m'
    C_WHITE=$'\033[1;37m'
else
    C_RESET=''
    C_DIM=''
    C_BLUE=''
    C_GREEN=''
    C_YELLOW=''
    C_RED=''
    C_WHITE=''
fi

print_banner() {
    printf '\n'
    printf '%s%s%s\n' "$C_BLUE" 'Nebuliton Dev Launcher' "$C_RESET"
    printf '%s%s%s\n' "$C_DIM" '──────────────────────' "$C_RESET"
}

print_row() {
    printf '%b%-14s%b %s\n' "$C_DIM" "$1" "$C_RESET" "$2"
}

step() {
    printf '%s[%s]%s %s\n' "$C_BLUE" "$(date '+%H:%M:%S')" "$C_RESET" "$1"
}

success() {
    printf '%s[%s]%s %s\n' "$C_GREEN" "$(date '+%H:%M:%S')" "$C_RESET" "$1"
}

warn() {
    printf '%s[%s]%s %s\n' "$C_YELLOW" "$(date '+%H:%M:%S')" "$C_RESET" "$1"
}

fail() {
    printf '%s[%s]%s %s\n' "$C_RED" "$(date '+%H:%M:%S')" "$C_RESET" "$1" >&2
    exit 1
}

require_command() {
    resolve_command "$1" >/dev/null || fail "Befehl '$1' wurde nicht gefunden."
}

normalize_windows_path() {
    local raw_path="$1"

    if command -v cygpath >/dev/null 2>&1; then
        cygpath -u "$raw_path"
        return
    fi

    printf '%s' "$raw_path"
}

resolve_command() {
    local name="$1"
    local candidate
    local resolved

    for candidate in "$name" "$name.exe" "$name.cmd" "$name.bat"; do
        if command -v "$candidate" >/dev/null 2>&1; then
            command -v "$candidate"
            return 0
        fi
    done

    if command -v powershell.exe >/dev/null 2>&1; then
        resolved="$(powershell.exe -NoProfile -Command "(Get-Command '$name' -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty Source)" 2>/dev/null | tr -d '\r')"

        if [[ -n "$resolved" ]]; then
            normalize_windows_path "$resolved"
            return 0
        fi
    fi

    return 1
}

open_browser() {
    local url="$1"

    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$url" >/dev/null 2>&1 &
        return
    fi

    if command -v open >/dev/null 2>&1; then
        open "$url" >/dev/null 2>&1 &
    fi
}

cleanup() {
    local exit_code=$?
    trap - EXIT INT TERM

    if [[ -n "$PHP_PID" ]]; then
        kill "$PHP_PID" >/dev/null 2>&1 || true
    fi

    if [[ -n "$VITE_PID" ]]; then
        kill "$VITE_PID" >/dev/null 2>&1 || true
    fi

    if [[ -n "$SCHEDULER_PID" ]]; then
        kill "$SCHEDULER_PID" >/dev/null 2>&1 || true
    fi

    exit "$exit_code"
}

trap cleanup EXIT INT TERM

[[ -f "$PROJECT_ROOT/artisan" ]] || fail "Im Projektordner wurde keine Datei 'artisan' gefunden."

require_command php
require_command npm

PHP_BIN="$(resolve_command php)" || fail "Befehl 'php' wurde nicht gefunden."
NPM_BIN="$(resolve_command npm)" || fail "Befehl 'npm' wurde nicht gefunden."

print_banner
print_row "Projekt" "$PROJECT_ROOT"
print_row "Laravel" "http://$PHP_HOST:$PHP_PORT"
print_row "Vite" "http://$VITE_HOST:5173"
print_row "Scheduler" "$([[ "$START_SCHEDULER" == "1" ]] && printf 'aktiv' || printf 'deaktiviert')"
printf '\n'

cd "$PROJECT_ROOT"

step "Starte Laravel-Server"
"$PHP_BIN" artisan serve --host="$PHP_HOST" --port="$PHP_PORT" &
PHP_PID=$!

step "Starte Vite-Dev-Server"
"$NPM_BIN" run dev -- --host "$VITE_HOST" &
VITE_PID=$!

if [[ "$START_SCHEDULER" == "1" ]]; then
    step "Starte Scheduler"
    "$PHP_BIN" artisan schedule:work &
    SCHEDULER_PID=$!
fi

success "Alle Dev-Prozesse laufen"
print_row "Laravel PID" "$PHP_PID"
print_row "Vite PID" "$VITE_PID"
if [[ -n "$SCHEDULER_PID" ]]; then
    print_row "Scheduler PID" "$SCHEDULER_PID"
fi
printf '\n'
printf '%s%s%s\n' "$C_DIM" 'Zum Beenden einfach Strg+C in diesem Fenster drücken.' "$C_RESET"

if [[ "$OPEN_BROWSER" == "1" ]]; then
    open_browser "http://$PHP_HOST:$PHP_PORT"
fi

wait -n "$PHP_PID" "$VITE_PID"
