# Update-, Deploy- und Installationssystem

## Zweck dieses Dokuments

Dieses Dokument beschreibt das in `nebu-secure` umgesetzte Update-System vollständig und so, dass ein anderer Entwickler es als Vorlage für ein anderes Produkt übernehmen kann.

Wichtiger Hinweis: Dieses Dokument ist bewusst als eigenständige Übergabe-Datei gedacht. Der Leser soll das System verstehen und nachbauen können, auch wenn er keine anderen Projektdateien sieht. Deshalb enthält dieses Dokument nicht nur Beschreibung, sondern weiter unten auch konkrete Kopiervorlagen und eingebettete Skript-Beispiele.

Es geht dabei um vier Bereiche:

1. Wie Releases erkannt werden
2. Wie Updates sicher eingespielt werden
3. Wie Deployments lokal oder per Docker ausgeführt werden
4. Wie ein Server per Installer komplett vorbereitet wird

Das System wurde so gebaut, dass es produktionsnah funktioniert, aber trotzdem verständlich, manuell testbar und über das Admin-Dashboard bedienbar bleibt.

## Designziele

Die Architektur wurde auf folgende Ziele ausgelegt:

- Updates sollen nur bei echten Releases laufen, nicht bei jedem beliebigen Commit.
- Nur bewusst freigegebene Projektbereiche dürfen automatisch überschrieben werden.
- Kritische Dateien wie `.env`, `storage`, `vendor`, `node_modules` oder `.git` dürfen niemals aus einem Release überschrieben werden.
- Der gleiche Mechanismus soll per Dashboard, CLI und Scheduler nutzbar sein.
- Jeder Update-Lauf soll nachvollziehbar protokolliert werden.
- Fehler sollen nicht als rohe Shell-Ausgabe enden, sondern möglichst verständlich normalisiert werden.
- Ein erfolgreicher Update-Lauf soll die Anwendung direkt in einen konsistenten Zustand bringen, also inklusive Build, Migrationen, Cache-Aufbau und Queue-Restart.

## Architekturüberblick

Das System besteht aus mehreren Schichten:

### 1. Release-Metadaten

Datei: `version.json`

Sie definiert:

- die aktuelle Release-Version
- den Update-Kanal
- den Git-Branch
- die erlaubten Update-Pfade

Beispiel:

```json
{
  "version": "0.1.11",
  "channel": "stable",
  "branch": "main",
  "update_paths": [
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "tests",
    "artisan",
    "composer.json",
    "composer.lock",
    "package.json",
    "package-lock.json",
    "Dockerfile",
    "docker-compose.yml",
    "deploy.sh",
    "install.sh",
    "README.md",
    "update.sh",
    "eslint.config.js",
    "phpunit.xml",
    "tsconfig.json",
    "vite.config.ts",
    "version.json"
  ]
}
```

Wichtig: Das System reagiert nicht auf jeden neuen Commit, sondern nur auf eine höhere `version`.

Das heißt:

- gleicher Commit, gleiche Version: kein Update
- anderer Commit, gleiche Version: ebenfalls kein Release
- höhere Version: Update wird als freigegeben betrachtet

Diese Entscheidung ist bewusst. Sie trennt Entwicklungs-Commits von echten Releases.

### 2. Manifest-Validierung

Klasse: `App\Services\VersionManifestService`

Diese Klasse liest und validiert `version.json`.

Sie hat drei zentrale Aufgaben:

- lokale Manifest-Datei laden
- Remote-Manifest parsen
- erlaubte Update-Pfade validieren

Das Sicherheitsmodell ist restriktiv:

- ganze Wurzelverzeichnisse wie `app`, `resources`, `routes`, `config` sind erlaubt
- einzelne Root-Dateien wie `composer.json`, `deploy.sh`, `version.json` sind erlaubt
- Shell-Skripte im Root können zusätzlich per Pattern freigegeben werden
- gefährliche Bereiche wie `.env`, `.git`, `storage`, `vendor`, `node_modules`, `bootstrap/cache` sind explizit verboten

Damit wird verhindert, dass ein Release versehentlich Laufzeitdaten oder Secrets überschreibt.

### 3. Update-Orchestrierung

Klasse: `App\Services\ApplicationUpdateService`

Das ist der Kern des Systems.

Sie macht zwei Dinge:

- `status()`: prüft den aktuellen Zustand, ohne zu ändern
- `run()`: führt einen Update-Lauf wirklich aus

Der Service arbeitet direkt mit Git und Shell-Prozessen und kapselt dabei:

- Versionsvergleich
- Git-Fetch
- Ermittlung geänderter Dateien
- Blockieren unsicherer Dateien
- optionales `composer install`
- optionales `npm install`
- Ausführung des Deploy-Skripts
- Logging und Persistenz des Laufes

### 4. Persistente Update-Läufe

Tabelle: `update_runs`

Gespeichert werden:

- auslösender Benutzer
- Modus: `manual` oder `automatic`
- Status: `running`, `succeeded`, `failed`, `skipped`
- lokale und Ziel-Version
- lokale und Ziel-Commits
- geänderte Dateien
- Zusammenfassung
- vollständiger Log-Output
- Start- und Endzeit

Damit ist jeder Lauf später im Admin-Bereich nachvollziehbar.

### 5. API und Dashboard

Controller: `App\Http\Controllers\Api\Admin\ApplicationUpdateController`

Routen:

- `GET /api/admin/updates`
- `POST /api/admin/updates/run`
- `PATCH /api/admin/updates/preferences`
- `GET /api/admin/updates/runs/{runId}`

Frontend-Komponente:

- `resources/js/components/admin/update-management-panel.tsx`

Das Dashboard zeigt:

- installierte Version
- verfügbare Remote-Version
- Branch und Repository
- erlaubte Update-Pfade
- blockierende lokale Änderungen
- blockierte Dateien
- letzte Update-Läufe
- vollständige Logs

Nach einem erfolgreichen Update lädt die Seite automatisch neu, damit direkt die frischen Assets und der neue Code geladen werden.

### 6. CLI-Schicht

Es gibt zwei Ebenen:

- `php artisan app:update`
- `./update.sh`

`update.sh` ist nur ein dünner Shell-Wrapper. Die eigentliche Logik liegt vollständig in Laravel.

### 7. Deploy-Schicht

Datei: `deploy.sh`

Sie führt nach einem erfolgreichen Git-Update den eigentlichen technischen Rollout aus.

### 8. Installer-Schicht

Datei: `install.sh`

Sie setzt einen neuen Server vollständig auf und bereitet ihn für das Update-System vor.

