<?php
/**
 * Test if opportunities.php works correctly
 */

// Simulate being logged in
session_start();
$_SESSION['user_id'] = 999; // Fake user ID for testing
$_SESSION['user_name'] = 'Test User';

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

// Run the exact same query as opportunities.php
$sql = "SELECT o.*, DATEDIFF(o.deadline, CURDATE()) as days_remaining
        FROM opportunities o
        WHERE o.is_active = TRUE
        AND (o.deadline IS NULL OR o.deadline >= CURDATE())
        AND o.application_url IS NOT NULL
        AND o.application_url != ''
        AND o.application_url NOT LIKE '%example.com%'
        AND o.application_url NOT LIKE '%test%'
        AND o.application_url NOT LIKE '%localhost%'
        ORDER BY o.deadline ASC";

$result = $conn->query($sql);

echo "Query executed successfully!\n";
echo "Number of opportunities found: " . $result->num_rows . "\n\n";

if ($result->num_rows > 0) {
    echo "First 5 opportunities:\n";
    $count = 0;
    while ($row = $result->fetch_assoc() && $count < 5) {
        echo ($count + 1) . ". " . $row['title'] . " (" . $row['type'] . ")\n";
        echo "   Deadline: " . $row['deadline'] . "\n";
        echo "   URL: " . $row['application_url'] . "\n\n";
        $count++;
    }
} else {
    echo "NO OPPORTUNITIES FOUND - This is the problem!\n";
}

closeDatabaseConnection($conn);
?>
