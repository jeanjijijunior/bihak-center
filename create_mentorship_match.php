<?php
/**
 * Create Test Mentorship Match
 * Creates mentorship relationships for testing
 */

require_once __DIR__ . '/config/database.php';

echo "=== Creating Test Mentorship Matches ===\n\n";

try {
    $conn = getDatabaseConnection();

    // Get our test accounts
    $test_user = $conn->query("SELECT id, email, full_name FROM users WHERE email = 'testuser@bihakcenter.org'")->fetch_assoc();
    $test_mentor = $conn->query("SELECT id, email, full_name FROM sponsors WHERE email = 'mentor@bihakcenter.org'")->fetch_assoc();
    $sarah_user = $conn->query("SELECT id, email, full_name FROM users WHERE email = 'sarah.uwase@demo.rw'")->fetch_assoc();
    $jean_mentor = $conn->query("SELECT id, email, full_name FROM sponsors WHERE email = 'jijiniyo@gmail.com'")->fetch_assoc();

    if (!$test_user || !$test_mentor) {
        echo "ERROR: Test accounts not found. Run setup_test_accounts.php first.\n";
        exit(1);
    }

    echo "Found Test Accounts:\n";
    echo "  User: {$test_user['full_name']} (ID: {$test_user['id']})\n";
    echo "  Mentor: {$test_mentor['full_name']} (ID: {$test_mentor['id']})\n";
    if ($sarah_user) {
        echo "  User: {$sarah_user['full_name']} (ID: {$sarah_user['id']})\n";
    }
    if ($jean_mentor) {
        echo "  Mentor: {$jean_mentor['full_name']} (ID: {$jean_mentor['id']})\n";
    }
    echo "\n";

    // ========================================
    // 1. Active Mentorship (Test User + John Mentor)
    // ========================================
    echo "1. Creating ACTIVE mentorship relationship...\n";

    // Check if relationship already exists
    $check = $conn->query("
        SELECT id, status FROM mentorship_relationships
        WHERE mentor_id = {$test_mentor['id']} AND mentee_id = {$test_user['id']}
    ");

    if ($check->num_rows > 0) {
        $existing = $check->fetch_assoc();
        // Update to active if exists
        $conn->query("
            UPDATE mentorship_relationships
            SET status = 'active',
                accepted_at = NOW(),
                updated_at = NOW()
            WHERE id = {$existing['id']}
        ");
        echo "   ✓ Updated existing relationship to ACTIVE (ID: {$existing['id']})\n";
    } else {
        // Create new active relationship
        $conn->query("
            INSERT INTO mentorship_relationships (
                mentor_id, mentee_id, status, requested_by,
                requested_at, accepted_at, match_score
            ) VALUES (
                {$test_mentor['id']},
                {$test_user['id']},
                'active',
                'mentor',
                NOW(),
                NOW(),
                85.50
            )
        ");
        echo "   ✓ Created new ACTIVE relationship (ID: {$conn->insert_id})\n";
    }

    // ========================================
    // 2. Pending Mentorship (Sarah + Jean Mentor)
    // ========================================
    if ($sarah_user && $jean_mentor) {
        echo "\n2. Creating PENDING mentorship request...\n";

        $check2 = $conn->query("
            SELECT id, status FROM mentorship_relationships
            WHERE mentor_id = {$jean_mentor['id']} AND mentee_id = {$sarah_user['id']}
        ");

        if ($check2->num_rows > 0) {
            $existing2 = $check2->fetch_assoc();
            $conn->query("
                UPDATE mentorship_relationships
                SET status = 'pending',
                    requested_by = 'mentee',
                    requested_at = NOW(),
                    accepted_at = NULL,
                    updated_at = NOW()
                WHERE id = {$existing2['id']}
            ");
            echo "   ✓ Updated existing relationship to PENDING (ID: {$existing2['id']})\n";
        } else {
            $conn->query("
                INSERT INTO mentorship_relationships (
                    mentor_id, mentee_id, status, requested_by,
                    requested_at, match_score
                ) VALUES (
                    {$jean_mentor['id']},
                    {$sarah_user['id']},
                    'pending',
                    'mentee',
                    NOW(),
                    78.30
                )
            ");
            echo "   ✓ Created new PENDING request (ID: {$conn->insert_id})\n";
        }
    }

    closeDatabaseConnection($conn);

    // ========================================
    // SUMMARY
    // ========================================
    echo "\n";
    echo "==============================================\n";
    echo "✓ Mentorship Matches Created!\n";
    echo "==============================================\n\n";

    echo "ACTIVE MENTORSHIP:\n";
    echo "  Mentor: {$test_mentor['full_name']} (mentor@bihakcenter.org)\n";
    echo "  Mentee: {$test_user['full_name']} (testuser@bihakcenter.org)\n";
    echo "  Status: ACTIVE\n";
    echo "  Match Score: 85.50%\n\n";

    if ($sarah_user && $jean_mentor) {
        echo "PENDING REQUEST:\n";
        echo "  Mentor: {$jean_mentor['full_name']} (jijiniyo@gmail.com)\n";
        echo "  Mentee: {$sarah_user['full_name']} (sarah.uwase@demo.rw)\n";
        echo "  Status: PENDING (Requested by mentee)\n";
        echo "  Match Score: 78.30%\n\n";
    }

    echo "==============================================\n";
    echo "TESTING WORKFLOW:\n";
    echo "==============================================\n\n";

    echo "A. Test as MENTEE (User with Active Mentor):\n";
    echo "   1. Login: testuser@bihakcenter.org / Test@123\n";
    echo "   2. Go to My Account or Mentorship section\n";
    echo "   3. Should see active mentorship with John Mentor\n";
    echo "   4. Access workspace/messaging with mentor\n\n";

    echo "B. Test as MENTOR (Viewing Mentee):\n";
    echo "   1. Login: mentor@bihakcenter.org / Test@123\n";
    echo "   2. Go to Mentorship Dashboard\n";
    echo "   3. Should see Test User as active mentee\n";
    echo "   4. Access workspace/messaging with mentee\n";
    echo "   5. View mentee profile/progress\n\n";

    echo "C. Test Profile Integration:\n";
    echo "   1. Login as mentor@bihakcenter.org\n";
    echo "   2. Visit user profiles in Stories section\n";
    echo "   3. Should see 'Offer Mentorship' button for unmatched users\n";
    echo "   4. Should see 'Active Mentorship' status for Test User\n";
    echo "   5. Click 'Open Workspace' to view mentorship details\n\n";

    echo "D. Test Pending Requests:\n";
    if ($sarah_user && $jean_mentor) {
        echo "   1. Login as jijiniyo@gmail.com / Test@123\n";
        echo "   2. Go to Mentorship Requests\n";
        echo "   3. Should see pending request from Sarah Uwase\n";
        echo "   4. Accept or reject the request\n\n";
    }

    echo "E. Test Mentorship Features:\n";
    echo "   - Workspace access\n";
    echo "   - Messaging between mentor/mentee\n";
    echo "   - Goal setting and tracking\n";
    echo "   - Progress reviews\n";
    echo "   - Session scheduling\n\n";

    echo "==============================================\n\n";

    echo "Database Records Created:\n";
    echo "  - 1 ACTIVE mentorship (ready to use)\n";
    if ($sarah_user && $jean_mentor) {
        echo "  - 1 PENDING request (for testing approval flow)\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
