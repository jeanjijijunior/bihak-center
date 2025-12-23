<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Diagnostic</h2>\n";

// Test different connection methods
$methods = [
    ['label' => '127.0.0.1:3306', 'host' => '127.0.0.1', 'port' => 3306],
    ['label' => 'localhost:3306', 'host' => 'localhost', 'port' => 3306],
    ['label' => '::1:3306 (IPv6)', 'host' => '::1', 'port' => 3306],
];

echo "<h3>Testing Connection Methods:</h3>\n";
$working_connection = null;

foreach ($methods as $method) {
    echo "<p>Testing <strong>{$method['label']}</strong>... ";
    flush();

    $conn = @new mysqli($method['host'], 'root', '', '', $method['port']);

    if ($conn->connect_error) {
        echo "<span style='color:red'>✗ Failed: {$conn->connect_error}</span></p>\n";
    } else {
        echo "<span style='color:green'>✓ Success!</span></p>\n";
        $working_connection = $conn;

        // Check if bihak database exists
        $result = $conn->query("SHOW DATABASES LIKE 'bihak'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='margin-left:20px;'>→ 'bihak' database exists</p>\n";

            // Try selecting it
            if ($conn->select_db('bihak')) {
                echo "<p style='margin-left:20px;color:green'>→ Successfully selected 'bihak' database</p>\n";

                // Check for existing incubation tables
                $result = $conn->query("SHOW TABLES LIKE 'incubation%'");
                if ($result) {
                    $count = $result->num_rows;
                    echo "<p style='margin-left:20px;'>→ Found $count existing incubation tables</p>\n";
                }
            } else {
                echo "<p style='margin-left:20px;color:red'>→ Failed to select 'bihak' database: {$conn->error}</p>\n";
            }
        } else {
            echo "<p style='margin-left:20px;color:orange'>→ 'bihak' database does not exist</p>\n";
        }

        $conn->close();
        break; // Use first working connection
    }
    flush();
}

if (!$working_connection) {
    echo "<hr>\n";
    echo "<h3 style='color:red'>❌ No working connection found</h3>\n";
    echo "<p><strong>Solution:</strong> You need to fix MariaDB user permissions.</p>\n";
    echo "<h4>Steps to fix:</h4>\n";
    echo "<ol>\n";
    echo "<li>Open XAMPP Control Panel</li>\n";
    echo "<li>Click 'Shell' button</li>\n";
    echo "<li>Run these commands:</li>\n";
    echo "</ol>\n";
    echo "<pre style='background:#f0f0f0;padding:10px;'>\n";
    echo "cd C:\\xampp\\mysql\\bin\n";
    echo "mysql -u root\n\n";
    echo "-- Then in MySQL prompt, run:\n";
    echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;\n";
    echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;\n";
    echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'::1' WITH GRANT OPTION;\n";
    echo "FLUSH PRIVILEGES;\n";
    echo "</pre>\n";
} else {
    echo "<hr>\n";
    echo "<h3 style='color:green'>✓ Connection successful!</h3>\n";
    echo "<p><a href='install_incubation.php' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Proceed to Installation →</a></p>\n";
}
?>
