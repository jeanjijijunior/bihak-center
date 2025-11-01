-- ========================================
-- FIX ADMIN PASSWORD
-- Run this to fix the admin login issue
-- ========================================

-- Delete any existing admin with username 'admin'
DELETE FROM admins WHERE username = 'admin';

-- Insert admin with CORRECT bcrypt hash for 'Admin@123'
-- Generated using PHP: password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 12])
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

-- Clear any existing sessions
DELETE FROM admin_sessions WHERE admin_id IN (SELECT id FROM admins WHERE username = 'admin');

-- ========================================
-- VERIFICATION QUERY
-- Run this to check if admin was created:
-- SELECT * FROM admins WHERE username = 'admin';
-- ========================================

-- ========================================
-- NOW TRY LOGGING IN:
-- URL: http://localhost/bihak-center/public/admin/login.php
-- Username: admin
-- Password: Admin@123
-- ========================================
