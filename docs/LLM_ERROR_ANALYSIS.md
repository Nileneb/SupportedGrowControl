# LLM-gestützte Arduino Error-Korrektur

## 🎯 Feature-Beschreibung

Wenn Arduino-Kompilierungen fehlschlagen, analysiert ein LLM (GitHub Models API) automatisch den Compiler-Fehler und schlägt eine Korrektur vor.

## ✨ Funktionsweise

1. **User klickt "Kompilieren"** → Befehl wird an Device-Agent gesendet
2. **Agent kompiliert** → Bei Fehler wird error-output zurückgemeldet
3. **Laravel empfängt Status** → Polling erkennt `status=failed`
4. **LLM analysiert Error** → GitHub Models API bekommt:
    - Original C++ Code
    - Compiler-Fehlermeldung
    - Board FQBN (z.B. `arduino:avr:nano`)
5. **Modal zeigt Ergebnis**:
    - ❌ Original Compiler-Error
    - 🤖 AI-Analyse (Zusammenfassung + Erklärung)
    - ✅ Korrigierter Code
    - ✨ "Fix anwenden" Button
6. **User klickt "Fix anwenden"** → Code wird automatisch aktualisiert

## 🔧 Setup

### 1. OpenAI API Key

```bash
# OpenAI API Key erstellen:
# https://platform.openai.com/api-keys

# In .env eintragen:
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4o-mini
```

### 2. Supported Models

| Model           | Beschreibung                   | Kosten (Input/Output)        | Empfehlung             |
| --------------- | ------------------------------ | ---------------------------- | ---------------------- |
| `gpt-4o-mini`   | Neuestes Mini-Modell (12/2024) | $0.15 / $0.60 per 1M tokens  | ✅ **Empfohlen**       |
| `gpt-4o`        | Neuestes GPT-4 Omni            | $2.50 / $10.00 per 1M tokens | 🎯 Für komplexe Fehler |
| `gpt-3.5-turbo` | Älter, weniger genau           | $0.50 / $1.50 per 1M tokens  | ❌ Nicht empfohlen     |

**Warum gpt-4o-mini?**

-   🚀 Schnellste Antwortzeit (~500ms)
-   💰 75% günstiger als gpt-4o
-   🎯 Optimiert für Code-Analyse
-   📅 Released: Dezember 2024

### 3. Kostenübersicht

**Beispiel-Rechnung:**

-   Durchschnittlicher Request: ~500 tokens (Error + Code + Prompt)
-   Durchschnittliche Response: ~300 tokens (Fix + Erklärung)
-   **Kosten pro Error-Analyse:** ~$0.0003 (0.03 Cent!)

**Bei 1000 Kompilierungsfehlern/Monat:**

-   Input: 500.000 tokens × $0.15/1M = $0.075
-   Output: 300.000 tokens × $0.60/1M = $0.180
-   **Total: ~$0.25/Monat** 💰

### 4. Rate Limits

OpenAI Free Tier (mit API Key):

-   ✅ Keine strikten Limits (Pay-as-you-go)
-   ⚡ Standard: 3.500 Requests/Min (mehr als ausreichend)
-   💳 Prepaid Credit erforderlich ($5 Minimum)

Details: https://platform.openai.com/docs/guides/rate-limits

## 📊 API Flow

```
┌─────────────┐
│   Frontend  │
│  (Compile)  │
└──────┬──────┘
       │ POST /api/arduino/scripts/{id}/compile
       ▼
┌─────────────────────┐
│ ArduinoCompile      │
│ Controller          │
│ - Create Command    │
│ - Return command_id │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  JavaScript         │
│  pollCommandStatus()│ ◄─── GET /api/arduino/commands/{id}/status (every 3s)
└──────┬──────────────┘
       │
       ▼ status === 'failed'
┌─────────────────────┐
│ ArduinoError        │
│ Analyzer Service    │
│ - analyzeAndFix()   │
└──────┬──────────────┘
       │ POST https://api.openai.com/v1/chat/completions
       ▼
┌─────────────────────┐
│  OpenAI API         │
│  (gpt-4o-mini)      │
│  - Analyze error    │
│  - Generate fix     │
└──────┬──────────────┘
       │ Return JSON {error_summary, explanation, fixed_code}
       ▼
┌─────────────────────┐
│   Error Modal       │
│   - Show error      │
│   - Show fix        │
│   - Apply button    │
└─────────────────────┘
```

## 🧪 Testing

### 1. Fehler provozieren

Erstelle ein Script mit Syntax-Error:

```cpp
void setup() {
  pinMode(LED_BUILTIN, OUTPUT);
}

void loop() {
  digitalWrite(LED_BUILTIN, HI);  // ❌ Typo: HI statt HIGH
  delay(1000);
  digitalWrite(LED_BUILTIN, LOW);
  delay(1000);
}
```

### 2. Kompilieren

1. Klick auf "🔨 Kompilieren"
2. Device + Board wählen
3. Warten auf Fehler-Modal

### 3. Erwartetes Ergebnis

**Modal zeigt:**

```
❌ Kompilierungsfehler

Compiler-Output:
error: 'HI' was not declared in this scope
note: suggested alternative: 'HIGH'

🤖 AI-Analyse:
Tippfehler: 'HI' ist nicht definiert. Meintest du 'HIGH'?

✅ Korrigierter Code:
void setup() { ... }
void loop() {
  digitalWrite(LED_BUILTIN, HIGH);  // ✅ Fixed
  ...
}
```