## Lebenszyklus eines Releases

### Release erzeugen

Ein neues Release entsteht in diesem System so:

1. Code ändern
2. Tests und Build prüfen
3. `version.json` hochzählen
4. pushen

Ohne Versionssprung erkennt das System bewusst kein neues Release.

### Update prüfen

`ApplicationUpdateService::status()` macht im Wesentlichen Folgendes:

1. Lokales `version.json` lesen
2. `git remote get-url origin`
3. `git rev-parse --abbrev-ref HEAD`
4. `git rev-parse HEAD`
5. lokale tracked Änderungen ermitteln
6. `git fetch --quiet origin <branch>`
7. Remote-`version.json` lesen über `git show origin/<branch>:version.json`
8. Remote-Commit lesen
9. geänderte Dateien zwischen `HEAD` und `origin/<branch>` ermitteln
10. geänderte Dateien gegen `update_paths` prüfen
11. Versionsnummern vergleichen

Das Ergebnis enthält dann:

- `healthy`
- `update_available`
- `can_update`
- `tracked_changes`
- `changed_files`
- `blocked_files`
- `local`
- `remote`
- `error`

### Update ausführen

`ApplicationUpdateService::run()` läuft grob in dieser Reihenfolge:

1. optional prüfen, ob Auto-Update aktiviert ist
2. Lock ziehen, damit nie zwei Updates parallel laufen
3. `update_runs`-Datensatz mit Status `running` anlegen
4. Remote-Manifest lesen
5. Versionsvergleich durchführen
6. lokale Änderungen blockieren
7. geänderte Dateien gegen erlaubte Pfade prüfen
8. `git pull --ff-only origin <branch>`
9. falls nötig `composer install`
10. falls nötig `npm install`
11. `bash ./deploy.sh --plain`
12. finalen Stand erneut lesen
13. Lauf als erfolgreich oder fehlgeschlagen speichern
14. Audit-Log schreiben

Wichtig ist dabei:

- Es wird nur `--ff-only` verwendet.
- Das System macht keine riskanten Merges.
- Ein Update bricht lieber ab, als stillschweigend unklare Zustände zu erzeugen.

## Warum `version.json` statt nur Git-Commit?

Ein reiner Commit-Vergleich ist für Produkt-Releases zu grob.

Probleme eines reinen Commit-Ansatzes:

- jeder Push würde sofort als Update gelten
- Hotfix-Branches und Zwischenstände wären schwer steuerbar
- Entwickler könnten unfertige Commits ausrollen

Vorteile des `version.json`-Ansatzes:

- Release-Freigabe ist explizit
- UI und CLI können klare Versionsstände zeigen
- derselbe Branch kann viele Commits enthalten, aber nur wenige echte Releases
- andere Produkte können denselben Mechanismus übernehmen, ohne Git-Tags erzwingen zu müssen

## Sicherheitsmodell des Updates

Das System ist absichtlich konservativ.

### Was überschrieben werden darf

Erlaubt sind bewusst nur:

- Anwendungscode
- Konfiguration im Repo
- Frontend-Quellen
- Migrationsdateien
- Tests
- Deploy- und Update-Skripte
- Build-Dateien wie `composer.lock`, `package-lock.json`

### Was niemals überschrieben werden darf

Explizit blockiert sind:

- `.env`
- `.git`
- `storage`
- `vendor`
- `node_modules`
- `bootstrap/cache`
- `public/storage`

Der Grund:

- `.env` enthält Secrets
- `.git` ist Metadaten- und Schreibbereich
- `storage` enthält Laufzeitdaten und Uploads
- `vendor` und `node_modules` sind Build-Artefakte, nicht Release-Quelle
- `bootstrap/cache` ist Laufzeitcache

## Umgang mit lokalen Änderungen

Ein Update wird blockiert, wenn `git status --porcelain --untracked-files=no` tracked Änderungen zeigt.

Das ist wichtig, weil sonst:

- lokale Server-Hotfixes überschrieben würden
- `git pull` scheitern könnte
- der neue Zustand nicht deterministisch wäre

Bewusst ignoriert werden untracked Dateien. Das ist sinnvoll, weil auf Servern oft Log- oder Hilfsdateien existieren, die das Release nicht betreffen.

## Logging-Konzept

Jeder Update-Lauf protokolliert:

- Start
- Ziel-Repository
- lokale Version
- jeden technischen Schritt
- ausgeführte Kommandos
- relevante Ausgabe
- Fehler
- Abschlussstatus

Diese Logs werden:

- in `update_runs.log_output` gespeichert
- im Dashboard angezeigt
- für CLI-Läufe direkt gestreamt

Zusätzlich werden bekannte Git- und Build-Fehler in verständliche Meldungen übersetzt.

## Fehlernormalisierung

Ein großer praktischer Teil der Arbeit bestand nicht aus der Grundfunktion, sondern aus robuster Fehlerbehandlung.

Typische Fälle, die explizit abgefangen werden:

- `detected dubious ownership in repository`
- `.git/FETCH_HEAD: Permission denied`
- `.git/objects ... insufficient permission`
- nicht vorhandenes `origin`
- Remote ohne `version.json`
- nicht erlaubte Update-Pfade
- Vite-Permission-Probleme
- `esbuild`-Execute-Probleme

Statt roher Low-Level-Ausgaben zeigt das System möglichst konkrete Hinweise wie:

- welcher Benutzer keine Rechte hat
- ob das Problem an `.git` liegt
- ob ein manueller Bootstrap nötig ist
- ob `node_modules`-Rechte beschädigt wurden

Das ist wichtig, weil Shell- und Git-Fehler für Admins sonst oft kaum verständlich sind.

## Warum der Deploy-Schritt separat in `deploy.sh` liegt

Das Update-System zieht nur den Code und stößt danach bewusst ein separates Deploy-Skript an.

Das ist eine gute Trennung:

- `ApplicationUpdateService` entscheidet, ob und wann ein Update erlaubt ist
- `deploy.sh` entscheidet, wie die technische Ausrollung aussieht

Dadurch bleibt das System austauschbar:

- andere Projekte können denselben Update-Mechanismus behalten
- aber ein anderes Deploy-Verhalten definieren

## Detaillierte Erklärung von `deploy.sh`

`deploy.sh` ist ein bewusst generisches Rollout-Skript.

### Unterstützte Modi

- `local`
- `docker`

### Wichtige Optionen

- `--skip-build`
- `--skip-migrate`
- `--skip-reload`
- `--service`
- `--no-color`
- `--plain`

### Aufgabe des Skripts

Im lokalen Modus:

