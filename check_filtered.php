<?php
require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

// Check what the opportunities.php query would return
$sql = "SELECT o.*, DATEDIFF(o.deadline, CURDATE()) as days_remaining
        FROM opportunities o
        WHERE o.is_active = TRUE
        AND (o.deadline IS NULL OR o.deadline >= CURDATE())
        AND o.application_url IS NOT NULL
        AND o.application_url != ''
        AND o.application_url NOT LIKE '%example.com%'
        AND o.application_url NOT LIKE '%test%'
        AND o.application_url NOT LIKE '%localhost%'";

$result = $conn->query($sql);
echo "Opportunities that PASS the filter: " . $result->num_rows . "\n\n";

// Check what gets filtered OUT
$sql2 = "SELECT COUNT(*) as count
         FROM opportunities o
         WHERE o.is_active = TRUE
         AND (o.deadline IS NULL OR o.deadline >= CURDATE())
         AND (o.application_url IS NULL
              OR o.application_url = ''
              OR o.application_url LIKE '%example.com%'
              OR o.application_url LIKE '%test%'
              OR o.application_url LIKE '%localhost%')";

$result2 = $conn->query($sql2);
$row = $result2->fetch_assoc();
echo "Opportunities FILTERED OUT: " . $row['count'] . "\n\n";

// Show some filtered out opportunities
echo "Sample of filtered out opportunities:\n";
$sql3 = "SELECT title, application_url
         FROM opportunities o
         WHERE o.is_active = TRUE
         AND (o.deadline IS NULL OR o.deadline >= CURDATE())
         AND (o.application_url IS NULL
              OR o.application_url = ''
              OR o.application_url LIKE '%example.com%'
              OR o.application_url LIKE '%test%'
              OR o.application_url LIKE '%localhost%')
         LIMIT 10";

$result3 = $conn->query($sql3);
while ($row = $result3->fetch_assoc()) {
    echo "- " . $row['title'] . "\n  URL: " . ($row['application_url'] ?? 'NULL') . "\n\n";
}

closeDatabaseConnection($conn);
?>
