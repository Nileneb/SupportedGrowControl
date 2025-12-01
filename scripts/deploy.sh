#!/usr/bin/env bash
set -euo pipefail

# Growdash Docker deployment (Linux)
# Usage: ./scripts/deploy.sh

echo "[+] Checking Docker and Compose"
command -v docker >/dev/null 2>&1 || { echo "[x] docker not found"; exit 1; }
command -v docker compose >/dev/null 2>&1 || command -v docker-compose >/dev/null 2>&1 || { echo "[x] docker compose not found"; exit 1; }

if [ ! -f .env ]; then
  echo "[!] .env missing. Copying .env.example -> .env"
  cp .env.example .env
fi

echo "[+] Building images"
docker compose build

echo "[+] Starting stack"
docker compose up -d

echo "[+] Running migrations"
docker compose exec -T app php artisan migrate --force

echo "[✓] Deploy completed. Web: http://localhost:8080, Reverb: ws://localhost:6001"
