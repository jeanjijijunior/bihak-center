<?php
/**
 * API Endpoint: Search Messages
 *
 * GET - Search messages across all conversations
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

try {
    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing search query parameter: q']);
        exit;
    }

    $search_term = trim($_GET['q']);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

    $results = $messagingManager->searchMessages($participant_type, $participant_id, $search_term, $limit);

    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results),
        'query' => $search_term
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

closeDatabaseConnection($conn);
