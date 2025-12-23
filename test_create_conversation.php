<?php
/**
 * Test Conversation Creation
 */

session_start();

// Simulate logged in user
$_SESSION['user_id'] = 2;

echo "<h1>Testing Conversation Creation API</h1>";
echo "<p>Simulating user_id = 2 creating conversation with admin_id = 2</p>";

// Test the API with cURL
$url = 'http://localhost/bihak-center/api/messaging/conversations.php';

$post_data = json_encode([
    'type' => 'direct',
    'participants' => [
        ['type' => 'admin', 'id' => 2]
    ]
]);

echo "<h2>Request Data:</h2>";
echo "<pre>" . htmlspecialchars($post_data) . "</pre>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "<h2>Response (HTTP $http_code):</h2>";
if ($error) {
    echo "<p style='color:red'>cURL Error: $error</p>";
}

echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

if ($http_code == 200) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<h2>Parsed JSON:</h2>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<p style='color:red'>Failed to parse JSON. Error: " . json_last_error_msg() . "</p>";
    }
} else {
    echo "<p style='color:red'>HTTP Error: $http_code</p>";
}

curl_close($ch);
?>
