<?php
/**
 * Profile Edit Page
 * Admin can edit all profile content with rich text formatting and media management
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Require authentication
Auth::requireAuth();

$admin = Auth::user();
$conn = getDatabaseConnection();

// Get profile ID
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($profile_id <= 0) {
    header('Location: profiles.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $full_name = trim($_POST['full_name'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $full_story = trim($_POST['full_story'] ?? '');
        $goals = trim($_POST['goals'] ?? '');
        $achievements = trim($_POST['achievements'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $education_level = trim($_POST['education_level'] ?? '');
        $field_of_study = trim($_POST['field_of_study'] ?? '');
        $current_institution = trim($_POST['current_institution'] ?? '');
        $media_url = trim($_POST['media_url'] ?? '');
        $media_type = trim($_POST['media_type'] ?? 'image');

        // Social media links
        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $twitter_url = trim($_POST['twitter_url'] ?? '');
        $instagram_url = trim($_POST['instagram_url'] ?? '');
        $linkedin_url = trim($_POST['linkedin_url'] ?? '');

        // Validate required fields
        if (empty($full_name) || empty($title) || empty($short_description) || empty($full_story)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Update profile
            $stmt = $conn->prepare("
                UPDATE profiles SET
                    full_name = ?,
                    title = ?,
                    short_description = ?,
                    full_story = ?,
                    goals = ?,
                    achievements = ?,
                    city = ?,
                    district = ?,
                    education_level = ?,
                    field_of_study = ?,
                    current_institution = ?,
                    media_url = ?,
                    media_type = ?,
                    facebook_url = ?,
                    twitter_url = ?,
                    instagram_url = ?,
                    linkedin_url = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                'sssssssssssssssssi',
                $full_name,
                $title,
                $short_description,
                $full_story,
                $goals,
                $achievements,
                $city,
                $district,
                $education_level,
                $field_of_study,
                $current_institution,
                $media_url,
                $media_type,
                $facebook_url,
                $twitter_url,
                $instagram_url,
                $linkedin_url,
                $profile_id
            );

            if ($stmt->execute()) {
                Auth::logActivity($admin['id'], 'profile_edited', 'profile', $profile_id, "Edited profile ID {$profile_id}");
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Get profile details
$stmt = $conn->prepare("SELECT * FROM profiles WHERE id = ?");
$stmt->bind_param('i', $profile_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: profiles.php');
    exit;
}

$profile = $result->fetch_assoc();
$csrf_token = Security::generateCSRFToken();

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo htmlspecialchars($profile['full_name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <!-- Include TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.mce.com/1/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .edit-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .edit-header h1 {
            color: #1cabe2;
            font-size: 2rem;
            margin: 0;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateX(-3px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 35px;
            padding-bottom: 35px;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .form-section h2 {
            color: #1cabe2;
            font-size: 1.4rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h2::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group label .required {
            color: #ef4444;
        }

        .form-group .help-text {
            display: block;
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1cabe2;
            box-shadow: 0 0 0 3px rgba(28, 171, 226, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .media-preview {
            margin-top: 15px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 2px dashed #d1d5db;
        }

        .media-preview img,
        .media-preview video {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .media-preview-empty {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #e5e7eb;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(28, 171, 226, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .radio-option label {
            margin: 0;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .edit-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .edit-form {
                padding: 20px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <h1>Edit Profile</h1>
            <a href="profile-review.php?id=<?php echo $profile_id; ?>" class="back-btn">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                </svg>
                Back to Review
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="edit-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Basic Information -->
            <div class="form-section">
                <h2>Basic Information</h2>

                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           value="<?php echo htmlspecialchars($profile['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="title">Title/Professional Role <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?php echo htmlspecialchars($profile['title']); ?>" required>
                    <span class="help-text">E.g., Student Entrepreneur, Software Developer, Community Leader</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" class="form-control"
                               value="<?php echo htmlspecialchars($profile['city']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="district">District <span class="required">*</span></label>
                        <input type="text" id="district" name="district" class="form-control"
                               value="<?php echo htmlspecialchars($profile['district']); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Education -->
            <div class="form-section">
                <h2>Education & Career</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="education_level">Education Level</label>
                        <input type="text" id="education_level" name="education_level" class="form-control"
                               value="<?php echo htmlspecialchars($profile['education_level']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="field_of_study">Field of Study</label>
                        <input type="text" id="field_of_study" name="field_of_study" class="form-control"
                               value="<?php echo htmlspecialchars($profile['field_of_study']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="current_institution">Current Institution</label>
                    <input type="text" id="current_institution" name="current_institution" class="form-control"
                           value="<?php echo htmlspecialchars($profile['current_institution']); ?>">
                </div>
            </div>

            <!-- Story Content -->
            <div class="form-section">
                <h2>Story & Content</h2>

                <div class="form-group">
                    <label for="short_description">Short Description <span class="required">*</span></label>
                    <textarea id="short_description" name="short_description" class="form-control" rows="3" required><?php echo htmlspecialchars($profile['short_description']); ?></textarea>
                    <span class="help-text">A brief summary (150-200 characters) for profile cards</span>
                </div>

                <div class="form-group">
                    <label for="full_story">Full Story <span class="required">*</span></label>
                    <textarea id="full_story" name="full_story" class="form-control" required><?php echo htmlspecialchars($profile['full_story']); ?></textarea>
                    <span class="help-text">Tell the complete story with rich formatting, embed videos, images, etc.</span>
                </div>

                <div class="form-group">
                    <label for="goals">Goals & Aspirations</label>
                    <textarea id="goals" name="goals" class="form-control"><?php echo htmlspecialchars($profile['goals']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="achievements">Achievements & Recognition</label>
                    <textarea id="achievements" name="achievements" class="form-control"><?php echo htmlspecialchars($profile['achievements']); ?></textarea>
                </div>
            </div>

            <!-- Media -->
            <div class="form-section">
                <h2>Featured Media</h2>

                <div class="form-group">
                    <label>Media Type</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="media_image" name="media_type" value="image"
                                   <?php echo $profile['media_type'] === 'image' ? 'checked' : ''; ?>>
                            <label for="media_image">Image</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="media_video" name="media_type" value="video"
                                   <?php echo $profile['media_type'] === 'video' ? 'checked' : ''; ?>>
                            <label for="media_video">Video</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="media_url">Media URL</label>
                    <input type="url" id="media_url" name="media_url" class="form-control"
                           value="<?php echo htmlspecialchars($profile['media_url']); ?>">
                    <span class="help-text">Enter direct URL to image or video, or YouTube/Vimeo embed URL</span>
                </div>

                <?php if (!empty($profile['media_url'])): ?>
                    <div class="media-preview" id="mediaPreview">
                        <?php if ($profile['media_type'] === 'video'): ?>
                            <video src="<?php echo htmlspecialchars($profile['media_url']); ?>" controls></video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($profile['media_url']); ?>" alt="Profile media">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Social Media -->
            <div class="form-section">
                <h2>Social Media Links</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="facebook_url">Facebook URL</label>
                        <input type="url" id="facebook_url" name="facebook_url" class="form-control"
                               value="<?php echo htmlspecialchars($profile['facebook_url']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="twitter_url">Twitter/X URL</label>
                        <input type="url" id="twitter_url" name="twitter_url" class="form-control"
                               value="<?php echo htmlspecialchars($profile['twitter_url']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="instagram_url">Instagram URL</label>
                        <input type="url" id="instagram_url" name="instagram_url" class="form-control"
                               value="<?php echo htmlspecialchars($profile['instagram_url']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="linkedin_url">LinkedIn URL</label>
                        <input type="url" id="linkedin_url" name="linkedin_url" class="form-control"
                               value="<?php echo htmlspecialchars($profile['linkedin_url']); ?>">
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <a href="profile-review.php?id=<?php echo $profile_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
        // Initialize TinyMCE for rich text editing
        tinymce.init({
            selector: '#full_story, #goals, #achievements',
            height: 400,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | image media link | code preview fullscreen | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size:16px; line-height:1.6 }',
            image_advtab: true,
            media_live_embeds: true,
            automatic_uploads: false,
            file_picker_types: 'image media',
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
        });

        // Media preview updater
        const mediaUrl = document.getElementById('media_url');
        const mediaTypeInputs = document.querySelectorAll('input[name="media_type"]');

        function updateMediaPreview() {
            const url = mediaUrl.value;
            const mediaType = document.querySelector('input[name="media_type"]:checked').value;
            const preview = document.getElementById('mediaPreview');

            if (!url) {
                if (preview) preview.remove();
                return;
            }

            if (!preview) {
                const newPreview = document.createElement('div');
                newPreview.className = 'media-preview';
                newPreview.id = 'mediaPreview';
                mediaUrl.parentElement.appendChild(newPreview);
            }

            const previewElement = document.getElementById('mediaPreview');

            if (mediaType === 'video') {
                previewElement.innerHTML = `<video src="${url}" controls></video>`;
            } else {
                previewElement.innerHTML = `<img src="${url}" alt="Profile media">`;
            }
        }

        mediaUrl.addEventListener('change', updateMediaPreview);
        mediaTypeInputs.forEach(input => {
            input.addEventListener('change', updateMediaPreview);
        });
    </script>
</body>
</html>
