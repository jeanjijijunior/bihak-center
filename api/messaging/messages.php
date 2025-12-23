<?php
/**
 * API Endpoint: Messages
 *
 * GET    - Get messages in a conversation
 * POST   - Send a new message
 * PUT    - Edit a message
 * DELETE - Delete a message
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
            // Get messages in conversation
            if (!isset($_GET['conversation_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing conversation_id']);
                exit;
            }

            $conversation_id = intval($_GET['conversation_id']);
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            $messages = $messagingManager->getMessages($conversation_id, $participant_type, $participant_id, $limit, $offset);

            if ($messages === null) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not authorized to view this conversation']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => $messages,
                'count' => count($messages)
            ]);
            break;

        case 'POST':
            // Send message
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            error_log("POST /messages - Input: " . print_r($data, true));
            error_log("Sender: $participant_type ID: $participant_id");

            if (!isset($data['conversation_id']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields: conversation_id, content']);
                exit;
            }

            try {
                $result = $messagingManager->sendMessage(
                    intval($data['conversation_id']),
                    $participant_type,
                    $participant_id,
                    $data['content'],
                    $data['reply_to_id'] ?? null
                );

                error_log("Send message result: " . print_r($result, true));

                if ($result['success']) {
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode($result);
                }
            } catch (Exception $e) {
                error_log("Exception sending message: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'PUT':
            // Edit message
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!isset($data['message_id']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields: message_id, content']);
                exit;
            }

            $result = $messagingManager->editMessage(
                intval($data['message_id']),
                $participant_type,
                $participant_id,
                $data['content']
            );

            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;

        case 'DELETE':
            // Delete message
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!isset($data['message_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required field: message_id']);
                exit;
            }

            $result = $messagingManager->deleteMessage(
                intval($data['message_id']),
                $participant_type,
                $participant_id
            );

            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
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
