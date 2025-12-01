# Growdash Multi-Tenant IoT Platform - Architecture

> Decisions (2025-12-01): Team sharing via `users_devices` pivot; agent auth with Sanctum; commands over WebSocket; migrate legacy sensor tables into unified `measurements` and clean up.

## System Overview

**Domain**: `https://grow.linn.games`  
**API Prefix**: `/api/growdash`  
**Auth**: Multi-Tenant (User → Devices), Device Token-based for Agents, plus Sanctum API tokens for agents/users

## Core Principles

1. **Multi-Tenancy**: Strikte User-Device-Isolation via `user_id`
2. **Dynamic Capabilities**: Keine hardcodierten Sensoren - alles über `capabilities` JSON
3. **Real-time State**: `device_latest_states` Caching für schnelle Dashboards
4. **Secure Pairing**: Time-limited Pair-Codes für Device-Registrierung
5. **Command Queue**: Prefer WebSocket delivery (Reverb); keep HTTP long-poll fallback

6. **Team Sharing**: Optional multi-user device access via `users_devices` pivot

---

## Data Model

### Tables

#### `users`

-   Standard Laravel User (email, password, name)

#### `devices`

```sql
id, user_id (FK), public_id (UUID), device_token (hash),
name, board_type (ESP32/Raspberry/Custom),
last_seen_at, status (online/offline/error),
capabilities (JSON), last_state (JSON),
paired_at, created_at, updated_at
```

**Capabilities JSON Structure:**

```json
{
    "sensors": [
        {
            "id": "water_level",
            "unit": "%",
            "type": "float",
            "range": [0, 100]
        },
        { "id": "tds", "unit": "ppm", "type": "int" },
        { "id": "temp", "unit": "°C", "type": "float" }
    ],
    "actuators": [
        { "id": "spray", "type": "duration", "params": ["seconds"] },
        { "id": "fill", "type": "target", "params": ["level", "liters"] }
    ],
    "firmware": "v1.2.3",
    "board": "ESP32-WROOM"
}
```

**Last State JSON:**

```json
{
    "water_level": {
        "value": 75.5,
        "unit": "%",
        "timestamp": "2025-12-01T16:30:00Z"
    },
    "tds": { "value": 450, "unit": "ppm", "timestamp": "2025-12-01T16:30:05Z" }
}
```

#### `telemetry_readings`

```sql
id, device_id (FK), sensor_key, value (float),
unit, raw (JSON for complex data), measured_at
```

Index: `(device_id, sensor_key, measured_at)`

#### `measurements` (normalized, replaces legacy water/tds/etc.)

```sql
id, device_id (FK), sensor_key (string), value (decimal),
unit (nullable), raw (JSON nullable), measured_at (datetime indexed)
```

Index: `(device_id, sensor_key, measured_at)`

#### `commands`

```sql
id, device_id (FK), type (spray/fill/custom),
params (JSON), status (pending/executing/completed/failed),
result_message, created_at, completed_at, created_by_user_id
```

Index: `(device_id, status)`

#### `device_logs`

```sql
id, device_id (FK), level (debug/info/warning/error),
message, context (JSON), created_at
```

#### `pair_codes` (time-limited)

```sql
id, user_id (FK), code (6-char unique),
device_name, expires_at, used_at, device_id (nullable)

#### `users_devices` (sharing pivot)

```sql
user_id (FK), device_id (FK), role (viewer/operator/owner), created_at, updated_at
```
```

---

## API Endpoints

### 🔓 Public (No Auth)

#### `POST /api/growdash/devices/register`

**Purpose**: Agent registriert sich mit Pair-Code  
**Request**:

```json
{
    "device_public_id": "uuid-from-agent",
    "pair_code": "ABC123",
    "capabilities": {
        /* siehe oben */
    }
}
```

**Response**:

```json
{
    "success": true,
    "device_token": "long-random-token",
    "device_name": "My Growdash",
    "polling_interval": 30
}
```

### 🔐 Device Auth (X-Device-ID + X-Device-Token)

#### `POST /api/growdash/telemetry`

**Request**:

```json
{
    "readings": [
        {
            "sensor_key": "water_level",
            "value": 75.5,
            "unit": "%",
            "raw": null,
            "measured_at": "2025-12-01T16:30:00Z"
        }
    ]
}
```

**Response**: `{ "success": true, "stored": 3 }`

#### `GET /api/growdash/commands/pending`

**Response**:

```json
{
    "commands": [
        {
            "id": 123,
            "type": "spray",
            "params": { "seconds": 5 }
        }
    ]
}
```

WebSocket delivery: Commands are also broadcast on channel `devices.{public_id}` as `CommandCreated` events; devices ACK via `CommandUpdated`.

#### `POST /api/growdash/commands/{id}/result`

**Request**:

```json
{
    "success": true,
    "message": "Sprayed for 5.2 seconds",
    "timestamp": "2025-12-01T16:35:00Z"
}
```

On success, emits `CommandUpdated` over Reverb.

#### `POST /api/growdash/devices/capabilities`

**Request**: `{ "capabilities": { /* JSON */ } }`

Persists to `devices.capabilities` and may update `last_state`; emits `DeviceStateUpdated`.

#### `POST /api/growdash/logs`

**Request**:

```json
{
    "level": "info",
    "message": "System started",
    "context": { "uptime": 123 }
}
```

#### `POST /api/growdash/heartbeat`

**Purpose**: Updates `last_seen_at` (Auto-Update bei jedem Request via Middleware)

### 👤 User Auth (auth:web)

#### `GET /api/growdash/devices`

**Response**: Liste aller Devices des Users mit `last_state`

#### `POST /api/growdash/devices/pair-code`

