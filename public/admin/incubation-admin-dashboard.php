<?php
/**
 * Incubation Program - Admin Dashboard
 * Main admins can manage teams, review submissions, and monitor progress
 */
require_once __DIR__ . '/../../config/auth.php';

// Require admin authentication
Auth::init();
if (!Auth::check()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$conn = getDatabaseConnection();

// Get statistics
$stats = [];

// Total teams (all except archived)
$result = $conn->query("SELECT COUNT(*) as count FROM incubation_teams WHERE status != 'archived'");
$stats['total_teams'] = $result->fetch_assoc()['count'];

// Total participants
$result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM incubation_team_members WHERE status = 'active'");
$stats['total_participants'] = $result->fetch_assoc()['count'];

// Pending submissions (submitted but not reviewed)
$result = $conn->query("SELECT COUNT(*) as count FROM exercise_submissions WHERE status = 'submitted'");
$stats['pending_reviews'] = $result->fetch_assoc()['count'];

// Completed teams (all exercises done)
$result = $conn->query("SELECT COUNT(DISTINCT team_id) as count FROM team_exercise_progress WHERE status = 'completed' GROUP BY team_id HAVING COUNT(*) = 19");
$stats['completed_teams'] = $result->fetch_assoc()['count'] ?? 0;

// Get recent teams
$recent_teams_query = "
    SELECT
        t.id,
        t.team_name,
        t.current_phase_id,
        t.status,
        t.created_at,
        t.completion_percentage,
        u.full_name as leader_name,
        u.email as leader_email,
        COUNT(DISTINCT tm.user_id) as member_count,
        COUNT(DISTINCT CASE WHEN tep.status = 'completed' THEN tep.id END) as completed_exercises
    FROM incubation_teams t
    LEFT JOIN incubation_team_members tm_leader ON t.id = tm_leader.team_id AND tm_leader.role = 'leader' AND tm_leader.status = 'active'
    LEFT JOIN users u ON tm_leader.user_id = u.id
    LEFT JOIN incubation_team_members tm ON t.id = tm.team_id AND tm.status = 'active'
    LEFT JOIN team_exercise_progress tep ON t.id = tep.team_id
    GROUP BY t.id, t.team_name, t.current_phase_id, t.status, t.created_at, t.completion_percentage, u.full_name, u.email
    ORDER BY t.created_at DESC
    LIMIT 10
";
$result = $conn->query($recent_teams_query);
if (!$result) {
    error_log("Query error in incubation-admin-dashboard.php: " . $conn->error);
    $recent_teams = [];
} else {
    $recent_teams = $result->fetch_all(MYSQLI_ASSOC);
}

// Get pending submissions
$pending_submissions_query = "
    SELECT
        es.id,
        es.submitted_at,
        t.team_name,
        ie.exercise_title,
        ip.phase_name,
        u.full_name as leader_name
    FROM exercise_submissions es
    JOIN incubation_teams t ON es.team_id = t.id
    JOIN incubation_exercises ie ON es.exercise_id = ie.id
    JOIN incubation_phases ip ON ie.phase_id = ip.id
    LEFT JOIN incubation_team_members tm ON t.id = tm.team_id AND tm.role = 'leader' AND tm.status = 'active'
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE es.status = 'submitted'
    ORDER BY es.submitted_at DESC
    LIMIT 10
";
$result = $conn->query($pending_submissions_query);
if (!$result) {
    error_log("Query error in incubation-admin-dashboard.php (pending submissions): " . $conn->error);
    $pending_submissions = [];
} else {
    $pending_submissions = $result->fetch_all(MYSQLI_ASSOC);
}

closeDatabaseConnection($conn);

$page_title = 'Incubation Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Bihak Center</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .dashboard-header p {
            margin: 0;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #1cabe2;
        }

        .stat-card.warning {
            border-left-color: #f59e0b;
        }

        .stat-card.success {
            border-left-color: #10b981;
        }

        .stat-card.info {
            border-left-color: #3b82f6;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: #1cabe2;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
        }

        tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-primary {
            background: #e0e7ff;
            color: #3730a3;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 4px 12px;
            font-size: 12px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .quick-action-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
            border: 2px solid transparent;
        }

        .quick-action-card:hover {
            transform: translateY(-3px);
            border-color: #1cabe2;
        }

        .quick-action-card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .quick-action-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .quick-action-label {
            font-weight: 600;
            color: #1f2937;
        }

        /* Badge Styles */
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background: #e5e7eb;
            color: #6b7280;
        }

        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        /* Return to Main Website Button */
        .back-to-main-site {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .back-to-main-site:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h1 style="margin: 0;">🚀 Incubation Program Administration</h1>
                <a href="../../public/index.php" class="back-to-main-site" style="margin: 0;">
                    <span>🏠</span>
                    <span>Return to Main Website</span>
                </a>
            </div>
            <p>Manage teams, review submissions, and track progress across all incubation activities</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-number"><?php echo $stats['total_teams']; ?></div>
                <div class="stat-label">Active Teams</div>
            </div>

            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['total_participants']; ?></div>
                <div class="stat-label">Total Participants</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['pending_reviews']; ?></div>
                <div class="stat-label">Pending Reviews</div>
            </div>

            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_teams']; ?></div>
                <div class="stat-label">Completed Teams</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="quick-action-card">
                <a href="incubation-teams.php">
                    <div class="quick-action-icon">👥</div>
                    <div class="quick-action-label">Manage Teams</div>
                </a>
            </div>

            <div class="quick-action-card">
                <a href="incubation-reviews.php">
                    <div class="quick-action-icon">📝</div>
                    <div class="quick-action-label">Review Submissions</div>
                </a>
            </div>

            <div class="quick-action-card">
                <a href="incubation-exercises.php">
                    <div class="quick-action-icon">📚</div>
                    <div class="quick-action-label">Manage Exercises</div>
                </a>
            </div>

            <div class="quick-action-card">
                <a href="incubation-reports.php">
                    <div class="quick-action-icon">📊</div>
                    <div class="quick-action-label">View Reports</div>
                </a>
            </div>
        </div>

        <!-- Recent Teams -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Teams</h2>
                <a href="incubation-teams.php" class="btn btn-primary">View All Teams</a>
            </div>

            <?php if (empty($recent_teams)): ?>
                <p style="text-align: center; color: #6b7280; padding: 40px 0;">
                    No teams yet. Teams will appear here once users create them.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Team Name</th>
                            <th>Leader</th>
                            <th>Members</th>
                            <th>Phase</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_teams as $team): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($team['leader_name'] ?? 'No leader'); ?></td>
                                <td><?php echo $team['member_count']; ?></td>
                                <td>
                                    <?php if ($team['current_phase_id']): ?>
                                        <span class="badge badge-primary">Phase <?php echo $team['current_phase_id']; ?></span>
                                    <?php else: ?>
                                        <span class="badge">Not started</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?php echo $team['completion_percentage']; ?>%; height: 100%; background: #10b981;"></div>
                                        </div>
                                        <span style="font-size: 0.85rem; color: #6b7280;"><?php echo round($team['completion_percentage']); ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'forming' => 'badge-warning',
                                        'in_progress' => 'badge-primary',
                                        'completed' => 'badge-success',
                                        'archived' => 'badge'
                                    ];
                                    $badge_class = $status_colors[$team['status']] ?? 'badge';
                                    echo '<span class="badge ' . $badge_class . '">' . ucfirst(str_replace('_', ' ', $team['status'])) . '</span>';
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($team['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="incubation-team-detail.php?id=<?php echo $team['id']; ?>" class="btn btn-primary btn-small">View</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pending Submissions -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Pending Submissions</h2>
                <a href="incubation-reviews.php" class="btn btn-primary">Review All</a>
            </div>

            <?php if (empty($pending_submissions)): ?>
                <p style="text-align: center; color: #6b7280; padding: 40px 0;">
                    No pending submissions. All caught up! 🎉
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>Exercise</th>
                            <th>Phase</th>
                            <th>Submitted By</th>
                            <th>Submitted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_submissions as $submission): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($submission['team_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($submission['exercise_title']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($submission['phase_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($submission['leader_name']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="incubation-review-submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-primary btn-small">Review</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
