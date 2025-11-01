<?php
/**
 * Admin Authentication System
 * Secure session management, login/logout, and access control
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

class Auth {
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
     * Login admin user
     */
    public static function login($username, $password, $remember = false) {
        $conn = getDatabaseConnection();

        try {
            // Check rate limiting
            $identifier = $_SERVER['REMOTE_ADDR'];
            if (!Security::checkRateLimit($identifier, 'admin_login', 5, 900)) {
                Security::logSecurityEvent('login_rate_limited', [
                    'username' => $username,
                    'ip' => $identifier
                ]);
                return [
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again in 15 minutes.'
                ];
            }

            // Get admin user
            $stmt = $conn->prepare("
                SELECT id, username, email, password_hash, full_name, role, is_active,
                       failed_login_attempts, locked_until
                FROM admins
                WHERE username = ? OR email = ?
            ");
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                Security::logSecurityEvent('login_failed', [
                    'username' => $username,
                    'reason' => 'user_not_found'
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }

            $admin = $result->fetch_assoc();

            // Check if account is active
            if (!$admin['is_active']) {
                Security::logSecurityEvent('login_failed', [
                    'username' => $username,
                    'reason' => 'account_inactive'
                ]);
                return [
                    'success' => false,
                    'message' => 'Account is inactive. Please contact support.'
                ];
            }

            // Check if account is locked
            if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                $unlock_time = date('H:i', strtotime($admin['locked_until']));
                Security::logSecurityEvent('login_failed', [
                    'username' => $username,
                    'reason' => 'account_locked'
                ]);
                return [
                    'success' => false,
                    'message' => "Account is locked until {$unlock_time}. Too many failed attempts."
                ];
            }

            // Verify password
            if (!Security::verifyPassword($password, $admin['password_hash'])) {
                // Increment failed attempts
                $failed_attempts = $admin['failed_login_attempts'] + 1;
                $locked_until = null;

                if ($failed_attempts >= 5) {
                    $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                }

                $stmt = $conn->prepare("
                    UPDATE admins
                    SET failed_login_attempts = ?, locked_until = ?
                    WHERE id = ?
                ");
                $stmt->bind_param('isi', $failed_attempts, $locked_until, $admin['id']);
                $stmt->execute();

                Security::logSecurityEvent('login_failed', [
                    'username' => $username,
                    'reason' => 'invalid_password',
                    'failed_attempts' => $failed_attempts
                ]);

                $message = 'Invalid username or password.';
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
                UPDATE admins
                SET failed_login_attempts = 0,
                    locked_until = NULL,
                    last_login = NOW(),
                    last_login_ip = ?
                WHERE id = ?
            ");
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param('si', $ip, $admin['id']);
            $stmt->execute();

            // Create session
            $session_token = bin2hex(random_bytes(64));
            $expires_at = date('Y-m-d H:i:s', time() + self::$session_lifetime);
            $remember_token = $remember ? bin2hex(random_bytes(64)) : null;

            $stmt = $conn->prepare("
                INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent, remember_token, expires_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param('isssss', $admin['id'], $session_token, $ip, $user_agent, $remember_token, $expires_at);
            $stmt->execute();

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_name'] = $admin['full_name'];
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
            self::logActivity($admin['id'], 'login', null, null, 'Admin logged in successfully');

            Security::logSecurityEvent('login_success', [
                'admin_id' => $admin['id'],
                'username' => $admin['username']
            ]);

            closeDatabaseConnection($conn);

            return [
                'success' => true,
                'message' => 'Login successful.',
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'name' => $admin['full_name'],
                    'role' => $admin['role']
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
     * Logout admin user
     */
    public static function logout() {
        // Start session if not already started (but don't call init() to avoid recursion)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['admin_id'], $_SESSION['session_token'])) {
            $conn = getDatabaseConnection();

            // Delete session from database
            $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE session_token = ?");
            $stmt->bind_param('s', $_SESSION['session_token']);
            $stmt->execute();

            // Log activity
            self::logActivity($_SESSION['admin_id'], 'logout', null, null, 'Admin logged out');

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
        return isset($_SESSION['admin_id']) && isset($_SESSION['session_token']);
    }

    /**
     * Get current admin user
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'role' => $_SESSION['admin_role'],
            'name' => $_SESSION['admin_name']
        ];
    }

    /**
     * Validate session
     */
    private static function validateSession() {
        // Check if session exists
        if (!isset($_SESSION['admin_id'])) {
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
            SELECT admin_id, expires_at
            FROM admin_sessions
            WHERE session_token = ? AND admin_id = ?
        ");
        $stmt->bind_param('si', $_SESSION['session_token'], $_SESSION['admin_id']);
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

        $stmt = $conn->prepare("UPDATE admin_sessions SET expires_at = ? WHERE session_token = ?");
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
            SELECT a.id, a.username, a.full_name, a.role, a.is_active
            FROM admin_sessions s
            JOIN admins a ON s.admin_id = a.id
            WHERE s.remember_token = ? AND s.expires_at > NOW() AND a.is_active = TRUE
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            closeDatabaseConnection($conn);
            return false;
        }

        $admin = $result->fetch_assoc();

        // Create new session
        $session_token = bin2hex(random_bytes(64));
        $expires_at = date('Y-m-d H:i:s', time() + self::$session_lifetime);

        $stmt = $conn->prepare("
            UPDATE admin_sessions
            SET session_token = ?, expires_at = ?, last_activity = NOW()
            WHERE remember_token = ?
        ");
        $stmt->bind_param('sss', $session_token, $expires_at, $token);
        $stmt->execute();

        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_name'] = $admin['full_name'];
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
     * Check if user has specific role
     */
    public static function hasRole($role) {
        if (!self::check()) {
            return false;
        }

        $user_role = $_SESSION['admin_role'];

        // Super admin has all permissions
        if ($user_role === 'super_admin') {
            return true;
        }

        return $user_role === $role;
    }

    /**
     * Log admin activity
     */
    public static function logActivity($admin_id, $action, $entity_type = null, $entity_id = null, $details = null) {
        $conn = getDatabaseConnection();

        $stmt = $conn->prepare("
            INSERT INTO admin_activity_log (admin_id, action, entity_type, entity_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt->bind_param('issssss', $admin_id, $action, $entity_type, $entity_id, $details, $ip, $user_agent);
        $stmt->execute();

        closeDatabaseConnection($conn);
    }

    /**
     * Clean expired sessions (call periodically)
     */
    public static function cleanExpiredSessions() {
        $conn = getDatabaseConnection();
        $conn->query("DELETE FROM admin_sessions WHERE expires_at < NOW()");
        closeDatabaseConnection($conn);
    }
}

// Initialize auth on every request
Auth::init();
?>
