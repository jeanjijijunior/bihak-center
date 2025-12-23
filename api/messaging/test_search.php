<?php
/**
 * Test Search Users API - Debug Version
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Search API Debug</h1>";

// Simulate user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; // Test user
    echo "<p>Set test session: user_id = 2</p>";
}

require_once __DIR__ . '/../../config/database.php';

$conn = getDatabaseConnection();

echo "<h2>Database Connection:</h2>";
if ($conn) {
    echo "<p style='color:green'>✓ Connected</p>";
} else {
    echo "<p style='color:red'>✗ Failed</p>";
    exit;
}

// Test admin query
echo "<h2>Testing Admin Query:</h2>";
$stmt = $conn->prepare("SELECT id, full_name as name, email, 'admin' as type FROM admins WHERE is_active = 1 LIMIT 5");
if ($stmt) {
    $stmt->execute();
    $admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "<p>Found " . count($admins) . " admins</p>";
    echo "<pre>" . print_r($admins, true) . "</pre>";
} else {
    echo "<p style='color:red'>Query failed: " . $conn->error . "</p>";
}

// Test users query
echo "<h2>Testing Users Query:</h2>";
$stmt = $conn->prepare("SELECT id, full_name as name, email, 'user' as type FROM users WHERE is_active = 1 LIMIT 5");
if ($stmt) {
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "<p>Found " . count($users) . " users</p>";
    echo "<pre>" . print_r($users, true) . "</pre>";
} else {
    echo "<p style='color:red'>Query failed: " . $conn->error . "</p>";
}

// Test mentors query
echo "<h2>Testing Mentors Query:</h2>";
$stmt = $conn->prepare("SELECT id, full_name as name, email, 'mentor' as type, organization FROM sponsors WHERE status = 'approved' AND is_active = 1 LIMIT 5");
if ($stmt) {
    $stmt->execute();
    $mentors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "<p>Found " . count($mentors) . " mentors</p>";
    echo "<pre>" . print_r($mentors, true) . "</pre>";
} else {
    echo "<p style='color:red'>Query failed: " . $conn->error . "</p>";
}

closeDatabaseConnection($conn);
?>
