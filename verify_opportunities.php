<?php
/**
 * Verify Opportunities in Database
 */

require_once __DIR__ . '/config/database.php';

echo "=== Verifying Opportunities Database ===\n\n";

try {
    $conn = getDatabaseConnection();

    // Count opportunities by type
    $types = ['scholarship', 'job', 'internship', 'grant', 'competition'];

    foreach ($types as $type) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM opportunities WHERE type = ?");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        echo ucfirst($type) . "s: " . $row['count'] . "\n";
    }

    // Total count
    $result = $conn->query("SELECT COUNT(*) as count FROM opportunities");
    $row = $result->fetch_assoc();
    echo "\nTotal Opportunities: " . $row['count'] . "\n\n";

    // Show recent competitions
    echo "=== Recent Competitions ===\n";
    $result = $conn->query("SELECT title, organization, deadline FROM opportunities WHERE type = 'competition' ORDER BY created_at DESC LIMIT 5");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['title'] . " (" . $row['organization'] . ") - Deadline: " . $row['deadline'] . "\n";
        }
    } else {
        echo "No competitions found.\n";
    }

    closeDatabaseConnection($conn);

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
