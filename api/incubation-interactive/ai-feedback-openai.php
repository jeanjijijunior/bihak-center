<?php
/**
 * AI Feedback API - OpenAI Version
 * Uses OpenAI GPT instead of Claude
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/ai-config-openai.php';

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

if (!isset($data['team_id'], $data['exercise_id'], $data['exercise_template'], $data['data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$team_id = intval($data['team_id']);
$exercise_id = intval($data['exercise_id']);
$exercise_template = $data['exercise_template'];
$exercise_data = $data['data'];

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

    // Build AI prompt based on exercise template
    $prompt = buildAIPrompt($exercise_template, $exercise_data, $conn);

    // Call OpenAI API
    $ai_response = callOpenAI($prompt);

    // Parse AI response
    $feedback = parseAIResponse($ai_response, $exercise_template);

    // Store feedback in database
    $insert_feedback = $conn->prepare("
        INSERT INTO incubation_ai_feedback
        (team_id, exercise_id, feedback_type, feedback_text, completeness_score, strengths, improvements, ai_model)
        VALUES (?, ?, 'complete_review', ?, ?, ?, ?, 'gpt-4o')
    ");

    $strengths_json = json_encode($feedback['strengths']);
    $improvements_json = json_encode($feedback['improvements']);

    $insert_feedback->bind_param(
        'iisiss',
        $team_id,
        $exercise_id,
        $feedback['feedback_text'],
        $feedback['completeness_score'],
        $strengths_json,
        $improvements_json
    );
    $insert_feedback->execute();

    $feedback_id = $conn->insert_id;

    // Update AI credits
    $update_credits = $conn->prepare("
        UPDATE incubation_teams
        SET ai_credits = ai_credits - 1,
            ai_credits_used = ai_credits_used + 1
        WHERE id = ?
    ");
    $update_credits->bind_param('i', $team_id);
    $update_credits->execute();

    // Update exercise metrics
    $update_metrics = $conn->prepare("
        UPDATE incubation_exercise_metrics
        SET completeness_score = ?,
            ai_suggestions_count = ai_suggestions_count + 1,
            last_ai_review_at = CURRENT_TIMESTAMP
        WHERE team_id = ? AND exercise_id = ?
    ");
    $update_metrics->bind_param('iii', $feedback['completeness_score'], $team_id, $exercise_id);
    $update_metrics->execute();

    // If no metrics exist, create them
    if ($conn->affected_rows === 0) {
        $insert_metrics = $conn->prepare("
            INSERT INTO incubation_exercise_metrics
            (team_id, exercise_id, completeness_score, ai_suggestions_count, last_ai_review_at)
            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");
        $insert_metrics->bind_param('iii', $team_id, $exercise_id, $feedback['completeness_score']);
        $insert_metrics->execute();
    }

    echo json_encode([
        'success' => true,
        'feedback' => [
            'id' => $feedback_id,
            'completeness_score' => $feedback['completeness_score'],
            'feedback_text' => $feedback['feedback_text'],
            'strengths' => $feedback['strengths'],
            'improvements' => $feedback['improvements']
        ],
        'credits_remaining' => $team_result['ai_credits'] - 1
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

function buildAIPrompt($template, $data, $conn) {
    // Get knowledge base content
    $kb_query = $conn->prepare("
        SELECT content FROM incubation_knowledge_base
        WHERE JSON_CONTAINS(exercise_relevance, ?) OR document_type = 'methodology'
        LIMIT 3
    ");
    $exercise_relevance = json_encode([$template]);
    $kb_query->bind_param('s', $exercise_relevance);
    $kb_query->execute();
    $kb_result = $kb_query->get_result();

    $knowledge_context = "";
    while ($kb_row = $kb_result->fetch_assoc()) {
        $knowledge_context .= $kb_row['content'] . "\n\n";
    }

    // Build prompt based on template
    $base_prompt = "You are an expert incubation coach helping young entrepreneurs in Rwanda develop social impact projects. You provide specific, constructive, and actionable feedback.

Context from Orientation Guides:
$knowledge_context

";

    switch ($template) {
        case 'problem_tree':
            $problem_count = isset($data['boxes']) ? count(array_filter($data['boxes'], function($b) { return $b['type'] === 'problem'; })) : 0;
            $cause_count = isset($data['boxes']) ? count(array_filter($data['boxes'], function($b) { return $b['type'] === 'cause'; })) : 0;
            $effect_count = isset($data['boxes']) ? count(array_filter($data['boxes'], function($b) { return $b['type'] === 'effect'; })) : 0;
            $arrow_count = isset($data['arrows']) ? count($data['arrows']) : 0;

            $prompt = $base_prompt . "
Current Exercise: Problem Tree Analysis

Team's Current Work:
- Core Problems: $problem_count
- Root Causes: $cause_count
- Effects: $effect_count
- Connections: $arrow_count

Detailed Data: " . json_encode($data) . "

Based on the Problem Tree methodology, analyze this work and provide:

1. **Completeness Score (0-100%)**: Calculate based on:
   - Core problem clarity (30%)
   - Root causes depth (3+ causes = 30%)
   - Effects identification (2+ effects = 20%)
   - Logical connections (20%)

2. **Three Specific Strengths**: What is well done?

3. **Three Areas for Improvement**: What needs more work?

4. **Actionable Suggestions**: Specific next steps

IMPORTANT: Respond ONLY with valid JSON in this exact format:
{
  \"completeness_score\": 75,
  \"feedback_text\": \"Your problem tree shows good progress. [Detailed feedback here]\",
  \"strengths\": [\"Clear core problem\", \"Good cause identification\", \"Logical structure\"],
  \"improvements\": [\"Add more effects\", \"Deepen cause analysis\", \"Improve connections\"]
}
";
            break;

        case 'business_model_canvas':
            $prompt = $base_prompt . "
Current Exercise: Business Model Canvas

Team's Current Work: " . json_encode($data) . "

Analyze each of the 9 blocks and provide comprehensive feedback.

Respond ONLY with valid JSON in this exact format:
{
  \"completeness_score\": 80,
  \"feedback_text\": \"Your business model shows...\",
  \"strengths\": [\"Strong value proposition\", \"Clear customer segments\", \"Good revenue streams\"],
  \"improvements\": [\"Define key partnerships\", \"Detail cost structure\", \"Clarify channels\"]
}
";
            break;

        default:
            $prompt = $base_prompt . "
Current Exercise: $template

Team's Current Work: " . json_encode($data) . "

Provide constructive feedback on completeness and quality.

Respond ONLY with valid JSON in this exact format:
{
  \"completeness_score\": 70,
  \"feedback_text\": \"Your work shows...\",
  \"strengths\": [\"strength 1\", \"strength 2\", \"strength 3\"],
  \"improvements\": [\"improvement 1\", \"improvement 2\", \"improvement 3\"]
}
";
    }

    return $prompt;
}

function callOpenAI($prompt) {
    $api_key = OPENAI_API_KEY;
    $api_url = OPENAI_API_URL;
    $model = OPENAI_MODEL;

    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert incubation coach. Always respond with valid JSON only, no markdown formatting.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000,
        'response_format' => ['type' => 'json_object'] // Force JSON response
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("API request failed: $error");
    }

    curl_close($ch);

    if ($http_code !== 200) {
        error_log("OpenAI API Error: HTTP $http_code - $response");
        throw new Exception("AI service returned error: HTTP $http_code");
    }

    $result = json_decode($response, true);

    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception("Invalid API response format");
    }

    return $result;
}

function parseAIResponse($response, $template) {
    try {
        // Extract the JSON from the response
        $content = $response['choices'][0]['message']['content'];

        // Parse JSON
        $json = json_decode($content, true);

        if (!$json || !isset($json['completeness_score'])) {
            throw new Exception("Invalid JSON response");
        }

        return [
            'completeness_score' => intval($json['completeness_score']),
            'feedback_text' => $json['feedback_text'] ?? '',
            'strengths' => $json['strengths'] ?? [],
            'improvements' => $json['improvements'] ?? []
        ];

    } catch (Exception $e) {
        error_log("AI Response Parse Error: " . $e->getMessage());

        // Fallback response
        return [
            'completeness_score' => 50,
            'feedback_text' => 'Your work is being reviewed. Please continue developing your ideas and try again.',
            'strengths' => ['Good start', 'Clear effort', 'On the right track'],
            'improvements' => ['Add more detail', 'Develop ideas further', 'Connect concepts']
        ];
    }
}
?>
