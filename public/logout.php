<?php
/**
 * Universal Logout
 * Securely logs out users, sponsors/mentors, and admins
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Determine which type of user is logging out
$redirect_to = 'login.php';

if (isset($_SESSION['admin_id'])) {
    // Admin logout
    $redirect_to = 'admin/login.php';
} elseif (isset($_SESSION['sponsor_id'])) {
    // Sponsor/Mentor logout
    $redirect_to = 'login.php';
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to appropriate login page with success message
header("Location: $redirect_to?logout=success");
exit;
?>
