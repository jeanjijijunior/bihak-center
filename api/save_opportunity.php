<?php
/**
 * Save/Unsave Opportunity API
 * Allows logged-in users to save opportunities for later
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/security.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!UserAuth::check()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to save opportunities.'
    ]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);
    exit;
}

// Validate CSRF token
if (!isset($data['csrf_token']) || !Security::validateCSRFToken($data['csrf_token'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token.'
    ]);
    exit;
}

$opportunity_id = isset($data['opportunity_id']) ? intval($data['opportunity_id']) : 0;
$action = isset($data['action']) ? $data['action'] : '';
$user = UserAuth::user();

if (!$opportunity_id || !$user) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request parameters.'
    ]);
    exit;
}

$conn = getDatabaseConnection();

try {
    if ($action === 'save') {
        // Check if opportunity exists
        $stmt = $conn->prepare("SELECT id FROM opportunities WHERE id = ? AND is_active = TRUE");
        $stmt->bind_param('i', $opportunity_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            closeDatabaseConnection($conn);
            echo json_encode([
                'success' => false,
                'message' => 'Opportunity not found.'
            ]);
            exit;
        }

        // Save opportunity
        $stmt = $conn->prepare("
            INSERT INTO user_saved_opportunities (user_id, opportunity_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE saved_at = CURRENT_TIMESTAMP
        ");
        $stmt->bind_param('ii', $user['id'], $opportunity_id);
        $stmt->execute();

        // Log activity
        UserAuth::logActivity($user['id'], 'opportunity_saved', 'opportunity', $opportunity_id, 'User saved opportunity');

        closeDatabaseConnection($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Opportunity saved successfully!'
        ]);

    } elseif ($action === 'unsave') {
        // Remove saved opportunity
        $stmt = $conn->prepare("
            DELETE FROM user_saved_opportunities
            WHERE user_id = ? AND opportunity_id = ?
        ");
        $stmt->bind_param('ii', $user['id'], $opportunity_id);
        $stmt->execute();

        // Log activity
        UserAuth::logActivity($user['id'], 'opportunity_unsaved', 'opportunity', $opportunity_id, 'User unsaved opportunity');

        closeDatabaseConnection($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Opportunity removed from saved.'
        ]);

    } else {
        closeDatabaseConnection($conn);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action.'
        ]);
    }

} catch (Exception $e) {
    error_log('Save opportunity error: ' . $e->getMessage());
    closeDatabaseConnection($conn);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>
