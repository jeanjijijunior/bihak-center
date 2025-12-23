<?php
/**
 * Fix User Password - Direct Reset Tool
 * Use this when password reset isn't working for a user
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

// User to fix
$email = 'jjniyo@gmail.com';

echo "=== Password Reset Fix Tool ===\n\n";

// Get user
$stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("❌ User not found: $email\n");
}

echo "User Found:\n";
echo "  ID: {$user['id']}\n";
echo "  Name: {$user['full_name']}\n";
echo "  Email: {$user['email']}\n\n";

// Check security questions
$stmt = $conn->prepare("
    SELECT usa.id, sq.question_text, usa.answer_hash
    FROM user_security_answers usa
    JOIN security_questions sq ON usa.question_id = sq.id
    WHERE usa.user_id = ?
");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "Security Questions:\n";
foreach ($questions as $q) {
    echo "  - {$q['question_text']}\n";
}
echo "\n";

// Set new password
$new_password = 'password123'; // You can change this
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param('si', $password_hash, $user['id']);

if ($stmt->execute()) {
    echo "✅ SUCCESS! Password reset for {$user['email']}\n\n";
    echo "New Login Credentials:\n";
    echo "  Email: {$user['email']}\n";
    echo "  Password: $new_password\n\n";
    echo "The user can now login with these credentials.\n";
    echo "They should change their password after logging in.\n";
} else {
    echo "❌ Failed to reset password\n";
}

closeDatabaseConnection($conn);
