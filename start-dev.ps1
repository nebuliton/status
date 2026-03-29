[CmdletBinding()]
param(
    [string]$PhpHost = "127.0.0.1",
    [int]$PhpPort = 8000,
    [string]$ViteHost = "127.0.0.1",
    [switch]$Browser
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path -LiteralPath $PSScriptRoot).Path
$shellPath = (Get-Process -Id $PID).Path
$escapedProjectRoot = $projectRoot.Replace("'", "''")

function Write-Block {
    param(
        [string]$Title,
        [string]$Value,
        [ConsoleColor]$Color = [ConsoleColor]::Gray
    )

    Write-Host ("{0,-14} {1}" -f $Title, $Value) -ForegroundColor $Color
}

function Require-Command {
    param([string]$Name)

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Befehl '$Name' wurde nicht gefunden. Bitte installiere ihn oder prüfe deine PATH-Variable."
    }
}

if (-not (Test-Path -LiteralPath (Join-Path $projectRoot "artisan"))) {
    throw "Im Projektverzeichnis wurde keine 'artisan'-Datei gefunden: $projectRoot"
}

Require-Command -Name "php"
Require-Command -Name "npm"

Write-Host ""
Write-Host "Nebuliton Dev Launcher" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor DarkCyan
Write-Block -Title "Projekt" -Value $projectRoot
Write-Block -Title "Laravel" -Value "http://$PhpHost`:$PhpPort" -Color Green
Write-Block -Title "Vite" -Value "http://$ViteHost`:5173" -Color Yellow
Write-Block -Title "Scheduler" -Value "aktiv" -Color Cyan
Write-Host ""

$serverCommand = "& { `$Host.UI.RawUI.WindowTitle = 'Nebuliton • Laravel Server'; Set-Location -LiteralPath '$escapedProjectRoot'; php artisan serve --host=$PhpHost --port=$PhpPort }"
$viteCommand = "& { `$Host.UI.RawUI.WindowTitle = 'Nebuliton • Vite Dev Server'; Set-Location -LiteralPath '$escapedProjectRoot'; npm run dev -- --host $ViteHost }"
$schedulerCommand = "& { `$Host.UI.RawUI.WindowTitle = 'Nebuliton • Scheduler'; Set-Location -LiteralPath '$escapedProjectRoot'; php artisan schedule:work }"

$serverProcess = Start-Process -FilePath $shellPath -WorkingDirectory $projectRoot -ArgumentList @(
    "-NoExit",
    "-Command",
    $serverCommand
) -PassThru

Start-Sleep -Milliseconds 300

$viteProcess = Start-Process -FilePath $shellPath -WorkingDirectory $projectRoot -ArgumentList @(
    "-NoExit",
    "-Command",
    $viteCommand
) -PassThru

Start-Sleep -Milliseconds 300

$schedulerProcess = Start-Process -FilePath $shellPath -WorkingDirectory $projectRoot -ArgumentList @(
    "-NoExit",
    "-Command",
    $schedulerCommand
) -PassThru

Write-Host "Gestartet" -ForegroundColor Green
Write-Block -Title "Server PID" -Value $serverProcess.Id
Write-Block -Title "Vite PID" -Value $viteProcess.Id
if ($schedulerProcess) {
    Write-Block -Title "Scheduler PID" -Value $schedulerProcess.Id
}
Write-Host ""
Write-Host "Zum Beenden einfach alle gestarteten Fenster schließen." -ForegroundColor DarkGray

if ($Browser.IsPresent) {
    Start-Sleep -Seconds 2
    Start-Process "http://$PhpHost`:$PhpPort" | Out-Null
}
