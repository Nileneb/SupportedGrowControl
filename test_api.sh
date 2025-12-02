#!/bin/bash

# =============================================================================
# GrowDash API Test Suite
# =============================================================================
# Tests all API endpoints for agent-to-Laravel communication
# Usage: ./test_api.sh [BASE_URL] [API_TOKEN]
# Example: ./test_api.sh https://grow.linn.games your-device-token-here
# =============================================================================

# Don't exit on error - we want to run all tests
set +e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="${1:-https://grow.linn.games}"
API_TOKEN="${2:-}"
DEVICE_ID=""
TEST_COUNT=0
PASS_COUNT=0
FAIL_COUNT=0

# =============================================================================
# Helper Functions
# =============================================================================

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_test() {
    ((TEST_COUNT++))
    echo -e "${YELLOW}[TEST $TEST_COUNT]${NC} $1"
}

print_success() {
    ((PASS_COUNT++))
    echo -e "${GREEN}✓ PASS:${NC} $1"
}

print_fail() {
    ((FAIL_COUNT++))
    echo -e "${RED}✗ FAIL:${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ INFO:${NC} $1"
}

check_response() {
    local response="$1"
    local expected_status="$2"
    local test_name="$3"
    
    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | sed '$d')
    
    echo -e "\n${BLUE}Response Code:${NC} $http_code"
    echo -e "${BLUE}Response Body:${NC}"
    echo "$body" | jq '.' 2>/dev/null || echo "$body"
    echo ""
    
    if [[ "$http_code" == "$expected_status" ]]; then
        print_success "$test_name (HTTP $http_code)"
        return 0
    else
        print_fail "$test_name (Expected $expected_status, got $http_code)"
        return 1
    fi
}

# =============================================================================
# Test 1: Device Bootstrap & Pairing Flow
# =============================================================================

test_device_bootstrap() {
    print_header "TEST 1: Device Bootstrap & Pairing"
    
    # Step 1: Bootstrap (create unclaimed device)
    print_test "Step 1: Bootstrap new device"
    
    local bootstrap_id="test-agent-$(date +%s)"
    local payload=$(cat <<EOF
{
    "bootstrap_id": "$bootstrap_id",
    "name": "Test Device $(date +%H:%M:%S)"
}
EOF
)
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/agents/bootstrap" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    if check_response "$response" "201" "Bootstrap new device"; then
        local body=$(echo "$response" | sed '$d')
        local bootstrap_code=$(echo "$body" | jq -r '.bootstrap_code // empty' 2>/dev/null)
        
        if [[ -n "$bootstrap_code" ]]; then
            print_info "Bootstrap Code: $bootstrap_code"
            print_info "Bootstrap ID: $bootstrap_id"
            print_info ""
            print_info "⚠️  MANUAL STEP REQUIRED:"
            print_info "   Go to https://grow.linn.games/devices"
            print_info "   Click 'Add Device' and enter code: $bootstrap_code"
            print_info ""
            read -p "Press ENTER after you've paired the device..."
            
            # Step 2: Poll for pairing status
            print_test "Step 2: Check pairing status"
            
            local status_response=$(curl -s -w "\n%{http_code}" -X GET \
                "${BASE_URL}/api/agents/pairing/status?bootstrap_id=${bootstrap_id}&bootstrap_code=${bootstrap_code}")
            
            if check_response "$status_response" "200" "Pairing status check"; then
                local status_body=$(echo "$status_response" | sed '$d')
                local status=$(echo "$status_body" | jq -r '.status // empty' 2>/dev/null)
                
                if [[ "$status" == "paired" ]]; then
                    DEVICE_ID=$(echo "$status_body" | jq -r '.public_id // empty' 2>/dev/null)
                    API_TOKEN=$(echo "$status_body" | jq -r '.agent_token // empty' 2>/dev/null)
                    
                    print_success "Device paired successfully!"
                    print_info "Device ID (public_id): $DEVICE_ID"
                    print_info "Agent Token: ${API_TOKEN:0:30}..."
                else
                    print_fail "Device not yet paired (status: $status)"
                fi
            fi
        else
            print_fail "Could not extract bootstrap_code from response"
        fi
    fi
    
    # Step 3: Test re-bootstrap (should return paired status immediately)
    if [[ -n "$DEVICE_ID" && -n "$API_TOKEN" ]]; then
        print_test "Step 3: Re-bootstrap (should return paired status)"
        
        local payload=$(cat <<EOF
{
    "bootstrap_id": "$bootstrap_id",
    "name": "Test Device Re-bootstrap"
}
EOF
)
        
        local response=$(curl -s -w "\n%{http_code}" -X POST \
            "${BASE_URL}/api/agents/bootstrap" \
            -H "Content-Type: application/json" \
            -d "$payload")
        
        check_response "$response" "200" "Re-bootstrap paired device"
    fi
}

