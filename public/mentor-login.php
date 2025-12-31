<?php
/**
 * Mentor/Sponsor Login Page
 * For mentors and sponsors to access their dashboard
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['sponsor_id'])) {
    header('Location: mentorship/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $conn = getDatabaseConnection();

        // Check if sponsor exists with this email
        $stmt = $conn->prepare("
            SELECT id, full_name, email, password_hash, role_type, status, is_active
            FROM sponsors
            WHERE email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $sponsor = $result->fetch_assoc();

        if ($sponsor) {
            // Check if account is approved and active
            if ($sponsor['status'] !== 'approved') {
                $error = 'Your account is pending approval. Please wait for admin confirmation.';
            } elseif (!$sponsor['is_active']) {
                $error = 'Your account has been deactivated. Please contact support.';
            } elseif (isset($sponsor['password_hash']) && password_verify($password, $sponsor['password_hash'])) {
                // Login successful
                $_SESSION['sponsor_id'] = $sponsor['id'];
                $_SESSION['sponsor_name'] = $sponsor['full_name'];
                $_SESSION['sponsor_email'] = $sponsor['email'];
                $_SESSION['sponsor_role'] = $sponsor['role_type'];

                // Log activity
                $activity_stmt = $conn->prepare("
                    INSERT INTO activity_log (user_type, user_id, action, description, ip_address, created_at)
                    VALUES ('sponsor', ?, 'login', 'Mentor logged in', ?, NOW())
                ");
                $ip = $_SERVER['REMOTE_ADDR'];
                $activity_stmt->bind_param('is', $sponsor['id'], $ip);
                $activity_stmt->execute();

                // Redirect to dashboard
                header('Location: mentorship/dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }

        closeDatabaseConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Login - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            padding: 50px 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1cabe2;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }

        .login-footer a {
            color: #1cabe2;
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">🎓</div>
            <h1>Mentor Login</h1>
            <p>Access your mentorship dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="mentor@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Enter your password"
                >
            </div>

            <button type="submit" class="btn-login">
                🔓 Login to Dashboard
            </button>
        </form>

        <div class="login-footer">
            <p>Not a mentor yet? <a href="get-involved.php">Register here</a></p>
            <p style="margin-top: 10px;"><a href="index.php">← Back to home</a></p>
        </div>
    </div>
</body>
</html>
