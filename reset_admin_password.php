<?php
/**
 * Reset Admin Password
 * Run this script once to reset the admin password
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';

// NEW PASSWORD - Change this to whatever you want
$new_password = 'Admin@123';
$admin_username = 'admin';  // or use 'jjunior'

try {
    $conn = getDatabaseConnection();

    // Hash the new password
    $password_hash = Security::hashPassword($new_password);

    // Update the password in admins table
    $stmt = $conn->prepare("UPDATE admins SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE username = ?");
    $stmt->bind_param('ss', $password_hash, $admin_username);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<h2 style='color: green;'>✓ Admin Password Reset Successful!</h2>";
            echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<p><strong>Username:</strong> $admin_username</p>";
            echo "<p><strong>New Password:</strong> $new_password</p>";
            echo "</div>";
            echo "<hr>";
            echo "<p><a href='public/admin/login.php' style='display: inline-block; padding: 12px 24px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Admin Login</a></p>";
            echo "<hr>";
            echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin: 20px 0;'>";
            echo "<p style='color: #856404; margin: 0;'><strong>⚠ SECURITY WARNING:</strong> Delete this file (reset_admin_password.php) after using it!</p>";
            echo "</div>";

            echo "<hr>";
            echo "<h3>Available Admin Accounts:</h3>";
            $result = $conn->query("SELECT username, email, role, is_active FROM admins");
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f5f5f5;'><th>Username</th><th>Email</th><th>Role</th><th>Active</th></tr>";
            while ($row = $result->fetch_assoc()) {
                $active = $row['is_active'] ? '✓ Yes' : '✗ No';
                $highlight = ($row['username'] === $admin_username) ? "style='background: #e8f5e9;'" : "";
                echo "<tr $highlight>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                echo "<td>$active</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<h2 style='color: red;'>✗ Error:</h2>";
            echo "<p>Admin user '$admin_username' not found.</p>";
            echo "<p>Available usernames: admin, jjunior</p>";
        }
    } else {
        echo "<h2 style='color: red;'>✗ Error:</h2>";
        echo "<p>" . $stmt->error . "</p>";
    }

    $stmt->close();
    closeDatabaseConnection($conn);

} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
