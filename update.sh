#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

supports_color() {
    [[ -t 1 ]] && [[ "${TERM:-}" != "dumb" ]]
}

if supports_color; then
    C_RESET=$'\033[0m'
    C_BLUE=$'\033[38;5;75m'
    C_DIM=$'\033[2m'
else
    C_RESET=''
    C_BLUE=''
    C_DIM=''
fi

printf '\n%sNebuliton Update Runner%s\n' "$C_BLUE" "$C_RESET"
printf '%sProjekt: %s%s\n\n' "$C_DIM" "$PROJECT_ROOT" "$C_RESET"

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

PHP_BIN="$(resolve_command php)" || {
    printf 'Befehl "php" wurde nicht gefunden.\n' >&2
    exit 1
}

cd "$PROJECT_ROOT"
"$PHP_BIN" artisan app:update "$@"
