<?php
/**
 * Setup Security Questions for All Existing Users
 * Adds 3 predefined security questions with answers for all users
 *
 * Run this script once to set up security questions for existing users
 */

require_once __DIR__ . '/../config/database.php';

// Security questions and answers
$questions_data = [
    [
        'question' => 'Who is the founder?',
        'answer' => 'June',
        'display_order' => 9
    ],
    [
        'question' => 'Who is the other?',
        'answer' => 'July',
        'display_order' => 10
    ],
    [
        'question' => 'Who is the older?',
        'answer' => 'August',
        'display_order' => 11
    ]
];

try {
    $conn = getDatabaseConnection();
    $conn->begin_transaction();

    echo "Starting security questions setup...\n\n";

    // Step 1: Insert questions if they don't exist
    echo "Step 1: Adding security questions...\n";
    $question_ids = [];

    foreach ($questions_data as $q_data) {
        // Check if question already exists
        $stmt = $conn->prepare("SELECT id FROM security_questions WHERE question_text = ?");
        $stmt->bind_param('s', $q_data['question']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Question already exists
            $question_ids[] = [
                'id' => $row['id'],
                'question' => $q_data['question'],
                'answer' => $q_data['answer']
            ];
            echo "  ✓ Question already exists: '{$q_data['question']}' (ID: {$row['id']})\n";
        } else {
            // Insert new question
            $stmt = $conn->prepare("INSERT INTO security_questions (question_text, is_active, display_order) VALUES (?, 1, ?)");
            $stmt->bind_param('si', $q_data['question'], $q_data['display_order']);
            $stmt->execute();
            $new_id = $conn->insert_id;

            $question_ids[] = [
                'id' => $new_id,
                'question' => $q_data['question'],
                'answer' => $q_data['answer']
            ];
            echo "  ✓ Question added: '{$q_data['question']}' (ID: {$new_id})\n";
        }
        $stmt->close();
    }

    echo "\n";

    // Step 2: Get all active users
    echo "Step 2: Finding active users...\n";
    $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    echo "  ✓ Found " . count($users) . " active user(s)\n\n";

    // Step 3: Add answers for each user
    echo "Step 3: Adding security answers for users...\n";
    $answers_added = 0;
    $answers_skipped = 0;

    foreach ($users as $user) {
        echo "  Processing user: {$user['full_name']} ({$user['email']})\n";

        foreach ($question_ids as $q) {
            // Check if answer already exists
            $stmt = $conn->prepare("SELECT id FROM user_security_answers WHERE user_id = ? AND question_id = ?");
            $stmt->bind_param('ii', $user['id'], $q['id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "    - Answer already exists for: '{$q['question']}'\n";
                $answers_skipped++;
            } else {
                // Hash the answer using bcrypt (same as password_hash)
                $answer_hash = password_hash($q['answer'], PASSWORD_BCRYPT);

                // Insert the answer
                $stmt = $conn->prepare("INSERT INTO user_security_answers (user_id, question_id, answer_hash) VALUES (?, ?, ?)");
                $stmt->bind_param('iis', $user['id'], $q['id'], $answer_hash);
                $stmt->execute();

                echo "    + Added answer for: '{$q['question']}' → '{$q['answer']}'\n";
                $answers_added++;
            }
            $stmt->close();
        }
        echo "\n";
    }

    // Commit transaction
    $conn->commit();

    // Display summary
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ SETUP COMPLETED SUCCESSFULLY!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Questions created/found: " . count($question_ids) . "\n";
    echo "Users processed: " . count($users) . "\n";
    echo "Answers added: {$answers_added}\n";
    echo "Answers skipped (already existed): {$answers_skipped}\n";
    echo "\n";

    echo "Security Questions:\n";
    foreach ($question_ids as $q) {
        echo "  • {$q['question']} → {$q['answer']}\n";
    }
    echo "\n";

    echo "All users now have these 3 security questions configured.\n";
    echo "Users can reset their password using these questions.\n";

    closeDatabaseConnection($conn);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        closeDatabaseConnection($conn);
    }
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
