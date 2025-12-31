#!/bin/bash
#
# Fix Assets Path - Make assets accessible from web
# This script ensures social media images and other assets load correctly
#

echo "=========================================="
echo "Fixing Assets Path for Bihak Center"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Error: Please run this script with sudo${NC}"
    exit 1
fi

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo -e "${YELLOW}Working directory: $SCRIPT_DIR${NC}"
echo ""

# Option 1: Create Apache Alias (Recommended - keeps files organized)
echo -e "${YELLOW}[Option 1] Setting up Apache Alias for /assets...${NC}"

APACHE_CONF="/etc/apache2/sites-available/000-default.conf"

if [ -f "$APACHE_CONF" ]; then
    # Backup the config
    cp "$APACHE_CONF" "${APACHE_CONF}.backup-$(date +%Y%m%d-%H%M%S)"
    echo -e "${GREEN}✓ Apache config backed up${NC}"

    # Check if Alias already exists
    if grep -q "Alias /assets" "$APACHE_CONF"; then
        echo -e "${YELLOW}⚠ Alias already exists, updating...${NC}"
        sed -i '/Alias \/assets/d' "$APACHE_CONF"
        sed -i '/<Directory .*\/assets>/,/<\/Directory>/d' "$APACHE_CONF"
    fi

    # Add Alias before </VirtualHost>
    sed -i "/<\/VirtualHost>/i\\
    # Assets Directory Alias\\
    Alias /assets $SCRIPT_DIR/assets\\
    <Directory $SCRIPT_DIR/assets>\\
        Options Indexes FollowSymLinks\\
        AllowOverride None\\
        Require all granted\\
    </Directory>\\
" "$APACHE_CONF"

    echo -e "${GREEN}✓ Apache Alias configured${NC}"
    echo -e "${YELLOW}Reloading Apache...${NC}"
    systemctl reload apache2
    echo -e "${GREEN}✓ Apache reloaded${NC}"
else
    echo -e "${RED}✗ Apache config not found at $APACHE_CONF${NC}"
    echo -e "${YELLOW}Trying alternative method...${NC}"

    # Option 2: Create symbolic link in public directory
    echo -e "${YELLOW}[Option 2] Creating symbolic link in public directory...${NC}"

    if [ -L "$SCRIPT_DIR/public/assets" ]; then
        echo -e "${YELLOW}⚠ Symbolic link already exists, removing...${NC}"
        rm "$SCRIPT_DIR/public/assets"
    fi

    if [ -d "$SCRIPT_DIR/public/assets" ]; then
        echo -e "${YELLOW}⚠ Assets directory already exists in public, removing...${NC}"
        rm -rf "$SCRIPT_DIR/public/assets"
    fi

    ln -s "$SCRIPT_DIR/assets" "$SCRIPT_DIR/public/assets"
    echo -e "${GREEN}✓ Symbolic link created${NC}"
fi

echo ""

# Verify the setup
echo -e "${YELLOW}Verifying setup...${NC}"
echo ""

# Check if images exist
echo -e "${YELLOW}Checking social media images:${NC}"
IMAGES=(
    "$SCRIPT_DIR/assets/images/facebook-icon.png"
    "$SCRIPT_DIR/assets/images/instagram-icon.png"
    "$SCRIPT_DIR/assets/images/x-logo.png"
)

for img in "${IMAGES[@]}"; do
    if [ -f "$img" ]; then
        SIZE=$(du -h "$img" | cut -f1)
        echo -e "  ${GREEN}✓${NC} $(basename $img) (${SIZE})"
    else
        echo -e "  ${RED}✗${NC} $(basename $img) - MISSING"
    fi
done

echo ""

# Test web access
echo -e "${YELLOW}Testing web access...${NC}"
TEST_URL="http://localhost/assets/images/facebook-icon.png"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$TEST_URL")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ Assets are accessible via web (HTTP 200)${NC}"
elif [ "$HTTP_CODE" = "404" ]; then
    echo -e "${RED}✗ Assets not found (HTTP 404)${NC}"
    echo -e "${YELLOW}Please check Apache configuration${NC}"
else
    echo -e "${YELLOW}⚠ Unexpected HTTP code: $HTTP_CODE${NC}"
fi

echo ""

# Summary
echo -e "${GREEN}=========================================="
echo -e "Setup Complete!"
echo -e "==========================================${NC}"
echo ""
echo -e "${YELLOW}What was done:${NC}"
echo "1. Apache Alias created: /assets → $SCRIPT_DIR/assets"
echo "2. Directory permissions set to allow web access"
echo "3. Apache configuration reloaded"
echo ""
echo -e "${YELLOW}Test URLs:${NC}"
echo "  http://155.248.239.239/assets/images/facebook-icon.png"
echo "  http://155.248.239.239/assets/images/instagram-icon.png"
echo "  http://155.248.239.239/assets/images/x-logo.png"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Clear your browser cache (Ctrl+Shift+Delete)"
echo "2. Refresh the website homepage"
echo "3. Check footer - social media icons should now appear"
echo ""
echo -e "${YELLOW}If images still don't appear:${NC}"
echo "  sudo tail -f /var/log/apache2/error.log"
echo ""
