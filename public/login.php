<?php
/**
 * User Login Page
 * For young people to access their accounts
 * Admin users are automatically redirected to admin login
 */

require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

// Initialize authentication systems
UserAuth::init();
Auth::init();

// If already logged in as user, redirect to account
if (UserAuth::check()) {
    header('Location: my-account.php');
    exit;
}

// If already logged in as admin, redirect to dashboard
if (Auth::check()) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            // First, check if this is an admin account
            $conn = getDatabaseConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admins WHERE email = ? OR username = ?");
            $stmt->bind_param('ss', $email, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $is_admin = $row['count'] > 0;
            $stmt->close();

            if ($is_admin) {
                // Try admin login
                $adminResult = Auth::login($email, $password, $remember);

                if ($adminResult['success']) {
                    // Redirect to admin dashboard
                    header('Location: admin/dashboard.php');
                    exit;
                } else {
                    $error = $adminResult['message'];
                }
            } else {
                // Try user login
                $userResult = UserAuth::login($email, $password, $remember);

                if ($userResult['success']) {
                    // Redirect to account or requested page (validate to prevent open redirects)
                    $redirect = $_GET['redirect'] ?? 'my-account.php';

                    // Security: Only allow relative URLs (no external redirects)
                    if (strpos($redirect, '://') !== false || strpos($redirect, '//') === 0) {
                        $redirect = 'my-account.php';
                    }

                    // Remove any potential XSS in redirect
                    $redirect = filter_var($redirect, FILTER_SANITIZE_URL);

                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = $userResult['message'];
                }
            }
        }
    }
}

// Check for logout success message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'You have been logged out successfully.';
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    <link rel="icon" type="image/png" href="../assets/images/logob.png">
    <style>
        /* Override admin colors with user-friendly colors */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(28, 171, 226, 0.5);
        }

        .login-footer a {
            color: #1cabe2;
        }

        .back-to-home {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-to-home:hover {
            transform: translateX(-5px);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .signup-link p {
            color: #718096;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .signup-link a {
            color: #1cabe2;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <!-- Back to Home -->
            <a href="index.php" class="back-to-home">
                ← Back to Home
            </a>

            <!-- Logo and Header -->
            <div class="login-header">
                <img src="../assets/images/logob.png" alt="Bihak Center" class="login-logo">
                <h1>Welcome Back!</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="login-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="input-icon">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="your.email@example.com"
                            required
                            autocomplete="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="input-icon">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" id="eyeIcon">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkbox-label">Remember me for 30 days</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <span class="btn-text">Sign In</span>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="btn-icon">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </form>

            <!-- Sign Up Link -->
            <div class="signup-link">
                <p>Don't have an account yet?</p>
                <a href="signup.php">Create an Account - Share Your Story</a>
            </div>

            <!-- Admin Login Link -->
            <div class="signup-link" style="border-top: none; padding-top: 10px; margin-top: 10px;">
                <p style="color: #94a3b8; font-size: 13px;">Are you an administrator?</p>
                <a href="admin/login.php" style="color: #f59e0b; font-size: 14px;">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                    </svg>
                    Admin Portal Login
                </a>
            </div>

            <!-- Login Footer -->
            <div class="login-footer">
                <p class="security-notice">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Secure connection with encryption
                </p>
                <div class="login-help">
                    <a href="index.php">Back to Website</a>
                    <span class="separator">•</span>
                    <a href="contact.php">Need help?</a>
                </div>
            </div>
        </div>

        <!-- Background Animation -->
        <div class="background-animation">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-3"></div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"/><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>';
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }

            // Simple email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
        });

        // Auto-focus first input
        document.getElementById('email').focus();
    </script>
</body>
</html>
