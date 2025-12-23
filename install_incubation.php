<?php
// Temporary installation script for incubation platform
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Installing Incubation Platform Database...</h2>\n";

// Database connection - Try multiple connection methods
$conn = null;
$last_error = '';
$hosts = ['127.0.0.1', 'localhost', '::1'];

// Try each host
foreach ($hosts as $host) {
    $test_conn = @new mysqli($host, 'root', '');
    if (!$test_conn->connect_error) {
        // Connection successful, now select database
        if (@$test_conn->select_db('bihak')) {
            $conn = $test_conn;
            echo "<p style='color:green'>✓ Connected using: <strong>$host</strong></p>\n";
            break;
        } else {
            $last_error = "Connected to $host but could not select 'bihak' database";
            $test_conn->close();
        }
    } else {
        $last_error = $test_conn->connect_error;
    }
}

// If no connection worked, show detailed error and troubleshooting
if (!$conn) {
    echo "<div style='border:2px solid #dc3545;padding:20px;background:#fff5f5;'>";
    echo "<h3 style='color:#dc3545;'>❌ Database Connection Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($last_error) . "</p>";
    echo "<hr>";
    echo "<h4>Quick Fix Options:</h4>";
    echo "<p><strong>Option 1: Use Command Line (Fastest)</strong></p>";
    echo "<pre style='background:#f4f4f4;padding:15px;border:1px solid #ddd;overflow-x:auto;'>cd C:\\xampp\\mysql\\bin\nmysql -u root bihak < C:\\xampp\\htdocs\\bihak-center\\includes\\incubation_platform_schema.sql</pre>";
    echo "<p><strong>Option 2: Fix Permissions</strong></p>";
    echo "<pre style='background:#f4f4f4;padding:15px;border:1px solid #ddd;overflow-x:auto;'>cd C:\\xampp\\mysql\\bin\nmysql -u root -e \"GRANT ALL ON *.* TO 'root'@'localhost'; GRANT ALL ON *.* TO 'root'@'127.0.0.1'; FLUSH PRIVILEGES;\"</pre>";
    echo "<p><strong>Option 3: Diagnostic</strong></p>";
    echo "<p><a href='diagnose_db.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;'>Run Database Diagnostic Tool</a></p>";
    echo "</div>";
    die();
}

// Read the SQL file
$sql_file = __DIR__ . '/includes/incubation_platform_schema.sql';
if (!file_exists($sql_file)) {
    die("<p style='color:red'>Error: Schema file not found at: $sql_file</p>");
}

echo "<p>Reading schema file...</p>\n";
$sql = file_get_contents($sql_file);

if ($sql === false) {
    die("<p style='color:red'>Error: Could not read schema file</p>");
}

echo "<p>Schema file loaded (" . number_format(strlen($sql)) . " bytes)</p>\n";

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    }
);

echo "<p>Found " . count($statements) . " SQL statements to execute</p>\n";
echo "<hr>\n";

// Execute each statement
$success_count = 0;
$error_count = 0;

foreach ($statements as $index => $statement) {
    if (empty($statement)) continue;

    // Show progress for major operations
    if (preg_match('/^(CREATE TABLE|INSERT INTO|ALTER TABLE|CREATE INDEX)/i', $statement, $matches)) {
        $operation = $matches[1];
        if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $table_match)) {
            echo "<p>Creating table: <strong>{$table_match[1]}</strong>... ";
        } else if (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $table_match)) {
            echo "<p>Inserting data into: <strong>{$table_match[1]}</strong>... ";
        } else {
            echo "<p>Executing: <strong>$operation</strong>... ";
        }
        flush();
    }

    if ($conn->query($statement) === TRUE) {
        $success_count++;
        if (preg_match('/^(CREATE TABLE|INSERT INTO|ALTER TABLE|CREATE INDEX)/i', $statement)) {
            echo "<span style='color:green'>✓ Success</span></p>\n";
            flush();
        }
    } else {
        $error_count++;
        $error_msg = $conn->error;
        if (preg_match('/^(CREATE TABLE|INSERT INTO|ALTER TABLE|CREATE INDEX)/i', $statement)) {
            echo "<span style='color:red'>✗ Error: $error_msg</span></p>\n";
        } else {
            echo "<p style='color:red'>Error executing statement #$index: $error_msg</p>\n";
        }
        flush();
    }
}

echo "<hr>\n";
echo "<h3>Installation Summary:</h3>\n";
echo "<p><strong>Success:</strong> <span style='color:green'>$success_count</span> statements executed successfully</p>\n";
echo "<p><strong>Errors:</strong> <span style='color:" . ($error_count > 0 ? 'red' : 'green') . "'>$error_count</span> errors encountered</p>\n";

// Verify tables were created
echo "<hr>\n";
echo "<h3>Verification:</h3>\n";

$result = $conn->query("SHOW TABLES LIKE 'incubation%'");
if ($result) {
    $table_count = $result->num_rows;
    echo "<p>Found <strong>$table_count</strong> incubation tables:</p>\n";
    echo "<ul>\n";
    while ($row = $result->fetch_array()) {
        echo "<li>{$row[0]}</li>\n";
    }
    echo "</ul>\n";
}

// Check for initial data
$result = $conn->query("SELECT COUNT(*) as cnt FROM incubation_programs");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Incubation programs: <strong>{$row['cnt']}</strong></p>\n";
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM program_phases");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Program phases: <strong>{$row['cnt']}</strong></p>\n";
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM program_exercises");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Program exercises: <strong>{$row['cnt']}</strong></p>\n";
}

$conn->close();

echo "<hr>\n";
if ($error_count == 0) {
    echo "<h2 style='color:green'>✓ Installation Complete!</h2>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li><a href='public/incubation-program.php'>View Incubation Program Landing Page</a></li>\n";
    echo "<li><a href='public/incubation-dashboard-v2.php'>View Enhanced Dashboard</a></li>\n";
    echo "<li><a href='public/incubation-showcase.php'>View Project Showcase</a></li>\n";
    echo "</ul>\n";
    echo "<p style='color:orange'><strong>Important:</strong> Delete this file (install_incubation.php) after installation for security.</p>\n";
} else {
    echo "<h2 style='color:orange'>⚠ Installation completed with errors</h2>\n";
    echo "<p>Please review the errors above and try again if needed.</p>\n";
}
?>
