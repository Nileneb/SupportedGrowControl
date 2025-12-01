# Device Pairing Flow - Complete Documentation

## 🔗 Problem Statement

**Question:** How does Laravel know which user a device belongs to?

**Answer:** Via **Device Pairing** with a 6-digit code!

---

## 🔄 Complete Pairing Flow

### Visual Diagram

```
┌─────────────┐                              ┌──────────────┐
│   Agent     │                              │   Laravel    │
│ (Raspberry) │                              │  (Backend)   │
└──────┬──────┘                              └──────┬───────┘
       │                                            │
       │  1. POST /api/agents/bootstrap             │
       │     { bootstrap_id: "esp32-abc123" }       │
       ├───────────────────────────────────────────>│
       │                                            │
       │                                            │  2. Create Device
       │                                            │     - Generate 6-digit code
       │                                            │     - Store bootstrap_id
       │                                            │
       │  3. Response: { status: "unpaired",        │
       │                bootstrap_code: "XY42Z7" }  │
       │<───────────────────────────────────────────┤
       │                                            │
       │  4. Display Code to User                   │
       │     ╔════════════════════╗                 │
       │     ║  Code: XY42Z7     ║                 │
       │     ╚════════════════════╝                 │
       │                                            │
       │  5. Start Polling:                         │
       │     GET /api/agents/pairing/status         │
       │     ?bootstrap_id=...&bootstrap_code=...   │
       ├───────────────────────────────────────────>│
       │                                            │
       │  Response: { status: "pending" }           │
       │<───────────────────────────────────────────┤
       │                                            │
       │  (Repeat every 5s for max 5 minutes)       │

┌──────┴──────┐
│    User     │
│  (Browser)  │
└──────┬──────┘
       │
       │  6. Login at grow.linn.games
       │
       │  7. Navigate to /devices/pair
       │
       │  8. Enter Code: XY42Z7
       │
       │  9. POST /api/devices/pair
       │     { bootstrap_code: "XY42Z7" }
       ├───────────────────────────────────────────>│
       │                                            │
       │                                            │  10. Pair Device
       │                                            │      - Link to user_id
       │                                            │      - Generate UUID
       │                                            │      - Create token hash
       │                                            │
       │  11. Response: { success: true,            │
       │                  device: {...} }           │
       │<───────────────────────────────────────────┤

┌──────┴──────┐
│   Agent     │
└──────┬──────┘
       │
       │  12. Next Poll (within 5s):
       │      GET /api/agents/pairing/status
       ├───────────────────────────────────────────>│
       │                                            │
       │  13. Response: { status: "paired",         │
       │                  public_id: "uuid",        │
       │                  agent_token: "xxx" }      │
       │<───────────────────────────────────────────┤
       │                                            │
       │  14. Save to .env:                         │
       │      DEVICE_PUBLIC_ID=uuid                 │
       │      DEVICE_TOKEN=xxx                      │
       │                                            │
       │  15. Start Agent Main Loop                 │
       │      POST /api/growdash/agent/telemetry    │
       │      Headers: X-Device-ID, X-Device-Token  │
       ├───────────────────────────────────────────>│
       │                                            │
       │                                            │  16. Verify Token
       │                                            │      hash_equals()
       │                                            │
       │  Response: { success: true }               │
       │<───────────────────────────────────────────┤
       └────────────────────────────────────────────┘
```

---

## 📝 Step-by-Step Implementation

### Agent Side (Python - `pairing.py`)

#### 1. Bootstrap Request

```python
response = requests.post(
    f"{LARAVEL_BASE_URL}/api/agents/bootstrap",
    json={
        "bootstrap_id": "esp32-abc123def456",  # Unique hardware ID
        "name": "GrowBox Kitchen",             # Optional display name
    }
)
```

**Response (unpaired):**

```json
{
    "status": "unpaired",
    "bootstrap_code": "XY42Z7",
    "message": "Device registered. Please pair via web UI with code: XY42Z7"
}
```

#### 2. Display Code to User

