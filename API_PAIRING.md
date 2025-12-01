# Device Pairing - API Reference

Complete API documentation for the GrowDash device pairing flow with 6-digit codes.

---

## 🔗 Public Endpoints (No Auth Required)

### POST `/api/agents/bootstrap`

Agent calls this endpoint on first startup to register the device.

**Request:**
```json
{
    "bootstrap_id": "esp32-abc123def456",
    "name": "GrowBox Kitchen"
}
```

**Response (device not yet paired):**
```json
{
    "status": "unpaired",
    "bootstrap_code": "XY42Z7",
    "message": "Device registered. Please pair via web UI with code: XY42Z7"
}
```

**Response (device already paired):**
```json
{
    "status": "paired",
    "public_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "agent_token": "7f3d9a8b...64-char-token...c2e1f4a6",
    "device_name": "GrowBox Kitchen",
    "user_email": "admin@growdash.local"
}
```

⚠️ **Important:** The `agent_token` is regenerated on every bootstrap call if device is already paired. Agent must save it immediately.

---

### GET `/api/agents/pairing/status`

Agent polls this endpoint to check if user has completed pairing.

**Query Parameters:**
- `bootstrap_id` (required): Hardware ID sent during bootstrap
- `bootstrap_code` (required): 6-digit code from bootstrap response

**Request Example:**
```
GET /api/agents/pairing/status?bootstrap_id=esp32-abc123&bootstrap_code=XY42Z7
```

**Response (pending - not yet paired):**
```json
{
    "status": "pending"
}
```

**Response (paired - user completed pairing):**
```json
{
    "status": "paired",
    "public_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "agent_token": "7f3d9a8b...64-char-token...c2e1f4a6",
    "device_name": "GrowBox Kitchen",
    "user_email": "admin@growdash.local"
}
```

**Response (error - invalid code):**
```json
{
    "status": "error",
    "message": "Invalid bootstrap_id or bootstrap_code"
}
```

**Polling Strategy:**
- Poll every **5 seconds**
- Timeout after **5 minutes** (300 seconds)
- Stop polling once `status: "paired"` received

---

## 🔒 User-Authenticated Endpoints

### POST `/api/devices/pair`

Web UI calls this endpoint when user enters the 6-digit code.

**Authentication:** Requires `auth:web` (Laravel session cookie)

**Request:**
```json
{
    "bootstrap_code": "XY42Z7"
}
```

**Response (success):**
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
    "agent_token": "7f3d9a8b...64-char-token...c2e1f4a6"
}
```

**Response (error - invalid code):**
```json
{
    "success": false,
    "message": "Invalid bootstrap code or device already paired."
}
```

**Validation:**
- `bootstrap_code`: required, string, exactly 6 characters
- Code must exist and not be expired (< 5 minutes old)
- Device must not already be paired

---

### GET `/api/devices/unclaimed`

List all devices waiting for pairing (admin/debug only).

**Authentication:** Requires `auth:web`

**Response:**
```json
{
    "devices": [
        {
            "id": 2,
            "name": "Unclaimed Device",
            "bootstrap_id": "esp32-xyz789",
            "bootstrap_code": "AB12CD",
            "created_at": "2025-12-01T16:30:00Z"
        }
    ]
}
```

---

## 🌐 Web UI Route

### GET `/devices/pair`

User-facing pairing page built with Livewire.

**Authentication:** Requires `auth:web`

**Features:**
- Input field for 6-digit code (auto-uppercase, max 6 chars)
- Real-time validation
- Success/error messages
- Instructions for pairing process
- Auto-redirect to dashboard on success

**File:** `resources/views/livewire/devices/pair.blade.php`

---

## 🔐 Security Features

### 1. Code Expiration
- Pairing codes expire **5 minutes** after device registration
- Expired codes return 404 error

### 2. Token Hashing
- Agent receives **plaintext token** (only once)
- Laravel stores **SHA256 hash** in database
- Verification: `hash_equals($storedHash, hash('sha256', $receivedToken))`

### 3. One-Time Token Delivery
Plaintext `agent_token` is returned **only** in these scenarios:
1. Initial pairing (POST `/api/devices/pair`)
2. Status polling when pairing completes (GET `/api/agents/pairing/status`)
3. Re-bootstrap (POST `/api/agents/bootstrap` for already-paired device)

After receiving the token, agent must save it to `.env` immediately.

### 4. Rate Limiting
All pairing endpoints use throttling:
- Bootstrap: 10 requests per minute
- Status polling: 20 requests per minute
- User pairing: 5 requests per minute

---

## 📋 Agent Implementation Example

```python
import requests
import time
from pathlib import Path

