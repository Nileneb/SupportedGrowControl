# Arduino Control via Laravel API

## Architektur

```
┌─────────────────┐      ┌─────────────────┐      ┌──────────────────┐      ┌─────────────┐
│  Laravel Web    │─────▶│  Laravel API    │─────▶│  commands table  │◀────▶│ Python Agent│
│  (Frontend)     │      │  (Backend)      │      │  (Database)      │      │ + Arduino   │
└─────────────────┘      └─────────────────┘      └──────────────────┘      └─────────────┘
```

**Flow:**
1. User klickt im Frontend auf "Send Command" (z.B. "STATUS")
2. Frontend sendet POST zu `/api/growdash/devices/{device}/commands`
3. Laravel erstellt Command-Eintrag mit `status='pending'`
4. Python Agent pollt `/api/growdash/agent/commands/pending` (alle 5-10s)
5. Agent empfängt Command, sendet es via Serial ans Arduino
6. Arduino antwortet über Serial
7. Agent sendet Ergebnis zurück: POST `/api/growdash/agent/commands/{id}/result`
8. Command wird als `completed` markiert

---

## Laravel API Endpoints

### 1. Command senden (Frontend → Backend)

**Endpoint:** `POST /api/growdash/devices/{device}/commands`  
**Auth:** Sanctum Bearer Token (eingeloggter User)  
**Headers:**
```
Authorization: Bearer <sanctum_token>
Content-Type: application/json
```

**Body:**
```json
{
  "type": "serial_command",
  "params": {
    "command": "STATUS"
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Command queued successfully",
  "command": {
    "id": 42,
    "type": "serial_command",
    "params": {"command": "STATUS"},
    "status": "pending",
    "created_at": "2025-12-02T10:30:00.000000Z"
  }
}
```

**Fehler (400 - Device offline):**
```json
{
  "success": false,
  "message": "Device is not online",
  "device_status": "paired"
}
```

---

### 2. Command-Historie abrufen (Frontend)

**Endpoint:** `GET /api/growdash/devices/{device}/commands?limit=50`  
**Auth:** Sanctum Bearer Token  

**Response:**
```json
{
  "success": true,
  "commands": [
    {
      "id": 42,
      "type": "serial_command",
      "params": {"command": "STATUS"},
      "status": "completed",
      "result_message": "Temperature: 23.5°C, Humidity: 65%",
      "created_by": "John Doe",
      "created_at": "2025-12-02T10:30:00.000000Z",
      "completed_at": "2025-12-02T10:30:05.000000Z"
    }
  ],
  "count": 1
}
```

---

### 3. Pending Commands abholen (Python Agent)

**Endpoint:** `GET /api/growdash/agent/commands/pending`  
**Auth:** Device Headers  
**Headers:**
```
X-Device-ID: <device_public_id>
X-Device-Token: <agent_token_plaintext>
```

**Response:**
```json
{
  "success": true,
  "commands": [
    {
      "id": 42,
      "type": "serial_command",
      "params": {"command": "STATUS"},
      "created_at": "2025-12-02T10:30:00.000000Z"
    }
  ]
}
```

---

### 4. Command-Ergebnis zurückmelden (Python Agent)

**Endpoint:** `POST /api/growdash/agent/commands/{id}/result`  
**Auth:** Device Headers  

**Body:**
```json
{
  "status": "completed",
  "result_message": "Temperature: 23.5°C, Humidity: 65%"
}
```

**Status-Werte:**
- `executing` - Command wird gerade ausgeführt
- `completed` - Erfolgreich abgeschlossen
- `failed` - Fehlgeschlagen

**Response:**
```json
{
  "success": true,
  "message": "Command status updated",
  "command": {
    "id": 42,
    "status": "completed",
    "completed_at": "2025-12-02T10:30:05.000000Z"
  }
}
```

---

## Frontend-Integration

### Beispiel: Serial Command senden

