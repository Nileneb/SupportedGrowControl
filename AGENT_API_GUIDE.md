# GrowDash Laravel API - Agent Integration Guide

## Base URL

```
Production: https://grow.linn.games
Development: http://localhost (Herd/Valet)
```

## Agent Authentication

All agent endpoints require these headers:

```http
X-Device-ID: <device_public_id>
X-Device-Token: <plaintext_agent_token>
```

## Onboarding Flows

### Flow 1: Bootstrap + Pairing (Recommended)

#### Step 1: Bootstrap

```http
POST /api/agents/bootstrap
Content-Type: application/json

{
  "bootstrap_id": "raspi-001-serial-abc123",
  "name": "Kitchen Growbox",
  "board_type": "arduino_uno",
  "capabilities": {
    "sensors": ["water_level", "tds", "temperature"],
    "actuators": ["spray_pump", "fill_valve"]
  }
}
```

**Response (201):**

```json
{
    "status": "unpaired",
    "bootstrap_code": "XY42Z7",
    "message": "Device registered. Please pair via web UI with code: XY42Z7",
    "device_id": "raspi-001-serial-abc123"
}
```

#### Step 2: Poll Pairing Status

```http
GET /api/agents/pairing/status?bootstrap_id=raspi-001-serial-abc123&bootstrap_code=XY42Z7
```

**Response (Unpaired):**

```json
{
    "status": "unpaired",
    "message": "Waiting for user to pair device"
}
```

**Response (Paired):**

```json
{
    "status": "paired",
    "public_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "agent_token": "7f3d9a8b...64-char-plaintext-token...c2e1f4a6",
    "device_name": "Kitchen Growbox",
    "user_email": "user@example.com"
}
```

⚠️ **CRITICAL**: Save `public_id` and `agent_token` to `.env` immediately!

---

### Flow 2: Direct Login (Advanced)

#### Step 1: User Login

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "secret"
}
```

**Response (200):**

```json
{
    "access_token": "1|abc123xyz...",
    "token_type": "Bearer",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com"
    }
}
```

#### Step 2: Register Device

```http
POST /api/growdash/devices/register
Authorization: Bearer 1|abc123xyz...
Content-Type: application/json

{
  "bootstrap_id": "raspi-002-serial-def456",
  "name": "Greenhouse Monitor",
  "board_type": "esp32",
  "capabilities": {
    "board_name": "esp32",
    "sensors": ["water_level", "ph", "ec"],
    "actuators": ["pump_a", "pump_b"]
  },
  "revoke_user_token": true
}
```

**Response (201):**

```json
{
    "success": true,
    "device": {
        "id": 7,
        "name": "Greenhouse Monitor",
        "public_id": "f8e3c1a2-5b7d-4c9e-8f3a-1b2d7e9c4a6f",
        "bootstrap_id": "raspi-002-serial-def456",
        "paired_at": "2025-12-02T03:00:00Z",
        "reused": false
    },
    "agent_token": "9a2f8d3c...64-char-plaintext-token...e1c4b7a9"
}
```

⚠️ **IMPORTANT**: User token is revoked automatically if `revoke_user_token=true`

---

## Agent API Endpoints

### Update Capabilities

```http
POST /api/growdash/agent/capabilities
X-Device-ID: <public_id>
X-Device-Token: <agent_token>
Content-Type: application/json

{
  "capabilities": {
    "board_name": "arduino_uno",
    "sensors": ["water_level", "tds", "temperature", "ph"],
    "actuators": ["spray_pump", "fill_valve", "led_grow"]
  }
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Device capabilities updated",
    "board_type": "arduino_uno",
    "capabilities": {
        "board_name": "arduino_uno",
        "sensors": ["water_level", "tds", "temperature", "ph"],
        "actuators": ["spray_pump", "fill_valve", "led_grow"]
    }
}
```

---

### Send Telemetry

```http
POST /api/growdash/agent/telemetry
X-Device-ID: <public_id>
X-Device-Token: <agent_token>
Content-Type: application/json

{
  "readings": [
    {
      "sensor_key": "water_level",
      "value": 75.5,
      "unit": "%",
      "measured_at": "2025-12-02T03:00:00Z"
    },
    {
      "sensor_key": "tds",
      "value": 850,
      "unit": "ppm",
      "measured_at": "2025-12-02T03:00:00Z"
    },
    {
      "sensor_key": "temperature",
      "value": 22.3,
      "unit": "°C",
      "measured_at": "2025-12-02T03:00:00Z"
    }
  ]
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Telemetry data stored successfully",
    "inserted_count": 3,
    "ids": [101, 102, 103]
}
```

---

### Get Pending Commands

```http
GET /api/growdash/agent/commands/pending
X-Device-ID: <public_id>
X-Device-Token: <agent_token>
```

**Response (200):**

```json
{
    "success": true,
    "commands": [
        {
            "id": 42,
            "type": "spray_on",
            "params": {
                "duration": 10
            },
            "created_at": "2025-12-02T03:00:00Z"
        },
        {
            "id": 43,
            "type": "serial_command",
            "params": {
                "command": "PUMP_A:ON"
            },
            "created_at": "2025-12-02T03:01:00Z"
        }
    ]
}
```

---

### Submit Command Result

```http
POST /api/growdash/agent/commands/42/result
X-Device-ID: <public_id>
X-Device-Token: <agent_token>
Content-Type: application/json

