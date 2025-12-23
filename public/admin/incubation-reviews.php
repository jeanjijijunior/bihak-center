<?php
/**
 * Admin - Review Exercise Submissions
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

Auth::requireAuth();
$admin = Auth::user();

$conn = getDatabaseConnection();

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submission'])) {
    $submission_id = $_POST['submission_id'] ?? 0;
    $status = $_POST['status'] ?? 'submitted';
    $feedback = trim($_POST['feedback'] ?? '');

    if ($submission_id) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Update submission status
            $update_query = "
                UPDATE exercise_submissions
                SET status = ?, feedback = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ssii', $status, $feedback, $admin['id'], $submission_id);
            $stmt->execute();

            // Get submission details
            $submission_query = "SELECT team_id, exercise_id FROM exercise_submissions WHERE id = ?";
            $stmt = $conn->prepare($submission_query);
            $stmt->bind_param('i', $submission_id);
            $stmt->execute();
            $submission_result = $stmt->get_result();
            $submission_data = $submission_result->fetch_assoc();
            $team_id = $submission_data['team_id'];
            $exercise_id = $submission_data['exercise_id'];

            // If approved, update team_exercise_progress and team completion percentage
            if ($status === 'approved') {
                // Update team_exercise_progress to 'completed'
                $progress_query = "
                    UPDATE team_exercise_progress
                    SET status = 'completed', reviewed_at = NOW(), reviewed_by = ?
                    WHERE team_id = ? AND exercise_id = ?
                ";
                $stmt = $conn->prepare($progress_query);
                $stmt->bind_param('iii', $admin['id'], $team_id, $exercise_id);
                $stmt->execute();

                // Calculate and update team completion percentage
                // Get total number of exercises
                $total_exercises_query = "
                    SELECT COUNT(*) as total
                    FROM incubation_exercises
                    WHERE is_active = 1 AND is_required = 1
                ";
                $total_result = $conn->query($total_exercises_query);
                $total_exercises = $total_result->fetch_assoc()['total'];

                // Get completed exercises count for this team
                $completed_query = "
                    SELECT COUNT(*) as completed
                    FROM team_exercise_progress
                    WHERE team_id = ? AND status = 'completed'
                ";
                $stmt = $conn->prepare($completed_query);
                $stmt->bind_param('i', $team_id);
                $stmt->execute();
                $completed_result = $stmt->get_result();
                $completed_exercises = $completed_result->fetch_assoc()['completed'];

                // Calculate percentage
                $completion_percentage = ($total_exercises > 0) ? ($completed_exercises / $total_exercises) * 100 : 0;

                // Update team completion percentage
                $update_team_query = "
                    UPDATE incubation_teams
                    SET completion_percentage = ?,
                        status = CASE
                            WHEN ? >= 100 THEN 'completed'
                            WHEN ? > 0 THEN 'in_progress'
                            ELSE status
                        END
                    WHERE id = ?
                ";
                $stmt = $conn->prepare($update_team_query);
                $stmt->bind_param('dddi', $completion_percentage, $completion_percentage, $completion_percentage, $team_id);
                $stmt->execute();

                // Log activity
                $log_query = "
                    INSERT INTO team_activity_log (team_id, user_id, activity_type, entity_type, entity_id, description)
                    SELECT team_id, submitted_by, 'exercise_approved', 'exercise', exercise_id,
                           CONCAT('Exercise submission approved by admin')
                    FROM exercise_submissions WHERE id = ?
                ";
                $stmt = $conn->prepare($log_query);
                $stmt->bind_param('i', $submission_id);
                $stmt->execute();
            }

            $conn->commit();
            header('Location: incubation-reviews.php?reviewed=1');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            error_log('Error reviewing submission: ' . $e->getMessage());
            header('Location: incubation-reviews.php?error=1');
            exit;
        }
    }
}

// Get filter
$status_filter = $_GET['status'] ?? 'submitted';

// Get submissions
$submissions_query = "
    SELECT
        es.*,
        ie.exercise_number,
        ie.exercise_title,
        ie.exercise_title_fr,
        ie.exercise_type as deliverable_type,
        ip.phase_name,
        ip.phase_name_fr,
        t.team_name,
        tm.user_id as submitter_id
    FROM exercise_submissions es
    JOIN incubation_exercises ie ON es.exercise_id = ie.id
    JOIN incubation_phases ip ON ie.phase_id = ip.id
    JOIN incubation_teams t ON es.team_id = t.id
    LEFT JOIN incubation_team_members tm ON t.id = tm.team_id AND tm.role = 'leader'
    WHERE es.status = ?
    ORDER BY es.submitted_at DESC
";
$stmt = $conn->prepare($submissions_query);
$stmt->bind_param('s', $status_filter);
$stmt->execute();
$submissions_result = $stmt->get_result();
$submissions = $submissions_result->fetch_all(MYSQLI_ASSOC);

// Enrich submissions with submitter details
foreach ($submissions as &$submission) {
    if ($submission['submitted_by']) {
        $user_query = "SELECT full_name, email FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bind_param('i', $submission['submitted_by']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $submission['submitter_name'] = $user_data['full_name'];
            $submission['submitter_email'] = $user_data['email'];
        } else {
            $submission['submitter_name'] = 'Unknown';
            $submission['submitter_email'] = '';
        }
    } else {
        $submission['submitter_name'] = 'Unknown';
        $submission['submitter_email'] = '';
    }
}
unset($submission); // Break reference

// Get counts for each status
$counts_query = "
    SELECT
        status,
        COUNT(*) as count
    FROM exercise_submissions
    GROUP BY status
";
$counts_result = $conn->query($counts_query);
$counts = [];
while ($row = $counts_result->fetch_assoc()) {
    $counts[$row['status']] = $row['count'];
}

closeDatabaseConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submissions - Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="../../assets/images/logob.png">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            padding: 15px 25px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab:hover {
            color: #333;
        }

        .tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 8px;
            background: #e0e0e0;
            color: #666;
        }

        .tab.active .badge {
            background: #6366f1;
            color: white;
        }

        .submissions-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .submission-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .submission-info h3 {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 8px;
        }

        .submission-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #666;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
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
            background: #d1fae5;
            color: #065f46;
        }

        .status-revision {
            background: #f8d7da;
            color: #721c24;
        }

        .submission-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .file-attachment {
            background: #e3f2fd;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .file-attachment a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 600;
        }

        .review-form {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #10b981;
            color: white;
        }

        .btn-primary:hover {
            background: #059669;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <?php include __DIR__ . '/includes/admin-header.php'; ?>

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/includes/admin-sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="dashboard-container">
        <div class="page-header">
            <div>
                <h1>Review Submissions</h1>
                <p>Review and provide feedback on team exercise submissions</p>
            </div>
        </div>

        <div class="tabs">
            <a href="?status=submitted" class="tab <?php echo $status_filter === 'submitted' ? 'active' : ''; ?>">
                Pending Review
                <span class="badge"><?php echo $counts['submitted'] ?? 0; ?></span>
            </a>
            <a href="?status=approved" class="tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                Approved
                <span class="badge"><?php echo $counts['approved'] ?? 0; ?></span>
            </a>
            <a href="?status=revision_needed" class="tab <?php echo $status_filter === 'revision_needed' ? 'active' : ''; ?>">
                Needs Revision
                <span class="badge"><?php echo $counts['revision_needed'] ?? 0; ?></span>
            </a>
            <a href="?status=draft" class="tab <?php echo $status_filter === 'draft' ? 'active' : ''; ?>">
                Drafts
                <span class="badge"><?php echo $counts['draft'] ?? 0; ?></span>
            </a>
        </div>

        <?php if (empty($submissions)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h2>No Submissions</h2>
                <p>No submissions with status "<?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?>"</p>
            </div>
        <?php else: ?>
            <div class="submissions-list">
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission-card">
                        <div class="submission-header">
                            <div class="submission-info">
                                <h3>
                                    <?php echo htmlspecialchars($submission['exercise_number']); ?>:
                                    <?php echo htmlspecialchars($submission['exercise_title']); ?>
                                </h3>
                                <div class="submission-meta">
                                    <div class="meta-item">
                                        <strong>Team:</strong>
                                        <?php echo htmlspecialchars($submission['team_name']); ?>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Submitted by:</strong>
                                        <?php echo htmlspecialchars($submission['submitter_name']); ?>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Date:</strong>
                                        <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                                    </div>
                                    <div class="meta-item">
                                        <strong>Phase:</strong>
                                        <?php echo htmlspecialchars($submission['phase_name']); ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <?php
                                $status_class = 'status-' . str_replace('_', '-', $submission['status']);
                                $status_labels = [
                                    'draft' => 'Draft',
                                    'submitted' => 'Submitted',
                                    'approved' => 'Approved',
                                    'revision_needed' => 'Needs Revision'
                                ];
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_labels[$submission['status']]; ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($submission['submission_text']): ?>
                            <div>
                                <strong style="display: block; margin-bottom: 10px;">Submission Text:</strong>
                                <div class="submission-content">
                                    <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($submission['file_path']): ?>
                            <?php
                            $files = explode(',', $submission['file_path']);
                            $file_names = explode(',', $submission['file_name']);
                            foreach ($files as $index => $file):
                                $file = trim($file);
                                if ($file):
                                    $file_name = isset($file_names[$index]) ? trim($file_names[$index]) : basename($file);
                            ?>
                                <div class="file-attachment">
                                    <span style="font-size: 2rem;">📎</span>
                                    <div style="flex: 1;">
                                        <div><strong><?php echo htmlspecialchars($file_name); ?></strong></div>
                                    </div>
                                    <a href="../../<?php echo $file; ?>" target="_blank" download>
                                        Download
                                    </a>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        <?php endif; ?>

                        <?php if ($submission['feedback']): ?>
                            <div>
                                <strong style="display: block; margin-bottom: 10px;">Previous Feedback:</strong>
                                <div class="submission-content" style="background: #fff3cd;">
                                    <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($submission['status'] === 'submitted'): ?>
                            <form method="POST" class="review-form">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">

                                <div class="form-group">
                                    <label>Decision</label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="status" value="approved" required>
                                            <span>✅ Approve</span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="status" value="revision_needed">
                                            <span>🔄 Request Revision</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="feedback_<?php echo $submission['id']; ?>">Feedback</label>
                                    <textarea name="feedback" id="feedback_<?php echo $submission['id']; ?>"
                                              placeholder="Provide detailed feedback to the team..."></textarea>
                                </div>

                                <div class="button-group">
                                    <button type="submit" name="review_submission" class="btn btn-primary">
                                        Submit Review
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </main>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
