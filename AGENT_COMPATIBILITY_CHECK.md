# Agent Kompatibilitäts-Check

**Datum**: 2. Dezember 2025  
**Status**: ✅ Laravel Backend ist Agent-ready

---

## Durchgeführte Anpassungen

### 1. ✅ AuthenticateDevice Middleware aktualisiert

**Problem**: Controller konnten Device nicht via `$request->user()` abrufen  
**Lösung**: Middleware setzt jetzt sowohl `userResolver` als auch `attributes`

```php
// app/Http/Middleware/AuthenticateDevice.php
$request->setUserResolver(fn () => $device);
$request->attributes->set('device', $device);
```

### 2. ✅ Controller aktualisiert

Alle Agent-Controller nutzen jetzt `$request->user()` statt `$request->user('device')`:

- ✅ `CommandController::pending()`
- ✅ `CommandController::result()`
- ✅ `TelemetryController::store()`
- ✅ `DeviceManagementController::updateCapabilities()`
- ✅ `DeviceManagementController::heartbeat()`
- ✅ `LogController::store()`

---

## API-Endpoints Verfügbarkeit

### Agent-Endpoints (Device-Token Auth)

| Endpoint | Controller | Status |
|----------|-----------|--------|
| `POST /api/growdash/agent/heartbeat` | DeviceManagementController@heartbeat | ✅ |
| `POST /api/growdash/agent/telemetry` | TelemetryController@store | ✅ |
| `GET /api/growdash/agent/commands/pending` | CommandController@pending | ✅ |
| `POST /api/growdash/agent/commands/{id}/result` | CommandController@result | ✅ |
| `POST /api/growdash/agent/capabilities` | DeviceManagementController@updateCapabilities | ✅ |
| `POST /api/growdash/agent/logs` | LogController@store | ✅ |

### User-Endpoints (Sanctum Auth)

| Endpoint | Controller | Status |
|----------|-----------|--------|
| `POST /api/growdash/devices/{device}/commands` | CommandController@send | ✅ |
| `GET /api/growdash/devices/{device}/commands` | CommandController@history | ✅ |

### Auth-Endpoints

| Endpoint | Controller | Status |
|----------|-----------|--------|
| `POST /api/auth/login` | AuthController@login | ✅ |
| `POST /api/auth/logout` | AuthController@logout | ✅ |

### Onboarding-Endpoints

| Endpoint | Controller | Status |
|----------|-----------|--------|
| `POST /api/agents/bootstrap` | BootstrapController@bootstrap | ✅ |
| `GET /api/agents/pairing/status` | BootstrapController@status | ✅ |
| `POST /api/devices/pair` | DevicePairingController@pair | ✅ |
| `POST /api/growdash/devices/register` | DeviceController@register | ✅ |

---

## Agent-Erwartungen vs. Laravel-Implementierung

### Heartbeat (Agent ➜ Laravel)

**Agent sendet:**
```python
POST /api/growdash/agent/heartbeat
Headers:
  X-Device-ID: 0709c4d2-14a9-4716-a7e4-663bb8acaa66
  X-Device-Token: <64-char-token>
Body:
{
  "last_state": {
    "uptime": 3600,
    "memory_free": 45000,
    "python_version": "3.12.0",
    "platform": "linux"
  }
}
```

**Laravel erwartet:**
```php
// ✅ KORREKT - Middleware verifiziert Token
// ✅ KORREKT - Controller setzt last_seen_at + status='online'
// ✅ KORREKT - last_state wird in JSON-Spalte gespeichert
```

**Response:**
```json
{
  "success": true,
  "message": "Heartbeat received",
  "last_seen_at": "2025-12-02T12:34:56.000000Z"
}
```

---

### Telemetrie (Agent ➜ Laravel)