```python
print(f"""
╔════════════════════════════════════════════════════════╗
║                                                        ║
║    Dein Pairing-Code:  {bootstrap_code}               ║
║                                                        ║
╚════════════════════════════════════════════════════════╝

📱 Gehe zu: {LARAVEL_BASE_URL}/devices/pair
🔢 Gib den Code ein: {bootstrap_code}
🆔 Device-ID: {bootstrap_id}

⏳ Warte auf Pairing-Bestätigung... (300s verbleibend)
""")
```

#### 3. Poll for Pairing Status

```python
while time.time() < timeout:
    response = requests.get(
        f"{LARAVEL_BASE_URL}/api/agents/pairing/status",
        params={
            "bootstrap_id": bootstrap_id,
            "bootstrap_code": bootstrap_code,
        }
    )

    data = response.json()

    if data["status"] == "paired":
        # Success! Save credentials
        save_to_env(
            DEVICE_PUBLIC_ID=data["public_id"],
            DEVICE_TOKEN=data["agent_token"]
        )
        break

    time.sleep(5)  # Poll every 5 seconds
```

---

### User Side (Laravel Web UI)

#### 6-9. Pairing Form

**Route:** `GET /devices/pair` (requires authentication)

**UI Component:** `resources/views/livewire/devices/pair.blade.php`

User enters 6-digit code and submits:

```php
POST /api/devices/pair
Content-Type: application/json
Cookie: laravel_session=...

{
    "bootstrap_code": "XY42Z7"
}
```

**Laravel Response:**

```json
{
    "success": true,
    "message": "Device paired successfully!",
    "device": {
        "id": 1,
        "name": "GrowBox Kitchen",
        "public_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
        "paired_at": "2025-12-01T16:42:00Z"
    },
    "agent_token": "7f3d9a8b...64-char-plaintext-token...c2e1f4a6"
}
```

---

### Agent Side (Continued)

#### 13-15. Receive Credentials and Start Agent

After successful polling, agent saves credentials:

```python
# .env
DEVICE_PUBLIC_ID=9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
DEVICE_TOKEN=7f3d9a8b...64-char-plaintext-token...c2e1f4a6
```

Then starts main loop with authenticated requests:

```python
headers = {
    "X-Device-ID": public_id,
    "X-Device-Token": agent_token,
}

# Send telemetry
requests.post(
    f"{LARAVEL_BASE_URL}/api/growdash/agent/telemetry",
    headers=headers,
    json={"readings": [...]}
)

# Poll for commands
requests.get(
    f"{LARAVEL_BASE_URL}/api/growdash/agent/commands/pending",
    headers=headers
)
```

---

## 🔒 Security Features

### 1. Token Hashing

**Agent stores plaintext:**

```env
DEVICE_TOKEN=7f3d9a8b...64-char-token...c2e1f4a6
```

**Laravel stores SHA256 hash:**

```php
$device->agent_token = hash('sha256', $plaintextToken);  // One-way hash
```

**Verification:**

```php
if (hash_equals($device->agent_token, hash('sha256', $request->header('X-Device-Token')))) {
    // Authenticated!
}
```

### 2. Code Expiration

Pairing codes expire after **5 minutes** (300 seconds).

```php
// In Device model
public function isBootstrapCodeValid(): bool
{
    if (!$this->bootstrap_code) return false;
    if ($this->isPaired()) return false;

    // Codes expire 5 minutes after device creation
    return $this->created_at->addMinutes(5)->isFuture();
}
```

### 3. One-Time Token Delivery

The plaintext `agent_token` is **only returned once**:

-   During pairing (POST /api/devices/pair)
-   During status polling (GET /api/agents/pairing/status) when status changes to "paired"

After that, only the hash exists in the database.

### 4. Rate Limiting

Pairing endpoints have rate limiting:

```php
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/agents/bootstrap', ...);
    Route::get('/agents/pairing/status', ...);
});
```

---

## 📊 Database Schema

### `devices` Table

