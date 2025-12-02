# GrowDash Capabilities - Quick Reference

## JSON Schema (Agent → Laravel)

```json
{
  "capabilities": {
    "board": {
      "id": "arduino_uno",
      "vendor": "Arduino",
      "model": "UNO R3",
      "connection": "serial",
      "firmware": "v1.0.0"
    },
    "sensors": [
      {
        "id": "water_level",
        "display_name": "Water Level",
        "category": "environment",
        "unit": "%",
        "value_type": "float",
        "range": [0, 100],
        "min_interval": 10,
        "critical": true
      }
    ],
    "actuators": [
      {
        "id": "spray_pump",
        "display_name": "Spray Pump",
        "category": "irrigation",
        "command_type": "duration",
        "params": [
          {"name": "seconds", "type": "int", "min": 1, "max": 120}
        ],
        "min_interval": 30,
        "critical": true
      }
    ]
  }
}
```

## Enums

**category**: `environment`, `nutrients`, `lighting`, `irrigation`, `system`, `custom`  
**connection**: `serial`, `wifi`, `ethernet`, `bluetooth`  
**value_type** / **type**: `int`, `float`, `string`, `bool`  
**command_type**: `toggle`, `duration`, `target`, `custom`

## Validation Rules

### Sensors
- ✅ `range` validates telemetry value
- ✅ `unit` must match in telemetry payload
- ✅ `value_type` enforced (int/float/string/bool)
- ⏱️ `min_interval` enforced by agent (not backend)

### Actuators
- ✅ `params` must all be present in command
- ✅ `min`/`max` constraints validated
- ✅ `type` enforced for each param
- ⏱️ `min_interval` enforced by agent

## API Endpoints

### Send Capabilities
```
POST /api/growdash/agent/capabilities
Headers: X-Device-ID, X-Device-Token
Body: { "capabilities": {...} }
```

### Send Telemetry (Validated)
```
POST /api/growdash/agent/telemetry
Body: {
  "readings": [
    {"sensor_key": "water_level", "value": 75.5, "unit": "%", "measured_at": "..."}
  ]
}
Response: { "inserted_count": 1, "skipped_count": 0, "skipped": [] }
```

### Send Command (Validated)
```
POST /api/growdash/devices/{device}/commands
Body: {
  "type": "spray_pump",
  "params": {"seconds": 10}
}
Response (422 if invalid): {
  "errors": {"seconds": "Invalid value for parameter: seconds"}
}
```

## Device Model Helpers

```php
$device->getCapabilitiesDTO(); // DeviceCapabilities DTO
$device->getSensorById('water_level'); // SensorCapability | null
$device->getActuatorById('spray_pump'); // ActuatorCapability | null
$device->getSensorsByCategory('environment'); // SensorCapability[]
$device->getAllCategories(); // ['environment', 'irrigation', ...]
$device->getCriticalSensors(); // SensorCapability[]
$device->validateTelemetryReading('water_level', 75.5, '%'); // bool
$device->validateCommandParams('spray_pump', ['seconds' => 10]); // array (errors)
```

## Livewire Component

```blade
<livewire:devices.commands :device="$device" />
```

**Features:**
- Auto-generated category tabs
- Dynamic forms per actuator
- Type-specific inputs (number, checkbox, text)
- Min/max constraints
- Online/offline status
- Recent commands history

## Python Agent (Pydantic)

```python
from pydantic import BaseModel

class SensorCapability(BaseModel):
    id: str
    display_name: str
    category: str
    unit: str
    value_type: str
    range: Optional[List[Union[int, float]]] = None
    min_interval: Optional[int] = None
    critical: bool = False

capabilities = DeviceCapabilities(
    board=BoardInfo(...),
    sensors=[SensorCapability(...)],
    actuators=[ActuatorCapability(...)]
)

# Send to Laravel
requests.post("/api/growdash/agent/capabilities", json={"capabilities": capabilities.dict()})
```

## Documentation Files

- **AGENT_API_GUIDE.md** - API reference + capabilities schema
- **PYTHON_AGENT_CAPABILITIES.md** - Pydantic models + agent implementation
- **CAPABILITIES_IMPLEMENTATION.md** - Complete implementation summary
- **This file** - Quick reference

---

**Version**: 1.0.0  
**Updated**: 2025-12-02
