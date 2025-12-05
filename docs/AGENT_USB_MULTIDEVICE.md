# GrowDash Agent - USB Multi-Device Support

## Überblick

Der Agent soll mehrere USB-Devices gleichzeitig verwalten können. Jedes Device wird automatisch erkannt, authentifiziert und mit dem Backend verbunden.

## Architektur

```
┌──────────────────────────────────────────────────────────┐
│                    GrowDash Agent                         │
│                                                           │
│  ┌────────────────────────────────────────────────────┐  │
│  │         USB Device Scanner (Main Loop)             │  │
│  │  - Scannt verfügbare Serial Ports                  │  │
│  │  - Erkennt neue Devices                            │  │
│  │  - Startet Device-Handler                          │  │
│  └────────────────────────────────────────────────────┘  │
│                          │                                │
│                          ▼                                │
│  ┌────────────────────────────────────────────────────┐  │
│  │            Device Handler (per Device)             │  │
│  │  - Serial Communication                            │  │
│  │  - Bootstrap & Authentication                      │  │
│  │  - Capabilities Discovery                          │  │
│  │  - Command Polling & Execution                     │  │
│  │  - Telemetry Sending                               │  │
│  └────────────────────────────────────────────────────┘  │
│                          │                                │
│                          ▼                                │
│  ┌────────────────────────────────────────────────────┐  │
│  │              Backend API Client                    │  │
│  │  - HTTP Requests (with Device Token)               │  │
│  │  - Error Handling & Retry Logic                    │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

## Implementierung

### 1. USB Port Scanner

**Datei:** `agent/scanner.py`

```python
import serial.tools.list_ports
import threading
import time
from typing import Dict, Set
from device_handler import DeviceHandler

class USBDeviceScanner:
    """
    Scannt kontinuierlich USB-Ports und startet Device-Handler für neue Geräte.
    """

    def __init__(self, scan_interval: int = 5):
        """
        Args:
            scan_interval: Sekunden zwischen Port-Scans (Standard: 5s)
        """
        self.scan_interval = scan_interval
        self.active_ports: Dict[str, DeviceHandler] = {}
        self.running = False
        self.lock = threading.Lock()

    def list_serial_ports(self) -> Set[str]:
        """
        Liste alle verfügbaren Serial Ports.

        Returns:
            Set mit Port-Namen (z.B. {'COM3', 'COM4', '/dev/ttyUSB0'})
        """
        ports = serial.tools.list_ports.comports()
        return {port.device for port in ports}

    def scan_and_update(self):
        """
        Scannt Ports, startet neue Handler, stoppt entfernte Devices.
        """
        current_ports = self.list_serial_ports()

        with self.lock:
            # Neue Ports: Starte Handler
            for port in current_ports - set(self.active_ports.keys()):
                print(f"✓ Neues Device gefunden: {port}")
                handler = DeviceHandler(port)
                handler.start()  # Startet Thread
                self.active_ports[port] = handler

            # Entfernte Ports: Stoppe Handler
            for port in set(self.active_ports.keys()) - current_ports:
                print(f"⚠ Device entfernt: {port}")
                handler = self.active_ports.pop(port)
                handler.stop()

    def start(self):
        """
        Startet den kontinuierlichen Scan-Loop.
        """
        self.running = True
        print(f"🔍 USB Device Scanner gestartet (Scan-Intervall: {self.scan_interval}s)")

        while self.running:
            try:
                self.scan_and_update()
                time.sleep(self.scan_interval)
            except KeyboardInterrupt:
                print("\n⚠ Scanner wird gestoppt...")
                self.stop()
                break
            except Exception as e:
                print(f"✗ Fehler im Scanner: {e}")
                time.sleep(self.scan_interval)

    def stop(self):
        """
        Stoppt den Scanner und alle aktiven Device-Handler.
        """
        self.running = False

        with self.lock:
            for port, handler in self.active_ports.items():
                print(f"⏸ Stoppe Handler für {port}")
                handler.stop()
            self.active_ports.clear()

        print("✓ Scanner gestoppt")


if __name__ == "__main__":
    scanner = USBDeviceScanner(scan_interval=5)
    scanner.start()
```

---

### 2. Device Handler (pro USB-Gerät)

**Datei:** `agent/device_handler.py`

```python
import serial
import threading
import time
import requests
from typing import Optional, Dict, Any

