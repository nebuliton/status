#!/usr/bin/env bash

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODE="local"
DO_BUILD=1
DO_MIGRATE=1
DO_RELOAD=1
WEB_SERVICE="app"
NO_COLOR=0
PLAIN_OUTPUT=0

supports_color() {
    [[ -t 1 ]] && [[ "${TERM:-}" != "dumb" ]] && [[ "$NO_COLOR" -eq 0 ]]
}

setup_colors() {
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
}

usage() {
    cat <<'EOF'
Verwendung:
  ./deploy.sh [local|docker] [--skip-build] [--skip-migrate] [--skip-reload] [--service web] [--no-color] [--plain]

Beispiele:
  ./deploy.sh local
  ./deploy.sh docker --service=app
  ./deploy.sh local --skip-build
EOF
}

banner() {
    [[ "$PLAIN_OUTPUT" -eq 1 ]] && return
    printf '\n%sNebuliton Deploy%s\n' "$C_BLUE" "$C_RESET"
    printf '%s─────────────────%s\n' "$C_DIM" "$C_RESET"
}

print_row() {
    [[ "$PLAIN_OUTPUT" -eq 1 ]] && return
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

die() {
    printf '%s[%s]%s %s\n' "$C_RED" "$(date '+%H:%M:%S')" "$C_RESET" "$1" >&2
    exit 1
}

have_command() {
    command -v "$1" >/dev/null 2>&1
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

require_command() {
    resolve_command "$1" >/dev/null || die "Befehl '$1' wurde nicht gefunden."
}

require_file() {
    [[ -f "$1" ]] || die "Fehlende Datei: $1"
}

run_cmd() {
    local description="$1"
    shift

    step "$description"
    printf '%s$ %s%s\n' "$C_DIM" "$*" "$C_RESET"
    "$@"
}

run_shell_cmd() {
    local description="$1"
    local command_string="$2"

    step "$description"
    printf '%s$ %s%s\n' "$C_DIM" "$command_string" "$C_RESET"
    sh -c "$command_string"
}

repair_node_tool_permissions() {
    [[ -d "$APP_DIR/node_modules" ]] || return

    if [[ -d "$APP_DIR/node_modules/.bin" ]]; then
        find "$APP_DIR/node_modules/.bin" -type f -exec chmod 755 {} \;
    fi

    if [[ -f "$APP_DIR/node_modules/vite/bin/vite.js" ]]; then
        chmod 755 "$APP_DIR/node_modules/vite/bin/vite.js"
    fi

    if [[ -d "$APP_DIR/node_modules/@esbuild" ]]; then
        find "$APP_DIR/node_modules/@esbuild" -path '*/bin/esbuild' -type f -exec chmod 755 {} \;
    fi
}

run_frontend_build() {
    repair_node_tool_permissions

    if [[ -f "$APP_DIR/node_modules/vite/bin/vite.js" ]]; then
        run_cmd "Baue Frontend" "$NODE_BIN" "$APP_DIR/node_modules/vite/bin/vite.js" build --configLoader runner
        return
    fi

    run_cmd "Baue Frontend" "$NPM_BIN" run build
}

detect_compose() {
    if [[ -n "${DOCKER_BIN:-}" ]] && "$DOCKER_BIN" compose version >/dev/null 2>&1; then
        printf '%s' "\"$DOCKER_BIN\" compose"
        return 0
    fi

    if [[ -n "${DOCKER_COMPOSE_BIN:-}" ]]; then
        printf '%s' "\"$DOCKER_COMPOSE_BIN\""
        return 0
    fi

    return 1
}

parse_args() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            local|docker)
                MODE="$1"
                ;;
            --skip-build)
                DO_BUILD=0
                ;;
            --skip-migrate)
                DO_MIGRATE=0
                ;;
            --skip-reload)
                DO_RELOAD=0
                ;;
            --service)
                shift
                [[ $# -gt 0 ]] || die "Für --service fehlt der Name."
                WEB_SERVICE="$1"
                ;;
            --service=*)
                WEB_SERVICE="${1#*=}"
                ;;
            --no-color)
                NO_COLOR=1
                ;;
            --plain)
                PLAIN_OUTPUT=1
                NO_COLOR=1
                ;;
            -h|--help)
                usage
                exit 0
                ;;
            *)
                die "Unbekannte Option: $1"
                ;;
        esac

        shift
    done
}

