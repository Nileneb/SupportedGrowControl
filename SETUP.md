# Growdash Laravel Integration - Setup Guide

## Schritt-für-Schritt Installation

### 1. Umgebungsvariablen konfigurieren

Kopiere `.env.example` zu `.env` (falls noch nicht geschehen):

```bash
cp .env.example .env
```

Bearbeite `.env` und setze die Growdash-Konfiguration:

```env
GROWDASH_DEVICE_SLUG=growdash-1
GROWDASH_WEBHOOK_TOKEN=IhrSicheresTokenHier
GROWDASH_PYTHON_BASE_URL=http://192.168.178.12:8000
```

**Wichtig:** Generiere ein sicheres Token für Produktion:

```bash
# Sicheres Token generieren
openssl rand -base64 32
```

### 2. Application Key generieren (falls noch nicht vorhanden)

```bash
php artisan key:generate
```

### 3. Datenbank migrieren

```bash
php artisan migrate
```

Dies erstellt folgende Tabellen:

-   `devices` - Growdash-Geräte
-   `water_levels` - Wasserstand-Messungen
-   `tds_readings` - TDS-Werte
-   `temperature_readings` - Temperatur-Messungen
-   `spray_events` - Sprüh-Ereignisse
-   `fill_events` - Füll-Ereignisse
-   `system_statuses` - Aktuelle System-Status
-   `arduino_logs` - Rohe Arduino-Logs

### 4. Initial-Device erstellen

```bash
php artisan db:seed --class=DeviceSeeder
```

Oder über den DatabaseSeeder:

```bash
php artisan db:seed
```

### 5. Tests ausführen (optional, aber empfohlen)

```bash
php artisan test --filter Growdash
```

Oder alle Tests:

```bash
php artisan test
```

## Webhook-Integration

### Python-Backend-Konfiguration

Passe dein Python-Script an, um Logs an Laravel zu senden:

```python
import requests
import os

LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', 'http://localhost/api/growdash')
WEBHOOK_TOKEN = os.getenv('GROWDASH_WEBHOOK_TOKEN', 'your-token-here')
DEVICE_SLUG = os.getenv('GROWDASH_DEVICE_SLUG', 'growdash-1')

def send_log_to_laravel(message, level='info'):
    """Send a log message to Laravel webhook endpoint."""
    try:
        response = requests.post(
            f'{LARAVEL_API_URL}/log',
            json={
                'device_slug': DEVICE_SLUG,
                'message': message,
                'level': level,
            },
            headers={'X-Growdash-Token': WEBHOOK_TOKEN},
            timeout=5
        )
        response.raise_for_status()
        return True
    except Exception as e:
        print(f"Failed to send log to Laravel: {e}")
        return False

# Beispiel-Verwendung
send_log_to_laravel("WaterLevel: 75.3")
send_log_to_laravel("TDS: 450.2")
send_log_to_laravel("Temp: 22.5")
send_log_to_laravel("Spray: ON")
```

### Alternative: Strukturierte Events senden

```python
def send_event_to_laravel(event_type, payload):
    """Send a structured event to Laravel."""
    try:
        response = requests.post(
            f'{LARAVEL_API_URL}/event',
            json={
                'device_slug': DEVICE_SLUG,
                'type': event_type,
                'payload': payload,
            },
            headers={'X-Growdash-Token': WEBHOOK_TOKEN},
            timeout=5
        )
        response.raise_for_status()
        return True
    except Exception as e:
        print(f"Failed to send event to Laravel: {e}")
        return False

# Beispiel: Wasserstand senden
send_event_to_laravel('water_level', {
    'level_percent': 75.3,
    'liters': 15.2,
    'measured_at': datetime.now().isoformat()
})
```

## API-Endpunkte testen

### Status abfragen

```bash
curl http://localhost/api/growdash/status?device_slug=growdash-1
```

### Wasserstand-Historie

