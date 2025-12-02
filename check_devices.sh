#!/bin/bash

# =============================================================================
# GrowDash Device Status Checker
# =============================================================================
# This script shows the current status of all devices for a user account.
# Use this to monitor device health, connectivity, and capabilities.
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="${GROWDASH_URL:-https://grow.linn.games}"

# =============================================================================
# Helper Functions
# =============================================================================

print_header() {
    echo -e "\n${CYAN}========================================${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_fail() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# =============================================================================
# Main Script
# =============================================================================

print_header "GrowDash Device Status Checker"
echo "Base URL: $BASE_URL"
echo ""

# Step 1: Get user credentials
read -p "Email: " USER_EMAIL
read -sp "Password: " USER_PASSWORD
echo ""
echo ""

# Step 2: Login and get Sanctum token
print_info "Logging in..."

LOGIN_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
    "${BASE_URL}/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$USER_EMAIL\",\"password\":\"$USER_PASSWORD\"}")

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | tail -n 1)
BODY=$(echo "$LOGIN_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" != "200" ]; then
    print_fail "Login failed (HTTP $HTTP_CODE)"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    exit 1
fi

USER_TOKEN=$(echo "$BODY" | jq -r '.token')
USER_NAME=$(echo "$BODY" | jq -r '.user.name')

if [ -z "$USER_TOKEN" ] || [ "$USER_TOKEN" = "null" ]; then
    print_fail "Could not extract auth token from response"
    exit 1
fi

print_success "Logged in as: $USER_NAME"
echo ""

# Step 3: Fetch user's devices
print_info "Fetching devices..."

DEVICES_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET \
    "${BASE_URL}/api/user/devices" \
    -H "Authorization: Bearer $USER_TOKEN")

HTTP_CODE=$(echo "$DEVICES_RESPONSE" | tail -n 1)
BODY=$(echo "$DEVICES_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" != "200" ]; then
    print_fail "Failed to fetch devices (HTTP $HTTP_CODE)"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    exit 1
fi

DEVICE_COUNT=$(echo "$BODY" | jq -r '.count')

print_header "Devices Overview"
echo "Total Devices: $DEVICE_COUNT"
echo ""

if [ "$DEVICE_COUNT" = "0" ]; then
    print_warning "No devices found for this account"
    exit 0
fi

# Step 4: Display each device
echo "$BODY" | jq -c '.devices[]' | while read -r device; do
    DEVICE_NAME=$(echo "$device" | jq -r '.name')
    PUBLIC_ID=$(echo "$device" | jq -r '.public_id')
    STATUS=$(echo "$device" | jq -r '.status')
    LAST_SEEN=$(echo "$device" | jq -r '.last_seen_at')
    BOARD_TYPE=$(echo "$device" | jq -r '.board_type // "unknown"')
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}Device:${NC} $DEVICE_NAME"
    echo -e "${BLUE}Public ID:${NC} $PUBLIC_ID"
    echo -e "${BLUE}Board Type:${NC} $BOARD_TYPE"
    
    # Status with color
    if [ "$STATUS" = "online" ]; then
        echo -e "${BLUE}Status:${NC} ${GREEN}$STATUS${NC}"
    elif [ "$STATUS" = "offline" ]; then
        echo -e "${BLUE}Status:${NC} ${RED}$STATUS${NC}"
    else
        echo -e "${BLUE}Status:${NC} ${YELLOW}$STATUS${NC}"
    fi
    
    # Last seen
    if [ "$LAST_SEEN" != "null" ] && [ -n "$LAST_SEEN" ]; then
        echo -e "${BLUE}Last Seen:${NC} $LAST_SEEN"
    else
        echo -e "${BLUE}Last Seen:${NC} ${YELLOW}Never${NC}"
    fi
    
    # Capabilities
    SENSORS=$(echo "$device" | jq -r '.capabilities.sensors // [] | length')
    ACTUATORS=$(echo "$device" | jq -r '.capabilities.actuators // [] | length')
    
    if [ "$SENSORS" != "null" ] && [ "$SENSORS" != "0" ]; then
        echo -e "${BLUE}Sensors:${NC} $SENSORS configured"
        echo "$device" | jq -r '.capabilities.sensors[] | "  • \(.display_name) (\(.id)) - \(.unit // "no unit")"'
    else
        echo -e "${BLUE}Sensors:${NC} ${YELLOW}None configured${NC}"
    fi
    
    if [ "$ACTUATORS" != "null" ] && [ "$ACTUATORS" != "0" ]; then
        echo -e "${BLUE}Actuators:${NC} $ACTUATORS configured"
        echo "$device" | jq -r '.capabilities.actuators[] | "  • \(.display_name) (\(.id)) - \(.command_type)"'
    else
        echo -e "${BLUE}Actuators:${NC} ${YELLOW}None configured${NC}"
    fi
    
    echo ""
done

echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

print_success "Device check complete!"
echo ""

# Ask if user wants to delete any devices
echo ""
read -p "Do you want to delete any devices? (y/N): " DELETE_CONFIRM

if [[ "$DELETE_CONFIRM" =~ ^[Yy]$ ]]; then
    echo ""
    echo "Available devices:"
    echo "$BODY" | jq -r '.devices[] | "\(.id): \(.name) (\(.public_id))"'
    echo ""
    read -p "Enter device ID to delete (or 'cancel'): " DELETE_ID
    
    if [ "$DELETE_ID" = "cancel" ] || [ -z "$DELETE_ID" ]; then
        print_info "Deletion cancelled"
    else
        print_warning "⚠️  This will DELETE the device and ALL associated data!"
        read -p "Type 'DELETE' to confirm: " CONFIRM_DELETE
        
        if [ "$CONFIRM_DELETE" = "DELETE" ]; then
            print_info "Deleting device ID $DELETE_ID..."
            
            DELETE_RESPONSE=$(curl -s -w "\n%{http_code}" -X DELETE \
                "${BASE_URL}/api/user/devices/${DELETE_ID}" \
                -H "Authorization: Bearer $USER_TOKEN")
            
            HTTP_CODE=$(echo "$DELETE_RESPONSE" | tail -n 1)
            BODY=$(echo "$DELETE_RESPONSE" | sed '$d')
            
            if [ "$HTTP_CODE" = "200" ]; then
                print_success "$(echo "$BODY" | jq -r '.message')"
            else
                print_fail "Failed to delete device (HTTP $HTTP_CODE)"
                echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
            fi
        else
            print_info "Deletion cancelled (confirmation did not match)"
        fi
    fi
fi
