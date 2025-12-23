<?php
/**
 * Example Configuration File
 *
 * Copy this file to config.local.php and update with your local settings.
 * The config.local.php file is ignored by Git for security.
 */

// Site Configuration
define('SITE_NAME', 'Bihak Center');
define('SITE_URL', 'http://localhost');
define('SITE_EMAIL', 'contact@bihakcenter.org');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'bihak');

// Environment
define('ENVIRONMENT', 'development'); // development, staging, production

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Africa/Kigali');
?>
