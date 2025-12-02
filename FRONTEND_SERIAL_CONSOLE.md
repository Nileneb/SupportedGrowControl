# Frontend Serial Console - User Guide

## Übersicht

Die Serial Console ermöglicht es, **direkte Befehle** an das Arduino Uno Board zu senden, das über den Python-Agent auf dem Raspberry Pi verbunden ist.

---

## Features

### ✅ Was funktioniert jetzt:

1. **Serial Console (links)**
   - Terminal-artige Oberfläche für Command-Input
   - Live-Output mit farbcodiertem Status
   - Auto-Scroll bei neuen Nachrichten
   - Limit auf 100 Zeilen (älteste werden automatisch entfernt)

2. **Command Sending**
   - Eingabefeld für Commands
   - Submit via Enter oder Button
   - Sofortiges Feedback (Command queued)
   - Automatisches Polling für Ergebnisse (alle 2s, max 60s)

3. **Command History (oben rechts)**
   - Zeigt letzten 20 Commands an
   - Status-Badges: Pending, Executing, Completed, Failed
   - Timestamp und Creator angezeigt
   - Result-Message bei completed/failed
   - Auto-Refresh alle 10 Sekunden
   - Manueller Refresh-Button

4. **Device Logs (unten rechts)**
   - Zeigt Arduino-Logs aus der Datenbank
   - Farbcodierte Log-Levels (ERROR, WARN, INFO, DEBUG)
   - Timestamp in HH:mm:ss Format
   - Reload-Button für manuelle Aktualisierung

---

## Verwendung

### Command senden

1. Gehe zu **Dashboard** → Klicke auf dein Device
2. Im Serial Console (linke Seite) Command eingeben, z.B.:
   ```
   STATUS
   ```
3. Enter drücken oder "Send" klicken
4. Output zeigt:
   ```
   > STATUS                           (gelb - gesendet)
   ✓ Command queued (ID: 123)        (grün - bestätigt)
   ← Temperature: 23.5°C, Humid...   (grün - Antwort vom Arduino)
   ```

### Status-Icons erklärt

**Serial Console Output:**
- `>` (gelb) - Command wurde gesendet
- `✓` (grün) - Command erfolgreich in Queue
- `✗` (rot) - Fehler beim Senden
- `←` (grün) - Antwort vom Arduino
- `⏳` (blau) - Command wird ausgeführt
- `⚠` (gelb) - Timeout (keine Antwort nach 60s)

**Command History Status:**
- **PENDING** (grau) - Wartet auf Verarbeitung durch Agent
- **EXECUTING** (blau) - Agent führt gerade aus
- **COMPLETED** (grün) - Erfolgreich abgeschlossen
- **FAILED** (rot) - Fehlgeschlagen

---

## Technische Details

### Frontend → Backend Flow

```
┌─────────────┐    POST /api/growdash/devices/{id}/commands    ┌──────────┐
│  Frontend   │ ──────────────────────────────────────────────▶ │  Laravel │
│ (JavaScript)│                                                  │   API    │
└─────────────┘                                                  └──────────┘
       │                                                                │
       │ Poll alle 2s: GET /api/growdash/devices/{id}/commands         │
       │◀───────────────────────────────────────────────────────────────┤
       │                                                                │
       ▼                                                                ▼
  Zeige Ergebnis                                           Python Agent holt
  im Terminal                                              Command und sendet
                                                           ans Arduino
```

### API Endpoints (vom Frontend genutzt)

1. **Command senden**
   ```javascript
   POST /api/growdash/devices/{device_id}/commands
   Headers: X-CSRF-TOKEN, Content-Type: application/json
   Body: {
     "type": "serial_command",
     "params": {"command": "STATUS"}
   }
   Response: {
     "success": true,
     "command": {"id": 123, "status": "pending", ...}
   }
   ```

2. **Command History abrufen**
   ```javascript
   GET /api/growdash/devices/{device_id}/commands?limit=20
   Headers: X-CSRF-TOKEN
   Response: {
     "success": true,
     "commands": [...]
   }
   ```

### Auto-Polling

- **Result-Polling**: 2 Sekunden Intervall, max 30 Versuche (60s total)
- **History-Polling**: 10 Sekunden Intervall für Command-Updates
- **Auto-Stop**: Polling stoppt bei `beforeunload` Event

### Performance

- **Output-Limit**: Maximal 100 Zeilen im Terminal
- **History-Cache**: Speichert IDs der letzten 50 Commands
- **Debouncing**: Verhindert Duplicate-Updates

---

## Beispiel Commands

Je nach Arduino-Sketch können folgende Commands funktionieren:

```bash
STATUS          # Get current sensor readings
SPRAY           # Trigger spray pump
FILL            # Trigger fill valve
RESET           # Reset Arduino
VERSION         # Get firmware version
HELP            # List available commands
```

**Hinweis**: Commands sind abhängig vom Arduino-Code und müssen dort implementiert sein!

---

## Debugging

### Wenn Commands nicht ankommen:

1. **Device Status prüfen**
   - Muss `online` sein (grüner Badge)
   - Wenn `paired`: Python-Agent läuft nicht
   - Wenn `offline`: Keine Heartbeats seit >2 Minuten

2. **Browser Console öffnen** (F12)
   ```javascript
   // Manuell Command senden
   fetch('/api/growdash/devices/0709c4d2-14a9-4716-a7e4-663bb8acaa66/commands', {
     method: 'POST',
     headers: {
       'Content-Type': 'application/json',
       'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
     },
     body: JSON.stringify({type: 'serial_command', params: {command: 'STATUS'}})
   }).then(r => r.json()).then(console.log)
   ```

3. **Laravel Logs prüfen**
   ```bash
   docker exec supportedgrowcontrol-php-cli-1 tail -f storage/logs/laravel.log
   ```

4. **Nginx Access Logs**
   ```bash
   docker logs supportedgrowcontrol-web-1 --tail 50
   ```

### Häufige Fehler:

- **401 Unauthorized**: User nicht eingeloggt → Neu einloggen
- **403 Forbidden**: Device gehört nicht dem User
- **404 Not Found**: Device-ID falsch
- **400 Bad Request**: Device ist offline

---

## Nächste Schritte (TODO)

- [ ] WebSocket-Integration für Echtzeit-Updates (statt Polling)
- [ ] Command-Autocomplete (basierend auf History)
- [ ] Command-Templates (Favoriten)
- [ ] Export von Command-History (CSV/JSON)
- [ ] Keyboard-Shortcuts (Ctrl+L für Clear, Pfeiltasten für History)
- [ ] Syntax-Highlighting für Commands
- [ ] Multi-Device Command Broadcasting

---

## Zusammenfassung

Das Frontend ist **vollständig funktional** für:
- ✅ Command-Eingabe und -Versand
- ✅ Status-Tracking (pending → executing → completed)
- ✅ Result-Anzeige im Terminal
- ✅ Command-History mit Auto-Refresh
- ✅ Device-Logs-Anzeige

**Was noch fehlt**: Python-Agent muss Commands abholen und ans Arduino senden.
