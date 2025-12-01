# Growdash - Laravel 12 Integration

Laravel-basiertes Dashboard zur Verwaltung und Überwachung von Growdash-Geräten (Arduino-basierte Hydroponik-Systeme).

## Projektübersicht

Dieses Projekt integriert mehrere Growdash-Geräte in eine zentrale Laravel-Anwendung. Jedes Gerät sendet Sensordaten (Wasserstand, TDS, Temperatur) und Events (Sprüh- und Füllvorgänge) via Webhooks an Laravel. Die Anwendung:

-   **Empfängt und persistiert** Sensordaten und Logs von mehreren Geräten
-   **Parst Arduino-Log-Messages** automatisch und extrahiert strukturierte Daten
-   **Verwaltet System-Status** in Echtzeit (letzter Wasserstand, TDS, Temperatur, aktive Prozesse)
-   **Stellt APIs bereit** für Historien, Status-Abfragen und manuelle Steuerung
-   **Sichert Webhooks ab** via Token-basierter Authentifizierung

## Datenmodell (ER-Diagramm)

```mermaid
erDiagram
    DEVICES ||--o{ WATER_LEVELS : has
    DEVICES ||--o{ TDS_READINGS : has
    DEVICES ||--o{ TEMPERATURE_READINGS : has
    DEVICES ||--o{ SPRAY_EVENTS : has
    DEVICES ||--o{ FILL_EVENTS : has
    DEVICES ||--o{ SYSTEM_STATUSES : has
    DEVICES ||--o{ ARDUINO_LOGS : has

    DEVICES {
        int id PK
        string name
        string slug
        string ip_address
        string serial_port
        datetime created_at
        datetime updated_at
    }

    WATER_LEVELS {
        int id PK
        int device_id FK
        datetime measured_at
        float level_percent
        float liters
        datetime created_at
        datetime updated_at
    }

    TDS_READINGS {
        int id PK
        int device_id FK
        datetime measured_at
        float value_ppm
        datetime created_at
        datetime updated_at
    }

    TEMPERATURE_READINGS {
        int id PK
        int device_id FK
        datetime measured_at
        float value_c
        datetime created_at
        datetime updated_at
    }

    SPRAY_EVENTS {
        int id PK
        int device_id FK
        datetime start_time
        datetime end_time
        int duration_seconds
        boolean manual
        datetime created_at
        datetime updated_at
    }

    FILL_EVENTS {
        int id PK
        int device_id FK
        datetime start_time
        datetime end_time
        int duration_seconds
        float target_level
        float target_liters
        float actual_liters
        boolean manual
        datetime created_at
        datetime updated_at
    }

    SYSTEM_STATUSES {
        int id PK
        int device_id FK
        datetime measured_at
        float water_level
        float water_liters
        boolean spray_active
        boolean filling_active
        float last_tds
        float last_temperature
        datetime created_at
        datetime updated_at
    }

    ARDUINO_LOGS {
        int id PK
        int device_id FK
        datetime logged_at
        string level
        text message
        datetime created_at
        datetime updated_at
    }
```

### Konzept

-   **devices**: Zentrale Tabelle für alle Growdash-Geräte (1:n zu allen Messungen/Events)
-   **system_statuses**: Komprimierte "aktueller Status"-Tabelle für schnelle Abfragen
-   **Historien-Tabellen**: Alle Messungen und Events werden vollständig gespeichert
-   **arduino_logs**: Rohdaten aller Arduino-Nachrichten für Debugging

## Installation & Setup

### 1. Dependencies installieren

```bash
composer install
npm install
```

### 2. Environment konfigurieren

Kopiere `.env.example` zu `.env` und setze folgende Werte:

```env
# Growdash-Konfiguration
GROWDASH_DEVICE_SLUG=growdash-1
GROWDASH_WEBHOOK_TOKEN=super-secret-token-hier-einfügen
GROWDASH_PYTHON_BASE_URL=http://192.168.178.12:8000
```

### 3. Datenbank migrieren

```bash
php artisan migrate
```

### 4. Initial-Device erstellen (optional)

```bash
php artisan db:seed --class=DeviceSeeder
```

## API-Dokumentation

### Webhook-Endpunkte (erfordern `X-Growdash-Token` Header)

#### POST `/api/growdash/log`

Empfängt einzelne Log-Zeilen vom Arduino.

**Request:**

```json
{
    "device_slug": "growdash-1",
    "message": "WaterLevel: 75.3",
    "level": "info"
}
```

#### POST `/api/growdash/event`

Empfängt strukturierte Events (optional, falls Python bereits parst).

**Request:**

```json
{
    "device_slug": "growdash-1",
    "type": "water_level",
    "payload": {
        "level_percent": 75.3,
        "liters": 15.2,
        "measured_at": "2025-12-01T10:30:00Z"
    }
}
```

#### POST `/api/growdash/manual-spray`

Manuelles Aktivieren/Deaktivieren der Sprühfunktion.

**Request:**

```json
{
    "device_slug": "growdash-1",
    "action": "on"
}
```

#### POST `/api/growdash/manual-fill`

Manuelles Starten/Stoppen des Füllvorgangs.

**Request:**

```json
{
    "device_slug": "growdash-1",
    "action": "start",
    "target_level": 80.0,
    "target_liters": 20.0
}
```

### Öffentliche API-Endpunkte

#### GET `/api/growdash/status?device_slug=growdash-1`

Aktueller System-Status.

**Response:**

```json
{
    "water_level": 75.3,
    "water_liters": 15.2,
    "spray_active": false,
    "filling_active": true,
    "last_tds": 450.2,
    "last_temperature": 22.5,
    "timestamp": 1701424800
}
```

