<?php
/**
 * Signup Debug Tool - Shows detailed error information
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Signup Form Debug Information</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .info{background:#e7f3ff;padding:10px;margin:10px 0;border-left:4px solid #2196F3;} .error{background:#ffe7e7;padding:10px;margin:10px 0;border-left:4px solid #f44336;} .success{background:#e7ffe7;padding:10px;margin:10px 0;border-left:4px solid #4CAF50;}</style>";

// Test 1: Database Connection
echo "<h3>Test 1: Database Connection</h3>";
try {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDatabaseConnection();
    echo "<div class='success'>✓ Database connection successful!</div>";
    echo "<p>Host: 127.0.0.1, Database: bihak</p>";

    // Test 2: Check users table structure
    echo "<h3>Test 2: Users Table Structure</h3>";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<div class='success'>✓ Users table exists</div>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>✗ Users table not found!</div>";
    }

    // Test 3: Check profiles table structure
    echo "<h3>Test 3: Profiles Table Structure</h3>";
    $result = $conn->query("DESCRIBE profiles");
    if ($result) {
        echo "<div class='success'>✓ Profiles table exists</div>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>✗ Profiles table not found!</div>";
    }

    // Test 4: Check profile_media table
    echo "<h3>Test 4: Profile Media Table</h3>";
    $result = $conn->query("SHOW TABLES LIKE 'profile_media'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✓ Profile_media table exists</div>";
        $result = $conn->query("DESCRIBE profile_media");
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Field</th><th>Type</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>✗ Profile_media table not found!</div>";
    }

    closeDatabaseConnection($conn);

} catch (Exception $e) {
    echo "<div class='error'>✗ Database error: " . $e->getMessage() . "</div>";
}

// Test 5: Check Security class
echo "<h3>Test 5: Security Class</h3>";
try {
    require_once __DIR__ . '/../config/security.php';

    // Test CSRF token generation
    $token = Security::generateCSRFToken();
    echo "<div class='success'>✓ CSRF token generated: " . substr($token, 0, 20) . "...</div>";

    // Test password hashing
    $testPassword = "TestPassword123";
    $hash = Security::hashPassword($testPassword);
    echo "<div class='success'>✓ Password hashing works</div>";
    echo "<p>Sample hash: " . substr($hash, 0, 30) . "...</p>";

} catch (Exception $e) {
    echo "<div class='error'>✗ Security class error: " . $e->getMessage() . "</div>";
}

// Test 6: Check upload directory
echo "<h3>Test 6: Upload Directory</h3>";
$uploadDir = __DIR__ . '/../assets/uploads/profiles/';
if (!file_exists($uploadDir)) {
    echo "<div class='info'>⚠ Upload directory doesn't exist. Attempting to create...</div>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<div class='success'>✓ Upload directory created successfully</div>";
    } else {
        echo "<div class='error'>✗ Failed to create upload directory</div>";
    }
} else {
    echo "<div class='success'>✓ Upload directory exists</div>";
}

echo "<p>Path: $uploadDir</p>";
echo "<p>Writable: " . (is_writable($uploadDir) ? "Yes ✓" : "No ✗") . "</p>";

// Test 7: Test a signup attempt
echo "<h3>Test 7: Simplified Signup Test</h3>";
echo "<div class='info'>You can test the actual signup form at: <a href='signup.php'>signup.php</a></div>";

// Check if we're receiving POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='info'>POST data received:</div>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "<div class='info'>FILES data:</div>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p>If all tests passed, the signup form should work. If you're still seeing errors, check:</p>";
echo "<ol>";
echo "<li>Browser console (F12 → Console tab) for JavaScript errors</li>";
echo "<li>Network tab (F12 → Network) to see the actual response from process_signup.php</li>";
echo "<li>Make sure you're filling all required fields including at least one image</li>";
echo "</ol>";

echo "<p><a href='signup.php' style='display:inline-block;padding:10px 20px;background:#2196F3;color:white;text-decoration:none;border-radius:5px;'>Go to Signup Form</a></p>";
?>
