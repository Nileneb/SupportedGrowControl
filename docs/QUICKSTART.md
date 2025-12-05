# GrowDash - Schnellstart-Anleitung

## 🚀 Komplettes Setup (Backend + Frontend + Agent)

Diese Anleitung führt dich durch die vollständige Einrichtung von GrowDash mit allen Features:

-   ✅ Laravel Backend mit Device-Management
-   ✅ Frontend mit Echtzeit-WebSocket-Updates
-   ✅ Multi-USB-Device Agent

---

## Voraussetzungen

### System

-   **PHP** >= 8.2
-   **Composer** >= 2.0
-   **Node.js** >= 18.x + npm
-   **Python** >= 3.8 (für Agent)
-   **SQLite** (bereits in PHP enthalten)

### Optionale Tools

-   **Laravel Herd** (für lokale Entwicklung, empfohlen)
-   **Git** (zum Klonen des Repos)

---

## 1. Backend Setup (Laravel)

### Repository klonen

```bash
git clone https://github.com/Nileneb/growdash.git
cd growdash
```

### Dependencies installieren

```bash
# PHP Dependencies
composer install

# Frontend Dependencies
npm install
```

### Environment konfigurieren

```bash
# .env aus Vorlage kopieren
cp .env.example .env

# App Key generieren
php artisan key:generate
```

**Wichtige .env Einstellungen:**

```dotenv
APP_NAME=GrowDash
APP_URL=http://growdash.test

# SQLite Database (Standard)
DB_CONNECTION=sqlite

# Broadcasting (Reverb für WebSockets)
BROADCAST_CONNECTION=reverb

# Reverb WebSocket Server
REVERB_APP_ID=683260
REVERB_APP_KEY=zkzj14faofpwi4hhad9w
REVERB_APP_SECRET=kw7lnemcht7nnoxcntta
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite (Frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Datenbank Setup

```bash
# SQLite DB-Datei erstellen
touch database/database.sqlite

# Migrations ausführen
php artisan migrate

# Optional: Seed mit Test-Daten
php artisan db:seed
```

### Storage Link erstellen

```bash
php artisan storage:link
```

---

## 2. Frontend Build

### Development Mode (mit Hot Reload)

```bash
npm run dev
```

**Lässt Terminal offen! Vite kompiliert bei Datei-Änderungen automatisch neu.**

### Production Build

```bash
npm run build
```

---

## 3. WebSocket Server starten (Reverb)

**In neuem Terminal-Tab:**

```bash
php artisan reverb:start
```

**Ausgabe:**

```
Starting Reverb server on 0.0.0.0:8080...
  ✓ Server started successfully
```

**Wichtig:** Reverb muss laufen, damit WebSocket-Events funktionieren!

---

## 4. Laravel Development Server starten

**Option A: Laravel Herd (empfohlen)**

```bash
# Herd verlinkt automatisch http://growdash.test
herd link
```

**Option B: Artisan Serve**

```bash
php artisan serve
```

**Zugriff:** http://localhost:8000

---

## 5. Agent Setup (Python)

### Requirements installieren

```bash
cd agent
pip install -r requirements.txt
```

**requirements.txt:**

```txt
pyserial>=3.5
requests>=2.31.0
```

### Agent starten

```bash
# Standard (Backend = http://growdash.test)
python main.py

# Custom Backend URL
python main.py --backend-url http://localhost:8000

# Custom Scan-Intervall
python main.py --scan-interval 10
```

**Ausgabe:**

```
============================================================
  GrowDash Agent - USB Multi-Device Support
============================================================
  Backend URL: http://growdash.test
  Scan Interval: 5s
============================================================

