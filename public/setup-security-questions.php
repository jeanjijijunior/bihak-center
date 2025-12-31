<?php
/**
 * Setup Security Questions
 * Users must set up 3 security questions for password recovery
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=setup-security-questions.php');
    exit;
}

$error = '';
$success = '';
$conn = getDatabaseConnection();

// Check if user already has security questions
$checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM user_security_answers WHERE user_id = ?");
$checkStmt->bind_param('i', $_SESSION['user_id']);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

$has_questions = $existing['count'] >= 3;

// Get all available security questions
$questionsStmt = $conn->query("SELECT id, question_text FROM security_questions WHERE is_active = 1 ORDER BY display_order");
$available_questions = $questionsStmt->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_questions = $_POST['questions'] ?? [];
    $answers = $_POST['answers'] ?? [];

    if (count($selected_questions) < 3) {
        $error = 'Please select 3 different security questions.';
    } elseif (count(array_unique($selected_questions)) < 3) {
        $error = 'Please select 3 different questions (no duplicates).';
    } else {
        $all_answered = true;
        foreach ($selected_questions as $index => $q_id) {
            if (empty(trim($answers[$index] ?? ''))) {
                $all_answered = false;
                break;
            }
        }

        if (!$all_answered) {
            $error = 'Please provide answers for all 3 questions.';
        } else {
            try {
                $conn->begin_transaction();

                // Delete existing questions if updating
                if ($has_questions) {
                    $deleteStmt = $conn->prepare("DELETE FROM user_security_answers WHERE user_id = ?");
                    $deleteStmt->bind_param('i', $_SESSION['user_id']);
                    $deleteStmt->execute();
                    $deleteStmt->close();
                }

                // Insert new questions and answers
                $insertStmt = $conn->prepare("INSERT INTO user_security_answers (user_id, question_id, answer_hash) VALUES (?, ?, ?)");

                foreach ($selected_questions as $index => $question_id) {
                    $answer = trim($answers[$index]);
                    // Hash answer (case-insensitive)
                    $answer_hash = password_hash(strtolower($answer), PASSWORD_BCRYPT);

                    $insertStmt->bind_param('iis', $_SESSION['user_id'], $question_id, $answer_hash);
                    $insertStmt->execute();
                }

                $insertStmt->close();
                $conn->commit();

                $success = 'Security questions saved successfully! You can now use them to reset your password if needed.';
                $has_questions = true;

            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Failed to save security questions. Please try again.';
                error_log('Security questions setup error: ' . $e->getMessage());
            }
        }
    }
}

closeDatabaseConnection($conn);
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Security Questions - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .header p {
            color: #718096;
            font-size: 15px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            color: #c53030;
            border-left: 4px solid #c53030;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .info-box {
            background: #e6f7ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #1890ff;
        }

        .question-group {
            background: #f7fafc;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #1cabe2;
        }

        .question-number {
            display: inline-block;
            background: #1cabe2;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1cabe2;
        }

        select.form-control {
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 20px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #1cabe2;
            text-decoration: none;
            font-weight: 500;
        }

        .security-tips {
            background: #fef5e7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            border-left: 3px solid #f39c12;
        }

        .security-tips ul {
            margin: 10px 0 0;
            padding-left: 20px;
        }

        .security-tips li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header_new.php'; ?>

    <div class="container">
        <div class="header">
            <h1>🔐 Setup Security Questions</h1>
            <p><?php echo $has_questions ? 'Update' : 'Set up'; ?> your security questions for password recovery</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>Why security questions?</strong><br>
            Security questions provide a way to reset your password without email. Choose questions only you know the answer to.
        </div>

        <form method="POST" action="" id="securityForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="question-group">
                    <div class="question-number"><?php echo $i; ?></div>

                    <div class="form-group">
                        <label for="question_<?php echo $i; ?>">Select Question <?php echo $i; ?></label>
                        <select name="questions[]" id="question_<?php echo $i; ?>" class="form-control" required>
                            <option value="">-- Choose a question --</option>
                            <?php foreach ($available_questions as $q): ?>
                                <option value="<?php echo $q['id']; ?>">
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="answer_<?php echo $i; ?>">Your Answer</label>
                        <input
                            type="text"
                            name="answers[]"
                            id="answer_<?php echo $i; ?>"
                            class="form-control"
                            placeholder="Type your answer here"
                            required
                            autocomplete="off"
                        >
                        <small style="color: #718096; font-size: 13px;">Answers are case-insensitive</small>
                    </div>
                </div>
            <?php endfor; ?>

            <button type="submit" class="btn">
                <?php echo $has_questions ? 'Update' : 'Save'; ?> Security Questions
            </button>
        </form>

        <div class="security-tips">
            <strong>💡 Security Tips:</strong>
            <ul>
                <li>Choose questions that are easy for you to remember but hard for others to guess</li>
                <li>Don't use information that's publicly available on social media</li>
                <li>Your answers are encrypted and stored securely</li>
                <li>You can update your questions anytime from your profile</li>
            </ul>
        </div>

        <div class="back-link">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Validate no duplicate questions selected
        document.getElementById('securityForm').addEventListener('submit', function(e) {
            const selects = document.querySelectorAll('select[name="questions[]"]');
            const values = Array.from(selects).map(s => s.value).filter(v => v);

            if (new Set(values).size !== values.length) {
                e.preventDefault();
                alert('Please select 3 different questions. No duplicates allowed.');
            }
        });
    </script>
</body>
</html>
