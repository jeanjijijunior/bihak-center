<?php
/**
 * End Mentorship API
 * POST /api/mentorship/end.php
 *
 * End an active mentorship relationship
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MentorshipManager.php';

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['mentor_id']) && !isset($_SESSION['sponsor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$conn = getDatabaseConnection();
$mentorshipManager = new MentorshipManager($conn);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Validate required fields
    if (!isset($data['relationship_id']) || !isset($data['reason'])) {
        throw new Exception('relationship_id and reason are required');
    }

    $relationship_id = intval($data['relationship_id']);
    $reason = trim($data['reason']);

    // Determine ender
    if (isset($_SESSION['mentor_id']) || isset($_SESSION['sponsor_id'])) {
        $ender_id = $_SESSION['mentor_id'] ?? $_SESSION['sponsor_id'];
        $ender_type = 'mentor';
    } else {
        $ender_id = $_SESSION['user_id'];
        $ender_type = 'mentee';
    }

    // End relationship
    $result = $mentorshipManager->endRelationship($relationship_id, $ender_id, $ender_type, $reason);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

closeDatabaseConnection($conn);
?>
