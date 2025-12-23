<?php
/**
 * AI Feedback API
 * Provides AI-powered feedback on interactive exercises
 * Requires: Anthropic Claude API key
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

    // Call Claude API
    // NOTE: You need to add your Anthropic API key in a secure config file
    $ai_response = callClaudeAPI($prompt);

    // Parse AI response
    $feedback = parseAIResponse($ai_response, $exercise_template);

    // Store feedback in database
    $insert_feedback = $conn->prepare("
        INSERT INTO incubation_ai_feedback
        (team_id, exercise_id, feedback_type, feedback_text, completeness_score, strengths, improvements, ai_model)
        VALUES (?, ?, 'complete_review', ?, ?, ?, ?, 'claude-3-sonnet')
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

Format your response as JSON:
{
  \"completeness_score\": 75,
  \"feedback_text\": \"Your problem tree shows...\",
  \"strengths\": [\"Clear core problem\", \"Good cause identification\", \"Logical structure\"],
  \"improvements\": [\"Add more effects\", \"Deepen cause analysis\", \"Improve connections\"]
}
";
            break;

        case 'business_model_canvas':
            $prompt = $base_prompt . "
Current Exercise: Business Model Canvas

Team's Current Work: " . json_encode($data) . "

Analyze each of the 9 blocks and provide comprehensive feedback...
";
            break;

        default:
            $prompt = $base_prompt . "
Current Exercise: $template

Team's Current Work: " . json_encode($data) . "

Provide constructive feedback on completeness and quality...
";
    }

    return $prompt;
}

function callClaudeAPI($prompt) {
    // NOTE: This is a placeholder. You need to implement actual Claude API call
    // Requires: Anthropic API key from https://console.anthropic.com/

    // For now, return simulated response
    // In production, use: https://docs.anthropic.com/claude/reference/making-api-calls

    /*
    // Example implementation:
    $api_key = 'YOUR_ANTHROPIC_API_KEY'; // Store securely in config
    $api_url = 'https://api.anthropic.com/v1/messages';

    $data = [
        'model' => 'claude-3-sonnet-20240229',
        'max_tokens' => 1024,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
    */

    // Simulated response for testing
    return [
        'content' => [[
            'text' => json_encode([
                'completeness_score' => 75,
                'feedback_text' => "Your problem tree analysis shows good progress! You've identified a clear core problem and begun mapping causes and effects. To strengthen your analysis:\n\n• Your core problem is well-defined and specific\n• You've identified several root causes, showing depth of analysis\n• The logical structure between elements is clear\n\nAreas for improvement:\n• Consider adding more direct effects of the problem\n• Some root causes could be broken down further\n• Ensure all connections are logically sound",
                'strengths' => [
                    'Clear and specific core problem definition',
                    'Good identification of root causes',
                    'Logical structure and connections'
                ],
                'improvements' => [
                    'Add 2-3 more direct effects of the problem',
                    'Deepen analysis of root causes',
                    'Verify all cause-effect relationships'
                ]
            ])
        ]]
    ];
}

function parseAIResponse($response, $template) {
    // Parse the AI response
    // In production, extract from Claude's response format

    if (isset($response['content'][0]['text'])) {
        $text = $response['content'][0]['text'];

        // Try to parse as JSON
        $json = json_decode($text, true);

        if ($json && isset($json['completeness_score'])) {
            return [
                'completeness_score' => intval($json['completeness_score']),
                'feedback_text' => $json['feedback_text'] ?? '',
                'strengths' => $json['strengths'] ?? [],
                'improvements' => $json['improvements'] ?? []
            ];
        }
    }

    // Fallback
    return [
        'completeness_score' => 50,
        'feedback_text' => 'Unable to parse AI response. Please try again.',
        'strengths' => [],
        'improvements' => []
    ];
}
?>
