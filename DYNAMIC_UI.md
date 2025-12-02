# Dynamic UI Implementation

## Overview

The new dynamic UI system renders device interfaces based on their **actual capabilities** rather than showing all components for every device. This makes the interface scalable for multiple microcontrollers with different sensor/actuator configurations.

## Architecture

### Main View Structure

```
resources/views/devices/
├── show-v2.blade.php          # Main view with sidebar navigation
└── sections/                   # Modular content sections
    ├── terminal.blade.php      # Serial console (always visible)
    ├── sensors.blade.php       # Sensor readings (conditional)
    ├── actuators.blade.php     # Actuator controls (conditional)
    └── info.blade.php          # Device information (always visible)
```

### Sidebar Navigation

The left sidebar provides quick navigation between device sections:
- **Terminal** 💻 - Always visible (serial console + command input)
- **Sensors** 📊 - Only if `capabilities['sensors']` exists and not empty
- **Actuators** ⚙️ - Only if `capabilities['actuators']` exists and not empty
- **Device Info** ℹ️ - Always visible (device details, capabilities summary)

### Section Visibility Logic

**Always Visible:**
- Terminal/Serial Console
- Device Information

**Conditionally Rendered:**
- Sensors section: `@if(!empty($sensors))`
- Actuators section: `@if(!empty($actuators))`

## Controller Implementation

### DeviceViewController.php

```php
public function show(Request $request, string $deviceId)
{
    $device = Device::where('public_id', $deviceId)
        ->where('user_id', $request->user()->id)
        ->firstOrFail();
    
    // Parse capabilities JSON
    $capabilities = $device->capabilities ?? [];
    $sensors = $capabilities['sensors'] ?? [];
    $actuators = $capabilities['actuators'] ?? [];
    
    // Get latest sensor readings
    $sensorReadings = [];
    foreach ($sensors as $sensor) {
        $sensorId = $sensor['id'];
        $latestReading = $device->telemetryReadings()
            ->where('sensor_key', $sensorId)
            ->latest()
            ->first();
        
        if ($latestReading) {
            $sensorReadings[$sensorId] = [
                'value' => $latestReading->value,
                'timestamp' => $latestReading->created_at
            ];
        }
    }
    
    // Use v2 view (modular)
    return view('devices.show-v2', compact('device', 'sensors', 'actuators', 'sensorReadings'));
}
```

### View Selection

- **Default**: `show-v2.blade.php` (new modular view)
- **Legacy**: `show.blade.php` (accessible via `?view=v1` query parameter)

Example:
- `/devices/{deviceId}` → New modular UI
- `/devices/{deviceId}?view=v1` → Old monolithic UI

## Section Details

### 1. Terminal Section (`terminal.blade.php`)

**Features:**
- ✅ Real-time serial output console
- ✅ Command input with history
- ✅ WebSocket status indicator
- ✅ Command status tracking (pending → executing → success/failed)
- ✅ Auto-scroll with 500-line limit

**WebSocket Events:**
- `DeviceTelemetryReceived` - Updates serial console with device output
- `CommandStatusUpdated` - Updates command history with status changes

**Usage:**
```blade
@include('devices.sections.terminal', ['device' => $device])
```

### 2. Sensors Section (`sensors.blade.php`)

**Features:**
- ✅ Dynamic grid layout (1-3 columns based on screen size)
- ✅ Real-time value updates via WebSocket
- ✅ Animated value changes (flashes blue on update)
- ✅ Status indicators (green dot when active)
- ✅ Unit display (°C, ppm, %, etc.)
- ✅ Min/max range display (if configured)
- ✅ Last update timestamp

**Capability Structure:**
```json
{
  "id": "water_level",
  "display_name": "Water Level",
  "category": "environmental",
  "unit": "%",
  "value_type": "float",
  "min_value": 0,
  "max_value": 100
}
```

**WebSocket Events:**
- `DeviceTelemetryReceived` - Updates sensor values in real-time

**Usage:**
```blade
@include('devices.sections.sensors', [
    'device' => $device,
    'sensors' => $sensors,
    'sensorReadings' => $sensorReadings
])
```

### 3. Actuators Section (`actuators.blade.php`)

