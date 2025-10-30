<?php
/**
 * Bihak Center - Process Signup Form
 * Handles profile registration with file uploads
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Set response header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Sanitize and validate input
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'gender' => $_POST['gender'] ?? 'Prefer not to say',
        'city' => trim($_POST['city'] ?? ''),
        'district' => trim($_POST['district'] ?? ''),
        'country' => trim($_POST['country'] ?? 'Rwanda'),
        'education_level' => $_POST['education_level'] ?? '',
        'current_institution' => trim($_POST['current_institution'] ?? ''),
        'field_of_study' => trim($_POST['field_of_study'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'full_story' => trim($_POST['full_story'] ?? ''),
        'goals' => trim($_POST['goals'] ?? ''),
        'achievements' => trim($_POST['achievements'] ?? ''),
        'facebook_url' => trim($_POST['facebook_url'] ?? ''),
        'twitter_url' => trim($_POST['twitter_url'] ?? ''),
        'instagram_url' => trim($_POST['instagram_url'] ?? ''),
        'linkedin_url' => trim($_POST['linkedin_url'] ?? '')
    ];

    // Validate required fields
    $required = ['full_name', 'email', 'date_of_birth', 'city', 'district', 'education_level', 'title', 'short_description', 'full_story'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $response['errors'][] = "Field '{$field}' is required";
        }
    }

    // Validate email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = 'Invalid email address';
    }

    // Validate title length
    if (strlen($data['title']) > 200) {
        $response['errors'][] = 'Title must be 200 characters or less';
    }

    // Validate full story length
    if (str_word_count($data['full_story']) < 50) {
        $response['errors'][] = 'Full story must be at least 50 words';
    }

    // Check if errors occurred
    if (!empty($response['errors'])) {
        $response['message'] = 'Please fix the following errors:';
        echo json_encode($response);
        exit;
    }

    // Handle file uploads
    $uploadDir = __DIR__ . '/../assets/uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $profileImage = null;
    $mediaFile = null;
    $mediaType = 'image';

    // Process profile image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = $_FILES['profile_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($fileInfo['type'], $allowedTypes)) {
            throw new Exception('Profile image must be JPG or PNG');
        }

        if ($fileInfo['size'] > $maxSize) {
            throw new Exception('Profile image must be less than 5MB');
        }

        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . uniqid() . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($fileInfo['tmp_name'], $destination)) {
            throw new Exception('Failed to upload profile image');
        }

        $profileImage = '../assets/uploads/profiles/' . $filename;
    } else {
        throw new Exception('Profile image is required');
    }

    // Process additional media (optional)
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = $_FILES['media_file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'video/mp4', 'video/webm'];
        $maxSize = 20 * 1024 * 1024; // 20MB

        if (!in_array($fileInfo['type'], $allowedTypes)) {
            throw new Exception('Additional media must be JPG, PNG, MP4, or WebM');
        }

        if ($fileInfo['size'] > $maxSize) {
            throw new Exception('Additional media must be less than 20MB');
        }

        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $filename = 'media_' . uniqid() . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($fileInfo['tmp_name'], $destination)) {
            throw new Exception('Failed to upload additional media');
        }

        $mediaFile = '../assets/uploads/profiles/' . $filename;
        $mediaType = strpos($fileInfo['type'], 'video') !== false ? 'video' : 'image';
    }

    // Insert into database
    $conn = getDatabaseConnection();

    $sql = "INSERT INTO profiles (
        full_name, email, phone, date_of_birth, gender,
        city, district, country,
        education_level, current_institution, field_of_study,
        title, short_description, full_story, goals, achievements,
        profile_image, media_type, media_url,
        facebook_url, twitter_url, instagram_url, linkedin_url,
        status, is_published
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?,
        'pending', FALSE
    )";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssssssssssssssssssss',
        $data['full_name'],
        $data['email'],
        $data['phone'],
        $data['date_of_birth'],
        $data['gender'],
        $data['city'],
        $data['district'],
        $data['country'],
        $data['education_level'],
        $data['current_institution'],
        $data['field_of_study'],
        $data['title'],
        $data['short_description'],
        $data['full_story'],
        $data['goals'],
        $data['achievements'],
        $profileImage,
        $mediaType,
        $mediaFile,
        $data['facebook_url'],
        $data['twitter_url'],
        $data['instagram_url'],
        $data['linkedin_url']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to save profile: ' . $stmt->error);
    }

    $profileId = $stmt->insert_id;
    $stmt->close();
    closeDatabaseConnection($conn);

    // Send success response
    $response['success'] = true;
    $response['message'] = 'Thank you! Your story has been submitted successfully. Our team will review it within 2-3 business days and notify you via email.';
    $response['profile_id'] = $profileId;

    // Send notification email to admin (optional)
    // mail('admin@bihakcenter.org', 'New Profile Submission', 'A new profile has been submitted...');

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Signup Error: ' . $e->getMessage());
}

echo json_encode($response);
?>
