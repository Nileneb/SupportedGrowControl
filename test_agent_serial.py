#!/usr/bin/env python3
"""
Test script to simulate agent handling serial commands
This demonstrates what the agent should do when receiving serial_command type
"""

import requests
import time
import sys

# Configuration
BASE_URL = "https://grow.linn.games"
DEVICE_ID = "your-device-id-here"  # Replace with actual device ID
AGENT_TOKEN = "your-agent-token-here"  # Replace with actual token

def get_pending_commands():
    """Poll for pending commands"""
    response = requests.get(
        f"{BASE_URL}/api/growdash/agent/commands/pending",
        headers={
            "X-Device-ID": DEVICE_ID,
            "X-Device-Token": AGENT_TOKEN
        }
    )
    response.raise_for_status()
    return response.json()["commands"]

def send_command_result(command_id, status, message):
    """Send command result back to backend"""
    response = requests.post(
        f"{BASE_URL}/api/growdash/agent/commands/{command_id}/result",
        headers={
            "X-Device-ID": DEVICE_ID,
            "X-Device-Token": AGENT_TOKEN,
            "Content-Type": "application/json"
        },
        json={
            "status": status,
            "result_message": message
        }
    )
    print(f"Result sent: {response.status_code} - {response.text}")
    return response

def execute_serial_command(command_string):
    """
    Execute serial command on Arduino
    This is where you would send to Arduino via Serial and read response
    """
    # TODO: Replace this with actual serial communication
    # import serial
    # ser = serial.Serial('/dev/ttyUSB0', 115200, timeout=2)
    # ser.write(f"{command_string}\n".encode())
    # response = ser.readline().decode().strip()
    # return response
    
    # For now, simulate Arduino responses
    if command_string == "status":
        return "Arduino Status: Running OK | Uptime: 12345s | Free RAM: 1234"
    elif command_string == "tds":
        return "TDS: 850 ppm"
    elif command_string.startswith("spray"):
        return f"Spraying for {command_string.split()[1] if len(command_string.split()) > 1 else '0'}ms"
    else:
        return f"Unknown command: {command_string}"

def process_commands():
    """Main command processing loop"""
    commands = get_pending_commands()
    print(f"Found {len(commands)} pending commands")
    
    for cmd in commands:
        print(f"\nProcessing command {cmd['id']}: {cmd['type']}")
        
        # Handle serial_command type specially
        if cmd['type'] == 'serial_command':
            try:
                # Extract command string from params
                command_string = cmd['params'].get('command', '')
                
                if not command_string:
                    send_command_result(cmd['id'], 'failed', 'No command string provided')
                    continue
                
                print(f"  Executing: {command_string}")
                
                # Mark as executing
                send_command_result(cmd['id'], 'executing', f'Sending: {command_string}')
                
                # Execute on Arduino and get response
                arduino_response = execute_serial_command(command_string)
                
                print(f"  Response: {arduino_response}")
                
                # Send successful result with Arduino's response
                send_command_result(cmd['id'], 'completed', arduino_response)
                
            except Exception as e:
                print(f"  Error: {e}")
                send_command_result(cmd['id'], 'failed', str(e))
        
        # Handle regular actuator commands
        else:
            # This would be your existing actuator logic
            print(f"  Regular actuator command: {cmd['type']}")
            # execute_actuator(cmd['type'], cmd['params'])

if __name__ == '__main__':
    print("Agent Serial Command Test")
    print("=" * 50)
    
    if DEVICE_ID == "your-device-id-here":
        print("\n⚠️  Please edit this file and set DEVICE_ID and AGENT_TOKEN")
        sys.exit(1)
    
    try:
        process_commands()
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
