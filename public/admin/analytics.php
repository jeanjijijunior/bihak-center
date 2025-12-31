<?php
/**
 * Admin Analytics Dashboard
 * Comprehensive data visualization and reporting
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();

$admin = Auth::user();
$conn = getDatabaseConnection();

// Get date range from query parameters (default: last 30 days)
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// ==========================
// USERS ANALYTICS
// ==========================

// Total users by status
$users_by_status = $conn->query("
    SELECT
        CASE WHEN p.status IS NULL THEN 'No Profile'
             ELSE p.status
        END as status,
        COUNT(*) as count
    FROM users u
    LEFT JOIN profiles p ON u.profile_id = p.id
    WHERE u.is_active = 1
    GROUP BY status
");

// Total active users
$total_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$total_users = 0;
if ($total_users_result && $total_users_result->num_rows > 0) {
    $total_users = $total_users_result->fetch_assoc()['count'];
}

// Users by registration date (last 12 months)
$users_by_month = $conn->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month ASC
");

// ==========================
// MENTORSHIP ANALYTICS
// ==========================

// Total mentors
$total_mentors_result = $conn->query("
    SELECT COUNT(*) as count
    FROM sponsors
    WHERE role_type = 'mentor' AND is_active = 1
");
$total_mentors = $total_mentors_result->fetch_assoc()['count'];

// Total mentorship relationships
$total_relationships_result = $conn->query("
    SELECT COUNT(*) as count
    FROM mentorship_relationships
    WHERE status = 'active'
");
$total_relationships = $total_relationships_result->fetch_assoc()['count'];

// Mentors by number of mentees
$mentors_by_mentees = $conn->query("
    SELECT
        s.full_name as mentor_name,
        s.organization,
        COUNT(mr.id) as mentee_count
    FROM sponsors s
    LEFT JOIN mentorship_relationships mr ON s.id = mr.mentor_id AND mr.status = 'active'
    WHERE s.role_type = 'mentor' AND s.is_active = 1
    GROUP BY s.id, s.full_name, s.organization
    ORDER BY mentee_count DESC
    LIMIT 10
");

// Mentorship relationships by status
$relationships_by_status = $conn->query("
    SELECT
        status,
        COUNT(*) as count
    FROM mentorship_relationships
    GROUP BY status
");

// ==========================
// MESSAGING ANALYTICS
// ==========================

// Total conversations
$total_conversations_result = $conn->query("SELECT COUNT(*) as count FROM conversations");
$total_conversations = $total_conversations_result->fetch_assoc()['count'];

// Total messages
$total_messages_result = $conn->query("SELECT COUNT(*) as count FROM messages");
$total_messages = $total_messages_result->fetch_assoc()['count'];

// Messages by sender type
$messages_by_type = $conn->query("
    SELECT
        sender_type,
        COUNT(*) as count
    FROM messages
    GROUP BY sender_type
");

// Messages over time (last 30 days)
$messages_by_day = $conn->query("
    SELECT
        DATE(created_at) as date,
        COUNT(*) as count
    FROM messages
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

// Average messages per conversation
$avg_messages_result = $conn->query("
    SELECT AVG(msg_count) as avg_count
    FROM (
        SELECT conversation_id, COUNT(*) as msg_count
        FROM messages
        GROUP BY conversation_id
    ) as conv_counts
");
$avg_messages = round($avg_messages_result->fetch_assoc()['avg_count'], 1);

// ==========================
// INCUBATION ANALYTICS
// ==========================

// Check if incubation tables exist
$incubation_exists = false;
$tables_check = $conn->query("SHOW TABLES LIKE 'incubation_teams'");
if ($tables_check && $tables_check->num_rows > 0) {
    $incubation_exists = true;
}

if ($incubation_exists) {
    // Total teams
    $total_teams_result = $conn->query("SELECT COUNT(*) as count FROM incubation_teams");
    $total_teams = 0;
    if ($total_teams_result && $total_teams_result->num_rows > 0) {
        $total_teams = $total_teams_result->fetch_assoc()['count'];
    }

    // Teams by phase
    $teams_by_phase = $conn->query("
        SELECT
            ip.phase_name,
            COUNT(it.id) as count
        FROM incubation_phases ip
        LEFT JOIN incubation_teams it ON ip.id = it.current_phase_id
        GROUP BY ip.id, ip.phase_name
        ORDER BY ip.phase_order
    ");

    // Average team progress
    $avg_progress_result = $conn->query("
        SELECT AVG(progress_percentage) as avg_progress
        FROM incubation_teams
    ");
    $avg_progress = 0;
    if ($avg_progress_result && $avg_progress_result->num_rows > 0) {
        $avg_data = $avg_progress_result->fetch_assoc();
        $avg_progress = $avg_data['avg_progress'] ? round($avg_data['avg_progress'], 1) : 0;
    }

    // Exercise completion stats
    $exercise_completion = $conn->query("
        SELECT
            ie.exercise_title,
            COUNT(DISTINCT its.team_id) as teams_completed,
            (SELECT COUNT(*) FROM incubation_teams) as total_teams
        FROM incubation_exercises ie
        LEFT JOIN incubation_team_submissions its ON ie.id = its.exercise_id
            AND its.status = 'approved'
        GROUP BY ie.id, ie.exercise_title
        ORDER BY teams_completed DESC
        LIMIT 10
    ");
} else {
    $total_teams = 0;
    $avg_progress = 0;
}

// ==========================
// PROFILE ANALYTICS
// ==========================

// Profiles by status
$profiles_by_status = $conn->query("
    SELECT
        status,
        COUNT(*) as count
    FROM profiles
    GROUP BY status
");

// Profiles by sector
$profiles_by_sector = $conn->query("
    SELECT
        sector,
        COUNT(*) as count
    FROM profiles
    WHERE sector IS NOT NULL AND sector != ''
    GROUP BY sector
    ORDER BY count DESC
    LIMIT 10
");

// ==========================
// ACTIVITY LOG ANALYTICS
// ==========================

// Recent admin actions
$recent_actions = $conn->query("
    SELECT
        al.action,
        COUNT(*) as count
    FROM activity_log al
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY al.action
    ORDER BY count DESC
    LIMIT 10
");

// Most active admins
$active_admins = $conn->query("
    SELECT
        a.username,
        COUNT(al.id) as action_count
    FROM admins a
    LEFT JOIN activity_log al ON a.id = al.admin_id
        AND al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY a.id, a.username
    ORDER BY action_count DESC
    LIMIT 5
");

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Bihak Center Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="../../assets/images/logob.png">

    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        .analytics-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .date-range-selector {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .date-range-selector label {
            font-weight: 600;
            color: #4b5563;
        }

        .date-range-selector input[type="date"] {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-export {
            padding: 0.5rem 1rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-export:hover {
            background: #059669;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .metric-card h3 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .metric-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .metric-card.users { border-left: 4px solid #3b82f6; }
        .metric-card.mentors { border-left: 4px solid #147ba5; }
        .metric-card.messages { border-left: 4px solid #10b981; }
        .metric-card.teams { border-left: 4px solid #f59e0b; }

        .chart-section {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .chart-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .two-column-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f9fafb;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
        }

        .data-table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.approved { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.rejected { background: #fee2e2; color: #991b1b; }
        .status-badge.active { background: #dbeafe; color: #1e40af; }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #147ba5);
            transition: width 0.3s ease;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
            font-style: italic;
        }

        @media print {
            .admin-sidebar,
            .export-buttons,
            .date-range-selector,
            .btn {
                display: none !important;
            }

            .analytics-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <?php include 'includes/admin-header.php'; ?>

    <!-- Admin Sidebar -->
    <?php include 'includes/admin-sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="analytics-container">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>Analytics Dashboard</h1>
                    <p>Comprehensive platform statistics and insights</p>
                </div>
                <div class="export-buttons">
                    <button class="btn-export" onclick="exportToPDF()">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"/>
                            <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                        </svg>
                        Export PDF
                    </button>
                    <button class="btn-export" onclick="exportToExcel()">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"/>
                        </svg>
                        Export Excel
                    </button>
                    <button class="btn-export" onclick="window.print()">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"/>
                        </svg>
                        Print
                    </button>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="analytics-grid">
                <div class="metric-card users">
                    <h3>Total Users</h3>
                    <div class="metric-value"><?php echo number_format($total_users); ?></div>
                    <div class="metric-label">Active registered users</div>
                </div>

                <div class="metric-card mentors">
                    <h3>Active Mentors</h3>
                    <div class="metric-value"><?php echo number_format($total_mentors); ?></div>
                    <div class="metric-label"><?php echo number_format($total_relationships); ?> mentorship relationships</div>
                </div>

                <div class="metric-card messages">
                    <h3>Total Messages</h3>
                    <div class="metric-value"><?php echo number_format($total_messages); ?></div>
                    <div class="metric-label"><?php echo number_format($total_conversations); ?> conversations</div>
                </div>

                <?php if ($incubation_exists): ?>
                <div class="metric-card teams">
                    <h3>Incubation Teams</h3>
                    <div class="metric-value"><?php echo number_format($total_teams); ?></div>
                    <div class="metric-label"><?php echo $avg_progress; ?>% average progress</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- User Analytics -->
            <div class="chart-section">
                <h2>
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    User Analytics
                </h2>

                <div class="two-column-grid">
                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">User Registration Trend (Last 12 Months)</h3>
                        <div class="chart-container">
                            <canvas id="usersOverTimeChart"></canvas>
                        </div>
                    </div>

                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Users by Profile Status</h3>
                        <div class="chart-container">
                            <canvas id="usersByStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mentorship Analytics -->
            <div class="chart-section">
                <h2>
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                    Mentorship Analytics
                </h2>

                <div class="two-column-grid">
                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Top Mentors by Mentee Count</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mentor Name</th>
                                    <th>Organization</th>
                                    <th>Mentees</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $mentors_by_mentees->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['mentor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['organization'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo $row['mentee_count']; ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Mentorship Relationships by Status</h3>
                        <div class="chart-container">
                            <canvas id="relationshipsByStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messaging Analytics -->
            <div class="chart-section">
                <h2>
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                        <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                    </svg>
                    Messaging Analytics
                </h2>

                <div class="analytics-grid" style="margin-bottom: 1.5rem;">
                    <div class="metric-card" style="border-left: 4px solid #10b981;">
                        <h3>Total Conversations</h3>
                        <div class="metric-value"><?php echo number_format($total_conversations); ?></div>
                    </div>
                    <div class="metric-card" style="border-left: 4px solid #3b82f6;">
                        <h3>Total Messages</h3>
                        <div class="metric-value"><?php echo number_format($total_messages); ?></div>
                    </div>
                    <div class="metric-card" style="border-left: 4px solid #147ba5;">
                        <h3>Avg Messages/Conversation</h3>
                        <div class="metric-value"><?php echo $avg_messages; ?></div>
                    </div>
                </div>

                <div class="two-column-grid">
                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Messages by Sender Type</h3>
                        <div class="chart-container">
                            <canvas id="messagesByTypeChart"></canvas>
                        </div>
                    </div>

                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Messages Over Time (Last 30 Days)</h3>
                        <div class="chart-container">
                            <canvas id="messagesOverTimeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($incubation_exists && $exercise_completion): ?>
            <!-- Incubation Analytics -->
            <div class="chart-section">
                <h2>
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                    Incubation Program Analytics
                </h2>

                <div class="two-column-grid">
                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Teams by Phase</h3>
                        <div class="chart-container">
                            <canvas id="teamsByPhaseChart"></canvas>
                        </div>
                    </div>

                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Exercise Completion Rates</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Exercise</th>
                                    <th>Completed</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $exercise_completion->fetch_assoc()):
                                    $completion_rate = ($row['teams_completed'] / max($row['total_teams'], 1)) * 100;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['exercise_title']); ?></td>
                                    <td><?php echo $row['teams_completed']; ?> / <?php echo $row['total_teams']; ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Analytics -->
            <div class="chart-section">
                <h2>
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    Profile Analytics
                </h2>

                <div class="two-column-grid">
                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Profiles by Status</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $profiles_by_status->data_seek(0);
                                while ($row = $profiles_by_status->fetch_assoc()):
                                    $percentage = ($row['count'] / max($total_users, 1)) * 100;
                                ?>
                                <tr>
                                    <td>
                                        <span class="status-badge <?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $row['count']; ?></strong></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h3 style="margin-bottom: 1rem; color: #6b7280;">Top 10 Sectors</h3>
                        <div class="chart-container">
                            <canvas id="profilesBySectorChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Prepare data for charts
        const usersMonthData = <?php
            $months = [];
            $counts = [];
            $users_by_month->data_seek(0);
            while ($row = $users_by_month->fetch_assoc()) {
                $months[] = $row['month'];
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $months, 'data' => $counts]);
        ?>;

        const userStatusData = <?php
            $statuses = [];
            $counts = [];
            $users_by_status->data_seek(0);
            while ($row = $users_by_status->fetch_assoc()) {
                $statuses[] = ucfirst($row['status']);
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $statuses, 'data' => $counts]);
        ?>;

        const relationshipStatusData = <?php
            $statuses = [];
            $counts = [];
            $relationships_by_status->data_seek(0);
            while ($row = $relationships_by_status->fetch_assoc()) {
                $statuses[] = ucfirst($row['status']);
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $statuses, 'data' => $counts]);
        ?>;

        const messageTypeData = <?php
            $types = [];
            $counts = [];
            $messages_by_type->data_seek(0);
            while ($row = $messages_by_type->fetch_assoc()) {
                $types[] = ucfirst($row['sender_type']);
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $types, 'data' => $counts]);
        ?>;

        const messagesTimeData = <?php
            $dates = [];
            $counts = [];
            $messages_by_day->data_seek(0);
            while ($row = $messages_by_day->fetch_assoc()) {
                $dates[] = $row['date'];
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $dates, 'data' => $counts]);
        ?>;

        <?php if ($incubation_exists && isset($teams_by_phase)): ?>
        const teamsPhaseData = <?php
            $phases = [];
            $counts = [];
            $teams_by_phase->data_seek(0);
            while ($row = $teams_by_phase->fetch_assoc()) {
                $phases[] = $row['phase_name'];
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $phases, 'data' => $counts]);
        ?>;
        <?php endif; ?>

        const sectorData = <?php
            $sectors = [];
            $counts = [];
            $profiles_by_sector->data_seek(0);
            while ($row = $profiles_by_sector->fetch_assoc()) {
                $sectors[] = $row['sector'];
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $sectors, 'data' => $counts]);
        ?>;

        // Chart configurations
        const chartColors = {
            blue: '#3b82f6',
            purple: '#147ba5',
            green: '#10b981',
            orange: '#f59e0b',
            red: '#ef4444',
            pink: '#ec4899',
            yellow: '#eab308',
            cyan: '#06b6d4'
        };

        // Users Over Time Chart
        new Chart(document.getElementById('usersOverTimeChart'), {
            type: 'line',
            data: {
                labels: usersMonthData.labels,
                datasets: [{
                    label: 'New Users',
                    data: usersMonthData.data,
                    borderColor: chartColors.blue,
                    backgroundColor: chartColors.blue + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Users by Status Chart
        new Chart(document.getElementById('usersByStatusChart'), {
            type: 'doughnut',
            data: {
                labels: userStatusData.labels,
                datasets: [{
                    data: userStatusData.data,
                    backgroundColor: [chartColors.green, chartColors.orange, chartColors.red, chartColors.blue]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Relationships by Status Chart
        new Chart(document.getElementById('relationshipsByStatusChart'), {
            type: 'pie',
            data: {
                labels: relationshipStatusData.labels,
                datasets: [{
                    data: relationshipStatusData.data,
                    backgroundColor: [chartColors.blue, chartColors.green, chartColors.orange, chartColors.red]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Messages by Type Chart
        new Chart(document.getElementById('messagesByTypeChart'), {
            type: 'bar',
            data: {
                labels: messageTypeData.labels,
                datasets: [{
                    label: 'Messages',
                    data: messageTypeData.data,
                    backgroundColor: [chartColors.blue, chartColors.purple, chartColors.green]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Messages Over Time Chart
        new Chart(document.getElementById('messagesOverTimeChart'), {
            type: 'line',
            data: {
                labels: messagesTimeData.labels,
                datasets: [{
                    label: 'Messages',
                    data: messagesTimeData.data,
                    borderColor: chartColors.green,
                    backgroundColor: chartColors.green + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        <?php if ($incubation_exists && isset($teams_by_phase)): ?>
        // Teams by Phase Chart
        new Chart(document.getElementById('teamsByPhaseChart'), {
            type: 'bar',
            data: {
                labels: teamsPhaseData.labels,
                datasets: [{
                    label: 'Teams',
                    data: teamsPhaseData.data,
                    backgroundColor: chartColors.orange
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                }
            }
        });
        <?php endif; ?>

        // Profiles by Sector Chart
        new Chart(document.getElementById('profilesBySectorChart'), {
            type: 'bar',
            data: {
                labels: sectorData.labels,
                datasets: [{
                    label: 'Profiles',
                    data: sectorData.data,
                    backgroundColor: chartColors.purple
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Export functions
        function exportToPDF() {
            alert('PDF export functionality will be implemented using libraries like jsPDF or server-side PDF generation.');
            // TODO: Implement PDF export
        }

        function exportToExcel() {
            alert('Excel export functionality will be implemented using libraries like SheetJS or server-side Excel generation.');
            // TODO: Implement Excel export
        }
    </script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