🔍 USB Device Scanner gestartet (Scan-Intervall: 5s)
✓ Neues Device gefunden: COM3
✓ [COM3] Serial connected
✓ [COM3] Device ID: GROW-ARDUINO-001
✓ [COM3] Authenticated with backend
✓ [COM3] Capabilities registered
✓ [COM3] Device handler running
```

---

## 6. Erste Schritte im Frontend

### 1. Account erstellen

Öffne http://growdash.test/register

-   Name, E-Mail, Passwort eingeben
-   Registrieren

### 2. Device verbinden

**Option A: Device ist bereits gepaired (via Agent)**

-   Agent hat Device automatisch registriert
-   Gehe zu Dashboard: http://growdash.test/dashboard
-   Device erscheint in der Liste

**Option B: Manuelles Pairing**

-   Gehe zu http://growdash.test/devices/pair
-   Bootstrap-ID eingeben (z.B. `GROW-ARDUINO-001`)
-   Device wird dem User zugeordnet

### 3. Device-Details anzeigen

-   Klicke auf Device-Karte im Dashboard
-   Device-Detailseite zeigt:
    -   Sensors (mit Live-Daten)
    -   Actuators (mit Steuerungs-UI)
    -   Serial Console (für direkte Commands)
    -   Command History
    -   Device Logs

### 4. Sensor/Actuator hinzufügen (UI)

**Via Livewire-Komponenten:**

-   Öffne Device-Detailseite
-   Sensors/Actuators-Sektion
-   "Add Sensor" / "Add Actuator" Button (falls vorhanden)
-   Formular ausfüllen, speichern

**Via Agent (automatisch):**

-   Agent sendet Capabilities an Backend
-   Backend erstellt DeviceSensor/DeviceActuator automatisch
-   `DeviceCapabilitiesUpdated` Event → Frontend reload

### 5. Commands senden

**Serial Console:**

```
STATUS
GET_TELEMETRY
EXECUTE spray_pump {"duration_ms": 1000}
```

**Actuator Widget:**

-   Duration/Value einstellen
-   Button klicken
-   WebSocket Event zeigt Status in Echtzeit

---

## 7. Überprüfung der Installation

### Backend Health Check

```bash
php artisan route:list | grep growdash
```

**Erwartete Routes:**

```
POST   /api/growdash/agent/bootstrap
POST   /api/growdash/agent/heartbeat
POST   /api/growdash/agent/capabilities
GET    /api/growdash/agent/commands/pending
POST   /api/growdash/agent/commands/{id}/result
POST   /api/growdash/devices/{device}/commands
GET    /api/growdash/devices/{device}/commands
```

### Frontend Check

Öffne Browser-Console auf Device-Seite:

```javascript
console.log(window.Echo); // Sollte Echo-Objekt zeigen
console.log(window.wsConnected); // Sollte true sein wenn Reverb läuft
```

### WebSocket Check

In Browser DevTools → Network → WS:

-   Connection zu `ws://localhost:8080/app/...` sollte offen sein
-   Messages sollten bei Events erscheinen

### Agent Check

```bash
# Terminal mit laufendem Agent
# Sollte Heartbeats senden alle 30s
✓ [COM3] Heartbeat sent
```

---

## 8. Testing

### Feature Tests ausführen

```bash
php artisan test
```

**Relevante Tests:**

-   `tests/Feature/DashboardTest.php`
-   `tests/Feature/Auth/`
-   `tests/Feature/Settings/`

### Manual Testing Scenarios

#### Scenario 1: Device Connect & Capabilities

1. Starte Agent mit Mock-Device
2. Agent sendet Bootstrap
3. Backend registriert Device
4. Agent sendet Capabilities
5. Backend feuert `DeviceCapabilitiesUpdated`
6. Frontend reload zeigt neue Sensors/Actuators

#### Scenario 2: Command Execution

1. User sendet Command via Serial Console
2. Command wird in DB gespeichert (status: pending)
3. Agent pollt Commands
4. Agent führt Command aus
5. Agent sendet Result an Backend
6. Backend feuert `CommandStatusUpdated`
7. Frontend zeigt Status in Echtzeit

#### Scenario 3: Multi-Device

1. Schließe 2 USB-Devices an
2. Agent erkennt beide Ports
3. Beide Devices werden parallel verwaltet
4. Jedes Device hat eigenen Handler-Thread

---

## 9. Production Deployment

### .env für Production

```dotenv
APP_ENV=production
APP_DEBUG=false

# HTTPS (Reverb)
REVERB_SCHEME=https
REVERB_PORT=443

# Database (MySQL statt SQLite)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=growdash
DB_USERNAME=root
DB_PASSWORD=secret
```

### Build & Optimize

```bash
# Frontend Build
npm run build

# Cache Config/Routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize Autoloader
composer install --optimize-autoloader --no-dev
```

### Supervisor (für Reverb + Queue)

**supervisor.conf:**

```ini
[program:reverb]
command=php /var/www/growdash/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log

[program:queue]
command=php /var/www/growdash/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/queue.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb queue
```

