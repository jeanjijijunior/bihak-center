<?php
/**
 * Test Search Users API
 */

session_start();

// Simulate logged in user
$_SESSION['user_id'] = 2; // jjniyo@gmail.com user

echo "<h1>Testing Search Users API</h1>";
echo "<p>Simulating user_id = 2</p>";

// Test the API
$url = 'http://localhost/bihak-center/api/messaging/search_users.php';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "<h2>Response (HTTP $http_code):</h2>";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

if ($http_code == 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "<h2>Parsed Results:</h2>";
        echo "<p>Found " . count($data['results']) . " contacts</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Email</th></tr>";
        foreach ($data['results'] as $contact) {
            echo "<tr>";
            echo "<td>" . $contact['id'] . "</td>";
            echo "<td>" . htmlspecialchars($contact['name']) . "</td>";
            echo "<td>" . $contact['type'] . "</td>";
            echo "<td>" . htmlspecialchars($contact['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>API returned error or no success</p>";
    }
} else {
    echo "<p style='color:red'>HTTP Error: $http_code</p>";
}

curl_close($ch);
?>
