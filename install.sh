#!/usr/bin/env bash

set -euo pipefail

REPOSITORY_URL="${REPOSITORY_URL:-https://github.com/nebuliton/status.git}"
BRANCH="${BRANCH:-main}"
INSTALL_PATH="${INSTALL_PATH:-/var/www/nebuliton-status}"
APP_NAME="${APP_NAME:-Nebuliton Status}"
APP_ENVIRONMENT="${APP_ENVIRONMENT:-production}"
APP_URL="${APP_URL:-http://localhost}"
APP_DEBUG="${APP_DEBUG:-false}"
DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-status}"
DB_USERNAME="${DB_USERNAME:-status}"
DB_PASSWORD="${DB_PASSWORD:-}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"
CACHE_STORE="${CACHE_STORE:-database}"
SESSION_DRIVER="${SESSION_DRIVER:-database}"
MAIL_MAILER="${MAIL_MAILER:-log}"
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-noreply@nebuliton.local}"
MAIL_FROM_NAME="${MAIL_FROM_NAME:-Nebuliton Status}"
RUN_SEED=0
NO_INTERACTION=0
FORCE=0

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

usage() {
    cat <<'EOF'
Verwendung:
  ./install.sh [optionen]

Optionen:
  --repo-url URL            Git-Repository
  --branch NAME             Git-Branch
  --path PFAD               Installationspfad
  --app-url URL             Öffentliche App-URL
  --app-name NAME           Anzeigename
  --app-env WERT            Laravel-Umgebung
  --app-debug true|false    APP_DEBUG setzen
  --db-host HOST            Datenbank-Host
  --db-port PORT            Datenbank-Port
  --db-name NAME            Datenbankname
  --db-user NAME            Datenbank-Benutzer
  --db-password PASSWORT    Datenbank-Passwort
  --queue WERT              QUEUE_CONNECTION setzen
  --cache-store WERT        CACHE_STORE setzen
  --session-driver WERT     SESSION_DRIVER setzen
  --mail-mailer WERT        MAIL_MAILER setzen
  --mail-from ADRESSE       MAIL_FROM_ADDRESS setzen
  --mail-name NAME          MAIL_FROM_NAME setzen
  --seed                    Seeder nach Migration ausführen
  --no-interaction          Keine Rückfragen stellen
  --force                   Vorhandenes Verzeichnis ohne Rückfrage verwenden
  -h, --help                Hilfe anzeigen

Beispiele:
  ./install.sh
  ./install.sh --path /var/www/nebuliton-status --app-url https://status.example.com
  ./install.sh --no-interaction --db-host 127.0.0.1 --db-name status --db-user status --db-password geheim
EOF
}

banner() {
    printf '\n%sNebuliton Installer%s\n' "$C_BLUE" "$C_RESET"
    printf '%s───────────────────%s\n' "$C_DIM" "$C_RESET"
}