1. Frontend bauen
2. Migrationen ausführen
3. Laravel-Caches leeren
4. Produktions-Caches neu bauen
5. Queue sauber neu starten

Im Docker-Modus:

1. Container bauen
2. Container starten
3. Migrationen im Web-Container ausführen
4. Caches im Web-Container neu bauen
5. Queue im Container neu starten

### Warum `--plain` wichtig ist

Das Dashboard speichert Logs. Ein riesiges ASCII-Banner oder übermäßig dekorierte Terminalausgabe macht diese Logs schlechter lesbar.

Darum gibt es `--plain`:

- kein Banner
- keine unnötige Show
- klare Zeilen mit Zeitstempeln

### Warum der Frontend-Build direkt per Node gestartet wird

Es gab in der Praxis Probleme mit:

- fehlenden Execute-Rechten auf `node_modules/.bin/vite`
- fehlenden Execute-Rechten auf `@esbuild/.../bin/esbuild`
- temporären Vite-Dateien

Deshalb macht `deploy.sh` vor dem Build gezielt Folgendes:

- Execute-Bits in `node_modules/.bin` reparieren
- `node_modules/vite/bin/vite.js` reparieren
- `node_modules/@esbuild/*/bin/esbuild` reparieren
- Vite direkt via `node .../vite.js build --configLoader runner` ausführen

Das ist robuster als `npx vite`.

## Detaillierte Erklärung von `update.sh`

`update.sh` ist absichtlich klein.

Es macht nur:

```sh
php artisan app:update "$@"
```

Der Mehrwert ist nicht technische Logik, sondern Bedienbarkeit:

- einfacher Einstieg für Admins
- konsistente Shell-Schnittstelle
- dokumentierbarer Server-Befehl

Unterstützte Optionen:

- `--check`
- `--auto`
- `--json`

## Detaillierte Erklärung von `install.sh`

`install.sh` ist ein interaktiver Voll-Installer für Debian und Ubuntu.

### Ziel des Installers

Ein neuer Server soll ohne manuelles Zusammenklicken aller Einzelschritte produktionsbereit werden.

### Was das Skript fragt

- Repository-URL
- Branch
- Installationspfad
- Systembenutzer
- Anwendungsname
- Domain
- zusätzliche Server-Namen
- HTTPS und Let’s Encrypt
- `APP_ENV`
- Cookie-Domain
- zusätzliche Sanctum-Domains
- Datenbanktyp
- MariaDB ja/nein
- Datenbank-Zugangsdaten
- Mail-Modus und SMTP-Daten
- erster Admin-Benutzer

### Was das Skript automatisch macht

- Root-Prüfung
- OS-Erkennung
- Paketinstallation
- PHP 8.2, Nginx, Composer, Node.js installieren
- optional MariaDB installieren
- vorhandene Installation sichern
- Repository klonen
- `.env` schreiben
- `APP_KEY` generieren
- `VAULT_MASTER_KEYS` generieren
- Datenbank anlegen
- Composer- und Node-Abhängigkeiten installieren
- Frontend bauen
- Migrationen ausführen
- Caches bauen
- Storage-Link anlegen
- ersten Admin anlegen
- Nginx konfigurieren
- optional Let’s Encrypt einrichten
- Scheduler-Cron schreiben
- Queue-Worker als `systemd`-Service anlegen
- Rechte setzen

### Warum der Installer auch für das Update-System relevant ist

Ein Update-System funktioniert nur dann sauber, wenn der Zielserver konsistent vorbereitet wurde.

Dazu gehören:

- korrekter Git-Checkout
- funktionierende PHP- und Node-Toolchain
- richtige Benutzer und Rechte
- Scheduler
- Queue-Service
- bestehende `.env`

`install.sh` löst genau diese Voraussetzungen.

## Scheduler und Auto-Update

Auto-Updates laufen nicht “magisch”, sondern über den Laravel-Scheduler.

In `routes/console.php` ist definiert:

```php
Schedule::command('app:update --auto')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
```

Das bedeutet:

- alle 15 Minuten wird geprüft
- nur wenn `auto_update_enabled = 1`, wird wirklich aktualisiert
- parallele Läufe werden verhindert

Zusätzlich muss auf dem Server natürlich `schedule:run` per Cron aktiv sein.

## Dashboard-Fluss

Im Dashboard passiert technisch Folgendes:

### Status laden

- `GET /api/admin/updates`
- liefert den kompletten Update-Zustand

### Update starten

- `POST /api/admin/updates/run`
- triggert `ApplicationUpdateService::run()`

### Auto-Update ein- oder ausschalten

- `PATCH /api/admin/updates/preferences`

### Log-Detail laden

- `GET /api/admin/updates/runs/{runId}`

### Nach erfolgreichem Update

Die Oberfläche zeigt:

- Erfolgsmeldung
- automatische Neuladung der Seite

Warum der Reload wichtig ist:

- neue JS- und CSS-Bundles werden geladen
- die Admin-Seite läuft nicht auf altem Frontend-Code weiter
- der sichtbare Versionsstand aktualisiert sich sofort

## Warum dieses System in der Praxis gut funktioniert

Die Hauptstärken sind:

- klarer Release-Schnitt über `version.json`
- konservative Sicherheitsregeln
- nachvollziehbare Logs
- einheitlicher Kern für Dashboard, CLI und Scheduler
- saubere Trennung zwischen “Update entscheiden” und “Deploy ausführen”

Diese Trennung ist der entscheidende Architekturpunkt.

## Was man in einem anderen Produkt übernehmen sollte

Für ein anderes Produkt würde ich exakt dieselbe Grundstruktur empfehlen.

### Mindestbestandteile

1. Release-Manifest
2. Manifest-Validator
3. Update-Orchestrator
4. persistente Update-Läufe
5. Deploy-Skript
6. CLI-Wrapper
7. Admin-Oberfläche
8. Scheduler

### Mein empfohlener Minimalumfang

Wenn das andere Projekt kleiner ist, reicht für Version 1:

- `version.json`
- `UpdateService`
- `update_runs`
- `php artisan app:update`
- `deploy.sh`

Das Dashboard kann später ergänzt werden.

### Was projektspezifisch angepasst werden muss

Nicht alles sollte 1:1 kopiert werden.

Anpassen musst du:

- erlaubte Update-Pfade
- Deploy-Schritte
- Docker- oder Non-Docker-Betrieb
- Queue-Handling
- Installer
- Rechte- und Benutzerkonzept
- welche Dateien einen `composer install` oder `npm install` triggern

## Konkrete Implementierungsempfehlung für andere Produkte

