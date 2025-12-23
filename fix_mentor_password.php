<?php
/**
 * Fix Mentor Password
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

$email = 'mentor@bihakcenter.org';
$password = 'Mentor@123';

echo "Fixing mentor password...\n\n";

// Generate proper password hash
$password_hash = password_hash($password, PASSWORD_BCRYPT);

echo "Generated hash: $password_hash\n\n";

// Update using prepared statement
$stmt = $conn->prepare("UPDATE sponsors SET password_hash = ? WHERE email = ?");
$stmt->bind_param('ss', $password_hash, $email);

if ($stmt->execute()) {
    echo "✅ Password updated successfully!\n\n";

    // Verify it works
    $stmt = $conn->prepare("SELECT password_hash FROM sponsors WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password_hash'])) {
        echo "✅ Password verification SUCCESS!\n";
        echo "   You can now login with:\n";
        echo "   Email: $email\n";
        echo "   Password: $password\n";
    } else {
        echo "❌ Password verification still failed!\n";
    }
} else {
    echo "❌ Failed to update password: " . $stmt->error . "\n";
}

closeDatabaseConnection($conn);
?>
