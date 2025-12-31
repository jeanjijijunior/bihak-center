<?php
// Check opportunities table schema
require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

echo "=== OPPORTUNITIES TABLE SCHEMA ===\n\n";

$result = $conn->query("DESCRIBE opportunities");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        printf("%-20s %-20s %-10s %-10s %-20s %s\n",
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
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['status'] . "\n";
    }
}

closeDatabaseConnection($conn);