#### GET `/api/growdash/water-history?device_slug=growdash-1&limit=100`

Wasserstand-Historie.

#### GET `/api/growdash/tds-history?device_slug=growdash-1&limit=100`

TDS-Wert-Historie.

#### GET `/api/growdash/temperature-history?device_slug=growdash-1&limit=100`

Temperatur-Historie.

#### GET `/api/growdash/spray-events?device_slug=growdash-1&limit=50`

Sprüh-Events.

#### GET `/api/growdash/fill-events?device_slug=growdash-1&limit=50`

Füll-Events.

#### GET `/api/growdash/logs?device_slug=growdash-1&limit=200`

Arduino-Logs.

## Todo-Liste

### ✅ Phase 1: Basis-Infrastruktur

-   [x] README.md mit ER-Diagramm und Projektdokumentation
-   [x] .env.example mit Growdash-Variablen
-   [x] config/services.php Growdash-Konfiguration

### ✅ Phase 2: Sicherheit & Middleware

-   [x] VerifyGrowdashToken Middleware erstellen
-   [x] Middleware in bootstrap/app.php registrieren

### ✅ Phase 3: Datenbank

-   [x] Migration: devices
-   [x] Migration: water_levels
-   [x] Migration: tds_readings
-   [x] Migration: temperature_readings
-   [x] Migration: spray_events
-   [x] Migration: fill_events
-   [x] Migration: system_statuses
-   [x] Migration: arduino_logs
-   [x] Alle Migrations ausführen

### ✅ Phase 4: Models

-   [x] Model: Device (mit allen Relations)
-   [x] Model: WaterLevel
-   [x] Model: TdsReading
-   [x] Model: TemperatureReading
-   [x] Model: SprayEvent
-   [x] Model: FillEvent
-   [x] Model: SystemStatus
-   [x] Model: ArduinoLog

### ✅ Phase 5: Controller & Routen

-   [x] GrowdashWebhookController mit allen Methoden
-   [x] API-Routen in routes/api.php
-   [x] Routen testen (manuell oder via Tests)

### ✅ Phase 6: Seeders & Test-Daten

-   [x] DeviceSeeder für Initial-Devices
-   [ ] Optional: Test-Daten-Seeder für Entwicklung

### ✅ Phase 7: Tests

-   [x] Feature-Test: Webhook-Authentifizierung
-   [x] Feature-Test: Log-Parsing (WaterLevel, TDS, Temp)
-   [x] Feature-Test: Event-Handling
-   [x] Feature-Test: Status-API
-   [x] Feature-Test: History-APIs
-   [x] Feature-Test: Manual-Control (Spray/Fill)

### 📋 Phase 8: Authentifizierung & Autorisierung

-   [x] API-Endpunkte mit Auth-Middleware absichern
-   [x] Tests mit Authentifizierung aktualisiert
-   [ ] Policy für Device-Zugriff erstellen
-   [ ] Benutzer-Device-Zuordnung (optional)

### 📋 Phase 9: Frontend (Livewire + Flux)

-   [x] Design-Konzept erstellt (DESIGN.md)
-   [ ] Dashboard-View mit Device-Liste
-   [ ] Echtzeit-Status-Anzeige
-   [ ] Manuelle Steuerungs-Buttons (Spray/Fill)
-   [ ] Charts für Historien (Water, TDS, Temperature)
-   [ ] Event-Timeline
-   [ ] Log-Viewer mit Filtering

### 📋 Phase 10: WebSockets & Echtzeit

-   [x] WebSocket-Konzept dokumentiert (WEBSOCKETS.md)
-   [ ] Laravel Reverb installieren und konfigurieren
-   [ ] Broadcasting-Events erstellen (DeviceStatusUpdated, NewLogReceived)
-   [ ] Events in Controller integrieren
-   [ ] Frontend: WebSocket-Listener implementieren
-   [ ] Echtzeit-Chart-Updates

## Offene Entscheidungen

### ✅ Entscheidungen getroffen:

1. **System-Status-Strategie**: ✅ Ein Status pro Device (wird überschrieben) - optimal für schnelle Abfragen
2. **API-Authentifizierung**: ✅ Auth-Middleware wird für öffentliche Endpunkte hinzugefügt
3. **Frontend-Framework**: ✅ Livewire + Flux (bereits im Projekt vorhanden)
4. **Echtzeit-Updates**: ✅ WebSockets mit Laravel Reverb

## Technologie-Stack

-   **Backend**: Laravel 12, PHP 8.3+
-   **Database**: MySQL/PostgreSQL/SQLite (konfigurierbar)
-   **Frontend**: Livewire 3.x + Flux UI
-   **Echtzeit**: Laravel Reverb (WebSockets)
-   **Testing**: Pest PHP
-   **Python-Interface**: HTTP Webhooks + REST API

## Architektur-Prinzipien

1. **Multi-Device-Ready**: Alle Tabellen sind auf device_id normalisiert
2. **Event-Sourcing-Light**: Vollständige Historie aller Messungen
3. **Status-Caching**: system_statuses für schnelle Abfragen
4. **Webhook-Sicherheit**: Token-basierte Authentifizierung
5. **Parser-Flexibilität**: Unterstützt sowohl rohe Logs als auch strukturierte Events
6. **API-Sicherheit**: Authentifizierte Endpunkte mit Laravel Sanctum

---

**Projekt-Status**: 🚀 Phase 8 - Authentifizierung & Frontend in Arbeit  
**Letzte Aktualisierung**: 2025-12-01