**In Livewire/Alpine.js:**
```javascript
async function sendSerialCommand() {
    const deviceId = '0709c4d2-14a9-4716-a7e4-663bb8acaa66';
    const command = document.getElementById('serial-command').value;
    
    const response = await fetch(`/api/growdash/devices/${deviceId}/commands`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            // Sanctum authentifiziert automatisch via Session-Cookie
        },
        body: JSON.stringify({
            type: 'serial_command',
            params: { command: command }
        })
    });
    
    const data = await response.json();
    
    if (data.success) {
        console.log('Command queued:', data.command.id);
        // Poll für Ergebnis oder WebSocket-Event abwarten
    } else {
        console.error('Failed:', data.message);
    }
}
```

---

## Python Agent Integration

Der Python Agent muss nach der **Registration** die Credentials speichern:

```python
# Nach erfolgreicher Registration:
device_id = response['device_id']  # z.B. "0709c4d2-14a9-4716-a7e4-663bb8acaa66"
agent_token = response['agent_token']  # PLAINTEXT, z.B. "secret_token_abc123"

# Speichern in .env oder config
os.environ['DEVICE_ID'] = device_id
os.environ['AGENT_TOKEN'] = agent_token
```

**Wichtig:** Der `agent_token` ist der **Klartext-Token** aus der Registration-Response, NICHT der Hash aus der Datenbank!

---

## Command-Typen

Aktuell definiert:
- `serial_command` - Direkter Serial-Befehl ans Arduino
  - Params: `{"command": "STATUS"}`, `{"command": "SPRAY"}`, etc.

Zukünftig erweiterbar:
- `upload_sketch` - Arduino-Code hochladen
- `restart_device` - Device neu starten
- `update_config` - Konfiguration ändern

---

## Testing

### 1. Command via Tinker erstellen
```bash
docker exec supportedgrowcontrol-php-cli-1 php artisan tinker --execute="
\$user = App\Models\User::first();
\$device = \$user->devices->first();
\$cmd = App\Models\Command::create([
    'device_id' => \$device->id,
    'created_by_user_id' => \$user->id,
    'type' => 'serial_command',
    'params' => ['command' => 'STATUS'],
    'status' => 'pending',
]);
echo 'Command ID: ' . \$cmd->id;
"
```

### 2. Pending Commands abrufen (simuliert Agent)
```bash
# Benötigt valide Device-ID und Agent-Token
curl -X GET "https://grow.linn.games/api/growdash/agent/commands/pending" \
  -H "X-Device-ID: 0709c4d2-14a9-4716-a7e4-663bb8acaa66" \
  -H "X-Device-Token: <plaintext_token_from_registration>"
```

### 3. Command als completed markieren
```bash
curl -X POST "https://grow.linn.games/api/growdash/agent/commands/2/result" \
  -H "X-Device-ID: 0709c4d2-14a9-4716-a7e4-663bb8acaa66" \
  -H "X-Device-Token: <plaintext_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed",
    "result_message": "Temperature: 23.5°C"
  }'
```

---

## Nächste Schritte

**Laravel (DONE):**
- ✅ CommandController implementiert
- ✅ API-Routen registriert
- ✅ Device-Auth-Middleware vorhanden
- ✅ Command-Model mit Beziehungen

**Frontend (TODO):**
- Serial Console im Device-Detail erweitern
- Command-Input-Feld funktional machen
- Command-Historie live anzeigen
- WebSocket für Live-Updates (optional)

**Python Agent (TODO - auf Raspberry Pi):**
- Command-Polling-Thread hinzufügen (alle 5-10s)
- Serial-Command-Execution implementieren
- Result-Reporting nach Laravel

---

## Wichtige Hinweise

1. **Device muss online sein**: Commands können nur an Devices mit `status='online'` gesendet werden
2. **Token-Sicherheit**: Der `agent_token` ist sensibel - nur HTTPS verwenden!
3. **Polling-Intervall**: Agent sollte alle 5-10 Sekunden pollen, nicht zu häufig
4. **Command-Queue**: Commands werden FIFO (First-In-First-Out) verarbeitet
5. **Timeout**: Commands sollten nach 60s timeout und als `failed` markiert werden