### 1. Manifest zuerst bauen

Fang mit einem einfachen Manifest an:

```json
{
  "version": "1.0.0",
  "channel": "stable",
  "branch": "main",
  "update_paths": [
    "app",
    "config",
    "resources",
    "routes",
    "database",
    "public",
    "composer.json",
    "composer.lock",
    "package.json",
    "package-lock.json",
    "deploy.sh",
    "version.json"
  ]
}
```

### 2. Validator restriktiv halten

Niemals mit “alles außer X” arbeiten.

Immer besser:

- explizit erlauben
- standardmäßig blockieren

### 3. Nie `.env` oder `storage` automatisch überschreiben

Das ist einer der wichtigsten Punkte.

Wenn ein anderes Projekt diesen Fehler macht, wird aus einem Update-System sehr schnell ein Produktionsrisiko.

### 4. Immer `git pull --ff-only`

Keine automatischen Merges.

Wenn der Serverzustand nicht sauber ist, soll das Update abbrechen und nicht raten.

### 5. Logs immer persistent speichern

Terminalausgabe allein reicht nicht.

Gerade bei Auto-Updates muss später noch nachvollziehbar sein:

- wann lief das Update
- warum ist es gescheitert
- welche Dateien waren betroffen

### 6. Fehlermeldungen normalisieren

Ein sehr großer Qualitätsgewinn ist es, rohe Toolfehler in produktnahe Meldungen zu übersetzen.

Beispiel:

schlecht:

```text
error: insufficient permission for adding an object to repository database .git/objects
fatal: failed to write object
fatal: unpack-objects failed
```

besser:

```text
Der Deploy-Benutzer kann nicht in .git/objects schreiben. Git kann deshalb neue Objekte weder empfangen noch speichern.
```

### 7. Dashboard nicht ohne Rechtekonzept bauen

Wenn das Dashboard selbst `git fetch` und `git pull` ausführt, muss klar sein:

- welcher Benutzer läuft unter PHP
- wem gehört das Repo
- wer darf in `.git` schreiben

Wenn das nicht klar geregelt ist, scheitert das System nicht logisch, sondern mit Datei- und Prozessrechten.

### 8. Installer oder Bootstrap-Skript einplanen

Viele Update-Probleme sind keine Code-Probleme, sondern Installationsprobleme.

Beispiele:

- falscher Benutzer
- Scheduler fehlt
- Queue läuft nicht
- Node fehlt
- Git-Repo ist kein echter Clone
- `.git` hat falsche Eigentümer

Darum lohnt sich ein Installer fast immer.

## Empfohlene Übernahme-Checkliste für den anderen Entwickler

### Phase 1: Kern

- `version.json` einführen
- Manifest-Service bauen
- Update-Service bauen
- `update_runs`-Tabelle anlegen
- CLI-Befehl `app:update` bauen

### Phase 2: Deploy

- `deploy.sh` bauen
- Build-, Migrations- und Cache-Schritte definieren
- Docker-Modus nur ergänzen, wenn wirklich benötigt

### Phase 3: Bedienung

- `update.sh` als Wrapper anlegen
- Admin-API ergänzen
- Dashboard-Ansicht mit Status, Freigaben, Logs und Start-Button bauen

### Phase 4: Automation

- Auto-Update-Flag in App-Settings
- Scheduler-Job
- Queue und Audit-Log

### Phase 5: Installation

- Installer oder Server-Bootstrap
- Nginx oder Caddy
- PHP, Node, Composer, Datenbank
- Cron
- Rechtekonzept

## Typische Stolperfallen

Diese Probleme sind in der Praxis besonders häufig:

### 1. Repository ist kein echter Git-Clone

Dann funktionieren `git fetch`, `git show`, `git pull` nicht.

### 2. falscher Besitzer auf `.git`

Dann scheitert schon die Prüfung, nicht erst das eigentliche Update.

### 3. manuelle `chmod`-Befehle zerstören Execute-Bits

Pauschale Rechtebefehle auf das ganze Repo sind gefährlich, besonders für `node_modules`.

### 4. kein Versionssprung

Dann sieht das System zwar neue Commits, aber absichtlich kein neues Release.

### 5. Build-Werkzeuge sind da, aber nicht ausführbar

Darum repariert `deploy.sh` die Execute-Bits vor dem Frontend-Build gezielt.

### 6. Dashboard-Update ohne Scheduler-Verständnis

Manuelle Updates und Auto-Updates sind zwei verschiedene Flüsse.

Der Button im Dashboard ersetzt nicht den Scheduler.

## Warum ich dieses System wieder so bauen würde

Ich würde denselben Ansatz wieder wählen, weil er:

- operativ gut kontrollierbar ist
- ohne externe Release-Plattform funktioniert
- einfach zu auditieren ist
- sich gut in Laravel integrieren lässt
- sowohl für kleine als auch mittlere Projekte gut skaliert

Ich würde ihn nicht anders bauen im Sinn von “grundlegend anderes Konzept”, sondern nur je nach Produkt anders ausprägen:

- vielleicht andere `update_paths`
- vielleicht kein Docker-Modus
- vielleicht ein anderer Installer
- vielleicht mit GitHub Releases statt reinem Branch-Manifest

Aber der Kern aus Manifest, Validator, Runner, Logs und Deploy-Skript ist solide.

## Kurzfassung für den anderen Entwickler

Wenn du das Ganze in einem anderen Projekt nachbauen willst, ist die wichtigste Formel:

1. Explizite Release-Datei
2. restriktive Pfad-Freigabe
3. Update-Service mit Git-Fetch und Fast-Forward-Pull
4. persistente Logs
5. separates Deploy-Skript
6. Scheduler für Auto-Updates
7. korrektes Rechtekonzept

Wenn diese sieben Punkte sauber gebaut sind, ist das System im Kern bereits tragfähig.

## Empfohlener Übergabetext

Falls du dem Entwickler des anderen Projekts nur einen kurzen Arbeitsauftrag geben willst, kannst du ihm praktisch Folgendes geben:

> Bitte implementiere ein Release-Update-System auf Basis einer `version.json`. Das System soll nur bei höherer Versionsnummer aktualisieren, nur explizit freigegebene Pfade überschreiben, lokale Git-Änderungen blockieren, jeden Lauf persistent protokollieren und nach dem Code-Update ein separates `deploy.sh` ausführen. Es soll per CLI, Scheduler und Admin-Dashboard nutzbar sein. Kritische Pfade wie `.env`, `.git`, `storage`, `vendor` und `node_modules` dürfen niemals automatisch überschrieben werden.

