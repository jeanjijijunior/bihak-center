<?php
/**
 * Password Reset - Step 3: Set New Password
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Check if user completed security questions
if (!isset($_SESSION['reset_user_id']) || $_SESSION['reset_step'] !== 'new_password' || !isset($_SESSION['reset_verified'])) {
    header('Location: forgot-password.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $conn = getDatabaseConnection();

            // Hash new password
            $password_hash = Security::hashPassword($new_password);

            // Update password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $password_hash, $_SESSION['reset_user_id']);

            if ($stmt->execute()) {
                // Log successful password reset
                Security::logSecurityEvent('password_reset_success', [
                    'user_id' => $_SESSION['reset_user_id'],
                    'email' => $_SESSION['reset_email']
                ]);

                // Clear session
                $user_email = $_SESSION['reset_email'];
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_step']);
                unset($_SESSION['reset_verified']);

                $success = 'Password reset successful! You can now login with your new password.';

                // Redirect after 3 seconds
                header('refresh:3;url=login.php?message=' . urlencode('Password reset successful! Please login.'));
            } else {
                $error = 'Failed to update password. Please try again.';
            }

            $stmt->close();
            closeDatabaseConnection($conn);

        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
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
    <title>Set New Password - Bihak Center</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
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
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
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

        .password-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 45px 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1cabe2;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            padding: 5px;
        }

        .password-requirements {
            background: #edf2f7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .password-requirements ul {
            margin: 10px 0 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 5px;
            color: #4a5568;
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
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .success-checkmark {
            text-align: center;
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <?php if ($success): ?>
            <div class="success-checkmark">✓</div>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <p style="text-align: center; color: #718096;">Redirecting to login page...</p>
        <?php else: ?>
            <div class="reset-header">
                <h1>🔑 Set New Password</h1>
                <p>Create a strong password for your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Mix of letters and numbers recommended</li>
                    <li>Include special characters for extra security</li>
                </ul>
            </div>

            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="form-control"
                            placeholder="Enter new password"
                            required
                            minlength="8"
                            autofocus
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                            👁️
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Confirm new password"
                            required
                            minlength="8"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }

        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
