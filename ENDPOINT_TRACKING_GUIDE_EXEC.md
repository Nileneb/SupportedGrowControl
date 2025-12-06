# Endpoint Tracking: Durchführung & Analyse

## Was wurde gemacht?

1. **Alle Controller-Funktionen mit Tracking-Logs ausgestattet** (🎯 ENDPOINT_TRACKED)
2. **Test-Suite erstellt** zum Aufrufen aller Endpoints
3. **Logging-Infrastruktur** für Analyse eingerichtet

## Schritt-für-Schritt Durchführung

### 1. Logs leeren und Container starten

```bash
cd /home/nileneb/SupportedGrowControl

# Log-Datei leeren
> storage/logs/laravel.log
chmod 666 storage/logs/laravel.log

# Container starten (falls nicht aktiv)
docker-compose up -d
```

### 2. Option A: Feature Tests ausführen (empfohlen)

```bash
# Tests ausführen (erstellt Logs)
docker-compose exec php php artisan test tests/Feature/EndpointTrackingTest.php

# oder
php artisan test tests/Feature/EndpointTrackingTest.php
```

### 3. Option B: Bash-Script ausführen (schneller)

```bash
./test_endpoint_tracking.sh
```

### 4. Logs auswerten

```bash
# ALLE getrackten Endpoints anzeigen
grep "ENDPOINT_TRACKED" storage/logs/laravel.log

# Nach Häufigkeit sortiert (am wichtigsten!)
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | cut -d':' -f2- | sort | uniq -c | sort -rn

# Nur bestimmte Controller
grep "ENDPOINT_TRACKED.*CommandController" storage/logs/laravel.log | sort | uniq -c

# Mit Zeitstempel
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | head -50
```

### 5. Ergebnisse speichern

```bash
# Alle Endpoints in Datei speichern
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | sort | uniq > used_endpoints.txt

# Mit Häufigkeit
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | cut -d':' -f2- | sort | uniq -c | sort -rn > endpoint_frequency.txt

# Anzeigen
cat endpoint_frequency.txt
```

## Was die Logs zeigen

Jeder Log-Eintrag sieht so aus:
```
[2025-12-06 12:34:56] production.INFO: 🎯 ENDPOINT_TRACKED: CommandController@send {"user_id": 1, "device_id": 42, "command_id": 123, ...}
```

Das ermöglicht zu sehen:
- **Welche Endpoints** tatsächlich aufgerufen werden
- **Wie oft** sie aufgerufen werden
- **Von wem** (user_id, device_id)
- **Mit welchen Daten** (params, status, etc.)

## Erwartete Ergebnisse

Nach vollständiger Testabdeckung sollten wir sehen:

✅ **Häufig genutzt:**
- `CommandController@send` - Core Funktion
- `CommandController@pending` - Agent ruft ab
- `BootstrapController@bootstrap` - Geräte-Registrierung
- `BootstrapController@status` - Agent Poll
- `DashboardController@index` - Web-UI
- `DevicePairingController@pair` - User pairing
- `DeviceManagementController@heartbeat` - Agent Heartbeat

⚠️ **Möglicherweise redundant:**
- `DeviceController@register` vs `DeviceRegistrationController@registerFromAgent`
- Mehrere `GrowdashWebhookController` Methoden
- Historische `GrowdashWebhookController@manualSpray/Fill`

❌ **Wahrscheinlich ungenutz:**
- `GrowdashWebhookController@event` - alte Event-Integration
- `GrowdashWebhookController@log` - alte Log-Integration  
- Manche `ShellySyncController` Methoden (optional)
- `ArduinoCompileController@checkCommandStatus` - kann optimiert werden

## Nächste Schritte nach Analyse

1. **Kopieren Sie die Logs**: `cp storage/logs/laravel.log endpoint_tracking_$(date +%s).log`
2. **Analysieren Sie Patterns**: Welche Kombinationen von Endpoints werden zusammen aufgerufen?
3. **Identifizieren Sie Duplikate**: Welche Funktionen können zusammengefasst werden?
4. **Planen Sie Cleanup**: In welcher Reihenfolge sollten wir refaktorieren?

## Manuelle Test-Beispiele

Falls Sie bestimmte Flows testen möchten:

### Device Registration Flow
```bash
# 1. Bootstrap
curl -X POST http://localhost:8000/api/agents/bootstrap \
  -H "Content-Type: application/json" \
  -d '{"bootstrap_id":"agent-manual-1"}'

# 2. Status polling
curl http://localhost:8000/api/agents/pairing/status?bootstrap_id=agent-manual-1&bootstrap_code=ABC123

# 3. User pairing (mit Auth)
curl -X POST http://localhost:8000/api/devices/pair \
  -H "Content-Type: application/json" \
  -d '{"bootstrap_code":"ABC123"}'
```

### Command Flow
```bash
# 1. Device polling for pending commands
curl http://localhost:8000/api/growdash/agent/commands/pending \
  -H "X-Device-ID: device-uuid" \
  -H "X-Device-Token: token-hash"

# 2. Send command
curl -X POST http://localhost:8000/api/growdash/devices/device-uuid/commands \
  -H "Content-Type: application/json" \
  -d '{"type":"serial_command","params":{"command":"STATUS"}}'

# 3. Report result
curl -X POST http://localhost:8000/api/growdash/agent/commands/1/result \
  -H "Content-Type: application/json" \
  -d '{"status":"completed","result_message":"OK"}'
```

## Tipps für genaue Analyse

1. **Mehrfach testen**: Führen Sie Tests 2-3x aus für repräsentative Daten
2. **Realistische Last**: Die echten Nutzungsmuster könnten anders sein
3. **Fehler-Paths**: Nicht alle Tests werden erfolgreiche Logs generieren (das ist ok!)
4. **Datum berücksichtigen**: Alte Endpoints könnten noch alte Logs haben

## Probleme beheben

**Problem: Keine Logs sichtbar**
```bash
# Log-Datei Permissions prüfen
ls -la storage/logs/laravel.log
chmod 666 storage/logs/laravel.log

# Container-Logs prüfen
docker-compose logs php | tail -50
```

**Problem: 401/403 Errors überall**
```bash
# Das ist ok! Wir tracken trotzdem den Aufruf
# Die Logs werden VOR der Auth-Prüfung generiert
```

**Problem: Tests schlagen fehl**
```bash
# Kann sein, dass Migrations fehlen
php artisan migrate --env=testing

# Oder Seeding
php artisan db:seed --env=testing
```

## Ausgabe exportieren

```bash
# Schöne Tabelle erzeugen
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | \
  sed 's/.*ENDPOINT_TRACKED: //' | \
  cut -d' ' -f1 | \
  sort | uniq -c | sort -rn | \
  awk '{print $2, "\t", $1}' | \
  column -t

# In CSV exportieren
echo "Endpoint,Count" > endpoints.csv
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | \
  sed 's/.*ENDPOINT_TRACKED: //' | \
  cut -d' ' -f1 | \
  sort | uniq -c | sort -rn | \
  awk '{print $2, ",", $1}' >> endpoints.csv
```

## Viel Erfolg! 🚀

Mit dieser Methode kriegen wir endlich Klarheit über:
- ✅ Was wirklich benutzt wird
- ✅ Was doppelt existiert
- ✅ Was optimiert werden kann
- ✅ Wie die echte Architektur aussieht (nicht wie wir denken)