Das ist die präzise Kurzfassung der Architektur.

## Standalone-Anhang für andere Entwickler

Wenn der andere Entwickler ausschließlich diese Datei bekommt, sind die folgenden Bausteine die wichtigsten Teile zum direkten Nachbauen.

## 1. Minimales Release-Manifest

Das ist die kleinste sinnvolle Form des Manifests:

```json
{
  "version": "1.0.0",
  "channel": "stable",
  "branch": "main",
  "update_paths": [
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "tests",
    "artisan",
    "composer.json",
    "composer.lock",
    "package.json",
    "package-lock.json",
    "deploy.sh",
    "update.sh",
    "version.json"
  ]
}
```

Regeln dazu:

- `version` muss bei jedem echten Release erhöht werden
- `branch` ist die Ziel-Branch, meist `main`
- `update_paths` ist die Whitelist
- alles, was nicht in `update_paths` liegt, blockiert das Release

## 2. Voller `update.sh`-Wrapper

Diesen Wrapper kann man praktisch 1:1 übernehmen:

```sh
#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(
    CDPATH= cd -- "$(dirname -- "$0")" && pwd
)

cd "$SCRIPT_DIR"

usage() {
    cat <<EOF
Verwendung
  ./update.sh [optionen]

Optionen
  --check       Prüft nur, ob ein Update verfügbar ist
  --auto        Führt das Update nur bei aktivem Auto-Update aus
  --json        Gibt das Ergebnis zusätzlich als JSON aus
  -h, --help    Zeigt diese Hilfe

Beispiele
  ./update.sh
  ./update.sh --check
  ./update.sh --auto
EOF
}

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    usage
    exit 0
fi

php artisan app:update "$@"
```

Warum so klein:

- die Geschäftslogik soll nicht doppelt in Shell und PHP liegen
- die Shell-Datei ist nur eine bequeme Einstiegsschicht

## 3. Voller `deploy.sh`-Ablauf

Das ist die aktuelle Form des Deploy-Skripts, inhaltlich so wie es hier produktiv gedacht ist:

```sh
#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(
    CDPATH= cd -- "$(dirname -- "$0")" && pwd
)

APP_DIR="$SCRIPT_DIR"
MODE="local"
WEB_SERVICE="${DEPLOY_WEB_SERVICE:-web}"
DO_BUILD=1
DO_MIGRATE=1
DO_RELOAD=1
NO_COLOR=0
PLAIN_OUTPUT=0

if [ -t 1 ]; then
    BOLD="$(printf '\033[1m')"
    DIM="$(printf '\033[2m')"
    RED="$(printf '\033[31m')"
    GREEN="$(printf '\033[32m')"
    YELLOW="$(printf '\033[33m')"
    BLUE="$(printf '\033[34m')"
    CYAN="$(printf '\033[36m')"
    RESET="$(printf '\033[0m')"
else
    BOLD=""
    DIM=""
    RED=""
    GREEN=""
    YELLOW=""
    BLUE=""
    CYAN=""
    RESET=""
fi

timestamp() {
    date '+%H:%M:%S'
}

log_line() {
    level="$1"
    color="$2"
    shift 2
    printf '%s[%s] [%s]%s %s\n' "$color" "$(timestamp)" "$level" "$RESET" "$*"
}

info() {
    log_line "INFO" "$BLUE" "$@"
}

step() {
    log_line "STEP" "$CYAN" "$@"
}

warn() {
    log_line "WARN" "$YELLOW" "$@"
}

success() {
    log_line "OK" "$GREEN" "$@"
}

die() {
    log_line "ERR" "$RED" "$@"
    exit 1
}

disable_colors() {
    BOLD=""
    DIM=""
    RED=""
    GREEN=""
    YELLOW=""
    BLUE=""
    CYAN=""
    RESET=""
}

usage() {
    cat <<EOF
Verwendung
  ./deploy.sh [local|docker] [optionen]

Beschreibung
  Führt den üblichen Deploy-Ablauf aus:
  Build, Migrationen sowie Laravel-Cache- und Reload-Schritte.

Optionen
  --skip-build
  --skip-migrate
  --skip-reload
  --service NAME
  --no-color
  --plain
  -h, --help
EOF
}

have_command() {
    command -v "$1" >/dev/null 2>&1
}

require_command() {
    command_name="$1"
    if ! have_command "$command_name"; then
        die "Benötigtes Kommando nicht gefunden: $command_name"
    fi
}

require_file() {
    file_path="$1"
    if [ ! -f "$file_path" ]; then
        die "Benötigte Datei fehlt: $file_path"
    fi
}

render_flag() {
    if [ "$1" -eq 1 ]; then
        printf '%s' 'ja'
    else
        printf '%s' 'nein'
    fi
}

show_banner() {
    printf '\n'
    printf '%s' "$BOLD$CYAN"
    cat <<'EOF'
███╗   ██╗███████╗██████╗ ██╗   ██╗██╗   ██╗ █████╗ ██╗   ██╗██╗  ████████╗
████╗  ██║██╔════╝██╔══██╗██║   ██║██║   ██║██╔══██╗██║   ██║██║  ╚══██╔══╝
██╔██╗ ██║█████╗  ██████╔╝██║   ██║██║   ██║███████║██║   ██║██║     ██║
██║╚██╗██║██╔══╝  ██╔══██╗██║   ██║╚██╗ ██╔╝██╔══██║██║   ██║██║     ██║
██║ ╚████║███████╗██████╔╝╚██████╔╝ ╚████╔╝ ██║  ██║╚██████╔╝███████╗██║
╚═╝  ╚═══╝╚══════╝╚═════╝  ╚═════╝   ╚═══╝  ╚═╝  ╚═╝ ╚═════╝ ╚══════╝╚═╝
EOF
    printf '%s\n' "$RESET"
}

show_summary() {
    info "Projekt: $APP_DIR"
    info "Modus: $MODE"

    if [ "$MODE" = "docker" ]; then
        info "Service: $WEB_SERVICE"
    fi

    info "Build: $(render_flag "$DO_BUILD") | Migrationen: $(render_flag "$DO_MIGRATE") | Reload: $(render_flag "$DO_RELOAD")"
}

run_cmd() {
    description="$1"
    shift
    step "$description"
    printf '%s$ %s%s\n' "$DIM" "$*" "$RESET"
    "$@"
}

repair_node_tool_permissions() {
    if [ ! -d "$APP_DIR/node_modules" ]; then
        return
    fi

    if [ -d "$APP_DIR/node_modules/.bin" ]; then
        find "$APP_DIR/node_modules/.bin" -type f -exec chmod 755 {} \;
    fi

    if [ -f "$APP_DIR/node_modules/vite/bin/vite.js" ]; then
        chmod 755 "$APP_DIR/node_modules/vite/bin/vite.js"
    fi

    if [ -d "$APP_DIR/node_modules/@esbuild" ]; then
        find "$APP_DIR/node_modules/@esbuild" -path '*/bin/esbuild' -type f -exec chmod 755 {} \;
    fi
}

run_frontend_build() {
    repair_node_tool_permissions

    if [ -f "$APP_DIR/node_modules/vite/bin/vite.js" ]; then
        run_cmd "Baue Frontend" node "$APP_DIR/node_modules/vite/bin/vite.js" build --configLoader runner
        return
    fi

    run_cmd "Baue Frontend" npm run build
}

run_shell_cmd() {
    description="$1"
    command_string="$2"
    step "$description"
    printf '%s$ %s%s\n' "$DIM" "$command_string" "$RESET"
    sh -c "$command_string"
}

detect_compose() {
    if have_command docker && docker compose version >/dev/null 2>&1; then
        printf '%s' 'docker compose'
        return 0
    fi

    if have_command docker-compose; then
        printf '%s' 'docker-compose'
        return 0
    fi

    return 1
}

parse_args() {
    while [ "$#" -gt 0 ]; do
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
                [ "$#" -gt 0 ] || die "Für --service fehlt der Name."
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

    if [ "$DO_BUILD" -eq 1 ]; then
        require_command node
        require_command npm
    fi
}

preflight_docker() {
    preflight_common
    require_file "$APP_DIR/docker-compose.yml"
    COMPOSE_CMD="$(detect_compose)" || die "Docker Compose wurde nicht gefunden."
}

run_local() {
    preflight_local

    if [ "$DO_BUILD" -eq 1 ]; then
        run_frontend_build
    else
        warn "Build übersprungen"
    fi

    if [ "$DO_MIGRATE" -eq 1 ]; then
        run_cmd "Führe Migrationen aus" php artisan migrate --force
    else
        warn "Migrationen übersprungen"
    fi

    if [ "$DO_RELOAD" -eq 1 ]; then
        run_cmd "Leere Laravel-Caches" php artisan optimize:clear
        run_cmd "Baue Config-Cache" php artisan config:cache
        run_cmd "Baue Route-Cache" php artisan route:cache
        run_cmd "Baue View-Cache" php artisan view:cache
        run_cmd "Starte Queue-Worker sauber neu" php artisan queue:restart
    else
        warn "Reload/Caches übersprungen"
    fi
}

run_docker() {
    preflight_docker
    info "Compose-Kommando: $COMPOSE_CMD"

    if [ "$DO_BUILD" -eq 1 ]; then
        run_shell_cmd "Baue Container neu" "$COMPOSE_CMD build"
    else
        warn "Container-Build übersprungen"
    fi

    run_shell_cmd "Starte Container" "$COMPOSE_CMD up -d --remove-orphans"

    if [ "$DO_MIGRATE" -eq 1 ]; then
        run_shell_cmd "Führe Migrationen im Container aus" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan migrate --force"
    else
        warn "Migrationen im Container übersprungen"
    fi

    if [ "$DO_RELOAD" -eq 1 ]; then
        run_shell_cmd "Leere Laravel-Caches im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan optimize:clear"
        run_shell_cmd "Baue Config-Cache im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan config:cache"
        run_shell_cmd "Baue Route-Cache im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan route:cache"
        run_shell_cmd "Baue View-Cache im Container" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan view:cache"
        run_shell_cmd "Starte Queue-Worker im Container sauber neu" "$COMPOSE_CMD exec -T $WEB_SERVICE php artisan queue:restart"
    else
        warn "Reload/Caches im Container übersprungen"
    fi
}

parse_args "$@"

if [ "$NO_COLOR" -eq 1 ]; then
    disable_colors
fi

cd "$APP_DIR"
START_TS="$(date '+%s' 2>/dev/null || printf '0')"

if [ "$PLAIN_OUTPUT" -eq 0 ]; then
    show_banner
fi

info "Starte Deploy-Workflow"
show_summary

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

if [ "$START_TS" -gt 0 ] && [ "$END_TS" -ge "$START_TS" ]; then
    ELAPSED="$((END_TS - START_TS))"
    success "Fertig in ${ELAPSED}s"
else
    success "Fertig"
fi
```

