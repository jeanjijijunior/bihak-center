<?php
/**
 * Debug Opportunities Page Issue
 */

require_once __DIR__ . '/config/database.php';

echo "=== Debugging Opportunities Issue ===\n\n";

try {
    $conn = getDatabaseConnection();

    // Check total opportunities
    $result = $conn->query("SELECT COUNT(*) as count FROM opportunities");
    $row = $result->fetch_assoc();
    echo "1. Total opportunities in database: " . $row['count'] . "\n\n";

    // Check type column
    $result = $conn->query("SHOW COLUMNS FROM opportunities WHERE Field='type'");
    $row = $result->fetch_assoc();
    echo "2. Type column definition: " . $row['Type'] . "\n\n";

    // Check opportunities by type
    echo "3. Opportunities by type:\n";
    $result = $conn->query("SELECT type, COUNT(*) as count FROM opportunities GROUP BY type");
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['type'] . ": " . $row['count'] . "\n";
    }

    // Check recent opportunities
    echo "\n4. Recent 5 opportunities:\n";
    $result = $conn->query("SELECT id, title, type, deadline FROM opportunities ORDER BY created_at DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "   - [" . $row['type'] . "] " . $row['title'] . " (ID: " . $row['id'] . ", Deadline: " . $row['deadline'] . ")\n";
    }

    // Test the query used in opportunities.php
    echo "\n5. Testing opportunities.php query (no filters):\n";
    $sql = "SELECT o.* FROM opportunities o WHERE 1=1 ORDER BY o.deadline ASC";
    $result = $conn->query($sql);
    echo "   Query returned " . $result->num_rows . " rows\n";

    // Check for NULL or invalid type values
    echo "\n6. Checking for invalid type values:\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM opportunities WHERE type IS NULL OR type NOT IN ('scholarship', 'job', 'internship', 'grant', 'competition')");
    $row = $result->fetch_assoc();
    echo "   Invalid type values: " . $row['count'] . "\n";

    closeDatabaseConnection($conn);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
