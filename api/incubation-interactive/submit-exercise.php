<?php
/**
 * Submit Exercise for Review API
 * Submits completed interactive exercise for admin review
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

    // Save the data first
    $insert_data = $conn->prepare("
        INSERT INTO incubation_interactive_data
        (team_id, exercise_id, data_type, data_json, version, is_current, created_by)
        VALUES (?, ?, ?, ?, (
            SELECT COALESCE(MAX(version), 0) + 1
            FROM incubation_interactive_data AS iid
            WHERE iid.team_id = ? AND iid.exercise_id = ? AND iid.data_type = ?
        ), 1, ?)
    ");
    $insert_data->bind_param('iissiisi', $team_id, $exercise_id, $data_type, $data_json, $team_id, $exercise_id, $data_type, $user_id);
    $insert_data->execute();

    $interactive_data_id = $conn->insert_id;

    // Check if submission exists
    $submission_check = $conn->prepare("
        SELECT id FROM exercise_submissions
        WHERE team_id = ? AND exercise_id = ?
    ");
    $submission_check->bind_param('ii', $team_id, $exercise_id);
    $submission_check->execute();
    $existing_submission = $submission_check->get_result()->fetch_assoc();

    if ($existing_submission) {
        // Update existing submission
        $update_submission = $conn->prepare("
            UPDATE exercise_submissions
            SET status = 'pending',
                submitted_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $update_submission->bind_param('i', $existing_submission['id']);
        $update_submission->execute();

        $submission_id = $existing_submission['id'];
    } else {
        // Create new submission
        $insert_submission = $conn->prepare("
            INSERT INTO exercise_submissions
            (team_id, exercise_id, status, submitted_at)
            VALUES (?, ?, 'pending', CURRENT_TIMESTAMP)
        ");
        $insert_submission->bind_param('ii', $team_id, $exercise_id);
        $insert_submission->execute();

        $submission_id = $conn->insert_id;
    }

    // Update exercise metrics
    $update_metrics = $conn->prepare("
        UPDATE incubation_exercise_metrics
        SET submitted_at = CURRENT_TIMESTAMP
        WHERE team_id = ? AND exercise_id = ?
    ");
    $update_metrics->bind_param('ii', $team_id, $exercise_id);
    $update_metrics->execute();

    // Log activity
    $log_activity = $conn->prepare("
        INSERT INTO activity_log (action, details, created_at)
        VALUES ('exercise_submitted', ?, CURRENT_TIMESTAMP)
    ");
    $activity_details = "Team $team_id submitted exercise $exercise_id for review";
    $log_activity->bind_param('s', $activity_details);
    $log_activity->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Exercise submitted successfully',
        'submission_id' => $submission_id,
        'interactive_data_id' => $interactive_data_id
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