**Request**: `{ "device_name": "Kitchen Growdash" }`  
**Response**: `{ "pair_code": "ABC123", "expires_at": "..." }`

#### `DELETE /api/growdash/devices/{id}`

**Purpose**: Device löschen (nur eigene)

#### `POST /api/growdash/devices/{id}/rotate-token`

**Response**: `{ "new_token": "..." }` (Agent muss neu konfiguriert werden)

#### `POST /api/growdash/commands`

**Request**:

```json
{
    "device_id": 5,
    "type": "spray",
    "params": { "seconds": 10 }
}
```

#### `GET /api/growdash/telemetry/{device_id}?sensor=water_level&from=...&to=...&limit=100`

**Purpose**: Historische Daten für Charts

#### `GET /api/growdash/devices/{id}/logs?level=error&limit=200`

---

## Agent .env Configuration

```env
# Laravel API
LARAVEL_BASE_URL=https://grow.linn.games
LARAVEL_API_PREFIX=/api/growdash

# Device Credentials (nach Pairing)
DEVICE_PUBLIC_ID=uuid-generated-by-agent
DEVICE_TOKEN=token-from-registration-response

# Optional: Für Erst-Registrierung
PAIR_CODE=ABC123  # Vom User im Web-UI generiert
```

---

## User Journey

### 1. User Registration

-   Sign up → Email verifizieren → Login

### 2. Device Setup (Web-UI)

-   Dashboard → "Add Device"
-   Input: Device Name → Generate Pair-Code
-   UI zeigt: **"ABC123"** (6 Minuten gültig)

### 3. Agent Installation (Raspberry Pi)

```bash
# Agent installieren
git clone ...
cd growdash-agent

# .env konfigurieren
cp .env.example .env
nano .env
# PAIR_CODE=ABC123 eintragen
# LARAVEL_BASE_URL=https://grow.linn.games

# Agent starten
python3 main.py
```

### 4. Agent-Registrierung (automatisch)

-   Agent generiert `device_public_id` (UUID)
-   Sendet `POST /devices/register` mit Pair-Code
-   Erhält `device_token`
-   Speichert Token in `.env` (oder config file)
-   Wechselt in Normal-Mode

### 5. Normal Operation

-   Agent sendet alle 30s Telemetrie
-   Agent pollt alle 10s pending Commands
-   User sieht Live-Dashboard mit Sensor-Werten
-   User klickt "Spray 5s" → Command in Queue → Agent führt aus

---

## Security

### Multi-Tenant Isolation

-   **Devices Query**: Immer `->where('user_id', auth()->id())`
-   **Telemetry Query**: Join über `devices.user_id`
-   **Commands Query**: Join über `devices.user_id`
-   **Policy**: `DevicePolicy::view()` prüft Ownership

### Device Token Security

-   Token als Hash speichern (bcrypt/hash)
-   Nur bei Erstellung/Rotation im Klartext zurückgeben
-   Rotation-Mechanismus: User kann Token neu generieren
-   Rate-Limiting auf Registrierung (max 10 Devices pro User?)

### Pair-Code Security

-   6-stellig, unique, Groß-/Kleinbuchstaben + Zahlen
-   Expires nach 10 Minuten
-   Single-Use (used_at gesetzt nach Registrierung)
-   Max 5 aktive Codes pro User gleichzeitig

---

## Implementation Priority

### Phase 1: Core Infrastructure ✅

-   [x] User-Device Relations (user_id, public_id, agent_token)
-   [x] DevicePolicy
-   [x] Bootstrap-Flow (bootstrap_id, bootstrap_code)

### Phase 2: Dynamic Capabilities (AKTUELL)

-   [ ] Migration: capabilities (JSON), last_state (JSON) zu devices
-   [ ] Migration: telemetry_readings Tabelle
-   [ ] Migration: commands Tabelle (erweitern)
-   [ ] Migration: device_logs Tabelle
-   [ ] Migration: pair_codes Tabelle

### Phase 3: API Implementation

-   [ ] TelemetryController
-   [ ] CommandController
-   [ ] DeviceController (CRUD, Pairing)
-   [ ] LogController
-   [ ] Middleware: UpdateLastSeen

### Phase 4: Web UI (Livewire)

-   [ ] Dashboard (alle Devices + Last State)
-   [ ] Device Detail (Charts, Commands, Logs)
-   [ ] Pair-Code Generator
-   [ ] Command Center
-   [ ] Device Settings (Token Rotation)

### Phase 5: Real-time & Polish

-   [ ] WebSocket Integration (Laravel Reverb)
-   [ ] Event Broadcasting (TelemetryReceived, CommandCompleted)
-   [ ] Frontend Charts (Chart.js / ApexCharts)
-   [ ] Mobile-Responsive Design

---

## Migration from Legacy

### Mapping Old → New

-   `GrowdashWebhookController::log()` → `TelemetryController::store()` + `LogController::store()`
-   `device_slug` → `device_public_id`
-   Hardcoded WaterLevel/TDS/Temp Models → Generic `telemetry_readings`
-   `manual-spray` / `manual-fill` → `commands` Queue
-   `bootstrap_id` → `device_public_id` (agent-generated UUID)

### Data Migration Script

```php
// Alte water_levels → telemetry_readings migrieren
WaterLevel::chunk(1000, function ($levels) {
    foreach ($levels as $level) {
        TelemetryReading::create([
            'device_id' => $level->device_id,
            'sensor_key' => 'water_level',
            'value' => $level->level_percent,
            'unit' => '%',
            'measured_at' => $level->measured_at,
        ]);
    }
});
```

---

**Status**: Phase 1 abgeschlossen (Bootstrap), Phase 2 startet jetzt
