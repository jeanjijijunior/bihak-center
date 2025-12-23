<?php
/**
 * Session Configuration
 * Handles secure session initialization for public pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Strict');

    session_start();
}

// Session timeout (1 hour)
$session_lifetime = 3600;

// Check if session has timed out
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    // Session expired
    session_unset();
    session_destroy();
    session_start();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
