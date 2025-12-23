<?php
/**
 * Test Password Reset System
 * Use this to verify password reset works and to manually reset a user's password if needed
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "=== Password Reset System Test ===\n\n";

// Check users
$users = $conn->query("SELECT id, email, full_name FROM users WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

echo "Active Users:\n";
foreach ($users as $user) {
    echo "  - {$user['full_name']} ({$user['email']}) - ID: {$user['id']}\n";

    // Check security questions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_security_answers WHERE user_id = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];

    if ($count >= 3) {
        echo "    ✅ Has {$count} security questions set up\n";
    } else {
        echo "    ❌ Only {$count} security questions (needs 3)\n";
    }
}

echo "\n=== Manual Password Reset ===\n";
echo "If you need to manually reset a user's password:\n\n";

// Prompt for user ID
if (php_sapi_name() === 'cli') {
    echo "Enter user ID to reset (or press Enter to skip): ";
    $user_id = trim(fgets(STDIN));

    if (!empty($user_id) && is_numeric($user_id)) {
        echo "Enter new password: ";
        $new_password = trim(fgets(STDIN));

        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $password_hash, $user_id);

            if ($stmt->execute()) {
                echo "\n✅ Password reset successfully for user ID {$user_id}\n";
                echo "   New password: {$new_password}\n";
                echo "   User can now login with this password.\n";
            } else {
                echo "\n❌ Failed to reset password\n";
            }
        }
    }
}

echo "\n=== Testing Password Reset Flow ===\n";
echo "1. Go to: http://localhost/public/forgot-password.php\n";
echo "2. Enter user email\n";
echo "3. Answer 3 security questions\n";
echo "4. Set new password\n";
echo "\n✅ System ready for testing!\n";

closeDatabaseConnection($conn);
