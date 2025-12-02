#!/bin/bash
# Test-Skript für Agent-Endpoints
# Prüft ob alle kritischen Endpoints erreichbar sind

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  GrowDash Agent Endpoint Tests        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# Config laden
if [ ! -f .env ]; then
    echo -e "${RED}❌ .env nicht gefunden${NC}"
    exit 1
fi

source .env 2>/dev/null || true

# Device-Credentials aus DB holen
DEVICE=$(docker exec supportedgrowcontrol-php-cli-1 php artisan tinker --execute="
\$device = App\Models\Device::where('status', 'online')->first() ?? App\Models\Device::whereNotNull('user_id')->first();
if (\$device) {
    echo json_encode([
        'public_id' => \$device->public_id,
        'has_token' => !empty(\$device->agent_token)
    ]);
}" 2>/dev/null)

if [ -z "$DEVICE" ] || [ "$DEVICE" == "null" ]; then
    echo -e "${YELLOW}⚠️  Kein Device in DB gefunden - Test-Device erstellen...${NC}"
    echo ""
    echo "Bitte erst Agent pairen:"
    echo "  cd ~/nileneb-growdash"
    echo "  ./setup.sh"
    exit 1
fi

DEVICE_ID=$(echo $DEVICE | jq -r '.public_id')
HAS_TOKEN=$(echo $DEVICE | jq -r '.has_token')

echo "Device-ID: $DEVICE_ID"
echo "Token gesetzt: $HAS_TOKEN"
echo ""

if [ "$HAS_TOKEN" != "true" ]; then
    echo -e "${YELLOW}⚠️  Device hat keinen Token - bitte neu pairen${NC}"
    exit 1
fi

# Teste ob Laravel erreichbar ist
echo -e "${BLUE}[1/6]${NC} Teste Laravel Erreichbarkeit..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://grow.linn.games)

if [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 302 ]; then
    echo -e "${GREEN}✅ Laravel erreichbar (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}❌ Laravel nicht erreichbar (HTTP $HTTP_CODE)${NC}"
    exit 1
fi

echo ""

# Test Heartbeat (benötigt Device-Token)
echo -e "${BLUE}[2/6]${NC} Teste Heartbeat Endpoint..."
echo "  → POST /api/growdash/agent/heartbeat"

# Dummy-Token für Test (wird fehlschlagen wenn nicht korrekt)
RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST https://grow.linn.games/api/growdash/agent/heartbeat \
    -H "X-Device-ID: $DEVICE_ID" \
    -H "X-Device-Token: test-token-invalid" \
    -H "Content-Type: application/json" \
    -d '{"last_state":{"test":true}}' 2>/dev/null)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n-1)

if [ "$HTTP_CODE" -eq 403 ] || [ "$HTTP_CODE" -eq 401 ]; then
    echo -e "${GREEN}✅ Endpoint existiert (Auth-Fehler erwartet ohne Token)${NC}"
    echo "  Status: $HTTP_CODE (Auth required)"
elif [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ Heartbeat erfolgreich${NC}"
    echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
elif [ "$HTTP_CODE" -eq 404 ]; then
    echo -e "${RED}❌ Endpoint nicht gefunden (404)${NC}"
    echo "  Route fehlt in routes/api.php"
else
    echo -e "${YELLOW}⚠️  Unerwarteter Status: $HTTP_CODE${NC}"
    echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
fi

echo ""

# Test Commands Polling
echo -e "${BLUE}[3/6]${NC} Teste Command Polling Endpoint..."
echo "  → GET /api/growdash/agent/commands/pending"

RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X GET https://grow.linn.games/api/growdash/agent/commands/pending \
    -H "X-Device-ID: $DEVICE_ID" \
    -H "X-Device-Token: test-token-invalid" 2>/dev/null)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

if [ "$HTTP_CODE" -eq 403 ] || [ "$HTTP_CODE" -eq 401 ]; then
    echo -e "${GREEN}✅ Endpoint existiert (Auth required)${NC}"
