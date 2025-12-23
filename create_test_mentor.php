<?php
/**
 * Create Test Mentor Account
 * Run this once to create a test mentor for development
 */

require_once __DIR__ . '/config/database.php';

$conn = getDatabaseConnection();

// Mentor credentials
$email = 'mentor@bihakcenter.org';
$password = 'Mentor@123';
$full_name = 'John Mentor';
$organization = 'Tech Innovations Rwanda';
$job_title = 'Senior Software Engineer';
$expertise_area = 'Technology, Business, Leadership';
$bio = 'Experienced software engineer with 10+ years in the tech industry. Passionate about mentoring young entrepreneurs and helping them build successful startups.';
$phone = '+250 788 123 456';
$role_type = 'mentor';

// Hash password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Check if mentor already exists
$stmt = $conn->prepare("SELECT id FROM sponsors WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->fetch_assoc()) {
    echo "✅ Mentor already exists!\n\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    exit;
}

// Insert mentor (sponsors table doesn't have password - they login through a different system)
// Let me check if they need a user account or admin account for login
$stmt = $conn->prepare("
    INSERT INTO sponsors
    (full_name, email, phone, organization, role_type, expertise_domain, message, status, is_active, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', 1, NOW())
");

$stmt->bind_param('sssssss',
    $full_name,
    $email,
    $phone,
    $organization,
    $role_type,
    $expertise_area,
    $bio
);

if ($stmt->execute()) {
    $mentor_id = $conn->insert_id;

    // Add mentor preferences
    $sectors = json_encode(['Technology', 'Business', 'Education']);
    $skills = json_encode(['Programming', 'Leadership', 'Business Strategy', 'Public Speaking']);
    $languages = json_encode(['English', 'French', 'Kinyarwanda']);
    $max_mentees = 5;
    $availability_hours = 10;

    $stmt = $conn->prepare("
        INSERT INTO mentor_preferences
        (mentor_id, preferred_sectors, preferred_skills, max_mentees, availability_hours, preferred_languages)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param('ississ',
        $mentor_id,
        $sectors,
        $skills,
        $max_mentees,
        $availability_hours,
        $languages
    );

    $stmt->execute();

    echo "✅ Test mentor account created successfully!\n\n";
    echo "Login URL: http://localhost/bihak-center/public/login.php\n\n";
    echo "Credentials:\n";
    echo "Email: $email\n";
    echo "Password: $password\n\n";
    echo "After login, you can access:\n";
    echo "- Mentor Dashboard: http://localhost/bihak-center/public/mentorship/dashboard.php\n";
    echo "- Preferences: http://localhost/bihak-center/public/mentorship/preferences.php\n";
    echo "- Browse Mentees: http://localhost/bihak-center/public/mentorship/browse-mentees.php\n";
    echo "- Requests: http://localhost/bihak-center/public/mentorship/requests.php\n\n";

    echo "Mentor Details:\n";
    echo "- ID: $mentor_id\n";
    echo "- Name: $full_name\n";
    echo "- Organization: $organization\n";
    echo "- Expertise: $expertise_area\n";
    echo "- Max Mentees: $max_mentees\n";

} else {
    echo "❌ Error creating mentor: " . $conn->error . "\n";
}

closeDatabaseConnection($conn);
?>