**Agent sendet:**
```python
POST /api/growdash/agent/telemetry
Body:
{
  "readings": [
    {
      "measured_at": "2025-12-02T10:30:00.000000Z",
      "sensor_key": "water_level",
      "value": 45.5,
      "unit": "percent",
      "raw": "WaterLevel: 45"
    },
    {
      "measured_at": "2025-12-02T10:30:00.000000Z",
      "sensor_key": "tds",
      "value": 320,
      "unit": "ppm",
      "raw": "TDS=320 TempC=22.5"
    }
  ]
}
```

**Laravel erwartet:**
```php
// ✅ KORREKT - Format passt 1:1
// ✅ KORREKT - Validiert gegen Capabilities (falls gesetzt)
// ✅ KORREKT - Speichert in telemetry_readings Tabelle
// ✅ KORREKT - Updated last_state mit aktuellen Sensor-Werten
```

---

### Commands Polling (Agent ➜ Laravel)

**Agent fragt:**
```python
GET /api/growdash/agent/commands/pending
```

**Laravel liefert:**
```json
{
  "success": true,
  "commands": [
    {
      "id": 42,
      "type": "serial_command",
      "params": {"command": "STATUS"},
      "created_at": "2025-12-02T10:30:00.000000Z"
    }
  ]
}
```

**Agent erwartet:**
- ✅ `success` Feld
- ✅ `commands` Array
- ✅ Felder: `id`, `type`, `params`, `created_at`

**⚠️ WICHTIG**: Agent nutzt **beide** Command-Formate:

1. **Neu (serial_command)**:
   ```json
   {
     "type": "serial_command",
     "params": {"command": "STATUS"}
   }
   ```
   → Agent sendet `params['command']` direkt ans Arduino

2. **Legacy (spray_on, fill_start, etc.)**:
   ```json
   {
     "type": "spray_on",
     "params": {"duration": 5}
   }
   ```
   → Agent übersetzt in Arduino-Befehl ("SprayOn" oder "Spray 5000")

---

### Command Result (Agent ➜ Laravel)

**Agent sendet:**
```python
POST /api/growdash/agent/commands/42/result
Body:
{
  "status": "completed",
  "result_message": "Command 'STATUS' sent to Arduino"
}
```

**Laravel erwartet:**
```php
// ✅ KORREKT - status: executing|completed|failed
// ✅ KORREKT - result_message optional
// ✅ KORREKT - completed_at wird automatisch gesetzt
// ✅ KORREKT - Broadcastet CommandStatusUpdated Event
```

---

### Capabilities (Agent ➜ Laravel)

**Agent sendet beim Start:**
```python
POST /api/growdash/agent/capabilities
Body:
{
  "capabilities": {
    "board_name": "arduino_uno",
    "sensors": ["water_level", "tds", "temperature"],
    "actuators": ["spray_pump", "fill_valve"]
  }
}
```

**Laravel Validierung:**
- ✅ Akzeptiert vereinfachtes Format vom Agent
- ✅ Speichert in `capabilities` JSON-Spalte
- ✅ Extrahiert `board_name` → `board_type` Spalte
- ✅ Broadcastet DeviceCapabilitiesUpdated Event

---

### Logs (Agent ➜ Laravel)

**Agent sendet (batched):**
```python
POST /api/growdash/agent/logs
Body:
{
  "logs": [
    {
      "level": "info",
      "message": "Agent gestartet",
      "timestamp": "2025-12-02T10:30:00.000000Z",
      "context": {"logger": "__main__"}
    }
  ]
}
```

**Laravel erwartet:**
```php
// ✅ KORREKT - level: debug|info|warning|error
// ✅ KORREKT - message (max 5000 Zeichen)
// ✅ KORREKT - context (optional JSON)
// ✅ KORREKT - Speichert in device_logs Tabelle
```

---

## Testen

### 1. Heartbeat testen

```bash
# Im Agent-Verzeichnis
cd ~/nileneb-growdash
./test_heartbeat.sh
```

**Erwartete Ausgabe:**
```
✅ Heartbeat erfolgreich!
HTTP Status: 200
{
  "success": true,
  "message": "Heartbeat received",
  "last_seen_at": "2025-12-02T12:34:56.000000Z"
}
```

