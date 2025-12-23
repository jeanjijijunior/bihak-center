<?php
/**
 * Mentorship Suggestions API
 * GET /api/mentorship/suggestions.php
 *
 * Get suggested mentors or mentees based on matching algorithm
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MentorshipManager.php';

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$conn = getDatabaseConnection();
$mentorshipManager = new MentorshipManager($conn);

// Get parameters
$as = $_GET['as'] ?? ''; // 'mentor' or 'mentee'
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

try {
    if ($as === 'mentor') {
        // User is looking for mentors
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not authenticated');
        }

        $mentee_id = $_SESSION['user_id'];
        $suggestions = $mentorshipManager->getSuggestedMentors($mentee_id, $limit);

        // Format response
        $formatted = array_map(function($mentor) {
            return [
                'id' => $mentor['id'],
                'name' => $mentor['full_name'],
                'email' => $mentor['email'],
                'organization' => $mentor['organization'],
                'expertise_domain' => $mentor['expertise_domain'],
                'role_type' => $mentor['role_type'],
                'match_score' => round($mentor['match_score'], 2),
                'active_mentees' => $mentor['active_mentees'],
                'max_mentees' => $mentor['max_mentees'] ?? 3,
                'availability' => $mentor['availability']
            ];
        }, $suggestions);

        echo json_encode([
            'success' => true,
            'data' => $formatted,
            'count' => count($formatted)
        ]);

    } elseif ($as === 'mentee') {
        // Mentor is looking for mentees
        // Check if user is a mentor (sponsor)
        if (!isset($_SESSION['mentor_id']) && !isset($_SESSION['sponsor_id'])) {
            throw new Exception('Not authorized as mentor');
        }

        $mentor_id = $_SESSION['mentor_id'] ?? $_SESSION['sponsor_id'];
        $suggestions = $mentorshipManager->getSuggestedMentees($mentor_id, $limit);

        // Format response
        $formatted = array_map(function($mentee) {
            return [
                'id' => $mentee['id'],
                'name' => $mentee['full_name'],
                'email' => $mentee['email'],
                'profile_picture' => $mentee['profile_picture'] ?? null,
                'match_score' => round($mentee['match_score'], 2),
                'bio' => $mentee['bio'] ?? '',
                'location' => $mentee['location'] ?? ''
            ];
        }, $suggestions);

        echo json_encode([
            'success' => true,
            'data' => $formatted,
            'count' => count($formatted)
        ]);

    } else {
        throw new Exception('Invalid parameter: as must be "mentor" or "mentee"');
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
