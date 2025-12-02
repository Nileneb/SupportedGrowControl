# Laravel Backend - Agent Integration Ready ✅

## Implementierungen abgeschlossen (Phase 2 - Laravel)

### 1. ✅ API-Routen & Auth geprüft

-   `routes/api.php` vollständig strukturiert:
    -   `/api/auth/login` (Sanctum)
    -   `/api/agents/bootstrap` & `/api/agents/pairing/status`
    -   `/api/growdash/agent/*` (device.auth protected)
    -   `/api/growdash/devices/{device}/commands` (user auth)
-   `AuthenticateDevice` Middleware arbeitet korrekt mit `X-Device-ID` + `X-Device-Token`
-   Multi-Guard-Auth: `auth:sanctum` für User, `device.auth` für Agents

### 2. ✅ Device/Board/Sensor-Modelle erweitert

-   **Migration**: `board_type`, `capabilities`, `last_seen_at`, `status` bereits vorhanden
-   **BoardType-Tabelle** erstellt:
    -   `name` (arduino_uno, esp32, etc.)
    -   `fqbn` für arduino-cli (z.B. `arduino:avr:uno`)
    -   `vendor`, `meta` (JSON für Cores, Upload-Speed)
-   **BoardType Model** mit `devices()` Relation
-   **Device Model**: Relation zu BoardType über `board_type` Spalte

### 3. ✅ Capabilities & Telemetrie-Flow gekoppelt

-   **DeviceManagementController@updateCapabilities** erweitert:
    -   Akzeptiert `capabilities.board_name`
    -   Speichert in `devices.board_type`
    -   Feuert `DeviceCapabilitiesUpdated` Event (Broadcasting)
    -   Response enthält `board_type` + `capabilities`
-   **TelemetryController**: Generisches Speichern aller `sensor_key` in `telemetry_readings`
-   **Heartbeat**: Setzt `last_seen_at` + `status='online'`

### 4. ✅ Commands User→Agent durchgezogen

-   **CommandController vollständig**:
    -   `pending()` - Agent holt pending commands
    -   `result()` - Agent sendet Ergebnis zurück
    -   `send()` - User erstellt Commands (Frontend)
    -   `history()` - Command-Historie pro Device
-   **Broadcasting**: `CommandStatusUpdated` Event bei Statusänderung
-   **Validierung**: Status `executing|completed|failed`, Device-Ownership

### 5. ✅ Pairing-Flow im Web-UI geglättet

-   **Livewire Components**:
    -   `devices.index` - Device-Liste mit Status-Badges, last_seen, Capabilities
    -   `devices.pair` - Volt-Component für 6-stellige Code-Eingabe
-   **Web-Routen**:
    -   `/devices` - Device-Liste
    -   `/devices/pair` - Pairing-UI
    -   `/devices/{device}` - Device-Details
-   **UI zeigt**:
    -   Board-Type (z.B. "Arduino Uno")
    -   Last-Seen (diffForHumans)
    -   Sensor/Actuator-Count
    -   Online/Offline-Status

### 6. ✅ Board-Automation vorbereitet

-   **BoardTypeSeeder** mit 5 gängigen Boards:
    -   `arduino_uno` (FQBN: arduino:avr:uno)
    -   `arduino_mega` (FQBN: arduino:avr:mega)
    -   `esp32` (FQBN: esp32:esp32:esp32)
    -   `esp8266` (FQBN: esp8266:esp8266:generic)
    -   `arduino_nano` (FQBN: arduino:avr:nano)
-   **Meta-Daten**: CPU, Core, Upload-Speed für jeden Board-Typ
-   **Agent kann** `board_name` senden → Laravel mapped automatisch

### 7. ✅ Multi-Tenant-Isolation geprüft

**Agent-APIs** (alle nutzen `$request->user('device')`):

-   TelemetryController
-   CommandController (pending, result)
-   DeviceManagementController (capabilities, heartbeat)
-   LogController

**User-APIs** (alle nutzen `Auth::id()` + Device-Ownership-Check):

