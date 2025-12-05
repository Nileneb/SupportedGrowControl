# PRODUCTION DEPLOYMENT - Agent API

## 🚀 Wichtig für JETZT

Die Laravel-Backend ist **bereit**. Der Agent muss jetzt konfiguriert werden.

### Deployment-Checklist

- [x] Agent API endpoints sind implementiert
- [x] Device authentication ist konfiguriert  
- [x] Database migrations sind bereit
- [x] Alle routes sind registriert
- [ ] **NÄCHSTER SCHRITT:** Agent.py implementieren

---

## 📋 Was wurde gemacht

### ✅ Completed

1. **AgentController** - Komplett rewritten mit 9 Endpoints
   - `POST /heartbeat` - Device Status aktualisieren
   - `POST /telemetry` - Sensor-Daten speichern
   - `GET /commands/pending` - Commands abrufen
   - `POST /commands/{id}/result` - Command-Ergebnis melden
   - `POST /capabilities` - Device-Eigenschaften berichten
   - `GET /capabilities` - Device-Eigenschaften abrufen
   - `POST /logs` - Device-Logs speichern
   - `GET /ports` - Verfügbare Serial-Ports

2. **Routes** - Alle registriert in `routes/api.php`
   - Device.auth Middleware verwendet `\App\Http\Middleware\AuthenticateDevice::class`
   - Prefix: `/api/growdash/agent`

3. **Model Relationships** - Corrected
   - `telemetryReadings()` statt `telemetry()`
   - `deviceLogs()` statt `logs()`
   - `commands()` ✓

4. **Database** - Migration erstellt
   - `api_port` column zu devices table

5. **Fixes Applied**
   - Device-Token Verification mit SHA256
   - Telemetry mapping: type → sensor_key, timestamp → measured_at
   - Logs context JSON für timestamp storage

---

## 🔐 Agent Authentication

Agent muss folgende Headers ALLE Requests mitschicken:

```
X-Device-ID: growdash-XXXX        (public_id from database)
X-Device-Token: your_token_here   (plaintext, NOT hashed)
```

### Beispiel (Python Agent)

```python
import requests
import os
from datetime import datetime, timezone

DEVICE_ID = os.getenv('DEVICE_PUBLIC_ID')
DEVICE_TOKEN = os.getenv('DEVICE_TOKEN')
LARAVEL_URL = os.getenv('LARAVEL_BASE_URL', 'https://grow.linn.games')

HEADERS = {
    'X-Device-ID': DEVICE_ID,
    'X-Device-Token': DEVICE_TOKEN,
    'Content-Type': 'application/json',
}

def heartbeat():
    """Send heartbeat every 30 seconds"""
    url = f"{LARAVEL_URL}/api/growdash/agent/heartbeat"
    payload = {
        "ip_address": "192.168.1.100",  # Agent's IP
        "api_port": 8000
    }
    response = requests.post(url, headers=HEADERS, json=payload, timeout=10)
    print(f"Heartbeat: {response.status_code}")
    return response.json()

def get_pending_commands():
    """Poll for pending commands every 5 seconds"""
    url = f"{LARAVEL_URL}/api/growdash/agent/commands/pending"
    response = requests.get(url, headers=HEADERS, timeout=10)
    if response.status_code == 200:
        return response.json()['commands']
    return []

def send_telemetry(readings):
    """Send sensor readings"""
    url = f"{LARAVEL_URL}/api/growdash/agent/telemetry"
    payload = {"telemetry": readings}
    response = requests.post(url, headers=HEADERS, json=payload, timeout=10)
    return response.json()

def report_command_result(command_id, status, message, output="", error=""):
    """Report command execution result"""
    url = f"{LARAVEL_URL}/api/growdash/agent/commands/{command_id}/result"
    payload = {
        "status": status,  # "completed" or "failed"
        "result_message": message,
        "output": output,
        "error": error,
    }
    response = requests.post(url, headers=HEADERS, json=payload, timeout=10)
    return response.json()
```

---

## 📊 Telemetry Format

### Request Format

```json
{
  "telemetry": [
    {
      "type": "WaterLevel",
      "value": "45",
      "timestamp": "2025-12-05T10:30:00Z"
    },
    {
      "type": "Temperature",
      "value": "22.5",
      "timestamp": "2025-12-05T10:30:01Z"
    }
  ]
}
```

### Field Mapping

| Agent sendet | DB speichert als |
|---|---|
| `type` | `sensor_key` |
| `value` | `value` |
| `timestamp` (ISO8601) | `measured_at` |

### Valid Timestamps

```
✅ "2025-12-05T10:30:00Z"
✅ "2025-12-05T10:30:00UTC"
❌ "2025-12-05 10:30:00"
❌ "10:30:00"
```

---

## 🎮 Command Execution

### Incoming Command Example

```json
{
  "id": 123,
  "type": "serial_command",
  "params": {
    "command": "Status"
  }
}
```

### Command Types

| Type | Was Agent tun muss |
|---|---|
| `serial_command` | `params['command']` an Serial senden |
| `arduino_compile` | `params['code']` mit `arduino-cli compile` kompilieren |
| `arduino_upload` | `params['code']` mit `arduino-cli compile --upload` hochladen |
| `scan_ports` | Verfügbare Serial-Ports scannen |

### Response Format

**Success:**
```json
{
  "status": "completed",
  "result_message": "✅ Command executed successfully",
  "output": "stdout output here",
  "error": ""
}
```

**Failure:**
```json
{
  "status": "failed",
  "result_message": "❌ Serial port timeout",
  "output": "attempted output here",
  "error": "TimeoutError: No response after 500ms"
}
```

---

## 🔍 Debugging

### Check Device Status (Laravel)

```bash
php artisan tinker
>>> Device::where('public_id', 'growdash-abc123')->first()
```

### Check Recent Logs

```bash
# Agent logs (in agent container/server)
tail -f /var/log/growdash/agent.log

# Laravel logs
tail -f /var/log/growdash/laravel.log
```

### Manual API Test

```bash
curl -X GET "https://grow.linn.games/api/growdash/agent/commands/pending" \
  -H "X-Device-ID: growdash-your-id" \
  -H "X-Device-Token: your-token"
```

---

## ✅ Validation Rules

### Device Authentication
- ✅ Public ID must exist
- ✅ Device must have user_id
- ✅ Device must be paired (paired_at NOT NULL)
- ✅ Token must verify with SHA256 hash

### Telemetry
- ✅ Array mit 1-100 items
- ✅ Alle 3 Felder erforderlich: type, value, timestamp
- ✅ Timestamp muss ISO8601 Format sein

### Commands
- ✅ Nur "completed" oder "failed" status erlaubt
- ✅ result_message max 1000 chars
- ✅ output/error beliebig groß

---

## 🎯 Next Steps

1. **Agent Repository** - Implementierung
   - Heartbeat loop (30s)
   - Telemetry loop (10s)
   - Command polling loop (5s)
   - Serial communication handlers

2. **Testing**
   - Agent mit Device ID/Token testen
   - Heartbeat überprüfen
   - Telemetry speichern verifizieren
   - Commands ausführen

3. **Monitoring**
   - Agent restart bei Fehler
   - Logging konfigurieren
   - Health checks einrichten

---

**Version:** 2025-12-05  
**Status:** Ready for Agent Implementation
