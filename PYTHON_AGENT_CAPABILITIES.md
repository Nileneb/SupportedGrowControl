# GrowDash Capabilities - Python Agent Implementation Guide

## Pydantic Models for Capabilities

Use these Pydantic models in your Python agent to ensure type safety and validation.

```python
from typing import List, Optional, Union
from pydantic import BaseModel, Field

class BoardInfo(BaseModel):
    id: str = Field(..., description="Board type ID (e.g., 'arduino_uno')")
    vendor: str = Field(..., description="Manufacturer name")
    model: str = Field(..., description="Board model")
    connection: str = Field(..., description="Connection type: serial, wifi, ethernet, bluetooth")
    firmware: Optional[str] = Field(None, description="Firmware version string")

class SensorCapability(BaseModel):
    id: str = Field(..., description="Unique sensor ID (used as sensor_key in telemetry)")
    display_name: str = Field(..., description="Human-readable name")
    category: str = Field(..., description="Category: environment, nutrients, lighting, irrigation, system, custom")
    unit: str = Field(..., description="Measurement unit")
    value_type: str = Field(..., description="Data type: float, int, string, bool")
    range: Optional[List[Union[int, float]]] = Field(None, description="Optional [min, max] range")
    min_interval: Optional[int] = Field(None, description="Minimum seconds between readings")
    critical: bool = Field(False, description="If true, prioritize for alerts")

    def validate_value(self, value) -> bool:
        """Validate a reading value against sensor spec"""
        # Type validation
        if self.value_type == "float" and not isinstance(value, (int, float)):
            return False
        if self.value_type == "int" and not isinstance(value, int):
            return False
        
        # Range validation
        if self.range is not None:
            min_val, max_val = self.range
            if value < min_val or value > max_val:
                return False
        
        return True

class ActuatorParam(BaseModel):
    name: str = Field(..., description="Parameter key")
    type: str = Field(..., description="Data type: int, float, string, bool")
    min: Optional[Union[int, float]] = Field(None, description="Minimum value")
    max: Optional[Union[int, float]] = Field(None, description="Maximum value")
    unit: Optional[str] = Field(None, description="Unit label")

    def validate_value(self, value) -> bool:
        """Validate a parameter value against spec"""
        # Type validation
        if self.type == "int" and not isinstance(value, int):
            return False
        if self.type == "float" and not isinstance(value, (int, float)):
            return False
        if self.type == "string" and not isinstance(value, str):
            return False
        if self.type == "bool" and not isinstance(value, bool):
            return False
        
        # Range validation for numeric types
        if self.type in ["int", "float"]:
            if self.min is not None and value < self.min:
                return False
            if self.max is not None and value > self.max:
                return False
        
        return True

class ActuatorCapability(BaseModel):
    id: str = Field(..., description="Unique actuator ID (used as command type)")
    display_name: str = Field(..., description="Human-readable name")
    category: str = Field(..., description="Category: same as sensors")
    command_type: str = Field(..., description="Command style: toggle, duration, target, custom")
    params: List[ActuatorParam] = Field(default_factory=list, description="Parameter definitions")
    min_interval: Optional[int] = Field(None, description="Minimum seconds between commands")
    critical: bool = Field(False, description="If true, highlight in UI")

    def validate_params(self, provided_params: dict) -> dict:
        """Validate command params against actuator spec
        
        Returns:
            dict: Empty if valid, otherwise error messages keyed by param name
        """
        errors = {}
        
        for param_def in self.params:
            if param_def.name not in provided_params:
                errors[param_def.name] = f"Missing required parameter: {param_def.name}"
            elif not param_def.validate_value(provided_params[param_def.name]):
                errors[param_def.name] = f"Invalid value for parameter: {param_def.name}"
        
        return errors

class DeviceCapabilities(BaseModel):
    board: Optional[BoardInfo] = Field(None, description="Board information")
    sensors: List[SensorCapability] = Field(default_factory=list, description="Sensor definitions")
    actuators: List[ActuatorCapability] = Field(default_factory=list, description="Actuator definitions")

    def get_sensor_by_id(self, sensor_id: str) -> Optional[SensorCapability]:
        """Find sensor by ID"""
        for sensor in self.sensors:
            if sensor.id == sensor_id:
                return sensor
        return None

    def get_actuator_by_id(self, actuator_id: str) -> Optional[ActuatorCapability]:
        """Find actuator by ID"""
        for actuator in self.actuators:
            if actuator.id == actuator_id:
                return actuator
        return None

    def get_sensors_by_category(self, category: str) -> List[SensorCapability]:
        """Get all sensors in a category"""
        return [s for s in self.sensors if s.category == category]

    def get_actuators_by_category(self, category: str) -> List[ActuatorCapability]:
        """Get all actuators in a category"""
        return [a for a in self.actuators if a.category == category]

    def get_all_categories(self) -> List[str]:
        """Get all unique categories"""
        categories = set()
        for sensor in self.sensors:
            categories.add(sensor.category)
        for actuator in self.actuators:
            categories.add(actuator.category)
        return sorted(list(categories))
```

