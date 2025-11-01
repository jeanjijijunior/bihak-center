<?php
/**
 * My Account Page
 * User dashboard for managing profile and account settings
 */

require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/database.php';

// Require authentication (cache headers applied automatically)
UserAuth::requireAuth();

$user = UserAuth::user();
$conn = getDatabaseConnection();

// Get user's profile if they have one
$profile = null;
if ($user['profile_id']) {
    $stmt = $conn->prepare("
        SELECT * FROM profiles WHERE id = ?
    ");
    $stmt->bind_param('i', $user['profile_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
    }
}

// Get user activity
$activity_stmt = $conn->prepare("
    SELECT action, details, created_at
    FROM user_activity_log
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$activity_stmt->bind_param('i', $user['id']);
$activity_stmt->execute();
$activities = $activity_stmt->get_result();

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="../assets/images/logob.png">
    <style>
        body {
            background: #f7fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .account-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #718096;
            font-size: 16px;
        }

        .account-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
        }

        .account-main, .account-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 16px;
        }

        .profile-status {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .profile-status.pending {
            background: #fef3c7;
            border: 1px solid #fcd34d;
        }

        .profile-status.approved {
            background: #d1fae5;
            border: 1px solid #86efac;
        }

        .profile-status.rejected {
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }

        .profile-status.none {
            background: #e0e7ff;
            border: 1px solid #a5b4fc;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 16px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #718096;
        }

        .info-value {
            font-size: 15px;
            color: #1a202c;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1cabe2, #147ba5);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(28, 171, 226, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: #f7fafc;
            border-radius: 8px;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: #1cabe2;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-content p {
            font-size: 14px;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .activity-time {
            font-size: 12px;
            color: #718096;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        @media (max-width: 992px) {
            .account-grid {
                grid-template-columns: 1fr;
            }

            .account-sidebar {
                order: -1;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header_new.php'; ?>

    <div class="account-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 data-translate="myAccount">My Account</h1>
            <p data-translate="manageProfileSettings">Manage your profile and account settings</p>
        </div>

        <!-- Account Grid -->
        <div class="account-grid">
            <!-- Main Content -->
            <div class="account-main">
                <!-- Profile Status Card -->
                <div class="card">
                    <h2 data-translate="myProfileStatus">My Profile Status</h2>

                    <?php if ($profile): ?>
                        <!-- Has Profile -->
                        <div class="profile-status <?php echo $profile['status']; ?>">
                            <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                                <?php if ($profile['status'] === 'approved'): ?>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                <?php elseif ($profile['status'] === 'pending'): ?>
                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                <?php else: ?>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                <?php endif; ?>
                            </svg>
                            <div>
                                <strong><span data-translate="status">Status</span>: <?php echo ucfirst($profile['status']); ?></strong>
                                <p style="font-size: 14px; margin-top: 4px;" id="profile-status-message">
                                    <?php if ($profile['status'] === 'pending'): ?>
                                        <span data-translate="profilePendingReview">Your profile is being reviewed by our team. We'll notify you soon!</span>
                                    <?php elseif ($profile['status'] === 'approved'): ?>
                                        <span data-translate="profileLive">Your profile is live!</span>
                                        <?php if ($profile['is_published']): ?>
                                            <span data-translate="visibleOnWebsite">Visible on website.</span>
                                        <?php else: ?>
                                            <span data-translate="awaitingPublication">Awaiting publication.</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span data-translate="profileNotApproved">Your profile was not approved. Reason:</span>
                                        <?php echo htmlspecialchars($profile['rejection_reason'] ?? ''); ?>
                                        <?php if (!$profile['rejection_reason']): ?>
                                            <span data-translate="notSpecified">Not specified</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Profile Info -->
                        <div class="profile-info-grid">
                            <div class="info-item">
                                <span class="info-label" data-translate="title">Title</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['title']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label" data-translate="submitted">Submitted</span>
                                <span class="info-value"><?php echo date('M j, Y', strtotime($profile['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label" data-translate="views">Views</span>
                                <span class="info-value"><?php echo number_format($profile['view_count']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label" data-translate="published">Published</span>
                                <span class="info-value">
                                    <?php if ($profile['is_published']): ?>
                                        <span data-translate="yes">Yes</span>
                                    <?php else: ?>
                                        <span data-translate="no">No</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($profile['is_published']): ?>
                            <a href="profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-primary" style="margin-top: 16px;">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                                <span data-translate="viewPublicProfile">View My Public Profile</span>
                            </a>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No Profile Yet -->
                        <div class="profile-status none">
                            <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <strong data-translate="noProfileYet">No Profile Yet</strong>
                                <p style="font-size: 14px; margin-top: 4px;" data-translate="shareYourStory">
                                    Share your story with the world! Create your profile to showcase your talents and achievements.
                                </p>
                            </div>
                        </div>

                        <a href="signup.php" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                            <span data-translate="createMyProfile">Create My Profile</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <h2 data-translate="recentActivity">Recent Activity</h2>
                    <div class="activity-list">
                        <?php if ($activities->num_rows > 0): ?>
                            <?php while ($activity = $activities->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="activity-content">
                                        <p><?php echo htmlspecialchars($activity['details']); ?></p>
                                        <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: #718096; text-align: center; padding: 32px;" data-translate="noActivityYet">No activity yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="account-sidebar">
                <!-- Account Info -->
                <div class="card">
                    <h2 data-translate="accountInformation">Account Information</h2>
                    <div class="profile-info-grid">
                        <div class="info-item">
                            <span class="info-label" data-translate="name">Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label" data-translate="email">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label" data-translate="emailStatus">Email Status</span>
                            <span class="info-value">
                                <?php if ($user['email_verified']): ?>
                                    ✓ <span data-translate="verified">Verified</span>
                                <?php else: ?>
                                    ⚠ <span data-translate="notVerified">Not Verified</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h2 data-translate="quickActions">Quick Actions</h2>
                    <div class="quick-actions">
                        <?php if (!$profile): ?>
                            <a href="signup.php" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                                <span data-translate="createProfile">Create Profile</span>
                            </a>
                        <?php endif; ?>

                        <a href="opportunities.php" class="btn btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            <span data-translate="browseOpportunities">Browse Opportunities</span>
                        </a>

                        <a href="index.php" class="btn btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                            </svg>
                            <span data-translate="backToHome">Back to Home</span>
                        </a>

                        <a href="logout.php" class="btn btn-secondary" style="color: #e53e3e;">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate="logout">Logout</span>
                        </a>
                    </div>
                </div>

                <!-- Help & Support -->
                <div class="card">
                    <h2 data-translate="needHelp">Need Help?</h2>
                    <p style="font-size: 14px; color: #718096; margin-bottom: 12px;" data-translate="haveQuestions">
                        Have questions or need assistance? We're here to help!
                    </p>
                    <a href="contact.php" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span data-translate="contactSupport">Contact Support</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/header_new.js"></script>
</body>
</html>
