# Growdash - Laravel 12 Multi-Tenant IoT Platform

Minimales, produktives Laravel 12 Backend für IoT-Device-Management mit Multi-Tenant-Architektur, Bootstrap/Pairing-Flow und Agent-Authentifizierung.

## Projektübersicht

Schlankes Projekt-Setup ohne unnötige Abstraktion:

-   **Multi-Tenant**: Jeder User verwaltet eigene Devices via User-Device-Ownership
-   **Device-Pairing**: 6-stelliger Bootstrap-Code für sichere Gerätekopplung
-   **Agent-API**: Minimal aber sicher - nur notwendige Endpoints (heartbeat, pending commands, command result)
-   **Authentifizierung**: SHA256-gehashte Agent-Tokens über X-Device-ID / X-Device-Token Headers
-   **Clean Code**: Telemetry, DTOs, Capabilities, Logs wurden entfernt - nur funktionale Core bleibt

## Datenmodell

```
USERS
  ├─ id (PK)
  ├─ name
  ├─ email
  └─ password

DEVICES
  ├─ id (PK)
  ├─ user_id (FK → USERS)
  ├─ name
  ├─ slug
  ├─ public_id (UUID)
  ├─ agent_token (SHA256 hash)
  ├─ bootstrap_id
  ├─ bootstrap_code (6-char pairing)
  ├─ paired_at
  ├─ status (paired|unpaired)
  ├─ last_seen_at
  └─ created_at

COMMANDS
  ├─ id (PK)
  ├─ device_id (FK → DEVICES)
  ├─ created_by_user_id (FK → USERS)
  ├─ type (string)
  ├─ params (JSON)
  ├─ status (pending|executing|completed|failed)
  ├─ result_data (JSON)
  ├─ completed_at
  └─ created_at
```

## Installation & Setup

### 1. Dependencies installieren

```bash
composer install
npm install
npm run build
```

### 2. Environment konfigurieren

Kopiere `.env.example` zu `.env`:

```env
APP_NAME=GrowDash
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Growdash Legacy (optional für Webhooks)
GROWDASH_WEBHOOK_TOKEN=super-secret-token
```

### 3. Datenbank migrieren

```bash
php artisan migrate
```

### 4. Test-User erstellen

```bash
php artisan db:seed --class=UserSeeder
# → admin@growdash.local / password
```

## API-Dokumentation

### 🔐 Bootstrap & Pairing

#### 1️⃣ Agent Bootstrap (öffentlich)

**POST** `/api/agents/bootstrap`

Agent sendet Hardware-ID beim ersten Start.

**Request:**

```json
{
    "bootstrap_id": "esp32-abc123def456"
}
```

**Response (unpaired):**

```json
{
    "status": "unpaired",
    "bootstrap_code": "XY42Z7",
    "message": "Device registered. Please pair via web UI with code: XY42Z7"
}
```

**Response (paired):**

```json
{
    "status": "paired",
    "public_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "agent_token": "7f3d9a8b...64-char-plaintext-token...c2e1f4a6",
    "device_name": "Kitchen GrowBox"
}
```

#### 2️⃣ User Pairing (auth:web)

**POST** `/api/devices/pair`

User gibt 6-stelligen Code ein, um Device zu koppeln.

**Request:**

```json
{
    "bootstrap_code": "XY42Z7"
}
```

**Response:**

```json
{
    "success": true,
    "device": {
        "id": 1,
        "name": "Kitchen GrowBox",
        "public_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
        "paired_at": "2025-12-01T16:42:00Z"
    },
    "agent_token": "7f3d9a8b...64-char-plaintext-token...c2e1f4a6"
}
```

⚠️ **Token wird nur beim Pairing angezeigt** - Agent muss ihn speichern!

---

### 🤖 Agent API (Device-Authenticated)

Header erforderlich:

```
X-Device-ID: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
X-Device-Token: 7f3d9a8b...64-char-plaintext-token...c2e1f4a6
```

#### POST `/api/growdash/agent/heartbeat`

Agent meldet sich regelmäßig an.

**Response:**

```json
{
    "success": true,
    "last_seen_at": "2025-12-06T10:30:00Z"
}
```

#### GET `/api/growdash/agent/commands/pending`

Agent holt ausstehende Commands ab.

**Response:**

```json
{
    "commands": [
        {
            "id": 42,
            "type": "spray",
            "params": { "duration": 10 },
            "created_at": "2025-12-06T10:30:00Z"
        }
    ]
}
```

#### POST `/api/growdash/agent/commands/{id}/result`

Agent meldet Command-Ergebnis zurück.

**Request:**

```json
{
    "status": "completed",
    "result_data": {
        "duration_actual": 10,
        "success": true
    }
}
```

#### POST `/api/growdash/agent/arduino/compile`

Kompiliert Arduino-Code auf dem Gerät.

**Request:**

```json
{
    "code": "void setup() { pinMode(13, OUTPUT); } void loop() { digitalWrite(13, HIGH); }",
    "board": "arduino:avr:uno"
}
```

**Response:**

```json
{
    "success": true,
    "command_id": 42,
    "message": "Compile command queued"
}
```

#### POST `/api/growdash/agent/arduino/upload`

Kompiliert und uploaded Firmware zum Arduino.

**Request:**

```json
{
    "code": "void setup() { ... }",
    "board": "arduino:avr:uno",
    "port": "/dev/ttyACM0"
}
```

#### GET `/api/growdash/agent/ports/scan`

Scannt verfügbare Serial-Ports.

**Response wird in Command Result geliefert:**

```json
{
    "ports": [
        {
            "port": "/dev/ttyACM0",
            "description": "Arduino Uno",
            "vendor_id": "2341",
            "product_id": "0043"
        }
    ],
    "count": 1
}
```

## Todo-Liste

### ✅ Core-Features (abgeschlossen)

-   [x] Multi-Tenant Device-Ownership
-   [x] 6-stelliger Bootstrap & Pairing-Code
-   [x] SHA256 Agent-Token-Hashing
-   [x] Device-Auth Middleware (X-Device-ID + X-Device-Token)
-   [x] Agent Heartbeat Endpoint
-   [x] Command Pending/Result Endpoints
-   [x] Pairing Flow Tests (OnboardingTest)
-   [x] Code Cleanup (Telemetry, DTOs, Capabilities, Logs entfernt)
-   [x] Intelephense Config (Test-Directory ausgeschlossen)

### 📋 Frontend (optional)

-   [ ] Dashboard mit Device-Liste
-   [ ] Device-Pairing UI
-   [ ] Command-Controls
-   [ ] Status-Anzeige

## Installation

1. **Dependencies installieren:**

    ```bash
    composer install
    npm install
    npm run build
    ```

2. **Environment konfigurieren:**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Datenbank migrieren:**

    ```bash
    php artisan migrate
    ```

4. **Test-User erstellen:**

    ```bash
    php artisan db:seed
    # Credentials: admin@growdash.local / password
    ```

5. **Tests ausführen:**
    ```bash
    php artisan test
    ```

## Technologie-Stack

-   **Backend**: Laravel 12, PHP 8.3+
-   **Database**: SQLite (standard) / MySQL / PostgreSQL
-   **Frontend**: Livewire 3 + Flux UI Components
-   **Testing**: Pest PHP
-   **Auth**: Fortify (Web) + Custom Device Middleware (API)
-   **Deployment**: Docker Compose Ready

**Status**: ✅ Core Agent API vollständig + Pairing Flow funktionierend  
**Letzte Aktualisierung**: 2025-12-06
