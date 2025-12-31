#!/bin/bash
#
# Deployment Verification Script for Bihak Center
# Run this to check if everything is configured correctly
#

echo "=========================================="
echo "Bihak Center - Deployment Verification"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check 1: Apache status
echo -e "${YELLOW}[1/8] Checking Apache status...${NC}"
if systemctl is-active --quiet apache2; then
    echo -e "${GREEN}✓ Apache is running${NC}"
else
    echo -e "${RED}✗ Apache is not running${NC}"
    echo -e "${YELLOW}Starting Apache...${NC}"
    sudo systemctl start apache2
    if systemctl is-active --quiet apache2; then
        echo -e "${GREEN}✓ Apache started successfully${NC}"
    else
        echo -e "${RED}✗ Failed to start Apache${NC}"
    fi
fi
echo ""

# Check 2: Apache configuration
echo -e "${YELLOW}[2/8] Checking Apache DocumentRoot...${NC}"
APACHE_CONF="/etc/apache2/sites-available/000-default.conf"
if [ -f "$APACHE_CONF" ]; then
    CURRENT_ROOT=$(grep -oP 'DocumentRoot\s+\K[^\s]+' "$APACHE_CONF")
    echo -e "DocumentRoot: ${YELLOW}$CURRENT_ROOT${NC}"
    if [ "$CURRENT_ROOT" = "/var/www/html/public" ]; then
        echo -e "${GREEN}✓ DocumentRoot is correct${NC}"
    else
        echo -e "${RED}✗ DocumentRoot should be /var/www/html/public${NC}"
    fi
else
    echo -e "${RED}✗ Apache config not found${NC}"
fi
echo ""

# Check 3: Git repository
echo -e "${YELLOW}[3/8] Checking Git repository...${NC}"
if [ -d "/var/www/html/.git" ]; then
    echo -e "${GREEN}✓ Git repository exists${NC}"
    cd /var/www/html
    CURRENT_BRANCH=$(git branch --show-current)
    echo -e "Current branch: ${YELLOW}$CURRENT_BRANCH${NC}"
    LATEST_COMMIT=$(git log -1 --oneline)
    echo -e "Latest commit: ${YELLOW}$LATEST_COMMIT${NC}"
else
    echo -e "${RED}✗ Git repository not found${NC}"
fi
echo ""

# Check 4: Database configuration
echo -e "${YELLOW}[4/8] Checking database configuration...${NC}"
if [ -f "/var/www/html/config/database.php" ]; then
    echo -e "${GREEN}✓ database.php exists${NC}"

    # Test database connection
    php -r "
    require_once '/var/www/html/config/database.php';
    try {
        \$conn = getDatabaseConnection();
        echo 'Database connection: SUCCESS' . PHP_EOL;

        // Check if security_questions table exists
        \$result = \$conn->query(\"SHOW TABLES LIKE 'security_questions'\");
        if (\$result->num_rows > 0) {
            echo 'security_questions table: EXISTS' . PHP_EOL;
        } else {
            echo 'security_questions table: MISSING' . PHP_EOL;
        }

        closeDatabaseConnection(\$conn);
    } catch (Exception \$e) {
        echo 'Database connection: FAILED - ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    "
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database connection successful${NC}"
    else
        echo -e "${RED}✗ Database connection failed${NC}"
    fi
else
    echo -e "${RED}✗ database.php not found${NC}"
fi
echo ""

# Check 5: File permissions
echo -e "${YELLOW}[5/8] Checking file permissions...${NC}"
if [ -d "/var/www/html" ]; then
    OWNER=$(stat -c '%U:%G' /var/www/html)
    echo -e "Owner: ${YELLOW}$OWNER${NC}"
    if [ "$OWNER" = "www-data:www-data" ]; then
        echo -e "${GREEN}✓ Ownership is correct${NC}"
    else
        echo -e "${RED}✗ Owner should be www-data:www-data${NC}"
    fi
fi
echo ""

# Check 6: Upload directories
echo -e "${YELLOW}[6/8] Checking upload directories...${NC}"
UPLOAD_DIRS=(
    "/var/www/html/assets/uploads/profiles"
    "/var/www/html/assets/uploads/content"
    "/var/www/html/assets/uploads/temp"
)

for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        PERMS=$(stat -c '%a' "$dir")
        echo -e "  $dir: ${GREEN}EXISTS${NC} (${YELLOW}$PERMS${NC})"
        if [ "$PERMS" = "775" ] || [ "$PERMS" = "777" ]; then
            echo -e "    ${GREEN}✓ Permissions OK${NC}"
        else
            echo -e "    ${YELLOW}⚠ Permissions should be 775${NC}"
        fi
    else
        echo -e "  $dir: ${RED}MISSING${NC}"
    fi
done
echo ""

# Check 7: PHP configuration
echo -e "${YELLOW}[7/8] Checking PHP upload settings...${NC}"
php -r "
echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;
echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;
echo 'max_file_uploads: ' . ini_get('max_file_uploads') . PHP_EOL;
"
echo ""

# Check 8: Key files exist
echo -e "${YELLOW}[8/8] Checking key files...${NC}"
KEY_FILES=(
    "/var/www/html/public/index.php"
    "/var/www/html/public/signup.php"
    "/var/www/html/public/login.php"
    "/var/www/html/config/database.php"
    "/var/www/html/config/security.php"
)

for file in "${KEY_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  $(basename $file): ${GREEN}✓${NC}"
    else
        echo -e "  $(basename $file): ${RED}✗ MISSING${NC}"
    fi
done
echo ""

# Summary
echo -e "${GREEN}=========================================="
echo -e "Verification Complete"
echo -e "==========================================${NC}"
echo ""
echo -e "${YELLOW}Server IP:${NC} 155.248.239.239"
echo -e "${YELLOW}Website URL:${NC} http://155.248.239.239"
echo ""
echo -e "${YELLOW}Commands to check logs if issues occur:${NC}"
echo "  sudo tail -f /var/log/apache2/error.log"
echo "  sudo tail -f /var/log/apache2/access.log"
echo ""
echo -e "${YELLOW}Restart Apache if needed:${NC}"
echo "  sudo systemctl restart apache2"
echo ""
