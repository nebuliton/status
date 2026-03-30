param(
    [ValidateSet('local', 'docker')]
    [string] $Mode = 'local',
    [string] $Service = 'app',
    [switch] $SkipBuild,
    [switch] $SkipMigrate,
    [switch] $SkipReload,
    [switch] $Plain,
    [switch] $NoColor
)

function Write-Step {
    param([string] $Message)

    Write-Host ("[{0}] {1}" -f (Get-Date -Format 'HH:mm:ss'), $Message)
}

function Invoke-LoggedCommand {
    param(
        [string] $Description,
        [scriptblock] $Command,
        [string] $DisplayCommand
    )

    Write-Step $Description
    Write-Host ('$ {0}' -f $DisplayCommand)
    & $Command

    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

function Get-ComposeCommand {
    if (Get-Command docker -ErrorAction SilentlyContinue) {
        & docker compose version *> $null
        if ($LASTEXITCODE -eq 0) {
            return 'docker compose'
        }
    }

    if (Get-Command docker-compose -ErrorAction SilentlyContinue) {
        return 'docker-compose'
    }

    throw 'Docker Compose wurde nicht gefunden.'
}

function Invoke-LocalDeploy {
    if (-not $SkipBuild -and (Test-Path package.json)) {
        Invoke-LoggedCommand 'Baue Frontend' { npm run build } 'npm run build'
    }

    if (-not $SkipMigrate) {
        Invoke-LoggedCommand 'Führe Migrationen aus' { php artisan migrate --force } 'php artisan migrate --force'
    }

    if (-not $SkipReload) {
        Invoke-LoggedCommand 'Leere Laravel-Caches' { php artisan optimize:clear } 'php artisan optimize:clear'
        Invoke-LoggedCommand 'Baue Config-Cache' { php artisan config:cache } 'php artisan config:cache'
        Invoke-LoggedCommand 'Baue Route-Cache' { php artisan route:cache } 'php artisan route:cache'
        Invoke-LoggedCommand 'Baue View-Cache' { php artisan view:cache } 'php artisan view:cache'
        Invoke-LoggedCommand 'Starte Queue-Worker sauber neu' { php artisan queue:restart } 'php artisan queue:restart'
    }
}

function Invoke-DockerDeploy {
    if (-not (Test-Path docker-compose.yml) -and -not (Test-Path docker-compose.yaml)) {
        throw 'docker-compose.yml oder docker-compose.yaml fehlt.'
    }

    $composeCommand = Get-ComposeCommand

    if (-not $SkipBuild) {
        Invoke-LoggedCommand 'Baue Container neu' { Invoke-Expression "$composeCommand build" } "$composeCommand build"
    }

    Invoke-LoggedCommand 'Starte Container' { Invoke-Expression "$composeCommand up -d --remove-orphans" } "$composeCommand up -d --remove-orphans"

    if (-not $SkipMigrate) {
        Invoke-LoggedCommand 'Führe Migrationen im Container aus' { Invoke-Expression "$composeCommand exec -T $Service php artisan migrate --force" } "$composeCommand exec -T $Service php artisan migrate --force"
    }

    if (-not $SkipReload) {
        Invoke-LoggedCommand 'Leere Laravel-Caches im Container' { Invoke-Expression "$composeCommand exec -T $Service php artisan optimize:clear" } "$composeCommand exec -T $Service php artisan optimize:clear"
        Invoke-LoggedCommand 'Baue Config-Cache im Container' { Invoke-Expression "$composeCommand exec -T $Service php artisan config:cache" } "$composeCommand exec -T $Service php artisan config:cache"
        Invoke-LoggedCommand 'Baue Route-Cache im Container' { Invoke-Expression "$composeCommand exec -T $Service php artisan route:cache" } "$composeCommand exec -T $Service php artisan route:cache"
        Invoke-LoggedCommand 'Baue View-Cache im Container' { Invoke-Expression "$composeCommand exec -T $Service php artisan view:cache" } "$composeCommand exec -T $Service php artisan view:cache"
        Invoke-LoggedCommand 'Starte Queue-Worker im Container sauber neu' { Invoke-Expression "$composeCommand exec -T $Service php artisan queue:restart" } "$composeCommand exec -T $Service php artisan queue:restart"
    }
}

$start = Get-Date
Write-Step 'Starte Deploy-Workflow'

switch ($Mode) {
    'local' { Invoke-LocalDeploy }
    'docker' { Invoke-DockerDeploy }
}

$elapsed = [int]((Get-Date) - $start).TotalSeconds
Write-Step ("Deploy abgeschlossen in {0}s" -f $elapsed)
