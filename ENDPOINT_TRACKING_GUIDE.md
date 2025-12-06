# Endpoint Tracking Guide

Alle Controller-Funktionen wurden mit eindeutigen Tracking-Logs versehen. Diese ermöglichen es, herauszufinden, welche Endpoints WIRKLICH genutzt werden.

## Format der Logs

Alle Tracking-Logs verwenden das Format:
```
🎯 ENDPOINT_TRACKED: {ControllerName}@{methodName}
```

Das ermöglicht leicht zu grepben:
```bash
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | sort | uniq -c
```

## Überblick aller getrackten Endpoints

### API Controllers

#### CommandController (API Agent Communication)
- `CommandController@pending` - GET /api/growdash/agent/commands/pending
- `CommandController@result` - POST /api/growdash/agent/commands/{id}/result
- `CommandController@send` - POST /api/growdash/devices/{device}/commands
- `CommandController@send (serial_command)` - Serial command variant
- `CommandController@history` - GET /api/growdash/devices/{device}/commands

#### AuthController
- `AuthController@login` - POST /api/auth/login
- `AuthController@logout` - POST /api/auth/logout

#### DeviceManagementController  
- `DeviceManagementController@heartbeat` - POST /api/growdash/agent/heartbeat

#### LogController
- `LogController@store` - POST /api/growdash/agent/logs

#### ShellyWebhookController
- `ShellyWebhookController@handle` - POST /api/shelly/webhook/{shelly_id}

#### DeviceController
- `DeviceController@register (new)` - POST /api/growdash/devices/register (new device)
- `DeviceController@register (re-pair)` - POST /api/growdash/devices/register (re-pairing)

#### DeviceRegistrationController
- `DeviceRegistrationController@registerFromAgent` - POST /api/growdash/devices/register-from-agent

### Web Controllers

#### BootstrapController
- `BootstrapController@bootstrap (new)` - POST /api/agents/bootstrap (new device)
- `BootstrapController@bootstrap (paired)` - POST /api/agents/bootstrap (already paired)
- `BootstrapController@bootstrap (pending)` - POST /api/agents/bootstrap (waiting to pair)
- `BootstrapController@status (not_found)` - GET /api/agents/pairing/status (invalid code)
- `BootstrapController@status (pending)` - GET /api/agents/pairing/status (not yet paired)
- `BootstrapController@status (paired)` - GET /api/agents/pairing/status (paired)

#### CalendarController
- `CalendarController@index` - GET /calendar
- `CalendarController@events` - GET /calendar/events

#### DashboardController
- `DashboardController@index` - GET /dashboard

#### DevicePairingController
- `DevicePairingController@pair` - POST /api/devices/pair
- `DevicePairingController@unclaimed` - GET /api/devices/unclaimed

#### DeviceViewController
- `DeviceViewController@show` - GET /devices/{id}

#### FeedbackController
- `FeedbackController@store` - POST /feedback

#### GrowdashWebhookController (umfangreich!)
- `GrowdashWebhookController@log` - POST /api/growdash/log
- `GrowdashWebhookController@event` - POST /api/growdash/event
- `GrowdashWebhookController@status` - GET /api/growdash/status
- `GrowdashWebhookController@waterHistory` - GET /api/growdash/water-history
- `GrowdashWebhookController@tdsHistory` - GET /api/growdash/tds-history
- `GrowdashWebhookController@temperatureHistory` - GET /api/growdash/temperature-history
- `GrowdashWebhookController@sprayEvents` - GET /api/growdash/spray-events
- `GrowdashWebhookController@fillEvents` - GET /api/growdash/fill-events
- `GrowdashWebhookController@logs` - GET /api/growdash/logs
- `GrowdashWebhookController@manualSpray` - POST /api/growdash/manual-spray
- `GrowdashWebhookController@manualFill` - POST /api/growdash/manual-fill

#### ShellySyncController
- `ShellySyncController@setup` - POST /devices/{id}/shelly/setup
- `ShellySyncController@update` - POST /devices/{id}/shelly/update
- `ShellySyncController@remove` - POST /devices/{id}/shelly/remove
- `ShellySyncController@control` - POST /devices/{id}/shelly/control

#### ArduinoCompileController
- `ArduinoCompileController@compile` - POST /scripts/{id}/compile
- `ArduinoCompileController@upload` - POST /scripts/{id}/upload
- `ArduinoCompileController@compileAndUpload` - POST /scripts/{id}/compile-upload
- `ArduinoCompileController@status` - GET /scripts/{id}/status
- `ArduinoCompileController@listDevices` - GET /devices/online
- `ArduinoCompileController@checkCommandStatus` - GET /commands/{id}/status
- `ArduinoCompileController@getPorts` - GET /devices/{id}/ports

## Test durchführen

1. Container starten
2. Log-Datei leeren: `rm storage/logs/laravel.log`
3. Test-Suite ausführen (wird noch erstellt)
4. Logs auswerten

## Logs auswerten

```bash
# Alle getrackten Endpoints
grep "ENDPOINT_TRACKED" storage/logs/laravel.log

# Nach Häufigkeit sortiert
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | sort | uniq -c | sort -rn

# Nur bestimmten Controller auswerten
grep "ENDPOINT_TRACKED.*CommandController" storage/logs/laravel.log | sort | uniq -c
```

## Erwartete ungenutzte Endpoints

Basierend auf der Architektur sollten vermutlich **nicht** genutzt werden:
- GrowdashWebhookController-Methoden (alte Webhook-Integration)
- Einige ArduinoCompileController-Methoden (abhängig von Frontend)
- ShellySyncController-Methoden (optional feature)
- Manche history-Endpoints

## Ergebnisse dokumentieren

Nach dem Test eine RESULTS.md Datei erstellen mit:
1. Liste aller getrackten Endpoints
2. Häufigkeit der Nutzung
3. Unused Endpoints
4. Funktionen die zusammengefasst werden können
