<?php
/**
 * Mentorship Activities API
 * GET/POST /api/mentorship/activities.php
 *
 * Manage activity log within mentorship relationships
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['mentor_id']) && !isset($_SESSION['sponsor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // List activities for a relationship
            if (!isset($_GET['relationship_id'])) {
                throw new Exception('relationship_id is required');
            }

            $relationship_id = intval($_GET['relationship_id']);

            // Verify user is part of this relationship
            $check_access = $conn->prepare("
                SELECT 1 FROM mentorship_relationships
                WHERE id = ?
                AND (mentor_id = ? OR mentee_id = ?)
            ");
            $user_id = $_SESSION['user_id'] ?? ($_SESSION['mentor_id'] ?? $_SESSION['sponsor_id']);
            $check_access->bind_param('iii', $relationship_id, $user_id, $user_id);
            $check_access->execute();

            if ($check_access->get_result()->num_rows === 0) {
                throw new Exception('Access denied to this relationship');
            }

            // Get activities
            $stmt = $conn->prepare("
                SELECT ma.*, mg.title as goal_title
                FROM mentorship_activities ma
                LEFT JOIN mentorship_goals mg ON mg.id = ma.goal_id
                WHERE ma.relationship_id = ?
                ORDER BY ma.activity_date DESC, ma.created_at DESC
            ");
            $stmt->bind_param('i', $relationship_id);
            $stmt->execute();
            $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $activities,
                'count' => count($activities)
            ]);
            break;

        case 'POST':
            // Create new activity
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['relationship_id']) || !isset($data['title']) || !isset($data['activity_type'])) {
                throw new Exception('relationship_id, title, and activity_type are required');
            }

            $relationship_id = intval($data['relationship_id']);
            $title = trim($data['title']);
            $description = trim($data['description'] ?? '');
            $activity_type = $data['activity_type']; // meeting, note, milestone, resource, other
            $goal_id = isset($data['goal_id']) ? intval($data['goal_id']) : null;
            $activity_date = $data['activity_date'] ?? date('Y-m-d H:i:s');

            // Verify access
            $check_access = $conn->prepare("
                SELECT mentor_id, mentee_id FROM mentorship_relationships
                WHERE id = ? AND status = 'active'
            ");
            $check_access->bind_param('i', $relationship_id);
            $check_access->execute();
            $rel = $check_access->get_result()->fetch_assoc();

            if (!$rel) {
                throw new Exception('Relationship not found or not active');
            }

            // Determine created_by
            $user_id = $_SESSION['user_id'] ?? null;
            $mentor_id = $_SESSION['mentor_id'] ?? $_SESSION['sponsor_id'] ?? null;

            if ($mentor_id && $rel['mentor_id'] == $mentor_id) {
                $created_by = 'mentor';
            } elseif ($user_id && $rel['mentee_id'] == $user_id) {
                $created_by = 'mentee';
            } else {
                throw new Exception('Access denied to this relationship');
            }

            // Create activity
            $stmt = $conn->prepare("
                INSERT INTO mentorship_activities
                (relationship_id, goal_id, activity_type, title, description, created_by, activity_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('iisssss', $relationship_id, $goal_id, $activity_type, $title, $description, $created_by, $activity_date);

            if ($stmt->execute()) {
                $activity_id = $conn->insert_id;

                // Get created activity
                $get_activity = $conn->prepare("
                    SELECT ma.*, mg.title as goal_title
                    FROM mentorship_activities ma
                    LEFT JOIN mentorship_goals mg ON mg.id = ma.goal_id
                    WHERE ma.id = ?
                ");
                $get_activity->bind_param('i', $activity_id);
                $get_activity->execute();
                $activity = $get_activity->get_result()->fetch_assoc();

                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'data' => $activity,
                    'message' => 'Activity logged successfully'
                ]);
            } else {
                throw new Exception('Failed to log activity');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

closeDatabaseConnection($conn);
?>