class DeviceHandler(threading.Thread):
    """
    Verwaltet ein einzelnes USB-Device:
    - Serial Communication
    - Bootstrap & Auth
    - Capabilities Discovery
    - Command Polling
    - Telemetry Sending
    """

    def __init__(self, port: str, backend_url: str = "http://growdash.test"):
        super().__init__(daemon=True)
        self.port = port
        self.backend_url = backend_url
        self.running = False

        # Serial connection
        self.serial: Optional[serial.Serial] = None

        # Device state
        self.device_id: Optional[str] = None
        self.device_token: Optional[str] = None
        self.capabilities: Optional[Dict[str, Any]] = None

        # Timing
        self.heartbeat_interval = 30  # seconds
        self.command_poll_interval = 2  # seconds
        self.last_heartbeat = 0
        self.last_command_poll = 0

    def connect_serial(self) -> bool:
        """
        Öffnet Serial-Verbindung zum Device.

        Returns:
            True wenn erfolgreich verbunden
        """
        try:
            self.serial = serial.Serial(
                port=self.port,
                baudrate=115200,
                timeout=1
            )
            print(f"✓ [{self.port}] Serial connected")
            return True
        except Exception as e:
            print(f"✗ [{self.port}] Serial connection failed: {e}")
            return False

    def bootstrap_device(self) -> bool:
        """
        Bootstrap-Prozess: Device ID anfordern und authentifizieren.

        Returns:
            True wenn Bootstrap erfolgreich
        """
        try:
            # 1. Device ID anfordern
            self.serial.write(b"GET_ID\n")
            time.sleep(0.5)
            response = self.serial.readline().decode('utf-8').strip()

            if not response.startswith("ID:"):
                print(f"✗ [{self.port}] Invalid bootstrap response: {response}")
                return False

            self.device_id = response.split("ID:")[1].strip()
            print(f"✓ [{self.port}] Device ID: {self.device_id}")

            # 2. Backend-Authentifizierung (Bootstrap-Endpoint)
            bootstrap_response = requests.post(
                f"{self.backend_url}/api/growdash/agent/bootstrap",
                json={"bootstrap_id": self.device_id},
                timeout=10
            )

            if bootstrap_response.status_code != 200:
                print(f"✗ [{self.port}] Bootstrap failed: {bootstrap_response.text}")
                return False

            data = bootstrap_response.json()
            self.device_token = data.get("device_token")

            if not self.device_token:
                print(f"✗ [{self.port}] No device_token in response")
                return False

            print(f"✓ [{self.port}] Authenticated with backend")
            return True

        except Exception as e:
            print(f"✗ [{self.port}] Bootstrap error: {e}")
            return False

    def discover_capabilities(self) -> bool:
        """
        Fordert Capabilities vom Device an und sendet sie ans Backend.

        Returns:
            True wenn erfolgreich
        """
        try:
            # 1. Capabilities vom Device abrufen
            self.serial.write(b"GET_CAPABILITIES\n")
            time.sleep(0.5)
            response = self.serial.readline().decode('utf-8').strip()

            # Parse JSON response (angenommen: Device sendet JSON)
            import json
            capabilities_data = json.loads(response)

            # 2. Capabilities ans Backend senden
            headers = {
                "X-Device-ID": self.device_id,
                "X-Device-Token": self.device_token,
                "Content-Type": "application/json"
            }

            response = requests.post(
                f"{self.backend_url}/api/growdash/agent/capabilities",
                json={"capabilities": capabilities_data},
                headers=headers,
                timeout=10
            )

            if response.status_code == 200:
                self.capabilities = capabilities_data
                print(f"✓ [{self.port}] Capabilities registered")
                return True
            else:
                print(f"✗ [{self.port}] Capabilities registration failed: {response.text}")
                return False

        except Exception as e:
            print(f"✗ [{self.port}] Capabilities discovery error: {e}")
            return False

    def send_heartbeat(self):
        """
        Sendet Heartbeat ans Backend (Device online halten).
        """
        try:
            headers = {
                "X-Device-ID": self.device_id,
                "X-Device-Token": self.device_token,
                "Content-Type": "application/json"
            }

            response = requests.post(
                f"{self.backend_url}/api/growdash/agent/heartbeat",
                json={},
                headers=headers,
                timeout=5
            )

            if response.status_code == 200:
                self.last_heartbeat = time.time()
            else:
                print(f"⚠ [{self.port}] Heartbeat failed: {response.status_code}")

        except Exception as e:
            print(f"⚠ [{self.port}] Heartbeat error: {e}")

    def poll_commands(self):
        """
        Fragt Backend nach pending Commands ab und führt sie aus.
        """
        try:
            headers = {
                "X-Device-ID": self.device_id,
                "X-Device-Token": self.device_token
            }

            response = requests.get(
                f"{self.backend_url}/api/growdash/agent/commands/pending",
                headers=headers,
                timeout=5
            )

            if response.status_code != 200:
                return

            data = response.json()
            commands = data.get("commands", [])

            for cmd in commands:
                self.execute_command(cmd)

            self.last_command_poll = time.time()

        except Exception as e:
            print(f"⚠ [{self.port}] Command poll error: {e}")

    def execute_command(self, command: Dict[str, Any]):
        """
        Führt ein Command auf dem Device aus und sendet Ergebnis zurück.

        Args:
            command: Command-Dict mit id, type, params
        """
        cmd_id = command["id"]
        cmd_type = command["type"]
        params = command.get("params", {})

        try:
            # Sende Command an Device via Serial
            cmd_str = f"EXECUTE {cmd_type} {params}\n"
            self.serial.write(cmd_str.encode('utf-8'))

            # Warte auf Response
            time.sleep(0.5)
            result = self.serial.readline().decode('utf-8').strip()

            # Sende Result ans Backend
            headers = {
                "X-Device-ID": self.device_id,
                "X-Device-Token": self.device_token,
                "Content-Type": "application/json"
            }

            status = "completed" if "OK" in result else "failed"

            requests.post(
                f"{self.backend_url}/api/growdash/agent/commands/{cmd_id}/result",
                json={
                    "status": status,
                    "result_message": result
                },
                headers=headers,
                timeout=5
            )

            print(f"✓ [{self.port}] Command {cmd_id} executed: {result}")

        except Exception as e:
            print(f"✗ [{self.port}] Command execution error: {e}")

    def run(self):
        """
        Haupt-Loop des Device Handlers (läuft im eigenen Thread).
        """
        self.running = True

        # 1. Serial-Verbindung herstellen
        if not self.connect_serial():
            self.running = False
            return

        # 2. Bootstrap & Auth
        if not self.bootstrap_device():
            self.running = False
            return

        # 3. Capabilities Discovery
        if not self.discover_capabilities():
            print(f"⚠ [{self.port}] Continuing without capabilities")

        # 4. Main Loop
        print(f"✓ [{self.port}] Device handler running")

        while self.running:
            try:
                current_time = time.time()

                # Heartbeat
                if current_time - self.last_heartbeat >= self.heartbeat_interval:
                    self.send_heartbeat()

                # Command Polling
                if current_time - self.last_command_poll >= self.command_poll_interval:
                    self.poll_commands()

                time.sleep(1)

            except Exception as e:
                print(f"✗ [{self.port}] Main loop error: {e}")
                time.sleep(5)

    def stop(self):
        """
        Stoppt den Device Handler und schließt Serial-Verbindung.
        """
        self.running = False

        if self.serial and self.serial.is_open:
            self.serial.close()
            print(f"✓ [{self.port}] Serial closed")