### 2. Commands testen

**Via Laravel Tinker (Command erstellen):**
```bash
docker exec supportedgrowcontrol-php-cli-1 php artisan tinker --execute="
\$user = App\Models\User::first();
\$device = \$user->devices->first();

\$cmd = App\Models\Command::create([
    'device_id' => \$device->id,
    'created_by_user_id' => \$user->id,
    'type' => 'serial_command',
    'params' => ['command' => 'STATUS'],
    'status' => 'pending',
]);

echo 'Command erstellt: ID=' . \$cmd->id;
"
```

**Agent sollte Command abholen:**
```bash
# Agent Logs prüfen
# Erwartete Ausgabe:
# INFO - Empfangene Befehle: 1
# INFO - Führe Befehl aus: serial_command
# INFO - Befehl an Arduino: STATUS
# INFO - Befehlsergebnis gemeldet: 42 -> completed
```

### 3. End-to-End Test

**Im Browser (Frontend):**
1. Öffne Device-Detail-Seite: `https://grow.linn.games/devices/{device-slug}`
2. Gib Command in Serial Console ein: `STATUS`
3. Klicke "Send"

**Erwarteter Ablauf:**
```
Frontend
  ↓ POST /api/growdash/devices/{device}/commands
Laravel (CommandController@send)
  ↓ Erstellt Command mit status='pending'
Agent (command_loop, alle 5s)
  ↓ GET /api/growdash/agent/commands/pending
Agent (execute_command)
  ↓ Sendet "STATUS" ans Arduino
Agent
  ↓ POST /api/growdash/agent/commands/{id}/result
Laravel (CommandController@result)
  ↓ Update status='completed', broadcast Event
Frontend (polling oder WebSocket)
  ↓ Zeigt Ergebnis in Command History
```

---

## Bekannte Unterschiede Agent vs. Backend

### 1. Command-Typen

**Agent unterstützt:**
- `serial_command` (neu, empfohlen)
- `spray_on`, `spray_off` (legacy)
- `fill_start`, `fill_stop` (legacy)
- `request_status`, `request_tds` (legacy)
- `firmware_update` (spezial)

**Backend (CommandController@send) validiert:**
- Prüft gegen `device->capabilities['actuators']`
- Falls Capabilities gesetzt, muss Command-Type dort existieren
- Falls keine Capabilities, wird Command durchgelassen

**⚠️ Empfehlung**: Frontend sollte `serial_command` nutzen für maximale Flexibilität

### 2. Telemetrie sensor_key

**Agent sendet:**
- `water_level`, `tds`, `temperature`, `spray_status`, `fill_status`

**Backend erwartet:**
- Beliebige `sensor_key` (String, max 50 Zeichen)
- Optional: Validierung gegen `device->capabilities['sensors']`

### 3. Heartbeat-Intervall

**Agent**: Alle 30s  
**Backend**: Erwartet < 2 Minuten (sonst Status → offline)

**Cron-Job für Offline-Marking** (noch nicht implementiert):
```php
// app/Console/Commands/MarkOfflineDevices.php
Device::where('last_seen_at', '<', now()->subMinutes(2))
      ->where('status', 'online')
      ->update(['status' => 'offline']);
```

**Registrierung in** `routes/console.php`:
```php
Schedule::command('devices:mark-offline')->everyMinute();
```

---

## Fehlende Features (Optional)

### 1. WebSocket für Real-time Updates

**Aktuell**: Frontend pollt alle 10s  
**Besser**: Laravel Reverb/Pusher Broadcasting

**Events die bereits gebroadcastet werden:**
- ✅ `CommandStatusUpdated` (in CommandController)
- ✅ `DeviceTelemetryReceived` (in TelemetryController)
- ✅ `DeviceCapabilitiesUpdated` (in DeviceManagementController)

**Frontend muss nur Echo.js einbinden und subscriben!**

### 2. Device Offline Cron-Job

Siehe oben unter "Heartbeat-Intervall"

### 3. Firmware Update Endpoint

