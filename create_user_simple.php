<?php
/**
 * Simple User Creation Script
 * Creates a user with a known password
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Create New User</h2>\n";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{background:#e7f3ff;padding:15px;border-radius:5px;margin:20px 0;}</style>\n";

// Database connection
$conn = new mysqli('127.0.0.1', 'root', '', 'bihak');

if ($conn->connect_error) {
    die("<p class='error'>✗ Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p class='success'>✓ Connected to database</p>\n";

// First, let's check what columns exist in the users table
$columns_result = $conn->query("DESCRIBE users");
if ($columns_result) {
    echo "<p><strong>Users table columns:</strong></p><ul>\n";
    $available_columns = [];
    while ($col = $columns_result->fetch_assoc()) {
        echo "<li>{$col['Field']} ({$col['Type']})</li>\n";
        $available_columns[] = $col['Field'];
    }
    echo "</ul>\n";
} else {
    die("<p class='error'>✗ Could not read users table structure</p>");
}

// User credentials
$email = 'newuser@example.com';
$password = 'NewUser2025!';
$full_name = 'Test User';

// Hash the password
$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "<hr>\n";

// Check if email already exists
$check = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($check && $check->num_rows > 0) {
    $user = $check->fetch_assoc();
    $user_id = $user['id'];

    echo "<p class='info'>⚠ User already exists with ID: $user_id</p>\n";
    echo "<p>Updating password...</p>\n";

    // Update password
    $update_sql = "UPDATE users SET password_hash = '$password_hash' WHERE email = '$email'";
    if ($conn->query($update_sql)) {
        echo "<p class='success'>✓ Password updated!</p>\n";
    } else {
        echo "<p class='error'>✗ Error updating: " . $conn->error . "</p>\n";
    }

} else {
    echo "<p>Creating new user...</p>\n";

    // Build INSERT based on available columns
    $insert_sql = "INSERT INTO users (email, password_hash";

    // Add optional columns if they exist
    if (in_array('full_name', $available_columns)) {
        $insert_sql .= ", full_name";
    }
    if (in_array('email_verified', $available_columns)) {
        $insert_sql .= ", email_verified";
    }
    if (in_array('created_at', $available_columns)) {
        $insert_sql .= ", created_at";
    }

    $insert_sql .= ") VALUES ('$email', '$password_hash'";

    if (in_array('full_name', $available_columns)) {
        $insert_sql .= ", '$full_name'";
    }
    if (in_array('email_verified', $available_columns)) {
        $insert_sql .= ", 1";
    }
    if (in_array('created_at', $available_columns)) {
        $insert_sql .= ", NOW()";
    }

    $insert_sql .= ")";

    echo "<p><small>SQL: " . htmlspecialchars($insert_sql) . "</small></p>\n";

    if ($conn->query($insert_sql)) {
        $user_id = $conn->insert_id;
        echo "<p class='success'>✓ User created! ID: $user_id</p>\n";

        // Try to create profile if profiles table exists
        $profiles_check = $conn->query("SHOW TABLES LIKE 'profiles'");
        if ($profiles_check && $profiles_check->num_rows > 0) {
            echo "<p>Creating profile...</p>\n";

            $profile_sql = "INSERT INTO profiles (
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
                created_at
            ) VALUES (
                $user_id,
                '$full_name',
                '$email',
                'Test User Profile',
                'This is a test user account.',
                'This profile was created for testing purposes.',
                'Rwanda',
                'Kigali',
                'Gasabo',
                'University',
                'approved',
                NOW()
            )";

            if ($conn->query($profile_sql)) {
                echo "<p class='success'>✓ Profile created!</p>\n";
            } else {
                echo "<p class='error'>✗ Profile creation failed: " . $conn->error . "</p>\n";
            }
        }

    } else {
        echo "<p class='error'>✗ Error creating user: " . $conn->error . "</p>\n";
    }
}

$conn->close();

echo "<hr>\n";
echo "<div style='border:2px solid #28a745;padding:20px;background:#d4edda;border-radius:8px;'>\n";
echo "<h3 style='color:#28a745;'>✓ Login Credentials</h3>\n";
echo "<p><strong>Email:</strong> <code style='background:#fff;padding:5px 10px;border-radius:4px;'>$email</code></p>\n";
echo "<p><strong>Password:</strong> <code style='background:#fff;padding:5px 10px;border-radius:4px;'>$password</code></p>\n";
echo "<hr style='margin:20px 0;'>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ol>\n";
echo "<li><a href='public/login.php' style='color:#007bff;font-weight:bold;'>Go to Login Page →</a></li>\n";
echo "<li>Use the credentials above</li>\n";
echo "<li><strong>Important:</strong> Delete this file after use!</li>\n";
echo "</ol>\n";
echo "</div>\n";
?>
