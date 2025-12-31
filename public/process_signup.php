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
        throw new Exception('Invalid request method. Please submit the form properly.');
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token'])) {
        throw new Exception('Security token is missing. Please refresh the page and try again.');
    }

    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid or expired security token. Please refresh the page and try again.');
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

    // Get security questions and answers
    $security_questions = [
        ['question_id' => intval($_POST['security_question_1'] ?? 0), 'answer' => trim($_POST['security_answer_1'] ?? '')],
        ['question_id' => intval($_POST['security_question_2'] ?? 0), 'answer' => trim($_POST['security_answer_2'] ?? '')],
        ['question_id' => intval($_POST['security_question_3'] ?? 0), 'answer' => trim($_POST['security_answer_3'] ?? '')]
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

    // Validate security questions
    foreach ($security_questions as $index => $sq) {
        $questionNum = $index + 1;
        if (empty($sq['question_id'])) {
            $response['errors'][] = "Security question {$questionNum} is required";
        }
        if (empty($sq['answer'])) {
            $response['errors'][] = "Answer for security question {$questionNum} is required";
        }
    }

    // Check if all security questions are different
    $question_ids = array_column($security_questions, 'question_id');
    if (count($question_ids) !== count(array_unique($question_ids))) {
        $response['errors'][] = 'All security questions must be different';
    }

    // Check if email already exists
    try {
        $conn = getDatabaseConnection();
    } catch (Exception $dbError) {
        throw new Exception('Database connection failed: ' . $dbError->getMessage());
    }

    if (!$conn) {
        throw new Exception('Could not connect to database. Please try again later.');
    }

    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$checkEmail) {
        throw new Exception('Database query error: ' . $conn->error);
    }

    $checkEmail->bind_param('s', $data['email']);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $response['errors'][] = 'An account with this email already exists. Please use a different email or try logging in.';
    }
    $checkEmail->close();

    // Validate title length
    if (strlen($data['title']) > 200) {
        $response['errors'][] = 'Title must be 200 characters or less';
    }

    // Full story is required but no minimum word count
    // Users can share their story in their own words without restrictions

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
        $maxSize = 2 * 1024 * 1024; // 2MB per image (matches server upload_max_filesize)
        $maxImages = 3; // Reduced to 3 to stay within 8MB post_max_size limit

        if ($fileCount > $maxImages) {
            throw new Exception("Maximum {$maxImages} images allowed");
        }

        for ($i = 0; $i < $fileCount; $i++) {
            // Check if file was uploaded successfully
            if ($_FILES['profile_images']['error'][$i] !== UPLOAD_ERR_OK) {
                if ($_FILES['profile_images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    // Provide user-friendly error messages based on PHP upload error codes
                    $errorCode = $_FILES['profile_images']['error'][$i];
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'Image ' . ($i + 1) . ' is too large. Maximum file size allowed by server is 2MB.',
                        UPLOAD_ERR_FORM_SIZE => 'Image ' . ($i + 1) . ' exceeds the maximum allowed size (2MB).',
                        UPLOAD_ERR_PARTIAL => 'Image ' . ($i + 1) . ' was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server error: Temporary upload folder is missing. Please contact support.',
                        UPLOAD_ERR_CANT_WRITE => 'Server error: Failed to save image ' . ($i + 1) . ' to disk. Please contact support.',
                        UPLOAD_ERR_EXTENSION => 'Server error: A PHP extension blocked the upload of image ' . ($i + 1) . '. Please contact support.'
                    ];

                    $errorMsg = isset($errorMessages[$errorCode])
                        ? $errorMessages[$errorCode]
                        : 'Unknown error uploading image ' . ($i + 1) . '. Error code: ' . $errorCode;

                    throw new Exception($errorMsg);
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
                throw new Exception('Image ' . ($i + 1) . ' must be less than 2MB');
            }

            // Generate unique filename
            $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . uniqid() . '_' . time() . '_' . $i . '.' . $extension;
            $destination = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($fileInfo['tmp_name'], $destination)) {
                // Provide detailed error message about why the move failed
                $error_msg = 'Failed to save image ' . ($i + 1) . '. ';

                // Check common issues
                if (!is_writable(dirname($destination))) {
                    $error_msg .= 'Upload folder does not have write permissions. Please contact support.';
                } elseif (!file_exists($fileInfo['tmp_name'])) {
                    $error_msg .= 'Temporary file was not found. This may be due to a server timeout. Please try uploading a smaller image.';
                } elseif (disk_free_space(dirname($destination)) < $fileInfo['size']) {
                    $error_msg .= 'Server disk is full. Please contact support.';
                } else {
                    $error_msg .= 'The file could not be saved to the server. Please try again or contact support if the problem persists.';
                }

                throw new Exception($error_msg);
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

        $userSql = "INSERT INTO users (email, password, full_name, is_active, email_verified, verification_token)
                    VALUES (?, ?, ?, 1, 0, ?)";

        $userStmt = $conn->prepare($userSql);
        if (!$userStmt) {
            throw new Exception('Failed to prepare user insert statement: ' . $conn->error);
        }

        $userStmt->bind_param('ssss',
            $data['email'],
            $password_hash,
            $data['full_name'],
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
    if (!$stmt) {
        throw new Exception('Failed to prepare profile insert statement: ' . $conn->error);
    }

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

        // Save security question answers
        $securitySql = "INSERT INTO user_security_answers (user_id, question_id, answer_hash) VALUES (?, ?, ?)";
        $securityStmt = $conn->prepare($securitySql);

        if (!$securityStmt) {
            throw new Exception('Failed to prepare security questions statement: ' . $conn->error);
        }

        foreach ($security_questions as $sq) {
            // Hash the answer (case-insensitive)
            $answerHash = password_hash(strtolower($sq['answer']), PASSWORD_BCRYPT);

            $securityStmt->bind_param('iis', $userId, $sq['question_id'], $answerHash);

            if (!$securityStmt->execute()) {
                throw new Exception('Failed to save security answer: ' . $securityStmt->error);
            }
        }

        $securityStmt->close();

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
    // Capture database error BEFORE closing connection
    $dbError = '';
    if (isset($conn) && $conn->error) {
        $dbError = $conn->error;
    }

    // Now close connection
    if (isset($conn)) {
        $conn->rollback();
        closeDatabaseConnection($conn);
    }

    // Get detailed error information
    $errorMessage = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();

    // Log the full error
    error_log("Signup Error: $errorMessage in $errorFile on line $errorLine");

    // Return user-friendly error with details
    $response['message'] = $errorMessage;
    $response['error_details'] = [
        'type' => get_class($e),
        'file' => basename($errorFile),
        'line' => basename($errorFile) . ':' . $errorLine
    ];

    // If there was a database error, provide more context
    if (!empty($dbError)) {
        $response['database_error'] = $dbError;
        $response['message'] .= ' (Database: ' . $dbError . ')';
    }
}

// Always return JSON
echo json_encode($response);
?>
