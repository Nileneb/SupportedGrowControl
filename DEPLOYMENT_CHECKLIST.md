# Production Deployment Checklist

## ✅ Vorbereitung abgeschlossen

### Backend (Laravel)

-   [x] Sanctum installiert & migriert (personal_access_tokens)
-   [x] Device-Auth Middleware implementiert
-   [x] Alle Agent-API-Controller vollständig (Telemetry, Commands, Logs, Capabilities, Heartbeat)
-   [x] User-API-Controller vollständig (Command send/history)
-   [x] BoardType-Tabelle & Seeder (5 gängige Boards)
-   [x] Multi-Tenant-Isolation geprüft (request->user('device') / Auth::id())
-   [x] Bootstrap/Pairing-Flow vollständig
-   [x] Direct-Login-Flow (Sanctum) vollständig
-   [x] Device-Liste UI (Livewire)
-   [x] Pairing-UI (Volt Component)
-   [x] Feature-Tests (Onboarding, Telemetry, Commands, Logs)

### Database

-   [x] Migrations ausgeführt (devices, board_types, telemetry_readings, commands, device_logs)
-   [x] BoardTypeSeeder ausgeführt
-   [x] Indices für Performance (last_seen_at, bootstrap_code, public_id)

### API-Routen

-   [x] `/api/agents/bootstrap` (public)
-   [x] `/api/agents/pairing/status` (public)
-   [x] `/api/auth/login` (public)
-   [x] `/api/auth/logout` (auth:sanctum)
-   [x] `/api/devices/pair` (auth:web)
-   [x] `/api/growdash/devices/register` (auth:sanctum, Alias)
-   [x] `/api/growdash/devices/register-from-agent` (auth:sanctum)
-   [x] `/api/growdash/agent/*` (device.auth)
-   [x] `/api/growdash/devices/{device}/commands` (auth:sanctum)

### Web-Routen

-   [x] `/devices` - Device-Liste
-   [x] `/devices/pair` - Pairing-UI
-   [x] `/devices/{device}` - Device-Details

## 📋 Vor Production-Deploy

### Cleanup

-   [ ] Test-Helper-Skripte entfernen (`check_token.php`, `create_test_user.php`)
-   [ ] `.env` prüfen: `APP_ENV=production`, `APP_DEBUG=false`
-   [ ] `APP_KEY` generiert & gesetzt
-   [ ] Sanctum `SANCTUM_STATEFUL_DOMAINS` gesetzt (grow.linn.games)
-   [ ] CORS-Config für Agent-Endpoints (`config/cors.php`)

### Security

-   [ ] Rate-Limiting aktivieren:
    -   `/api/auth/login` (5 pro Minute)
    -   `/api/agents/bootstrap` (10 pro Minute)
    -   `/api/devices/pair` (10 pro Minute)
-   [ ] HTTPS erzwingen (`AppServiceProvider` oder Middleware)
-   [ ] Database-Credentials rotieren
-   [ ] Sanctum Token-Expiry setzen (`config/sanctum.php`)

### Performance

-   [ ] `php artisan config:cache`
-   [ ] `php artisan route:cache`
-   [ ] `php artisan view:cache`
-   [ ] `php artisan optimize`
-   [ ] Redis für Cache & Sessions (optional)
-   [ ] Queue-Worker für Broadcasting (optional)

### Monitoring

-   [ ] Laravel Telescope (nur Dev) deaktivieren/entfernen
-   [ ] Error-Logging konfigurieren (Sentry/Bugsnag)
-   [ ] Application Performance Monitoring (APM)
-   [ ] Database-Slow-Query-Log aktivieren

### Reverb (WebSockets)

-   [ ] `php artisan reverb:install` (manuell publishen falls hängt)
-   [ ] Broadcasting-Config setzen (`BROADCAST_DRIVER=reverb`)
-   [ ] Reverb-Server starten (`php artisan reverb:start`)
-   [ ] SSL-Config für WebSocket-Proxy (Nginx)

### Deployment-Prozess

-   [ ] `composer install --no-dev --optimize-autoloader`
-   [ ] `npm run build`
-   [ ] `php artisan migrate --force`
-   [ ] `php artisan db:seed --class=BoardTypeSeeder`
-   [ ] `php artisan storage:link`
-   [ ] File-Permissions setzen (`storage/`, `bootstrap/cache/`)

### Docker-Deployment (optional)

-   [ ] `docker-compose.yml` prüfen (Port 6480, Env-Vars)
-   [ ] `scripts/deploy.sh` anpassen (DB-Credentials, Domain)
-   [ ] Nginx-Config für Agent-API & WebUI
-   [ ] SSL-Zertifikate (Let's Encrypt)

## 🧪 Testing vor Go-Live

### Manual Tests

-   [ ] User-Login über Web-UI
-   [ ] Device-Pairing (6-stelliger Code)
-   [ ] Direct-Login (Sanctum) vom Python-Agent
-   [ ] Telemetrie senden (Agent → Laravel)
-   [ ] Commands senden (Web-UI → Agent)
-   [ ] Heartbeat & last_seen_at Update
-   [ ] Device-Liste zeigt Status korrekt an

### Load Tests

-   [ ] 10 Devices gleichzeitig Telemetrie senden
-   [ ] 100 Commands/Minute verarbeiten
-   [ ] Pairing-Flow unter Last (5 gleichzeitige Pairings)

### Security Tests

-   [ ] Invalid Device-Token → 403
-   [ ] Cross-User Device-Access → 404
-   [ ] SQL-Injection Tests (API-Endpoints)
-   [ ] CSRF-Protection (Web-UI)

## 🚀 Go-Live

1. **DNS-Update**: `grow.linn.games` → Server-IP
2. **SSL aktivieren**: Let's Encrypt oder Wildcard-Cert
3. **Deploy-Script**: `./scripts/deploy.sh` ausführen
4. **Health-Check**: `/api/health` (optional implementieren)
5. **Monitoring aktivieren**: Logs, APM, Error-Tracking
6. **Backup-Strategie**: DB-Dumps, Code-Repo

## 📊 Post-Deployment

-   [ ] Monitoring-Dashboard prüfen (Uptime, Response-Times)
-   [ ] Error-Log initial prüfen (erste 24h)
-   [ ] Agent-Logs prüfen (Connectivity, Token-Auth)
-   [ ] User-Feedback sammeln (Pairing-UX, Device-Status)

---

**Status**: ✅ Laravel-Backend production-ready  
**Deployment**: Bereit für Docker Compose oder manuelles Deployment  
**Dokumentation**: LARAVEL_BACKEND_READY.md, README.md aktualisiert