**Features:**
- ✅ Dynamic controls based on `command_type`
- ✅ Duration-based actuators (e.g., spray pump, fill valve)
- ✅ Toggle-based actuators (e.g., lights, fans)
- ✅ Real-time status updates (queued → executing → success/failed)
- ✅ Visual feedback (colored status dots)
- ✅ Button disable during execution
- ✅ Configurable ranges (min/max duration/amount)

**Command Types:**

**Duration-based** (`command_type: "duration"`):
```json
{
  "id": "spray_pump",
  "display_name": "Spray Pump",
  "command_type": "duration",
  "duration_unit": "ms",
  "duration_label": "Duration",
  "min_duration": 100,
  "max_duration": 30000,
  "default_duration": 1000,
  "duration_help": "Spray time in milliseconds"
}
```

**Toggle-based** (`command_type: "toggle"`):
```json
{
  "id": "grow_light",
  "display_name": "Grow Light",
  "command_type": "toggle"
}
```

**WebSocket Events:**
- `CommandStatusUpdated` - Updates actuator status in real-time

**Usage:**
```blade
@include('devices.sections.actuators', [
    'device' => $device,
    'actuators' => $actuators
])
```

### 4. Device Info Section (`info.blade.php`)

**Features:**
- ✅ Basic device information (name, ID, status, last seen)
- ✅ Board information (type, vendor, MCU, architecture)
- ✅ Capabilities summary (sensor/actuator/pin counts)
- ✅ Detailed sensor list with categories and units
- ✅ Detailed actuator list with command types
- ✅ Timestamps (created, updated)
- ✅ Action buttons (edit device, refresh capabilities)

**Usage:**
```blade
@include('devices.sections.info', ['device' => $device])
```

## Navigation System

### JavaScript Implementation

```javascript
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Remove active state from all nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-700');
    });
    
    // Show selected section
    document.getElementById(`section-${sectionName}`).classList.remove('hidden');
    
    // Highlight active nav item
    document.querySelector(`[data-section="${sectionName}"]`)
        .classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-700');
    
    // Store preference in localStorage
    localStorage.setItem('device-view-section', sectionName);
}
```

### State Persistence

The last viewed section is stored in `localStorage` and restored on page load:

```javascript
document.addEventListener('DOMContentLoaded', () => {
    const lastSection = localStorage.getItem('device-view-section') || 'terminal';
    showSection(lastSection);
});
```

## Scalability Features

### Multi-Device Support

Each device gets a customized interface based on its capabilities:

**Device A** (Full hydroponics setup):
- 3 sensors (water level, TDS, temperature)
- 2 actuators (spray pump, fill valve)
- Shows: Terminal, Sensors, Actuators, Info

**Device B** (Simple monitoring):
- 1 sensor (temperature)
- 0 actuators
- Shows: Terminal, Sensors, Info (no Actuators section)

**Device C** (Not configured):
- 0 sensors
- 0 actuators
- Shows: Terminal, Info only

### Adding New Capabilities

To add a new sensor/actuator type:

1. **Update device capabilities JSON:**
```json
{
  "sensors": [
    {
      "id": "ph_sensor",
      "display_name": "pH Level",
      "category": "water_chemistry",
      "unit": "pH",
      "value_type": "float",
      "min_value": 0,
      "max_value": 14
    }
  ]
}
```

2. **No view changes needed** - the dynamic UI automatically renders new sensors/actuators based on capabilities structure.

3. **Update Arduino agent** (if new serial commands):
```python
# Add command mapping in CommandController
def mapActuatorToArduinoCommand($type, $params):
    if type == 'ph_adjuster':
        return f"AdjustPH {params['target_ph']}"
```

## WebSocket Integration

All sections use the same WebSocket channel pattern:

```javascript
if (window.Echo) {
    window.Echo.private(`device.${deviceId}`)
        .listen('DeviceTelemetryReceived', (event) => {
            // Update sensors, terminal, etc.
        })
        .listen('CommandStatusUpdated', (event) => {
            // Update actuator status, command history
        });
}
```

### Global WebSocket Events

Custom events dispatched in `app.js`:

- `ws-initializing` - Echo is connecting
- `ws-connected` - WebSocket connection established
- `ws-disconnected` - Connection lost
- `ws-error` - Connection error

Sections listen for these events to update status indicators.

## Styling

### Dark Mode Support

All sections support dark mode via Tailwind's `dark:` variant:

