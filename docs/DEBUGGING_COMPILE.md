# Debugging: Compile-Button funktioniert nicht

## Problem

Compile-Button erzeugt weder in Laravel noch im Agent eine Reaktion.

## Diagnose - Schritt für Schritt

### 1️⃣ Check: Frontend sendet Command

```javascript
// Browser Console (F12):
// Klick auf Compile-Button
// Sollte zeigen:
fetch /api/arduino/scripts/1/compile
→ Response 200: {"success":true,"command_id":28}
```

**Wenn nicht:**

-   ❌ POST Request wird nicht gesendet
-   Check: JavaScript Fehler in Console
-   Check: DeviceSelect + Board Select gefüllt?

### 2️⃣ Check: Laravel speichert Command

```bash
# SSH/Terminal auf Server:
php artisan tinker

# In tinker:
>>> $cmd = \App\Models\Command::latest()->first();
>>> $cmd->id;          # → sollte 28 sein
>>> $cmd->status;      # → sollte 'pending' sein
>>> $cmd->type;        # → sollte 'arduino_compile' sein
>>> $cmd->device_id;   # → sollte Device ID sein
```

**Wenn Command nicht existiert:**

-   ❌ Laravel speichert nicht
-   Check: `ArduinoCompileController::compile()` wird aufgerufen?
-   Check: Auth OK? (User-ID gespeichert?)
-   Check: DB Error in `storage/logs/laravel.log`?

### 3️⃣ Check: Agent erhält Command

**Agent Terminal beobachten:**

```bash
cd ~/growdash
./grow_start.sh

# Sollte zeigen:
2025-12-04 14:30:15 - INFO - Empfangene Befehle: 1
2025-12-04 14:30:15 - INFO - Führe Befehl aus: arduino_compile
2025-12-04 14:30:16 - INFO - Sketch erstellt: /tmp/arduino_sketch_xxx/arduino_sketch_xxx.ino
```

**Wenn nichts angezeigt wird:**

-   ❌ Agent pollt nicht oder erhält keine Commands
-   Check: Agent ist online? (Device Status in Laravel: `online`?)
-   Check: Device-Auth OK? (X-Device-ID + X-Device-Token Header?)
-   Check: Agent-Logs für "Fehler beim Abrufen der Befehle"?

```bash
2025-12-04 14:30:20 - ERROR - Fehler beim Abrufen der Befehle: 502 Server Error
# → Laravel Backend antwortet nicht richtig!
```

### 4️⃣ Check: Agent führt Handler aus

```bash
# Wenn Agent Commands empfängt aber nichts tut:
# → Handler existiert nicht oder ist fehlerhaft

# Prüfe Python-Agent:
grep -n "def handle_arduino_compile" ~/growdash/agent.py

# Sollte Methode existieren
# Wenn nicht: Handler muss hinzugefügt werden!
```

### 5️⃣ Check: Agent sendet Result zurück

```bash
# Agent-Logs sollten zeigen:
2025-12-04 14:30:25 - INFO - Sketch erfolgreich kompiliert
2025-12-04 14:30:26 - INFO - Befehlsergebnis gemeldet: 28 -> completed

# ODER bei Fehler:
2025-12-04 14:30:25 - ERROR - Kompilierung fehlgeschlagen: ...
2025-12-04 14:30:26 - INFO - Befehlsergebnis gemeldet: 28 -> failed
```

**Wenn nicht angezeigt wird:**

-   ❌ Handler wirft Exception
-   Check: `result_data` wird korrekt gespeichert?
-   Check: Agent kann zu Laravel zurück kommunizieren?

### 6️⃣ Check: Laravel empfängt Result

```bash
# Terminal:
tail -f storage/logs/laravel.log | grep -i command

# Sollte zeigen:
[2025-12-04 14:30:26] local.INFO: Command status updated [{"command_id":28,"status":"completed"}]
```

### 7️⃣ Check: Frontend empfängt Status

