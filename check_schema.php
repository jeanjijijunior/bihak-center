<?php
/**
 * Check Opportunities Table Schema
 * Diagnose database schema issues
 */

// Load database configuration
require_once __DIR__ . '/config/database.php';

try {
    $conn = getDatabaseConnection();

    echo "=== OPPORTUNITIES TABLE SCHEMA ===\n\n";

    $result = $conn->query("DESCRIBE opportunities");
    if ($result) {
        printf("%-25s %-25s %-8s %-8s %-20s %s\n",
            "Field", "Type", "Null", "Key", "Default", "Extra");
        echo str_repeat("-", 100) . "\n";

        while ($row = $result->fetch_assoc()) {
            printf("%-25s %-25s %-8s %-8s %-20s %s\n",
                $row['Field'],
                $row['Type'],
                $row['Null'],
                $row['Key'],
                $row['Default'] ?? 'NULL',
                $row['Extra']
            );
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }

    echo "\n=== SAMPLE STATUS VALUES ===\n\n";
    $result = $conn->query("SELECT DISTINCT status FROM opportunities LIMIT 10");
    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "- '" . $row['status'] . "'\n";
            }
        } else {
            echo "No opportunities in database yet.\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }

    echo "\n=== TOTAL OPPORTUNITIES COUNT ===\n\n";
    $result = $conn->query("SELECT COUNT(*) as total FROM opportunities");
    if ($result) {
        $count = $result->fetch_assoc()['total'];
        echo "Total: " . $count . " opportunities\n";
    }

    closeDatabaseConnection($conn);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
