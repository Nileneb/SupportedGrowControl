# Arduino CLI Installation & Setup Guide

## Installation

### Windows
```powershell
# Via Chocolatey
choco install arduino-cli

# Oder manuell von GitHub
# Download von: https://github.com/arduino/arduino-cli/releases
# Extrahiere und füge zum PATH hinzu
```

### Linux
```bash
curl -fsSL https://raw.githubusercontent.com/arduino/arduino-cli/master/install.sh | sh
```

### macOS
```bash
brew install arduino-cli
```

## Erste Schritte

### 1. Arduino CLI initialisieren
```bash
arduino-cli config init
```

### 2. ESP32 Board Support installieren
```bash
# Board Index URLs aktualisieren
arduino-cli core update-index --additional-urls https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json

# ESP32 Core installieren
arduino-cli core install esp32:esp32 --additional-urls https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
```

### 3. Andere Boards installieren (optional)
```bash
# Arduino AVR (Uno, Nano, Mega)
arduino-cli core install arduino:avr

# Arduino SAMD (MKR, Zero)
arduino-cli core install arduino:samd
```

### 4. Bibliotheken installieren (falls benötigt)
```bash
arduino-cli lib install "DHT sensor library"
arduino-cli lib install "Adafruit Unified Sensor"
arduino-cli lib install "PubSubClient"
```

## Konfiguration in GrowDash

### .env Datei
Füge diese Zeile zu deiner `.env` hinzu:

```env
# Arduino CLI Pfad (optional, wenn im PATH)
ARDUINO_CLI_PATH=arduino-cli

# Oder absoluter Pfad:
# ARDUINO_CLI_PATH=C:\ProgramData\chocolatey\bin\arduino-cli.exe
# ARDUINO_CLI_PATH=/usr/local/bin/arduino-cli
```

## Verwendung

### 1. Script erstellen
- Gehe zu `/admin/scripts`
- Klicke "Script hinzufügen"
- Füge deinen C++ Code ein

### 2. Kompilieren
- Klicke "🔨 Kompilieren" beim Script
- Gib das Board FQBN ein (z.B. `esp32:esp32:esp32`)
- Warte auf Kompilierung

### 3. Flashen
- Schließe dein Device an (z.B. COM3)
- Klicke "📤 Flashen"
- Gib den Port ein (z.B. `COM3` oder `/dev/ttyUSB0`)
- Bestätige das Board FQBN

## Board FQBNs (Fully Qualified Board Names)

### ESP32
- ESP32 Dev Module: `esp32:esp32:esp32`
- ESP32-S2: `esp32:esp32:esp32s2`
- ESP32-S3: `esp32:esp32:esp32s3`
- ESP32-C3: `esp32:esp32:esp32c3`

### Arduino
- Arduino Uno: `arduino:avr:uno`
- Arduino Nano: `arduino:avr:nano`
- Arduino Mega: `arduino:avr:mega`
- Arduino Leonardo: `arduino:avr:leonardo`

### Weitere
- NodeMCU (ESP8266): `esp8266:esp8266:nodemcu`

## Troubleshooting

### Arduino CLI nicht gefunden
```bash
# Prüfe Installation
arduino-cli version

# Prüfe PATH
echo $PATH  # Linux/Mac
echo %PATH%  # Windows
```

### Board nicht erkannt
```bash
# Liste verfügbare Boards
arduino-cli board list

# Liste installierte Cores
arduino-cli core list
```

### Kompilierungsfehler
- Prüfe ob alle Libraries installiert sind
- Prüfe Board FQBN
- Prüfe C++ Syntax

### Upload-Fehler
- Prüfe ob Device angeschlossen ist
- Prüfe korrekten Port
- Prüfe Treiber (CH340, CP2102, etc.)
- Schließe Serial Monitor wenn offen

## Beispiel-Script

```cpp
void setup() {
  Serial.begin(115200);
  pinMode(LED_BUILTIN, OUTPUT);
}

void loop() {
  digitalWrite(LED_BUILTIN, HIGH);
  delay(1000);
  digitalWrite(LED_BUILTIN, LOW);
  delay(1000);
  Serial.println("Blink!");
}
```

## API Endpunkte

- `GET /api/arduino/status` - Arduino CLI Status
- `GET /api/arduino/boards` - Liste verbundener Boards
- `POST /api/arduino/scripts/{id}/compile` - Script kompilieren
- `POST /api/arduino/scripts/{id}/upload` - Script flashen
- `POST /api/arduino/scripts/{id}/compile-upload` - Beides in einem
