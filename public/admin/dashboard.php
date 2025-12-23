<?php
/**
 * Admin Dashboard
 * Overview with statistics, recent profiles, and quick actions
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication (cache headers applied automatically)
Auth::requireAuth();

$admin = Auth::user();
$conn = getDatabaseConnection();

// Get dashboard statistics
$stats_query = "SELECT * FROM dashboard_stats LIMIT 1";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent profiles
$recent_profiles_query = "
    SELECT id, full_name, title, short_description, profile_image, status, created_at
    FROM profiles
    ORDER BY created_at DESC
    LIMIT 6
";
$recent_profiles = $conn->query($recent_profiles_query);

// Get recent admin activity
$recent_activity_query = "
    SELECT * FROM recent_admin_activity LIMIT 10
";
$recent_activity = $conn->query($recent_activity_query);

// Get profile statistics by status
$profile_chart_query = "
    SELECT
        status,
        COUNT(*) as count
    FROM profiles
    GROUP BY status
";
$profile_chart = $conn->query($profile_chart_query);
$chart_data = [];
while ($row = $profile_chart->fetch_assoc()) {
    $chart_data[$row['status']] = $row['count'];
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bihak Center Admin</title>
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
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($admin['name']); ?>!</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.reload()">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-pending">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3>Pending Approval</h3>
                        <div class="stat-value"><?php echo $stats['pending_profiles']; ?></div>
                        <a href="profiles.php?status=pending" class="stat-link">Review now →</a>
                    </div>
                </div>

                <div class="stat-card stat-approved">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3>Approved Profiles</h3>
                        <div class="stat-value"><?php echo $stats['approved_profiles']; ?></div>
                        <a href="profiles.php?status=approved" class="stat-link">View all →</a>
                    </div>
                </div>

                <div class="stat-card stat-new">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3>New This Week</h3>
                        <div class="stat-value"><?php echo $stats['new_profiles_week']; ?></div>
                        <p class="stat-subtext"><?php echo $stats['new_profiles_month']; ?> this month</p>
                    </div>
                </div>

                <div class="stat-card stat-activity">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3>Actions Today</h3>
                        <div class="stat-value"><?php echo $stats['actions_today']; ?></div>
                        <a href="activity-log.php" class="stat-link">View log →</a>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="dashboard-grid">
                <!-- Recent Profiles -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h2>Recent Submissions</h2>
                        <a href="profiles.php" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="profiles-list">
                        <?php if ($recent_profiles->num_rows > 0): ?>
                            <?php while ($profile = $recent_profiles->fetch_assoc()): ?>
                                <div class="profile-item">
                                    <div class="profile-avatar">
                                        <?php
                                        $image_path = $profile['profile_image'];
                                        $image_exists = $image_path && file_exists(__DIR__ . '/../../' . $image_path);
                                        ?>
                                        <?php if ($image_exists): ?>
                                            <img src="../../<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($profile['full_name']); ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?php echo strtoupper(substr($profile['full_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="profile-info">
                                        <h4><?php echo htmlspecialchars($profile['full_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($profile['title']); ?></p>
                                        <span class="profile-date"><?php echo date('M j, Y', strtotime($profile['created_at'])); ?></span>
                                    </div>
                                    <div class="profile-status">
                                        <span class="badge badge-<?php echo $profile['status']; ?>">
                                            <?php echo ucfirst($profile['status']); ?>
                                        </span>
                                    </div>
                                    <div class="profile-actions">
                                        <a href="profile-review.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No recent profiles</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="quick-actions">
                        <a href="profiles.php?status=pending" class="action-card">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h4>Review Pending</h4>
                                <p><?php echo $stats['pending_profiles']; ?> profiles waiting</p>
                            </div>
                        </a>

                        <a href="profiles.php" class="action-card">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h4>Manage Profiles</h4>
                                <p>View and edit all profiles</p>
                            </div>
                        </a>

                        <a href="activity-log.php" class="action-card">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h4>Activity Log</h4>
                                <p>View recent admin actions</p>
                            </div>
                        </a>

                        <a href="manage-passwords.php" class="action-card">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h4>Password Management</h4>
                                <p>Manage credentials for all user types</p>
                            </div>
                        </a>

                        <a href="settings.php" class="action-card">
                            <div class="action-icon">
                                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="action-content">
                                <h4>Settings</h4>
                                <p>Configure system settings</p>
                            </div>
                        </a>
                    </div>

                    <!-- Recent Activity -->
                    <div class="panel-section">
                        <h3>Recent Activity</h3>
                        <div class="activity-list">
                            <?php if ($recent_activity->num_rows > 0): ?>
                                <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="activity-content">
                                            <p>
                                                <strong><?php echo htmlspecialchars($activity['admin_name']); ?></strong>
                                                <?php echo htmlspecialchars($activity['details']); ?>
                                            </p>
                                            <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/admin-dashboard.js"></script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
