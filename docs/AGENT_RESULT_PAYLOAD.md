# Agent Result Payload Format

## Problem

Agent meldet Command-Result, aber Laravel erhält keine Error-Details (`error`, `output` Felder).

## Lösung

Python-Agent MUSS das Result mit vollständigem Payload senden:

### ✅ Korrektes Format

```python
# Bei Kompilierungsfehler:
result = {
    'status': 'failed',
    'error': '''
/tmp/arduino_sketch_w4tdfrqu/arduino_sketch_w4tdfrqu.ino: In function 'void blinkLong()':
/tmp/arduino_sketch_w4tdfrqu/arduino_sketch_w4tdfrqu.ino:21:9: error: 'LO' was not declared in this scope
   delay(LO LONG_ON);
         ^~
''',
    'output': result.stdout  # Full compiler output
}
```

### ❌ Falsches Format (aktuell)

```python
result = {
    'status': 'failed',
    'message': 'Kompilierung fehlgeschlagen'
}
# ❌ FEHLT: error, output Felder!
```

## Implementation

### In `agent.py` - handle_arduino_compile()

```python
def handle_arduino_compile(self, command_data: dict) -> dict:
    """Handle arduino_compile command"""
    code = command_data.get('code')
    fqbn = command_data.get('board', 'arduino:avr:nano')
    
    sketch_dir = None
    try:
        # Create sketch
        sketch_dir = Path(tempfile.mkdtemp(prefix="arduino_sketch_"))
        sketch_file = sketch_dir / f"{sketch_dir.name}.ino"
        sketch_file.write_text(code)
        
        logger.info(f"Kompiliere Sketch: {sketch_file} für Board: {fqbn}")
        
        # Compile
        cmd = [
            self.config.arduino_cli_path,
            'compile',
            '--fqbn', fqbn,
            str(sketch_dir)
        ]
        
        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=60
        )
        
        if result.returncode == 0:
            logger.info("✅ Sketch erfolgreich kompiliert")
            return {
                'status': 'completed',
                'output': result.stdout
            }
        else:
            # ✅ WICHTIG: Beide error und output mitsenden!
            error_msg = result.stderr + '\n' + result.stdout
            logger.error(f"❌ Kompilierung fehlgeschlagen:\n{error_msg}")
            return {
                'status': 'failed',
                'error': error_msg,        # ← WICHTIG: error Feld
                'output': result.stdout    # ← WICHTIG: output Feld
            }
    
    except subprocess.TimeoutExpired:
        return {
            'status': 'failed',
            'error': 'Compilation timeout (>60s)'
        }
    except Exception as e:
        logger.exception("Compilation error")
        return {
            'status': 'failed',
            'error': str(e)
        }
    finally:
        if sketch_dir and sketch_dir.exists():
            shutil.rmtree(sketch_dir, ignore_errors=True)
```

### In `agent.py` - report_command_result()

Stelle sicher, dass LaravelClient das komplette Result-Dictionary sendet:

```python
def report_command_result(self, command_id: int, result: dict) -> bool:
    """Report command result to Laravel"""
    try:
        # ✅ Sende das KOMPLETTE result dict
        response = self.laravel_client.post(
            f'/commands/{command_id}/result',
            data={
                'status': result.get('status'),
                'result_message': result.get('message', ''),
                'output': result.get('output', ''),     # ← Agent gibt output
                'error': result.get('error', ''),       # ← Agent gibt error
                'stdout': result.get('stdout', ''),
                'stderr': result.get('stderr', '')
            }
        )
        
        if response.status_code != 200:
            logger.error(f"Result-Report fehlgeschlagen: {response.status_code}")
            return False
        
        logger.info(f"✅ Result gemeldet: {command_id} -> {result['status']}")
        return True
        
    except Exception as e:
        logger.exception(f"Fehler beim Result-Report: {e}")
        return False
```

## Teste das

### 1. Agent-Logs prüfen

```bash
# Sollte zeigen:
2025-12-04 14:00:20 - INFO - ✅ Sketch erfolgreich kompiliert
# ODER bei Fehler:
2025-12-04 14:00:25 - ERROR - ❌ Kompilierung fehlgeschlagen:
error: 'LO' was not declared...
```

### 2. Laravel-Logs prüfen

```bash
php artisan tail

# Sollte zeigen:
[2025-12-04 14:00:25] local.INFO: Command status updated [{"command_id":28,"status":"failed",...}]
```

### 3. Frontend-Test

Kompilieren mit Fehler → Error-Modal sollte jetzt:
- ❌ Original-Error anzeigen
- 🤖 LLM-Analyse durchführen
- ✅ Fix anbieten

## Checkliste für Python-Agent

- [ ] `handle_arduino_compile()` gibt `error` + `output` zurück
- [ ] `handle_arduino_upload()` gibt `error` + `output` zurück
- [ ] `handle_arduino_compile_upload()` gibt `error` + `output` zurück
- [ ] `report_command_result()` sendet **alle** Felder an Laravel
- [ ] Agent-Logs zeigen vollständige Error-Messages

---

**Ohne diese Änderungen:** Frontend hat keine Fehler-Daten → LLM kann nicht analysieren

**Mit diesen Änderungen:** Frontend zeigt Error + LLM-Fix ✅
