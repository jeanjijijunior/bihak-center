-- ========================================
-- USER AUTHENTICATION SYSTEM
-- Login, accounts, and user profile management for young people
-- ========================================

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(128),
    reset_token VARCHAR(128),
    reset_token_expires TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_profile_id (profile_id),
    INDEX idx_verification_token (verification_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Sessions Table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    remember_token VARCHAR(128) UNIQUE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Activity Log
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link existing profiles to user accounts
-- When someone creates an account, their profile_id gets linked
-- This allows them to edit their profile and track its status

-- Add user_id column to profiles table if it doesn't exist
ALTER TABLE profiles
    ADD COLUMN IF NOT EXISTS user_id INT NULL,
    ADD COLUMN IF NOT EXISTS allow_user_edit BOOLEAN DEFAULT TRUE,
    ADD INDEX IF NOT EXISTS idx_user_id (user_id);

-- Add foreign key constraint
ALTER TABLE profiles
    ADD CONSTRAINT fk_profiles_user_id
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- ========================================
-- DEMO USER FOR TESTING
-- ========================================
-- Email: demo@bihakcenter.org
-- Password: Demo@123
INSERT INTO users (email, password, full_name, email_verified, is_active)
VALUES (
    'demo@bihakcenter.org',
    '$2y$12$LQ5E3p5Y0K5Q5f5Y0K5Q5uJ5Y0K5Q5f5Y0K5Q5f5Y0K5Q5f5Y0K5Qe',
    'Demo User',
    TRUE,
    TRUE
)
ON DUPLICATE KEY UPDATE email = email;

-- ========================================
-- USEFUL VIEWS
-- ========================================

-- User Profile View
CREATE OR REPLACE VIEW user_profile_view AS
SELECT
    u.id as user_id,
    u.email,
    u.full_name,
    u.is_active,
    u.email_verified,
    u.last_login,
    u.created_at as user_since,
    p.id as profile_id,
    p.title as profile_title,
    p.status as profile_status,
    p.is_published as profile_published,
    p.profile_image,
    p.view_count,
    p.created_at as profile_created_at
FROM users u
LEFT JOIN profiles p ON u.profile_id = p.id;

-- ========================================
-- CLEANUP PROCEDURES
-- ========================================

-- Clean expired sessions (run periodically)
-- DELETE FROM user_sessions WHERE expires_at < NOW();

-- Clean old activity logs (keep 90 days)
-- DELETE FROM user_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- ========================================
-- NOTES
-- ========================================
-- 1. Demo user password: Demo@123 (bcrypt hashed)
-- 2. Session lifetime: 1 hour default
-- 3. Remember me: 30 days
-- 4. Email verification: Required for full access
-- 5. Failed login attempts: 5 attempts before 30-minute lockout
-- ========================================
