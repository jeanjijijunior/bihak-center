<?php
/**
 * AI Feedback API - Ollama Version
 * FREE self-hosted AI using Ollama
 *
 * Requires:
 * - Ollama installed and running (ollama serve)
 * - Model downloaded (ollama pull mistral)
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

if (!isset($data['team_id'], $data['exercise_id'], $data['data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$team_id = intval($data['team_id']);
$exercise_id = intval($data['exercise_id']);
$exercise_data = $data['data'];
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
        throw new Exception('Your team has run out of AI credits. Please contact your administrator.');
    }

    // Check if Ollama server is running
    if (!checkOllamaServer()) {
        throw new Exception('Ollama server is not running. Please ensure Ollama is started with "ollama serve"');
    }

    // Save current data version
    $data_json = json_encode($exercise_data);
    $version = 1;

    // Get current version number
    $version_query = $conn->prepare("
        SELECT MAX(version) as max_version
        FROM incubation_interactive_data
        WHERE team_id = ? AND exercise_id = ?
    ");
    $version_query->bind_param('ii', $team_id, $exercise_id);
    $version_query->execute();
    $version_result = $version_query->get_result()->fetch_assoc();
    if ($version_result['max_version']) {
        $version = $version_result['max_version'] + 1;
    }

    // Mark previous versions as not current
    $update_prev = $conn->prepare("
        UPDATE incubation_interactive_data
        SET is_current = 0
        WHERE team_id = ? AND exercise_id = ?
    ");
    $update_prev->bind_param('ii', $team_id, $exercise_id);
    $update_prev->execute();

    // Insert new version
    $insert_data = $conn->prepare("
        INSERT INTO incubation_interactive_data
        (team_id, exercise_id, data_type, data_json, version, is_current, created_by)
        VALUES (?, ?, 'interactive', ?, ?, 1, ?)
    ");
    $insert_data->bind_param('iisii', $team_id, $exercise_id, $data_json, $version, $user_id);
    $insert_data->execute();
    $interactive_data_id = $insert_data->insert_id;

    // Build AI prompt
    $prompt = buildAIPrompt($exercise_template, $exercise_data, $conn);

    // Get AI response from Ollama
    $ai_response = callOllama($prompt);

    // Parse response
    $response_text = $ai_response['response'] ?? '';

    // Try to extract JSON from response
    $feedback_data = extractJSONFromResponse($response_text);

    if (!$feedback_data) {
        throw new Exception('AI returned invalid response. Please try again.');
    }

    $completeness_score = $feedback_data['completeness_score'] ?? 0;
    $feedback_text = $feedback_data['feedback_text'] ?? 'No feedback provided';
    $strengths = json_encode($feedback_data['strengths'] ?? []);
    $improvements = json_encode($feedback_data['improvements'] ?? []);

    // Store AI feedback
    $insert_feedback = $conn->prepare("
        INSERT INTO incubation_ai_feedback
        (team_id, exercise_id, interactive_data_id, feedback_type, feedback_text,
         completeness_score, strengths, improvements, ai_model)
        VALUES (?, ?, ?, 'automated', ?, ?, ?, ?, ?)
    ");
    $ai_model = 'ollama-' . OLLAMA_MODEL;
    $insert_feedback->bind_param('iiisissss',
        $team_id, $exercise_id, $interactive_data_id,
        $feedback_text, $completeness_score, $strengths, $improvements, $ai_model
    );
    $insert_feedback->execute();

    // Update exercise metrics
    $update_metrics = $conn->prepare("
        INSERT INTO incubation_exercise_metrics
        (team_id, exercise_id, completeness_score, quality_score, ai_suggestions_count, last_activity)
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            completeness_score = VALUES(completeness_score),
            quality_score = VALUES(quality_score),
            ai_suggestions_count = ai_suggestions_count + 1,
            last_activity = CURRENT_TIMESTAMP
    ");
    $quality_score = min(100, $completeness_score + 10);
    $suggestions_count = count($feedback_data['improvements'] ?? []);
    $update_metrics->bind_param('iiiii', $team_id, $exercise_id, $completeness_score, $quality_score, $suggestions_count);
    $update_metrics->execute();

    // Deduct AI credit
    $update_credits = $conn->prepare("
        UPDATE incubation_teams
        SET ai_credits = ai_credits - 1,
            ai_credits_used = ai_credits_used + 1
        WHERE id = ?
    ");
    $update_credits->bind_param('i', $team_id);
    $update_credits->execute();

    echo json_encode([
        'success' => true,
        'feedback' => [
            'completeness_score' => $completeness_score,
            'feedback_text' => $feedback_text,
            'strengths' => $feedback_data['strengths'] ?? [],
            'improvements' => $feedback_data['improvements'] ?? []
        ],
        'credits_remaining' => $team_result['ai_credits'] - 1,
        'ai_model' => $ai_model
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

    return $http_code === 200 || $http_code === 404; // 404 is OK, means server is running
}

/**
 * Build AI prompt based on exercise template
 */
