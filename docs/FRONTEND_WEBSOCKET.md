# Frontend WebSocket Integration - Dokumentation

## Überblick

Das Frontend nutzt **Laravel Echo** mit **Reverb** für Echtzeit-Updates von Device-Capabilities und Command-Status. WebSocket-Events ersetzen das Polling für bessere Performance und UX.

---

## Architektur

```
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Backend                           │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Events (ShouldBroadcast)                            │  │
│  │  - DeviceCapabilitiesUpdated                         │  │
│  │  - CommandStatusUpdated                              │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │        Laravel Reverb (WebSocket Server)             │  │
│  │        Port: 8080 (HTTP) / 443 (HTTPS)               │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          │
                          │ WebSocket
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Browser)                        │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Laravel Echo (resources/js/app.js)                  │  │
│  │  - Verbindet mit Reverb                              │  │
│  │  - Subscribt private Channels                        │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Event Listeners (devices/show.blade.php)            │  │
│  │  - capabilities.updated → Reload Page                │  │
│  │  - command.status.updated → Update UI                │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## Installation

### 1. NPM Dependencies

Die benötigten Packages sind bereits in `package.json` enthalten:

```json
{
    "dependencies": {
        "laravel-echo": "^1.16.1",
        "pusher-js": "^8.4.0-rc2"
    }
}
```

**Installation:**

```bash
npm install
```

### 2. Vite Build

Nach Änderungen in `resources/js/app.js`:

```bash
npm run dev    # Development (Watch Mode)
npm run build  # Production Build
```

---

## Konfiguration

### .env Variablen

```dotenv
# Reverb WebSocket Server
REVERB_APP_ID=683260
REVERB_APP_KEY=zkzj14faofpwi4hhad9w
REVERB_APP_SECRET=kw7lnemcht7nnoxcntta
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite (Frontend Build)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Für Production (HTTPS):**

```dotenv
REVERB_SCHEME=https
REVERB_PORT=443
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## Code-Übersicht

### 1. Echo Initialisierung

**Datei:** `resources/js/app.js`

```javascript
import "./bootstrap";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Initialize Laravel Echo with Reverb
window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
    enabledTransports: ["ws", "wss"],
});

