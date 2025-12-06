#!/bin/bash

# Endpoint Tracking Test Suite
# Dieses Script testet ALLE Endpoints, um herauszufinden, welche wirklich genutzt werden

set -e

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== SupportedGrowControl: Endpoint Tracking Test Suite ===${NC}\n"

# 1. Log-Datei leeren
echo -e "${YELLOW}1. Clearing log files...${NC}"
> storage/logs/laravel.log
rm -f tests/endpoint_tracking_results.txt

# 2. Basis-URLs definieren
API_URL="http://localhost:8000/api"
WEB_URL="http://localhost:8000"

# Standard Auth-Header (würde von echten Tests kommen)
AUTH_HEADER="Authorization: Bearer test-token"

# Hilfsfunktion für API Calls
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo -e "  Testing: ${YELLOW}$method $endpoint${NC} - $description"
    
    if [ -z "$data" ]; then
        curl -s -X "$method" "$endpoint" -H "Accept: application/json" > /dev/null 2>&1 || true
    else
        curl -s -X "$method" "$endpoint" \
            -H "Accept: application/json" \
            -H "Content-Type: application/json" \
            -d "$data" > /dev/null 2>&1 || true
    fi
}

# 3. Test-Kategorien starten
echo -e "\n${BLUE}Testing API Endpoints...${NC}\n"

# CommandController Tests
echo -e "${YELLOW}CommandController:${NC}"
test_endpoint "GET" "$API_URL/growdash/agent/commands/pending" "" "get pending commands"
test_endpoint "POST" "$API_URL/growdash/agent/commands/1/result" '{"status":"completed"}' "submit command result"
test_endpoint "POST" "$API_URL/growdash/devices/test-id/commands" '{"type":"serial_command","params":{"command":"STATUS"}}' "send serial command"
test_endpoint "GET" "$API_URL/growdash/devices/test-id/commands" "" "get command history"

# AuthController Tests
echo -e "\n${YELLOW}AuthController:${NC}"
test_endpoint "POST" "$API_URL/auth/login" '{"email":"test@test.com","password":"password"}' "login"

# DeviceManagementController Tests
echo -e "\n${YELLOW}DeviceManagementController:${NC}"
test_endpoint "POST" "$API_URL/growdash/agent/heartbeat" '{"last_state":{}}' "device heartbeat"

# LogController Tests
echo -e "\n${YELLOW}LogController:${NC}"
test_endpoint "POST" "$API_URL/growdash/agent/logs" '{"logs":[{"level":"info","message":"Test"}]}' "store logs"

# ShellyWebhookController Tests
echo -e "\n${YELLOW}ShellyWebhookController:${NC}"
test_endpoint "POST" "$API_URL/shelly/webhook/1" '{"device":"shelly","event":"test"}' "shelly webhook"

# DeviceController Tests
echo -e "\n${YELLOW}DeviceController:${NC}"
test_endpoint "POST" "$API_URL/growdash/devices/register" '{"bootstrap_id":"test123"}' "register device"

# DeviceRegistrationController Tests
echo -e "\n${YELLOW}DeviceRegistrationController:${NC}"
test_endpoint "POST" "$API_URL/growdash/devices/register-from-agent" '{"bootstrap_id":"test123","name":"TestDevice"}' "register from agent"

# BootstrapController Tests
echo -e "\n${YELLOW}BootstrapController:${NC}"
test_endpoint "POST" "$API_URL/agents/bootstrap" '{"bootstrap_id":"agent-123"}' "bootstrap device"
test_endpoint "GET" "$API_URL/agents/pairing/status?bootstrap_id=agent-123&bootstrap_code=ABC123" "" "check pairing status"

# DevicePairingController Tests
echo -e "\n${YELLOW}DevicePairingController:${NC}"
test_endpoint "POST" "$API_URL/devices/pair" '{"bootstrap_code":"ABC123"}' "pair device"
test_endpoint "GET" "$API_URL/devices/unclaimed" "" "list unclaimed devices"

