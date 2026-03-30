param(
    [string] $RepositoryUrl = 'https://github.com/nebuliton/status.git',
    [string] $Branch = 'main',
    [string] $InstallPath = 'C:\inetpub\wwwroot\nebuliton-status'
)

function Write-Step {
    param([string] $Message)

    Write-Host ("[{0}] {1}" -f (Get-Date -Format 'HH:mm:ss'), $Message)
}

if (-not (Test-Path (Join-Path $InstallPath '.git'))) {
    Write-Step "Klonen des Repositorys"
    git clone --branch $Branch $RepositoryUrl $InstallPath
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

Set-Location $InstallPath

if (-not (Test-Path '.env')) {
    Write-Step ".env anlegen"
    Copy-Item '.env.example' '.env'
}

Write-Step "Composer-Abhängigkeiten installieren"
& composer install
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Step "Node-Abhängigkeiten installieren"
& npm install
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Step "App-Key erzeugen"
& php artisan key:generate --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Step "Migrationen ausführen"
& php artisan migrate --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Step "Frontend bauen"
& npm run build
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Step "Storage-Link anlegen"
& php artisan storage:link
if ($LASTEXITCODE -ne 0 -and $LASTEXITCODE -ne 1) { exit $LASTEXITCODE }

Write-Step "Produktions-Caches bauen"
& php artisan optimize
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Step "Installation abgeschlossen"
