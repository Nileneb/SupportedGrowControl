# OpenAI API Setup - Quick Guide

## 🚀 Schnellstart

### 1. API Key erstellen (2 Minuten)

```bash
# 1. Gehe zu: https://platform.openai.com/api-keys
# 2. Klick "Create new secret key"
# 3. Name: "GrowDash Arduino Error Analysis"
# 4. Permissions: "All" (oder nur "Model capabilities")
# 5. Kopiere den Key: sk-proj-xxxxxxxxxxxxx
```

⚠️ **Wichtig:** Key wird nur EINMAL angezeigt - sofort kopieren!

### 2. .env konfigurieren

```bash
# In .env eintragen:
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4o-mini

# Optional: Custom Endpoint (z.B. Azure OpenAI)
# OPENAI_ENDPOINT=https://api.openai.com/v1
```

### 3. Credit aufladen (ERFORDERLICH!)

```bash
# OpenAI erfordert Prepaid Credit (Minimum $5)
# https://platform.openai.com/account/billing

# Zahlungsmethode hinzufügen → Credit kaufen
```

💡 **Tipp:** $5 Credit reichen für ~15.000 Error-Analysen mit gpt-4o-mini!

### 4. Testen

```bash
# Laravel-App starten
php artisan serve

# Script mit Fehler kompilieren
# → Error-Modal sollte LLM-Fix anzeigen

# Bei Problemen:
tail -f storage/logs/laravel.log | grep -i "openai"
```

## 📊 Model-Übersicht

| Model           | Speed  | Qualität   | Kosten/1000 Analysen | Use Case                   |
| --------------- | ------ | ---------- | -------------------- | -------------------------- |
| **gpt-4o-mini** | ⚡⚡⚡ | ⭐⭐⭐⭐   | **$0.30**            | ✅ Standard (empfohlen)    |
| gpt-4o          | ⚡⚡   | ⭐⭐⭐⭐⭐ | $2.50                | Komplexe Multi-File Errors |
| gpt-3.5-turbo   | ⚡⚡⚡ | ⭐⭐⭐     | $0.60                | Legacy (nicht empfohlen)   |

## 💰 Kosten-Kalkulator

```
Annahmen:
- 1 Error-Analyse = ~800 tokens (500 input + 300 output)
- gpt-4o-mini: $0.15/1M input, $0.60/1M output

Pro Analyse:
- Input:  500 tokens × $0.15/1M = $0.000075
- Output: 300 tokens × $0.60/1M = $0.000180
- Total:  $0.000255 (~0.03 Cent)

Bei verschiedenen Nutzungsszenarien:
┌─────────────────┬────────────┬──────────┐
│ Analysen/Monat  │ Kosten     │ Credit   │
├─────────────────┼────────────┼──────────┤
│ 100             │ $0.03      │ $5 (167x)│
│ 1.000           │ $0.26      │ $5 (19x) │
│ 10.000          │ $2.55      │ $5 (2x)  │
│ 100.000         │ $25.50     │ $30      │
└─────────────────┴────────────┴──────────┘
```

## 🔧 Environment Variables

```bash
# Minimal (erforderlich)
OPENAI_API_KEY=sk-proj-xxxxx

# Mit Custom Model
OPENAI_MODEL=gpt-4o-mini

# Mit Custom Endpoint (z.B. Azure)
OPENAI_ENDPOINT=https://your-instance.openai.azure.com/openai/deployments/gpt-4o-mini

# Vollständig (alle Optionen)
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4o-mini
OPENAI_ENDPOINT=https://api.openai.com/v1
```

## 🧪 API-Test (ohne Laravel)

```bash
# Test mit cURL
curl https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $OPENAI_API_KEY" \
  -d '{
    "model": "gpt-4o-mini",
    "messages": [
      {"role": "system", "content": "You are a helpful assistant."},
      {"role": "user", "content": "Say hello"}
    ]
  }'

# Erwartete Antwort:
# {
#   "choices": [{
#     "message": {"content": "Hello! How can I help you?"}
#   }]
# }
```

## 🔐 Sicherheit

### API Key Schutz

✅ **DO:**

-   In `.env` speichern (nicht in Git)
-   Environment Variables auf Server setzen
-   Regelmäßig rotieren (alle 90 Tage)

❌ **DON'T:**

-   Hardcoded in Code
-   In Frontend-JavaScript
-   In Public Repository commits

### Rate Limiting

OpenAI hat automatische Rate Limits:

```
Tier 1 (Free/$5):    3.500 requests/min
Tier 2 ($50+):       5.000 requests/min
Tier 3 ($1.000+):    10.000 requests/min
```

Bei Überschreitung → HTTP 429 Error.

## 📚 Weitere Ressourcen

-   **API Docs:** https://platform.openai.com/docs/api-reference
-   **Models:** https://platform.openai.com/docs/models
-   **Pricing:** https://openai.com/api/pricing/
-   **Rate Limits:** https://platform.openai.com/docs/guides/rate-limits
-   **Playground:** https://platform.openai.com/playground

## 🆘 Support

Bei Problemen:

1. **Logs prüfen:**

    ```bash
    tail -f storage/logs/laravel.log | grep -i "openai\|error"
    ```

2. **Config prüfen:**

    ```bash
    php artisan config:cache
    php artisan config:clear
    ```

3. **API Status:**
   https://status.openai.com/

---

**Setup-Zeit:** ~5 Minuten  
**Kosten:** Ab $5 (reicht für Monate)  
**Support:** https://help.openai.com/