```sql
CREATE TABLE devices (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NULLABLE,  -- NULL until paired
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,

    -- Bootstrap/Pairing
    bootstrap_id VARCHAR(64) UNIQUE,  -- Hardware ID (esp32-abc123)
    bootstrap_code VARCHAR(6),        -- 6-digit pairing code (XY42Z7)
    paired_at TIMESTAMP NULLABLE,     -- When user paired

    -- Authentication
    public_id UUID UNIQUE,            -- Device UUID for API
    agent_token VARCHAR(64),          -- SHA256 hash of token

    -- Device info
    board_type VARCHAR(50),
    status VARCHAR(20),
    capabilities JSON,
    last_state JSON,
    last_seen_at TIMESTAMP,

    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Pairing Lifecycle

| Stage        | user_id | bootstrap_code | public_id | agent_token | paired_at    |
| ------------ | ------- | -------------- | --------- | ----------- | ------------ |
| 1. Bootstrap | NULL    | "XY42Z7"       | NULL      | NULL        | NULL         |
| 2. Paired    | 1       | NULL           | "uuid..." | "hash..."   | "2025-12-01" |
| 3. Active    | 1       | NULL           | "uuid..." | "hash..."   | "2025-12-01" |

---

## 🧪 Testing the Flow

### 1. Agent Side (Python)

```bash
cd ~/growdash
source .venv/bin/activate
python pairing.py
```

**Expected Output:**

```
╔════════════════════════════════════════════════════════╗
║                                                        ║
║    Dein Pairing-Code:  XY42Z7                         ║
║                                                        ║
╚════════════════════════════════════════════════════════╝

📱 Gehe zu: https://grow.linn.games/devices/pair
🔢 Gib den Code ein: XY42Z7
🆔 Device-ID: esp32-abc123

⏳ Warte auf Pairing-Bestätigung... (300s verbleibend)
```

### 2. User Side (Browser)

1. Open: `https://grow.linn.games/devices/pair`
2. Login as user
3. Enter code: `XY42Z7`
4. Click "Pair Device"
5. Success message: "Device paired successfully!"

### 3. Agent Side (Continued)

```
✅ Pairing erfolgreich!
   Verknüpft mit User: user@example.com
💾 Speichere Credentials in .env...
✅ Credentials gespeichert

.env wurde aktualisiert:
  DEVICE_PUBLIC_ID=9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
  DEVICE_TOKEN=7f3d9a8b...
```

### 4. Start Agent

```bash
./grow_start.sh
```

**Expected Output:**

```
2025-12-01 22:00:00 - INFO - Agent gestartet für Device: 9b1deb4d-...
2025-12-01 22:00:00 - INFO - Laravel Backend: https://grow.linn.games/api/growdash/agent
2025-12-01 22:00:00 - INFO - Führe Startup-Health-Check durch...
2025-12-01 22:00:01 - INFO - ✅ Laravel-Backend erreichbar und Auth erfolgreich
2025-12-01 22:00:01 - INFO - Agent läuft... (Strg+C zum Beenden)
```

---

## 🎯 Advantages

✅ **Simple** - User only enters 6 digits  
✅ **Secure** - Token hash in DB, codes expire after 5 min  
✅ **Multi-User** - Each user can pair multiple devices  
✅ **User Assignment** - All data linked to user_id  
✅ **Offline Capable** - Token persists in .env  
✅ **Revocable** - User can unpair device in web UI

---

## 🔄 Re-Pairing

If token is lost or device is reset:

```bash
# Agent side
python pairing.py  # Generate new code
```

**Options:**

1. **Keep existing device:** Same bootstrap_id → updates existing device
2. **New device:** New bootstrap_id → creates new device entry

---

## 📚 Related Files

### Laravel Backend

-   `app/Http/Controllers/BootstrapController.php` - Bootstrap + status polling
-   `app/Http/Controllers/DevicePairingController.php` - User pairing
-   `app/Models/Device.php` - Device model with pairing methods
-   `routes/api.php` - API routes
-   `routes/web.php` - Web UI routes
-   `resources/views/livewire/devices/pair.blade.php` - Pairing UI

### Python Agent

-   `pairing.py` - Pairing script
-   `agent.py` - Main agent (uses saved credentials)
-   `.env.example` - Configuration template

### Documentation

-   `README.md` - Project overview with ER diagram
-   `PAIRING_FLOW.md` - This file
-   `QUICKSTART.md` - Setup guide

---

**Status:** ✅ Fully Implemented  
**Last Updated:** 2025-12-01
