<?php
/**
 * MentorshipManager Class
 *
 * Handles all mentorship relationship operations including:
 * - Matching algorithm
 * - Relationship requests and responses
 * - Goals and activities management
 * - Notifications
 */

class MentorshipManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Calculate match score between mentor and mentee
     * Score breakdown: 40pts sectors + 40pts skills + 20pts languages = 100pts max
     *
     * @param int $mentor_id FK to sponsors.id
     * @param int $mentee_id FK to users.id
     * @return float Score 0-100
     */
    public function calculateMatchScore($mentor_id, $mentee_id) {
        $score = 0;

        // Get mentor preferences
        $mentor_prefs = $this->getMentorPreferences($mentor_id);
        if (!$mentor_prefs) {
            return 0; // No preferences set
        }

        // Get mentee needs
        $mentee_needs = $this->getMenteeNeeds($mentee_id);
        if (!$mentee_needs) {
            return 0; // No needs set
        }

        // Parse JSON fields
        $mentor_sectors = json_decode($mentor_prefs['preferred_sectors'] ?? '[]', true) ?: [];
        $mentor_skills = json_decode($mentor_prefs['preferred_skills'] ?? '[]', true) ?: [];
        $mentor_languages = json_decode($mentor_prefs['preferred_languages'] ?? '[]', true) ?: [];

        $mentee_sectors = json_decode($mentee_needs['needed_sectors'] ?? '[]', true) ?: [];
        $mentee_skills = json_decode($mentee_needs['needed_skills'] ?? '[]', true) ?: [];
        $mentee_languages = json_decode($mentee_needs['preferred_languages'] ?? '[]', true) ?: [];

        // Calculate sector match (40 points max)
        $sector_matches = count(array_intersect($mentor_sectors, $mentee_sectors));
        $score += min($sector_matches * 20, 40);

        // Calculate skills match (40 points max)
        $skills_matches = count(array_intersect($mentor_skills, $mentee_skills));
        $score += min($skills_matches * 20, 40);

        // Calculate language match (20 points max)
        $language_matches = count(array_intersect($mentor_languages, $mentee_languages));
        $score += min($language_matches * 10, 20);

        return min($score, 100);
    }

    /**
     * Get suggested mentors for a mentee
     *
     * @param int $mentee_id FK to users.id
     * @param int $limit Number of results
     * @return array Array of mentor suggestions with scores
     */
    public function getSuggestedMentors($mentee_id, $limit = 10) {
        // Get all potential mentors (sponsors with role_type = mentor/sponsor/partner, status = approved)
        $query = "
            SELECT s.*,
                   mp.max_mentees,
                   (SELECT COUNT(*) FROM mentorship_relationships mr
                    WHERE mr.mentor_id = s.id AND mr.status = 'active') as active_mentees
            FROM sponsors s
            LEFT JOIN mentor_preferences mp ON mp.mentor_id = s.id
            WHERE s.role_type IN ('mentor', 'sponsor', 'partner')
            AND s.status = 'approved'
            AND s.is_active = 1
        ";

        $result = $this->conn->query($query);
        $mentors = [];

        while ($mentor = $result->fetch_assoc()) {
            // Check if mentor has capacity
            $max_mentees = $mentor['max_mentees'] ?? 3;
            if ($mentor['active_mentees'] >= $max_mentees) {
                continue; // Skip mentors at capacity
            }

            // Calculate match score
            $score = $this->calculateMatchScore($mentor['id'], $mentee_id);

            // Only include mentors with score > 0
            if ($score > 0) {
                $mentor['match_score'] = $score;
                $mentors[] = $mentor;
            }
        }

        // Sort by score descending
        usort($mentors, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        // Return top N results
        return array_slice($mentors, 0, $limit);
    }

    /**
     * Get suggested mentees for a mentor
     *
     * @param int $mentor_id FK to sponsors.id
     * @param int $limit Number of results
     * @return array Array of mentee suggestions with scores
     */
    public function getSuggestedMentees($mentor_id, $limit = 10) {
        // Get all users who don't have active mentors
        $query = "
            SELECT u.*
            FROM users u
            WHERE u.is_active = 1
            AND NOT EXISTS (
                SELECT 1 FROM mentorship_relationships mr
                WHERE mr.mentee_id = u.id AND mr.status = 'active'
            )
        ";

        $result = $this->conn->query($query);
        $mentees = [];

        while ($mentee = $result->fetch_assoc()) {
            // Calculate match score
            $score = $this->calculateMatchScore($mentor_id, $mentee['id']);

            // Only include mentees with score > 0
            if ($score > 0) {
                $mentee['match_score'] = $score;
                $mentees[] = $mentee;
            }
        }

        // Sort by score descending
        usort($mentees, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        // Return top N results
        return array_slice($mentees, 0, $limit);
    }

    /**
     * Request mentorship relationship
     *
     * @param int $mentor_id FK to sponsors.id
     * @param int $mentee_id FK to users.id
     * @param string $requested_by 'mentor' or 'mentee'
     * @return array Result with success status and relationship_id
     */
    public function requestMentorship($mentor_id, $mentee_id, $requested_by) {
        // Validation
        if (!in_array($requested_by, ['mentor', 'mentee'])) {
            return ['success' => false, 'message' => 'Invalid requester type'];
        }

        // Check if mentee already has active mentor
        $check_mentee = $this->conn->prepare("
            SELECT id FROM mentorship_relationships
            WHERE mentee_id = ? AND status = 'active'
        ");
        $check_mentee->bind_param('i', $mentee_id);
        $check_mentee->execute();
        if ($check_mentee->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Mentee already has an active mentor'];
        }

        // Check if mentor has capacity
        $mentor_prefs = $this->getMentorPreferences($mentor_id);
        $max_mentees = $mentor_prefs['max_mentees'] ?? 3;

        $check_mentor = $this->conn->prepare("
            SELECT COUNT(*) as count FROM mentorship_relationships
            WHERE mentor_id = ? AND status = 'active'
        ");
        $check_mentor->bind_param('i', $mentor_id);
        $check_mentor->execute();
        $active_count = $check_mentor->get_result()->fetch_assoc()['count'];

        if ($active_count >= $max_mentees) {
            return ['success' => false, 'message' => 'Mentor has reached maximum capacity'];
        }

        // Check if relationship already exists (pending/active)
        $check_existing = $this->conn->prepare("
            SELECT id, status FROM mentorship_relationships
            WHERE mentor_id = ? AND mentee_id = ? AND status IN ('pending', 'active')
        ");
        $check_existing->bind_param('ii', $mentor_id, $mentee_id);
        $check_existing->execute();
        $existing = $check_existing->get_result()->fetch_assoc();

        if ($existing) {
            return ['success' => false, 'message' => 'Relationship request already exists'];
        }

        // Calculate match score
        $match_score = $this->calculateMatchScore($mentor_id, $mentee_id);

        // Create relationship request
        $stmt = $this->conn->prepare("
            INSERT INTO mentorship_relationships (mentor_id, mentee_id, requested_by, match_score, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param('iisd', $mentor_id, $mentee_id, $requested_by, $match_score);

        if ($stmt->execute()) {
            $relationship_id = $this->conn->insert_id;

            // Create notification for the recipient
            $this->createMentorshipNotification($relationship_id, 'mentorship_request');

            return [
                'success' => true,
                'relationship_id' => $relationship_id,
                'message' => 'Mentorship request sent successfully'
            ];
        }

        return ['success' => false, 'message' => 'Failed to create relationship request'];
    }

    /**
     * Respond to mentorship request (accept or reject)
     *
     * @param int $relationship_id
     * @param string $action 'accept' or 'reject'
     * @param int $responder_id User ID responding (for validation)
     * @param string $responder_type 'mentor' or 'mentee'
     * @return array Result with success status
     */
    public function respondToRequest($relationship_id, $action, $responder_id, $responder_type) {
        if (!in_array($action, ['accept', 'reject'])) {
            return ['success' => false, 'message' => 'Invalid action'];
        }

        // Get relationship
        $stmt = $this->conn->prepare("
            SELECT * FROM mentorship_relationships WHERE id = ?
        ");
        $stmt->bind_param('i', $relationship_id);
        $stmt->execute();
        $relationship = $stmt->get_result()->fetch_assoc();

        if (!$relationship) {
            return ['success' => false, 'message' => 'Relationship not found'];
        }

        if ($relationship['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Relationship is not pending'];
        }

        // Validate responder
        $is_valid_responder = false;
        if ($responder_type === 'mentor' && $relationship['mentor_id'] == $responder_id && $relationship['requested_by'] === 'mentee') {
            $is_valid_responder = true;
        } elseif ($responder_type === 'mentee' && $relationship['mentee_id'] == $responder_id && $relationship['requested_by'] === 'mentor') {
            $is_valid_responder = true;
        }

        if (!$is_valid_responder) {
            return ['success' => false, 'message' => 'You are not authorized to respond to this request'];
        }

        // Update relationship status
        $new_status = ($action === 'accept') ? 'active' : 'rejected';
        $accepted_at = ($action === 'accept') ? 'NOW()' : 'NULL';

        $update = $this->conn->prepare("
            UPDATE mentorship_relationships
            SET status = ?, accepted_at = $accepted_at
            WHERE id = ?
        ");
        $update->bind_param('si', $new_status, $relationship_id);

        if ($update->execute()) {
            // Create notification
            if ($action === 'accept') {
                $this->createMentorshipNotification($relationship_id, 'mentorship_accepted');

                // Create direct conversation for mentor-mentee
                $this->createMentorMenteeConversation($relationship['mentor_id'], $relationship['mentee_id']);
            }

            return [
                'success' => true,
                'message' => $action === 'accept' ? 'Mentorship request accepted' : 'Mentorship request rejected'
            ];
        }

        return ['success' => false, 'message' => 'Failed to update relationship'];
    }

    /**
     * End mentorship relationship
     *
     * @param int $relationship_id
     * @param int $ender_id User ID ending relationship
     * @param string $ender_type 'mentor' or 'mentee'
     * @param string $reason Mandatory reason for ending
     * @return array Result with success status
     */
    public function endRelationship($relationship_id, $ender_id, $ender_type, $reason) {
        if (empty(trim($reason))) {
            return ['success' => false, 'message' => 'Reason is required when ending a relationship'];
        }

        // Get relationship
        $stmt = $this->conn->prepare("
            SELECT * FROM mentorship_relationships WHERE id = ?
        ");
        $stmt->bind_param('i', $relationship_id);
        $stmt->execute();
        $relationship = $stmt->get_result()->fetch_assoc();

        if (!$relationship) {
            return ['success' => false, 'message' => 'Relationship not found'];
        }

        if ($relationship['status'] !== 'active') {
            return ['success' => false, 'message' => 'Only active relationships can be ended'];
        }

        // Validate ender
        $is_valid_ender = false;
        if ($ender_type === 'mentor' && $relationship['mentor_id'] == $ender_id) {
            $is_valid_ender = true;
        } elseif ($ender_type === 'mentee' && $relationship['mentee_id'] == $ender_id) {
            $is_valid_ender = true;
        }

        if (!$is_valid_ender) {
            return ['success' => false, 'message' => 'You are not part of this relationship'];
        }

        // Update relationship
        $update = $this->conn->prepare("
            UPDATE mentorship_relationships
            SET status = 'ended', ended_at = NOW(), ended_by = ?, end_reason = ?
            WHERE id = ?
        ");
        $update->bind_param('ssi', $ender_type, $reason, $relationship_id);

        if ($update->execute()) {
            // Create notification
            $this->createMentorshipNotification($relationship_id, 'mentorship_ended');

            return [
                'success' => true,
                'message' => 'Relationship ended successfully'
            ];
        }

        return ['success' => false, 'message' => 'Failed to end relationship'];
    }

    /**
     * Get mentor preferences
     */
    private function getMentorPreferences($mentor_id) {
        $stmt = $this->conn->prepare("SELECT * FROM mentor_preferences WHERE mentor_id = ?");
        $stmt->bind_param('i', $mentor_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get mentee needs
     */
    private function getMenteeNeeds($mentee_id) {
        $stmt = $this->conn->prepare("SELECT * FROM mentee_needs WHERE mentee_id = ?");
        $stmt->bind_param('i', $mentee_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create mentorship notification
     */
    private function createMentorshipNotification($relationship_id, $type) {
        // Get relationship details
        $stmt = $this->conn->prepare("
            SELECT mr.*, s.full_name as mentor_name, u.full_name as mentee_name
            FROM mentorship_relationships mr
            JOIN sponsors s ON s.id = mr.mentor_id
            JOIN users u ON u.id = mr.mentee_id
            WHERE mr.id = ?
        ");
        $stmt->bind_param('i', $relationship_id);
        $stmt->execute();
        $rel = $stmt->get_result()->fetch_assoc();

        if (!$rel) return;

        $notifications = [];

        switch ($type) {
            case 'mentorship_request':
                // Notify the recipient of the request
                if ($rel['requested_by'] === 'mentor') {
                    // Notify mentee
                    $notifications[] = [
                        'user_id' => $rel['mentee_id'],
                        'recipient_type' => 'user',
                        'title' => 'New Mentorship Offer',
                        'message' => "{$rel['mentor_name']} wants to be your mentor",
                        'link_url' => '/mentorship/requests.php'
                    ];
                } else {
                    // Notify mentor
                    $notifications[] = [
                        'mentor_id' => $rel['mentor_id'],
                        'recipient_type' => 'mentor',
                        'title' => 'New Mentorship Request',
                        'message' => "{$rel['mentee_name']} has requested your mentorship",
                        'link_url' => '/mentorship/requests.php'
                    ];
                }
                break;

            case 'mentorship_accepted':
                // Notify the requester that their request was accepted
                if ($rel['requested_by'] === 'mentor') {
                    // Notify mentor
                    $notifications[] = [
                        'mentor_id' => $rel['mentor_id'],
                        'recipient_type' => 'mentor',
                        'title' => 'Mentorship Request Accepted',
                        'message' => "{$rel['mentee_name']} accepted your mentorship offer",
                        'link_url' => "/mentorship/workspace.php?id={$relationship_id}"
                    ];
                } else {
                    // Notify mentee
                    $notifications[] = [
                        'user_id' => $rel['mentee_id'],
                        'recipient_type' => 'user',
                        'title' => 'Mentorship Request Accepted',
                        'message' => "{$rel['mentor_name']} accepted your mentorship request",
                        'link_url' => "/mentorship/workspace.php?id={$relationship_id}"
                    ];
                }
                break;

            case 'mentorship_ended':
                // Notify both parties
                $notifications[] = [
                    'mentor_id' => $rel['mentor_id'],
                    'recipient_type' => 'mentor',
                    'title' => 'Mentorship Ended',
                    'message' => "Your mentorship with {$rel['mentee_name']} has ended",
                    'link_url' => '/mentorship/dashboard.php'
                ];
                $notifications[] = [
                    'user_id' => $rel['mentee_id'],
                    'recipient_type' => 'user',
                    'title' => 'Mentorship Ended',
                    'message' => "Your mentorship with {$rel['mentor_name']} has ended",
                    'link_url' => '/mentorship/dashboard.php'
                ];
                break;
        }

        // Insert notifications
        foreach ($notifications as $notif) {
            $insert = $this->conn->prepare("
                INSERT INTO notifications
                (user_id, mentor_id, recipient_type, notification_type, title, message, link_url, related_relationship_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $user_id = $notif['user_id'] ?? null;
            $mentor_id = $notif['mentor_id'] ?? null;
            $insert->bind_param(
                'iisssssi',
                $user_id,
                $mentor_id,
                $notif['recipient_type'],
                $type,
                $notif['title'],
                $notif['message'],
                $notif['link_url'],
                $relationship_id
            );
            $insert->execute();
        }
    }

    /**
     * Create direct conversation for mentor-mentee
     */
    private function createMentorMenteeConversation($mentor_id, $mentee_id) {
        // Check if conversation already exists
        $check = $this->conn->prepare("
            SELECT c.id FROM conversations c
            INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
            INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
            WHERE c.conversation_type = 'direct'
            AND cp1.mentor_id = ? AND cp1.participant_type = 'mentor'
            AND cp2.user_id = ? AND cp2.participant_type = 'user'
        ");
        $check->bind_param('ii', $mentor_id, $mentee_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            return; // Conversation already exists
        }

        // Create conversation
        $create = $this->conn->prepare("
            INSERT INTO conversations (conversation_type, created_by)
            VALUES ('direct', ?)
        ");
        $create->bind_param('i', $mentor_id);
        $create->execute();
        $conversation_id = $this->conn->insert_id;

        // Add mentor as participant
        $add_mentor = $this->conn->prepare("
            INSERT INTO conversation_participants (conversation_id, mentor_id, participant_type)
            VALUES (?, ?, 'mentor')
        ");
        $add_mentor->bind_param('ii', $conversation_id, $mentor_id);
        $add_mentor->execute();

        // Add mentee as participant
        $add_mentee = $this->conn->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id, participant_type)
            VALUES (?, ?, 'user')
        ");
        $add_mentee->bind_param('ii', $conversation_id, $mentee_id);
        $add_mentee->execute();
    }

    /**
     * Get active mentorship relationships for a user
     *
     * @param int $user_id
     * @param string $user_type 'mentor' or 'mentee'
     * @return array List of relationships
     */
    public function getActiveRelationships($user_id, $user_type) {
        if ($user_type === 'mentor') {
            $stmt = $this->conn->prepare("
                SELECT mr.*, u.full_name as mentee_name, u.email as mentee_email
                FROM mentorship_relationships mr
                JOIN users u ON u.id = mr.mentee_id
                WHERE mr.mentor_id = ? AND mr.status = 'active'
                ORDER BY mr.accepted_at DESC
            ");
        } else {
            $stmt = $this->conn->prepare("
                SELECT mr.*, s.full_name as mentor_name, s.email as mentor_email
                FROM mentorship_relationships mr
                JOIN sponsors s ON s.id = mr.mentor_id
                WHERE mr.mentee_id = ? AND mr.status = 'active'
                ORDER BY mr.accepted_at DESC
            ");
        }

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get pending mentorship requests
     *
     * @param int $user_id
     * @param string $user_type 'mentor' or 'mentee'
     * @return array List of pending requests
     */
    public function getPendingRequests($user_id, $user_type) {
        if ($user_type === 'mentor') {
            $stmt = $this->conn->prepare("
                SELECT mr.*, u.full_name as mentee_name, u.email as mentee_email
                FROM mentorship_relationships mr
                JOIN users u ON u.id = mr.mentee_id
                WHERE mr.mentor_id = ? AND mr.status = 'pending'
                ORDER BY mr.requested_at DESC
            ");
        } else {
            $stmt = $this->conn->prepare("
                SELECT mr.*, s.full_name as mentor_name, s.email as mentor_email
                FROM mentorship_relationships mr
                JOIN sponsors s ON s.id = mr.mentor_id
                WHERE mr.mentee_id = ? AND mr.status = 'pending'
                ORDER BY mr.requested_at DESC
            ");
        }

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