preflight_common() {
    require_file "$APP_DIR/artisan"
    require_file "$APP_DIR/package.json"
}

preflight_local() {
    preflight_common
    require_command php
    PHP_BIN="$(resolve_command php)"

    if [[ "$DO_BUILD" -eq 1 ]]; then
        NODE_BIN="$(resolve_command node)"
        NPM_BIN="$(resolve_command npm)"
    fi
}

preflight_docker() {
    preflight_common
    PHP_BIN="$(resolve_command php)"

    if [[ -f "$APP_DIR/docker-compose.yml" ]]; then
        :
    elif [[ -f "$APP_DIR/docker-compose.yaml" ]]; then
        :
    else
        die "docker-compose.yml oder docker-compose.yaml fehlt."
    fi

    DOCKER_BIN="$(resolve_command docker || true)"
    DOCKER_COMPOSE_BIN="$(resolve_command docker-compose || true)"
    COMPOSE_CMD="$(detect_compose)" || die "Docker Compose wurde nicht gefunden."
}

run_local() {
    preflight_local

    if [[ "$DO_BUILD" -eq 1 ]]; then
        run_frontend_build
    else
        warn "Build übersprungen"
    fi

    if [[ "$DO_MIGRATE" -eq 1 ]]; then
        run_cmd "Führe Migrationen aus" "$PHP_BIN" artisan migrate --force
    else
        warn "Migrationen übersprungen"
    fi

    if [[ "$DO_RELOAD" -eq 1 ]]; then
        run_cmd "Leere Laravel-Caches" "$PHP_BIN" artisan optimize:clear
        run_cmd "Baue Config-Cache" "$PHP_BIN" artisan config:cache
        run_cmd "Baue Route-Cache" "$PHP_BIN" artisan route:cache
        run_cmd "Baue View-Cache" "$PHP_BIN" artisan view:cache
        run_cmd "Starte Queue-Worker sauber neu" "$PHP_BIN" artisan queue:restart
    else
        warn "Reload und Caches übersprungen"
    fi
}

run_docker() {
    preflight_docker

    if [[ "$DO_BUILD" -eq 1 ]]; then
        run_shell_cmd "Baue Container neu" "$COMPOSE_CMD build"
    else
        warn "Container-Build übersprungen"
    fi

    run_shell_cmd "Starte Container" "$COMPOSE_CMD up -d --remove-orphans"

    if [[ "$DO_MIGRATE" -eq 1 ]]; then
        run_shell_cmd "Führe Migrationen im Container aus" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan migrate --force"
    else
        warn "Migrationen im Container übersprungen"
    fi

    if [[ "$DO_RELOAD" -eq 1 ]]; then
        run_shell_cmd "Leere Laravel-Caches im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan optimize:clear"
        run_shell_cmd "Baue Config-Cache im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan config:cache"
        run_shell_cmd "Baue Route-Cache im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan route:cache"
        run_shell_cmd "Baue View-Cache im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan view:cache"
        run_shell_cmd "Starte Queue-Worker im Container sauber neu" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan queue:restart"
    else
        warn "Reload und Caches im Container übersprungen"
    fi
}

parse_args "$@"
setup_colors

cd "$APP_DIR"
START_TS="$(date '+%s' 2>/dev/null || printf '0')"

banner
print_row "Projekt" "$APP_DIR"
print_row "Modus" "$MODE"
print_row "Build" "$([[ "$DO_BUILD" -eq 1 ]] && printf 'ja' || printf 'nein')"
print_row "Migration" "$([[ "$DO_MIGRATE" -eq 1 ]] && printf 'ja' || printf 'nein')"
print_row "Reload" "$([[ "$DO_RELOAD" -eq 1 ]] && printf 'ja' || printf 'nein')"
if [[ "$MODE" == "docker" ]]; then
    print_row "Service" "$WEB_SERVICE"
fi
[[ "$PLAIN_OUTPUT" -eq 1 ]] || printf '\n'

step "Starte Deploy-Workflow"

case "$MODE" in
    local)
        run_local
        ;;
    docker)
        run_docker
        ;;
    *)
        die "Ungültiger Modus: $MODE"
        ;;
esac

END_TS="$(date '+%s' 2>/dev/null || printf '0')"

if [[ "$START_TS" -gt 0 && "$END_TS" -ge "$START_TS" ]]; then
    success "Deploy abgeschlossen in $((END_TS - START_TS))s"
else
    success "Deploy abgeschlossen"
fi
