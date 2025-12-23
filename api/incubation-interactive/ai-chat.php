<?php
/**
 * AI Chat API
 * Handles conversational AI assistant for exercises
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';

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

    // Store user message
    $insert_user_msg = $conn->prepare("
        INSERT INTO incubation_ai_chat
        (team_id, exercise_id, user_id, message_type, message_text)
        VALUES (?, ?, ?, 'user', ?)
    ");
    $insert_user_msg->bind_param('iiis', $team_id, $exercise_id, $user_id, $message);
    $insert_user_msg->execute();

    // Get conversation history (last 5 messages)
    $history_query = $conn->prepare("
        SELECT message_type, message_text
        FROM incubation_ai_chat
        WHERE team_id = ? AND exercise_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $history_query->bind_param('ii', $team_id, $exercise_id);
    $history_query->execute();
    $history = $history_query->get_result();

    $conversation_history = [];
    while ($row = $history->fetch_assoc()) {
        array_unshift($conversation_history, $row);
    }

    // Build context-aware prompt
    $prompt = buildChatPrompt($message, $exercise_template, $conversation_history, $conn);

    // Get AI response
    $ai_response = callClaudeAPIForChat($prompt);

    // Parse response
    $response_text = $ai_response['content'][0]['text'] ?? 'I apologize, I encountered an error. Please try again.';

    // Store AI message
    $insert_ai_msg = $conn->prepare("
        INSERT INTO incubation_ai_chat
        (team_id, exercise_id, user_id, message_type, message_text)
        VALUES (?, ?, ?, 'ai', ?)
    ");
    $insert_ai_msg->bind_param('iiis', $team_id, $exercise_id, $user_id, $response_text);
    $insert_ai_msg->execute();

    // Update AI credits (chat uses 1 credit per 5 messages)
    if (count($conversation_history) % 5 === 0) {
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
        'credits_remaining' => $team_result['ai_credits']
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

function buildChatPrompt($message, $template, $history, $conn) {
    // Get knowledge base content
    $kb_query = $conn->prepare("
        SELECT content FROM incubation_knowledge_base
        WHERE document_type = 'methodology'
        LIMIT 2
    ");
    $kb_query->execute();
    $kb_result = $kb_query->get_result();

    $knowledge_context = "";
    while ($kb_row = $kb_result->fetch_assoc()) {
        $knowledge_context .= $kb_row['content'] . "\n\n";
    }

    $prompt = "You are a helpful AI assistant for an incubation program in Rwanda. You help young entrepreneurs develop their social impact projects.

Knowledge Base:
$knowledge_context

Current Exercise: $template

Conversation History:
";

    foreach ($history as $msg) {
        $prompt .= ($msg['message_type'] === 'user' ? 'User: ' : 'Assistant: ') . $msg['message_text'] . "\n";
    }

    $prompt .= "\nUser: $message\nAssistant:";

    return $prompt;
}

function callClaudeAPIForChat($prompt) {
    // Placeholder - same as ai-feedback.php
    // In production, implement actual Claude API call

    // Simulated response
    $responses = [
        "problem" => "A problem tree helps you identify the root causes of an issue. Start by defining the core problem clearly, then work backwards to find what causes it, and forwards to see its effects.",
        "cause" => "Root causes are the fundamental reasons why a problem exists. They're usually systemic issues, not symptoms. Try asking 'why?' multiple times to get to the real causes.",
        "effect" => "Effects are the consequences of the problem. They can be direct (immediate results) or indirect (longer-term impacts). Think about who is affected and how.",
        "canvas" => "The Business Model Canvas has 9 blocks that describe how your organization creates, delivers, and captures value. Each block is interconnected.",
        "default" => "That's a great question! Based on the design thinking methodology, I'd suggest starting by clearly defining your challenge. What specific problem are you trying to solve?"
    ];

    // Simple keyword matching for simulation
    $message_lower = strtolower($prompt);
    $response_text = $responses['default'];

    if (strpos($message_lower, 'problem') !== false) {
        $response_text = $responses['problem'];
    } elseif (strpos($message_lower, 'cause') !== false || strpos($message_lower, 'why') !== false) {
        $response_text = $responses['cause'];
    } elseif (strpos($message_lower, 'effect') !== false) {
        $response_text = $responses['effect'];
    } elseif (strpos($message_lower, 'canvas') !== false || strpos($message_lower, 'business model') !== false) {
        $response_text = $responses['canvas'];
    }

    return [
        'content' => [[
            'text' => $response_text
        ]]
    ];
}
?>
