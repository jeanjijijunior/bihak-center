<?php
/**
 * Incubation Program Dashboard V2
 * Enhanced with phase locking and progress persistence
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

// Get all phases with detailed completion info
$phases_query = "
    SELECT
        pp.*,
        COUNT(DISTINCT pe.id) as total_exercises,
        COUNT(DISTINCT CASE WHEN es.status = 'approved' THEN pe.id END) as completed_exercises,
        pc.completed_at as phase_completed_at
    FROM program_phases pp
    LEFT JOIN program_exercises pe ON pp.id = pe.phase_id AND pe.is_active = TRUE
    LEFT JOIN exercise_submissions es ON pe.id = es.exercise_id AND es.team_id = ? AND es.status = 'approved'
    LEFT JOIN phase_completions pc ON pp.id = pc.phase_id AND pc.team_id = ?
    WHERE pp.program_id = ? AND pp.is_active = TRUE
    GROUP BY pp.id
    ORDER BY pp.display_order
";
$stmt = $conn->prepare($phases_query);
$stmt->bind_param('iii', $team_id, $team_id, $team['program_id']);
$stmt->execute();
$phases_result = $stmt->get_result();
$phases = $phases_result->fetch_all(MYSQLI_ASSOC);

// Calculate which phases are unlocked
$unlocked_phases = [];
$current_unlocked_phase = null;

foreach ($phases as $index => $phase) {
    $is_completed = ($phase['completed_exercises'] >= $phase['total_exercises'] && $phase['total_exercises'] > 0);

    if ($index === 0) {
        // First phase always unlocked
        $unlocked_phases[$phase['id']] = true;
        if (!$is_completed) {
            $current_unlocked_phase = $phase['id'];
        }
    } else {
        // Check if previous phase is completed
        $prev_phase = $phases[$index - 1];
        $prev_completed = ($prev_phase['completed_exercises'] >= $prev_phase['total_exercises'] && $prev_phase['total_exercises'] > 0);

        if ($prev_completed) {
            $unlocked_phases[$phase['id']] = true;
            if (!$is_completed && $current_unlocked_phase === null) {
                $current_unlocked_phase = $phase['id'];
            }
        } else {
            $unlocked_phases[$phase['id']] = false;
        }
    }
}

// Determine current phase to display
$selected_phase_id = $_GET['phase'] ?? null;

// If no phase selected or invalid selection, show current unlocked phase
if (!$selected_phase_id || !isset($unlocked_phases[$selected_phase_id]) || !$unlocked_phases[$selected_phase_id]) {
    $selected_phase_id = $current_unlocked_phase ?? $phases[0]['id'];
}

// Update team's current phase if changed
if ($team['current_phase_id'] != $selected_phase_id) {
    $update_team = "UPDATE incubation_teams SET current_phase_id = ? WHERE id = ?";
    $stmt = $conn->prepare($update_team);
    $stmt->bind_param('ii', $selected_phase_id, $team_id);
    $stmt->execute();
}

// Get exercises for selected phase
$exercises_query = "
    SELECT
        pe.*,
        es.id as submission_id,
        es.status as submission_status,
        es.submitted_at,
        es.feedback,
        es.submission_text,
        es.file_name
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
$stmt->bind_param('iii', $team_id, $team_id, $selected_phase_id);
$stmt->execute();
$exercises_result = $stmt->get_result();
$exercises = $exercises_result->fetch_all(MYSQLI_ASSOC);

// Calculate overall completion
$total_exercises_all = 0;
$completed_exercises_all = 0;
foreach ($phases as $p) {
    $total_exercises_all += $p['total_exercises'];
    $completed_exercises_all += $p['completed_exercises'];
}
$overall_completion = $total_exercises_all > 0 ? ($completed_exercises_all / $total_exercises_all) * 100 : 0;

// Update team completion percentage
$update_completion = "UPDATE incubation_teams SET completion_percentage = ? WHERE id = ?";
$stmt = $conn->prepare($update_completion);
$stmt->bind_param('di', $overall_completion, $team_id);
$stmt->execute();

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

// Get current phase info
$current_phase = null;
foreach ($phases as $p) {
    if ($p['id'] == $selected_phase_id) {
        $current_phase = $p;
        break;
    }
}
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
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
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

        .phase-progress-banner {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .phase-progress-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
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
            position: relative;
            min-width: 180px;
        }

        .phase-tab.locked {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }

        .phase-tab.locked::after {
            content: '🔒';
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }

        .phase-tab:not(.locked):hover {
            border-color: #1cabe2;
            background: #f8f9ff;
        }

        .phase-tab.active {
            background: #1cabe2;
            color: white;
            border-color: #1cabe2;
        }

        .phase-tab.completed {
            border-color: #28a745;
            background: #d4edda;
        }

        .phase-tab.completed::before {
            content: '✓';
            display: inline-block;
            margin-right: 8px;
            font-weight: 700;
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

        .phase-info-box {
            background: #f8f9ff;
            border-left: 4px solid #1cabe2;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .phase-info-box h3 {
            color: #1cabe2;
            margin-bottom: 10px;
        }

        .phase-info-box p {
            color: #666;
            line-height: 1.6;
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
            border-color: #1cabe2;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .exercise-info {
            flex: 1;
        }

        .exercise-number {
            font-weight: 700;
            color: #1cabe2;
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
            background: #1cabe2;
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
            background: #1cabe2;
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
            border-left: 4px solid #1cabe2;
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

        .completion-message {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 20px;
        }

        .completion-message h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .locked-message {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
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
                width: 100%;
            }

            .progress-bar-container {
                width: 100%;
            }
        }
    </style>
</head>
<body>
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
                    <strong><?php echo number_format($overall_completion, 1); ?>%</strong>
                    (<?php echo $completed_exercises_all; ?> / <?php echo $total_exercises_all; ?>
                    <?php echo $lang === 'fr' ? 'exercices' : 'exercises'; ?>)
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $overall_completion; ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Phase Navigation -->
        <div class="phase-progress-banner">
            <div class="phase-progress-title">
                <?php echo $lang === 'fr' ? '📚 Phases du Programme' : '📚 Program Phases'; ?>
            </div>

            <div class="phases-nav">
                <?php foreach ($phases as $phase): ?>
                    <?php
                    $completion = $phase['total_exercises'] > 0
                        ? ($phase['completed_exercises'] / $phase['total_exercises']) * 100
                        : 0;
                    $is_active = $phase['id'] == $selected_phase_id;
                    $is_completed = $completion >= 100;
                    $is_locked = !($unlocked_phases[$phase['id']] ?? false);

                    $class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                    if ($is_locked) $class .= ' locked';
                    ?>
                    <?php if ($is_locked): ?>
                        <div class="phase-tab <?php echo $class; ?>">
                            <div>
                                <strong><?php echo $lang === 'fr' ? $phase['phase_name_fr'] : $phase['phase_name']; ?></strong>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 5px;">
                                <?php echo $phase['completed_exercises']; ?> / <?php echo $phase['total_exercises']; ?>
                                <?php echo $lang === 'fr' ? 'exercices' : 'exercises'; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="?phase=<?php echo $phase['id']; ?>" class="phase-tab <?php echo $class; ?>">
                            <div>
                                <strong><?php echo $lang === 'fr' ? $phase['phase_name_fr'] : $phase['phase_name']; ?></strong>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 5px;">
                                <?php echo $phase['completed_exercises']; ?> / <?php echo $phase['total_exercises']; ?>
                                <?php echo $lang === 'fr' ? 'exercices' : 'exercises'; ?>
                            </div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid">
            <div class="main-content">
                <!-- Current Phase Info -->
                <?php if ($current_phase): ?>
                    <?php
                    $phase_completion = $current_phase['total_exercises'] > 0
                        ? ($current_phase['completed_exercises'] / $current_phase['total_exercises']) * 100
                        : 0;
                    $is_phase_complete = $phase_completion >= 100;
                    ?>

                    <?php if ($is_phase_complete): ?>
                        <div class="completion-message">
                            <h3>🎉 <?php echo $lang === 'fr' ? 'Phase Terminée !' : 'Phase Completed!'; ?></h3>
                            <p><?php echo $lang === 'fr' ? 'Félicitations ! Vous avez terminé cette phase.' : 'Congratulations! You have completed this phase.'; ?></p>
                            <?php
                            // Find next phase
                            $next_phase = null;
                            foreach ($phases as $p) {
                                if ($p['display_order'] > $current_phase['display_order'] && ($unlocked_phases[$p['id']] ?? false)) {
                                    $next_phase = $p;
                                    break;
                                }
                            }
                            if ($next_phase): ?>
                                <a href="?phase=<?php echo $next_phase['id']; ?>" class="btn btn-primary" style="margin-top: 15px;">
                                    <?php echo $lang === 'fr' ? '➡️ Continuer à la prochaine phase' : '➡️ Continue to Next Phase'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="phase-info-box">
                            <h3><?php echo $lang === 'fr' ? $current_phase['phase_name_fr'] : $current_phase['phase_name']; ?></h3>
                            <p><?php echo $lang === 'fr' ? $current_phase['description_fr'] : $current_phase['description']; ?></p>
                        </div>

                        <div class="card-title">
                            <?php echo $lang === 'fr' ? 'Exercices' : 'Exercises'; ?>
                            <span style="font-size: 1rem; font-weight: normal; color: #666;">
                                <?php echo $current_phase['completed_exercises']; ?> / <?php echo $current_phase['total_exercises']; ?>
                                <?php echo $lang === 'fr' ? 'terminés' : 'completed'; ?>
                            </span>
                        </div>

                        <?php if (empty($exercises)): ?>
                            <div style="text-align: center; padding: 60px 20px; color: #666;">
                                <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;">📝</div>
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
                <?php endif; ?>
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
                                        <?php if ($member['id'] == $user_id): ?>
                                            <span style="color: #1cabe2; font-size: 0.85rem;">(<?php echo $lang === 'fr' ? 'Vous' : 'You'; ?>)</span>
                                        <?php endif; ?>
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
                        <div style="text-align: center; padding: 30px 20px; color: #666;">
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