# GrowdashWebhookController Tests (umfangreich!)
echo -e "\n${YELLOW}GrowdashWebhookController:${NC}"
test_endpoint "POST" "$API_URL/growdash/log" '{"device_slug":"test","message":"test log"}' "log message"
test_endpoint "POST" "$API_URL/growdash/event" '{"device_slug":"test","type":"water_level","payload":{"level_percent":50}}' "send event"
test_endpoint "GET" "$API_URL/growdash/status?device_slug=test" "" "get status"
test_endpoint "GET" "$API_URL/growdash/water-history?device_slug=test" "" "get water history"
test_endpoint "GET" "$API_URL/growdash/tds-history?device_slug=test" "" "get tds history"
test_endpoint "GET" "$API_URL/growdash/temperature-history?device_slug=test" "" "get temperature history"
test_endpoint "GET" "$API_URL/growdash/spray-events?device_slug=test" "" "get spray events"
test_endpoint "GET" "$API_URL/growdash/fill-events?device_slug=test" "" "get fill events"
test_endpoint "GET" "$API_URL/growdash/logs?device_slug=test" "" "get logs"
test_endpoint "POST" "$API_URL/growdash/manual-spray" '{"device_slug":"test","action":"on"}' "manual spray"
test_endpoint "POST" "$API_URL/growdash/manual-fill" '{"device_slug":"test","action":"start"}' "manual fill"

echo -e "\n${BLUE}Testing Web Endpoints...${NC}\n"

# CalendarController Tests
echo -e "${YELLOW}CalendarController:${NC}"
test_endpoint "GET" "$WEB_URL/calendar" "" "calendar view"
test_endpoint "GET" "$WEB_URL/calendar/events?start=2025-12-01&end=2025-12-31" "" "calendar events"

# DashboardController Tests
echo -e "\n${YELLOW}DashboardController:${NC}"
test_endpoint "GET" "$WEB_URL/dashboard" "" "dashboard view"

# FeedbackController Tests
echo -e "\n${YELLOW}FeedbackController:${NC}"
test_endpoint "POST" "$WEB_URL/feedback" '{"rating":5,"message":"Test feedback"}' "submit feedback"

# 4. Ergebnisse sammeln
echo -e "\n${BLUE}=== Collecting Results ===${NC}\n"
echo -e "${YELLOW}Analyzing logs...${NC}"

# Extrahiere alle ENDPOINT_TRACKED logs
echo "ENDPOINT TRACKING RESULTS" > tests/endpoint_tracking_results.txt
echo "========================" >> tests/endpoint_tracking_results.txt
echo "" >> tests/endpoint_tracking_results.txt
echo "Generated at: $(date)" >> tests/endpoint_tracking_results.txt
echo "" >> tests/endpoint_tracking_results.txt

echo "All Tracked Endpoints:" >> tests/endpoint_tracking_results.txt
echo "---------------------" >> tests/endpoint_tracking_results.txt
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | sort | uniq >> tests/endpoint_tracking_results.txt || echo "No endpoints tracked yet" >> tests/endpoint_tracking_results.txt

echo "" >> tests/endpoint_tracking_results.txt
echo "Endpoint Call Frequency:" >> tests/endpoint_tracking_results.txt
echo "------------------------" >> tests/endpoint_tracking_results.txt
grep "ENDPOINT_TRACKED" storage/logs/laravel.log | sort | uniq -c | sort -rn >> tests/endpoint_tracking_results.txt || echo "No data available" >> tests/endpoint_tracking_results.txt

# Zeige Ergebnisse
echo -e "\n${GREEN}Results saved to: tests/endpoint_tracking_results.txt${NC}\n"
cat tests/endpoint_tracking_results.txt

echo -e "\n${BLUE}To analyze logs manually:${NC}"
echo -e "  grep ${YELLOW}'ENDPOINT_TRACKED'${NC} storage/logs/laravel.log | sort | uniq -c | sort -rn"
echo -e "\n${BLUE}To filter specific controller:${NC}"
echo -e "  grep ${YELLOW}'ENDPOINT_TRACKED.*CommandController'${NC} storage/logs/laravel.log"

echo -e "\n${GREEN}✓ Test suite completed!${NC}\n"
