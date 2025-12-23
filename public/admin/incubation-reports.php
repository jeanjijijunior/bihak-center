<?php
/**
 * Incubation Program - Reports
 * Analytics and reports for the incubation program
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

// Total teams by status
$status_query = "
    SELECT status, COUNT(*) as count
    FROM incubation_teams
    GROUP BY status
";
$result = $conn->query($status_query);
$stats['by_status'] = $result->fetch_all(MYSQLI_ASSOC);

// Exercise completion rates
$exercise_query = "
    SELECT
        ie.exercise_number,
        ie.exercise_title,
        COUNT(DISTINCT tep.team_id) as teams_completed,
        (SELECT COUNT(*) FROM incubation_teams WHERE status != 'archived') as total_teams,
        ROUND(COUNT(DISTINCT tep.team_id) / (SELECT COUNT(*) FROM incubation_teams WHERE status != 'archived') * 100, 1) as completion_rate
    FROM incubation_exercises ie
    LEFT JOIN team_exercise_progress tep ON ie.id = tep.exercise_id AND tep.status = 'completed'
    WHERE ie.is_active = 1
    GROUP BY ie.id, ie.exercise_number, ie.exercise_title
    ORDER BY ie.exercise_number
";
$result = $conn->query($exercise_query);
$stats['exercise_completion'] = $result->fetch_all(MYSQLI_ASSOC);

// Team progress distribution
$progress_query = "
    SELECT
        CASE
            WHEN completion_percentage = 0 THEN 'Not Started'
            WHEN completion_percentage < 25 THEN '1-24%'
            WHEN completion_percentage < 50 THEN '25-49%'
            WHEN completion_percentage < 75 THEN '50-74%'
            WHEN completion_percentage < 100 THEN '75-99%'
            ELSE '100%'
        END as progress_range,
        COUNT(*) as team_count
    FROM incubation_teams
    WHERE status != 'archived'
    GROUP BY progress_range
    ORDER BY
        CASE progress_range
            WHEN 'Not Started' THEN 1
            WHEN '1-24%' THEN 2
            WHEN '25-49%' THEN 3
            WHEN '50-74%' THEN 4
            WHEN '75-99%' THEN 5
            WHEN '100%' THEN 6
        END
";
$result = $conn->query($progress_query);
$stats['progress_distribution'] = $result->fetch_all(MYSQLI_ASSOC);

closeDatabaseConnection($conn);

$page_title = 'Reports - Incubation Admin';
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

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            margin-bottom: 20px;
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="incubation-admin-dashboard.php" class="back-to-dashboard">
            <span>← Back to Dashboard</span>
        </a>

        <div class="dashboard-header">
            <h1>📊 Program Reports & Analytics</h1>
            <p>Comprehensive insights into the incubation program performance</p>
        </div>

        <!-- Teams by Status -->
        <div class="section">
            <h2>Teams by Status</h2>
            <div class="stats-grid">
                <?php foreach ($stats['by_status'] as $status): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $status['count']; ?></div>
                        <div class="stat-label"><?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Progress Distribution -->
        <div class="section">
            <h2>Team Progress Distribution</h2>
            <table>
                <thead>
                    <tr>
                        <th>Progress Range</th>
                        <th>Number of Teams</th>
                        <th>Visual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['progress_distribution'] as $dist): ?>
                        <tr>
                            <td><strong><?php echo $dist['progress_range']; ?></strong></td>
                            <td><?php echo $dist['team_count']; ?> teams</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, $dist['team_count'] * 20); ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Exercise Completion Rates -->
        <div class="section">
            <h2>Exercise Completion Rates</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Exercise</th>
                        <th>Teams Completed</th>
                        <th>Total Teams</th>
                        <th>Completion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['exercise_completion'] as $exercise): ?>
                        <tr>
                            <td><strong><?php echo $exercise['exercise_number']; ?></strong></td>
                            <td><?php echo htmlspecialchars($exercise['exercise_title']); ?></td>
                            <td><?php echo $exercise['teams_completed']; ?></td>
                            <td><?php echo $exercise['total_teams']; ?></td>
                            <td>
                                <strong><?php echo $exercise['completion_rate']; ?>%</strong>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $exercise['completion_rate']; ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
