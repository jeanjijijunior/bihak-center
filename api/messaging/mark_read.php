<?php
/**
 * API Endpoint: Mark Messages as Read
 *
 * POST - Mark all messages in a conversation as read
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
session_start();
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
    if ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['conversation_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing conversation_id']);
            exit;
        }

        $conversation_id = intval($data['conversation_id']);

        // Mark messages as read
        $result = $messagingManager->markMessagesAsRead($conversation_id, $participant_type, $participant_id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

closeDatabaseConnection($conn);
ob_end_flush();
