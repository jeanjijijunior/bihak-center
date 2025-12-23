<?php
/**
 * Test Mentor Password Verification
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

$email = 'mentor@bihakcenter.org';
$password = 'Mentor@123';

echo "Testing mentor login...\n\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

// Get sponsor data
$stmt = $conn->prepare("
    SELECT id, full_name, email, password_hash, role_type, status, is_active
    FROM sponsors
    WHERE email = ?
");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$sponsor = $result->fetch_assoc();
$stmt->close();

if (!$sponsor) {
    echo "❌ Sponsor not found!\n";
    exit;
}

echo "✅ Sponsor found:\n";
echo "   ID: " . $sponsor['id'] . "\n";
echo "   Name: " . $sponsor['full_name'] . "\n";
echo "   Role: " . $sponsor['role_type'] . "\n";
echo "   Status: " . $sponsor['status'] . "\n";
echo "   Active: " . $sponsor['is_active'] . "\n";
echo "   Has password_hash: " . ($sponsor['password_hash'] ? 'Yes' : 'No') . "\n\n";

if (!$sponsor['password_hash']) {
    echo "❌ No password hash set!\n";
    exit;
}

// Test password verification
echo "Testing password verification...\n";
$verify = password_verify($password, $sponsor['password_hash']);

if ($verify) {
    echo "✅ Password verification SUCCESS!\n";
    echo "   The password 'Mentor@123' matches the hash.\n";
} else {
    echo "❌ Password verification FAILED!\n";
    echo "   The password 'Mentor@123' does NOT match the hash.\n\n";

    // Try to generate a new hash
    echo "Generating new password hash for 'Mentor@123'...\n";
    $new_hash = password_hash($password, PASSWORD_BCRYPT);
    echo "New hash: $new_hash\n\n";

    echo "SQL to update password:\n";
    echo "UPDATE sponsors SET password_hash = '$new_hash' WHERE email = '$email';\n";
}

closeDatabaseConnection($conn);
?>