### Agent als Systemd Service

**growdash-agent.service:**

```ini
[Unit]
Description=GrowDash USB Agent
After=network.target

[Service]
Type=simple
User=growdash
WorkingDirectory=/home/growdash/agent
ExecStart=/usr/bin/python3 /home/growdash/agent/main.py --backend-url https://growdash.example.com
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable growdash-agent
sudo systemctl start growdash-agent
sudo systemctl status growdash-agent
```

---

## 10. Troubleshooting

### Problem: WebSocket verbindet nicht

**Lösung 1: Reverb läuft?**

```bash
php artisan reverb:start
```

**Lösung 2: Port 8080 offen?**

```bash
netstat -an | findstr 8080  # Windows
netstat -tuln | grep 8080   # Linux
```

**Lösung 3: Broadcasting aktiviert?**

```dotenv
BROADCAST_CONNECTION=reverb  # NICHT 'log'!
```

### Problem: Agent kann Device nicht finden

**Lösung 1: Serial Port korrekt?**

```python
import serial.tools.list_ports
print([p.device for p in serial.tools.list_ports.comports()])
```

**Lösung 2: Permissions (Linux)?**

```bash
sudo usermod -a -G dialout $USER
# Logout/Login erforderlich
```

### Problem: Capabilities nicht synchronisiert

**Lösung: Manuell triggern**

```php
php artisan tinker

$device = App\Models\Device::find(1);
$device->syncCapabilitiesFromInstances();
```

### Problem: Commands bleiben in "pending"

**Check 1: Agent pollt?**

```bash
# Agent sollte alle 2s pollen
✓ [COM3] Polling commands...
```

**Check 2: Device online?**

```sql
SELECT id, name, status, last_seen_at FROM devices;
```

**Lösung: Heartbeat senden**

```bash
curl -X POST http://growdash.test/api/growdash/agent/heartbeat \
  -H "X-Device-ID: GROW-ARDUINO-001" \
  -H "X-Device-Token: xxx"
```

---

## 11. Nützliche Commands

### Development

```bash
# Logs anzeigen (Laravel)
tail -f storage/logs/laravel.log

# Queue-Jobs anzeigen
php artisan queue:work --verbose

# Cache leeren (bei Problemen)
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reverb Connections anzeigen
php artisan reverb:connections
```

### Database

```bash
# Tinker (REPL)
php artisan tinker

# Migration zurücksetzen + neu
php artisan migrate:fresh --seed

# Alle Devices listen
php artisan tinker
>>> App\Models\Device::all(['id', 'name', 'status', 'last_seen_at']);
```

### Agent

```bash
# Mock Serial Device (ohne Hardware)
# test_device.py im agent/ Ordner

# Agent mit verbose Logging
python main.py --scan-interval 5 --verbose
```

---

## 12. Weitere Dokumentation

-   **Agent Multi-Device:** `AGENT_USB_MULTIDEVICE.md`
-   **Frontend WebSocket:** `FRONTEND_WEBSOCKET.md`
-   **API Reference:** `AGENT_API.md`
-   **Capabilities:** `CAPABILITIES_QUICKREF.md`
-   **WebSocket Events:** `WEBSOCKETS.md`
-   **Backend Update:** `LARAVEL_AGENT_UPDATE.md`

---

## Zusammenfassung: Was läuft wo?

```
┌─────────────────────────────────────────────────────────┐
│ Terminal 1: Vite Dev Server                             │
│ $ npm run dev                                           │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Terminal 2: Laravel Reverb (WebSocket)                  │
│ $ php artisan reverb:start                              │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Terminal 3: Laravel Development Server (optional)       │
│ $ php artisan serve                                     │
│ ODER: Laravel Herd (im Hintergrund)                     │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Terminal 4: Python Agent (USB Device Management)        │
│ $ python agent/main.py                                  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Browser: http://growdash.test                           │
│ - Dashboard                                             │
│ - Device Details (mit WebSocket)                        │
│ - Settings                                              │
└─────────────────────────────────────────────────────────┘
```

---

## Support & Feedback

Bei Problemen oder Fragen:

1. Logs prüfen: `storage/logs/laravel.log`
2. Browser Console öffnen (F12)
3. Agent Output prüfen
4. GitHub Issues: https://github.com/Nileneb/growdash/issues

**Viel Erfolg mit GrowDash! 🌱🚀**
