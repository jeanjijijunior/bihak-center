<?php
/**
 * Save Interactive Exercise Data API
 * Saves user's interactive exercise progress
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

if (!isset($data['team_id'], $data['exercise_id'], $data['data_type'], $data['data_json'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$team_id = intval($data['team_id']);
$exercise_id = intval($data['exercise_id']);
$data_type = $data['data_type'];
$data_json = json_encode($data['data_json']);

$conn = getDatabaseConnection();

try {
    // Verify user is part of the team
    $team_check = $conn->prepare("
        SELECT 1 FROM incubation_team_members
        WHERE team_id = ? AND user_id = ?
    ");
    $team_check->bind_param('ii', $team_id, $user_id);
    $team_check->execute();

    if ($team_check->get_result()->num_rows === 0) {
        throw new Exception('You are not a member of this team');
    }

    // Mark previous versions as not current
    $update_prev = $conn->prepare("
        UPDATE incubation_interactive_data
        SET is_current = 0
        WHERE team_id = ? AND exercise_id = ? AND data_type = ?
    ");
    $update_prev->bind_param('iis', $team_id, $exercise_id, $data_type);
    $update_prev->execute();

    // Get next version number
    $version_query = $conn->prepare("
        SELECT MAX(version) as max_version
        FROM incubation_interactive_data
        WHERE team_id = ? AND exercise_id = ? AND data_type = ?
    ");
    $version_query->bind_param('iis', $team_id, $exercise_id, $data_type);
    $version_query->execute();
    $version_result = $version_query->get_result()->fetch_assoc();
    $version = ($version_result['max_version'] ?? 0) + 1;

    // Insert new version
    $insert = $conn->prepare("
        INSERT INTO incubation_interactive_data
        (team_id, exercise_id, data_type, data_json, version, is_current, created_by)
        VALUES (?, ?, ?, ?, ?, 1, ?)
    ");
    $insert->bind_param('iissii', $team_id, $exercise_id, $data_type, $data_json, $version, $user_id);
    $insert->execute();

    $data_id = $conn->insert_id;

    // Update or create exercise metrics
    $metrics_check = $conn->prepare("
        SELECT id FROM incubation_exercise_metrics
        WHERE team_id = ? AND exercise_id = ?
    ");
    $metrics_check->bind_param('ii', $team_id, $exercise_id);
    $metrics_check->execute();

    if ($metrics_check->get_result()->num_rows > 0) {
        // Update existing
        $update_metrics = $conn->prepare("
            UPDATE incubation_exercise_metrics
            SET revisions_count = revisions_count + 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE team_id = ? AND exercise_id = ?
        ");
        $update_metrics->bind_param('ii', $team_id, $exercise_id);
        $update_metrics->execute();
    } else {
        // Create new
        $insert_metrics = $conn->prepare("
            INSERT INTO incubation_exercise_metrics
            (team_id, exercise_id, revisions_count)
            VALUES (?, ?, 1)
        ");
        $insert_metrics->bind_param('ii', $team_id, $exercise_id);
        $insert_metrics->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Data saved successfully',
        'data_id' => $data_id,
        'version' => $version
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
?>
