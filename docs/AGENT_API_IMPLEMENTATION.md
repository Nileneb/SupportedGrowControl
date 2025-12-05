# Agent API - Implementation Guide

## Overview

Die Agent-API wurde komplett überarbeitet nach dem **SIMPLE. CLEAN. NO BULLSHIT.** Standard. Alle Endpoints sind dokumentiert und folgen konsistenten Patterns.

**Production URL:** `https://grow.linn.games/api/growdash/agent`  
**Local Testing:** `http://localhost:8000/api/growdash/agent`

---

## 🔐 Authentication

Alle Requests müssen diese Headers enthalten:

```http
X-Device-ID: {device.public_id}
X-Device-Token: {plaintext_token}
```

**Token wird gehashed:** Nur bei Device-Erstellung wird der Plaintext-Token zurückgegeben. Danach nur gehashed in DB.

---

## 📡 Implemented Endpoints

### 1. Heartbeat - Agent bleibt online

**POST** `/heartbeat`

```bash
curl -X POST https://grow.linn.games/api/growdash/agent/heartbeat \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token" \
  -H "Content-Type: application/json" \
  -d '{
    "ip_address": "192.168.1.100",
    "api_port": 8000
  }'
```

**Response:**

```json
{
    "success": true
}
```

**Was Laravel tut:**

-   `device.status = 'online'`
-   `device.last_seen_at = now()`
-   `device.ip_address = ...` (optional)
-   `device.api_port = ...` (optional, default 8000)

**Frequency:** Alle 30 Sekunden

---

### 2. Get Pending Commands - Agent holt Commands

**GET** `/commands/pending`

```bash
curl -X GET https://grow.linn.games/api/growdash/agent/commands/pending \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token"
```

**Response:**

```json
{
    "success": true,
    "commands": [
        {
            "id": 1,
            "type": "serial_command",
            "params": {
                "command": "Status"
            }
        },
        {
            "id": 2,
            "type": "arduino_upload",
            "params": {
                "code": "void setup() {...}",
                "board": "arduino:avr:uno",
                "port": "/dev/ttyACM0"
            }
        }
    ]
}
```

**Command Types:**

-   **`serial_command`** - Direktes Serial-Command (z.B. "Status")
-   **`arduino_compile`** - Code kompilieren (ohne Upload)
-   **`arduino_upload`** - Code kompilieren + uploaden
-   **`scan_ports`** - Serial-Ports scannen

**Frequency:** Alle 5 Sekunden

---

### 4. Report Command Result - Agent meldet Ergebnis

**POST** `/commands/{id}/result`

**Success-Case:**

```bash
curl -X POST https://grow.linn.games/api/growdash/agent/commands/1/result \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed",
    "result_message": "✅ Sketch uploaded successfully",
    "output": "Sketch uses 1234 bytes of program storage space...",
    "error": ""
  }'
```

**Failed-Case:**

```bash
curl -X POST https://grow.linn.games/api/growdash/agent/commands/2/result \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "failed",
    "result_message": "❌ Compilation failed",
    "output": "Linking everything together...",
    "error": "error: 'LED_BUILTIN' was not declared in this scope"
  }'
```

**Response:**

```json
{
    "success": true
}
```

**Was Laravel tut:**

-   Setzt `command.status = 'completed'` oder `'failed'`
-   Speichert `output` + `error` in `result_data` JSON-Feld
-   Setzt `completed_at = now()`
-   Triggered Event für Frontend-Update

---

### 5. Update Capabilities - Agent meldet Gerät

**POST** `/capabilities`

```bash
curl -X POST https://grow.linn.games/api/growdash/agent/capabilities \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token" \
  -H "Content-Type: application/json" \
  -d '{
    "board": {
      "name": "Arduino Uno",
      "type": "arduino:avr:uno",
      "firmware": "GrowDash v1.0"
    },
    "sensors": [
      {
        "id": "water_level",
        "name": "Water Level",
        "unit": "%",
        "min": 0,
        "max": 100
      },
      {
        "id": "temperature",
        "name": "Temperature",
        "unit": "°C",
        "min": -10,
        "max": 50
      }
    ],
    "actuators": [
      {
        "id": "spray_pump",
        "name": "Spray Pump",
        "type": "relay"
      },
      {
        "id": "fill_pump",
        "name": "Fill Pump",
        "type": "relay"
      }
    ]
  }'
```

**Response:**

```json
{
    "success": true
}
```

**Was Laravel tut:**

