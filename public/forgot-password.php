<?php
/**
 * Password Reset - Step 1: Enter Email
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $conn = getDatabaseConnection();

            $user_found = false;
            $user_id = null;
            $user_type = null;

            // Check in users table
            $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE email = ? AND is_active = 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $user_type = 'user';
                $user_found = true;
            }
            $stmt->close();

            // If not found in users, check sponsors (mentors)
            if (!$user_found) {
                $stmt = $conn->prepare("SELECT id, email, full_name FROM sponsors WHERE email = ? AND is_active = 1 AND status = 'approved'");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $user_id = $user['id'];
                    $user_type = 'sponsor';
                    $user_found = true;
                }
                $stmt->close();
            }

            // If not found in users or sponsors, check admins
            if (!$user_found) {
                $stmt = $conn->prepare("SELECT id, email, username as full_name FROM admins WHERE email = ? AND is_active = 1");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $user_id = $user['id'];
                    $user_type = 'admin';
                    $user_found = true;
                }
                $stmt->close();
            }

            if ($user_found) {
                // Check if user has security questions set up
                if ($user_type === 'sponsor') {
                    // Sponsors/mentors don't have security questions yet - contact support
                    $error = 'Password reset for mentors/sponsors is not yet enabled. Please contact admin@bihakcenter.org for assistance.';
                } else {
                    $table_suffix = ($user_type === 'user') ? 'user_security_answers' : 'admin_security_answers';
                    $id_field = ($user_type === 'user') ? 'user_id' : 'admin_id';

                    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM $table_suffix WHERE $id_field = ?");
                    $checkStmt->bind_param('i', $user_id);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result()->fetch_assoc();

                    if ($checkResult['count'] >= 3) {
                        // Store info in session and redirect to security questions
                        $_SESSION['reset_user_id'] = $user_id;
                        $_SESSION['reset_user_type'] = $user_type;
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['reset_step'] = 'questions';

                        header('Location: reset-security-questions.php');
                        exit;
                    } else {
                        $error = 'You haven\'t set up security questions yet. Please contact support for assistance.';
                    }

                    $checkStmt->close();
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $error = 'If this email exists in our system, you will be able to reset your password.';
            }

            closeDatabaseConnection($conn);

        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log('Password reset error: ' . $e->getMessage());
        }
    }
}

$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 500px;
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

        .alert-success {
            background: #e6fffa;
            color: #047857;
            border-left: 4px solid #047857;
        }

        .form-group {
            margin-bottom: 20px;
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

        .back-link a:hover {
            text-decoration: underline;
        }

        .info-box {
            background: #edf2f7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1><i class="fas fa-lock" style="color: #667eea;"></i> Reset Password</h1>
            <p>Enter your email to begin the password reset process</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>How it works:</strong><br>
            1. Enter your email address<br>
            2. Answer your security questions<br>
            3. Create a new password
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="your.email@example.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                    autofocus
                >
            </div>

            <button type="submit" class="btn">Continue</button>
        </form>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
