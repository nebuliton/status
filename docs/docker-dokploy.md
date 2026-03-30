# Docker & Dokploy Deployment

Diese Anwendung kann als Docker-Compose-Service in Dokploy betrieben werden.

Die bereitgestellte Compose-Struktur trennt:

- `web`: Nginx als öffentlicher HTTP-Endpunkt
- `app`: PHP-FPM / Laravel-Anwendung
- `queue`: Queue-Worker
- `scheduler`: Laravel-Scheduler

## Warum diese Struktur?

Dokploy empfiehlt für Docker-Compose-Deployments:

- Umgebungsvariablen per `.env` und `env_file`
- benannte Volumes für persistente Daten und Backups
- bei Domains für Compose den Dienst über die Dokploy-Domain-Konfiguration zu routen

Offizielle Quellen:

- Docker Compose in Dokploy: https://docs.dokploy.com/docs/core/docker-compose
- Domains für Docker Compose: https://docs.dokploy.com/docs/core/docker-compose/domains
- Auto Deploy: https://docs.dokploy.com/docs/core/auto-deploy

## Was ist persistent?

Das benannte Volume `app_storage` speichert:

- hochgeladene Dateien
- Service-Icons
- Laravel-Logs
- Laufzeitdaten unter `storage/`

Wenn du in Dokploy Volume Backups nutzen willst, ist genau das die richtige Variante, weil Dokploy dafür benannte Volumes empfiehlt.

## Vorbereitung in Dokploy

1. Repository als Docker-Compose-Service verbinden.
2. Als Compose-Datei die Projektdatei `docker-compose.yml` verwenden.
3. Im Tab `Environment` alle benötigten Laravel-Variablen setzen.
4. Im Tab `Domains` die Domain auf den Service `web` und Port `80` legen.

## Empfohlene Umgebungsvariablen

Mindestens:

```env
APP_NAME="Nebuliton Status"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://status.example.com
APP_KEY=base64:DEIN_KEY

APP_LOCALE=de
APP_FALLBACK_LOCALE=de

DB_CONNECTION=mysql
DB_HOST=dein-mysql-host
DB_PORT=3306
DB_DATABASE=nebuliton_status
DB_USERNAME=nebuliton_status
DB_PASSWORD=starkes-passwort

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=dein-user
MAIL_PASSWORD=dein-passwort
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Nebuliton Status"

NEBULITON_SHOP_URL=https://shop.nebuliton.com
NEBULITON_CONTROL_PANEL_URL=/admin

APP_RUN_OPTIMIZE=true
APP_RUN_MIGRATIONS=false
```

## MySQL in Dokploy

Empfehlung:

- Die Datenbank nicht in denselben Compose-Stack legen.
- Stattdessen in Dokploy eine eigene MySQL-Datenbank anlegen oder eine externe MySQL-Instanz verwenden.

Das passt besser zur Projektarchitektur mit zentraler MySQL-Datenbank.

## Erster produktiver Start

Für den ersten Deploy gibt es zwei saubere Wege:

### Variante A: Migration einmal manuell ausführen

Nach dem ersten erfolgreichen Deploy:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

Danach `APP_RUN_MIGRATIONS=false` lassen.

### Variante B: Einmalig Auto-Migration aktivieren

Vor dem ersten Deploy temporär setzen:

```env
APP_RUN_MIGRATIONS=true
```

Deploy durchführen, danach sofort wieder auf `false` zurückstellen.

Wichtig:

- `db:seed` solltest du bewusst manuell ausführen
- Der Seeder legt den Initial-Admin an, der danach entfernt oder abgesichert werden muss

## Domain in Dokploy

Nutze in Dokploy für Compose die native Domain-Verwaltung.

Empfohlene Zuordnung:

- Service: `web`
- Port: `80`

Du musst dafür keine Traefik-Labels manuell in `docker-compose.yml` pflegen.

## Auto Deploy

Dokploy unterstützt Auto Deploy auch für Docker-Compose-Services. Nach Einrichtung des Repositories kannst du Auto Deploy direkt im Service aktivieren.

## Lokales Testen mit Docker

Wenn du lokal bereits eine MySQL-Datenbank hast:

```bash
cp .env.example .env
docker compose build
docker compose up -d
```

Danach Migrationen:

```bash
docker compose exec app php artisan migrate --force
```

Optional Seeder:

```bash
docker compose exec app php artisan db:seed --force
```

## Updates

Wenn du das Projekt außerhalb von Dokploy selbst mit Docker Compose aktualisieren willst:

```bash
./deploy.sh docker --service=app
```

Für Dokploy selbst genügt normalerweise ein neuer Deploy über Git oder Auto Deploy.
