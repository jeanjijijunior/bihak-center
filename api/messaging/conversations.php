<?php
/**
 * API Endpoint: Conversations
 *
 * GET    - List user's conversations
 * POST   - Create new conversation
 */

// Suppress any PHP errors from being output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any stray output
ob_start();

session_start();

// Clear any output that happened before this point
ob_clean();

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
            // List conversations
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            $conversations = $messagingManager->getUserConversations($participant_type, $participant_id, $limit, $offset);

            echo json_encode([
                'success' => true,
                'data' => $conversations,
                'count' => count($conversations)
            ]);
            break;

        case 'POST':
            // Create new conversation
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            // Log for debugging
            error_log("POST /conversations - Input: " . print_r($data, true));
            error_log("Current user: $participant_type ID: $participant_id");

            if (!isset($data['type']) || !isset($data['participants'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields: type, participants']);
                exit;
            }

            // Add current user to participants if not already included
            $current_user_included = false;
            foreach ($data['participants'] as $p) {
                if ($p['type'] === $participant_type && $p['id'] == $participant_id) {
                    $current_user_included = true;
                    break;
                }
            }

            if (!$current_user_included) {
                $data['participants'][] = ['type' => $participant_type, 'id' => $participant_id];
            }

            error_log("Creating conversation with participants: " . print_r($data['participants'], true));

            try {
                $result = $messagingManager->createConversation(
                    $data['type'],
                    $data['participants'],
                    $data['title'] ?? null,
                    $data['team_id'] ?? null,
                    $data['exercise_id'] ?? null
                );

                error_log("Conversation creation result: " . print_r($result, true));

                if ($result['success']) {
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode($result);
                }
            } catch (Exception $e) {
                error_log("Exception in createConversation: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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

// Flush output buffer
ob_end_flush();
