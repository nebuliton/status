#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Diese Werte kannst du bei Bedarf direkt hier überschreiben.
PHP_HOST="${PHP_HOST:-127.0.0.1}"
PHP_PORT="${PHP_PORT:-8000}"
VITE_HOST="${VITE_HOST:-127.0.0.1}"
OPEN_BROWSER="${OPEN_BROWSER:-0}"
START_SCHEDULER="${START_SCHEDULER:-0}"

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Befehl '$1' wurde nicht gefunden. Bitte installiere ihn oder prüfe deine PATH-Variable." >&2
    exit 1
  fi
}

cleanup() {
  local exit_code=$?
  trap - EXIT INT TERM

  if [[ -n "${PHP_PID:-}" ]]; then
    kill "$PHP_PID" >/dev/null 2>&1 || true
  fi

  if [[ -n "${VITE_PID:-}" ]]; then
    kill "$VITE_PID" >/dev/null 2>&1 || true
  fi

  if [[ -n "${SCHEDULER_PID:-}" ]]; then
    kill "$SCHEDULER_PID" >/dev/null 2>&1 || true
  fi

  exit "$exit_code"
}

trap cleanup EXIT INT TERM

if [[ ! -f "$PROJECT_ROOT/artisan" ]]; then
  echo "Im Projektverzeichnis wurde keine 'artisan'-Datei gefunden: $PROJECT_ROOT" >&2
  exit 1
fi

require_command php
require_command npm

printf "\nNebuliton Dev Launcher\n"
printf "======================\n"
printf "%-14s %s\n" "Projekt" "$PROJECT_ROOT"
printf "%-14s %s\n" "Laravel" "http://$PHP_HOST:$PHP_PORT"
printf "%-14s %s\n" "Vite" "http://$VITE_HOST:5173"
if [[ "$START_SCHEDULER" == "1" ]]; then
  printf "%-14s %s\n" "Scheduler" "aktiv"
fi
printf "\n"

cd "$PROJECT_ROOT"

php artisan serve --host="$PHP_HOST" --port="$PHP_PORT" &
PHP_PID=$!

npm run dev -- --host "$VITE_HOST" &
VITE_PID=$!

if [[ "$START_SCHEDULER" == "1" ]]; then
  php artisan schedule:work &
  SCHEDULER_PID=$!
fi

if [[ "$OPEN_BROWSER" == "1" ]]; then
  if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "http://$PHP_HOST:$PHP_PORT" >/dev/null 2>&1 &
  elif command -v open >/dev/null 2>&1; then
    open "http://$PHP_HOST:$PHP_PORT" >/dev/null 2>&1 &
  fi
fi

wait -n "$PHP_PID" "$VITE_PID"