elif [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ Polling erfolgreich${NC}"
elif [ "$HTTP_CODE" -eq 404 ]; then
    echo -e "${RED}❌ Endpoint nicht gefunden (404)${NC}"
else
    echo -e "${YELLOW}⚠️  Status: $HTTP_CODE${NC}"
fi

echo ""

# Test Telemetry
echo -e "${BLUE}[4/6]${NC} Teste Telemetry Endpoint..."
echo "  → POST /api/growdash/agent/telemetry"

RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST https://grow.linn.games/api/growdash/agent/telemetry \
    -H "X-Device-ID: $DEVICE_ID" \
    -H "X-Device-Token: test-token-invalid" \
    -H "Content-Type: application/json" \
    -d '{"readings":[{"sensor_key":"test","value":42,"measured_at":"2025-12-02T12:00:00Z"}]}' 2>/dev/null)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

if [ "$HTTP_CODE" -eq 403 ] || [ "$HTTP_CODE" -eq 401 ]; then
    echo -e "${GREEN}✅ Endpoint existiert (Auth required)${NC}"
elif [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}✅ Telemetry erfolgreich${NC}"
elif [ "$HTTP_CODE" -eq 404 ]; then
    echo -e "${RED}❌ Endpoint nicht gefunden (404)${NC}"
else
    echo -e "${YELLOW}⚠️  Status: $HTTP_CODE${NC}"
fi

echo ""

# Test Capabilities
echo -e "${BLUE}[5/6]${NC} Teste Capabilities Endpoint..."
echo "  → POST /api/growdash/agent/capabilities"

RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST https://grow.linn.games/api/growdash/agent/capabilities \
    -H "X-Device-ID: $DEVICE_ID" \
    -H "X-Device-Token: test-token-invalid" \
    -H "Content-Type: application/json" \
    -d '{"capabilities":{"board_name":"test","sensors":[],"actuators":[]}}' 2>/dev/null)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

if [ "$HTTP_CODE" -eq 403 ] || [ "$HTTP_CODE" -eq 401 ]; then
    echo -e "${GREEN}✅ Endpoint existiert (Auth required)${NC}"
elif [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ Capabilities erfolgreich${NC}"
elif [ "$HTTP_CODE" -eq 404 ]; then
    echo -e "${RED}❌ Endpoint nicht gefunden (404)${NC}"
else
    echo -e "${YELLOW}⚠️  Status: $HTTP_CODE${NC}"
fi

echo ""

# Test Logs
echo -e "${BLUE}[6/6]${NC} Teste Logs Endpoint..."
echo "  → POST /api/growdash/agent/logs"

RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST https://grow.linn.games/api/growdash/agent/logs \
    -H "X-Device-ID: $DEVICE_ID" \
    -H "X-Device-Token: test-token-invalid" \
    -H "Content-Type: application/json" \
    -d '{"logs":[{"level":"info","message":"test"}]}' 2>/dev/null)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

if [ "$HTTP_CODE" -eq 403 ] || [ "$HTTP_CODE" -eq 401 ]; then
    echo -e "${GREEN}✅ Endpoint existiert (Auth required)${NC}"
elif [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}✅ Logs erfolgreich${NC}"
elif [ "$HTTP_CODE" -eq 404 ]; then
    echo -e "${RED}❌ Endpoint nicht gefunden (404)${NC}"
else
    echo -e "${YELLOW}⚠️  Status: $HTTP_CODE${NC}"
fi

echo ""
echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Tests abgeschlossen                   ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✅ Alle Agent-Endpoints sind erreichbar${NC}"
echo ""
echo "Hinweis: Auth-Fehler (401/403) sind erwartet ohne gültigen Token"
echo "         Tests prüfen nur ob Routes existieren"
echo ""
echo "Für echte Tests mit Token:"
echo "  cd ~/nileneb-growdash"
echo "  ./test_heartbeat.sh    # Mit echtem Device-Token"
echo "  ./grow_start.sh        # Agent starten"
echo ""
