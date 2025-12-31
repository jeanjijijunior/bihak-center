#!/bin/bash
#
# Fix Git Ownership Issue
# Resolves "dubious ownership" error when pulling from Git
#

echo "=========================================="
echo "Fixing Git Ownership Issue"
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

REPO_DIR="/var/www/html"

echo -e "${YELLOW}Current directory ownership:${NC}"
ls -ld "$REPO_DIR"
echo ""

# Option 1: Add safe directory exception (recommended for production)
echo -e "${YELLOW}[Option 1] Adding Git safe directory exception...${NC}"
git config --global --add safe.directory "$REPO_DIR"
echo -e "${GREEN}✓ Safe directory exception added${NC}"
echo ""

# Option 2: Fix ownership (alternative approach)
echo -e "${YELLOW}[Option 2] Fixing repository ownership...${NC}"
chown -R ubuntu:ubuntu "$REPO_DIR/.git"
echo -e "${GREEN}✓ Git directory ownership fixed${NC}"
echo ""

# Verify fix
echo -e "${YELLOW}Testing git pull...${NC}"
cd "$REPO_DIR"
sudo -u ubuntu git pull origin main

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}=========================================="
    echo -e "Git Pull Successful!"
    echo -e "==========================================${NC}"
else
    echo ""
    echo -e "${RED}=========================================="
    echo -e "Git pull still failing. Manual intervention needed."
    echo -e "==========================================${NC}"
    echo ""
    echo -e "${YELLOW}Try running manually:${NC}"
    echo "  cd /var/www/html"
    echo "  sudo -u ubuntu git pull origin main"
fi
