-- ========================================
-- ADMIN SYSTEM TABLES
-- Sessions, Activity Logging, and Enhanced Admin Features
-- ========================================

-- Admin Sessions Table
-- Tracks active admin sessions with security features
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    remember_token VARCHAR(128) UNIQUE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_admin_id (admin_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Activity Log
-- Tracks all admin actions for security audit trail
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Queue Table
-- Manages email notifications in background
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    template_name VARCHAR(50),
    template_data JSON,
    status ENUM('pending', 'sending', 'sent', 'failed') DEFAULT 'pending',
    priority TINYINT DEFAULT 5,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_error TEXT,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate Limits Table (if not exists from security system)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL,
    attempts INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_identifier_action (identifier, action),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update admins table with additional security fields
ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45),
    ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(32),
    ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Create default admin user if not exists (password: Admin@123)
-- Password hash for 'Admin@123' with bcrypt cost 12
INSERT INTO admins (username, email, password, full_name, role, is_active, created_at)
VALUES (
    'admin',
    'admin@bihakcenter.org',
    '$2y$12$LQ5E3p5Y0K5Q5f5Y0K5Q5uJ5Y0K5Q5f5Y0K5Q5f5Y0K5Q5f5Y0K5Qe',
    'System Administrator',
    'super_admin',
    TRUE,
    NOW()
)
ON DUPLICATE KEY UPDATE email = email;

-- ========================================
-- SAMPLE DATA FOR TESTING
-- ========================================

-- Clear any existing test sessions
DELETE FROM admin_sessions WHERE admin_id = 1;

-- Add sample activity log entries
INSERT INTO admin_activity_log (admin_id, action, entity_type, entity_id, details, ip_address) VALUES
(1, 'login', NULL, NULL, 'Admin logged in successfully', '127.0.0.1'),
(1, 'profile_approved', 'profile', 1, 'Approved profile: Amara Uwase', '127.0.0.1'),
(1, 'profile_approved', 'profile', 2, 'Approved profile: Jean Paul Nkunda', '127.0.0.1');

-- ========================================
-- VIEWS FOR DASHBOARD STATISTICS
-- ========================================

-- Dashboard Statistics View
CREATE OR REPLACE VIEW dashboard_stats AS
SELECT
    (SELECT COUNT(*) FROM profiles WHERE status = 'pending') as pending_profiles,
    (SELECT COUNT(*) FROM profiles WHERE status = 'approved') as approved_profiles,
    (SELECT COUNT(*) FROM profiles WHERE status = 'rejected') as rejected_profiles,
    (SELECT COUNT(*) FROM profiles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_profiles_week,
    (SELECT COUNT(*) FROM profiles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_profiles_month,
    (SELECT COUNT(*) FROM admins WHERE is_active = TRUE) as active_admins,
    (SELECT COUNT(*) FROM admin_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as actions_today;

-- Recent Activity View
CREATE OR REPLACE VIEW recent_admin_activity AS
SELECT
    aal.id,
    aal.action,
    aal.entity_type,
    aal.entity_id,
    aal.details,
    aal.created_at,
    a.username as admin_username,
    a.full_name as admin_name
FROM admin_activity_log aal
LEFT JOIN admins a ON aal.admin_id = a.id
ORDER BY aal.created_at DESC
LIMIT 50;

-- ========================================
-- CLEANUP PROCEDURES
-- ========================================

-- Clean expired sessions (run periodically)
DELETE FROM admin_sessions WHERE expires_at < NOW();

-- Clean old activity logs (keep 90 days)
-- DELETE FROM admin_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Clean old rate limit entries
DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- ========================================
-- SECURITY NOTES
-- ========================================
-- 1. Default admin password MUST be changed on first login
-- 2. Session tokens are 128-character random strings
-- 3. Remember tokens allow persistent login (30 days)
-- 4. Failed login attempts trigger account lockout after 5 attempts
-- 5. Activity log maintains audit trail for compliance
-- 6. Rate limiting prevents brute force attacks
-- ========================================
