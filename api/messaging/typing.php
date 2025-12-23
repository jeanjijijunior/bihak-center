<?php
/**
 * API Endpoint: Typing Indicators
 *
 * POST   - Set typing indicator
 * DELETE - Remove typing indicator
 * GET    - Get who's typing in a conversation
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
            // Get who's typing
            if (!isset($_GET['conversation_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing conversation_id']);
                exit;
            }

            $conversation_id = intval($_GET['conversation_id']);
            $typing_users = $messagingManager->getTypingUsers($conversation_id);

            echo json_encode([
                'success' => true,
                'data' => $typing_users,
                'count' => count($typing_users)
            ]);
            break;

        case 'POST':
            // Set typing
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!isset($data['conversation_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing conversation_id']);
                exit;
            }

            $success = $messagingManager->setTyping(
                intval($data['conversation_id']),
                $participant_type,
                $participant_id
            );

            echo json_encode(['success' => $success]);
            break;

        case 'DELETE':
            // Remove typing
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!isset($data['conversation_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing conversation_id']);
                exit;
            }

            $success = $messagingManager->removeTyping(
                intval($data['conversation_id']),
                $participant_type,
                $participant_id
            );

            echo json_encode(['success' => $success]);
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
