<?php
/**
 * Password Reset - Step 2: Answer Security Questions
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Check if user came from step 1
if (!isset($_SESSION['reset_user_id']) || $_SESSION['reset_step'] !== 'questions') {
    header('Location: forgot-password.php');
    exit;
}

$error = '';
$conn = getDatabaseConnection();

// Get user's security questions
$stmt = $conn->prepare("
    SELECT usa.id, usa.question_id, sq.question_text
    FROM user_security_answers usa
    JOIN security_questions sq ON usa.question_id = sq.id
    WHERE usa.user_id = ?
    LIMIT 3
");
$stmt->bind_param('i', $_SESSION['reset_user_id']);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($questions) < 3) {
    $_SESSION['reset_error'] = 'Security questions not properly set up. Please contact support.';
    header('Location: forgot-password.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];

    if (count($answers) < 3) {
        $error = 'Please answer all security questions.';
    } else {
        $correct_count = 0;

        foreach ($questions as $question) {
            $user_answer = trim($answers[$question['id']] ?? '');

            if (!empty($user_answer)) {
                // Get stored answer hash
                $checkStmt = $conn->prepare("SELECT answer_hash FROM user_security_answers WHERE id = ?");
                $checkStmt->bind_param('i', $question['id']);
                $checkStmt->execute();
                $stored = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();

                // Verify answer (case-insensitive, trimmed)
                if (password_verify(strtolower($user_answer), $stored['answer_hash'])) {
                    $correct_count++;
                }
            }
        }

        if ($correct_count >= 3) {
            // All answers correct, proceed to password reset
            $_SESSION['reset_step'] = 'new_password';
            $_SESSION['reset_verified'] = true;
            header('Location: reset-new-password.php');
            exit;
        } else {
            $error = 'One or more answers are incorrect. Please try again.';

            // Log failed attempt
            Security::logSecurityEvent('password_reset_failed_security_questions', [
                'user_id' => $_SESSION['reset_user_id'],
                'email' => $_SESSION['reset_email']
            ]);
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
    <title>Security Questions - Bihak Center</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            background: white;
            max-width: 600px;
            width: 100%;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-header h1 {
            font-size: 28px;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .reset-header p {
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

        .question-group {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .question-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
            display: block;
        }

        .question-number {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            margin-right: 10px;
            font-size: 14px;
            font-weight: bold;
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
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .info-box {
            background: #edf2f7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #4a5568;
        }

        .security-note {
            background: #fef5e7;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #856404;
            margin-bottom: 20px;
            border-left: 3px solid #f39c12;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>🔒 Security Questions</h1>
            <p>Answer your security questions to verify your identity</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="security-note">
            <strong>⚠️ Security Note:</strong> Answers are case-insensitive. Please enter the exact answers you provided during setup.
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <?php foreach ($questions as $index => $question): ?>
                <div class="question-group">
                    <label class="question-label">
                        <span class="question-number"><?php echo $index + 1; ?></span>
                        <?php echo htmlspecialchars($question['question_text']); ?>
                    </label>
                    <input
                        type="text"
                        name="answers[<?php echo $question['id']; ?>]"
                        class="form-control"
                        placeholder="Your answer"
                        required
                        autocomplete="off"
                    >
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn">Verify & Continue</button>
        </form>

        <div class="back-link">
            <a href="forgot-password.php">← Start Over</a>
        </div>
    </div>
</body>
</html>