function buildAIPrompt($template, $data, $conn) {
    // Get knowledge base content
    $kb_query = $conn->prepare("
        SELECT content FROM incubation_knowledge_base
        WHERE document_type IN ('methodology', 'guide')
        ORDER BY RAND()
        LIMIT 2
    ");
    $kb_query->execute();
    $kb_result = $kb_query->get_result();

    $knowledge_context = "";
    while ($kb_row = $kb_result->fetch_assoc()) {
        $knowledge_context .= $kb_row['content'] . "\n\n";
    }

    $data_summary = json_encode($data, JSON_PRETTY_PRINT);

    if ($template === 'problem_tree') {
        $box_count = count($data['boxes'] ?? []);
        $arrow_count = count($data['arrows'] ?? []);

        $prompt = "Vous êtes un coach expert en incubation aidant de jeunes entrepreneurs au Rwanda.

CONTEXTE DE L'EXERCICE: Arbre à Problèmes
Un arbre à problèmes aide à identifier les causes profondes d'un problème et ses effets.

$knowledge_context

TRAVAIL DE L'ÉQUIPE:
Nombre de boîtes créées: $box_count
Nombre de connexions: $arrow_count
Détails:
$data_summary

VOTRE TÂCHE:
Évaluez ce travail et fournissez un retour constructif en français.

Répondez UNIQUEMENT avec un objet JSON valide (pas de texte avant ou après):
{
  \"completeness_score\": [0-100, score de complétude],
  \"feedback_text\": \"Votre analyse du problème...\",
  \"strengths\": [
    \"Point fort spécifique 1\",
    \"Point fort spécifique 2\",
    \"Point fort spécifique 3\"
  ],
  \"improvements\": [
    \"Amélioration concrète 1\",
    \"Amélioration concrète 2\",
    \"Amélioration concrète 3\"
  ]
}";
    } else {
        // Generic template
        $prompt = "You are an expert incubation coach helping young entrepreneurs in Rwanda.

EXERCISE CONTEXT: $template

KNOWLEDGE BASE:
$knowledge_context

TEAM'S WORK:
$data_summary

YOUR TASK:
Evaluate this work and provide constructive feedback.

Respond ONLY with a valid JSON object (no text before or after):
{
  \"completeness_score\": [0-100],
  \"feedback_text\": \"Your analysis shows...\",
  \"strengths\": [
    \"Specific strength 1\",
    \"Specific strength 2\",
    \"Specific strength 3\"
  ],
  \"improvements\": [
    \"Concrete improvement 1\",
    \"Concrete improvement 2\",
    \"Concrete improvement 3\"
  ]
}";
    }

    return $prompt;
}

/**
 * Call Ollama API
 */
function callOllama($prompt) {
    $data = [
        'model' => OLLAMA_MODEL,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => OLLAMA_TEMPERATURE,
            'num_predict' => OLLAMA_MAX_TOKENS
        ]
    ];

    $ch = curl_init(OLLAMA_API_URL);
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
        error_log("Ollama API Error: $curl_error");
        throw new Exception("Failed to connect to Ollama server: $curl_error");
    }

    if ($http_code !== 200) {
        error_log("Ollama API Error: HTTP $http_code - $response");
        throw new Exception("Ollama server returned error: HTTP $http_code");
    }

    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Ollama JSON Error: " . json_last_error_msg());
        throw new Exception("Invalid response from Ollama server");
    }

    return $result;
}

/**
 * Extract JSON from AI response
 * Handles cases where AI includes extra text before/after JSON
 */
function extractJSONFromResponse($text) {
    // Try direct JSON decode first
    $decoded = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Look for JSON object in the text
    if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
        $decoded = json_decode($matches[0], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }

    // Fallback: create structured response from text
    return [
        'completeness_score' => 50,
        'feedback_text' => $text,
        'strengths' => ['Work submitted for review'],
        'improvements' => ['Continue developing your analysis']
    ];
}
?>