Wichtig an diesem Skript:

- `--plain` für Dashboard-Logs
- `local` und `docker` als getrennte Modi
- Build, Migrationen und Reload sind einzeln abschaltbar
- Vite und `esbuild` werden vor dem Build auf Execute-Rechte repariert

## 4. Datenbanktabelle für Update-Läufe

Diese Migration ist klein, aber essenziell:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 32);
            $table->string('status', 32);
            $table->string('local_version')->nullable();
            $table->string('target_version')->nullable();
            $table->string('local_commit', 40)->nullable();
            $table->string('target_commit', 40)->nullable();
            $table->json('changed_files_json')->nullable();
            $table->text('summary')->nullable();
            $table->longText('log_output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_runs');
    }
};
```

## 5. Artisan-Befehl und Scheduler

Das ist der relevante CLI-Einstieg:

```php
Artisan::command('app:update
    {--check : Prüft nur, ob eine neue freigegebene Version vorliegt}
    {--auto : Führt das Update nur aus, wenn automatische Updates aktiviert sind}
    {--json : Gibt das Ergebnis als JSON aus}
', function () {
    /** @var ApplicationUpdateService $updateService */
    $updateService = app(ApplicationUpdateService::class);

    if ((bool) $this->option('check')) {
        $status = $updateService->status();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $status['healthy'] ? self::SUCCESS : self::FAILURE;
        }

        $this->table(
            ['Feld', 'Wert'],
            [
                ['Lokale Version', (string) data_get($status, 'local.version', 'unbekannt')],
                ['Remote Version', (string) data_get($status, 'remote.version', 'unbekannt')],
                ['Branch', (string) ($status['branch'] ?? 'unbekannt')],
                ['Update verfügbar', ($status['update_available'] ?? false) ? 'ja' : 'nein'],
                ['Update möglich', ($status['can_update'] ?? false) ? 'ja' : 'nein'],
                ['Auto-Update', ($status['auto_update_enabled'] ?? false) ? 'ja' : 'nein'],
                ['Fehler', (string) ($status['error'] ?? '-') ?: '-'],
            ],
        );

        return $status['healthy'] ? self::SUCCESS : self::FAILURE;
    }

    $result = $updateService->run(
        automatic: (bool) $this->option('auto'),
        output: fn (string $line) => $this->line($line),
    );

    if ((bool) $this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    return in_array($result['status'], ['succeeded', 'skipped'], true)
        ? self::SUCCESS
        : self::FAILURE;
})->purpose('Prüft und installiert freigegebene App-Updates');

