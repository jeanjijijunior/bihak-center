<?php
/**
 * Mentorship Request API
 * POST /api/mentorship/request.php
 *
 * Request mentorship relationship
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
    // Determine who is requesting
    if (isset($data['mentor_id'])) {
        // Mentee is requesting a mentor
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not authenticated');
        }

        $mentor_id = intval($data['mentor_id']);
        $mentee_id = $_SESSION['user_id'];
        $requested_by = 'mentee';

    } elseif (isset($data['mentee_id'])) {
        // Mentor is offering to mentor a mentee
        if (!isset($_SESSION['mentor_id']) && !isset($_SESSION['sponsor_id'])) {
            throw new Exception('Not authorized as mentor');
        }

        $mentor_id = $_SESSION['mentor_id'] ?? $_SESSION['sponsor_id'];
        $mentee_id = intval($data['mentee_id']);
        $requested_by = 'mentor';

    } else {
        throw new Exception('Either mentor_id or mentee_id is required');
    }

    // Create mentorship request
    $result = $mentorshipManager->requestMentorship($mentor_id, $mentee_id, $requested_by);

    if ($result['success']) {
        http_response_code(201);
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
