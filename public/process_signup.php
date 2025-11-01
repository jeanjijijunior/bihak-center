<?php
/**
 * Bihak Center - Process Signup Form
 * Handles profile registration with file uploads
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

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

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token. Please refresh the page and try again.');
    }

    // Sanitize and validate input
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
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
    $required = ['full_name', 'email', 'password', 'password_confirm', 'date_of_birth', 'city', 'district', 'education_level', 'title', 'short_description', 'full_story'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $response['errors'][] = "Field '{$field}' is required";
        }
    }

    // Validate email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = 'Invalid email address';
    }

    // Validate password
    if (strlen($data['password']) < 8) {
        $response['errors'][] = 'Password must be at least 8 characters long';
    }

    // Validate password match
    if ($data['password'] !== $data['password_confirm']) {
        $response['errors'][] = 'Passwords do not match';
    }

    // Check if email already exists
    $conn = getDatabaseConnection();
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param('s', $data['email']);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $response['errors'][] = 'An account with this email already exists';
    }
    $checkEmail->close();

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

    // Handle multiple image uploads
    $uploadDir = __DIR__ . '/../assets/uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedImages = [];
    $imageDescriptions = $_POST['image_descriptions'] ?? [];

    // Process multiple profile images
    if (isset($_FILES['profile_images']) && !empty($_FILES['profile_images']['name'][0])) {
        $fileCount = count($_FILES['profile_images']['name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB per image
        $maxImages = 5;

        if ($fileCount > $maxImages) {
            throw new Exception("Maximum {$maxImages} images allowed");
        }

        for ($i = 0; $i < $fileCount; $i++) {
            // Check if file was uploaded successfully
            if ($_FILES['profile_images']['error'][$i] !== UPLOAD_ERR_OK) {
                if ($_FILES['profile_images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    throw new Exception("Error uploading image " . ($i + 1));
                }
                continue;
            }

            $fileInfo = [
                'name' => $_FILES['profile_images']['name'][$i],
                'type' => $_FILES['profile_images']['type'][$i],
                'tmp_name' => $_FILES['profile_images']['tmp_name'][$i],
                'size' => $_FILES['profile_images']['size'][$i]
            ];

            // Validate file type
            if (!in_array($fileInfo['type'], $allowedTypes)) {
                throw new Exception('Image ' . ($i + 1) . ' must be JPG or PNG');
            }

            // Validate file size
            if ($fileInfo['size'] > $maxSize) {
                throw new Exception('Image ' . ($i + 1) . ' must be less than 5MB');
            }

            // Generate unique filename
            $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . uniqid() . '_' . time() . '_' . $i . '.' . $extension;
            $destination = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($fileInfo['tmp_name'], $destination)) {
                throw new Exception('Failed to upload image ' . ($i + 1));
            }

            // Store image info
            $uploadedImages[] = [
                'path' => '../assets/uploads/profiles/' . $filename,
                'filename' => $filename,
                'description' => isset($imageDescriptions[$i]) ? trim($imageDescriptions[$i]) : '',
                'display_order' => $i
            ];
        }

        if (empty($uploadedImages)) {
            throw new Exception('At least one profile image is required');
        }
    } else {
        throw new Exception('At least one profile image is required');
    }

    // Set primary profile image (first uploaded image)
    $profileImage = $uploadedImages[0]['path'];
    $mediaType = 'image';
    $mediaFile = null;

    // Insert into database
    if (!isset($conn)) {
        $conn = getDatabaseConnection();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create user account first
        $password_hash = Security::hashPassword($data['password']);
        $verification_token = bin2hex(random_bytes(32));

        $userSql = "INSERT INTO users (email, password_hash, full_name, phone, is_active, email_verified, verification_token, created_at)
                    VALUES (?, ?, ?, ?, TRUE, FALSE, ?, NOW())";

        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param('sssss',
            $data['email'],
            $password_hash,
            $data['full_name'],
            $data['phone'],
            $verification_token
        );

        if (!$userStmt->execute()) {
            throw new Exception('Failed to create user account: ' . $userStmt->error);
        }

        $userId = $userStmt->insert_id;
        $userStmt->close();

        // Now create profile linked to user
        $sql = "INSERT INTO profiles (
            user_id,
        full_name, email, phone, date_of_birth, gender,
        city, district, country,
        education_level, current_institution, field_of_study,
        title, short_description, full_story, goals, achievements,
        profile_image, media_type, media_url,
        facebook_url, twitter_url, instagram_url, linkedin_url,
        status, is_published
    ) VALUES (
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?,
        'pending', FALSE
    )";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'isssssssssssssssssssssss',
        $userId,
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

        // Link user to profile
        $updateUserStmt = $conn->prepare("UPDATE users SET profile_id = ? WHERE id = ?");
        $updateUserStmt->bind_param('ii', $profileId, $userId);
        $updateUserStmt->execute();
        $updateUserStmt->close();

        // Save all uploaded images to profile_media table
        if (!empty($uploadedImages)) {
            $mediaInsertSql = "INSERT INTO profile_media (profile_id, media_type, file_path, file_name, caption, display_order) VALUES (?, ?, ?, ?, ?, ?)";
            $mediaStmt = $conn->prepare($mediaInsertSql);

            foreach ($uploadedImages as $image) {
                $mediaType = 'image';
                $mediaStmt->bind_param(
                    'issssi',
                    $profileId,
                    $mediaType,
                    $image['path'],
                    $image['filename'],
                    $image['description'],
                    $image['display_order']
                );

                if (!$mediaStmt->execute()) {
                    throw new Exception('Failed to save image metadata: ' . $mediaStmt->error);
                }
            }

            $mediaStmt->close();
        }

        // Commit transaction
        $conn->commit();
        closeDatabaseConnection($conn);

        // Send success response
        $response['success'] = true;
        $response['message'] = 'Thank you! Your account has been created successfully. You can now login with your email and password. Your profile will be reviewed within 2-3 business days.';
        $response['profile_id'] = $profileId;
        $response['user_id'] = $userId;

        // Send notification email to admin (optional)
        // mail('admin@bihakcenter.org', 'New Profile Submission', 'A new profile has been submitted...');

    } catch (Exception $innerEx) {
        // Rollback transaction on error
        $conn->rollback();
        throw $innerEx;
    }

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        closeDatabaseConnection($conn);
    }
    $response['message'] = $e->getMessage();
    error_log('Signup Error: ' . $e->getMessage());
}

echo json_encode($response);
?>