print_row() {
    printf '%b%-16s%b %s\n' "$C_DIM" "$1" "$C_RESET" "$2"
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

require_command() {
    resolve_command "$1" >/dev/null || die "Befehl '$1' wurde nicht gefunden."
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

ask() {
    local prompt="$1"
    local default_value="$2"
    local result

    if [[ "$NO_INTERACTION" -eq 1 ]]; then
        printf '%s' "$default_value"
        return
    fi

    read -r -p "$prompt [$default_value]: " result
    printf '%s' "${result:-$default_value}"
}

ask_secret() {
    local prompt="$1"
    local default_value="$2"
    local result

    if [[ "$NO_INTERACTION" -eq 1 ]]; then
        printf '%s' "$default_value"
        return
    fi

    read -r -s -p "$prompt [versteckt]: " result
    printf '\n'
    if [[ -z "$result" ]]; then
        printf '%s' "$default_value"
        return
    fi

    printf '%s' "$result"
}

confirm() {
    local prompt="$1"

    if [[ "$NO_INTERACTION" -eq 1 || "$FORCE" -eq 1 ]]; then
        return 0
    fi

    read -r -p "$prompt [y/N]: " result
    [[ "${result:-}" =~ ^[Yy]$ ]]
}

run_cmd() {
    local description="$1"
    shift

    step "$description"
    printf '%s$ %s%s\n' "$C_DIM" "$*" "$C_RESET"
    "$@"
}

set_env_value() {
    local file="$1"
    local key="$2"
    local value="$3"
    local escaped_value

    escaped_value="$(printf '%s' "$value" | sed -e 's/[\/&]/\\&/g')"

    if grep -q "^${key}=" "$file"; then
        sed -i "s/^${key}=.*/${key}=${escaped_value}/" "$file"
    else
        printf '%s=%s\n' "$key" "$value" >> "$file"
    fi
}

ensure_storage_permissions() {
    mkdir -p "$INSTALL_PATH/storage" "$INSTALL_PATH/bootstrap/cache"
    chmod -R ug+rwX "$INSTALL_PATH/storage" "$INSTALL_PATH/bootstrap/cache" 2>/dev/null || true
}

configure_env() {
    local env_file="$INSTALL_PATH/.env"

    [[ -f "$env_file" ]] || cp "$INSTALL_PATH/.env.example" "$env_file"

    set_env_value "$env_file" "APP_NAME" "\"$APP_NAME\""
    set_env_value "$env_file" "APP_ENV" "$APP_ENVIRONMENT"
    set_env_value "$env_file" "APP_URL" "$APP_URL"
    set_env_value "$env_file" "APP_DEBUG" "$APP_DEBUG"
    set_env_value "$env_file" "DB_CONNECTION" "$DB_CONNECTION"
    set_env_value "$env_file" "DB_HOST" "$DB_HOST"
    set_env_value "$env_file" "DB_PORT" "$DB_PORT"
    set_env_value "$env_file" "DB_DATABASE" "$DB_DATABASE"
    set_env_value "$env_file" "DB_USERNAME" "$DB_USERNAME"
    set_env_value "$env_file" "DB_PASSWORD" "$DB_PASSWORD"
    set_env_value "$env_file" "QUEUE_CONNECTION" "$QUEUE_CONNECTION"
    set_env_value "$env_file" "CACHE_STORE" "$CACHE_STORE"
    set_env_value "$env_file" "SESSION_DRIVER" "$SESSION_DRIVER"
    set_env_value "$env_file" "MAIL_MAILER" "$MAIL_MAILER"
    set_env_value "$env_file" "MAIL_FROM_ADDRESS" "$MAIL_FROM_ADDRESS"
    set_env_value "$env_file" "MAIL_FROM_NAME" "\"$MAIL_FROM_NAME\""
}

parse_args() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --repo-url)
                shift
                REPOSITORY_URL="${1:-}"
                ;;
            --branch)
                shift
                BRANCH="${1:-}"
                ;;
            --path)
                shift
                INSTALL_PATH="${1:-}"
                ;;
            --app-url)
                shift
                APP_URL="${1:-}"
                ;;
            --app-name)
                shift
                APP_NAME="${1:-}"
                ;;
            --app-env)
                shift
                APP_ENVIRONMENT="${1:-}"
                ;;
            --app-debug)
                shift
                APP_DEBUG="${1:-}"
                ;;
            --db-host)
                shift
                DB_HOST="${1:-}"
                ;;
            --db-port)
                shift
                DB_PORT="${1:-}"
                ;;
            --db-name)
                shift
                DB_DATABASE="${1:-}"
                ;;
            --db-user)
                shift
                DB_USERNAME="${1:-}"
                ;;
            --db-password)
                shift
                DB_PASSWORD="${1:-}"
                ;;
            --queue)
                shift
                QUEUE_CONNECTION="${1:-}"
                ;;
            --cache-store)
                shift
                CACHE_STORE="${1:-}"
                ;;
            --session-driver)
                shift
                SESSION_DRIVER="${1:-}"
                ;;
            --mail-mailer)
                shift
                MAIL_MAILER="${1:-}"
                ;;
            --mail-from)
                shift
                MAIL_FROM_ADDRESS="${1:-}"
                ;;
            --mail-name)
                shift
                MAIL_FROM_NAME="${1:-}"
                ;;
            --seed)
                RUN_SEED=1
                ;;
            --no-interaction)
                NO_INTERACTION=1
                ;;
            --force)
                FORCE=1
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

parse_args "$@"

require_command git
require_command php
require_command composer
require_command npm

GIT_BIN="$(resolve_command git)"
PHP_BIN="$(resolve_command php)"
COMPOSER_BIN="$(resolve_command composer)"
NPM_BIN="$(resolve_command npm)"

banner

