# Device Workstation View - Best Practices & Features

## 🎯 Overview

Die neue **Device Workstation** ist eine flexible, persistent konfigurierbare Ansicht für die Arbeit mit IoT-Geräten. Sie wurde mit Best Practices für UX, Performance und Usability entwickelt.

## ✨ Hauptmerkmale

### 1. **Flexible Layout-Konfiguration**
- **Sidebar Navigation**: Schnelle Kontrolle über sichtbare Sections
- **Workspace Grid**: Modernes CSS Grid mit automatischem Reflow
- **Drag-and-Drop Ready**: Struktur für zukünftige Drag-Funktionalität
- **Responsive Design**: Passt sich automatisch an Bildschirmgröße an

### 2. **Persistent Preferences**
```javascript
// Automatisch gespeichert in localStorage
{
  "workspace-{deviceId}": {
    "visibleSections": ["terminal", "sensors", "actuators"],
    "sectionOrder": [],
    "gridLayout": {},
    "lastUpdated": "2024-12-06T10:30:00.000Z"
  },
  "minimize-{deviceId}": ["logs", "info"]
}
```

### 3. **Workspace-Management**
- ✅ **Toggle Sections**: Checkboxes im Sidebar zum Ein-/Ausblenden
- ✅ **Minimize**: Sections auf Header-Höhe zusammenfalten
- ✅ **Close**: Sections ausblenden (können später wieder geöffnet werden)
- ✅ **Reset Layout**: Zurück zur Standard-Konfiguration
- ✅ **Export Config**: Speichern der Konfiguration als JSON-Datei

### 4. **On-Demand Loading**
```
Initial Page Load:
├── Device Header ✓ (schnell)
├── Sidebar ✓ (schnell)
└── Terminal Section (eingeladen)
    └── Sensors/Actuators/Logs (lazy-loaded nur wenn geöffnet)

WebSocket Connection:
├── Device Telemetry
├── Command Status Updates
└── Real-time Capabilities Updates
```

### 5. **Quick Actions**
- 🔄 **Refresh**: Aktualisiert Device-Daten (sendet refresh-Event)
- ⚡ **Reconnect**: Re-establishes WebSocket connection

## 📐 Architektur

### WorkspaceManager Klasse
```javascript
class WorkspaceManager {
  loadState()          // Lädt gespeicherte Konfiguration
  saveState()          // Speichert aktuelle Konfiguration
  toggleSection()      // Zeigt/verbirgt Section
  toggleMinimize()     // Minimiert/restellt Section
  export()             // Exportiert Config als JSON
  reset()              // Setzt auf Standard zurück
}
```

### Event System
```javascript
// Global Events (verwendbar in allen Sections)
window.addEventListener('device-telemetry', (e) => {
  // Neue Sensor-/Actuator-Daten
  updateSensorReadings(e.detail.telemetry);
});

window.addEventListener('command-status', (e) => {
  // Command wurde aktualisiert
  updateCommandStatus(e.detail);
});

window.addEventListener('device-refresh', (e) => {
  // Benutzer klickte "Refresh"
  reloadDeviceData();
});

window.addEventListener('ws-connected', (e) => {
  // WebSocket verbunden
  showConnectionStatus('Connected');
});
```

## 🎨 Layout-Strategie

### CSS Grid System
```css
.workspace-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1rem;
  grid-auto-rows: 500px;
  grid-auto-flow: dense;  /* Füllt Lücken intelligente auf */
}

/* Sections können mit .tall (2x hoch) oder .wide (2x breit) erweitert werden */
.workspace-item.tall { grid-row: span 2; }
.workspace-item.wide { grid-column: span 2; }
```

### Responsive Breakpoints
```css
/* Desktop (default) */
@media (max-width: 1024px) {
  /* Tablet: Single-column layout */
  grid-template-columns: 1fr;
}

/* Sidebar */
.sidebar-container: w-64 (auf 1024px+ voll sichtbar)
```

## 🔌 Integration mit Bestehenden Sections

Alle Sections verwenden das neue Event-System:

### Terminal (`terminal.blade.php`)
```javascript
window.addEventListener('device-refresh', () => {
  clearSerialConsole();
  reloadTerminalState();
});
```

### Sensors (`sensors.blade.php`)
```javascript
window.addEventListener('device-telemetry', (e) => {
  updateSensorReadings(e.detail.telemetry);
});
```

### Actuators (`actuators.blade.php`)
```javascript
window.addEventListener('command-status', (e) => {
  updateActuatorStatus(e.detail);
});
```

## 📡 API-Endpoints

### Device Logs (NEW)
```
GET  /api/devices/{device}/logs
     Query: limit=50, type=error, level=ERROR, search="pattern"
     Response: { logs, device }

GET  /api/devices/{device}/logs/stats
     Response: { INFO: 150, ERROR: 5, DEBUG: 0 }

DELETE /api/devices/{device}/logs
       Response: { message, deleted }

GET  /api/devices/{device}/logs/export
     Query: format=json|csv, limit=1000
     Response: JSON array oder CSV file
```

