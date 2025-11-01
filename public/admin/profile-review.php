<?php
/**
 * Profile Review Page
 * Admin can view full profile details and approve/reject submissions
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

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if ($action === 'approve') {
            $stmt = $conn->prepare("
                UPDATE profiles
                SET status = 'approved', is_published = TRUE, approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $admin['id'], $profile_id);

            if ($stmt->execute()) {
                Auth::logActivity($admin['id'], 'profile_approved', 'profile', $profile_id, "Approved profile ID {$profile_id}");
                $success = 'Profile approved successfully!';

                // TODO: Send email notification to user
            } else {
                $error = 'Failed to approve profile. Please try again.';
            }

        } elseif ($action === 'reject') {
            if (empty($reason)) {
                $error = 'Please provide a reason for rejection.';
            } else {
                $stmt = $conn->prepare("
                    UPDATE profiles
                    SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('sii', $reason, $admin['id'], $profile_id);

                if ($stmt->execute()) {
                    Auth::logActivity($admin['id'], 'profile_rejected', 'profile', $profile_id, "Rejected profile ID {$profile_id}: {$reason}");
                    $success = 'Profile rejected. User will be notified.';

                    // TODO: Send email notification to user with reason
                } else {
                    $error = 'Failed to reject profile. Please try again.';
                }
            }

        } elseif ($action === 'publish') {
            $stmt = $conn->prepare("UPDATE profiles SET is_published = TRUE WHERE id = ?");
            $stmt->bind_param('i', $profile_id);

            if ($stmt->execute()) {
                Auth::logActivity($admin['id'], 'profile_published', 'profile', $profile_id, "Published profile ID {$profile_id}");
                $success = 'Profile published successfully!';
            } else {
                $error = 'Failed to publish profile.';
            }

        } elseif ($action === 'unpublish') {
            $stmt = $conn->prepare("UPDATE profiles SET is_published = FALSE WHERE id = ?");
            $stmt->bind_param('i', $profile_id);

            if ($stmt->execute()) {
                Auth::logActivity($admin['id'], 'profile_unpublished', 'profile', $profile_id, "Unpublished profile ID {$profile_id}");
                $success = 'Profile unpublished successfully!';
            } else {
                $error = 'Failed to unpublish profile.';
            }
        }
    }
}

// Get profile details
$stmt = $conn->prepare("
    SELECT *
    FROM profiles
    WHERE id = ?
");
$stmt->bind_param('i', $profile_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    closeDatabaseConnection($conn);
    header('Location: profiles.php');
    exit;
}

$profile = $result->fetch_assoc();

// Get additional media
$media_query = "
    SELECT * FROM profile_media
    WHERE profile_id = ?
    ORDER BY uploaded_at ASC
";
$stmt = $conn->prepare($media_query);
$stmt->bind_param('i', $profile_id);
$stmt->execute();
$media = $stmt->get_result();

closeDatabaseConnection($conn);

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Profile - <?php echo htmlspecialchars($profile['full_name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="../../assets/images/logob.png">
</head>
<body>
    <!-- Admin Header -->
    <?php include 'includes/admin-header.php'; ?>

    <!-- Admin Sidebar -->
    <?php include 'includes/admin-sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="dashboard-container">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <a href="profiles.php" class="back-link">← Back to Profiles</a>
                    <h1>Review Profile</h1>
                    <p><?php echo htmlspecialchars($profile['full_name']); ?></p>
                </div>
                <div class="header-actions">
                    <span class="badge badge-<?php echo $profile['status']; ?> badge-lg">
                        <?php echo ucfirst($profile['status']); ?>
                    </span>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Review Content -->
            <div class="profile-review-grid">
                <!-- Main Profile Content -->
                <div class="profile-review-main">
                    <!-- Profile Image -->
                    <div class="review-section">
                        <h2>Profile Image</h2>
                        <div class="profile-image-preview">
                            <?php if ($profile['profile_image']): ?>
                                <img src="../../<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="<?php echo htmlspecialchars($profile['full_name']); ?>">
                            <?php else: ?>
                                <div class="no-image">No image uploaded</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="review-section">
                        <h2>Personal Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Full Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['full_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date of Birth</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['date_of_birth'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Gender</span>
                                <span class="info-value"><?php echo htmlspecialchars(ucfirst($profile['gender'] ?? 'N/A')); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="review-section">
                        <h2>Location</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Province</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['province'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">District</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['district'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Sector</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['sector'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Story -->
                    <div class="review-section">
                        <h2>Title</h2>
                        <p class="profile-title"><?php echo htmlspecialchars($profile['title']); ?></p>
                    </div>

                    <div class="review-section">
                        <h2>Short Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($profile['short_description'])); ?></p>
                    </div>

                    <div class="review-section">
                        <h2>Full Story</h2>
                        <div class="full-story">
                            <?php echo nl2br(htmlspecialchars($profile['full_story'])); ?>
                        </div>
                    </div>

                    <!-- Education & Occupation -->
                    <?php if (!empty($profile['education_level']) || !empty($profile['field_of_study'])): ?>
                    <div class="review-section">
                        <h2>Education</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Level</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['education_level'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Field of Study</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['field_of_study'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['current_occupation']) || !empty($profile['skills'])): ?>
                    <div class="review-section">
                        <h2>Professional</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Current Occupation</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['current_occupation'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Skills</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['skills'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Social Media -->
                    <?php if (!empty($profile['facebook_url']) || !empty($profile['twitter_url']) || !empty($profile['instagram_url']) || !empty($profile['linkedin_url'])): ?>
                    <div class="review-section">
                        <h2>Social Media</h2>
                        <div class="social-links">
                            <?php if ($profile['facebook_url']): ?>
                                <a href="<?php echo htmlspecialchars($profile['facebook_url']); ?>" target="_blank" class="social-link">Facebook</a>
                            <?php endif; ?>
                            <?php if ($profile['twitter_url']): ?>
                                <a href="<?php echo htmlspecialchars($profile['twitter_url']); ?>" target="_blank" class="social-link">Twitter</a>
                            <?php endif; ?>
                            <?php if ($profile['instagram_url']): ?>
                                <a href="<?php echo htmlspecialchars($profile['instagram_url']); ?>" target="_blank" class="social-link">Instagram</a>
                            <?php endif; ?>
                            <?php if ($profile['linkedin_url']): ?>
                                <a href="<?php echo htmlspecialchars($profile['linkedin_url']); ?>" target="_blank" class="social-link">LinkedIn</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Additional Media -->
                    <?php if ($media->num_rows > 0): ?>
                    <div class="review-section">
                        <h2>Additional Media</h2>
                        <div class="media-grid">
                            <?php while ($item = $media->fetch_assoc()): ?>
                                <div class="media-item">
                                    <?php if ($item['media_type'] === 'image'): ?>
                                        <img src="../../<?php echo htmlspecialchars($item['file_path']); ?>" alt="Additional media">
                                    <?php else: ?>
                                        <video src="../../<?php echo htmlspecialchars($item['file_path']); ?>" controls></video>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Actions -->
                <div class="profile-review-sidebar">
                    <!-- Status & Actions -->
                    <div class="review-panel">
                        <h3>Profile Status</h3>
                        <div class="status-info">
                            <span class="status-label">Current Status:</span>
                            <span class="badge badge-<?php echo $profile['status']; ?> badge-lg">
                                <?php echo ucfirst($profile['status']); ?>
                            </span>
                        </div>

                        <?php if ($profile['status'] === 'pending'): ?>
                        <!-- Approve/Reject Actions -->
                        <form method="POST" class="action-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <button type="submit" name="action" value="approve" class="btn btn-success btn-block">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Approve Profile
                            </button>

                            <div class="rejection-section">
                                <textarea name="reason" class="form-control" rows="4" placeholder="Reason for rejection (required if rejecting)"></textarea>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-block">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    Reject Profile
                                </button>
                            </div>
                        </form>
                        <?php elseif ($profile['status'] === 'approved'): ?>
                        <!-- Publish/Unpublish -->
                        <form method="POST" class="action-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <?php if ($profile['is_published']): ?>
                                <button type="submit" name="action" value="unpublish" class="btn btn-warning btn-block">
                                    Unpublish from Website
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="publish" class="btn btn-success btn-block">
                                    Publish to Website
                                </button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- Metadata -->
                    <div class="review-panel">
                        <h3>Metadata</h3>
                        <div class="metadata-list">
                            <div class="metadata-item">
                                <span class="metadata-label">Submitted:</span>
                                <span class="metadata-value"><?php echo date('M j, Y g:i A', strtotime($profile['created_at'])); ?></span>
                            </div>
                            <?php if (!empty($profile['approved_at'])): ?>
                            <div class="metadata-item">
                                <span class="metadata-label">Reviewed:</span>
                                <span class="metadata-value"><?php echo date('M j, Y g:i A', strtotime($profile['approved_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="metadata-item">
                                <span class="metadata-label">Profile ID:</span>
                                <span class="metadata-value">#<?php echo $profile['id']; ?></span>
                            </div>
                            <div class="metadata-item">
                                <span class="metadata-label">Views:</span>
                                <span class="metadata-value"><?php echo $profile['view_count']; ?></span>
                            </div>
                            <div class="metadata-item">
                                <span class="metadata-label">Published:</span>
                                <span class="metadata-value"><?php echo $profile['is_published'] ? 'Yes' : 'No'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="review-panel">
                        <h3>Quick Actions</h3>
                        <a href="profile-edit.php?id=<?php echo $profile['id']; ?>" class="btn btn-secondary btn-block">
                            Edit Profile
                        </a>
                        <a href="../profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-secondary btn-block" target="_blank">
                            Preview on Website
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/admin-dashboard.js"></script>
</body>
</html>
