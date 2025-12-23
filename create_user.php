<?php
/**
 * Create New User Script
 * Run this once to create a new user account
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli('127.0.0.1', 'root', '', 'bihak');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Create New User</h2>\n";

// User details
$email = 'newuser@example.com';
$password = 'NewUser2025!';
$full_name = 'Test User';

// Hash the password
$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Check if user already exists
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color:orange;'>⚠ User with email <strong>$email</strong> already exists!</p>\n";

    // Update the password for existing user
    $update_stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
    $update_stmt->bind_param("ss", $password_hash, $email);

    if ($update_stmt->execute()) {
        echo "<p style='color:green;'>✓ Password updated successfully!</p>\n";
    } else {
        echo "<p style='color:red;'>✗ Error updating password: " . $conn->error . "</p>\n";
    }
    $update_stmt->close();

} else {
    // Create new user
    $stmt = $conn->prepare("
        INSERT INTO users (
            email,
            password_hash,
            full_name,
            email_verified,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, 1, NOW(), NOW())
    ");

    $stmt->bind_param("sss", $email, $password_hash, $full_name);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        echo "<p style='color:green;'>✓ User created successfully!</p>\n";
        echo "<p><strong>User ID:</strong> $user_id</p>\n";

        // Create a basic profile for this user
        $profile_stmt = $conn->prepare("
            INSERT INTO profiles (
                user_id,
                full_name,
                email,
                title,
                short_description,
                full_story,
                country,
                city,
                district,
                education_level,
                approval_status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())
        ");

        $title = "Test User Profile";
        $short_desc = "This is a test user account for login purposes.";
        $full_story = "This profile was automatically created for testing purposes.";
        $country = "Rwanda";
        $city = "Kigali";
        $district = "Gasabo";
        $edu_level = "University";

        $profile_stmt->bind_param(
            "isssssssss",
            $user_id,
            $full_name,
            $email,
            $title,
            $short_desc,
            $full_story,
            $country,
            $city,
            $district,
            $edu_level
        );

        if ($profile_stmt->execute()) {
            echo "<p style='color:green;'>✓ Profile created successfully!</p>\n";
        } else {
            echo "<p style='color:orange;'>⚠ Profile creation failed: " . $conn->error . "</p>\n";
        }
        $profile_stmt->close();

    } else {
        echo "<p style='color:red;'>✗ Error creating user: " . $conn->error . "</p>\n";
    }
    $stmt->close();
}

$check_stmt->close();

echo "<hr>\n";
echo "<div style='border: 2px solid #28a745; padding: 20px; background: #d4edda; border-radius: 8px;'>\n";
echo "<h3 style='color: #28a745;'>✓ Login Credentials</h3>\n";
echo "<p><strong>Email:</strong> <code>$email</code></p>\n";
echo "<p><strong>Password:</strong> <code>$password</code></p>\n";
echo "<hr>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ol>\n";
echo "<li><a href='public/login.php' style='color: #007bff;'>Go to Login Page →</a></li>\n";
echo "<li>Use the credentials above to login</li>\n";
echo "<li><strong>IMPORTANT:</strong> Delete this file (create_user.php) after use for security</li>\n";
echo "</ol>\n";
echo "</div>\n";

$conn->close();
?>
