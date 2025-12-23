<?php
/**
 * Incubation Program Dashboard
 * Main workspace for teams to view phases, exercises, and progress
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';
$conn = getDatabaseConnection();

// Get user's team
$team_query = "
    SELECT t.*, tm.role
    FROM incubation_teams t
    JOIN team_members tm ON t.id = tm.team_id
    WHERE tm.user_id = ? AND tm.is_active = TRUE
    LIMIT 1
";
$stmt = $conn->prepare($team_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$team_result = $stmt->get_result();
$team = $team_result->fetch_assoc();

if (!$team) {
    header('Location: incubation-team-create.php');
    exit;
}

$team_id = $team['id'];
$is_leader = ($team['role'] === 'leader');

// Get team members
$members_query = "
    SELECT u.id, u.full_name, u.email, tm.role, tm.join_date
    FROM team_members tm
    JOIN users u ON tm.user_id = u.id
    WHERE tm.team_id = ? AND tm.is_active = TRUE
    ORDER BY tm.role, tm.join_date
";
$stmt = $conn->prepare($members_query);
$stmt->bind_param('i', $team_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = $members_result->fetch_all(MYSQLI_ASSOC);

// Get all phases with exercises and completion status
$phases_query = "
    SELECT
        pp.*,
        COUNT(DISTINCT pe.id) as total_exercises,
        COUNT(DISTINCT CASE WHEN es.status = 'approved' THEN pe.id END) as completed_exercises
    FROM program_phases pp
    LEFT JOIN program_exercises pe ON pp.id = pe.phase_id AND pe.is_active = TRUE
    LEFT JOIN exercise_submissions es ON pe.id = es.exercise_id AND es.team_id = ?
    WHERE pp.program_id = ? AND pp.is_active = TRUE
    GROUP BY pp.id
    ORDER BY pp.display_order
";
$stmt = $conn->prepare($phases_query);
$stmt->bind_param('ii', $team_id, $team['program_id']);
$stmt->execute();
$phases_result = $stmt->get_result();
$phases = $phases_result->fetch_all(MYSQLI_ASSOC);

// Get current phase exercises
$current_phase_id = $team['current_phase_id'] ?? $phases[0]['id'];
$exercises_query = "
    SELECT
        pe.*,
        es.id as submission_id,
        es.status as submission_status,
        es.submitted_at,
        es.feedback
    FROM program_exercises pe
    LEFT JOIN exercise_submissions es ON pe.id = es.exercise_id
        AND es.team_id = ?
        AND es.version = (
            SELECT MAX(version) FROM exercise_submissions
            WHERE exercise_id = pe.id AND team_id = ?
        )
    WHERE pe.phase_id = ? AND pe.is_active = TRUE
    ORDER BY pe.display_order
";
$stmt = $conn->prepare($exercises_query);
$stmt->bind_param('iii', $team_id, $team_id, $current_phase_id);
$stmt->execute();
$exercises_result = $stmt->get_result();
$exercises = $exercises_result->fetch_all(MYSQLI_ASSOC);

// Get recent activity
$activity_query = "
    SELECT
        tal.*,
        u.full_name
    FROM team_activity_log tal
    JOIN users u ON tal.user_id = u.id
    WHERE tal.team_id = ?
    ORDER BY tal.created_at DESC
    LIMIT 10
";
$stmt = $conn->prepare($activity_query);
$stmt->bind_param('i', $team_id);
$stmt->execute();
$activity_result = $stmt->get_result();
$activities = $activity_result->fetch_all(MYSQLI_ASSOC);

closeDatabaseConnection($conn);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['team_name']); ?> - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .team-info h1 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .team-status {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .progress-overview {
            text-align: right;
        }

        .progress-bar-container {
            width: 300px;
            height: 20px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            height: 100%;
            background: white;
            transition: width 0.3s ease;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .phases-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .phase-tab {
            padding: 15px 25px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            text-decoration: none;
            color: #333;
        }

        .phase-tab:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .phase-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .phase-tab.completed {
            border-color: #28a745;
            background: #d4edda;
        }

        .exercise-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .exercise-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .exercise-item:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .exercise-info {
            flex: 1;
        }

        .exercise-number {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .exercise-title {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 5px;
        }

        .exercise-meta {
            font-size: 0.9rem;
            color: #666;
        }

        .exercise-status {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-not-started {
            background: #f0f0f0;
            color: #666;
        }

        .status-draft {
            background: #fff3cd;
            color: #856404;
        }

        .status-submitted {
            background: #cfe2ff;
            color: #084298;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-revision {
            background: #f8d7da;
            color: #721c24;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .team-members-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .member-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            color: #333;
        }

        .member-role {
            font-size: 0.85rem;
            color: #666;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }

        .activity-text {
            color: #333;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 1200px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .progress-overview {
                text-align: left;
            }

            .progress-bar-container {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/incubation-header.php'; ?>

    <div class="header">
        <div class="header-content">
            <div class="team-info">
                <h1><?php echo htmlspecialchars($team['team_name']); ?></h1>
                <div class="team-status">
                    <?php echo $lang === 'fr' ? 'Statut :' : 'Status:'; ?>
                    <?php echo ucfirst(str_replace('_', ' ', $team['status'])); ?>
                </div>
            </div>
            <div class="progress-overview">
                <div>
                    <?php echo $lang === 'fr' ? 'Progression globale' : 'Overall Progress'; ?>:
                    <strong><?php echo number_format($team['completion_percentage'], 1); ?>%</strong>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $team['completion_percentage']; ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="grid">
            <div class="main-content">
                <!-- Phases Navigation -->
                <div class="card">
                    <div class="card-title">
                        <?php echo $lang === 'fr' ? 'Phases du Programme' : 'Program Phases'; ?>
                    </div>

                    <div class="phases-nav">
                        <?php foreach ($phases as $phase): ?>
                            <?php
                            $completion = $phase['total_exercises'] > 0
                                ? ($phase['completed_exercises'] / $phase['total_exercises']) * 100
                                : 0;
                            $is_active = $phase['id'] == $current_phase_id;
                            $is_completed = $completion >= 100;
                            $class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                            ?>
                            <a href="?phase=<?php echo $phase['id']; ?>" class="phase-tab <?php echo $class; ?>">
                                <div>
                                    <strong><?php echo $lang === 'fr' ? $phase['phase_name_fr'] : $phase['phase_name']; ?></strong>
                                </div>
                                <div style="font-size: 0.85rem; margin-top: 5px;">
                                    <?php echo $phase['completed_exercises']; ?> / <?php echo $phase['total_exercises']; ?>
                                    <?php echo $lang === 'fr' ? 'exercices' : 'exercises'; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Exercises List -->
                <div class="card">
                    <div class="card-title">
                        <?php echo $lang === 'fr' ? 'Exercices' : 'Exercises'; ?>
                    </div>

                    <?php if (empty($exercises)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📝</div>
                            <p><?php echo $lang === 'fr' ? 'Aucun exercice dans cette phase.' : 'No exercises in this phase.'; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="exercise-list">
                            <?php foreach ($exercises as $exercise): ?>
                                <div class="exercise-item">
                                    <div class="exercise-info">
                                        <div class="exercise-number">
                                            <?php echo htmlspecialchars($exercise['exercise_number']); ?>
                                        </div>
                                        <div class="exercise-title">
                                            <?php echo $lang === 'fr' ? $exercise['exercise_title_fr'] : $exercise['exercise_title']; ?>
                                        </div>
                                        <div class="exercise-meta">
                                            ⏱️ <?php echo $exercise['duration_minutes']; ?> <?php echo $lang === 'fr' ? 'minutes' : 'minutes'; ?>
                                        </div>
                                    </div>
                                    <div class="exercise-status">
                                        <?php
                                        if (!$exercise['submission_id']) {
                                            $status_class = 'status-not-started';
                                            $status_text = $lang === 'fr' ? 'Non commencé' : 'Not Started';
                                        } else {
                                            switch ($exercise['submission_status']) {
                                                case 'draft':
                                                    $status_class = 'status-draft';
                                                    $status_text = $lang === 'fr' ? 'Brouillon' : 'Draft';
                                                    break;
                                                case 'submitted':
                                                    $status_class = 'status-submitted';
                                                    $status_text = $lang === 'fr' ? 'Soumis' : 'Submitted';
                                                    break;
                                                case 'approved':
                                                    $status_class = 'status-approved';
                                                    $status_text = $lang === 'fr' ? 'Approuvé' : 'Approved';
                                                    break;
                                                case 'revision_needed':
                                                    $status_class = 'status-revision';
                                                    $status_text = $lang === 'fr' ? 'Révision' : 'Needs Revision';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <a href="incubation-exercise.php?id=<?php echo $exercise['id']; ?>"
                                           class="btn btn-primary">
                                            <?php echo $lang === 'fr' ? 'Voir' : 'View'; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar">
                <!-- Team Members -->
                <div class="card">
                    <div class="card-title">
                        <?php echo $lang === 'fr' ? 'Membres de l\'équipe' : 'Team Members'; ?>
                        <span style="font-size: 1rem; font-weight: normal; color: #666;">
                            (<?php echo count($members); ?>)
                        </span>
                    </div>

                    <div class="team-members-list">
                        <?php foreach ($members as $member): ?>
                            <div class="member-item">
                                <div class="member-avatar">
                                    <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                                </div>
                                <div class="member-info">
                                    <div class="member-name">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </div>
                                    <div class="member-role">
                                        <?php echo ucfirst($member['role']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-title">
                        <?php echo $lang === 'fr' ? 'Activité récente' : 'Recent Activity'; ?>
                    </div>

                    <?php if (empty($activities)): ?>
                        <div class="empty-state" style="padding: 30px 20px;">
                            <p><?php echo $lang === 'fr' ? 'Aucune activité.' : 'No activity yet.'; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-text">
                                        <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