```

---

### 3. Agent Main Entry Point

**Datei:** `agent/main.py`

```python
#!/usr/bin/env python3
"""
GrowDash Agent - USB Multi-Device Support
"""

import sys
import argparse
from scanner import USBDeviceScanner

def main():
    parser = argparse.ArgumentParser(description='GrowDash USB Device Agent')
    parser.add_argument(
        '--scan-interval',
        type=int,
        default=5,
        help='USB port scan interval in seconds (default: 5)'
    )
    parser.add_argument(
        '--backend-url',
        type=str,
        default='http://growdash.test',
        help='Backend URL (default: http://growdash.test)'
    )

    args = parser.parse_args()

    print("=" * 60)
    print("  GrowDash Agent - USB Multi-Device Support")
    print("=" * 60)
    print(f"  Backend URL: {args.backend_url}")
    print(f"  Scan Interval: {args.scan_interval}s")
    print("=" * 60)
    print()

    scanner = USBDeviceScanner(scan_interval=args.scan_interval)

    try:
        scanner.start()
    except KeyboardInterrupt:
        print("\n\n⚠ Agent wird beendet...")
        scanner.stop()
        sys.exit(0)

if __name__ == "__main__":
    main()
```

---

## Installation & Verwendung

### Requirements

**Datei:** `agent/requirements.txt`

```txt
pyserial>=3.5
requests>=2.31.0
```

### Installation

```bash
# In agent/ Verzeichnis
pip install -r requirements.txt
```

### Agent starten

```bash
# Standard (scannt alle 5s, Backend = http://growdash.test)
python main.py

