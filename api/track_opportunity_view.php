<?php
/**
 * Track Opportunity View API
 * Increments view count when user clicks on opportunity
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['opportunity_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);
    exit;
}

$opportunity_id = intval($data['opportunity_id']);

if (!$opportunity_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid opportunity ID.'
    ]);
    exit;
}

$conn = getDatabaseConnection();

try {
    // Increment view count
    $stmt = $conn->prepare("
        UPDATE opportunities
        SET views_count = views_count + 1
        WHERE id = ? AND is_active = TRUE
    ");
    $stmt->bind_param('i', $opportunity_id);
    $stmt->execute();

    closeDatabaseConnection($conn);

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    error_log('Track view error: ' . $e->getMessage());
    closeDatabaseConnection($conn);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred.'
    ]);
}
?>
