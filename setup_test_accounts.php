<?php
/**
 * Setup Real Test Accounts for Demo/Testing
 * Creates or updates test accounts with known passwords
 */

require_once __DIR__ . '/config/database.php';

// Test password for all accounts: Test@123
$test_password = 'Test@123';
$password_hash = password_hash($test_password, PASSWORD_DEFAULT);

echo "=== Setting Up Test Accounts ===\n\n";
echo "Test Password for all accounts: Test@123\n\n";

try {
    $conn = getDatabaseConnection();

    // ========================================
    // 1. TEST USER (Regular User Account)
    // ========================================
    echo "1. Creating/Updating TEST USER...\n";

    $user_email = 'testuser@bihakcenter.org';
    $user_check = $conn->query("SELECT id FROM users WHERE email = '$user_email'");

    if ($user_check->num_rows > 0) {
        // Update existing user
        $stmt = $conn->prepare("UPDATE users SET password = ?, is_active = 1 WHERE email = ?");
        $stmt->bind_param('ss', $password_hash, $user_email);
        $stmt->execute();
        echo "   ✓ Updated existing user: $user_email\n";
    } else {
        // Create new user
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, full_name, is_active, created_at)
            VALUES (?, ?, 'Test User', 1, NOW())
        ");
        $stmt->bind_param('ss', $user_email, $password_hash);
        $stmt->execute();
        echo "   ✓ Created new user: $user_email\n";
    }

    // ========================================
    // 2. TEST MENTOR (Sponsor/Mentor Account)
    // ========================================
    echo "\n2. Creating/Updating TEST MENTOR...\n";

    $mentor_email = 'mentor@bihakcenter.org';
    $mentor_check = $conn->query("SELECT id FROM sponsors WHERE email = '$mentor_email'");

    if ($mentor_check->num_rows > 0) {
        // Update existing mentor
        $stmt = $conn->prepare("
            UPDATE sponsors
            SET password_hash = ?,
                status = 'approved',
                is_active = 1,
                role_type = 'mentor'
            WHERE email = ?
        ");
        $stmt->bind_param('ss', $password_hash, $mentor_email);
        $stmt->execute();
        echo "   ✓ Updated existing mentor: $mentor_email\n";
    } else {
        // Create new mentor
        $stmt = $conn->prepare("
            INSERT INTO sponsors (
                email, password_hash, full_name, organization_name,
                role_type, status, is_active, created_at
            ) VALUES (?, ?, 'John Mentor', 'Bihak Center', 'mentor', 'approved', 1, NOW())
        ");
        $stmt->bind_param('ss', $mentor_email, $password_hash);
        $stmt->execute();
        echo "   ✓ Created new mentor: $mentor_email\n";
    }

    // ========================================
    // 3. TEST ADMIN (Admin Account)
    // ========================================
    echo "\n3. Creating/Updating TEST ADMIN...\n";

    $admin_username = 'testadmin';
    $admin_check = $conn->query("SELECT id FROM admins WHERE username = '$admin_username'");

    if ($admin_check->num_rows > 0) {
        // Update existing admin
        $stmt = $conn->prepare("UPDATE admins SET password_hash = ?, is_active = 1 WHERE username = ?");
        $stmt->bind_param('ss', $password_hash, $admin_username);
        $stmt->execute();
        echo "   ✓ Updated existing admin: $admin_username\n";
    } else {
        // Create new admin
        $stmt = $conn->prepare("
            INSERT INTO admins (username, password_hash, email, is_active, created_at)
            VALUES (?, ?, 'testadmin@bihakcenter.org', 1, NOW())
        ");
        $stmt->bind_param('ss', $admin_username, $password_hash);
        $stmt->execute();
        echo "   ✓ Created new admin: $admin_username\n";
    }

    // ========================================
    // 4. UPDATE EXISTING ACCOUNTS
    // ========================================
    echo "\n4. Updating Existing Accounts...\n";

    // Update existing mentor account from database
    if ($mentor_check->num_rows > 0) {
        $row = $mentor_check->fetch_assoc();
        $mentor_id = $row['id'];
        echo "   ✓ Mentor account ready (ID: $mentor_id)\n";
    }

    // Update Sarah Uwase (existing user)
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'sarah.uwase@demo.rw'");
    $stmt->bind_param('s', $password_hash);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo "   ✓ Updated sarah.uwase@demo.rw\n";
    }

    // Update Jean Jiji (existing mentor)
    $stmt = $conn->prepare("UPDATE sponsors SET password_hash = ?, status = 'approved' WHERE email = 'jijiniyo@gmail.com'");
    $stmt->bind_param('s', $password_hash);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo "   ✓ Updated jijiniyo@gmail.com (mentor)\n";
    }

    closeDatabaseConnection($conn);

    // ========================================
    // SUMMARY
    // ========================================
    echo "\n";
    echo "==============================================\n";
    echo "✓ Test Accounts Setup Complete!\n";
    echo "==============================================\n\n";

    echo "TEST CREDENTIALS (Password: Test@123):\n\n";

    echo "1. REGULAR USER:\n";
    echo "   URL: http://localhost/bihak-center/public/login.php\n";
    echo "   Email: testuser@bihakcenter.org\n";
    echo "   Password: Test@123\n";
    echo "   (Also: sarah.uwase@demo.rw / Test@123)\n\n";

    echo "2. MENTOR/SPONSOR:\n";
    echo "   URL: http://localhost/bihak-center/public/login.php\n";
    echo "   Email: mentor@bihakcenter.org\n";
    echo "   Password: Test@123\n";
    echo "   (Also: jijiniyo@gmail.com / Test@123)\n\n";

    echo "3. ADMIN:\n";
    echo "   URL: http://localhost/bihak-center/public/admin/login.php\n";
    echo "   Username: testadmin\n";
    echo "   Password: Test@123\n";
    echo "   (Also: admin / Test@123)\n\n";

    echo "==============================================\n";
    echo "TESTING WORKFLOW:\n";
    echo "==============================================\n\n";

    echo "A. Test User Session:\n";
    echo "   1. Login as testuser@bihakcenter.org\n";
    echo "   2. Check header shows user menu with 'Test User'\n";
    echo "   3. Click dropdown - should see 'My Account', 'Profile', 'Logout'\n";
    echo "   4. Navigate to different pages - header should persist\n";
    echo "   5. Test logout - should redirect to login\n\n";

    echo "B. Test Mentor Session:\n";
    echo "   1. Login as mentor@bihakcenter.org\n";
    echo "   2. Check header shows user menu with 'John Mentor'\n";
    echo "   3. Click dropdown - should see 'Mentorship Dashboard', 'Preferences', 'Logout'\n";
    echo "   4. Go to mentorship/dashboard.php - check navbar links work\n";
    echo "   5. Test all navbar links (Home, About, Stories, etc.)\n";
    echo "   6. Test logout - should redirect to login\n\n";

    echo "C. Test Admin Session:\n";
    echo "   1. Login as testadmin\n";
    echo "   2. Check header shows admin menu\n";
    echo "   3. Click dropdown - should see 'Admin Dashboard', 'Incubation Admin', 'Logout'\n";
    echo "   4. Navigate between admin pages\n";
    echo "   5. Test logout - should redirect to admin/login.php\n\n";

    echo "D. Test Language Switcher & Dropdown:\n";
    echo "   1. Login as any user type\n";
    echo "   2. Click EN/FR buttons - should switch language\n";
    echo "   3. Click user menu - dropdown should open\n";
    echo "   4. Click outside - dropdown should close\n";
    echo "   5. Test on mobile size - mobile menu should work\n\n";

    echo "==============================================\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