```javascript
// Browser Console (F12):
// Nach Compile-Button Klick sollten API-Calls angezeigt werden:
GET /api/arduino/commands/28/status
→ Response: {"command":{"status":"completed"}, ...}
```

**Wenn nicht:**

-   ❌ Frontend pollt nicht richtig
-   Check: JavaScript Fehler?
-   Check: `pollCommandStatus()` wird aufgerufen?

## Vollständige Debug-Befehlskette

```bash
# Terminal 1: Laravel Logs
php artisan tail

# Terminal 2: Agent starten
cd ~/growdash
./grow_start.sh

# Terminal 3: Compile Button klicken + Browser Console (F12) öffnen
# F12 → Network → Filter "status"
# Sollte sehen:
# POST /api/arduino/scripts/1/compile → 200 ✅
# GET  /api/arduino/commands/28/status → 200 ✅ (mehrmals, alle 3s)

# Dann prüfen ob Error-Modal erscheint
```

## Häufige Fehler

| Symptom                                       | Ursache                     | Lösung                                   |
| --------------------------------------------- | --------------------------- | ---------------------------------------- |
| Frontend: "keine Reaktion"                    | Agent läuft nicht           | `./grow_start.sh`                        |
| Frontend: "keine Reaktion"                    | Handler existiert nicht     | Python-Agent Handler hinzufügen          |
| Agent zeigt: "502 Server Error"               | Laravel API antwortet nicht | Laravel neu starten: `php artisan serve` |
| Agent zeigt: "Fehler beim Abrufen"            | Device nicht online         | Device-Status in Laravel prüfen          |
| Error-Modal zeigt keine LLM-Analyse           | OpenAI API Key fehlt        | `.env`: `OPENAI_API_KEY=sk-proj-...`     |
| Error-Modal zeigt "⚠️ Analyse fehlgeschlagen" | LLM Error (401, 429)        | OpenAI Logs checken                      |

## Quick-Fix Checklist

```bash
# 1. Alle Services neustarten
php artisan serve &
cd ~/growdash && ./grow_start.sh &

# 2. Cache clearen
php artisan cache:clear
php artisan config:clear

# 3. Migration ausführen (falls result_data Feld fehlt)
php artisan migrate

# 4. Logs beobachten
php artisan tail

# 5. Test: Compile-Button klicken
# → Beobachte Terminal Output
# → Prüfe Browser Console (F12)
```

## Logs lesen

### Laravel Log-Beispiel (Erfolg)

```
[2025-12-04 14:30:15] local.INFO: Command created [id:28, type:arduino_compile, device_id:5]
[2025-12-04 14:30:20] local.INFO: Command status updated [id:28, status:executing]
[2025-12-04 14:30:25] local.INFO: Command status updated [id:28, status:completed, output:"Sketch erfolgreich..."]
```

### Agent Log-Beispiel (Erfolg)

```
2025-12-04 14:30:15 - INFO - Empfangene Befehle: 1
2025-12-04 14:30:15 - INFO - Führe Befehl aus: arduino_compile
2025-12-04 14:30:16 - INFO - Sketch erstellt: /tmp/arduino_sketch_abc123/arduino_sketch_abc123.ino
2025-12-04 14:30:25 - INFO - ✅ Sketch erfolgreich kompiliert
2025-12-04 14:30:26 - INFO - Befehlsergebnis gemeldet: 28 -> completed
```

### Agent Log-Beispiel (Fehler)

```
2025-12-04 14:30:15 - INFO - Führe Befehl aus: arduino_compile
2025-12-04 14:30:16 - INFO - Sketch erstellt: /tmp/arduino_sketch_abc123/arduino_sketch_abc123.ino
2025-12-04 14:30:20 - ERROR - ❌ Kompilierung fehlgeschlagen:
error: 'LO' was not declared in this scope
2025-12-04 14:30:21 - INFO - Befehlsergebnis gemeldet: 28 -> failed
```

---

**Support:** Prüfe Logs von vorne nach hinten - wird klarer wo das Problem ist!
