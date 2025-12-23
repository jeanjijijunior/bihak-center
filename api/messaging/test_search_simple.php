<?php
/**
 * Simple test for search API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simulate logged in user
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2;
}

echo "<h1>Testing Search API</h1>";

require_once __DIR__ . '/../../config/database.php';
$conn = getDatabaseConnection();

echo "<h2>Session Info:</h2>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET') . "\n";
echo "sponsor_id: " . ($_SESSION['sponsor_id'] ?? 'NOT SET') . "\n";
echo "</pre>";

// Determine participant type
$participant_type = null;
$participant_id = null;

if (isset($_SESSION['user_id'])) {
    $participant_type = 'user';
    $participant_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $participant_type = 'admin';
    $participant_id = $_SESSION['admin_id'];
} elseif (isset($_SESSION['sponsor_id'])) {
    $participant_type = 'mentor';
    $participant_id = $_SESSION['sponsor_id'];
}

echo "<h2>Participant:</h2>";
echo "<pre>";
echo "Type: $participant_type\n";
echo "ID: $participant_id\n";
echo "</pre>";

// For regular users - get their mentors and admins
echo "<h2>For Regular User - Query Mentors:</h2>";
$stmt = $conn->prepare("
    SELECT DISTINCT s.id, s.full_name as name, s.email, 'mentor' as type, s.organization
    FROM sponsors s
    INNER JOIN mentorship_relationships mr ON s.id = mr.mentor_id
    WHERE mr.mentee_id = ? AND mr.status = 'active' AND s.is_active = 1
    ORDER BY s.full_name
    LIMIT 5
");

if ($stmt) {
    $stmt->bind_param('i', $participant_id);
    $stmt->execute();
    $mentors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "<p>Found " . count($mentors) . " mentors:</p>";
    echo "<pre>" . print_r($mentors, true) . "</pre>";
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

echo "<h2>Query All Admins:</h2>";
$stmt = $conn->prepare("
    SELECT id, full_name as name, email, 'admin' as type
    FROM admins
    WHERE is_active = 1
    ORDER BY full_name
    LIMIT 5
");

if ($stmt) {
    $stmt->execute();
    $admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "<p>Found " . count($admins) . " admins:</p>";
    echo "<pre>" . print_r($admins, true) . "</pre>";
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

closeDatabaseConnection($conn);

echo "<hr>";
echo "<h2>Now test actual API:</h2>";
echo "<a href='search_users.php' target='_blank'>Click to test search_users.php</a>";
?>
