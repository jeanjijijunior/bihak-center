<?php
/**
 * MessagingManager Class
 *
 * Handles all messaging functionality for the Bihak Center platform:
 * - Direct messages between users, mentors, and admins
 * - Team chats for incubation teams
 * - Broadcast messages
 * - Exercise feedback threads
 * - Read receipts and typing indicators
 * - Online status tracking
 * - Message search and management
 *
 * @author Claude
 * @version 1.0
 * @date 2025-11-20
 */

class MessagingManager {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // ==================== CONVERSATION MANAGEMENT ====================

    /**
     * Create a new conversation
     *
     * @param string $type Conversation type: 'direct', 'team', 'broadcast', 'exercise'
     * @param array $participants Array of participant data: [['type' => 'user', 'id' => 1], ...]
     * @param string|null $title Optional title for group/team conversations
     * @param int|null $team_id Optional team ID for team conversations
     * @param int|null $exercise_id Optional exercise ID for exercise feedback
     * @return array ['success' => bool, 'conversation_id' => int|null, 'message' => string]
     */
    public function createConversation($type, $participants, $title = null, $team_id = null, $exercise_id = null) {
        try {
            // Validate conversation type
            $valid_types = ['direct', 'team', 'broadcast', 'exercise'];
            if (!in_array($type, $valid_types)) {
                return ['success' => false, 'message' => 'Invalid conversation type'];
            }

            // Validate participants
            if (empty($participants) || count($participants) < 2) {
                return ['success' => false, 'message' => 'At least 2 participants required'];
            }

            // Check if direct conversation already exists
            if ($type === 'direct' && count($participants) === 2) {
                $existing = $this->findExistingDirectConversation($participants);
                if ($existing) {
                    return [
                        'success' => true,
                        'conversation_id' => $existing['id'],
                        'message' => 'Existing conversation found',
                        'existing' => true
                    ];
                }
            }

            // Determine created_by from first participant
            $created_by = $participants[0]['id'];

            // Create conversation
            $stmt = $this->conn->prepare("
                INSERT INTO conversations (conversation_type, name, team_id, exercise_id, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            if (!$stmt) {
                error_log("Failed to prepare INSERT: " . $this->conn->error);
                return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
            }

            $stmt->bind_param('ssiii', $type, $title, $team_id, $exercise_id, $created_by);
            $stmt->execute();
            $conversation_id = $this->conn->insert_id;

            // Add participants
            foreach ($participants as $participant) {
                $this->addParticipant($conversation_id, $participant['type'], $participant['id']);
            }

            return [
                'success' => true,
                'conversation_id' => $conversation_id,
                'message' => 'Conversation created successfully'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating conversation: ' . $e->getMessage()];
        }
    }

    /**
     * Find existing direct conversation between two participants
     *
     * @param array $participants Array of 2 participants
     * @return array|null Conversation data or null
     */
    private function findExistingDirectConversation($participants) {
        if (count($participants) !== 2) return null;

        $p1 = $participants[0];
        $p2 = $participants[1];

        $stmt = $this->conn->prepare("
            SELECT c.*
            FROM conversations c
            WHERE c.conversation_type = 'direct'
            AND c.id IN (
                SELECT cp1.conversation_id
                FROM conversation_participants cp1
                WHERE cp1.participant_type = ?
                AND (
                    (cp1.participant_type = 'user' AND cp1.user_id = ?) OR
                    (cp1.participant_type = 'admin' AND cp1.admin_id = ?) OR
                    (cp1.participant_type = 'mentor' AND cp1.mentor_id = ?)
                )
            )
            AND c.id IN (
                SELECT cp2.conversation_id
                FROM conversation_participants cp2
                WHERE cp2.participant_type = ?
                AND (
                    (cp2.participant_type = 'user' AND cp2.user_id = ?) OR
                    (cp2.participant_type = 'admin' AND cp2.admin_id = ?) OR
                    (cp2.participant_type = 'mentor' AND cp2.mentor_id = ?)
                )
            )
            LIMIT 1
        ");

        $p1_user_id = ($p1['type'] === 'user') ? $p1['id'] : null;
        $p1_admin_id = ($p1['type'] === 'admin') ? $p1['id'] : null;
        $p1_mentor_id = ($p1['type'] === 'mentor') ? $p1['id'] : null;

        $p2_user_id = ($p2['type'] === 'user') ? $p2['id'] : null;
        $p2_admin_id = ($p2['type'] === 'admin') ? $p2['id'] : null;
        $p2_mentor_id = ($p2['type'] === 'mentor') ? $p2['id'] : null;

        $stmt->bind_param(
            'ssiissii',
            $p1['type'], $p1_user_id, $p1_admin_id, $p1_mentor_id,
            $p2['type'], $p2_user_id, $p2_admin_id, $p2_mentor_id
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result ?: null;
    }

    /**
     * Add participant to conversation
     *
     * @param int $conversation_id
     * @param string $participant_type 'user', 'admin', or 'mentor'
     * @param int $participant_id
     * @return bool Success
     */
    private function addParticipant($conversation_id, $participant_type, $participant_id) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            INSERT INTO conversation_participants
            (conversation_id, participant_type, user_id, admin_id, mentor_id, joined_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('isiii', $conversation_id, $participant_type, $user_id, $admin_id, $mentor_id);
        return $stmt->execute();
    }

    /**
     * Get all conversations for a user
     *
     * @param string $participant_type 'user', 'admin', or 'mentor'
     * @param int $participant_id
     * @param int $limit
     * @param int $offset
     * @return array List of conversations with latest message and unread count
     */
    public function getUserConversations($participant_type, $participant_id, $limit = 50, $offset = 0) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            SELECT
                c.*,
                0 as unread_count,
                (SELECT m2.message_text
                 FROM messages m2
                 WHERE m2.conversation_id = c.id
                 ORDER BY m2.created_at DESC
                 LIMIT 1
                ) as last_message,
                (SELECT m3.created_at
                 FROM messages m3
                 WHERE m3.conversation_id = c.id
                 ORDER BY m3.created_at DESC
                 LIMIT 1
                ) as last_message_at
            FROM conversations c
            INNER JOIN conversation_participants cp ON cp.conversation_id = c.id
            WHERE cp.participant_type = ?
            AND (
                (cp.participant_type = 'user' AND cp.user_id = ?) OR
                (cp.participant_type = 'admin' AND cp.admin_id = ?) OR
                (cp.participant_type = 'mentor' AND cp.mentor_id = ?)
            )
            ORDER BY last_message_at DESC, c.created_at DESC
            LIMIT ? OFFSET ?
        ");

        if (!$stmt) {
            error_log("MessagingManager SQL Error: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param(
            'siiiii',
            $participant_type, $user_id, $admin_id, $mentor_id,
            $limit, $offset
        );
        $stmt->execute();
        $conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get participants and unread counts for each conversation
        foreach ($conversations as &$conversation) {
            $conversation['participants'] = $this->getConversationParticipants($conversation['id']);

            // Calculate unread count for this conversation
            $conversation['unread_count'] = $this->getUnreadCountForConversation(
                $conversation['id'],
                $participant_type,
                $participant_id
            );

            // For direct conversations, get the other participant's name
            if ($conversation['conversation_type'] === 'direct') {
                $other_participant = null;
                foreach ($conversation['participants'] as $p) {
                    if (!($p['participant_type'] === $participant_type && $p['id'] == $participant_id)) {
                        $other_participant = $p;
                        break;
                    }
                }
                if ($other_participant) {
                    $conversation['display_name'] = $other_participant['name'];
                    $conversation['display_email'] = $other_participant['email'] ?? null;
                }
            }
        }

        return $conversations;
    }

    /**
     * Get participants of a conversation
     *
     * @param int $conversation_id
     * @return array List of participants with names
     */
    public function getConversationParticipants($conversation_id) {
        $stmt = $this->conn->prepare("
            SELECT
                cp.*,
                CASE
                    WHEN cp.participant_type = 'user' THEN u.full_name
                    WHEN cp.participant_type = 'admin' THEN a.full_name
                    WHEN cp.participant_type = 'mentor' THEN s.full_name
                END as name,
                CASE
                    WHEN cp.participant_type = 'user' THEN u.email
                    WHEN cp.participant_type = 'admin' THEN a.email
                    WHEN cp.participant_type = 'mentor' THEN s.email
                END as email,
                CASE
                    WHEN cp.participant_type = 'user' THEN cp.user_id
                    WHEN cp.participant_type = 'admin' THEN cp.admin_id
                    WHEN cp.participant_type = 'mentor' THEN cp.mentor_id
                END as id
            FROM conversation_participants cp
            LEFT JOIN users u ON u.id = cp.user_id
            LEFT JOIN admins a ON a.id = cp.admin_id
            LEFT JOIN sponsors s ON s.id = cp.mentor_id
            WHERE cp.conversation_id = ?
        ");

        if (!$stmt) {
            error_log("getConversationParticipants SQL Error: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $conversation_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Check if user is participant in conversation
     *
     * @param int $conversation_id
     * @param string $participant_type
     * @param int $participant_id
     * @return bool
     */
    public function isParticipant($conversation_id, $participant_type, $participant_id) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM conversation_participants
            WHERE conversation_id = ?
            AND participant_type = ?
            AND user_id <=> ?
            AND admin_id <=> ?
            AND mentor_id <=> ?
        ");
        $stmt->bind_param('isiii', $conversation_id, $participant_type, $user_id, $admin_id, $mentor_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }

    /**
     * Get unread message count for a specific conversation
     *
     * @param int $conversation_id
     * @param string $participant_type 'user', 'admin', or 'mentor'
     * @param int $participant_id
     * @return int Number of unread messages
     */
    public function getUnreadCountForConversation($conversation_id, $participant_type, $participant_id) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        // Simple approach: Count messages that don't have read receipts
        // Uses NULL-safe comparison operators (<=>)
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as unread_count
            FROM messages m
            WHERE m.conversation_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM message_read_receipts mrr
                WHERE mrr.message_id = m.id
                AND mrr.reader_type = ?
                AND mrr.user_id <=> ?
                AND mrr.admin_id <=> ?
                AND mrr.mentor_id <=> ?
            )
            AND NOT (
                (m.sender_type = 'user' AND m.sender_id <=> ?) OR
                (m.sender_type = 'admin' AND m.sender_admin_id <=> ?) OR
                (m.sender_type = 'mentor' AND m.sender_mentor_id <=> ?)
            )
        ");

        if (!$stmt) {
            error_log("getUnreadCountForConversation SQL Error: " . $this->conn->error);
            return 0;
        }

        // 8 parameters: conversation_id, participant_type, user_id, admin_id, mentor_id, user_id, admin_id, mentor_id
        $stmt->bind_param(
            'isiiiiii',
            $conversation_id,
            $participant_type,
            $user_id, $admin_id, $mentor_id,
            $user_id, $admin_id, $mentor_id
        );

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return intval($result['unread_count'] ?? 0);
    }

    // ==================== MESSAGE MANAGEMENT ====================

    /**
     * Send a message in a conversation
     *
     * @param int $conversation_id
     * @param string $sender_type 'user', 'admin', or 'mentor'
     * @param int $sender_id
     * @param string $content Message content
     * @param int|null $reply_to_id Optional message ID being replied to
     * @return array ['success' => bool, 'message_id' => int|null, 'message' => string]
     */
    public function sendMessage($conversation_id, $sender_type, $sender_id, $content, $reply_to_id = null) {
        try {
            // Validate participant
            if (!$this->isParticipant($conversation_id, $sender_type, $sender_id)) {
                return ['success' => false, 'message' => 'Not authorized to send messages in this conversation'];
            }

            // Validate content
            $content = trim($content);
            if (empty($content)) {
                return ['success' => false, 'message' => 'Message content cannot be empty'];
            }

            $user_id = ($sender_type === 'user') ? $sender_id : null;
            $admin_id = ($sender_type === 'admin') ? $sender_id : null;
            $mentor_id = ($sender_type === 'mentor') ? $sender_id : null;

            $stmt = $this->conn->prepare("
                INSERT INTO messages
                (conversation_id, sender_type, sender_id, sender_admin_id, sender_mentor_id, message_text, parent_message_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            if (!$stmt) {
                error_log("sendMessage SQL Error: " . $this->conn->error);
                return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
            }

            $stmt->bind_param('isiiisi', $conversation_id, $sender_type, $user_id, $admin_id, $mentor_id, $content, $reply_to_id);
            $stmt->execute();
            $message_id = $this->conn->insert_id;

            // Update conversation's last_activity_at
            $this->updateConversationActivity($conversation_id);

            // Create notification for other participants
            $this->notifyParticipants($conversation_id, $sender_type, $sender_id, $message_id);

            return [
                'success' => true,
                'message_id' => $message_id,
                'message' => 'Message sent successfully'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error sending message: ' . $e->getMessage()];
        }
    }

    /**
     * Get messages in a conversation
     *
     * @param int $conversation_id
     * @param string $participant_type Current user's type
     * @param int $participant_id Current user's ID
     * @param int $limit
     * @param int $offset
     * @return array|null List of messages or null if not authorized
     */
    public function getMessages($conversation_id, $participant_type, $participant_id, $limit = 50, $offset = 0) {
        // Check authorization
        if (!$this->isParticipant($conversation_id, $participant_type, $participant_id)) {
            return null;
        }

        $stmt = $this->conn->prepare("
            SELECT
                m.*,
                m.message_text as content,
                CASE
                    WHEN m.sender_type = 'user' THEN u.full_name
                    WHEN m.sender_type = 'admin' THEN a.full_name
                    WHEN m.sender_type = 'mentor' THEN s.full_name
                END as sender_name,
                CASE
                    WHEN m.sender_type = 'user' THEN u.email
                    WHEN m.sender_type = 'admin' THEN a.email
                    WHEN m.sender_type = 'mentor' THEN s.email
                END as sender_email,
                (SELECT COUNT(*)
                 FROM message_read_receipts mrr
                 WHERE mrr.message_id = m.id
                ) as read_count
            FROM messages m
            LEFT JOIN users u ON u.id = m.sender_id AND m.sender_type = 'user'
            LEFT JOIN admins a ON a.id = m.sender_admin_id AND m.sender_type = 'admin'
            LEFT JOIN sponsors s ON s.id = m.sender_mentor_id AND m.sender_type = 'mentor'
            WHERE m.conversation_id = ?
            AND m.deleted_at IS NULL
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ");

        if (!$stmt) {
            error_log("getMessages SQL Error: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param('iii', $conversation_id, $limit, $offset);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Mark messages as read
        $this->markMessagesAsRead($conversation_id, $participant_type, $participant_id);

        return array_reverse($messages); // Return oldest first
    }

    /**
     * Edit a message
     *
     * @param int $message_id
     * @param string $sender_type
     * @param int $sender_id
     * @param string $new_content
     * @return array ['success' => bool, 'message' => string]
     */
    public function editMessage($message_id, $sender_type, $sender_id, $new_content) {
        try {
            // Get message to verify ownership
            $message = $this->getMessage($message_id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            // Verify ownership
            $sender_field = ($sender_type === 'user') ? 'user_id' :
                           (($sender_type === 'admin') ? 'admin_id' : 'mentor_id');

            if ($message['sender_type'] !== $sender_type || $message[$sender_field] != $sender_id) {
                return ['success' => false, 'message' => 'Not authorized to edit this message'];
            }

            // Validate content
            $new_content = trim($new_content);
            if (empty($new_content)) {
                return ['success' => false, 'message' => 'Message content cannot be empty'];
            }

            $stmt = $this->conn->prepare("
                UPDATE messages
                SET message_text = ?, edited_at = NOW()
                WHERE id = ?
            ");

            if (!$stmt) {
                error_log("editMessage SQL Error: " . $this->conn->error);
                return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
            }

            $stmt->bind_param('si', $new_content, $message_id);
            $stmt->execute();

            return ['success' => true, 'message' => 'Message updated successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error editing message: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a message (soft delete)
     *
     * @param int $message_id
     * @param string $sender_type
     * @param int $sender_id
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteMessage($message_id, $sender_type, $sender_id) {
        try {
            // Get message to verify ownership
            $message = $this->getMessage($message_id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            // Verify ownership
            $sender_field = ($sender_type === 'user') ? 'user_id' :
                           (($sender_type === 'admin') ? 'admin_id' : 'mentor_id');

            if ($message['sender_type'] !== $sender_type || $message[$sender_field] != $sender_id) {
                return ['success' => false, 'message' => 'Not authorized to delete this message'];
            }

            $stmt = $this->conn->prepare("
                UPDATE messages
                SET deleted_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('i', $message_id);
            $stmt->execute();

            return ['success' => true, 'message' => 'Message deleted successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting message: ' . $e->getMessage()];
        }
    }

    /**
     * Get a single message
     *
     * @param int $message_id
     * @return array|null
     */
    private function getMessage($message_id) {
        $stmt = $this->conn->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->bind_param('i', $message_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Search messages
     *
     * @param string $participant_type
     * @param int $participant_id
     * @param string $search_term
     * @param int $limit
     * @return array List of matching messages with conversation info
     */
    public function searchMessages($participant_type, $participant_id, $search_term, $limit = 20) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $search_term = '%' . $search_term . '%';

        $stmt = $this->conn->prepare("
            SELECT
                m.*,
                c.conversation_type,
                c.name as conversation_title,
                CASE
                    WHEN m.sender_type = 'user' THEN u.full_name
                    WHEN m.sender_type = 'admin' THEN a.full_name
                    WHEN m.sender_type = 'mentor' THEN s.full_name
                END as sender_name
            FROM messages m
            INNER JOIN conversations c ON c.id = m.conversation_id
            INNER JOIN conversation_participants cp ON cp.conversation_id = c.id
            LEFT JOIN users u ON u.id = m.sender_id AND m.sender_type = 'user'
            LEFT JOIN admins a ON a.id = m.sender_admin_id AND m.sender_type = 'admin'
            LEFT JOIN sponsors s ON s.id = m.sender_mentor_id AND m.sender_type = 'mentor'
            WHERE cp.participant_type = ?
            AND cp.user_id <=> ?
            AND cp.admin_id <=> ?
            AND cp.mentor_id <=> ?
            AND m.message_text LIKE ?
            AND m.deleted_at IS NULL
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param('siiisi', $participant_type, $user_id, $admin_id, $mentor_id, $search_term, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ==================== READ RECEIPTS ====================

    /**
     * Mark messages in a conversation as read
     *
     * @param int $conversation_id
     * @param string $reader_type
     * @param int $reader_id
     * @return bool
     */
    public function markMessagesAsRead($conversation_id, $reader_type, $reader_id) {
        $user_id = ($reader_type === 'user') ? $reader_id : null;
        $admin_id = ($reader_type === 'admin') ? $reader_id : null;
        $mentor_id = ($reader_type === 'mentor') ? $reader_id : null;

        // Get unread messages
        $stmt = $this->conn->prepare("
            SELECT m.id
            FROM messages m
            WHERE m.conversation_id = ?
            AND m.deleted_at IS NULL
            AND NOT (m.sender_type = ? AND m.sender_id <=> ? AND m.sender_admin_id <=> ? AND m.sender_mentor_id <=> ?)
            AND m.id NOT IN (
                SELECT message_id
                FROM message_read_receipts
                WHERE reader_type = ?
                AND user_id <=> ?
                AND admin_id <=> ?
                AND mentor_id <=> ?
            )
        ");
        $stmt->bind_param(
            'isiiisiii',
            $conversation_id,
            $reader_type, $user_id, $admin_id, $mentor_id,
            $reader_type, $user_id, $admin_id, $mentor_id
        );
        $stmt->execute();
        $unread_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Mark each as read
        foreach ($unread_messages as $msg) {
            $insert_stmt = $this->conn->prepare("
                INSERT IGNORE INTO message_read_receipts
                (message_id, reader_type, user_id, admin_id, mentor_id, read_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insert_stmt->bind_param('isiii', $msg['id'], $reader_type, $user_id, $admin_id, $mentor_id);
            $insert_stmt->execute();
        }

        return true;
    }

    /**
     * Get unread message count for a user
     *
     * @param string $participant_type
     * @param int $participant_id
     * @return int Total unread count
     */
    public function getUnreadCount($participant_type, $participant_id) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM messages m
            INNER JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id
            WHERE cp.participant_type = ?
            AND cp.user_id <=> ?
            AND cp.admin_id <=> ?
            AND cp.mentor_id <=> ?
            AND m.deleted_at IS NULL
            AND NOT (m.sender_type = ? AND m.sender_id <=> ? AND m.sender_admin_id <=> ? AND m.sender_mentor_id <=> ?)
            AND m.id NOT IN (
                SELECT message_id
                FROM message_read_receipts
                WHERE reader_type = ?
                AND user_id <=> ?
                AND admin_id <=> ?
                AND mentor_id <=> ?
            )
        ");
        $stmt->bind_param(
            'siiisiiisiii',
            $participant_type, $user_id, $admin_id, $mentor_id,
            $participant_type, $user_id, $admin_id, $mentor_id,
            $participant_type, $user_id, $admin_id, $mentor_id
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)$result['count'];
    }

    // ==================== TYPING INDICATORS ====================

    /**
     * Set typing indicator
     *
     * @param int $conversation_id
     * @param string $participant_type
     * @param int $participant_id
     * @return bool
     */
    public function setTyping($conversation_id, $participant_type, $participant_id) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            INSERT INTO typing_indicators
            (conversation_id, participant_type, user_id, admin_id, mentor_id, started_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE started_at = NOW()
        ");
        $stmt->bind_param('isiii', $conversation_id, $participant_type, $user_id, $admin_id, $mentor_id);
        return $stmt->execute();
    }

    /**
     * Remove typing indicator
     *
     * @param int $conversation_id
     * @param string $participant_type
     * @param int $participant_id
     * @return bool
     */
    public function removeTyping($conversation_id, $participant_type, $participant_id) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            DELETE FROM typing_indicators
            WHERE conversation_id = ?
            AND participant_type = ?
            AND user_id <=> ?
            AND admin_id <=> ?
            AND mentor_id <=> ?
        ");
        $stmt->bind_param('isiii', $conversation_id, $participant_type, $user_id, $admin_id, $mentor_id);
        return $stmt->execute();
    }

    /**
     * Get who is typing in a conversation
     *
     * @param int $conversation_id
     * @return array List of participants currently typing
     */
    public function getTypingUsers($conversation_id) {
        // Clear stale typing indicators (older than 10 seconds)
        $this->conn->query("DELETE FROM typing_indicators WHERE started_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)");

        $stmt = $this->conn->prepare("
            SELECT
                ti.*,
                CASE
                    WHEN ti.participant_type = 'user' THEN u.full_name
                    WHEN ti.participant_type = 'admin' THEN a.full_name
                    WHEN ti.participant_type = 'mentor' THEN s.full_name
                END as name
            FROM typing_indicators ti
            LEFT JOIN users u ON u.id = ti.user_id
            LEFT JOIN admins a ON a.id = ti.admin_id
            LEFT JOIN sponsors s ON s.id = ti.mentor_id
            WHERE ti.conversation_id = ?
        ");
        $stmt->bind_param('i', $conversation_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ==================== ONLINE STATUS ====================

    /**
     * Update user's online status
     *
     * @param string $participant_type
     * @param int $participant_id
     * @param string $status 'online', 'away', 'offline'
     * @return bool
     */
    public function updateOnlineStatus($participant_type, $participant_id, $status = 'online') {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            INSERT INTO user_online_status
            (participant_type, user_id, admin_id, mentor_id, status, last_seen_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE status = ?, last_seen_at = NOW()
        ");
        $stmt->bind_param('siiiss', $participant_type, $user_id, $admin_id, $mentor_id, $status, $status);
        return $stmt->execute();
    }

    /**
     * Get online status for a user
     *
     * @param string $participant_type
     * @param int $participant_id
     * @return array Status info
     */
    public function getOnlineStatus($participant_type, $participant_id) {
        $user_id = ($participant_type === 'user') ? $participant_id : null;
        $admin_id = ($participant_type === 'admin') ? $participant_id : null;
        $mentor_id = ($participant_type === 'mentor') ? $participant_id : null;

        $stmt = $this->conn->prepare("
            SELECT status, last_seen_at
            FROM user_online_status
            WHERE participant_type = ?
            AND user_id <=> ?
            AND admin_id <=> ?
            AND mentor_id <=> ?
        ");
        $stmt->bind_param('siii', $participant_type, $user_id, $admin_id, $mentor_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            return ['status' => 'offline', 'last_seen_at' => null];
        }

        // Auto-mark as offline if last seen > 5 minutes ago
        $last_seen = strtotime($result['last_seen_at']);
        if (time() - $last_seen > 300) { // 5 minutes
            $result['status'] = 'offline';
        }

        return $result;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Update conversation's last activity timestamp
     *
     * @param int $conversation_id
     * @return bool
     */
    private function updateConversationActivity($conversation_id) {
        $stmt = $this->conn->prepare("
            UPDATE conversations
            SET last_activity_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('i', $conversation_id);
        return $stmt->execute();
    }

    /**
     * Notify participants of new message
     *
     * @param int $conversation_id
     * @param string $sender_type
     * @param int $sender_id
     * @param int $message_id
     * @return void
     */
    private function notifyParticipants($conversation_id, $sender_type, $sender_id, $message_id) {
        // Get all participants except sender
        $participants = $this->getConversationParticipants($conversation_id);

        foreach ($participants as $participant) {
            // Skip sender
            if ($participant['participant_type'] === $sender_type && $participant['id'] == $sender_id) {
                continue;
            }

            // Create notification
            $user_id = ($participant['participant_type'] === 'user') ? $participant['id'] : null;
            $admin_id = ($participant['participant_type'] === 'admin') ? $participant['id'] : null;
            $mentor_id = ($participant['participant_type'] === 'mentor') ? $participant['id'] : null;

            $notification_type = 'new_message';
            $title = 'New message';
            $message = 'You have a new message';
            $link = '/messages/conversation.php?id=' . $conversation_id;

            $stmt = $this->conn->prepare("
                INSERT INTO notifications
                (recipient_type, user_id, admin_id, mentor_id, notification_type, title, message, link, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param(
                'siiissss',
                $participant['participant_type'],
                $user_id,
                $admin_id,
                $mentor_id,
                $notification_type,
                $title,
                $message,
                $link
            );
            $stmt->execute();
        }
    }
}
