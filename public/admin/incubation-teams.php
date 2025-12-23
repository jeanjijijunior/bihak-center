<?php
/**
 * Incubation Program - Manage Teams
 * Admin interface for viewing and managing all incubation teams
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

// Get all teams with details
$teams_query = "
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
";
$result = $conn->query($teams_query);
$teams = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

closeDatabaseConnection($conn);

$page_title = 'Manage Teams - Incubation Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #6b7280;
        }

        .back-to-dashboard {
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

        .back-to-dashboard:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        td {
            padding: 16px 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
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

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="incubation-admin-dashboard.php" class="back-to-dashboard">
            <span>← Back to Dashboard</span>
        </a>

        <div class="dashboard-header">
            <h1>👥 Manage Teams</h1>
            <p>View and manage all incubation program teams</p>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 20px;">All Teams (<?php echo count($teams); ?>)</h2>

            <?php if (empty($teams)): ?>
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
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                                <td>
                                    <?php if ($team['leader_name']): ?>
                                        <?php echo htmlspecialchars($team['leader_name']); ?><br>
                                        <small style="color: #9ca3af;"><?php echo htmlspecialchars($team['leader_email']); ?></small>
                                    <?php else: ?>
                                        <em>No leader</em>
                                    <?php endif; ?>
                                </td>
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
                                        <a href="incubation-team-detail.php?id=<?php echo $team['id']; ?>" class="btn btn-primary btn-small">View Details</a>
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