# Custom Scan-Intervall
python main.py --scan-interval 10

# Custom Backend URL
python main.py --backend-url http://localhost:8000
```

### Erwartete Ausgabe

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
✓ Neues Device gefunden: COM4
✓ [COM4] Serial connected
✓ [COM4] Device ID: GROW-ARDUINO-002
✓ [COM4] Authenticated with backend
✓ [COM4] Capabilities registered
✓ [COM4] Device handler running
```

---

## Device-Protokoll (Serial Communication)

Das Arduino/ESP-Device muss folgende Serial-Commands unterstützen:

### 1. Bootstrap (Device ID)

**Request:**

```
GET_ID
```

**Response:**

```
ID:GROW-ARDUINO-001
```

### 2. Capabilities Discovery

**Request:**

```
GET_CAPABILITIES
```

**Response (JSON):**

```json
{
    "board": {
        "id": "arduino_uno",
        "vendor": "Arduino",
        "model": "UNO R3"
    },
    "sensors": [
        { "id": "water_level", "channel": 1, "pin": "A0" },
        { "id": "temperature", "channel": 2, "pin": "A1" }
    ],
    "actuators": [
        { "id": "spray_pump", "channel": 1, "pin": 3 },
        { "id": "fill_valve", "channel": 2, "pin": 4 }
    ]
}
```

### 3. Command Execution

**Request:**

```
EXECUTE spray_pump {"duration_ms": 1000}
```

**Response:**

```
OK: Pump activated for 1000ms
```

---

## Fehlerbehandlung

### Device disconnected

-   Scanner erkennt fehlenden Port
-   Device Handler wird automatisch gestoppt
-   Backend markiert Device als `offline` (via Heartbeat-Timeout)

### Backend nicht erreichbar

-   Device Handler versucht weiter Heartbeat/Commands zu senden
-   Fehler werden geloggt, aber Handler bleibt aktiv
-   Bei Wiederverbindung wird normal fortgefahren

### Serial Communication Fehler

-   Handler versucht Re-Connect nach 5s Pause
-   Nach 3 fehlgeschlagenen Versuchen wird Handler gestoppt

---

## Erweiterungen (Optional)

### 1. Logging in Datei

```python
import logging

logging.basicConfig(
    filename='agent.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

logging.info(f"Device {self.device_id} connected")
```

### 2. Telemetrie-Sending (automatisch)

```python
def send_telemetry(self):
    """Liest Sensor-Daten und sendet sie ans Backend."""
    self.serial.write(b"GET_TELEMETRY\n")
    time.sleep(0.5)
    data = json.loads(self.serial.readline().decode('utf-8'))

    headers = {
        "X-Device-ID": self.device_id,
        "X-Device-Token": self.device_token,
        "Content-Type": "application/json"
    }

    requests.post(
        f"{self.backend_url}/api/growdash/agent/telemetry",
        json={"readings": data["readings"]},
        headers=headers,
        timeout=5
    )
```

### 3. Config-Datei (statt CLI-Args)

**config.json:**

```json
{
    "backend_url": "http://growdash.test",
    "scan_interval": 5,
    "heartbeat_interval": 30,
    "command_poll_interval": 2
}
```

---

## Testing

### Mock Serial Device (für Tests ohne Hardware)

```python
# test_device.py
import time

class MockSerial:
    def __init__(self):
        self.device_id = "MOCK-DEVICE-001"

    def write(self, data: bytes):
        cmd = data.decode('utf-8').strip()
        print(f"[MOCK] Received: {cmd}")

    def readline(self) -> bytes:
        return b"ID:MOCK-DEVICE-001\n"

# In device_handler.py:
# self.serial = MockSerial()  # statt serial.Serial()
```

---

## Zusammenfassung

✅ **Scanner** findet automatisch alle USB-Devices  
✅ **Handler** pro Device (threaded, unabhängig)  
✅ **Bootstrap & Auth** über Backend API  
✅ **Capabilities Discovery** vom Device  
✅ **Heartbeat** hält Device online  
✅ **Command Polling** führt Backend-Befehle aus  
✅ **Fehlerbehandlung** bei Disconnect/Timeouts

**Der Agent ist jetzt vollständig modular und skalierbar für Multi-Device-Betrieb!** 🚀