Schedule::command('app:update --auto')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
```

Die Kerngedanken daran:

- `--check` und echter Lauf sind derselbe Service
- `--auto` ist nur ein anderer Modus, keine zweite Logik
- Scheduler und Dashboard landen dadurch auf derselben Update-Pipeline

## 6. API-Routen für das Dashboard

Diese Endpunkte reichen:

```php
Route::prefix('/admin')->middleware('can:viewAny,App\\Models\\User')->group(function (): void {
    Route::get('updates', [AdminApplicationUpdateController::class, 'show']);
    Route::post('updates/run', [AdminApplicationUpdateController::class, 'run'])->middleware('throttle:sensitive');
    Route::patch('updates/preferences', [AdminApplicationUpdateController::class, 'preferences']);
    Route::get('updates/runs/{runId}', [AdminApplicationUpdateController::class, 'runShow']);
});
```

## 7. Controller-Skelett

Der Controller darf bewusst dünn bleiben:

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\ApplicationUpdateService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationUpdateController extends Controller
{
    public function __construct(
        private readonly ApplicationUpdateService $applicationUpdateService,
        private readonly AppSettingsService $appSettingsService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function show(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json($this->applicationUpdateService->status());
    }

    public function run(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json(
            $this->applicationUpdateService->run(
                actorUserId: $request->user()?->id,
                automatic: false,
            ),
        );
    }

    public function preferences(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'auto_update_enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['auto_update_enabled'];

        $this->appSettingsService->update([
            'auto_update_enabled' => $enabled ? '1' : '0',
        ], $request->user()?->id);

        $this->auditLogService->record(
            'application_update_preferences_updated',
            'system_update',
            null,
            ['auto_update_enabled' => $enabled],
            $request->user()?->id,
            $request,
        );

        return response()->json([
            'auto_update_enabled' => $enabled,
        ]);
    }

    public function runShow(int $runId): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $run = $this->applicationUpdateService->runDetail($runId);

        abort_if($run === null, 404);

        return response()->json($run);
    }
}
```

## 8. Service-Blueprint für den Update-Kern

Der komplette Service in unserem Projekt ist größer, weil er viele Fehlerfälle normalisiert. Für einen anderen Entwickler reicht als Blaupause dieser Aufbau:

```php
class ApplicationUpdateService
{
    public function status(): array
    {
        $localManifest = $this->manifestService->readLocal();
        $branch = $localManifest['branch'];

        $repositoryUrl = $this->runCommand(['git', 'remote', 'get-url', 'origin']);
        $currentBranch = $this->runCommand(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        $localCommit = $this->runCommand(['git', 'rev-parse', 'HEAD']);
        $trackedChanges = $this->runLines(['git', 'status', '--porcelain', '--untracked-files=no']);

        $this->runCommand(['git', 'fetch', '--quiet', 'origin', $branch], 180);

        $remoteManifest = $this->manifestService->parseJson(
            $this->runCommand(['git', 'show', "origin/{$branch}:version.json"]),
        );

        $remoteCommit = $this->runCommand(['git', 'rev-parse', "origin/{$branch}"]);
        $allChangedFiles = $this->runLines(['git', 'diff', '--name-only', 'HEAD', "origin/{$branch}"]);
        $blockedFiles = $this->blockedFiles($allChangedFiles, $remoteManifest['update_paths']);
        $managedChangedFiles = array_values(array_diff($allChangedFiles, $blockedFiles));

        $updateAvailable = version_compare(
            ltrim($remoteManifest['version'], 'vV'),
            ltrim($localManifest['version'], 'vV'),
            '>',
        );

        return [
            'healthy' => true,
            'repository_url' => $repositoryUrl,
            'current_branch' => $currentBranch,
            'branch' => $branch,
            'local' => [
                'version' => $localManifest['version'],
                'commit' => $localCommit,
            ],
            'remote' => [
                'version' => $remoteManifest['version'],
                'commit' => $remoteCommit,
            ],
            'tracked_changes' => $trackedChanges,
            'changed_files' => $managedChangedFiles,
            'blocked_files' => $blockedFiles,
            'update_available' => $updateAvailable,
            'can_update' => $updateAvailable && $trackedChanges === [] && $blockedFiles === [],
        ];
    }

    public function run(?int $actorUserId = null, bool $automatic = false, ?callable $output = null): array
    {
        $lock = Cache::lock('application-update-run', 1800);

        if (! $lock->get()) {
            return [
                'status' => 'busy',
                'message' => 'Es läuft bereits ein Update.',
                'run' => null,
                'status_snapshot' => $this->status(),
            ];
        }

        $run = UpdateRun::query()->create([
            'triggered_by_user_id' => $actorUserId,
            'mode' => $automatic ? 'automatic' : 'manual',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $status = $this->status();

            if (! $status['healthy']) {
                throw new RuntimeException($status['error'] ?? 'Update-Prüfung fehlgeschlagen.');
            }

            if (! $status['update_available']) {
                $this->finishRun($run, 'skipped', 'Keine neue freigegebene Version gefunden.', [], $output);

                return [
                    'status' => 'skipped',
                    'message' => 'Keine neue freigegebene Version gefunden.',
                    'run' => $this->formatRunDetail($run->fresh()),
                    'status_snapshot' => $this->status(),
                ];
            }

            if ($status['tracked_changes'] !== []) {
                throw new RuntimeException('Lokale Änderungen in tracked Dateien blockieren das Update.');
            }

            if ($status['blocked_files'] !== []) {
                throw new RuntimeException('Das Release enthält Änderungen außerhalb der freigegebenen Update-Pfade.');
            }

            $branch = $status['branch'];
            $targetVersion = $status['remote']['version'];
            $changedFiles = $status['changed_files'];

            $this->runLoggedCommand($run, "Übernehme Version {$targetVersion} per git pull", ['git', 'pull', '--ff-only', 'origin', $branch], $output, 300);

            if ($this->shouldRunComposerInstall($changedFiles)) {
                $this->runLoggedCommand($run, 'Installiere Composer-Abhängigkeiten', $this->composerInstallCommand(), $output, 1800);
            }

            if ($this->shouldRunNpmInstall($changedFiles)) {
                $this->runLoggedCommand($run, 'Installiere Node-Abhängigkeiten', ['npm', 'install'], $output, 1800);
            }

            $this->runLoggedCommand($run, 'Führe Deploy-Skript aus', ['bash', './deploy.sh', '--plain'], $output, 1800);

            $finalManifest = $this->manifestService->readLocal();

            $this->finishRun($run, 'succeeded', "Update auf Version {$finalManifest['version']} abgeschlossen.", $changedFiles, $output);

            return [
                'status' => 'succeeded',
                'message' => "Update auf Version {$finalManifest['version']} abgeschlossen.",
                'run' => $this->formatRunDetail($run->fresh()),
                'status_snapshot' => $this->status(),
            ];
        } catch (\Throwable $exception) {
            $summary = $this->normalizeErrorMessage($exception->getMessage());
            $this->finishRun($run, 'failed', $summary, [], $output);

            return [
                'status' => 'failed',
                'message' => $summary,
                'run' => $this->formatRunDetail($run->fresh()),
                'status_snapshot' => $this->status(),
            ];
        } finally {
            $lock->release();
        }
    }
}
```