## 🔒 Sicherheit

### Token-Schutz

-   Token liegt in `.env` (nicht in Git)
-   Nur Server hat Zugriff
-   Frontend sieht nur Ergebnis

### Rate Limiting

Bei vielen Requests → GitHub API Limit erreicht:

```json
{
    "has_fix": false,
    "error": "LLM-Anfrage fehlgeschlagen: HTTP 429"
}
```

**Lösung:** Warten oder auf `gpt-4o-mini` wechseln (höheres Limit).

## 📝 Prompting-Strategie

Der Service nutzt folgendes Prompt-Template:

```
# Arduino Compilation Error Analysis

**Board:** arduino:avr:nano

**Compiler Error:**
error: 'LO' was not declared in this scope
note: suggested alternative: 'LOW'

**Original Code:**
<code hier>

**Task:** Analysiere den Compiler-Fehler und korrigiere den Code.

**Response Format (JSON):**
{
  "error_summary": "Kurze Zusammenfassung (max 100 Zeichen)",
  "explanation": "Detaillierte Erklärung",
  "fixed_code": "Vollständiger korrigierter Code",
  "confidence": "high|medium|low"
}
```

**Warum JSON?**

-   Strukturierte Antwort → Einfaches Parsing
-   `response_format: json_object` → GPT garantiert valides JSON
-   Frontend braucht separate Felder (summary, code, etc.)

## 🛠️ Troubleshooting

### Error: "LLM-Integration nicht konfiguriert"

```bash
# Check .env
cat .env | grep OPENAI_API_KEY

# Muss gesetzt sein (nicht leer)
OPENAI_API_KEY=sk-proj-xxxx...
```

### Error: "Ungültige LLM-Antwort"

LLM hat kein valides JSON zurückgegeben.

**Fix:** Model wechseln zu `gpt-4o-mini`:

```dotenv
OPENAI_MODEL=gpt-4o-mini
```

### Error: HTTP 401 Unauthorized

API Key ungültig oder nicht gesetzt.

**Fix:** Neuen Key erstellen:

1. https://platform.openai.com/api-keys
2. "Create new secret key"
3. `.env` aktualisieren: `OPENAI_API_KEY=sk-proj-...`

### Error: HTTP 429 Rate Limit

Zu viele Requests oder kein Credit mehr.

**Fix:**

1. Credit aufladen: https://platform.openai.com/account/billing
2. Rate Limit prüfen: https://platform.openai.com/account/limits

## 📚 Code-Referenz

### Service-Layer

```php
// app/Services/ArduinoErrorAnalyzer.php
$analyzer = new ArduinoErrorAnalyzer();
$result = $analyzer->analyzeAndFix($errorMessage, $code, $boardFqbn);

// Returns:
[
  'has_fix' => true,
  'error_summary' => 'Tippfehler...',
  'explanation' => '...',
  'fixed_code' => '...',
  'confidence' => 'high'
]
```

### Controller

```php
// app/Http/Controllers/ArduinoCompileController.php
public function checkCommandStatus(Command $command) {
  if ($command->status === 'failed' && $command->type === 'arduino_compile') {
    $analyzer = new ArduinoErrorAnalyzer();
    $analysis = $analyzer->analyzeAndFix(...);
    return ['error_analysis' => $analysis];
  }
}
```

### Frontend

```javascript
// device-script-management.blade.php
async function pollCommandStatus(commandId) {
    // Poll every 3s
    const data = await fetch(`/api/arduino/commands/${commandId}/status`);

    if (data.status === "failed") {
        openErrorModal(commandId, data.original_error, data.error_analysis);
    }
}
```

## 🎉 Erfolgsbeispiele

### 1. Undeclared Variable

**Error:**

```
'LO' was not declared in this scope
```

**Fix:**

```cpp
delay(LONG_ON);  // statt: delay(LO LONG_ON);
```

### 2. Missing Semicolon

**Error:**

```
expected ';' before 'digitalWrite'
```

**Fix:**

```cpp
pinMode(LED_BUILTIN, OUTPUT);  // ✅ Semicolon hinzugefügt
digitalWrite(LED_BUILTIN, HIGH);
```

### 3. Wrong Function Name

**Error:**

```
'digitalWrit' was not declared
note: suggested alternative: 'digitalWrite'
```

**Fix:**

```cpp
digitalWrite(LED_BUILTIN, HIGH);  // ✅ Tippfehler korrigiert
```

## 🚀 Erweiterungsmöglichkeiten

### 1. Library-Empfehlungen

LLM könnte fehlende Libraries erkennen:

```cpp
// Error: Adafruit_Sensor.h: No such file
→ Vorschlag: "arduino-cli lib install Adafruit Unified Sensor"
```

### 2. Code-Optimierung

Nicht nur Fixes, sondern auch Verbesserungen:

```cpp
// Vorher:
for (int i=0; i<10; i++) { delay(1000); }

// Optimiert:
delay(10000);  // ✅ Einfacher
```

### 3. Multi-Language Support

Aktuell nur C++, könnte erweitert werden:

-   Python (MicroPython)
-   Rust (embedded)
-   Assembly

---

**Version:** 1.0  
**Autor:** Nileneb  
**Datum:** 4. Dezember 2025
