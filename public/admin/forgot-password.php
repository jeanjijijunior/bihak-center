<?php
/**
 * Admin Password Reset - Step 1: Enter Username/Email
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        $error = 'Please enter your username or email.';
    } else {
        try {
            $conn = getDatabaseConnection();

            // Check if admin exists
            $stmt = $conn->prepare("SELECT id, username, email FROM admins WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();

                // Check if admin has security questions
                $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_security_answers WHERE admin_id = ?");
                $checkStmt->bind_param('i', $admin['id']);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result()->fetch_assoc();

                if ($checkResult['count'] >= 3) {
                    $_SESSION['reset_admin_id'] = $admin['id'];
                    $_SESSION['reset_admin_username'] = $admin['username'];
                    $_SESSION['reset_step'] = 'questions';

                    header('Location: reset-security-questions.php');
                    exit;
                } else {
                    $error = 'Security questions not set up. Please contact the super admin.';
                }

                $checkStmt->close();
            } else {
                $error = 'Invalid username or email.';
            }

            $stmt->close();
            closeDatabaseConnection($conn);

        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
            error_log('Admin password reset error: ' . $e->getMessage());
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
    <title>Admin Password Reset - Bihak Center</title>
    <link rel="stylesheet" href="../../assets/css/admin-login.css">
    <style>
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            padding: 20px;
        }

        .reset-box {
            background: white;
            max-width: 450px;
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
            font-size: 26px;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .reset-header .badge {
            background: #1cabe2;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-box">
            <div class="reset-header">
                <div class="badge">ADMIN PANEL</div>
                <h1>🔐 Reset Password</h1>
                <p style="color: #718096;">Enter your admin username or email</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Enter your admin username or email"
                        required
                        autofocus
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    Continue
                </button>
            </form>

            <div class="login-footer" style="margin-top: 20px; text-align: center;">
                <a href="login.php" style="color: #1cabe2; text-decoration: none; font-weight: 500;">← Back to Admin Login</a>
            </div>
        </div>
    </div>
</body>
</html>
