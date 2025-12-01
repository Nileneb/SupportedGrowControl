# WebSocket-Integration mit Laravel Reverb

## Installation

### 1. Laravel Reverb installieren

```bash
composer require laravel/reverb
php artisan reverb:install
```

### 2. Environment-Variablen (.env)

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 3. Broadcasting-Konfiguration

Die Konfiguration erfolgt automatisch durch `reverb:install`. Prüfe `config/broadcasting.php`:

```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => env('REVERB_SERVER_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],
],
```

## Broadcasting-Events erstellen

### 1. DeviceStatusUpdated Event

```bash
php artisan make:event DeviceStatusUpdated
```

```php
<?php

namespace App\Events;

use App\Models\Device;
use App\Models\SystemStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device,
        public SystemStatus $status
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('device.' . $this->device->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->device->id,
            'device_slug' => $this->device->slug,
            'water_level' => $this->status->water_level,
            'water_liters' => $this->status->water_liters,
            'spray_active' => $this->status->spray_active,
            'filling_active' => $this->status->filling_active,
            'last_tds' => $this->status->last_tds,
            'last_temperature' => $this->status->last_temperature,
            'timestamp' => $this->status->measured_at->timestamp,
        ];
    }
}
```

### 2. NewLogReceived Event

```bash
php artisan make:event NewLogReceived
```

```php
<?php

namespace App\Events;

use App\Models\ArduinoLog;
use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewLogReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device,
        public ArduinoLog $log
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('device.' . $this->device->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'log.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->log->id,
            'level' => $this->log->level,
            'message' => $this->log->message,
            'timestamp' => $this->log->logged_at->timestamp,
        ];
    }
}
```

### 3. SprayEventStarted Event

```php
<?php

namespace App\Events;

use App\Models\Device;
use App\Models\SprayEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SprayEventStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device,
        public SprayEvent $event
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('device.' . $this->device->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'spray.started';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->event->id,
            'manual' => $this->event->manual,
            'start_time' => $this->event->start_time->timestamp,
        ];
    }
}
```

## Events im Controller dispatchen

Update `GrowdashWebhookController.php`:

```php
use App\Events\DeviceStatusUpdated;
use App\Events\NewLogReceived;
use App\Events\SprayEventStarted;

// In updateStatus() method:
protected function updateStatus(Device $device, array $attributes): void
{
    $status = $device->systemStatuses()->latest('measured_at')->first();

    if (!$status) {
        $status = new SystemStatus([
            'device_id' => $device->id,
            'measured_at' => now(),
        ]);
    }

    foreach ($attributes as $key => $value) {
        $status->{$key} = $value;
    }

    $status->measured_at = now();
    $status->save();

    // Broadcast status update
    broadcast(new DeviceStatusUpdated($device, $status))->toOthers();
}

// In log() method:
public function log(Request $request): JsonResponse
{
    // ... existing code ...

    $log = ArduinoLog::create([...]);

    // Broadcast new log
    broadcast(new NewLogReceived($device, $log))->toOthers();

    $this->parseMessage($device, $log->message);

    return response()->json(['success' => true, 'log_id' => $log->id]);
}
```

## Frontend-Integration

### 1. JavaScript Setup (resources/js/app.js)

```javascript
import './bootstrap';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

console.log('Laravel Echo initialized');
```

### 2. NPM Dependencies

```bash
npm install --save-dev laravel-echo pusher-js
```

Update `vite.config.js`:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

### 3. Livewire Component mit WebSocket-Listener