## Agent Implementation Example

```python
import time
from datetime import datetime
from typing import Dict
import requests

class HardwareAgent:
    def __init__(self, device_id: str, agent_token: str, base_url: str):
        self.device_id = device_id
        self.agent_token = agent_token
        self.base_url = base_url
        self.capabilities: Optional[DeviceCapabilities] = None
        self.last_sent_at: Dict[str, float] = {}  # sensor_id -> timestamp
        self.last_command_at: Dict[str, float] = {}  # actuator_id -> timestamp

    def build_capabilities(self) -> DeviceCapabilities:
        """Build capabilities based on connected hardware"""
        return DeviceCapabilities(
            board=BoardInfo(
                id="arduino_uno",
                vendor="Arduino",
                model="UNO R3",
                connection="serial",
                firmware="growdash-unified-v1.0.0"
            ),
            sensors=[
                SensorCapability(
                    id="water_level",
                    display_name="Water Level",
                    category="environment",
                    unit="%",
                    value_type="float",
                    range=[0, 100],
                    min_interval=10,
                    critical=True
                ),
                SensorCapability(
                    id="tds",
                    display_name="TDS",
                    category="nutrients",
                    unit="ppm",
                    value_type="int",
                    range=None,
                    min_interval=60,
                    critical=False
                ),
            ],
            actuators=[
                ActuatorCapability(
                    id="spray_pump",
                    display_name="Spray Pump",
                    category="irrigation",
                    command_type="duration",
                    params=[
                        ActuatorParam(name="seconds", type="int", min=1, max=120)
                    ],
                    min_interval=30,
                    critical=True
                ),
                ActuatorCapability(
                    id="fill_valve",
                    display_name="Fill Valve",
                    category="irrigation",
                    command_type="target",
                    params=[
                        ActuatorParam(name="target_level", type="float", min=0, max=100, unit="%")
                    ],
                    min_interval=60,
                    critical=True
                ),
            ]
        )

    def send_capabilities(self):
        """Send capabilities to Laravel backend"""
        self.capabilities = self.build_capabilities()
        
        response = requests.post(
            f"{self.base_url}/api/growdash/agent/capabilities",
            headers={
                "X-Device-ID": self.device_id,
                "X-Device-Token": self.agent_token,
                "Content-Type": "application/json"
            },
            json={"capabilities": self.capabilities.dict()}
        )
        response.raise_for_status()
        return response.json()

    def collect_and_send_telemetry(self):
        """Collect telemetry from hardware and send to backend"""
        if not self.capabilities:
            return
        
        now = time.time()
        readings = []
        
        for sensor in self.capabilities.sensors:
            # Check min_interval
            last_sent = self.last_sent_at.get(sensor.id, 0)
            if sensor.min_interval and (now - last_sent) < sensor.min_interval:
                continue
            
            # Read sensor value (implement per your hardware)
            value = self.read_sensor(sensor.id)
            
            # Validate value
            if not sensor.validate_value(value):
                print(f"Invalid value {value} for sensor {sensor.id}")
                continue
            
            readings.append({
                "sensor_key": sensor.id,
                "value": value,
                "unit": sensor.unit,
                "measured_at": datetime.utcnow().isoformat() + "Z"
            })
            
            self.last_sent_at[sensor.id] = now
        
        if readings:
            response = requests.post(
                f"{self.base_url}/api/growdash/agent/telemetry",
                headers={
                    "X-Device-ID": self.device_id,
                    "X-Device-Token": self.agent_token,
                    "Content-Type": "application/json"
                },
                json={"readings": readings}
            )
            response.raise_for_status()

    def poll_and_execute_commands(self):
        """Poll for pending commands and execute them"""
        response = requests.get(
            f"{self.base_url}/api/growdash/agent/commands/pending",
            headers={
                "X-Device-ID": self.device_id,
                "X-Device-Token": self.agent_token
            }
        )
        response.raise_for_status()
        
        commands = response.json()["commands"]
        
        for cmd in commands:
            actuator = self.capabilities.get_actuator_by_id(cmd["type"])
            
            if not actuator:
                self.report_command_result(cmd["id"], "failed", f"Unknown actuator: {cmd['type']}")
                continue
            
            # Validate params
            param_errors = actuator.validate_params(cmd["params"])
            if param_errors:
                self.report_command_result(cmd["id"], "failed", f"Invalid params: {param_errors}")
                continue
            
            # Check min_interval
            now = time.time()
            last_cmd = self.last_command_at.get(actuator.id, 0)
            if actuator.min_interval and (now - last_cmd) < actuator.min_interval:
                self.report_command_result(cmd["id"], "failed", "Min interval not elapsed")
                continue
            
            # Execute command
            try:
                self.report_command_result(cmd["id"], "executing", "Starting execution")
                result = self.execute_actuator(actuator.id, cmd["params"])
                self.report_command_result(cmd["id"], "completed", result)
                self.last_command_at[actuator.id] = now
            except Exception as e:
                self.report_command_result(cmd["id"], "failed", str(e))

    def report_command_result(self, command_id: int, status: str, message: str):
        """Report command result to backend"""
        requests.post(
            f"{self.base_url}/api/growdash/agent/commands/{command_id}/result",
            headers={
                "X-Device-ID": self.device_id,
                "X-Device-Token": self.agent_token,
                "Content-Type": "application/json"
            },
            json={
                "status": status,
                "result_message": message
            }
        )

    def read_sensor(self, sensor_id: str):
        """Read sensor value from hardware (implement per your setup)"""
        # Example: read from serial, I2C, GPIO, etc.
        pass

    def execute_actuator(self, actuator_id: str, params: dict) -> str:
        """Execute actuator command on hardware (implement per your setup)"""
        # Example: send serial command, toggle GPIO, etc.
        pass

    def run(self):
        """Main agent loop"""
        # Send capabilities once on startup
        self.send_capabilities()
        
        while True:
            try:
                # Send telemetry (every 10s, min_interval enforced per sensor)
                self.collect_and_send_telemetry()
                
                # Poll commands (every 5s)
                self.poll_and_execute_commands()
                
                # Heartbeat (every 30s, tracked separately)
                # ...
                
                time.sleep(5)
            except Exception as e:
                print(f"Error in main loop: {e}")
                time.sleep(10)
```

