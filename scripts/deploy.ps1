<#
Deploy script for Growdash (Windows PowerShell).
Usage:
  pwsh -File scripts/deploy.ps1 [-Env Production] [-StartReverb] [-StartQueue]
Parameters:
  -Env           Environment name (Default: Production)
  -StartReverb   Start Reverb websocket server after deploy
  -StartQueue    Start Laravel queue worker after deploy
Prereqs:
  - PHP 8.3+, Composer, Node.js (>=18), npm
  - .env configured (DB, cache, queue, broadcasting)
#>
param(
    [string]$Env = "Production",
    [switch]$StartReverb,
    [switch]$StartQueue
)

function Write-Step($msg) { Write-Host "[+] $msg" -ForegroundColor Cyan }
function Fail($msg) { Write-Host "[x] $msg" -ForegroundColor Red; exit 1 }

# Validate tools
Write-Step "Checking required tools"
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { Fail "PHP not found in PATH" }
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { Fail "Composer not found in PATH" }
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) { Fail "npm not found in PATH" }

# Ensure at project root
$root = Get-Location
if (-not (Test-Path "$root\artisan")) { Fail "Run from project root (artisan missing)" }

# Environment info
Write-Step "Environment: $Env"
$env:APP_ENV = $Env
$dotenv = Join-Path $root ".env"
if (-not (Test-Path $dotenv)) { Write-Host "[!] .env missing. Copying .env.example -> .env" -ForegroundColor Yellow; Copy-Item ".env.example" ".env" }

# Backend deps
Write-Step "Composer install (no-dev, optimized)"
composer install --no-dev --prefer-dist --optimize-autoloader
if ($LASTEXITCODE -ne 0) { Fail "Composer install failed" }

# Frontend deps
Write-Step "npm ci"
npm ci
if ($LASTEXITCODE -ne 0) { Fail "npm ci failed" }

# Build assets
Write-Step "npm run build"
npm run build
if ($LASTEXITCODE -ne 0) { Fail "npm run build failed" }

# Laravel optimize + caches
Write-Step "Clearing optimize caches"
php artisan optimize:clear
Write-Step "Caching config/routes/views/events"
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Database migrations
Write-Step "Running database migrations"
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { Fail "Migrations failed" }

# Optional services
if ($StartReverb) {
    Write-Step "Starting Reverb server"
    $host = $env:REVERB_HOST
    $port = $env:REVERB_PORT
    if (-not $host) { $host = "127.0.0.1" }
    if (-not $port) { $port = 6001 }
    php artisan reverb:start --host "$host" --port $port
}

if ($StartQueue) {
    Write-Step "Starting queue worker"
    php artisan queue:work --sleep=3 --tries=3 --max-time=3600
}

Write-Host "[✓] Deploy completed" -ForegroundColor Green
