#!/usr/bin/env bash
set -euo pipefail

# Pfad zum Grow-Laravel-Projekt
PROJECT_DIR="/home/nileneb/SupportedGrowControl"
COMPOSE_FILE="docker-compose.grow.prod.yaml"
VOLUME_NAME="supportedgrowcontrol_grow-postgres-data-production"

cd "$PROJECT_DIR"

echo "=========================================="
echo " GrowDash Datenbank Neuaufsetzen"
echo "=========================================="
echo ""
echo "⚠️  WARNUNG: Dies löscht ALLE Daten in der GrowDash-Datenbank!"
echo "    Andere Projekte (Axia, n8n) sind NICHT betroffen."
echo ""
read -p "Möchten Sie fortfahren? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Abgebrochen."
    exit 0
fi

echo ""
echo ">>> Stoppe GrowDash Container..."
docker compose -f "$COMPOSE_FILE" stop

echo ""
echo ">>> Lösche GrowDash Postgres Volume..."
docker volume rm "$VOLUME_NAME" || echo "Volume existiert nicht oder ist in Verwendung"

echo ""
echo ">>> Starte GrowDash Container neu..."
docker compose -f "$COMPOSE_FILE" up -d

echo ""
echo ">>> Warte auf Postgres (gesund)..."
sleep 5

echo ""
echo ">>> Führe Datenbank-Migrationen aus..."
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan migrate --force

echo ""
read -p "Möchten Sie auch die Seeders ausführen? (yes/no): " seed_confirm

if [ "$seed_confirm" = "yes" ]; then
    echo ""
    echo ">>> Führe Database Seeder aus..."
    docker compose -f "$COMPOSE_FILE" exec php-cli php artisan db:seed --force
fi

echo ""
echo ">>> Optimiere Laravel..."
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec php-cli php artisan view:cache

echo ""
echo "=========================================="
echo "✅ GrowDash Datenbank erfolgreich neu aufgesetzt!"
echo "=========================================="
echo ""
echo "Zugriff:"
echo "  - Lokal:    http://localhost:6480"
echo "  - Extern:   http://192.168.178.12:6480"
echo "  - Public:   https://grow.linn.games"
echo ""