```html
<div class="bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100">
```

### Responsive Design

- **Mobile**: Single column, stacked sections
- **Tablet**: 2-column grid for sensors/actuators
- **Desktop**: 3-column grid, fixed sidebar

### Status Colors

**Sensors:**
- Active: `bg-green-500` (green dot)
- Updating: `bg-green-500 animate-pulse`

**Actuators:**
- Idle: `bg-neutral-400` (gray)
- Pending: `bg-blue-500 animate-pulse` (blue)
- Executing: `bg-yellow-500 animate-pulse` (yellow)
- Success: `bg-green-500` (green)
- Failed: `bg-red-500` (red)

**WebSocket:**
- Connecting: `⏳ Connecting...`
- Connected: `✓ Connected` (green)
- Disconnected: `✗ Disconnected` (red)
- Error: `✗ Error` (red)

## Testing

### Test Different Device Configurations

1. **Full capabilities** (u-server, GrowPi):
   - Navigate to `/devices/{deviceId}`
   - Verify all 4 sections visible in sidebar
   - Test sensor updates, actuator controls

2. **No capabilities** (Test Device):
   - Navigate to `/devices/{deviceId}`
   - Verify only Terminal and Info visible
   - Verify sensors/actuators sections not rendered

3. **Legacy view**:
   - Navigate to `/devices/{deviceId}?view=v1`
   - Verify old monolithic view still works

### WebSocket Testing

```bash
# In browser console:
window.Echo.connector.pusher.connection.state
# Should show: "connected"

# Trigger sensor update
# Should see value flash blue and update

# Send actuator command
# Should see status: gray → blue → yellow → green
```

## Migration Path

### From Old View to New View

**Old (show.blade.php):**
- Monolithic template
- Hardcoded sensor/actuator sections
- All components always visible

**New (show-v2.blade.php):**
- Modular partials
- Dynamic rendering based on capabilities
- Sidebar navigation
- State persistence

**Gradual Migration:**
1. ✅ Create new view files (show-v2, sections/)
2. ✅ Update controller to support both views
3. ⏳ Test with production devices
4. ⏳ Update default view in routes
5. ⏳ Remove old view once confirmed stable

## Future Enhancements

### Planned Features

- [ ] **Multi-device dashboard**: Side-by-side comparison
- [ ] **Drag-and-drop layout**: Reorder sections
- [ ] **Custom widgets**: User-defined sensor combinations
- [ ] **Historical charts**: Time-series data visualization
- [ ] **Alerts & notifications**: Threshold-based alerts
- [ ] **Export data**: CSV/JSON export for sensors
- [ ] **Device groups**: Organize devices by location/type

### Extension Points

**Custom Sections:**
```blade
<!-- Add new section -->
<div id="section-charts" class="content-section hidden">
    @include('devices.sections.charts', ['device' => $device])
</div>
```

**Custom Capabilities:**
```json
{
  "custom_features": {
    "camera": {
      "enabled": true,
      "stream_url": "rtsp://..."
    }
  }
}
```

## Troubleshooting

### Section Not Showing

**Problem**: Sensors section visible but empty

**Solution**: Check capabilities JSON structure:
```bash
docker compose exec php-fpm php artisan tinker
> Device::find(1)->capabilities
```

Ensure `sensors` array exists and has items.

### WebSocket Updates Not Working

**Problem**: Values not updating in real-time

**Solution**: Check WebSocket connection:
1. Open browser console
2. Look for `✓ WebSocket connected` message
3. Check network tab for `/app` WebSocket connection
4. Verify Reverb server is running:
```bash
docker compose ps reverb
```

### View Cache Issues

**Problem**: Changes to blade files not reflecting

**Solution**: Clear view cache:
```bash
docker compose exec php-fpm php artisan view:clear
docker compose exec php-fpm php artisan config:clear
```

### Navigation Not Working

**Problem**: Clicking sidebar items doesn't switch sections

**Solution**: Check JavaScript console for errors. Ensure `showSection()` function is defined in main view.

## References

- **Capabilities Documentation**: `CAPABILITIES_IMPLEMENTATION.md`
- **WebSocket Setup**: `WEBSOCKET_FIX_VERIFICATION.md`
- **Arduino Commands**: `ARDUINO_CONTROL.md`
- **Agent Integration**: `AGENT_API.md`
