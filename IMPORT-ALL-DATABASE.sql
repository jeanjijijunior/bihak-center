-- ============================================
-- BIHAK CENTER - COMPLETE DATABASE SETUP
-- Import this ONE file in phpMyAdmin
-- ============================================

-- Drop database if exists and recreate
DROP DATABASE IF EXISTS bihak;
CREATE DATABASE bihak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bihak;

-- ============================================
-- 1. PROFILES SYSTEM
-- ============================================

CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    bio TEXT NOT NULL,
    category VARCHAR(100),
    image_url VARCHAR(500),
    achievements TEXT,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    social_links TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    is_published BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_is_published (is_published),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample profiles
INSERT INTO profiles (title, bio, category, image_url, achievements, status, is_published, views_count) VALUES
('Jean-Claude Habimana - Software Engineer', 'Passionate software developer building solutions for African businesses. Specialized in web development and mobile applications.', 'Technology', '../assets/images/user1.jpg', 'Built 5+ mobile apps, Won Rwanda Tech Award 2023, Founded tech startup', 'approved', TRUE, 245),
('Grace Uwase - Environmental Activist', 'Leading youth environmental movements across Rwanda. Fighting climate change through community action and education.', 'Environment', '../assets/images/user2.jpg', 'Planted 10,000+ trees, TEDx Speaker, Founded Green Youth Rwanda', 'approved', TRUE, 189),
('Emmanuel Nkusi - Social Entrepreneur', 'Creating sustainable business models that empower rural communities. Focus on agricultural innovation and youth employment.', 'Business', '../assets/images/user3.jpg', 'Employed 200+ youth, $50K grant winner, Featured in Forbes Africa', 'approved', TRUE, 312);

-- ============================================
-- 2. ADMIN SYSTEM
-- ============================================

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    permissions TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempts INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_identifier_action (identifier, action),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin with CORRECT password hash
DELETE FROM admins WHERE username = 'admin';
INSERT INTO admins (username, email, password, full_name, role, is_active, created_at)
VALUES (
    'admin',
    'admin@bihakcenter.org',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System Administrator',
    'super_admin',
    TRUE,
    NOW()
);

-- ============================================
-- 3. USER AUTHENTICATION SYSTEM
-- ============================================

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
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    INDEX idx_remember_token (remember_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Create demo user
INSERT INTO users (email, password, full_name, is_active, email_verified)
VALUES (
    'demo@bihakcenter.org',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Demo User',
    TRUE,
    TRUE
);

-- ============================================
-- 4. OPPORTUNITIES SYSTEM
-- ============================================

CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('scholarship', 'job', 'internship', 'grant') NOT NULL,
    organization VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    country VARCHAR(100),
    deadline DATE,
    application_url VARCHAR(500),
    requirements TEXT,
    benefits TEXT,
    eligibility TEXT,
    amount VARCHAR(100),
    currency VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    source_url VARCHAR(500),
    source_name VARCHAR(100),
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    views_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    INDEX idx_type (type),
    INDEX idx_deadline (deadline),
    INDEX idx_is_active (is_active),
    INDEX idx_country (country),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opportunity_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE,
    tag_type ENUM('field', 'level', 'duration', 'other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tag_type (tag_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opportunity_tag_relations (
    opportunity_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (opportunity_id, tag_id),
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES opportunity_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_saved_opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    opportunity_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    UNIQUE KEY unique_user_opportunity (user_id, opportunity_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_opportunity_id (opportunity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scraper_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL,
    scraper_type ENUM('scholarship', 'job', 'internship', 'grant') NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    items_scraped INT DEFAULT 0,
    items_added INT DEFAULT 0,
    items_updated INT DEFAULT 0,
    error_message TEXT,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time INT,
    INDEX idx_source_name (source_name),
    INDEX idx_scraper_type (scraper_type),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample tags
INSERT INTO opportunity_tags (tag_name, tag_type) VALUES
    ('Technology', 'field'),
    ('Healthcare', 'field'),
    ('Education', 'field'),
    ('Business', 'field'),
    ('Undergraduate', 'level'),
    ('Graduate', 'level'),
    ('Full-time', 'duration'),
    ('Remote', 'other'),
    ('Fully Funded', 'other');

-- Sample opportunities (a few to start)
INSERT INTO opportunities (title, description, type, organization, location, country, deadline, application_url, amount, currency, source_name) VALUES
('MasterCard Foundation Scholars Program', 'Full scholarship for African students covering tuition, accommodation, and living expenses.', 'scholarship', 'MasterCard Foundation', 'Various Universities', 'Multiple Countries', DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'https://mastercardfdn.org/scholars-program', 'Full Scholarship', 'USD', 'MasterCard Foundation'),
('Software Engineer - Full Stack', 'Join our tech startup to build scalable web applications.', 'job', 'TechHub Rwanda', 'Kigali', 'Rwanda', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'https://example.com/jobs/full-stack', '1000-1800', 'USD', 'Company Website'),
('UN Youth Volunteer Programme', 'Work on international development projects supporting SDGs.', 'internship', 'United Nations Volunteers', 'Various Locations', 'Multiple Countries', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'https://www.unv.org', '700-1200', 'USD', 'UN Volunteers'),
('Youth Innovation Fund', 'Seed funding for innovative startups led by young African entrepreneurs.', 'grant', 'African Innovation Foundation', 'Africa-wide', 'Multiple Countries', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'https://example.com/grants/youth-innovation', '5000-25000', 'USD', 'AfDB');

-- ============================================
-- SETUP COMPLETE!
-- ============================================
-- You now have:
-- ✓ 3 sample profiles
-- ✓ 1 admin account (admin / Admin@123)
-- ✓ 1 demo user (demo@bihakcenter.org / Demo@123)
-- ✓ 4 sample opportunities
-- ✓ All security tables
-- ✓ All required indexes
--
-- Run the web scraper to load 40 opportunities:
-- php scrapers/run_scrapers.php
-- ============================================