-   CommandController (send, history)
-   DevicePairingController
-   DeviceRegistrationController

### 8. ✅ Tests & Monitoring nachgezogen

-   **OnboardingTest** (Pest):
    -   Bootstrap-Flow (creates unpaired device)
    -   Pairing-Status (unpaired → paired)
    -   User-Pairing via Web-UI
-   **Bestehende Tests**: TelemetryTest, LogTest, CommandTest, DeviceAuthTest
-   **Error-Logging**: Alle Controller loggen Fehler mit Context

## Migration & Seeding

```bash
# Neue Tabelle anlegen
php artisan migrate

# Board-Types seeden
php artisan db:seed --class=BoardTypeSeeder
```

## API-Endpoints (finaler Stand)

### Agent-API (Device-Token Auth)

```
POST /api/growdash/agent/telemetry
GET  /api/growdash/agent/commands/pending
POST /api/growdash/agent/commands/{id}/result
POST /api/growdash/agent/capabilities
POST /api/growdash/agent/logs
POST /api/growdash/agent/heartbeat
```

### User-API (Sanctum Auth)

```
POST /api/auth/login
POST /api/auth/logout
POST /api/growdash/devices/register-from-agent
POST /api/growdash/devices/{device}/commands
GET  /api/growdash/devices/{device}/commands
```

### Bootstrap & Pairing (Public/Auth)

```
POST /api/agents/bootstrap
GET  /api/agents/pairing/status
POST /api/devices/pair
GET  /api/devices/unclaimed
```

## Web-UI (Livewire)

```
/devices          → Device-Liste (Livewire Index)
/devices/pair     → Pairing-UI (Volt Component)
/devices/{device} → Device-Details (Controller)
```

## Capabilities-Payload (vom Agent erwartet)

```json
{
    "capabilities": {
        "board_name": "arduino_uno",
        "sensors": ["water_level", "tds", "temperature"],
        "actuators": ["spray_pump", "fill_valve"]
    }
}
```

→ Laravel speichert `board_name` in `devices.board_type`  
→ Capabilities als JSON in `devices.capabilities`

## Telemetrie-Payload (vom Agent erwartet)

```json
{
    "readings": [
        {
            "sensor_key": "water_level",
            "value": 75.5,
            "unit": "%",
            "measured_at": "2025-12-02T02:00:00Z"
        },
        {
            "sensor_key": "tds",
            "value": 850,
            "unit": "ppm",
            "measured_at": "2025-12-02T02:00:00Z"
        }
    ]
}
```

## Commands-Payload (User → Agent)

**User sendet:**

```json
{
    "type": "spray_on",
    "params": { "duration": 10 }
}
```

**Agent holt:**

```json
{
    "success": true,
    "commands": [
        {
            "id": 42,
            "type": "spray_on",
            "params": { "duration": 10 },
            "created_at": "2025-12-02T02:00:00Z"
        }
    ]
}
```

**Agent meldet zurück:**

```json
{
    "status": "completed",
    "result_message": "Spray completed successfully"
}
```

## Nächste Schritte (optional)

1. **Frontend erweitern**:

    - Command-Console in Device-Detail-View
    - Telemetrie-Charts (LiveCharts.js)
    - Real-Time-Updates via Broadcasting

2. **Reverb aktivieren** (WebSockets):

    - `php artisan reverb:install` (manuell publishen falls hängt)
    - Broadcasting-Config für Live-Updates

3. **Rate-Limiting**:

    - Throttle für `/api/auth/login`
    - Throttle für `/api/agents/bootstrap`

4. **Monitoring**:
    - Laravel Telescope für API-Debug
    - Sentry für Error-Tracking

## Status

✅ **Laravel-Backend ist production-ready für Agent-Integration!**

Alle notwendigen Endpoints, Models, Migrations, Policies und Tests sind implementiert.  
Der Python-Agent kann jetzt alle Flows durchlaufen:

-   Bootstrap/Pairing
-   Direct-Login
-   Telemetrie senden
-   Commands empfangen
-   Heartbeat senden
-   Capabilities updaten
