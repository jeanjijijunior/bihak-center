<?php
/**
 * Database Configuration - EXAMPLE
 *
 * Copy this file to database.php and update with your local credentials.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bihak');

/**
 * Get database connection
 *
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDatabaseConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed. Please try again later.");
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Close database connection
 *
 * @param mysqli $conn Database connection to close
 */
function closeDatabaseConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