```bash
curl http://localhost/api/growdash/water-history?device_slug=growdash-1&limit=10
```

### Manuelles Sprühen aktivieren

```bash
curl -X POST http://localhost/api/growdash/manual-spray \
  -H "Content-Type: application/json" \
  -H "X-Growdash-Token: IhrToken" \
  -d '{"device_slug":"growdash-1","action":"on"}'
```

### Log senden (Test)

```bash
curl -X POST http://localhost/api/growdash/log \
  -H "Content-Type: application/json" \
  -H "X-Growdash-Token: IhrToken" \
  -d '{"device_slug":"growdash-1","message":"WaterLevel: 75.3","level":"info"}'
```

## Produktions-Deployment

### Sicherheits-Checkliste

-   [ ] **Starkes Webhook-Token** generiert und gesetzt
-   [ ] **HTTPS aktiviert** für alle API-Endpunkte
-   [ ] **Rate Limiting** für Webhooks konfigurieren (optional)
-   [ ] **Authentifizierung** für öffentliche API-Endpunkte hinzufügen (falls gewünscht)
-   [ ] **Logs rotieren** (storage/logs)
-   [ ] **Queue-Worker** für asynchrone Verarbeitung (optional)

### Optional: Rate Limiting hinzufügen

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'growdash.webhook' => \App\Http\Middleware\VerifyGrowdashToken::class,
    ]);

    // Rate Limiting für Webhooks
    $middleware->throttleApi('growdash:60,1'); // 60 Requests pro Minute
})
```

### Optional: Queue für Logs

Für bessere Performance bei vielen Logs:

```php
// In GrowdashWebhookController
use Illuminate\Support\Facades\Queue;

public function log(Request $request): JsonResponse
{
    $data = $request->validate([...]);

    // Job in Queue stellen
    \App\Jobs\ProcessGrowdashLog::dispatch($data);

    return response()->json(['success' => true]);
}
```

## Troubleshooting

### Problem: "Invalid Growdash token"

**Lösung:** Stelle sicher, dass der `X-Growdash-Token` Header korrekt gesetzt ist und mit `GROWDASH_WEBHOOK_TOKEN` in `.env` übereinstimmt.

### Problem: Migrations schlagen fehl

**Lösung:**

```bash
php artisan migrate:fresh --seed
```

**Warnung:** Dies löscht alle Daten!

### Problem: Tests schlagen fehl

**Lösung:** Stelle sicher, dass die Test-Datenbank konfiguriert ist:

```env
# In .env oder phpunit.xml
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Problem: API-Routen nicht gefunden (404)

**Lösung:** Stelle sicher, dass `routes/api.php` in `bootstrap/app.php` registriert ist:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',  // <- Diese Zeile
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

## Nächste Schritte

1. **Frontend erstellen** (Livewire/Flux Dashboard)
2. **Echtzeit-Updates** (WebSockets mit Laravel Reverb)
3. **Benachrichtigungen** (Mail/Slack bei kritischen Events)
4. **Charts/Visualisierung** (ApexCharts, Chart.js)
5. **Multi-User-Support** (Authentifizierung, Autorisierung)

## Weitere Devices hinzufügen

Bearbeite `database/seeders/DeviceSeeder.php`:

```php
$devices = [
    [
        'name' => 'Growdash Primary',
        'slug' => 'growdash-1',
        'ip_address' => '192.168.178.12',
        'serial_port' => '/dev/ttyUSB0',
    ],
    [
        'name' => 'Growdash Secondary',
        'slug' => 'growdash-2',
        'ip_address' => '192.168.178.13',
        'serial_port' => '/dev/ttyUSB1',
    ],
];
```

Dann:

```bash
php artisan db:seed --class=DeviceSeeder
```

## Support

Bei Fragen oder Problemen siehe:

-   `README.md` für Projektübersicht
-   `tests/Feature/Growdash*.php` für Beispiele
-   Laravel-Dokumentation: https://laravel.com/docs
