<?php
/**
 * Donation Statistics API
 * Returns real-time donation statistics from the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDatabaseConnection();

    // Get statistics from the view
    $stmt = $conn->prepare("SELECT * FROM donation_stats LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    if ($stats) {
        // Format numbers
        $response = [
            'success' => true,
            'total_donations' => (int) $stats['total_donations'],
            'unique_donors' => (int) $stats['unique_donors'],
            'total_raised' => round((float) $stats['total_raised'], 2),
            'net_raised' => round((float) $stats['net_raised'], 2),
            'average_donation' => round((float) $stats['average_donation'], 2),
            'smallest_donation' => round((float) $stats['smallest_donation'], 2),
            'largest_donation' => round((float) $stats['largest_donation'], 2),
            'raised_this_year' => round((float) $stats['raised_this_year'], 2),
            'raised_this_month' => round((float) $stats['raised_this_month'], 2),
            'pending_donations' => (int) $stats['pending_donations'],
            'refunded_donations' => (int) $stats['refunded_donations'],
            'total_refunded' => round((float) $stats['total_refunded'], 2)
        ];
    } else {
        // No donations yet - return zeros
        $response = [
            'success' => true,
            'total_donations' => 0,
            'unique_donors' => 0,
            'total_raised' => 0,
            'net_raised' => 0,
            'average_donation' => 0,
            'smallest_donation' => 0,
            'largest_donation' => 0,
            'raised_this_year' => 0,
            'raised_this_month' => 0,
            'pending_donations' => 0,
            'refunded_donations' => 0,
            'total_refunded' => 0
        ];
    }

    $conn->close();
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch donation statistics'
    ]);
}
