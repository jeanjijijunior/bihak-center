<?php
/**
 * Enterprise Security System
 * Implements bank-level security for Bihak Center
 */

class Security {

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitize input
     */
    public static function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);

            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);

            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);

            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            case 'string':
            default:
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Validate input
     */
    public static function validateInput($input, $type, $options = []) {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;

            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;

            case 'int':
                $valid = filter_var($input, FILTER_VALIDATE_INT) !== false;
                if ($valid && isset($options['min'])) {
                    $valid = $input >= $options['min'];
                }
                if ($valid && isset($options['max'])) {
                    $valid = $input <= $options['max'];
                }
                return $valid;

            case 'string':
                if (isset($options['min_length']) && strlen($input) < $options['min_length']) {
                    return false;
                }
                if (isset($options['max_length']) && strlen($input) > $options['max_length']) {
                    return false;
                }
                if (isset($options['pattern']) && !preg_match($options['pattern'], $input)) {
                    return false;
                }
                return true;

            case 'date':
                $date = DateTime::createFromFormat('Y-m-d', $input);
                return $date && $date->format('Y-m-d') === $input;

            default:
                return false;
        }
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $options = []) {
        $errors = [];

        // Check if file was uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file size
        $maxSize = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB default
        if ($file['size'] > $maxSize) {
            $errors[] = 'File too large (max ' . ($maxSize / 1024 / 1024) . 'MB)';
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = $options['allowed_mimes'] ?? ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'Invalid file type';
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = $options['allowed_extensions'] ?? ['jpg', 'jpeg', 'png'];
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension';
        }

        // Check if file is actually an image (for image uploads)
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = 'File is not a valid image';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }

    /**
     * Generate secure random filename
     */
    public static function generateSecureFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    }

    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Check rate limit
     */
    public static function checkRateLimit($identifier, $action, $limit = 5, $window = 900) {
        require_once __DIR__ . '/database.php';

        try {
            $conn = getDatabaseConnection();

            // Check if rate_limits table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'rate_limits'");
            if ($tableCheck->num_rows === 0) {
                // Table doesn't exist, allow the action (fail open for better UX during setup)
                closeDatabaseConnection($conn);
                return true;
            }

            // Clean old entries
            $conn->query("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL $window SECOND)");

            // Check current attempts
            $stmt = $conn->prepare("SELECT attempts FROM rate_limits WHERE identifier = ? AND action = ?");
            if (!$stmt) {
                // Query failed, allow the action (fail open)
                closeDatabaseConnection($conn);
                return true;
            }
            $stmt->bind_param('ss', $identifier, $action);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if ($row['attempts'] >= $limit) {
                    $stmt->close();
                    closeDatabaseConnection($conn);
                    return false;
                }

                // Increment attempts
                $stmt = $conn->prepare("UPDATE rate_limits SET attempts = attempts + 1 WHERE identifier = ? AND action = ?");
                $stmt->bind_param('ss', $identifier, $action);
                $stmt->execute();
            } else {
                // Create new entry
                $stmt = $conn->prepare("INSERT INTO rate_limits (identifier, action, attempts) VALUES (?, ?, 1)");
                $stmt->bind_param('ss', $identifier, $action);
                $stmt->execute();
            }

            $stmt->close();
            closeDatabaseConnection($conn);
            return true;

        } catch (Exception $e) {
            error_log('Rate limit error: ' . $e->getMessage());
            return true; // Fail open to avoid blocking users if system fails
        }
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        // Don't set headers if they've already been sent
        if (headers_sent()) {
            return;
        }

        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');

        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');

        // HSTS (only in production with HTTPS)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://translate.google.com https://www.google.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; frame-src https://translate.google.com;");

        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        // Remove any path info
        $filename = basename($filename);

        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        return $filename;
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);

        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];

        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    }
}

// Set security headers on every request
Security::setSecurityHeaders();
?>
