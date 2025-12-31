#!/bin/bash
#
# Production Deployment Script for Bihak Center
# Run this script on the Oracle Cloud server after cloning the repository
#

echo "=========================================="
echo "Bihak Center - Production Deployment"
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

# Get the actual directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo -e "${YELLOW}Working directory: $SCRIPT_DIR${NC}"
echo ""

# Step 1: Create production database config
echo -e "${YELLOW}[1/6] Creating production database configuration...${NC}"
cat > "$SCRIPT_DIR/config/database.production.php" <<'EOF'
<?php
/**
 * Production Database Configuration
 * This file contains production database settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'bihak2024');
define('DB_NAME', 'bihak');

/**
 * Get database connection
 *
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDatabaseConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed. Please try again later.");
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Close database connection
 *
 * @param mysqli $conn Database connection object
 */
function closeDatabaseConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
EOF
echo -e "${GREEN}✓ Production database config created${NC}"
echo ""

# Step 2: Update database.php to use production config
echo -e "${YELLOW}[2/6] Updating database.php to use production settings...${NC}"
cp "$SCRIPT_DIR/config/database.production.php" "$SCRIPT_DIR/config/database.php"
echo -e "${GREEN}✓ Database configuration updated${NC}"
echo ""

# Step 3: Set correct file permissions
echo -e "${YELLOW}[3/6] Setting file permissions...${NC}"

# Set ownership to www-data (Apache user)
chown -R www-data:www-data "$SCRIPT_DIR"
echo -e "${GREEN}✓ Changed ownership to www-data${NC}"

# Set directory permissions
find "$SCRIPT_DIR" -type d -exec chmod 755 {} \;
echo -e "${GREEN}✓ Set directory permissions (755)${NC}"

# Set file permissions
find "$SCRIPT_DIR" -type f -exec chmod 644 {} \;
echo -e "${GREEN}✓ Set file permissions (644)${NC}"

# Make specific directories writable
chmod -R 775 "$SCRIPT_DIR/assets/uploads"
chmod -R 775 "$SCRIPT_DIR/logs" 2>/dev/null || mkdir -p "$SCRIPT_DIR/logs" && chmod -R 775 "$SCRIPT_DIR/logs"
echo -e "${GREEN}✓ Made upload and log directories writable${NC}"
echo ""

# Step 4: Verify Apache DocumentRoot
echo -e "${YELLOW}[4/6] Checking Apache configuration...${NC}"
APACHE_CONF="/etc/apache2/sites-available/000-default.conf"

if [ -f "$APACHE_CONF" ]; then
    CURRENT_ROOT=$(grep -oP 'DocumentRoot\s+\K[^\s]+' "$APACHE_CONF")
    echo -e "Current DocumentRoot: ${YELLOW}$CURRENT_ROOT${NC}"

    # The correct DocumentRoot should be /var/www/html/public
    CORRECT_ROOT="/var/www/html/public"

    if [ "$CURRENT_ROOT" != "$CORRECT_ROOT" ]; then
        echo -e "${YELLOW}⚠ DocumentRoot needs to be updated to: $CORRECT_ROOT${NC}"
        echo -e "${YELLOW}Creating backup of Apache config...${NC}"
        cp "$APACHE_CONF" "${APACHE_CONF}.backup"

        # Update DocumentRoot
        sed -i "s|DocumentRoot.*|DocumentRoot $CORRECT_ROOT|g" "$APACHE_CONF"

        # Update Directory directive
        sed -i "s|<Directory /var/www/html>|<Directory $CORRECT_ROOT>|g" "$APACHE_CONF"

        echo -e "${GREEN}✓ Apache configuration updated${NC}"
        echo -e "${YELLOW}Reloading Apache...${NC}"
        systemctl reload apache2
        echo -e "${GREEN}✓ Apache reloaded${NC}"
    else
        echo -e "${GREEN}✓ DocumentRoot is already correct${NC}"
    fi

    # Add Alias for /assets directory (for images, CSS, JS)
    echo -e "${YELLOW}Setting up /assets Alias...${NC}"
    if ! grep -q "Alias /assets" "$APACHE_CONF"; then
        sed -i "/<\/VirtualHost>/i\\
    # Assets Directory Alias\\
    Alias /assets $SCRIPT_DIR/assets\\
    <Directory $SCRIPT_DIR/assets>\\
        Options Indexes FollowSymLinks\\
        AllowOverride None\\
        Require all granted\\
    </Directory>\\
" "$APACHE_CONF"
        echo -e "${GREEN}✓ Assets Alias added${NC}"
        systemctl reload apache2
    else
        echo -e "${GREEN}✓ Assets Alias already exists${NC}"
    fi

    # Add Alias for /api directory (for chat widget and messaging APIs)
    echo -e "${YELLOW}Setting up /api Alias...${NC}"
    if ! grep -q "Alias /api" "$APACHE_CONF"; then
        sed -i "/<\/VirtualHost>/i\\
    # API Directory Alias\\
    Alias /api $SCRIPT_DIR/api\\
    <Directory $SCRIPT_DIR/api>\\
        Options Indexes FollowSymLinks\\
        AllowOverride None\\
        Require all granted\\
    </Directory>\\
" "$APACHE_CONF"
        echo -e "${GREEN}✓ API Alias added${NC}"
        systemctl reload apache2
    else
        echo -e "${GREEN}✓ API Alias already exists${NC}"
    fi
else
    echo -e "${RED}⚠ Apache config file not found at expected location${NC}"
fi
echo ""

# Step 5: Test database connection
echo -e "${YELLOW}[5/6] Testing database connection...${NC}"
php -r "
require_once '$SCRIPT_DIR/config/database.php';
try {
    \$conn = getDatabaseConnection();
    echo 'Database connection: SUCCESS\n';
    closeDatabaseConnection(\$conn);
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
    exit(1);
}
"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
fi
echo ""

# Step 6: Verify upload directory
echo -e "${YELLOW}[6/6] Verifying upload directories...${NC}"
UPLOAD_DIRS=(
    "$SCRIPT_DIR/assets/uploads/profiles"
    "$SCRIPT_DIR/assets/uploads/content"
    "$SCRIPT_DIR/assets/uploads/temp"
)

for dir in "${UPLOAD_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo -e "${GREEN}✓ Created directory: $dir${NC}"
    else
        echo -e "${GREEN}✓ Directory exists: $dir${NC}"
    fi
    chown -R www-data:www-data "$dir"
    chmod -R 775 "$dir"
done
echo ""

# Summary
echo -e "${GREEN}=========================================="
echo -e "Deployment Complete!"
echo -e "==========================================${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Test the website: http://155.248.239.239"
echo "2. Verify signup with security questions works"
echo "3. Check mobile responsiveness"
echo "4. Verify all pages load correctly"
echo ""
echo -e "${YELLOW}Important files:${NC}"
echo "- Config: $SCRIPT_DIR/config/database.php"
echo "- Apache: $APACHE_CONF"
echo "- Uploads: $SCRIPT_DIR/assets/uploads/"
echo ""
echo -e "${GREEN}To pull future updates from GitHub:${NC}"
echo "cd $SCRIPT_DIR && sudo git pull origin main"
echo ""
