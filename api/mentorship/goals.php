<?php
/**
 * Mentorship Goals API
 * GET/POST/PUT/DELETE /api/mentorship/goals.php
 *
 * Manage goals within mentorship relationships
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
            // List goals for a relationship
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

            // Get goals
            $stmt = $conn->prepare("
                SELECT *
                FROM mentorship_goals
                WHERE relationship_id = ?
                ORDER BY
                    CASE status
                        WHEN 'in_progress' THEN 1
                        WHEN 'not_started' THEN 2
                        WHEN 'completed' THEN 3
                        WHEN 'cancelled' THEN 4
                    END,
                    CASE priority
                        WHEN 'high' THEN 1
                        WHEN 'medium' THEN 2
                        WHEN 'low' THEN 3
                    END,
                    target_date ASC
            ");
            $stmt->bind_param('i', $relationship_id);
            $stmt->execute();
            $goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $goals,
                'count' => count($goals)
            ]);
            break;

        case 'POST':
            // Create new goal
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['relationship_id']) || !isset($data['title'])) {
                throw new Exception('relationship_id and title are required');
            }

            $relationship_id = intval($data['relationship_id']);
            $title = trim($data['title']);
            $description = trim($data['description'] ?? '');
            $target_date = $data['target_date'] ?? null;
            $priority = $data['priority'] ?? 'medium';

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

            // Create goal
            $stmt = $conn->prepare("
                INSERT INTO mentorship_goals
                (relationship_id, title, description, target_date, priority, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('isssss', $relationship_id, $title, $description, $target_date, $priority, $created_by);

            if ($stmt->execute()) {
                $goal_id = $conn->insert_id;

                // Get created goal
                $get_goal = $conn->prepare("SELECT * FROM mentorship_goals WHERE id = ?");
                $get_goal->bind_param('i', $goal_id);
                $get_goal->execute();
                $goal = $get_goal->get_result()->fetch_assoc();

                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'data' => $goal,
                    'message' => 'Goal created successfully'
                ]);
            } else {
                throw new Exception('Failed to create goal');
            }
            break;

        case 'PUT':
            // Update goal
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id'])) {
                throw new Exception('Goal id is required');
            }

            $goal_id = intval($data['id']);

            // Get goal and verify access
            $check = $conn->prepare("
                SELECT mg.*, mr.mentor_id, mr.mentee_id
                FROM mentorship_goals mg
                JOIN mentorship_relationships mr ON mr.id = mg.relationship_id
                WHERE mg.id = ?
            ");
            $check->bind_param('i', $goal_id);
            $check->execute();
            $goal = $check->get_result()->fetch_assoc();

            if (!$goal) {
                throw new Exception('Goal not found');
            }

            $user_id = $_SESSION['user_id'] ?? null;
            $mentor_id = $_SESSION['mentor_id'] ?? $_SESSION['sponsor_id'] ?? null;

            $has_access = false;
            if ($mentor_id && $goal['mentor_id'] == $mentor_id) {
                $has_access = true;
            } elseif ($user_id && $goal['mentee_id'] == $user_id) {
                $has_access = true;
            }

            if (!$has_access) {
                throw new Exception('Access denied');
            }

            // Build update query dynamically
            $updates = [];
            $params = [];
            $types = '';

            if (isset($data['title'])) {
                $updates[] = "title = ?";
                $params[] = trim($data['title']);
                $types .= 's';
            }
            if (isset($data['description'])) {
                $updates[] = "description = ?";
                $params[] = trim($data['description']);
                $types .= 's';
            }
            if (isset($data['status'])) {
                $updates[] = "status = ?";
                $params[] = $data['status'];
                $types .= 's';

                // If completed, set completed_at
                if ($data['status'] === 'completed') {
                    $updates[] = "completed_at = NOW()";
                }
            }
            if (isset($data['priority'])) {
                $updates[] = "priority = ?";
                $params[] = $data['priority'];
                $types .= 's';
            }
            if (isset($data['target_date'])) {
                $updates[] = "target_date = ?";
                $params[] = $data['target_date'];
                $types .= 's';
            }

            if (empty($updates)) {
                throw new Exception('No fields to update');
            }

            $params[] = $goal_id;
            $types .= 'i';

            $sql = "UPDATE mentorship_goals SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                // Get updated goal
                $get_goal = $conn->prepare("SELECT * FROM mentorship_goals WHERE id = ?");
                $get_goal->bind_param('i', $goal_id);
                $get_goal->execute();
                $updated_goal = $get_goal->get_result()->fetch_assoc();

                echo json_encode([
                    'success' => true,
                    'data' => $updated_goal,
                    'message' => 'Goal updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update goal');
            }
            break;

        case 'DELETE':
            // Delete goal
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id'])) {
                throw new Exception('Goal id is required');
            }

            $goal_id = intval($data['id']);

            // Get goal and verify access
            $check = $conn->prepare("
                SELECT mg.*, mr.mentor_id, mr.mentee_id
                FROM mentorship_goals mg
                JOIN mentorship_relationships mr ON mr.id = mg.relationship_id
                WHERE mg.id = ?
            ");
            $check->bind_param('i', $goal_id);
            $check->execute();
            $goal = $check->get_result()->fetch_assoc();

            if (!$goal) {
                throw new Exception('Goal not found');
            }

            $user_id = $_SESSION['user_id'] ?? null;
            $mentor_id = $_SESSION['mentor_id'] ?? $_SESSION['sponsor_id'] ?? null;

            $has_access = false;
            if ($mentor_id && $goal['mentor_id'] == $mentor_id) {
                $has_access = true;
            } elseif ($user_id && $goal['mentee_id'] == $user_id) {
                $has_access = true;
            }

            if (!$has_access) {
                throw new Exception('Access denied');
            }

            // Delete goal
            $stmt = $conn->prepare("DELETE FROM mentorship_goals WHERE id = ?");
            $stmt->bind_param('i', $goal_id);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Goal deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete goal');
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