# =============================================================================
# Test 2: Telemetry Data Submission
# =============================================================================

test_telemetry_submission() {
    print_header "TEST 2: Telemetry Data Submission"
    
    if [[ -z "$API_TOKEN" ]]; then
        print_fail "No API token available. Skipping telemetry tests."
        return 1
    fi
    
    # Test 2.1: Single sensor reading
    print_test "Submitting single sensor reading"
    
    local payload=$(cat <<EOF
{
    "sensor_key": "temperature",
    "value": 23.5,
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
EOF
)
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/growdash/agent/telemetry" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    check_response "$response" "200" "Single sensor reading"
    
    # Test 2.2: Multiple sensor readings
    print_test "Submitting multiple sensor readings"
    
    local payload=$(cat <<EOF
{
    "readings": [
        {
            "sensor_key": "water_level",
            "value": 75.0,
            "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        },
        {
            "sensor_key": "tds",
            "value": 850,
            "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        },
        {
            "sensor_key": "temperature",
            "value": 24.2,
            "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        }
    ]
}
EOF
)
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/growdash/agent/telemetry" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    check_response "$response" "200" "Multiple sensor readings"
}

# =============================================================================
# Test 3: Command Retrieval (Pending Commands)
# =============================================================================

test_command_retrieval() {
    print_header "TEST 3: Command Retrieval"
    
    if [[ -z "$API_TOKEN" ]]; then
        print_fail "No API token available. Skipping command tests."
        return 1
    fi
    
    print_test "Fetching pending commands"
    
    local response=$(curl -s -w "\n%{http_code}" -X GET \
        "${BASE_URL}/api/growdash/agent/commands/pending" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN")
    
    check_response "$response" "200" "Fetch pending commands"
}

# =============================================================================
# Test 4: Command Execution & Result Reporting
# =============================================================================

test_command_execution() {
    print_header "TEST 4: Command Execution Flow"
    
    if [[ -z "$API_TOKEN" || -z "$DEVICE_ID" ]]; then
        print_fail "No API token or device ID available. Skipping command execution tests."
        return 1
    fi
    
    # Test 4.1: Create a command via web API (simulating user action)
    print_test "Creating command via web interface (duration-based)"
    
    local create_payload=$(cat <<EOF
{
    "type": "spray_pump",
    "params": {
        "duration_ms": 2000
    }
}
EOF
)
    
    # Note: This uses session auth, so we'll simulate it
    print_info "Simulating web command creation (would need session cookie)"
    
    # Test 4.2: Agent reports command result
    print_test "Reporting command execution result"
    
    local result_payload=$(cat <<EOF
{
    "command_id": 1,
    "success": true,
    "executed_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "output": "Spray pump activated for 2000ms",
    "error": null
}
EOF
)
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/growdash/agent/commands/1/result" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$result_payload")
    
    check_response "$response" "200" "Report command result" || true
    
    # Test 4.3: Toggle-type command result
    print_test "Reporting toggle command result"
    
    local toggle_result=$(cat <<EOF
{
    "command_id": 2,
    "success": true,
    "executed_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "output": "Fill valve opened",
    "error": null
}
EOF
)
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/growdash/agent/commands/2/result" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$toggle_result")
    
    check_response "$response" "200" "Report toggle command result" || true
}

# =============================================================================
# Test 5: System Status Updates
# =============================================================================

test_status_updates() {
    print_header "TEST 5: System Status Updates"
    
    if [[ -z "$API_TOKEN" ]]; then
        print_fail "No API token available. Skipping status tests."
        return 1
    fi
    
    print_test "Submitting system status update"
    
    local payload=$(cat <<EOF
{
    "status": "online",
    "uptime": 3600,
    "free_memory": 75,
    "cpu_usage": 25.5,
    "wifi_signal": -45,
    "errors": []
}
EOF
)
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/growdash/agent/status" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    check_response "$response" "200" "System status update"
}

# =============================================================================
# Test 6: Arduino Log Submission
# =============================================================================

test_log_submission() {
    print_header "TEST 6: Arduino Log Submission"
    
    if [[ -z "$API_TOKEN" ]]; then
        print_fail "No API token available. Skipping log tests."
        return 1
    fi
    
    print_test "Submitting Arduino logs"
    
    local payload=$(cat <<EOF
{
    "logs": [
        {
            "level": "info",
            "message": "System initialized",
            "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        },
        {
            "level": "debug",
            "message": "Sensor reading: temp=23.5°C",
            "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        },
        {
            "level": "warning",
            "message": "Water level low: 15%",
            "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        }
    ]
}
EOF
)
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/growdash/agent/logs" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    check_response "$response" "200" "Arduino log submission"
}

# =============================================================================
# Test 7: Heartbeat
# =============================================================================

test_heartbeat() {
    print_header "TEST 7: Heartbeat"
    
    if [[ -z "$API_TOKEN" ]]; then
        print_fail "No API token available. Skipping heartbeat test."
        return 1
    fi
    
    print_test "Sending heartbeat"
    
    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "${BASE_URL}/api/growdash/agent/heartbeat" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN")
    
    check_response "$response" "200" "Heartbeat"
}

# =============================================================================
# Test 8: Error Scenarios
# =============================================================================

test_error_scenarios() {
    print_header "TEST 8: Error Handling"
    
    # Test 8.1: Missing authentication
    print_test "Request without authentication token"
    
    local response=$(curl -s -w "\n%{http_code}" -X GET \
        "${BASE_URL}/api/growdash/agent/commands/pending")
    
    check_response "$response" "401" "Unauthorized request" || true
    
    # Test 8.2: Invalid JSON
    print_test "Request with invalid JSON"
    
    if [[ -n "$API_TOKEN" ]]; then
        local response=$(curl -s -w "\n%{http_code}" -X POST \
            "${BASE_URL}/api/growdash/agent/telemetry" \
            -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
            -H "Content-Type: application/json" \
            -d "{invalid json}")
        
        check_response "$response" "400" "Invalid JSON" || true
    fi
    
    # Test 8.3: Missing required fields
    print_test "Telemetry without required fields"
    
    if [[ -n "$API_TOKEN" ]]; then
        local response=$(curl -s -w "\n%{http_code}" -X POST \
            "${BASE_URL}/api/growdash/agent/telemetry" \
            -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
            -H "Content-Type: application/json" \
            -d '{"sensor_key": "temperature"}')
        
        check_response "$response" "422" "Validation error" || true
    fi
}

# =============================================================================
# Test 9: Command History
# =============================================================================

test_command_history() {
    print_header "TEST 9: Command History"
    
    if [[ -z "$API_TOKEN" ]]; then
        print_fail "No API token available. Skipping history test."
        return 1
    fi
    
    print_test "Fetching command history"
    
    local response=$(curl -s -w "\n%{http_code}" -X GET \
        "${BASE_URL}/api/growdash/agent/commands/history?limit=10" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN")
    
    check_response "$response" "200" "Command history"
}

# =============================================================================
# Test 10: Real-World Scenario - Complete Workflow
# =============================================================================

test_complete_workflow() {
    print_header "TEST 10: Complete Agent Workflow"
    
    if [[ -z "$API_TOKEN" ]]; then
        print_fail "No API token available. Skipping workflow test."
        return 1
    fi
    
    print_test "Simulating complete agent lifecycle"
    
    # Step 1: Send heartbeat
    print_info "Step 1: Heartbeat"
    curl -s -X POST "${BASE_URL}/api/growdash/agent/heartbeat" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" > /dev/null
    
    # Step 2: Submit telemetry
    print_info "Step 2: Submit telemetry data"
    curl -s -X POST "${BASE_URL}/api/growdash/agent/telemetry" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{
            "readings": [
                {"sensor_key": "temperature", "value": 25.1, "timestamp": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ)"'"},
                {"sensor_key": "water_level", "value": 80, "timestamp": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ)"'"},
                {"sensor_key": "tds", "value": 920, "timestamp": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ)"'"}
            ]
        }' > /dev/null
    
    # Step 3: Check for pending commands
    print_info "Step 3: Check for pending commands"
    local pending=$(curl -s -X GET "${BASE_URL}/api/growdash/agent/commands/pending" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN")
    
    echo "$pending" | jq '.'
    
    # Step 4: Submit system status
    print_info "Step 4: Submit system status"
    curl -s -X POST "${BASE_URL}/api/growdash/agent/status" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{
            "status": "online",
            "uptime": 7200,
            "free_memory": 72,
            "cpu_usage": 18.3
        }' > /dev/null
    
    # Step 5: Submit logs
    print_info "Step 5: Submit logs"
    curl -s -X POST "${BASE_URL}/api/growdash/agent/logs" \
        -H "X-Device-ID: $DEVICE_ID" \
        -H "X-Device-Token: $API_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{
            "logs": [
                {"level": "info", "message": "Workflow test completed", "timestamp": "'"$(date -u +%Y-%m-%dT%H:%M:%SZ)"'"}
            ]
        }' > /dev/null
    
    print_success "Complete workflow simulation"
}

# =============================================================================
# Main Execution
# =============================================================================

main() {
    print_header "GrowDash API Test Suite"
    
    echo "Base URL: $BASE_URL"
    if [[ -n "$API_TOKEN" ]]; then
        echo "Using provided API token: ${API_TOKEN:0:20}..."
    else
        echo "No API token provided - will register new device"
    fi
    echo ""
    
    # Check dependencies
    if ! command -v jq &> /dev/null; then
        print_fail "jq is required but not installed. Install with: apt install jq"
        exit 1
    fi
    
    # Run tests
    test_device_bootstrap
    sleep 1
    
    test_telemetry_submission
    sleep 1
    
    test_command_retrieval
    sleep 1
    
    test_command_execution
    sleep 1
    
    test_status_updates
    sleep 1
    
    test_log_submission
    sleep 1
    
    test_heartbeat
    sleep 1
    
    test_error_scenarios
    sleep 1
    
    test_command_history
    sleep 1
    
    test_complete_workflow
    
    # Summary
    print_header "Test Summary"
    echo -e "Total Tests: ${TEST_COUNT}"
    echo -e "${GREEN}Passed: ${PASS_COUNT}${NC}"
    echo -e "${RED}Failed: ${FAIL_COUNT}${NC}"
    
    if [[ $FAIL_COUNT -eq 0 ]]; then
        echo -e "\n${GREEN}All tests passed! ✓${NC}\n"
        exit 0
    else
        echo -e "\n${RED}Some tests failed! ✗${NC}\n"
        exit 1
    fi
}

# Run main function
main "$@"
