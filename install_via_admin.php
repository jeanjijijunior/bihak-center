<?php
/**
 * Alternative Installation Script
 * Uses the same connection method as admin pages (which we know works)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Incubation Platform Installation (Alternative Method)</h2>\n";

// Use the same connection approach as the working admin pages
require_once __DIR__ . '/config/database.php';

try {
    echo "<p>Attempting database connection using admin method...</p>\n";
    flush();

    $conn = getDatabaseConnection();

    echo "<p style='color:green;'>✓ Successfully connected to database!</p>\n";
    echo "<p>→ Connection host: " . htmlspecialchars($conn->host_info) . "</p>\n";
    flush();

    // Read the SQL file
    $sql_file = __DIR__ . '/includes/incubation_platform_schema.sql';
    if (!file_exists($sql_file)) {
        die("<p style='color:red;'>Error: Schema file not found at: $sql_file</p>");
    }

    echo "<p>Reading schema file...</p>\n";
    flush();

    $sql = file_get_contents($sql_file);
    if ($sql === false) {
        die("<p style='color:red;'>Error: Could not read schema file</p>");
    }

    echo "<p>Schema file loaded (" . number_format(strlen($sql)) . " bytes)</p>\n";
    flush();

    // Remove comments and split into individual statements
    // Remove single-line comments (-- comments)
    $sql = preg_replace('/^--.*$/m', '', $sql);
    // Remove multi-line comments (/* comments */)
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split by semicolon and filter
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Keep non-empty statements that start with SQL keywords
            return !empty($stmt) && preg_match('/^(CREATE|INSERT|UPDATE|DELETE|ALTER|DROP|GRANT)/i', $stmt);
        }
    );

    echo "<p>Found " . count($statements) . " SQL statements to execute</p>\n";
    echo "<hr>\n";
    flush();

    // Execute each statement
    $success_count = 0;
    $error_count = 0;
    $errors = [];

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
                echo "<span style='color:green;'>✓ Success</span></p>\n";
                flush();
            }
        } else {
            $error_count++;
            $error_msg = $conn->error;

            // Ignore "table already exists" errors
            if (strpos($error_msg, 'already exists') === false) {
                $errors[] = [
                    'statement' => substr($statement, 0, 100) . '...',
                    'error' => $error_msg
                ];

                if (preg_match('/^(CREATE TABLE|INSERT INTO|ALTER TABLE|CREATE INDEX)/i', $statement)) {
                    echo "<span style='color:red;'>✗ Error: $error_msg</span></p>\n";
                } else {
                    echo "<p style='color:red;'>Error executing statement #$index: $error_msg</p>\n";
                }
                flush();
            } else {
                // Table exists - that's ok
                if (preg_match('/^(CREATE TABLE|INSERT INTO|ALTER TABLE|CREATE INDEX)/i', $statement)) {
                    echo "<span style='color:orange;'>⚠ Already exists</span></p>\n";
                    flush();
                }
                $error_count--; // Don't count this as an error
                $success_count++;
            }
        }
    }

    echo "<hr>\n";
    echo "<h3>Installation Summary:</h3>\n";
    echo "<p><strong>Success:</strong> <span style='color:green;'>$success_count</span> statements executed successfully</p>\n";
    echo "<p><strong>Errors:</strong> <span style='color:" . ($error_count > 0 ? 'red' : 'green') . "'>$error_count</span> errors encountered</p>\n";

    if (!empty($errors)) {
        echo "<details><summary>Show Errors</summary><ul>";
        foreach ($errors as $err) {
            echo "<li><strong>Statement:</strong> " . htmlspecialchars($err['statement']) . "<br>";
            echo "<strong>Error:</strong> " . htmlspecialchars($err['error']) . "</li>";
        }
        echo "</ul></details>";
    }

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
        echo "<p>✓ Incubation programs: <strong>{$row['cnt']}</strong></p>\n";
    }

    $result = $conn->query("SELECT COUNT(*) as cnt FROM program_phases");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>✓ Program phases: <strong>{$row['cnt']}</strong></p>\n";
    }

    $result = $conn->query("SELECT COUNT(*) as cnt FROM program_exercises");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>✓ Program exercises: <strong>{$row['cnt']}</strong></p>\n";
    }

    closeDatabaseConnection($conn);

    echo "<hr>\n";
    if ($error_count == 0) {
        echo "<div style='border:2px solid #28a745;padding:20px;background:#d4edda;'>";
        echo "<h2 style='color:#28a745;'>✓ Installation Complete!</h2>\n";
        echo "<p><strong>Next steps:</strong></p>\n";
        echo "<ul>\n";
        echo "<li><a href='public/incubation-program.php' style='color:#007bff;'>View Incubation Program Landing Page →</a></li>\n";
        echo "<li><a href='public/incubation-dashboard-v2.php' style='color:#007bff;'>View Enhanced Dashboard →</a></li>\n";
        echo "<li><a href='public/incubation-showcase.php' style='color:#007bff;'>View Project Showcase →</a></li>\n";
        echo "<li><a href='public/business-model-canvas.php' style='color:#007bff;'>View Business Model Canvas →</a></li>\n";
        echo "</ul>\n";
        echo "<p style='color:#856404;'><strong>Important:</strong> Delete install_via_admin.php and install_incubation.php after installation for security.</p>\n";
        echo "</div>";
    } else {
        echo "<h2 style='color:orange;'>⚠ Installation completed with errors</h2>\n";
        echo "<p>Please review the errors above. Some features may not work correctly.</p>\n";
    }

} catch (Exception $e) {
    echo "<div style='border:2px solid #dc3545;padding:20px;background:#f8d7da;'>";
    echo "<h3 style='color:#dc3545;'>❌ Installation Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr>";
    echo "<h4>What to try:</h4>";
    echo "<ol>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check that the admin pages work (login to admin panel)</li>";
    echo "<li>Run the <a href='diagnose_db.php'>diagnostic tool</a></li>";
    echo "</ol>";
    echo "</div>";
}
?>
