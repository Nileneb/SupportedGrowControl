# GrowDash Agent API Documentation

## Device Heartbeat

The agent MUST send regular heartbeats to keep the device status as "online" in the dashboard.

### Endpoint
```
POST /api/growdash/agent/heartbeat
```

### Authentication
Device authentication via headers (obtained from registration):
```
X-Device-ID: <device_id from registration response>
X-Device-Token: <agent_token from registration response>
```

### Request Body (Optional)
```json
{
  "last_state": {
    "uptime": 3600,
    "memory": 45000,
    "wifi_rssi": -65,
    "python_version": "3.12.0"
  }
}
```

### Response
```json
{
  "success": true,
  "message": "Heartbeat received",
  "last_seen_at": "2025-12-01T23:45:12.000000Z"
}
```

### Recommended Implementation

**Interval**: Send heartbeat every 30-60 seconds

**Python Example**:
```python
import threading
import time
import requests

class HeartbeatThread(threading.Thread):
    def __init__(self, base_url, device_id, agent_token, interval=30):
        super().__init__(daemon=True)
        self.base_url = base_url
        self.device_id = device_id
        self.agent_token = agent_token
        self.interval = interval
        self.running = True
    
    def run(self):
        while self.running:
            try:
                response = requests.post(
                    f"{self.base_url}/api/growdash/agent/heartbeat",
                    headers={
                        "X-Device-ID": self.device_id,
                        "X-Device-Token": self.agent_token,
                    },
                    json={
                        "last_state": {
                            "uptime": int(time.time() - start_time),
                        }
                    },
                    timeout=5
                )
                if response.status_code == 200:
                    print(f"✅ Heartbeat sent")
                else:
                    print(f"⚠️ Heartbeat failed: {response.status_code}")
            except Exception as e:
                print(f"❌ Heartbeat error: {e}")
            
            time.sleep(self.interval)
    
    def stop(self):
        self.running = False

# Usage in main agent:
heartbeat = HeartbeatThread(
    base_url="https://grow.linn.games",
    device_id=os.getenv("DEVICE_ID"),
    agent_token=os.getenv("AGENT_TOKEN"),
    interval=30
)
heartbeat.start()
```

## Device Status Logic

- **paired**: Device registered but no recent heartbeat
- **online**: Heartbeat received within last 2 minutes
- **offline**: No heartbeat for > 2 minutes (auto-detected by backend cron job)
- **error**: Device reported an error state

## Other Agent Endpoints

### Poll Pending Commands
**The agent MUST regularly poll for pending commands to execute.**

```
GET /api/growdash/agent/commands/pending
Headers: X-Device-ID, X-Device-Token
```

**Response:**
```json
{
  "success": true,
  "commands": [
    {
      "id": 42,
      "type": "serial_command",
      "params": {
        "command": "STATUS"
      },
      "created_at": "2025-12-02T10:30:00Z"
    }
  ]
}
```

**Recommended Implementation:**
- Poll every 5-10 seconds when online
- Process commands in order (FIFO)
- Send result after execution

**Python Example:**
```python
def poll_commands(base_url, device_id, agent_token):
    response = requests.get(
        f"{base_url}/api/growdash/agent/commands/pending",
        headers={
            "X-Device-ID": device_id,
            "X-Device-Token": agent_token,
        },
        timeout=5
    )
    
    if response.status_code == 200:
        data = response.json()
        for cmd in data.get('commands', []):
            execute_command(cmd)
```

### Submit Command Result
```
POST /api/growdash/agent/commands/{id}/result
Headers: X-Device-ID, X-Device-Token
Body: {
  "status": "completed",
  "result_message": "Command executed successfully"
}
```

**Status values:**
- `executing`: Command is being processed
- `completed`: Command finished successfully
- `failed`: Command execution failed

**Python Example:**
```python
def submit_result(base_url, device_id, agent_token, command_id, status, message):
    response = requests.post(
        f"{base_url}/api/growdash/agent/commands/{command_id}/result",
        headers={
            "X-Device-ID": device_id,
            "X-Device-Token": agent_token,
        },
        json={
            "status": status,
            "result_message": message
        },
        timeout=5
    )
    return response.json()
```

### Send Telemetry Data
```
POST /api/growdash/agent/telemetry
Headers: X-Device-ID, X-Device-Token
Body: { "sensor_type": "temperature", "value": 23.5, "unit": "celsius" }
```

### Get Pending Commands
```
GET /api/growdash/agent/commands/pending
Headers: X-Device-ID, X-Device-Token
```

### Update Device Capabilities
```
POST /api/growdash/agent/capabilities
Headers: X-Device-ID, X-Device-Token
Body: {
  "capabilities": {
    "sensors": ["water_level", "tds", "temperature"],
    "actuators": ["spray_pump", "fill_valve"]
  }
}
```

### Send Device Logs
```
POST /api/growdash/agent/logs
Headers: X-Device-ID, X-Device-Token
Body: { "level": "info", "message": "Device started", "timestamp": "2025-12-01T23:00:00Z" }
```
