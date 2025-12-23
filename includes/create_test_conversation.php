<?php
/**
 * Create Test Conversation and Messages
 * This script creates sample data for testing the messaging system
 */

require_once __DIR__ . '/../config/database.php';

$conn = getDatabaseConnection();

echo "🧪 Creating test conversation and messages...\n\n";

// Get a test user
$user_result = $conn->query("SELECT id, full_name, email FROM users LIMIT 1");
if ($user_result->num_rows === 0) {
    die("❌ No users found in database. Please create a user first.\n");
}
$user = $user_result->fetch_assoc();

// Get a test sponsor (mentor)
$sponsor_result = $conn->query("SELECT id, full_name, email FROM sponsors LIMIT 1");
if ($sponsor_result->num_rows === 0) {
    die("❌ No sponsors found in database. Please create a sponsor first.\n");
}
$sponsor = $sponsor_result->fetch_assoc();

echo "✅ Found test user: {$user['full_name']} (ID: {$user['id']})\n";
echo "✅ Found test mentor: {$sponsor['full_name']} (ID: {$sponsor['id']})\n\n";

// Check if conversation already exists
$check = $conn->prepare("
    SELECT c.id
    FROM conversations c
    INNER JOIN conversation_participants cp1 ON cp1.conversation_id = c.id
    INNER JOIN conversation_participants cp2 ON cp2.conversation_id = c.id
    WHERE c.conversation_type = 'direct'
    AND cp1.participant_type = 'user' AND cp1.user_id = ?
    AND cp2.participant_type = 'mentor' AND cp2.mentor_id = ?
");
$check->bind_param('ii', $user['id'], $sponsor['id']);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $conv = $result->fetch_assoc();
    echo "✅ Conversation already exists (ID: {$conv['id']})\n";
    $conversation_id = $conv['id'];
} else {
    // Create conversation
    $stmt = $conn->prepare("
        INSERT INTO conversations (conversation_type, created_by, created_at, last_message_at)
        VALUES ('direct', ?, NOW(), NOW())
    ");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $conversation_id = $conn->insert_id;

    echo "✅ Created new conversation (ID: $conversation_id)\n";

    // Add user as participant
    $stmt = $conn->prepare("
        INSERT INTO conversation_participants
        (conversation_id, participant_type, user_id, joined_at)
        VALUES (?, 'user', ?, NOW())
    ");
    $stmt->bind_param('ii', $conversation_id, $user['id']);
    $stmt->execute();

    echo "✅ Added user as participant\n";

    // Add mentor as participant
    $stmt = $conn->prepare("
        INSERT INTO conversation_participants
        (conversation_id, participant_type, mentor_id, joined_at)
        VALUES (?, 'mentor', ?, NOW())
    ");
    $stmt->bind_param('ii', $conversation_id, $sponsor['id']);
    $stmt->execute();

    echo "✅ Added mentor as participant\n";

    // Add some test messages
    $messages = [
        "Hello! How can I help you with your entrepreneurship journey?",
        "Hi! I'm working on my business plan and would love some guidance.",
        "That's great! What specific areas would you like to focus on?",
        "I'm struggling with the financial projections section.",
        "No problem, let's start with revenue projections. What's your business model?"
    ];

    $is_from_mentor = true;
    foreach ($messages as $msg) {
        if ($is_from_mentor) {
            $stmt = $conn->prepare("
                INSERT INTO messages
                (conversation_id, sender_type, sender_mentor_id, message_text, created_at)
                VALUES (?, 'mentor', ?, ?, NOW())
            ");
            $stmt->bind_param('iis', $conversation_id, $sponsor['id'], $msg);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO messages
                (conversation_id, sender_type, sender_id, message_text, created_at)
                VALUES (?, 'user', ?, ?, NOW())
            ");
            $stmt->bind_param('iis', $conversation_id, $user['id'], $msg);
        }
        $stmt->execute();
        $is_from_mentor = !$is_from_mentor;
    }

    echo "✅ Added " . count($messages) . " test messages\n";
}

echo "\n✅ Test data ready!\n\n";
echo "📋 Test Information:\n";
echo "-------------------\n";
echo "Conversation ID: $conversation_id\n";
echo "User ID: {$user['id']} ({$user['full_name']})\n";
echo "Mentor ID: {$sponsor['id']} ({$sponsor['full_name']})\n";
echo "\n";
echo "🧪 To test:\n";
echo "1. Open: http://localhost/public/test-websocket.php\n";
echo "2. Use Conversation ID: $conversation_id\n";
echo "3. Click 'Send Test Message' to send a message\n";
echo "4. Open the same page in another browser (incognito) to see real-time updates\n";

closeDatabaseConnection($conn);