LARAVEL_BASE_URL = "https://grow.linn.games"
BOOTSTRAP_ID = "esp32-abc123"

# 1. Bootstrap
response = requests.post(
    f"{LARAVEL_BASE_URL}/api/agents/bootstrap",
    json={"bootstrap_id": BOOTSTRAP_ID, "name": "My Device"}
)
data = response.json()

if data["status"] == "paired":
    # Already paired, save token
    save_to_env(data["public_id"], data["agent_token"])
else:
    # Not paired yet
    code = data["bootstrap_code"]
    print(f"Pairing Code: {code}")
    print(f"Go to: {LARAVEL_BASE_URL}/devices/pair")
    
    # 2. Poll for pairing
    timeout = time.time() + 300  # 5 minutes
    while time.time() < timeout:
        response = requests.get(
            f"{LARAVEL_BASE_URL}/api/agents/pairing/status",
            params={
                "bootstrap_id": BOOTSTRAP_ID,
                "bootstrap_code": code
            }
        )
        data = response.json()
        
        if data["status"] == "paired":
            print("✅ Paired successfully!")
            save_to_env(data["public_id"], data["agent_token"])
            break
        
        time.sleep(5)

def save_to_env(public_id, token):
    with open(".env", "a") as f:
        f.write(f"\nDEVICE_PUBLIC_ID={public_id}\n")
        f.write(f"DEVICE_TOKEN={token}\n")
```

---

## 🧪 Testing

### 1. Test Bootstrap (cURL)

```bash
curl -X POST https://grow.linn.games/api/agents/bootstrap \
  -H "Content-Type: application/json" \
  -d '{"bootstrap_id":"test-device-001","name":"Test Device"}'
```

**Expected:** Returns `status: "unpaired"` with a 6-digit code.

### 2. Test Pairing (Browser)

1. Login at `https://grow.linn.games/login`
2. Navigate to `https://grow.linn.games/devices/pair`
3. Enter the 6-digit code from step 1
4. Click "Pair Device"

**Expected:** Success message, redirect to dashboard.

### 3. Test Status Polling (cURL)

```bash
curl "https://grow.linn.games/api/agents/pairing/status?bootstrap_id=test-device-001&bootstrap_code=XY42Z7"
```

**Expected (before pairing):** `{"status":"pending"}`  
**Expected (after pairing):** `{"status":"paired","public_id":"...","agent_token":"..."}`

---

## 🔄 Re-Pairing Workflow

If device loses credentials or needs re-pairing:

1. Agent calls `/api/agents/bootstrap` with **same** `bootstrap_id`
2. Laravel recognizes existing device
3. If device is already paired:
   - Regenerates `agent_token`
   - Returns new token immediately
4. Agent saves new token to `.env`

**No user intervention needed** if device was previously paired.

---

## 📚 Related Documentation

- `PAIRING_FLOW.md` - Complete visual flow diagram
- `README.md` - Project overview and ER diagram
- `app/Http/Controllers/BootstrapController.php` - Bootstrap implementation
- `app/Http/Controllers/DevicePairingController.php` - User pairing implementation
- `app/Models/Device.php` - Device model with pairing methods

---

**Status:** ✅ Fully Implemented  
**Version:** 1.0  
**Last Updated:** 2025-12-01