console.log("✓ Laravel Echo initialized");
```

**Was passiert hier:**

-   Echo wird mit Reverb-Konfiguration initialisiert
-   `window.Echo` ist global verfügbar für alle Views
-   Connection-Status wird in Console geloggt

---

### 2. Event Listeners (Device Detail View)

**Datei:** `resources/views/devices/show.blade.php`

```javascript
function initializeWebSocketListeners() {
    if (!window.Echo) {
        console.warn("Echo not initialized, falling back to polling only");
        return;
    }

    console.log(`Subscribing to private channel: device.${deviceDbId}`);

    // Listen to device-specific private channel
    window.Echo.private(`device.${deviceDbId}`)
        .listen(".capabilities.updated", (event) => {
            console.log("Capabilities updated:", event);
            handleCapabilitiesUpdate(event);
        })
        .listen(".command.status.updated", (event) => {
            console.log("Command status updated:", event);
            handleCommandStatusUpdate(event);
        })
        .error((error) => {
            console.error("WebSocket channel error:", error);
        });

    console.log("✓ WebSocket listeners initialized");
}
```

**Channel-Format:**

-   Private Channel: `device.{device_id}` (DB-ID, nicht public_id!)
-   Nur authentifizierter User mit Zugriff auf Device kann subscriben

**Event-Namen:**

-   `.capabilities.updated` → Sensor/Actuator CRUD
-   `.command.status.updated` → Command-Status ändert sich

---

### 3. Event Handler

#### Capabilities Update

```javascript
function handleCapabilitiesUpdate(event) {
    // Reload page to show updated capabilities (sensors/actuators)
    console.log("Reloading page due to capabilities update...");
    addToOutput(
        "⚡ Device capabilities updated - reloading...",
        "text-blue-400"
    );
    setTimeout(() => {
        location.reload();
    }, 1000);
}
```

**Warum Page Reload?**

-   Sensors/Actuators werden dynamisch aus DB gerendert
-   Einfachste Lösung ohne komplexes State-Management
-   Alternativen: Livewire Wire-Polling, Alpine.js Reactive State

#### Command Status Update

```javascript
function handleCommandStatusUpdate(event) {
    const { command_id, status, result_message } = event;

    // Update command in history if visible
    refreshCommandHistory();

    // Add to serial output if completed/failed
    if (status === "completed") {
        addToOutput(
            `✓ Command ${command_id} completed: ${result_message || "OK"}`,
            "text-green-500"
        );
    } else if (status === "failed") {
        addToOutput(
            `✗ Command ${command_id} failed: ${
                result_message || "Unknown error"
            }`,
            "text-red-500"
        );
    } else if (status === "executing") {
        addToOutput(
            `⏳ Command ${command_id} is executing...`,
            "text-blue-400"
        );
    }
}
```

**Payload (event):**

```json
{
    "command_id": 42,
    "status": "completed",
    "result_message": "Pump activated for 1000ms"
}
```

---

## Backend Events

### 1. DeviceCapabilitiesUpdated

**Datei:** `app/Events/DeviceCapabilitiesUpdated.php`

```php
class DeviceCapabilitiesUpdated implements ShouldBroadcast
{
    public function __construct(public Device $device) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("device.{$this->device->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'capabilities.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->device->id,
            'public_id' => $this->device->public_id,
            'capabilities' => $this->device->capabilities,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

**Wann gefeuert:**

-   Nach `Device::syncCapabilitiesFromInstances()` in AddSensor/DeleteSensor
-   Nach `POST /api/growdash/agent/capabilities` (Agent-Update)

---

### 2. CommandStatusUpdated

**Datei:** `app/Events/CommandStatusUpdated.php`

```php
class CommandStatusUpdated implements ShouldBroadcast
{
    public function __construct(public Command $command) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("device.{$this->command->device_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'command.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'command_id' => $this->command->id,
            'status' => $this->command->status,
            'result_message' => $this->command->result_message,
            'completed_at' => $this->command->completed_at?->toISOString(),
        ];
    }
}
```

**Wann gefeuert:**

-   Nach `POST /api/growdash/agent/commands/{id}/result` (Agent sendet Result)
-   Status-Werte: `pending`, `executing`, `completed`, `failed`

---

## Channel Authorization

**Datei:** `routes/channels.php`

```php
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('device.{deviceId}', function (User $user, int $deviceId) {
    $device = Device::find($deviceId);

    // User ist Owner oder hat Share-Zugriff
    return $device && (
        $device->user_id === $user->id ||
        $device->sharedUsers->contains($user->id)
    );
});
```

**Wichtig:**

-   Nur authentifizierte User
-   Nur Devices mit Zugriffsberechtigung (Owner oder Shared)

---

## Reverb Server starten

### Development

```bash
php artisan reverb:start
```

**Ausgabe:**

```
Starting Reverb server on 0.0.0.0:8080...
  ✓ Server started successfully
```

### Production (mit Supervisor)

**supervisor.conf:**

```ini
[program:reverb]
command=php /path/to/growdash/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

---

## Testing

### 1. WebSocket Connection Test (Browser Console)

```javascript
// Prüfen ob Echo initialisiert
console.log(window.Echo);

// Connection Status
console.log(window.wsConnected);

// Manual Subscribe
window.Echo.private("device.1").listen(".capabilities.updated", (e) => {
    console.log("Received:", e);
});
```

### 2. Event Trigger (Tinker)

```bash
php artisan tinker
```

```php
$device = App\Models\Device::find(1);
broadcast(new App\Events\DeviceCapabilitiesUpdated($device));

$command = App\Models\Command::find(1);
broadcast(new App\Events\CommandStatusUpdated($command));
```

### 3. Network Tab (Browser DevTools)

**WebSocket Connection:**

-   URL: `ws://localhost:8080/app/zkzj14faofpwi4hhad9w?protocol=7&...`
-   Messages: JSON payloads mit Events

---

## Troubleshooting

### WebSocket verbindet nicht

**Check 1: Reverb läuft?**

```bash
php artisan reverb:start
```

**Check 2: .env Variablen korrekt?**

```bash
php artisan config:clear
npm run dev
```

**Check 3: Firewall/Ports offen?**

```bash
# Port 8080 muss erreichbar sein
netstat -an | findstr 8080  # Windows
netstat -tuln | grep 8080   # Linux/Mac
```

### Events kommen nicht an

**Check 1: Broadcasting aktiviert?**

```dotenv
# .env
BROADCAST_CONNECTION=reverb  # NICHT 'log'!
```

**Check 2: Channel Authorization?**

```php
// routes/channels.php - Return muss true sein
Broadcast::channel('device.{deviceId}', function (User $user, int $deviceId) {
    return true; // Für Tests
});
```

**Check 3: Event implementiert ShouldBroadcast?**

```php
class DeviceCapabilitiesUpdated implements ShouldBroadcast
```

### Polling läuft trotzdem

Das ist OK! Polling ist **Fallback** wenn WebSocket nicht verfügbar:

```javascript
if (window.wsConnected) {
    // WebSocket is active, reduce polling frequency
    return;
}
```

---

## Performance-Optimierung

### 1. Event Queue (Optional)

Events im Queue statt synchron:

**Event:**

```php
class CommandStatusUpdated implements ShouldBroadcast, ShouldQueue
{
    use SerializesModels;
}
```

**Queue Worker starten:**

```bash
php artisan queue:work
```

### 2. Presence Channels (Optional)

Zeige "Wer ist gerade online" auf Device-Seite:

```javascript
window.Echo.join(`device.${deviceDbId}`)
    .here((users) => {
        console.log("Users here:", users);
    })
    .joining((user) => {
        console.log("User joined:", user);
    })
    .leaving((user) => {
        console.log("User left:", user);
    });
```

---

## UI-Feedback (Optional)

### WebSocket Status Indicator

```blade
<div id="ws-status" class="fixed top-4 right-4 px-3 py-1 rounded-full text-xs">
    <span id="ws-status-text">Connecting...</span>
</div>

<script>
window.Echo.connector.pusher.connection.bind('connected', () => {
    const el = document.getElementById('ws-status');
    el.className = 'fixed top-4 right-4 px-3 py-1 rounded-full text-xs bg-green-100 text-green-700';
    document.getElementById('ws-status-text').textContent = '✓ Connected';
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    const el = document.getElementById('ws-status');
    el.className = 'fixed top-4 right-4 px-3 py-1 rounded-full text-xs bg-red-100 text-red-700';
    document.getElementById('ws-status-text').textContent = '✗ Disconnected';
});
</script>
```

---

## Zusammenfassung

✅ **Echo** ist initialisiert in `resources/js/app.js`  
✅ **Events** werden im Backend gebroadcastet  
✅ **Frontend** subscribt private Channels per Device  
✅ **Capabilities Update** → Page Reload  
✅ **Command Status** → UI Update in Echtzeit  
✅ **Fallback** auf Polling wenn WebSocket nicht verfügbar  
✅ **Authorization** via Channel-Routes

**Das Frontend ist jetzt vollständig WebSocket-fähig!** 🚀
