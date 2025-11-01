<?php
/**
 * User Logout
 * Securely logs out user and redirects to login page
 */

require_once __DIR__ . '/../config/user_auth.php';

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Perform logout
$result = UserAuth::logout();

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit;
?>
