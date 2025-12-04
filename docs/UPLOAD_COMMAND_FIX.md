# Arduino Upload Command Fix

## Problem

User berichtet: "flashen erzeugt keine Reaktion"

## Root Cause

Python-Agent hat keinen Handler für `arduino_upload` Command-Type registriert.

## Lösung

### 1. Handler implementieren (`agent.py`)

```python
def handle_arduino_upload(self, command_data: dict) -> dict:
    """Handle arduino_upload command - Flash pre-compiled binary"""
    port = command_data.get('port')
    fqbn = command_data.get('board', 'arduino:avr:nano')
    code = command_data.get('code')  # Laravel sendet code mit
    
    if not port:
        return {'status': 'failed', 'error': 'Port not specified'}
    
    if not code:
        return {'status': 'failed', 'error': 'No code provided'}
    
    logger.info(f"Upload Sketch zu Port: {port} (Board: {fqbn})")
    
    sketch_dir = None
    try:
        # Sketch-Datei erstellen
        sketch_dir = Path(tempfile.mkdtemp(prefix="arduino_upload_"))
        sketch_file = sketch_dir / f"{sketch_dir.name}.ino"
        sketch_file.write_text(code)
        
        # Kompilieren + Upload in einem Schritt
        cmd = [
            self.config.arduino_cli_path,
            'compile',
            '--upload',
            '--fqbn', fqbn,
            '--port', port,
            str(sketch_dir)
        ]
        
        logger.info(f"Ausführe: {' '.join(cmd)}")
        
        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=120  # Upload kann lange dauern
        )
        
        if result.returncode == 0:
            logger.info("✅ Upload erfolgreich")
            return {
                'status': 'completed',
                'output': result.stdout
            }
        else:
            logger.error(f"❌ Upload fehlgeschlagen:\n{result.stderr}")
            return {
                'status': 'failed',
                'error': result.stderr,
                'output': result.stdout
            }
            
    except subprocess.TimeoutExpired:
        logger.error("Upload timeout (>120s)")
        return {'status': 'failed', 'error': 'Upload timeout - Board antwortet nicht'}
    except Exception as e:
        logger.exception("Upload-Fehler")
        return {'status': 'failed', 'error': str(e)}
    finally:
        # Cleanup
        if sketch_dir and sketch_dir.exists():
            shutil.rmtree(sketch_dir, ignore_errors=True)
```

### 2. In `command_loop()` registrieren

```python
def command_loop(self):
    """Poll Laravel for pending commands"""
    while self.running:
        time.sleep(self.config.command_poll_interval)
        
        try:
            commands = self.laravel_client.get_pending_commands()
            
            if not commands:
                continue
            
            logger.info(f"Empfangene Befehle: {len(commands)}")
            
            for cmd in commands:
                cmd_id = cmd.get('id')
                cmd_type = cmd.get('type')
                params = cmd.get('params', {})
                
                logger.info(f"Führe Befehl aus: {cmd_type}")
                
                # Route command to handler
                if cmd_type == 'arduino_compile':
                    result = self.handle_arduino_compile(params)
                elif cmd_type == 'arduino_upload':
                    result = self.handle_arduino_upload(params)  # ← ADD THIS
                elif cmd_type == 'arduino_compile_upload':
                    result = self.handle_arduino_compile_upload(params)
                elif cmd_type == 'serial_command':
                    result = self.handle_serial_command(params)
                else:
                    result = {
                        'status': 'failed',
                        'error': f'Unknown command type: {cmd_type}'
                    }
                
                # Report result back to Laravel
                self.laravel_client.report_command_result(cmd_id, result)
                logger.info(f"Befehlsergebnis gemeldet: {cmd_id} -> {result['status']}")
                
        except Exception as e:
            logger.error(f"Fehler in command_loop: {e}")
```

### 3. Laravel Controller - Code mitsenden

**Problem:** Upload braucht den Code, aber Laravel sendet nur `script_id`.

**Fix in `ArduinoCompileController.php`:**

```php
public function upload(Request $request, DeviceScript $script)
{
    // ... validation ...
    
    $command = Command::create([
        'device_id' => $device->id,
        'created_by_user_id' => Auth::id(),
        'type' => 'arduino_upload',
        'params' => [
            'script_id' => $script->id,
            'script_name' => $script->name,
            'code' => $script->code,  // ← ADD THIS
            'port' => $port,
            'board' => $board,
            'target_device_id' => $request->input('target_device_id'),
        ],
        'status' => 'pending',
    ]);
    
    // ...
}
```

## Testing

### 1. Upload Command testen

```bash
# Agent-Terminal beobachten
tail -f /path/to/agent/logs/agent.log

# In Laravel: Script flashen
# → Agent sollte zeigen:
# "Führe Befehl aus: arduino_upload"
# "Upload Sketch zu Port: /dev/ttyACM0 (Board: arduino:avr:nano)"
# "✅ Upload erfolgreich"
```

### 2. Häufige Fehler

| Fehler | Ursache | Lösung |
|--------|---------|--------|
| "Port not specified" | Frontend sendet keinen Port | Port-Dropdown prüfen |
| "No code provided" | Laravel sendet Code nicht mit | Controller-Fix anwenden |
| "Upload timeout" | Board antwortet nicht | USB-Kabel prüfen, Reset-Button |
| "permission denied: /dev/ttyACM0" | User nicht in dialout Gruppe | `sudo usermod -a -G dialout $USER` |

## Deployment

### Update Python Agent

```bash
cd ~/growdash
git pull
./grow_start.sh  # Neustart mit aktualisierten Handlern
```

### Verify

```bash
# Check ob Handler existiert
grep -n "handle_arduino_upload" agent.py

# Should show method definition
```

## Alternative: compile_upload verwenden

Statt separatem Upload kann auch `arduino_compile_upload` verwendet werden:

```php
// Frontend: "Compile & Upload" Button
Route::post('/scripts/{script}/compile-upload', [ArduinoCompileController::class, 'compileAndUpload']);
```

**Vorteil:** Nur ein Befehl, kein Status-Check zwischen compile/upload.

---

**Status:** 🔧 Fix Ready  
**Priorität:** HIGH  
**Deployment:** Needs Python Agent Update
