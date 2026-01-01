<?php
/**
 * Run Database Migration - Add Competition Type
 * This script adds 'competition' to the opportunities type ENUM
 */

require_once __DIR__ . '/config/database.php';

echo "=== Running Database Migration ===\n\n";

try {
    $conn = getDatabaseConnection();

    // Run the ALTER TABLE query
    $sql = "ALTER TABLE opportunities MODIFY COLUMN type ENUM('scholarship', 'job', 'internship', 'grant', 'competition') NOT NULL";

    echo "Executing migration...\n";

    if ($conn->query($sql)) {
        echo "✓ Migration successful: 'competition' type added to opportunities table\n\n";

        // Verify the change
        $result = $conn->query("SHOW COLUMNS FROM opportunities WHERE Field='type'");
        if ($row = $result->fetch_assoc()) {
            echo "Verified column type: " . $row['Type'] . "\n";
        }

        echo "\n✓ Migration completed successfully!\n";
    } else {
        echo "✗ Migration failed: " . $conn->error . "\n";
        exit(1);
    }

    closeDatabaseConnection($conn);

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