Dieser Blueprint ist die eigentliche Kernlogik:

- `status()` prüft alles
- `run()` benutzt `status()` als Entscheidungsgrundlage
- `run()` macht danach nur noch Git-Pull, Install-Schritte und Deploy

## 9. Manifest-Validator als Kopiervorlage

Auch dafür reicht oft ein kompakter Aufbau:

```php
class VersionManifestService
{
    private const SAFE_DIRECTORY_ROOTS = [
        'app',
        'bootstrap',
        'config',
        'database',
        'public',
        'resources',
        'routes',
        'tests',
    ];

    private const SAFE_ROOT_FILES = [
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'deploy.sh',
        'update.sh',
        'version.json',
    ];

    private const DISALLOWED_PREFIXES = [
        '.env',
        '.git',
        'bootstrap/cache',
        'node_modules',
        'public/storage',
        'storage',
        'vendor',
    ];

    public function parseJson(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $version = trim((string) ($decoded['version'] ?? ''));
        $channel = trim((string) ($decoded['channel'] ?? 'stable'));
        $branch = trim((string) ($decoded['branch'] ?? 'main'));
        $paths = $decoded['update_paths'] ?? $this->defaultUpdatePaths();

        if ($version === '') {
            throw new RuntimeException('version.json benötigt ein Feld "version".');
        }

        return [
            'version' => $version,
            'channel' => $channel !== '' ? $channel : 'stable',
            'branch' => $branch,
            'update_paths' => $this->validateUpdatePaths($paths),
        ];
    }

    public function defaultUpdatePaths(): array
    {
        return [
            ...self::SAFE_DIRECTORY_ROOTS,
            ...self::SAFE_ROOT_FILES,
        ];
    }

    public function validateUpdatePaths(array $paths): array
    {
        $validated = [];

        foreach ($paths as $path) {
            $normalizedPath = trim(str_replace('\\', '/', (string) $path));
            $normalizedPath = trim($normalizedPath, '/');

            if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
                throw new RuntimeException("Ungültiger Update-Pfad [{$path}].");
            }

            foreach (self::DISALLOWED_PREFIXES as $prefix) {
                if ($normalizedPath === $prefix || str_starts_with($normalizedPath.'/', $prefix.'/')) {
                    throw new RuntimeException("Unsicherer Update-Pfad [{$normalizedPath}].");
                }
            }

            if (in_array($normalizedPath, self::SAFE_ROOT_FILES, true)) {
                $validated[] = $normalizedPath;
                continue;
            }

            $rootSegment = explode('/', $normalizedPath)[0];

            if (! in_array($rootSegment, self::SAFE_DIRECTORY_ROOTS, true)) {
                throw new RuntimeException("Nicht erlaubter Update-Pfad [{$normalizedPath}].");
            }

            $validated[] = $normalizedPath;
        }

        return array_values(array_unique($validated));
    }
}
```

## 10. Praktische Serverregeln, die der andere Entwickler unbedingt wissen muss

Diese Regeln sollte der andere Entwickler nicht nur lesen, sondern als harte Betriebsregeln übernehmen:

### Repository-Rechte

Wenn das Dashboard `git fetch` und `git pull` ausführt, dann muss der Runtime-Benutzer:

- in das Repo schreiben können
- in `.git` schreiben können
- insbesondere in `.git/objects` schreiben können

### Keine pauschalen Rechtekommandos auf das ganze Repo

Das hier ist gefährlich:

```sh
find /var/www/app -type f -exec chmod 664 {} \;
```

Warum:

- das zerstört Execute-Bits in `node_modules`
- danach scheitern `vite`, `esbuild` und ähnliche Tools

Wenn Rechte gesetzt werden müssen, dann gezielt:

- auf das Repo
- auf `.git`
- auf konkrete Build-Binärdateien

### Immer echter Git-Clone

Das Update-System braucht:

- `git fetch`
- `git show`
- `git pull`

Ein reiner Dateiupload oder ein tarball-artiges Deployment reicht nicht.

### Scheduler ist Pflicht

Auto-Update ohne Scheduler gibt es nicht.

Mindestens:

```cron
* * * * * www-data cd /var/www/app && php artisan schedule:run >> /dev/null 2>&1
```

### Queue-Restart ist Teil des Deploys

Wenn Queues im Projekt relevant sind, muss nach dem Deploy mindestens ein `php artisan queue:restart` laufen.

## 11. Wenn der andere Entwickler es noch schlanker will

Dann empfehle ich diesen Minimalplan:

1. `version.json`
2. `VersionManifestService`
3. `ApplicationUpdateService`
4. `update_runs`
5. `php artisan app:update`
6. `deploy.sh`
7. optional später Dashboard und Installer

Damit ist das System bereits tragfähig.

## 12. Wenn der andere Entwickler es exakt nachbauen will

Dann sollte er diese Reihenfolge verwenden:

1. Manifest-Datei und Validator bauen
2. `update_runs`-Tabelle anlegen
3. `ApplicationUpdateService::status()` bauen
4. `ApplicationUpdateService::run()` bauen
5. `app:update`-Command bauen
6. `deploy.sh` bauen
7. `update.sh` als Wrapper bauen
8. Admin-API ergänzen
9. Dashboard mit Status, Logs und Start-Button bauen
10. Scheduler und Auto-Update-Flag ergänzen
11. Installer oder Bootstrap-Skript ergänzen

Damit ist die Übertragungslogik praktisch vollständig beschrieben, ohne dass weitere Projektdateien nötig sind.