**Agent erwartet** (laut AGENT_API_UPDATE.md):
```
POST /api/growdash/agent/firmware/update
Body:
{
  "module_id": "main",
  "checksum": "sha256:abc123...",
  "version": "1.0.0"
}
```

**Status**: ❌ Nicht implementiert (Agent macht Firmware-Updates lokal mit arduino-cli)

**Alternative**: Agent führt Firmware-Updates lokal aus, loggt nur das Ergebnis ans Backend via `/logs`

---

## Status-Übersicht

| Feature | Agent-Code | Laravel-Backend | Status |
|---------|-----------|-----------------|--------|
| Device-Token-Auth | ✅ | ✅ | ✅ Ready |
| Heartbeat | ✅ | ✅ | ✅ Ready |
| Telemetrie | ✅ | ✅ | ✅ Ready |
| Command Polling | ✅ | ✅ | ✅ Ready |
| Command Result | ✅ | ✅ | ✅ Ready |
| Capabilities | ✅ | ✅ | ✅ Ready |
| Logs Batching | ✅ | ✅ | ✅ Ready |
| Pairing-Code-Flow | ✅ | ✅ | ✅ Ready |
| Direct-Login-Flow | ✅ | ✅ | ✅ Ready |
| Frontend Commands | ⏳ | ✅ | ⚠️ Testen |
| WebSocket Events | ❌ | ✅ Broadcast | ⏳ Frontend TODO |
| Offline Cron-Job | N/A | ❌ | ⏳ TODO |

---

## Deployment-Checklist

### Laravel (Docker)

- [x] Middleware `device.auth` registriert
- [x] Routes unter `/api/growdash/agent` definiert
- [x] Controller erstellt und funktional
- [x] Migrations ausgeführt (devices, commands, telemetry_readings, device_logs)
- [x] Device Model hat `verifyAgentToken()` Methode
- [ ] Cron-Job für Offline-Marking (optional)
- [ ] Laravel Reverb/Broadcasting konfiguriert (optional)

### Agent (Raspberry Pi)

- [x] Python 3.12+, venv, requirements.txt
- [x] `.env` mit LARAVEL_BASE_URL + Device-Credentials
- [x] Serial-Port Berechtigungen (dialout Gruppe)
- [x] Arduino-CLI installiert (für Firmware-Updates)
- [x] Onboarding-Wizard (bootstrap.py)
- [x] Systemd-Service (grow_start.sh)

### Frontend

- [x] Serial Console UI (show.blade.php)
- [x] Command Sending (POST /api/growdash/devices/{device}/commands)
- [x] Command History (GET /api/growdash/devices/{device}/commands)
- [x] Polling (2s für Results, 10s für History)
- [ ] WebSocket Integration (Echo.js) - optional

---

## Nächste Schritte

1. **Testen Sie den Heartbeat**:
   ```bash
   cd ~/nileneb-growdash
   ./test_heartbeat.sh
   ```

2. **Starten Sie den Agent**:
   ```bash
   ./grow_start.sh
   ```

3. **Prüfen Sie die Logs**:
   ```bash
   # Agent Logs
   # Erwartete Ausgabe:
   # ✅ Heartbeat gesendet (uptime=30s)
   # ✅ Telemetrie gesendet: 5 Messwerte
   ```

4. **Senden Sie einen Test-Command** (via Frontend oder Tinker)

5. **Verifizieren Sie in der Datenbank**:
   ```bash
   docker exec supportedgrowcontrol-php-cli-1 php artisan tinker --execute="
   \$device = App\Models\Device::where('public_id', '0709c4d2-14a9-4716-a7e4-663bb8acaa66')->first();
   echo 'Status: ' . \$device->status;
   echo 'Last Seen: ' . \$device->last_seen_at;
   echo 'Commands: ' . \$device->commands()->count();
   "
   ```

---

**Fazit**: Laravel Backend ist **Agent-ready** ✅  
Alle kritischen Endpoints sind implementiert und kompatibel mit dem umfassend überarbeiteten Agent.