```php
<?php

namespace App\Livewire\Growdash;

use App\Models\Device;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public Device $device;
    public $waterLevel = 0;
    public $waterLiters = 0;
    public $tds = null;
    public $temperature = null;
    public $sprayActive = false;
    public $fillingActive = false;

    public function mount(Device $device)
    {
        $this->device = $device;
        $this->refreshStatus();
    }

    public function refreshStatus()
    {
        $status = $this->device->latestStatus();
        
        if ($status) {
            $this->waterLevel = $status->water_level;
            $this->waterLiters = $status->water_liters;
            $this->tds = $status->last_tds;
            $this->temperature = $status->last_temperature;
            $this->sprayActive = $status->spray_active;
            $this->fillingActive = $status->filling_active;
        }
    }

    #[On('echo-private:device.{device.id},status.updated')]
    public function handleStatusUpdate($payload)
    {
        $this->waterLevel = $payload['water_level'];
        $this->waterLiters = $payload['water_liters'];
        $this->tds = $payload['last_tds'];
        $this->temperature = $payload['last_temperature'];
        $this->sprayActive = $payload['spray_active'];
        $this->fillingActive = $payload['filling_active'];
    }

    public function getListeners()
    {
        return [
            "echo-private:device.{$this->device->id},status.updated" => 'handleStatusUpdate',
        ];
    }

    public function render()
    {
        return view('livewire.growdash.dashboard');
    }
}
```

### 4. Blade View mit Alpine.js

```blade
<div x-data="{ connected: false }">
    <div class="status-indicator">
        <span x-show="connected" class="text-green-500">🟢 Live</span>
        <span x-show="!connected" class="text-gray-500">⚫ Offline</span>
    </div>

    <div class="status-grid">
        <div class="status-card">
            <h3>Wasserstand</h3>
            <div class="value" wire:poll.5s="refreshStatus">
                {{ $waterLevel }}%
            </div>
            <div class="sub-value">{{ $waterLiters }}L</div>
        </div>

        <div class="status-card">
            <h3>TDS</h3>
            <div class="value">
                {{ $tds ?? '--' }} ppm
            </div>
        </div>

        <div class="status-card">
            <h3>Temperatur</h3>
            <div class="value">
                {{ $temperature ?? '--' }}°C
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('websocketConnection', () => ({
                init() {
                    window.Echo.private('device.{{ $device->id }}')
                        .listen('.status.updated', (e) => {
                            this.connected = true;
                            console.log('Status updated:', e);
                        })
                        .error((error) => {
                            this.connected = false;
                            console.error('WebSocket error:', error);
                        });
                }
            }));
        });
    </script>
</div>
```

## Broadcasting Authorization

In `routes/channels.php`:

```php
use App\Models\Device;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('device.{deviceId}', function ($user, $deviceId) {
    // Alle authentifizierten User können Device-Channels abonnieren
    // Optional: Device-User-Relation prüfen
    return $user !== null;
});
```

## Reverb Server starten

### Development:

```bash
php artisan reverb:start
```

Optional mit debug output:

```bash
php artisan reverb:start --debug
```

### Production (mit Supervisor):

```ini
[program:reverb]
command=php /path/to/artisan reverb:start
directory=/path/to/project
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/reverb.log
```

## Testing

### Test WebSocket Connection:

```javascript
// Browser Console
window.Echo.private('device.1')
    .listen('.status.updated', (e) => {
        console.log('Received:', e);
    });
```

### Trigger Event manually:

```php
// Tinker
php artisan tinker

$device = App\Models\Device::first();
$status = $device->latestStatus();
broadcast(new App\Events\DeviceStatusUpdated($device, $status));
```

## Troubleshooting

### WebSocket verbindet nicht:

1. Reverb-Server läuft: `php artisan reverb:start`
2. Port 8080 ist frei: `netstat -ano | findstr :8080`
3. Firewall-Regel für Port 8080

### Events kommen nicht an:

1. Broadcasting-Driver prüfen: `BROADCAST_CONNECTION=reverb`
2. Queue-Worker läuft (falls `ShouldQueue` verwendet)
3. Browser-Console auf Fehler prüfen

### Production-Setup:

1. SSL/TLS für WSS (via Nginx/Apache Proxy)
2. Reverb hinter Reverse-Proxy
3. Supervisor für Auto-Restart

---

**Status**: 📋 Konfiguration vorbereitet  
**Nächste Schritte**:
1. `php artisan reverb:install` ausführen
2. Events erstellen
3. Controller aktualisieren
4. Frontend testen