## 💾 Persistent State Beispiele

### Beispiel 1: User öffnet Device-Page
```javascript
// localStorage.getItem('workspace-123')
{
  "visibleSections": ["terminal", "sensors"],
  "minimized": ["logs"],
  "lastUpdated": "2024-12-06T09:00:00Z"
}

// → Terminal + Sensors werden angezeigt
// → Logs-Section existiert aber ist minimiert
```

### Beispiel 2: User exportiert Config
```javascript
// Download: device-123-workspace-2024-12-06.json
{
  "workspace": {
    "visibleSections": ["terminal", "sensors", "actuators"],
    "sectionOrder": [],
    "gridLayout": {},
    "lastUpdated": "2024-12-06T10:30:00.000Z"
  },
  "minimized": ["logs"],
  "exportedAt": "2024-12-06T10:31:45.000Z"
}

// Kann später importiert werden (feature für future)
```

## 🚀 Performance-Optimierungen

### 1. **Lazy Loading**
```javascript
// Logs werden nur geladen wenn Section geöffnet
const logsSection = document.getElementById('section-logs');
logsSection?.addEventListener('click', () => {
  if (!logsSection.classList.contains('hidden')) {
    loadDeviceLogs();  // Nur dann API-Request
  }
});
```

### 2. **Minimized Sections**
```javascript
// Minimierte Sections: Nur Header sichtbar (sehr schnell)
// Body wird mit `display: none` versteckt (keine Layout recalculation)
item.classList.add('h-12');
body.classList.add('hidden');
```

### 3. **Event Debouncing**
```javascript
// In zukünftigen Updates: resize/reorder events debounced
const debouncedSave = debounce(() => workspace.saveState(), 300);
```

### 4. **Initial Page Load**
```
Time to Interactive:
- Device Header: < 50ms (Server-Rendered)
- Sidebar: < 50ms
- Terminal: < 100ms (erste Render)
- WebSocket Connection: ~200-500ms (parallel)
- Sensor Readings: Nach WS Connect (Real-time)
- Logs: On-Demand nur wenn geöffnet
```

## 🛠️ Entwickler-Guide

### Neue Section hinzufügen

1. **Create Blade Template**: `resources/views/devices/sections/my-section.blade.php`

2. **Add HTML zu show-workstation.blade.php**:
```blade
<div id="section-my-section" class="workspace-item hidden" data-section="my-section">
    <div class="workspace-header dark:bg-neutral-700">
        <span class="text-sm font-medium">🆕 My Section</span>
        <div class="flex gap-1">
            <button class="workspace-action-btn section-minimize-btn">-</button>
            <button class="workspace-action-btn section-close-btn">×</button>
        </div>
    </div>
    <div class="workspace-body">
        @include('devices.sections.my-section')
    </div>
</div>
```

3. **Add Toggle zu Sidebar**:
```blade
<label class="flex items-center gap-2 cursor-pointer p-2 rounded">
    <input type="checkbox" data-section-toggle="my-section" class="section-toggle">
    <span class="text-sm">🆕 My Section</span>
</label>
```

4. **Listen to Events**:
```javascript
window.addEventListener('device-telemetry', (e) => {
    // Update your section with new data
});
```

## 🎓 Best Practices

### ✅ DO
- ✓ Verwende globale Events statt direkter DOM-Manipulation
- ✓ Speichere State am Ende von Operationen
- ✓ Nutze Lazy-Loading für schwere Operationen
- ✓ Minimiere Initial Page Load (nur Terminal sichtbar)
- ✓ Teste auf verschiedenen Bildschirmgrößen

### ❌ DON'T
- ✗ Direkter DOM-Zugriff auf andere Sections
- ✗ Synchrone Operationen in Event-Listenern
- ✗ localStorage.setItem() bei jedem Keystroke
- ✗ Große Datenmengen ohne Pagination
- ✗ Hardcode von Section-Namen (verwende data-attributes)

## 📊 Messbar

### Tracking eingebaut:
```javascript
Log::info('🎯 ENDPOINT_TRACKED: DeviceViewController@show', [
    'user_id' => $request->user()->id,
    'device_id' => $device->id,
    'view' => 'devices.show-workstation',
]);

// API Logs werden auch getracked:
Log::info('🎯 ENDPOINT_TRACKED: DeviceLogsController@index', [
    'user_id' => $request->user()->id,
    'device_id' => $device->id,
    'log_count' => 50,
]);
```

## 🔮 Future Enhancements

1. **Drag-and-Drop**: Sections reordern mit Maus
2. **Config Import**: Workspace-Konfiguration von JSON-Datei laden
3. **Presets**: Vordefinierte Layouts (z.B. "Diagnostics", "Monitoring", "Control")
4. **Split-View**: Terminal + Sensors nebeneinander
5. **Auto-Refresh**: Automatische Aktualisierung bei Device-Offline
6. **Tabs**: Multiple Workspaces pro Device speichern

---

**Created**: 2024-12-06  
**Version**: 1.0 - Initial Release  
**Status**: Production Ready ✓