-   Speichert komplette Capabilities in `device.capabilities` JSON-Feld
-   Zählt Sensoren + Aktuatoren für Logging

---

### 6. Get Capabilities - Agent liest gespeicherte Capabilities

**GET** `/capabilities`

```bash
curl -X GET https://grow.linn.games/api/growdash/agent/capabilities \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token"
```

**Response:**

```json
{
  "success": true,
  "capabilities": {
    "board": { ... },
    "sensors": [ ... ],
    "actuators": [ ... ]
  }
}
```

---

### 7. Store Device Logs - Agent sendet Logs

**POST** `/logs`

```bash
curl -X POST https://grow.linn.games/api/growdash/agent/logs \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token" \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [
      {
        "level": "info",
        "message": "Agent started successfully",
        "timestamp": "2025-12-05T10:30:00Z"
      },
      {
        "level": "warning",
        "message": "Serial port scanning took 2.5 seconds",
        "timestamp": "2025-12-05T10:30:01Z"
      }
    ]
  }'
```

**Response:**

```json
{
    "success": true,
    "inserted": 2
}
```

---

### 8. Get Serial Ports - Verfügbare Ports scannen

**GET** `/ports`

```bash
curl -X GET https://grow.linn.games/api/growdash/agent/ports \
  -H "X-Device-ID: growdash-test" \
  -H "X-Device-Token: test_token"
```

**Response (Agent online):**

```json
{
    "success": true,
    "ports": [
        {
            "port": "/dev/ttyACM0",
            "description": "Arduino Uno",
            "vendor_id": "2341",
            "product_id": "0043"
        }
    ]
}
```

**Response (Fallback - Agent offline):**

```json
{
    "success": true,
    "ports": [
        {
            "port": "/dev/ttyACM0",
            "description": "Arduino (ACM)",
            "vendor_id": "",
            "product_id": ""
        },
        {
            "port": "/dev/ttyUSB0",
            "description": "Serial Device (USB)",
            "vendor_id": "",
            "product_id": ""
        },
        {
            "port": "COM3",
            "description": "Serial Port",
            "vendor_id": "",
            "product_id": ""
        },
        {
            "port": "COM4",
            "description": "Serial Port",
            "vendor_id": "",
            "product_id": ""
        }
    ]
}
```

**Was passiert:**

-   Wenn `device.ip_address` gesetzt → Proxied zu `http://{ip}:8000/ports`
-   Wenn Timeout oder kein IP → Fallback-Liste (für manuelle Auswahl)

---

## 🚀 Agent Implementation Checklist

-   [x] **Laravel API** - Komplett überarbeitet und deployed
-   [ ] **Agent Code** - Muss implementiert werden in `~/growdash/agent.py`

### Required Agent Methods

```python
class GrowDashAgent:
    def heartbeat_loop(self):
        """Send heartbeat every 30s"""
        # POST /heartbeat with ip_address, api_port

    def telemetry_loop(self):
        """Send telemetry every 10s"""
        # Sammle Serial-Daten

    def command_loop(self):
        """Poll commands every 5s"""
        # GET /commands/pending
        # Führe aus: serial_command, arduino_compile, arduino_upload, scan_ports
        # POST /commands/{id}/result mit status + output + error

    def capabilities_loop(self):
        """Send capabilities on startup"""
        # POST /capabilities mit board, sensors, actuators

    def logs_loop(self):
        """Send logs periodically"""
        # POST /logs
```

---

## 📊 Database Schema

### `devices` Table

```sql
id                  BIGINT PRIMARY KEY
user_id             BIGINT (FK users)
public_id           VARCHAR(255) UNIQUE
name                VARCHAR(255)
agent_token         VARCHAR(255) HASHED
ip_address          VARCHAR(45) NULLABLE
api_port            INT DEFAULT 8000
status              ENUM('online', 'offline')
last_seen_at        TIMESTAMP NULLABLE
capabilities        JSON NULLABLE
board_type          VARCHAR(100) NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### `telemetry` Table

```sql
id                  BIGINT PRIMARY KEY
device_id           BIGINT (FK devices)
type                VARCHAR(50)
value               VARCHAR(255)
timestamp           TIMESTAMP
created_at          TIMESTAMP
```

### `commands` Table

```sql
id                  BIGINT PRIMARY KEY
device_id           BIGINT (FK devices)
type                VARCHAR(50) -- serial_command, arduino_upload, etc.
params              JSON
status              ENUM('pending', 'completed', 'failed')
result_message      TEXT NULLABLE
result_data         JSON NULLABLE -- {error, output}
completed_at        TIMESTAMP NULLABLE
created_at          TIMESTAMP
```

---

## 🔄 Data Flows

### Command Execution: Laravel → Arduino

```
User (Frontend)
    ↓ Clicks "Turn on Pump"
