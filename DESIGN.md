# Growdash Dashboard - UI/UX Design Konzept

## Design-Philosophie

- **Modern & Clean**: Flux UI Design Language
- **Echtzeit-fokussiert**: Live-Updates via WebSockets
- **Datenvisualisierung**: Charts für Trends und Historien
- **Responsive**: Desktop-first, mobile-tauglich
- **Dark Mode**: Optional (Flux unterstützt dies nativ)

## Seiten-Struktur

### 1. Dashboard (Übersicht)
**Route:** `/dashboard/growdash`

#### Layout-Komponenten:

**Header**
- Logo/Titel: "Growdash"
- Device-Selector (Dropdown wenn mehrere Devices)
- User-Menu (rechts)
- Notifications-Bell (für Alerts)

**Hero-Section: Aktueller Status**
```
┌─────────────────────────────────────────────────────┐
│  Device: growdash-1        🟢 Online  Last: 2m ago  │
├─────────────────────────────────────────────────────┤
│                                                       │
│   Wasserstand          TDS               Temp        │
│   ┌────┐75.3%         450.2 ppm         22.5°C      │
│   │████│                                              │
│   │████│15.2L          Normal           Optimal      │
│   └────┘                                              │
│                                                       │
│   [Manual Fill]  [Manual Spray]        [Refresh]    │
└─────────────────────────────────────────────────────┘
```

**Grid: 3-Spalten-Layout**

**Spalte 1: Wasserstand-Chart (24h)**
- Line Chart mit Zoom
- Min/Max-Marker
- Tooltip beim Hover

**Spalte 2: TDS & Temperatur**
- Dual-Axis Chart
- Color-coded (blau=Temp, grün=TDS)
- Zeitrange-Selector (1h, 6h, 24h, 7d)

**Spalte 3: Aktive Events**
- Timeline der letzten Events
- Spray-Events (grün)
- Fill-Events (blau)
- Errors (rot)

**Bottom Section: Recent Logs**
- Table mit den letzten 10 Logs
- Filter nach Level (all, info, warning, error)
- Live-Update bei neuen Logs

### 2. History / Analytics
**Route:** `/dashboard/growdash/history`

**Große Charts:**
- Wasserstand-Verlauf (7/14/30 Tage)
- TDS-Trend-Analyse
- Temperatur-Schwankungen
- Event-Frequency-Analyse

**Export-Funktion:**
- CSV-Export für Daten
- PDF-Report-Generator

### 3. Control Panel
**Route:** `/dashboard/growdash/control`

**Manuelle Steuerung:**
- Spray aktivieren/deaktivieren mit Timer
- Fill starten mit Target-Level
- Emergency Stop (alle Aktionen beenden)

**Zeitpläne:**
- Recurring Spray (z.B. alle 3h für 2min)
- Auto-Fill bei Level < X%

### 4. Logs & Debug
**Route:** `/dashboard/growdash/logs`

**Advanced Log-Viewer:**
- Volltext-Suche
- Filter: Level, Zeitbereich, Keyword
- Pagination (100 pro Seite)
- Auto-Refresh Toggle

### 5. Settings
**Route:** `/dashboard/growdash/settings`

**Device-Konfiguration:**
- Name, IP, Serial Port
- Webhook-Token anzeigen/regenerieren
- Alerts konfigurieren (z.B. E-Mail bei Level < 20%)

## Komponenten-Design (Flux)

### Status-Cards

```vue
<flux:card class="growdash-status-card">
    <flux:heading size="lg">{{ $title }}</flux:heading>
    <div class="metric-value">
        {{ $value }} <span class="unit">{{ $unit }}</span>
    </div>
    <flux:badge :variant="$status">{{ $statusLabel }}</flux:badge>
</flux:card>
```

**Status-Farben:**
- `success`: Grün (optimal)
- `warning`: Gelb (Achtung)
- `danger`: Rot (kritisch)
- `info`: Blau (neutral)

### Chart-Component (ApexCharts Integration)

```vue
<div class="chart-container" wire:poll.5s="refreshChartData">
    <div id="water-level-chart"></div>
</div>

<script>
    Alpine.data('waterLevelChart', () => ({
        chart: null,
        init() {
            this.chart = new ApexCharts(document.querySelector("#water-level-chart"), {
                series: [{
                    name: 'Water Level',
                    data: @json($waterLevelData)
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        dynamicAnimation: {
                            speed: 1000
                        }
                    }
                },
                xaxis: {
                    type: 'datetime'
                },
                yaxis: {
                    max: 100,
                    title: {
                        text: 'Level (%)'
                    }
                }
            });
            this.chart.render();
        }
    }))
</script>
```

### Manual Control Buttons

```vue
<div class="control-panel">
    <flux:button 
        wire:click="toggleSpray" 
        :variant="$sprayActive ? 'danger' : 'primary'"
        :loading="$loading"
    >
        @if($sprayActive)
            <flux:icon.stop /> Stop Spray
        @else
            <flux:icon.play /> Start Spray
        @endif
    </flux:button>

    <flux:button 
        wire:click="$dispatch('open-modal', 'fill-modal')" 
        variant="primary"
    >
        <flux:icon.droplet /> Fill Tank
    </flux:button>
</div>
```

### Event Timeline

