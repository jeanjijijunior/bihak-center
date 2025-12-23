<?php
/**
 * API Endpoint: Online Status
 *
 * POST - Update online status
 * GET  - Get online status for a user
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MessagingManager.php';

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['sponsor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conn = getDatabaseConnection();
$messagingManager = new MessagingManager($conn);

// Determine participant type and ID
$participant_type = null;
$participant_id = null;

if (isset($_SESSION['user_id'])) {
    $participant_type = 'user';
    $participant_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $participant_type = 'admin';
    $participant_id = $_SESSION['admin_id'];
} elseif (isset($_SESSION['sponsor_id'])) {
    $participant_type = 'mentor';
    $participant_id = $_SESSION['sponsor_id'];
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get status for another user
            if (!isset($_GET['type']) || !isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing parameters: type, id']);
                exit;
            }

            $status = $messagingManager->getOnlineStatus($_GET['type'], intval($_GET['id']));

            echo json_encode([
                'success' => true,
                'data' => $status
            ]);
            break;

        case 'POST':
            // Update current user's status
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            $status = $data['status'] ?? 'online';
            $valid_statuses = ['online', 'away', 'offline'];

            if (!in_array($status, $valid_statuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status. Must be: online, away, or offline']);
                exit;
            }

            $success = $messagingManager->updateOnlineStatus($participant_type, $participant_id, $status);

            echo json_encode(['success' => $success, 'status' => $status]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

closeDatabaseConnection($conn);
