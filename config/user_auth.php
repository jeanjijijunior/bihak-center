<?php
/**
 * User Authentication System
 * Login, signup, and account management for young people
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

class UserAuth {
    private static $session_lifetime = 3600; // 1 hour
    private static $remember_lifetime = 2592000; // 30 days

    /**
     * Initialize authentication system
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');

            session_start();
        }

        // Check session validity
        self::validateSession();
    }

    /**
     * Register new user
     */
    public static function register($email, $password, $full_name) {
        $conn = getDatabaseConnection();

        try {
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email address.'
                ];
            }

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                closeDatabaseConnection($conn);
                return [
                    'success' => false,
                    'message' => 'An account with this email already exists.'
                ];
            }

            // Hash password
            $hashed_password = Security::hashPassword($password);

            // Generate verification token
            $verification_token = bin2hex(random_bytes(32));

            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO users (email, password, full_name, verification_token)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param('ssss', $email, $hashed_password, $full_name, $verification_token);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                // Log activity
                self::logActivity($user_id, 'user_registered', null, null, 'New user registered');

                closeDatabaseConnection($conn);

                // TODO: Send verification email

                return [
                    'success' => true,
                    'message' => 'Account created successfully! Please check your email to verify your account.',
                    'user_id' => $user_id
                ];
            } else {
                closeDatabaseConnection($conn);
                return [
                    'success' => false,
                    'message' => 'Failed to create account. Please try again.'
                ];
            }

        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            closeDatabaseConnection($conn);
            return [
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.'
            ];
        }
    }

    /**
     * Login user
     */
    public static function login($email, $password, $remember = false) {
        $conn = getDatabaseConnection();

        try {
            // Check rate limiting
            $identifier = $_SERVER['REMOTE_ADDR'];
            if (!Security::checkRateLimit($identifier, 'user_login', 5, 900)) {
                return [
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again in 15 minutes.'
                ];
            }

            // Get user
            $stmt = $conn->prepare("
                SELECT id, email, password, full_name, profile_id, is_active,
                       email_verified, failed_login_attempts, locked_until
                FROM users
                WHERE email = ?
            ");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                closeDatabaseConnection($conn);
                return [
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ];
            }

            $user = $result->fetch_assoc();

            // Check if account is active
            if (!$user['is_active']) {
                closeDatabaseConnection($conn);
                return [
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.'
                ];
            }

            // Check if account is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $unlock_time = date('H:i', strtotime($user['locked_until']));
                closeDatabaseConnection($conn);
                return [
                    'success' => false,
                    'message' => "Account is locked until {$unlock_time}. Too many failed login attempts."
                ];
            }

            // Verify password
            if (!Security::verifyPassword($password, $user['password'])) {
                // Increment failed attempts
                $failed_attempts = $user['failed_login_attempts'] + 1;
                $locked_until = null;

                if ($failed_attempts >= 5) {
                    $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                }

                $stmt = $conn->prepare("
                    UPDATE users
                    SET failed_login_attempts = ?, locked_until = ?
                    WHERE id = ?
                ");
                $stmt->bind_param('isi', $failed_attempts, $locked_until, $user['id']);
                $stmt->execute();

                closeDatabaseConnection($conn);

                $message = 'Invalid email or password.';
                if ($failed_attempts >= 5) {
                    $message .= ' Account locked for 30 minutes due to multiple failed attempts.';
                } elseif ($failed_attempts >= 3) {
                    $remaining = 5 - $failed_attempts;
                    $message .= " {$remaining} attempts remaining before lockout.";
                }

                return [
                    'success' => false,
                    'message' => $message
                ];
            }

            // Password correct - reset failed attempts
            $stmt = $conn->prepare("
                UPDATE users
                SET failed_login_attempts = 0,
                    locked_until = NULL,
                    last_login = NOW(),
                    last_login_ip = ?
                WHERE id = ?
            ");
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param('si', $ip, $user['id']);
            $stmt->execute();

            // Create session
            $session_token = bin2hex(random_bytes(64));
            $expires_at = date('Y-m-d H:i:s', time() + self::$session_lifetime);
            $remember_token = $remember ? bin2hex(random_bytes(64)) : null;

            $stmt = $conn->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, remember_token, expires_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param('isssss', $user['id'], $session_token, $ip, $user_agent, $remember_token, $expires_at);
            $stmt->execute();

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['profile_id'] = $user['profile_id'];
            $_SESSION['email_verified'] = $user['email_verified'];
            $_SESSION['session_token'] = $session_token;
            $_SESSION['session_expires'] = time() + self::$session_lifetime;

            // Set remember me cookie
            if ($remember && $remember_token) {
                setcookie(
                    'remember_token',
                    $remember_token,
                    time() + self::$remember_lifetime,
                    '/',
                    '',
                    !empty($_SERVER['HTTPS']),
                    true
                );
            }

            // Log activity
            self::logActivity($user['id'], 'user_login', null, null, 'User logged in');

            closeDatabaseConnection($conn);

            return [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['full_name'],
                    'profile_id' => $user['profile_id'],
                    'email_verified' => $user['email_verified']
                ]
            ];

        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            closeDatabaseConnection($conn);
            return [
                'success' => false,
                'message' => 'An error occurred during login. Please try again.'
            ];
        }
    }

    /**
     * Logout user
     */
    public static function logout() {
        // Start session if not already started (but don't call init() to avoid recursion)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'], $_SESSION['session_token'])) {
            $conn = getDatabaseConnection();

            // Delete session from database
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->bind_param('s', $_SESSION['session_token']);
            $stmt->execute();

            // Log activity
            self::logActivity($_SESSION['user_id'], 'user_logout', null, null, 'User logged out');

            closeDatabaseConnection($conn);
        }

        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);
        }

        // Destroy session
        $_SESSION = [];
        session_destroy();

        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    /**
     * Check if user is authenticated
     */
    public static function check() {
        self::init();
        return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
    }

    /**
     * Get current user
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'profile_id' => $_SESSION['profile_id'] ?? null,
            'email_verified' => $_SESSION['email_verified'] ?? false
        ];
    }

    /**
     * Validate session
     */
    private static function validateSession() {
        // Check if session exists
        if (!isset($_SESSION['user_id'])) {
            // Try remember me token
            if (isset($_COOKIE['remember_token'])) {
                self::loginWithRememberToken($_COOKIE['remember_token']);
            }
            return;
        }

        // Check session expiration
        if (isset($_SESSION['session_expires']) && $_SESSION['session_expires'] < time()) {
            self::logout();
            return;
        }

        // Validate session token in database
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("
            SELECT user_id, expires_at
            FROM user_sessions
            WHERE session_token = ? AND user_id = ?
        ");
        $stmt->bind_param('si', $_SESSION['session_token'], $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            closeDatabaseConnection($conn);
            self::logout();
            return;
        }

        $session = $result->fetch_assoc();
        if (strtotime($session['expires_at']) < time()) {
            closeDatabaseConnection($conn);
            self::logout();
            return;
        }

        // Extend session
        $_SESSION['session_expires'] = time() + self::$session_lifetime;

        $stmt = $conn->prepare("UPDATE user_sessions SET expires_at = ? WHERE session_token = ?");
        $new_expires = date('Y-m-d H:i:s', time() + self::$session_lifetime);
        $stmt->bind_param('ss', $new_expires, $_SESSION['session_token']);
        $stmt->execute();

        closeDatabaseConnection($conn);
    }

    /**
     * Login with remember me token
     */
    private static function loginWithRememberToken($token) {
        $conn = getDatabaseConnection();

        $stmt = $conn->prepare("
            SELECT u.id, u.email, u.full_name, u.profile_id, u.email_verified, u.is_active
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.remember_token = ? AND s.expires_at > NOW() AND u.is_active = TRUE
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            closeDatabaseConnection($conn);
            return false;
        }

        $user = $result->fetch_assoc();

        // Create new session
        $session_token = bin2hex(random_bytes(64));
        $expires_at = date('Y-m-d H:i:s', time() + self::$session_lifetime);

        $stmt = $conn->prepare("
            UPDATE user_sessions
            SET session_token = ?, expires_at = ?, last_activity = NOW()
            WHERE remember_token = ?
        ");
        $stmt->bind_param('sss', $session_token, $expires_at, $token);
        $stmt->execute();

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['profile_id'] = $user['profile_id'];
        $_SESSION['email_verified'] = $user['email_verified'];
        $_SESSION['session_token'] = $session_token;
        $_SESSION['session_expires'] = time() + self::$session_lifetime;

        closeDatabaseConnection($conn);
        return true;
    }

    /**
     * Require authentication (redirect if not logged in)
     */
    public static function requireAuth() {
        // Prevent caching - no back button after logout
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

        if (!self::check()) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    /**
     * Log user activity
     */
    public static function logActivity($user_id, $action, $entity_type = null, $entity_id = null, $details = null) {
        $conn = getDatabaseConnection();

        $stmt = $conn->prepare("
            INSERT INTO user_activity_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt->bind_param('issssss', $user_id, $action, $entity_type, $entity_id, $details, $ip, $user_agent);
        $stmt->execute();

        closeDatabaseConnection($conn);
    }
}

// Initialize auth on every request
UserAuth::init();
?>