if [[ "$NO_INTERACTION" -eq 0 ]]; then
    REPOSITORY_URL="$(ask 'Repository-URL' "$REPOSITORY_URL")"
    BRANCH="$(ask 'Git-Branch' "$BRANCH")"
    INSTALL_PATH="$(ask 'Installationspfad' "$INSTALL_PATH")"
    APP_NAME="$(ask 'App-Name' "$APP_NAME")"
    APP_URL="$(ask 'App-URL' "$APP_URL")"
    APP_ENVIRONMENT="$(ask 'APP_ENV' "$APP_ENVIRONMENT")"
    APP_DEBUG="$(ask 'APP_DEBUG' "$APP_DEBUG")"
    DB_HOST="$(ask 'DB_HOST' "$DB_HOST")"
    DB_PORT="$(ask 'DB_PORT' "$DB_PORT")"
    DB_DATABASE="$(ask 'DB_DATABASE' "$DB_DATABASE")"
    DB_USERNAME="$(ask 'DB_USERNAME' "$DB_USERNAME")"
    DB_PASSWORD="$(ask_secret 'DB_PASSWORD' "$DB_PASSWORD")"
    QUEUE_CONNECTION="$(ask 'QUEUE_CONNECTION' "$QUEUE_CONNECTION")"
    CACHE_STORE="$(ask 'CACHE_STORE' "$CACHE_STORE")"
    SESSION_DRIVER="$(ask 'SESSION_DRIVER' "$SESSION_DRIVER")"
    MAIL_MAILER="$(ask 'MAIL_MAILER' "$MAIL_MAILER")"
    MAIL_FROM_ADDRESS="$(ask 'MAIL_FROM_ADDRESS' "$MAIL_FROM_ADDRESS")"
    MAIL_FROM_NAME="$(ask 'MAIL_FROM_NAME' "$MAIL_FROM_NAME")"
fi

print_row "Repository" "$REPOSITORY_URL"
print_row "Branch" "$BRANCH"
print_row "Pfad" "$INSTALL_PATH"
print_row "App-URL" "$APP_URL"
print_row "DB" "$DB_CONNECTION://$DB_USERNAME@$DB_HOST:$DB_PORT/$DB_DATABASE"
print_row "Seeder" "$([[ "$RUN_SEED" -eq 1 ]] && printf 'ja' || printf 'nein')"
printf '\n'

if [[ -d "$INSTALL_PATH" && ! -d "$INSTALL_PATH/.git" ]]; then
    confirm "Das Zielverzeichnis existiert bereits ohne Git-Repository. Trotzdem verwenden?" || die "Installation abgebrochen."
fi

mkdir -p "$INSTALL_PATH"

if [[ ! -d "$INSTALL_PATH/.git" ]]; then
    run_cmd "Klonen des Repositorys" "$GIT_BIN" clone --branch "$BRANCH" "$REPOSITORY_URL" "$INSTALL_PATH"
else
    run_cmd "Aktualisiere vorhandenes Repository" "$GIT_BIN" -C "$INSTALL_PATH" fetch --all --prune
    run_cmd "Wechsle auf Branch $BRANCH" "$GIT_BIN" -C "$INSTALL_PATH" checkout "$BRANCH"
    run_cmd "Ziehe aktuellen Stand" "$GIT_BIN" -C "$INSTALL_PATH" pull --ff-only origin "$BRANCH"
fi

cd "$INSTALL_PATH"

[[ -f .env.example ]] || die ".env.example fehlt im Projekt."

step "Konfiguriere .env"
configure_env

run_cmd "Installiere Composer-Abhängigkeiten" "$COMPOSER_BIN" install --no-interaction --prefer-dist --optimize-autoloader
run_cmd "Installiere Node-Abhängigkeiten" "$NPM_BIN" install
run_cmd "Erzeuge App-Key" "$PHP_BIN" artisan key:generate --force
run_cmd "Erzeuge Storage-Link" "$PHP_BIN" artisan storage:link || true

ensure_storage_permissions

run_cmd "Führe Migrationen aus" "$PHP_BIN" artisan migrate --force

if [[ "$RUN_SEED" -eq 1 ]]; then
    run_cmd "Führe Seeder aus" "$PHP_BIN" artisan db:seed --force
fi

run_cmd "Baue Frontend-Assets" "$NPM_BIN" run build
run_cmd "Leere Laravel-Caches" "$PHP_BIN" artisan optimize:clear
run_cmd "Baue Produktions-Caches" "$PHP_BIN" artisan optimize

printf '\n'
success "Installation abgeschlossen"
print_row "Pfad" "$INSTALL_PATH"
print_row "App-URL" "$APP_URL"
print_row "Admin" "$APP_URL/admin"
print_row "Status" "$APP_URL/"
printf '\n'
printf '%sNächste Schritte:%s\n' "$C_WHITE" "$C_RESET"
printf '%s1.%s Webserver oder Reverse-Proxy auf %s zeigen lassen.\n' "$C_DIM" "$C_RESET" "$INSTALL_PATH/public"
printf '%s2.%s Scheduler einrichten: * * * * * cd %s && php artisan schedule:run\n' "$C_DIM" "$C_RESET" "$INSTALL_PATH"
printf '%s3.%s Optional Queue-Worker oder Supervisor ergänzen.\n' "$C_DIM" "$C_RESET"