Laravel Backend
    ↓ Command.create(type: 'serial_command', params: {command: 'PumpOn'})
Agent (command_loop every 5s)
    ↓ GET /commands/pending
Agent
    ↓ Executes: serial.write('PumpOn\n')
Arduino (Serial)
    ↓ Executes: Turn on relay
Arduino
    ↓ Responses: "PUMP_ON"
Agent
    ↓ POST /commands/{id}/result (status: completed, output: "PUMP_ON")
Laravel Backend
    ↓ Updates Command status to 'completed'
Frontend
    ↓ Shows: "✅ Pump turned on"
```

### Arduino Code Upload: Frontend → Arduino

```
User (Frontend)
    ↓ Clicks "Upload Code"
    ↓ Selects port /dev/ttyACM0
Laravel Backend
    ↓ Command.create(
        type: 'arduino_upload',
        params: {
          code: 'void setup() {...}',
          board: 'arduino:avr:uno',
          port: '/dev/ttyACM0'
        }
      )
Agent (command_loop)
    ↓ GET /commands/pending
Agent (handle_arduino_upload)
    ↓ Create temp sketch file
    ↓ arduino-cli compile --upload --fqbn {board} --port {port} {sketch}
Agent
    ↓ [Serial port closed during upload]
    ↓ Parse output + errors
Agent
    ↓ POST /commands/{id}/result (
        status: 'completed',
        output: 'Sketch uses 1234 bytes...',
        error: ''
      )
Laravel Backend
    ↓ Stores result_data
    ↓ Updates Command status
Frontend
    ↓ Shows: "✅ Upload successful"
    ↓ [Optional] LLM analyzes errors if failed
User
    ↓ Sees upload result
```

---

## ✅ Status

| Component            | Status   | Notes                          |
| -------------------- | -------- | ------------------------------ |
| Laravel API          | ✅ READY | All 8 endpoints implemented    |
| Routes               | ✅ READY | All routes configured          |
| Middleware           | ✅ READY | device.auth middleware working |
| Migrations           | ✅ READY | api_port column added          |
| Agent Implementation | ⏳ TODO  | Needs Python implementation    |

---

## 🚀 Deployment Steps

### 1. Production (Laravel)

```bash
cd /home/grow/growdash
git pull origin main
php artisan migrate --force
php artisan config:cache
```

### 2. Agent (After Python Implementation)

```bash
cd ~/growdash
python agent.py 2>&1 | tee -a agent.log
```

---

## 📝 Logs

Agent logs to stdout:

```
2025-12-05 10:30:00 - INFO - 🚀 Agent started: growdash-abc123
2025-12-05 10:30:00 - INFO - 📡 Laravel: https://grow.linn.games
2025-12-05 10:30:00 - INFO - 🔌 Serial: /dev/ttyACM0
2025-12-05 10:30:00 - INFO - ✅ Connected to Laravel
2025-12-05 10:30:30 - INFO - 💓 Heartbeat sent
2025-12-05 10:30:35 - INFO - 📦 Telemetry: WaterLevel=45, Temp=22.5
2025-12-05 10:30:40 - INFO - 🔄 Polling commands...
```

**In Production:** Use systemd service with journalctl:

```bash
sudo systemctl start growdash-agent
sudo journalctl -u growdash-agent -f
```

---

## ❓ Troubleshooting

### Agent Not Sending Heartbeat?

-   ✅ Check network connectivity: `ping grow.linn.games`
-   ✅ Check device token in .env
-   ✅ Check agent logs: `tail agent.log`

### Telemetry Not Arriving?

-   ✅ Check serial port connection
-   ✅ Verify Arduino sends data every 10s
-   ✅ Check Laravel logs: `tail -f storage/logs/laravel.log`

### Commands Not Executing?

-   ✅ Device must be `status = 'online'`
-   ✅ Check command_loop is running
-   ✅ Verify agent can execute arduino-cli

### Port Scan Returns Fallback?

-   ✅ `device.ip_address` is NULL → Agent endpoint unreachable
-   ✅ Try manually selecting port from fallback list
-   ✅ Configure IP: `device.ip_address = '192.168.1.x'`

---

**SIMPLE. CLEAN. NO BULLSHIT.**
