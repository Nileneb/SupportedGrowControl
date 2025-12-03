#!/usr/bin/env bash
set -euo pipefail

# Pfad zum Grow-Laravel-Projekt anpassen, falls nötig
PROJECT_DIR="/home/nileneb/SupportedGrowControl"
COMPOSE_FILE="docker-compose.grow.prod.yaml"

cd "$PROJECT_DIR"

echo ">>> Build & start GrowDash production stack..."
docker compose -f "$COMPOSE_FILE" pull || true
docker compose -f "$COMPOSE_FILE" build
docker compose -f "$COMPOSE_FILE" up -d

echo ">>> Run Laravel setup tasks inside php-cli container..."
# APP_KEY generieren (falls leer)
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan key:generate --force

# Datenbank-Migrationen
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan migrate --force

# Clear old caches first (important for scheduler to pick up new Kernel)
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan config:clear
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan route:clear
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan cache:clear

# Caches optimieren
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan view:cache

echo ">>> GrowDash Laravel production deployment finished."
echo "    -> Intern:    http://web:80"
echo "    -> Extern:    http://192.168.178.12:6480 (vor Reverse Proxy)"
echo "    -> Public:    https://grow.linn.games (über Synology Reverse Proxy)"
