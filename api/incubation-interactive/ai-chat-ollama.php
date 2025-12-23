<?php
/**
 * AI Chat API - Ollama Version
 * FREE conversational AI using Ollama
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/ai-config-ollama.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get input data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['team_id'], $data['exercise_id'], $data['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$team_id = intval($data['team_id']);
$exercise_id = intval($data['exercise_id']);
$message = trim($data['message']);
$exercise_template = $data['exercise_template'] ?? '';

$conn = getDatabaseConnection();

try {
    // Verify user is part of the team
    $team_check = $conn->prepare("
        SELECT ai_credits FROM incubation_teams t
        JOIN incubation_team_members tm ON t.id = tm.team_id
        WHERE t.id = ? AND tm.user_id = ?
    ");
    $team_check->bind_param('ii', $team_id, $user_id);
    $team_check->execute();
    $team_result = $team_check->get_result()->fetch_assoc();

    if (!$team_result) {
        throw new Exception('You are not a member of this team');
    }

    if ($team_result['ai_credits'] <= 0) {
        throw new Exception('Your team has run out of AI credits');
    }

    // Check if Ollama server is running
    if (!checkOllamaServer()) {
        throw new Exception('Ollama server is not running. Please ensure Ollama is started.');
    }

    // Store user message
    $insert_user_msg = $conn->prepare("
        INSERT INTO incubation_ai_chat
        (team_id, exercise_id, user_id, message_type, message_text)
        VALUES (?, ?, ?, 'user', ?)
    ");
    $insert_user_msg->bind_param('iiis', $team_id, $exercise_id, $user_id, $message);
    $insert_user_msg->execute();

    // Get conversation history (last 10 messages)
    $history_query = $conn->prepare("
        SELECT message_type, message_text
        FROM incubation_ai_chat
        WHERE team_id = ? AND exercise_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $history_query->bind_param('ii', $team_id, $exercise_id);
    $history_query->execute();
    $history = $history_query->get_result();

    $conversation_history = [];
    while ($row = $history->fetch_assoc()) {
        array_unshift($conversation_history, $row);
    }

    // Build chat messages for Ollama
    $messages = buildChatMessages($message, $exercise_template, $conversation_history, $conn);

    // Get AI response using Ollama chat endpoint
    $ai_response = callOllamaChat($messages);

    // Extract response text
    $response_text = $ai_response['message']['content'] ?? 'Je suis désolé, j\'ai rencontré une erreur. Veuillez réessayer.';

    // Store AI message
    $insert_ai_msg = $conn->prepare("
        INSERT INTO incubation_ai_chat
        (team_id, exercise_id, user_id, message_type, message_text)
        VALUES (?, ?, ?, 'ai', ?)
    ");
    $insert_ai_msg->bind_param('iiis', $team_id, $exercise_id, $user_id, $response_text);
    $insert_ai_msg->execute();

    // Update AI credits (chat uses 1 credit per 5 messages)
    $message_count = count($conversation_history) + 2; // +2 for current exchange
    if ($message_count % 5 === 0) {
        $update_credits = $conn->prepare("
            UPDATE incubation_teams
            SET ai_credits = ai_credits - 1,
                ai_credits_used = ai_credits_used + 1
            WHERE id = ?
        ");
        $update_credits->bind_param('i', $team_id);
        $update_credits->execute();
    }

    echo json_encode([
        'success' => true,
        'response' => $response_text,
        'credits_remaining' => $team_result['ai_credits'],
        'ai_model' => 'ollama-' . OLLAMA_MODEL
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    closeDatabaseConnection($conn);
}

/**
 * Check if Ollama server is running
 */
function checkOllamaServer() {
    $ch = curl_init('http://' . OLLAMA_HOST . ':' . OLLAMA_PORT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_NOBODY, true);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_code === 200 || $http_code === 404;
}

/**
 * Build chat messages array for Ollama
 */
function buildChatMessages($message, $template, $history, $conn) {
    // Get knowledge base content
    $kb_query = $conn->prepare("
        SELECT content FROM incubation_knowledge_base
        WHERE document_type = 'methodology'
        ORDER BY RAND()
        LIMIT 1
    ");
    $kb_query->execute();
    $kb_result = $kb_query->get_result();

    $knowledge_context = "";
    if ($kb_row = $kb_result->fetch_assoc()) {
        $knowledge_context = $kb_row['content'];
    }

    // System message
    $system_message = "Vous êtes un assistant IA serviable pour un programme d'incubation au Rwanda. Vous aidez de jeunes entrepreneurs à développer leurs projets à impact social.

Base de connaissances:
$knowledge_context

Exercice actuel: $template

Répondez toujours en français, de manière claire, encourageante et constructive. Donnez des conseils pratiques et actionnables.";

    $messages = [
        [
            'role' => 'system',
            'content' => $system_message
        ]
    ];

    // Add conversation history
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['message_type'] === 'user' ? 'user' : 'assistant',
            'content' => $msg['message_text']
        ];
    }

    // Add current message
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];

    return $messages;
}

/**
 * Call Ollama Chat API
 */
function callOllamaChat($messages) {
    $data = [
        'model' => OLLAMA_MODEL,
        'messages' => $messages,
        'stream' => false,
        'options' => [
            'temperature' => OLLAMA_TEMPERATURE,
            'num_predict' => OLLAMA_MAX_TOKENS
        ]
    ];

    $ch = curl_init(OLLAMA_CHAT_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, OLLAMA_TIMEOUT);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("Ollama Chat API Error: $curl_error");
        throw new Exception("Failed to connect to Ollama: $curl_error");
    }

    if ($http_code !== 200) {
        error_log("Ollama Chat API Error: HTTP $http_code - $response");
        throw new Exception("Ollama error: HTTP $http_code");
    }

    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Ollama JSON Error: " . json_last_error_msg());
        throw new Exception("Invalid response from Ollama");
    }

    return $result;
}
?>
