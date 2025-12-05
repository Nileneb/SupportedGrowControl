# Agent Communication - Testing & Debugging

## ✅ Agent muss JETZT diese 3 Dinge machen

### 1️⃣ Heartbeat (Alle 30 Sekunden)

```bash
curl -X POST "https://grow.linn.games/api/growdash/agent/heartbeat" \
  -H "X-Device-ID: growdash-XXXX" \
  -H "X-Device-Token: your_device_token" \
  -H "Content-Type: application/json" \
  -d '{
    "ip_address": "192.168.1.100",
    "api_port": 8000
  }'
```

**Antwort:**
```json
{
  "success": true
}
```

**Was passiert:**
- `last_seen_at` wird aktualisiert
- `status` wird auf `online` gesetzt
- IP-Adresse wird aktualisiert

---

### 2️⃣ Telemetry (Alle 10 Sekunden)

```bash
curl -X POST "https://grow.linn.games/api/growdash/agent/telemetry" \
  -H "X-Device-ID: growdash-XXXX" \
  -H "X-Device-Token: your_device_token" \
  -H "Content-Type: application/json" \
  -d '{
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
  }'
```

**Antwort:**
```json
{
  "success": true,
  "inserted": 2
}
```

**Mapping:**
- Agent sendet: `type` → Datenbank speichert: `sensor_key`
- Agent sendet: `timestamp` (ISO8601) → Datenbank speichert: `measured_at`

---

### 3️⃣ Commands Polling (Alle 5 Sekunden)

```bash
curl -X GET "https://grow.linn.games/api/growdash/agent/commands/pending" \
  -H "X-Device-ID: growdash-XXXX" \
  -H "X-Device-Token: your_device_token"
```

**Antwort:**
```json
{
  "success": true,
  "commands": [
    {
      "id": 123,
      "type": "serial_command",
      "params": {
        "command": "Status"
      }
    },
    {
      "id": 124,
      "type": "arduino_compile",
      "params": {
        "code": "void setup() {...}",
        "board": "arduino:avr:uno"
      }
    }
  ]
}
```

---

### 4️⃣ Command Result (Nach Ausführung)

```bash
curl -X POST "https://grow.linn.games/api/growdash/agent/commands/123/result" \
  -H "X-Device-ID: growdash-XXXX" \
  -H "X-Device-Token: your_device_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed",
    "result_message": "✅ Arduino respond with: OK",
    "output": "Status: 45% Water Level",
    "error": ""
  }'
```

**Für Fehler:**
```json
{
  "status": "failed",
  "result_message": "❌ Serial timeout",
  "output": "Attempted to send: Status\\n",
  "error": "No response from Arduino after 500ms"
}
```

**Antwort:**
```json
{
  "success": true
}
```

---

## 🔧 Debugging Checklist

### Device Authentication Fehler (401)
```
{
  "error": "Missing device credentials",
  "message": "X-Device-ID and X-Device-Token headers are required"
}
```

**Fix:** Stelle sicher, dass Headers gesetzt sind:
- `X-Device-ID: growdash-XXXX`
- `X-Device-Token: token_here`

---

### Device nicht gefunden (404)
```
{
  "error": "Device not found",
  "message": "Invalid device ID or device not paired"
}
```

**Fix:** 
- Device muss in der Datenbank existieren
- Muss `user_id` haben
- Muss `paired_at` haben

---

### Token verification failed (403)
```
{
  "error": "Invalid credentials",
  "message": "Device token verification failed"
}
```

**Fix:** Token ist falsch oder wurde geändert

---

## 📊 Database Tables

### `devices` Table
- `id` - Device ID
- `public_id` - Public UUID (in X-Device-ID Header)
- `agent_token` - SHA256 Hash von plaintext token
- `ip_address` - Zuletzt bericht IP
- `api_port` - Zuletzt bericht API Port
- `last_seen_at` - Letzter Heartbeat
- `status` - 'paired', 'online', 'offline', 'error'
- `capabilities` - JSON mit board/sensors/actuators

### `telemetry_readings` Table
- `device_id` - FK zu devices
- `sensor_key` - Typ (z.B. 'WaterLevel')
- `value` - Wert als String
- `unit` - Einheit (optional)
- `measured_at` - Zeitstempel
- `created_at` - Wann in DB gespeichert

### `commands` Table
- `device_id` - FK zu devices
- `type` - 'serial_command', 'arduino_compile', etc.
- `params` - JSON mit Command-Parametern
- `status` - 'pending', 'executing', 'completed', 'failed'
- `result_message` - Kurze Zusammenfassung
- `output` - Stdout vom Command
- `error` - Stderr vom Command
- `completed_at` - Wann fertig

### `device_logs` Table
- `device_id` - FK zu devices
- `level` - 'debug', 'info', 'warning', 'error'
- `message` - Log-Text
- `context` - JSON mit extras (z.B. timestamp)
- `created_at` - Wann in DB gespeichert

---

## 🚀 Production Deployment

1. **Laravel deployen:**
   ```bash
   cd /home/growdash/grow.linn.games
   git pull origin main
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   ```

2. **Agent im Raspberry Pi:**
   - Agent kennt: `DEVICE_PUBLIC_ID`, `DEVICE_TOKEN`
   - Agent sendet alle Requests zu: `https://grow.linn.games/api/growdash/agent/*`
   - Agent muss Timestamps in ISO8601 Format senden: `2025-12-05T10:30:00Z`

3. **Verifizieren:**
   - Logs überprüfen: `tail -f /var/log/growdash/agent.log`
   - Database überprüfen: Agent sollte in `last_seen_at` aktualisiert sein

---

## ✨ Quick Status Check (Laravel)

```bash
# In Laravel Tinker:
Device::where('public_id', 'growdash-abc123')->first()->toArray();
```

Sollte zeigen:
- `last_seen_at` ist aktuell
- `status` ist 'online'
- `ip_address` ist gesetzt
- `api_port` ist 8000

---

**Status:** Agent API ist vollständig. Agent-Implementation wird als nächstes durchgeführt.