## Board-Specific Capabilities

Create board modules for different hardware configurations:

```python
# boards/arduino_uno.py
def get_capabilities() -> DeviceCapabilities:
    return DeviceCapabilities(
        board=BoardInfo(
            id="arduino_uno",
            vendor="Arduino",
            model="UNO R3",
            connection="serial",
            firmware="growdash-v1.0.0"
        ),
        sensors=[
            # UNO-specific sensors
        ],
        actuators=[
            # UNO-specific actuators
        ]
    )

# boards/esp32.py
def get_capabilities() -> DeviceCapabilities:
    return DeviceCapabilities(
        board=BoardInfo(
            id="esp32",
            vendor="Espressif",
            model="ESP32-WROOM",
            connection="wifi",
            firmware="growdash-esp32-v1.0.0"
        ),
        sensors=[
            # ESP32-specific sensors (may include WiFi signal, etc.)
        ],
        actuators=[
            # ESP32-specific actuators
        ]
    )
```

Then in your agent initialization:

```python
# Detect board type (via arduino-cli, config file, etc.)
board_type = detect_board_type()

# Load capabilities for detected board
if board_type == "arduino_uno":
    from boards.arduino_uno import get_capabilities
elif board_type == "esp32":
    from boards.esp32 import get_capabilities

capabilities = get_capabilities()
```

---

**Status**: ✅ Complete Implementation Guide  
**Version**: 1.0.0  
**Last Updated**: 2025-12-02