{
  "status": "completed",
  "result_message": "Spray cycle completed successfully (10 seconds)"
}
```

**Valid statuses**: `executing`, `completed`, `failed`

**Response (200):**

```json
{
    "success": true,
    "message": "Command status updated",
    "command": {
        "id": 42,
        "status": "completed",
        "completed_at": "2025-12-02T03:00:15Z"
    }
}
```

---

### Send Heartbeat

```http
POST /api/growdash/agent/heartbeat
X-Device-ID: <public_id>
X-Device-Token: <agent_token>
Content-Type: application/json

{
  "last_state": {
    "uptime": 3600,
    "memory_free": 45000,
    "wifi_rssi": -65
  }
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Heartbeat received",
    "last_seen_at": "2025-12-02T03:00:30Z"
}
```

---

### Send Logs

```http
POST /api/growdash/agent/logs
X-Device-ID: <public_id>
X-Device-Token: <agent_token>
Content-Type: application/json

{
  "logs": [
    {
      "level": "info",
      "message": "Serial connection established",
      "context": {
        "port": "/dev/ttyACM0",
        "baud": 9600
      }
    },
    {
      "level": "error",
      "message": "Failed to read sensor: tds",
      "context": {
        "error": "Timeout after 5 seconds"
      }
    }
  ]
}
```

**Valid levels**: `debug`, `info`, `warning`, `error`

**Response (201):**

```json
{
    "success": true,
    "message": "Logs stored successfully",
    "count": 2
}
```

---

## Error Responses

### 401 Unauthorized (Missing Headers)

```json
{
    "error": "Missing device credentials",
    "message": "X-Device-ID and X-Device-Token headers are required"
}
```

### 403 Forbidden (Invalid Token)

```json
{
    "error": "Invalid credentials",
    "message": "Device token verification failed"
}
```

### 404 Not Found (Device Not Paired)

```json
{
    "error": "Device not found",
    "message": "Invalid device ID or device not paired"
}
```

### 422 Validation Error

```json
{
    "success": false,
    "errors": {
        "readings.0.sensor_key": ["The sensor_key field is required."],
        "readings.1.value": ["The value must be a number."]
    }
}
```

---

## Board Types (Supported)

| Board Name   | FQBN                    | Vendor    |
| ------------ | ----------------------- | --------- |
| arduino_uno  | arduino:avr:uno         | Arduino   |
| arduino_mega | arduino:avr:mega        | Arduino   |
| arduino_nano | arduino:avr:nano        | Arduino   |
| esp32        | esp32:esp32:esp32       | Espressif |
| esp8266      | esp8266:esp8266:generic | Espressif |

Use `board_name` in capabilities payload.

---

## Agent Loop Pseudo-Code

```python
# 1. Onboarding
if not DEVICE_PUBLIC_ID or not DEVICE_TOKEN:
    if ONBOARDING_MODE == "PAIRING":
        bootstrap_response = post("/api/agents/bootstrap", {...})
        show_code(bootstrap_response["bootstrap_code"])
        while True:
            status = get(f"/api/agents/pairing/status?bootstrap_id=...&bootstrap_code=...")
            if status["status"] == "paired":
                save_to_env(status["public_id"], status["agent_token"])
                break
            sleep(5)
    elif ONBOARDING_MODE == "DIRECT_LOGIN":
        email, password = prompt_user()
        login_response = post("/api/auth/login", {"email": email, "password": password})
        token = login_response["access_token"]
        register_response = post("/api/growdash/devices/register", {
            "Authorization": f"Bearer {token}",
            ...
        })
        save_to_env(register_response["device"]["public_id"], register_response["agent_token"])

# 2. Main Loop
while True:
    # Send heartbeat (every 30-60s)
    post("/api/growdash/agent/heartbeat", headers={...}, json={...})

    # Send telemetry (every 10-30s)
    readings = collect_telemetry()
    post("/api/growdash/agent/telemetry", headers={...}, json={"readings": readings})

    # Poll commands (every 5-10s)
    commands = get("/api/growdash/agent/commands/pending", headers={...}).json()["commands"]
    for cmd in commands:
        result = execute_command(cmd)
        post(f"/api/growdash/agent/commands/{cmd['id']}/result", headers={...}, json=result)

    # Send logs (batch, every 60s)
    logs = collect_logs()
    post("/api/growdash/agent/logs", headers={...}, json={"logs": logs})

    sleep(10)
```

---

## Testing

```bash
# Test Bootstrap
curl -X POST https://grow.linn.games/api/agents/bootstrap \
  -H "Content-Type: application/json" \
  -d '{"bootstrap_id":"test-001","name":"Test Device","board_type":"arduino_uno","capabilities":{"sensors":["water_level"]}}'

# Test Heartbeat
curl -X POST https://grow.linn.games/api/growdash/agent/heartbeat \
  -H "X-Device-ID: your-public-id" \
  -H "X-Device-Token: your-agent-token"

# Test Telemetry
curl -X POST https://grow.linn.games/api/growdash/agent/telemetry \
  -H "X-Device-ID: your-public-id" \
  -H "X-Device-Token: your-agent-token" \
  -H "Content-Type: application/json" \
  -d '{"readings":[{"sensor_key":"water_level","value":75.5,"unit":"%","measured_at":"2025-12-02T03:00:00Z"}]}'
```

---

**Status**: ✅ Production Ready  
**Version**: 1.0.0  
**Last Updated**: 2025-12-02
