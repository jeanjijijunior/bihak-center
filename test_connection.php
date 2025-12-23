<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Connection Test</h2>\n";

// Test 1: Regular connection like admin pages use
echo "<h3>Test 1: Standard Connection (localhost, root, no password)</h3>\n";
$conn1 = @new mysqli('localhost', 'root', '', 'bihak');
if ($conn1->connect_error) {
    echo "<p style='color:red'>✗ Failed: {$conn1->connect_error}</p>\n";
} else {
    echo "<p style='color:green'>✓ Success!</p>\n";
    $result = $conn1->query("SELECT COUNT(*) as cnt FROM usagers");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>→ Found {$row['cnt']} users in database</p>\n";
    }
    echo "<p>→ Connection info: Host={$conn1->host_info}</p>\n";
    $conn1->close();
}

// Test 2: 127.0.0.1
echo "<h3>Test 2: Using 127.0.0.1</h3>\n";
$conn2 = @new mysqli('127.0.0.1', 'root', '', 'bihak');
if ($conn2->connect_error) {
    echo "<p style='color:red'>✗ Failed: {$conn2->connect_error}</p>\n";
} else {
    echo "<p style='color:green'>✓ Success!</p>\n";
    echo "<p>→ Connection info: Host={$conn2->host_info}</p>\n";
    $conn2->close();
}

// Test 3: Using socket (Unix socket or Windows named pipe)
echo "<h3>Test 3: PHP Default Connection</h3>\n";
$conn3 = @new mysqli(ini_get("mysqli.default_host") ?: 'localhost',
                      ini_get("mysqli.default_user") ?: 'root',
                      ini_get("mysqli.default_pw") ?: '',
                      'bihak');
if ($conn3->connect_error) {
    echo "<p style='color:red'>✗ Failed: {$conn3->connect_error}</p>\n";
} else {
    echo "<p style='color:green'>✓ Success!</p>\n";
    echo "<p>→ Host info: {$conn3->host_info}</p>\n";
    echo "<p>→ Protocol: " . ($conn3->client_info) . "</p>\n";
    $conn3->close();
}

// Show PHP MySQL settings
echo "<hr><h3>PHP MySQL Configuration:</h3>\n";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>\n";
echo "<tr><th>Setting</th><th>Value</th></tr>\n";
$settings = [
    'mysqli.default_host',
    'mysqli.default_user',
    'mysqli.default_port',
    'mysqli.default_socket',
    'pdo_mysql.default_socket'
];
foreach ($settings as $setting) {
    $value = ini_get($setting);
    echo "<tr><td>$setting</td><td>" . ($value ?: '<em>not set</em>') . "</td></tr>\n";
}
echo "</table>\n";

?>
