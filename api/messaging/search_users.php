<?php
/**
 * API Endpoint: Search Users for Messaging
 *
 * GET - Search users, admins, mentors by name
 * Returns users that the current user can message
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to output
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['sponsor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conn = getDatabaseConnection();

// Determine current user type and ID
$current_participant_type = null;
$current_participant_id = null;

if (isset($_SESSION['user_id'])) {
    $current_participant_type = 'user';
    $current_participant_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $current_participant_type = 'admin';
    $current_participant_id = $_SESSION['admin_id'];
} elseif (isset($_SESSION['sponsor_id'])) {
    $current_participant_type = 'mentor';
    $current_participant_id = $_SESSION['sponsor_id'];
}

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

try {
    $results = [];

    // Debug: Log what we're doing
    error_log("Search API called by: $current_participant_type (ID: $current_participant_id)");

    // For admins: can message anyone (users, mentors, other admins)
    if ($current_participant_type === 'admin') {
        // Search users
        if (empty($search_query)) {
            // Get recent active users
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'user' as type
                FROM users
                WHERE is_active = 1
                ORDER BY last_login DESC
                LIMIT ?
            ");

            if (!$stmt) {
                error_log("SQL Error (users query): " . $conn->error);
                throw new Exception("Database error");
            }

            $stmt->bind_param('i', $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'user' as type
                FROM users
                WHERE is_active = 1
                AND (full_name LIKE ? OR email LIKE ?)
                LIMIT ?
            ");

            if (!$stmt) {
                error_log("SQL Error (users search query): " . $conn->error);
                throw new Exception("Database error");
            }

            $stmt->bind_param('ssi', $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $user_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $user_results);

        // Search mentors/sponsors
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'mentor' as type, organization
                FROM sponsors
                WHERE status = 'approved' AND is_active = 1
                ORDER BY full_name
                LIMIT ?
            ");

            if (!$stmt) {
                error_log("SQL Error (mentors query): " . $conn->error);
                throw new Exception("Database error");
            }

            $stmt->bind_param('i', $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'mentor' as type, organization
                FROM sponsors
                WHERE status = 'approved' AND is_active = 1
                AND (full_name LIKE ? OR email LIKE ? OR organization LIKE ?)
                LIMIT ?
            ");

            if (!$stmt) {
                error_log("SQL Error (mentors search query): " . $conn->error);
                throw new Exception("Database error");
            }

            $stmt->bind_param('sssi', $search_pattern, $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $mentor_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $mentor_results);

        // Search other admins
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'admin' as type
                FROM admins
                WHERE is_active = 1 AND id != ?
                ORDER BY full_name
                LIMIT ?
            ");

            if (!$stmt) {
                error_log("SQL Error (admins query): " . $conn->error);
                throw new Exception("Database error");
            }

            $stmt->bind_param('ii', $current_participant_id, $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'admin' as type
                FROM admins
                WHERE is_active = 1 AND id != ?
                AND (full_name LIKE ? OR email LIKE ?)
                LIMIT ?
            ");
            $stmt->bind_param('issi', $current_participant_id, $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $admin_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $admin_results);
    }

    // For mentors: can message everyone (all users, other mentors, admins)
    elseif ($current_participant_type === 'mentor') {
        // Search all users
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'user' as type
                FROM users
                WHERE is_active = 1
                ORDER BY full_name
                LIMIT ?
            ");
            $stmt->bind_param('i', $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'user' as type
                FROM users
                WHERE is_active = 1
                AND (full_name LIKE ? OR email LIKE ?)
                LIMIT ?
            ");
            $stmt->bind_param('ssi', $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $user_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $user_results);

        // Search other mentors/sponsors (excluding self)
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'mentor' as type, organization
                FROM sponsors
                WHERE status = 'approved' AND is_active = 1 AND id != ?
                ORDER BY full_name
                LIMIT ?
            ");
            $stmt->bind_param('ii', $current_participant_id, $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'mentor' as type, organization
                FROM sponsors
                WHERE status = 'approved' AND is_active = 1 AND id != ?
                AND (full_name LIKE ? OR email LIKE ? OR organization LIKE ?)
                LIMIT ?
            ");
            $stmt->bind_param('isssi', $current_participant_id, $search_pattern, $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $mentor_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $mentor_results);

        // Search admins
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'admin' as type
                FROM admins
                WHERE is_active = 1
                ORDER BY full_name
                LIMIT ?
            ");
            $stmt->bind_param('i', $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'admin' as type
                FROM admins
                WHERE is_active = 1
                AND (full_name LIKE ? OR email LIKE ?)
                LIMIT ?
            ");
            $stmt->bind_param('ssi', $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $admin_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $admin_results);
    }

    // For regular users: can message all users, their mentors, and admins
    elseif ($current_participant_type === 'user') {
        // Search all other users (excluding self)
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'user' as type
                FROM users
                WHERE is_active = 1 AND id != ?
                ORDER BY full_name
                LIMIT ?
            ");
            $stmt->bind_param('ii', $current_participant_id, $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'user' as type
                FROM users
                WHERE is_active = 1 AND id != ?
                AND (full_name LIKE ? OR email LIKE ?)
                LIMIT ?
            ");
            $stmt->bind_param('issi', $current_participant_id, $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $user_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $user_results);

        // Search their mentors (sponsors with active mentorship relationship)
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT DISTINCT s.id, s.full_name as name, s.email, 'mentor' as type, s.organization
                FROM sponsors s
                INNER JOIN mentorship_relationships mr ON s.id = mr.mentor_id
                WHERE mr.mentee_id = ? AND mr.status = 'active' AND s.is_active = 1
                ORDER BY s.full_name
                LIMIT ?
            ");
            $stmt->bind_param('ii', $current_participant_id, $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT DISTINCT s.id, s.full_name as name, s.email, 'mentor' as type, s.organization
                FROM sponsors s
                INNER JOIN mentorship_relationships mr ON s.id = mr.mentor_id
                WHERE mr.mentee_id = ? AND mr.status = 'active' AND s.is_active = 1
                AND (s.full_name LIKE ? OR s.email LIKE ? OR s.organization LIKE ?)
                ORDER BY s.full_name
                LIMIT ?
            ");
            $stmt->bind_param('isssi', $current_participant_id, $search_pattern, $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $mentor_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $mentor_results);

        // Search admins (always available to users)
        if (empty($search_query)) {
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'admin' as type
                FROM admins
                WHERE is_active = 1
                ORDER BY full_name
                LIMIT ?
            ");
            $stmt->bind_param('i', $limit);
        } else {
            $search_pattern = "%{$search_query}%";
            $stmt = $conn->prepare("
                SELECT id, full_name as name, email, 'admin' as type
                FROM admins
                WHERE is_active = 1
                AND (full_name LIKE ? OR email LIKE ?)
                LIMIT ?
            ");
            $stmt->bind_param('ssi', $search_pattern, $search_pattern, $limit);
        }
        $stmt->execute();
        $admin_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $results = array_merge($results, $admin_results);
    }

    // Add labels for display and filter out empty names
    $filtered_results = [];
    foreach ($results as $result) {
        // Skip if name is empty or null
        if (empty($result['name']) || trim($result['name']) === '') {
            continue;
        }

        if ($result['type'] === 'admin') {
            $result['label'] = 'Admin';
            $result['badge_color'] = '#dc2626';
        } elseif ($result['type'] === 'mentor') {
            $result['label'] = 'Mentor';
            $result['badge_color'] = '#667eea';
        } else {
            $result['label'] = 'User';
            $result['badge_color'] = '#10b981';
        }

        $filtered_results[] = $result;
    }

    $results = $filtered_results;

    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results),
        'search_query' => $search_query
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

closeDatabaseConnection($conn);