```vue
<div class="event-timeline">
    @foreach($recentEvents as $event)
        <div class="timeline-item {{ $event->type }}">
            <div class="timeline-marker">
                @if($event->type === 'spray')
                    <flux:icon.spray />
                @else
                    <flux:icon.droplet />
                @endif
            </div>
            <div class="timeline-content">
                <span class="time">{{ $event->start_time->diffForHumans() }}</span>
                <p>{{ $event->description }}</p>
                @if($event->duration_seconds)
                    <span class="duration">{{ $event->duration_seconds }}s</span>
                @endif
            </div>
        </div>
    @endforeach
</div>
```

## Livewire-Komponenten

### 1. `DeviceStatusCard.php`

```php
class DeviceStatusCard extends Component
{
    public Device $device;
    
    #[On('device-status-updated')]
    public function refreshStatus()
    {
        $this->device->refresh();
    }
    
    public function render()
    {
        $status = $this->device->latestStatus();
        
        return view('livewire.growdash.device-status-card', [
            'waterLevel' => $status?->water_level ?? 0,
            'tds' => $status?->last_tds,
            'temperature' => $status?->last_temperature,
            'sprayActive' => $status?->spray_active ?? false,
            'fillingActive' => $status?->filling_active ?? false,
        ]);
    }
}
```

### 2. `WaterLevelChart.php`

```php
class WaterLevelChart extends Component
{
    public Device $device;
    public string $range = '24h';
    
    public function getWaterLevelDataProperty()
    {
        $since = match($this->range) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
        };
        
        return $this->device->waterLevels()
            ->where('measured_at', '>=', $since)
            ->orderBy('measured_at')
            ->get()
            ->map(fn($wl) => [
                $wl->measured_at->timestamp * 1000, // JS timestamp
                $wl->level_percent
            ])
            ->toArray();
    }
    
    public function render()
    {
        return view('livewire.growdash.water-level-chart');
    }
}
```

### 3. `ManualControlPanel.php`

```php
class ManualControlPanel extends Component
{
    public Device $device;
    
    public function toggleSpray()
    {
        $status = $this->device->latestStatus();
        $newState = !$status->spray_active;
        
        // Call API
        Http::withHeaders([
            'X-Growdash-Token' => config('services.growdash.webhook_token')
        ])->post('/api/growdash/manual-spray', [
            'device_slug' => $this->device->slug,
            'action' => $newState ? 'on' : 'off',
        ]);
        
        $this->dispatch('device-status-updated');
        
        session()->flash('success', $newState ? 'Spray activated' : 'Spray stopped');
    }
    
    public function startFill($targetLevel)
    {
        Http::withHeaders([
            'X-Growdash-Token' => config('services.growdash.webhook_token')
        ])->post('/api/growdash/manual-fill', [
            'device_slug' => $this->device->slug,
            'action' => 'start',
            'target_level' => $targetLevel,
        ]);
        
        $this->dispatch('device-status-updated');
        session()->flash('success', "Fill started (target: {$targetLevel}%)");
    }
    
    public function render()
    {
        return view('livewire.growdash.manual-control-panel');
    }
}
```

### 4. `EventTimeline.php`

```php
class EventTimeline extends Component
{
    public Device $device;
    public int $limit = 10;
    
    #[On('device-status-updated')]
    public function refresh() {}
    
    public function getEventsProperty()
    {
        $sprays = $this->device->sprayEvents()->latest('start_time')->take($this->limit/2);
        $fills = $this->device->fillEvents()->latest('start_time')->take($this->limit/2);
        
        return $sprays->get()->merge($fills->get())
            ->sortByDesc('start_time')
            ->take($this->limit);
    }
    
    public function render()
    {
        return view('livewire.growdash.event-timeline');
    }
}
```

## WebSocket-Events

### Broadcasting-Events

```php
// app/Events/DeviceStatusUpdated.php
class DeviceStatusUpdated implements ShouldBroadcast
{
    public function __construct(
        public Device $device,
        public SystemStatus $status
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('device.' . $this->device->id),
        ];
    }
    
    public function broadcastWith(): array
    {
        return [
            'water_level' => $this->status->water_level,
            'water_liters' => $this->status->water_liters,
            'tds' => $this->status->last_tds,
            'temperature' => $this->status->last_temperature,
            'spray_active' => $this->status->spray_active,
            'filling_active' => $this->status->filling_active,
        ];
    }
}
```

### Frontend-Listener (Livewire + Echo)

```javascript
// resources/js/app.js
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

// In Livewire Component
Livewire.on('device-connected', (deviceId) => {
    window.Echo.private(`device.${deviceId}`)
        .listen('DeviceStatusUpdated', (e) => {
            Livewire.dispatch('device-status-updated', e);
        });
});
```

## Farb-Schema (Tailwind)

```css
/* Growdash Custom Colors */
:root {
    --growdash-water: #3B82F6;      /* Blue-500 */
    --growdash-tds: #10B981;        /* Green-500 */
    --growdash-temp: #F59E0B;       /* Amber-500 */
    --growdash-spray: #8B5CF6;      /* Violet-500 */
    --growdash-fill: #06B6D4;       /* Cyan-500 */
    --growdash-danger: #EF4444;     /* Red-500 */
    --growdash-success: #22C55E;    /* Green-500 */
}
```

## Nächste Schritte

1. ✅ Figma-Mockups erstellen
2. Livewire-Komponenten implementieren
3. ApexCharts integrieren
4. WebSocket-Events konfigurieren
5. Responsive-Design testen

---

**Design-Status**: 📋 Konzept erstellt  
**Nächster Schritt**: Figma-Prototyp
