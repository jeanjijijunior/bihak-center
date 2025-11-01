<?php
/**
 * Sponsor Review Page
 * Admin can view full details and approve/reject sponsor applications
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Require authentication
Auth::requireAuth();

$admin = Auth::user();
$conn = getDatabaseConnection();

// Get sponsor ID
$sponsor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sponsor_id <= 0) {
    header('Location: sponsors.php');
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

        if ($action === 'approve') {
            $stmt = $conn->prepare("
                UPDATE sponsors
                SET status = 'approved', is_active = TRUE, approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $admin['id'], $sponsor_id);

            if ($stmt->execute()) {
                Auth::logActivity($admin['id'], 'sponsor_approved', 'sponsor', $sponsor_id, "Approved sponsor interest submission ID {$sponsor_id}");
                $success = 'Interest submission approved successfully!';
            } else {
                $error = 'Failed to approve submission. Please try again.';
            }

        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            if (empty($reason)) {
                $error = 'Please provide a reason for rejection.';
            } else {
                $stmt = $conn->prepare("
                    UPDATE sponsors
                    SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('sii', $reason, $admin['id'], $sponsor_id);

                if ($stmt->execute()) {
                    Auth::logActivity($admin['id'], 'sponsor_rejected', 'sponsor', $sponsor_id, "Rejected sponsor interest submission ID {$sponsor_id}: {$reason}");
                    $success = 'Interest submission rejected.';
                } else {
                    $error = 'Failed to reject submission. Please try again.';
                }
            }
        }
    }
}

// Fetch sponsor details
$stmt = $conn->prepare("SELECT * FROM sponsors WHERE id = ?");
$stmt->bind_param('i', $sponsor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    closeDatabaseConnection($conn);
    header('Location: sponsors.php');
    exit;
}

$sponsor = $result->fetch_assoc();
closeDatabaseConnection($conn);

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Interest - <?php echo htmlspecialchars($sponsor['full_name']); ?></title>
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
                    <a href="sponsors.php" class="back-link">← Back to Sponsors</a>
                    <h1>Review Interest</h1>
                    <p><?php echo htmlspecialchars($sponsor['full_name']); ?></p>
                </div>
                <div class="header-actions">
                    <span class="badge badge-<?php echo $sponsor['status']; ?> badge-lg">
                        <?php echo ucfirst($sponsor['status']); ?>
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

            <!-- Main Grid -->
            <div class="profile-review-grid">
                <!-- Main Content -->
                <div class="profile-review-main">
                    <!-- Personal Information -->
                    <div class="review-section">
                        <h2>Contact Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Full Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['full_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Preferred Contact</span>
                                <span class="info-value"><?php echo ucfirst($sponsor['preferred_contact']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Organization -->
                    <?php if (!empty($sponsor['organization']) || !empty($sponsor['website'])): ?>
                    <div class="review-section">
                        <h2>Organization Details</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Organization</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['organization'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Website</span>
                                <span class="info-value">
                                    <?php if (!empty($sponsor['website'])): ?>
                                        <a href="<?php echo htmlspecialchars($sponsor['website']); ?>" target="_blank"><?php echo htmlspecialchars($sponsor['website']); ?></a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Country</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['country'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">City</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['city'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Role & Expertise -->
                    <div class="review-section">
                        <h2>Role & Expertise</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Role Type</span>
                                <span class="info-value">
                                    <span class="badge badge-info"><?php echo ucfirst($sponsor['role_type']); ?></span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Expertise Domain</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['expertise_domain'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Availability</span>
                                <span class="info-value"><?php echo htmlspecialchars($sponsor['availability'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($sponsor['involvement_areas'])): ?>
                        <div style="margin-top: 20px;">
                            <span class="info-label" style="display: block; margin-bottom: 10px;">Areas of Involvement</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                <?php
                                $areas = explode(',', $sponsor['involvement_areas']);
                                foreach ($areas as $area):
                                ?>
                                <span class="badge badge-primary"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $area))); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Message -->
                    <?php if (!empty($sponsor['message'])): ?>
                    <div class="review-section">
                        <h2>Message</h2>
                        <div class="full-story">
                            <?php echo nl2br(htmlspecialchars($sponsor['message'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Social Media -->
                    <?php if (!empty($sponsor['linkedin_url']) || !empty($sponsor['facebook_url']) || !empty($sponsor['twitter_url'])): ?>
                    <div class="review-section">
                        <h2>Social Media</h2>
                        <div class="social-links">
                            <?php if (!empty($sponsor['linkedin_url'])): ?>
                            <a href="<?php echo htmlspecialchars($sponsor['linkedin_url']); ?>" target="_blank" class="social-link">LinkedIn</a>
                            <?php endif; ?>
                            <?php if (!empty($sponsor['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($sponsor['facebook_url']); ?>" target="_blank" class="social-link">Facebook</a>
                            <?php endif; ?>
                            <?php if (!empty($sponsor['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($sponsor['twitter_url']); ?>" target="_blank" class="social-link">Twitter</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <aside class="profile-review-sidebar">
                    <!-- Actions -->
                    <?php if ($sponsor['status'] === 'pending'): ?>
                    <div class="review-panel">
                        <h3>Actions</h3>

                        <!-- Approve Form -->
                        <form method="POST" action="" style="margin-bottom: 15px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 8px;">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Approve Interest
                            </button>
                        </form>

                        <!-- Reject Form -->
                        <form method="POST" action="" class="action-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="reject">

                            <label for="reason">Reason for Rejection</label>
                            <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Provide a reason..." required></textarea>

                            <button type="submit" class="btn btn-danger" style="width: 100%; margin-top: 10px;">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 8px;">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                Reject Interest
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Status Info -->
                    <?php if ($sponsor['status'] !== 'pending'): ?>
                    <div class="review-panel">
                        <h3>Status Information</h3>
                        <div class="status-info">
                            <span class="status-label">Status</span>
                            <span class="badge badge-<?php echo $sponsor['status']; ?> badge-lg">
                                <?php echo ucfirst($sponsor['status']); ?>
                            </span>
                        </div>

                        <?php if ($sponsor['rejection_reason']): ?>
                        <div style="margin-top: 15px;">
                            <span class="info-label">Rejection Reason</span>
                            <p style="color: #dc2626; margin-top: 8px; font-size: 0.9rem;">
                                <?php echo nl2br(htmlspecialchars($sponsor['rejection_reason'])); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Metadata -->
                    <div class="review-panel">
                        <h3>Metadata</h3>
                        <div class="metadata-list">
                            <div class="metadata-item">
                                <span class="metadata-label">Submitted:</span>
                                <span class="metadata-value"><?php echo date('M j, Y g:i A', strtotime($sponsor['created_at'])); ?></span>
                            </div>
                            <?php if (!empty($sponsor['approved_at'])): ?>
                            <div class="metadata-item">
                                <span class="metadata-label">Reviewed:</span>
                                <span class="metadata-value"><?php echo date('M j, Y g:i A', strtotime($sponsor['approved_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="metadata-item">
                                <span class="metadata-label">Submission ID:</span>
                                <span class="metadata-value">#<?php echo $sponsor['id']; ?></span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <style>
        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-lg {
            font-size: 0.95rem;
            padding: 6px 14px;
        }

        .action-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--gray-700);
        }
    </style>
</body>
</html>
