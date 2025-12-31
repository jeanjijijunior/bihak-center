<?php
/**
 * Incubation Program - Review Submission (Enhanced)
 * Admin interface for reviewing exercise submissions with rubrics, history, and file preview
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

$submission_id = $_GET['id'] ?? 0;

// Get submission details
$submission_query = "
    SELECT
        es.*,
        ie.exercise_number,
        ie.exercise_title,
        ie.instructions,
        ie.exercise_type,
        ip.phase_name,
        t.team_name,
        u.full_name as submitted_by_name,
        u.email as submitted_by_email,
        reviewer.full_name as reviewed_by_name
    FROM exercise_submissions es
    JOIN incubation_exercises ie ON es.exercise_id = ie.id
    JOIN incubation_phases ip ON ie.phase_id = ip.id
    JOIN incubation_teams t ON es.team_id = t.id
    JOIN users u ON es.submitted_by = u.id
    LEFT JOIN admins reviewer ON es.reviewed_by = reviewer.id
    WHERE es.id = ?
";
$stmt = $conn->prepare($submission_query);
$stmt->bind_param('i', $submission_id);
$stmt->execute();
$submission_result = $stmt->get_result();
$submission = $submission_result->fetch_assoc();

if (!$submission) {
    header('Location: incubation-reviews.php');
    exit;
}

// Get submission history (previous versions)
$history_query = "
    SELECT es.*, u.full_name as submitted_by_name, reviewer.full_name as reviewed_by_name
    FROM exercise_submissions es
    JOIN users u ON es.submitted_by = u.id
    LEFT JOIN admins reviewer ON es.reviewed_by = reviewer.id
    WHERE es.team_id = ? AND es.exercise_id = ? AND es.id != ?
    ORDER BY es.version DESC, es.submitted_at DESC
    LIMIT 10
";
$stmt = $conn->prepare($history_query);
$stmt->bind_param('iii', $submission['team_id'], $submission['exercise_id'], $submission_id);
$stmt->execute();
$history_result = $stmt->get_result();
$submission_history = $history_result->fetch_all(MYSQLI_ASSOC);

$admin = Auth::user();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'submitted';
    $feedback = trim($_POST['feedback'] ?? '');

    // Get scoring rubric data if provided
    $completeness_score = isset($_POST['completeness']) ? intval($_POST['completeness']) : null;
    $quality_score = isset($_POST['quality']) ? intval($_POST['quality']) : null;
    $clarity_score = isset($_POST['clarity']) ? intval($_POST['clarity']) : null;
    $innovation_score = isset($_POST['innovation']) ? intval($_POST['innovation']) : null;

    // Calculate overall score (average of provided scores)
    $scores = array_filter([$completeness_score, $quality_score, $clarity_score, $innovation_score]);
    $overall_score = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : null;

    $conn->begin_transaction();

    try {
        // Update submission
        $update_query = "
            UPDATE exercise_submissions
            SET status = ?, feedback = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ssii', $status, $feedback, $admin['id'], $submission_id);
        $stmt->execute();

        // Update or insert metrics with scores
        $metrics_query = "
            INSERT INTO incubation_exercise_metrics
            (team_id, exercise_id, completeness_score, quality_score, updated_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                completeness_score = VALUES(completeness_score),
                quality_score = VALUES(quality_score),
                updated_at = NOW()
        ";
        $stmt = $conn->prepare($metrics_query);
        $stmt->bind_param('iiii',
            $submission['team_id'],
            $submission['exercise_id'],
            $overall_score,
            $quality_score
        );
        $stmt->execute();

        // If approved, update team_exercise_progress
        if ($status === 'approved') {
            $progress_query = "
                INSERT INTO team_exercise_progress
                (team_id, exercise_id, status, started_at, completed_at, reviewed_at, reviewed_by)
                VALUES (?, ?, 'completed', NOW(), NOW(), NOW(), ?)
                ON DUPLICATE KEY UPDATE
                    status = 'completed',
                    completed_at = NOW(),
                    reviewed_at = NOW(),
                    reviewed_by = VALUES(reviewed_by)
            ";
            $stmt = $conn->prepare($progress_query);
            $stmt->bind_param('iii', $submission['team_id'], $submission['exercise_id'], $admin['id']);
            $stmt->execute();

            // Update team completion percentage
            $total_exercises_query = "SELECT COUNT(*) as total FROM incubation_exercises WHERE is_active = 1 AND is_required = 1";
            $total_result = $conn->query($total_exercises_query);
            $total_exercises = $total_result->fetch_assoc()['total'];

            $completed_query = "
                SELECT COUNT(*) as completed
                FROM team_exercise_progress
                WHERE team_id = ? AND status = 'completed'
            ";
            $stmt = $conn->prepare($completed_query);
            $stmt->bind_param('i', $submission['team_id']);
            $stmt->execute();
            $completed_result = $stmt->get_result();
            $completed_exercises = $completed_result->fetch_assoc()['completed'];

            $completion_percentage = ($total_exercises > 0) ? ($completed_exercises / $total_exercises) * 100 : 0;

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
            $stmt->bind_param('dddi', $completion_percentage, $completion_percentage, $completion_percentage, $submission['team_id']);
            $stmt->execute();
        }

        $conn->commit();
        header('Location: incubation-reviews.php?reviewed=1');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log('Error reviewing submission: ' . $e->getMessage());
        $error = 'Failed to submit review. Please try again.';
    }
}

closeDatabaseConnection($conn);

$page_title = 'Review Submission';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .back-button {
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

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        .review-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }

        .main-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .submission-header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .submission-header h1 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 1.75rem;
        }

        .submission-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .meta-item {
            padding: 10px;
            background: #f9fafb;
            border-radius: 6px;
        }

        .meta-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .meta-value {
            font-weight: 600;
            color: #1f2937;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            color: #1f2937;
            margin-bottom: 15px;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section h3 {
            color: #374151;
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        .instructions-box {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .submission-content {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .file-list {
            list-style: none;
            margin-top: 10px;
        }

        .file-list li {
            padding: 12px;
            background: white;
            margin-bottom: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .file-list li:hover {
            border-color: #1cabe2;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.1);
        }

        .file-list a {
            color: #1cabe2;
            text-decoration: none;
            font-weight: 500;
            flex: 1;
        }

        .file-list a:hover {
            text-decoration: underline;
        }

        .file-preview-btn {
            padding: 4px 12px;
            background: #1cabe2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .file-preview-btn:hover {
            background: #4f46e5;
        }

        .review-form {
            background: #f9fafb;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1cabe2;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .rubric-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
        }

        .rubric-section h3 {
            margin-bottom: 15px;
            color: #1f2937;
        }

        .rubric-item {
            margin-bottom: 15px;
        }

        .rubric-item label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .score-display {
            color: #1cabe2;
            font-weight: 700;
        }

        .score-slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            outline: none;
            -webkit-appearance: none;
        }

        .score-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #1cabe2;
            cursor: pointer;
        }

        .score-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #1cabe2;
            cursor: pointer;
            border: none;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-info {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .history-list {
            list-style: none;
        }

        .history-item {
            padding: 12px;
            background: #f9fafb;
            margin-bottom: 8px;
            border-radius: 6px;
            border-left: 3px solid #1cabe2;
        }

        .history-item .version {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .history-item .date {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .history-item .status-badge {
            font-size: 0.75rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .stat-box {
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1cabe2;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .quick-action-btn {
            padding: 10px 15px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s;
            color: #374151;
            font-weight: 500;
        }

        .quick-action-btn:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }

        .quick-action-btn i {
            margin-right: 8px;
            color: #1cabe2;
        }

        @media (max-width: 1200px) {
            .review-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: 2;
            }
        }

        /* File preview modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            position: relative;
            background: white;
            margin: 50px auto;
            padding: 20px;
            width: 90%;
            max-width: 900px;
            border-radius: 12px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="incubation-reviews.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Reviews
        </a>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="review-layout">
            <!-- Main Content -->
            <div class="main-content">
                <div class="submission-header">
                    <h1><i class="fas fa-clipboard-check"></i> Review Exercise Submission</h1>
                    <div class="submission-meta">
                        <div class="meta-item">
                            <div class="meta-label">Team</div>
                            <div class="meta-value"><?php echo htmlspecialchars($submission['team_name']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Exercise</div>
                            <div class="meta-value">#<?php echo $submission['exercise_number']; ?> - <?php echo htmlspecialchars($submission['exercise_title']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Phase</div>
                            <div class="meta-value"><span class="badge badge-info"><?php echo htmlspecialchars($submission['phase_name']); ?></span></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Type</div>
                            <div class="meta-value"><?php echo htmlspecialchars($submission['exercise_type'] ?? 'Standard'); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Submitted By</div>
                            <div class="meta-value"><?php echo htmlspecialchars($submission['submitted_by_name']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Submitted At</div>
                            <div class="meta-value"><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Current Status</div>
                            <div class="meta-value">
                                <span class="badge <?php
                                    echo $submission['status'] === 'approved' ? 'badge-success' :
                                         ($submission['status'] === 'revision_needed' ? 'badge-warning' : 'badge-info');
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($submission['reviewed_at']): ?>
                        <div class="meta-item">
                            <div class="meta-label">Reviewed By</div>
                            <div class="meta-value"><?php echo htmlspecialchars($submission['reviewed_by_name'] ?? 'Admin'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Exercise Instructions -->
                <div class="section">
                    <h2><i class="fas fa-tasks"></i> Exercise Instructions</h2>
                    <div class="instructions-box">
                        <?php echo nl2br(htmlspecialchars($submission['instructions'])); ?>
                    </div>
                </div>

                <!-- Submission Content -->
                <div class="section">
                    <h2><i class="fas fa-file-alt"></i> Submission</h2>
                    <div class="submission-content">
                        <p><strong>Submission Text:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($submission['submission_text'] ?? 'No text provided')); ?></p>

                        <?php if ($submission['file_path']): ?>
                            <p style="margin-top: 20px;"><strong>Attached Files:</strong></p>
                            <ul class="file-list">
                                <?php
                                $files = explode(',', $submission['file_path']);
                                foreach ($files as $file):
                                    $file = trim($file);
                                    if ($file):
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        $icon = 'fa-file';
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'fa-image';
                                        elseif ($ext === 'pdf') $icon = 'fa-file-pdf';
                                        elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                                        elseif (in_array($ext, ['xls', 'xlsx'])) $icon = 'fa-file-excel';
                                ?>
                                    <li>
                                        <i class="fas <?php echo $icon; ?>" style="color: #1cabe2;"></i>
                                        <a href="../../<?php echo htmlspecialchars($file); ?>" target="_blank">
                                            <?php echo basename($file); ?>
                                        </a>
                                        <button class="file-preview-btn" onclick="previewFile('../../<?php echo htmlspecialchars($file); ?>')">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                    </li>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Previous Feedback (if any) -->
                <?php if ($submission['feedback']): ?>
                <div class="section">
                    <h2><i class="fas fa-comment-dots"></i> Previous Feedback</h2>
                    <div class="submission-content">
                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Review Form -->
                <div class="section">
                    <h2><i class="fas fa-pen"></i> Your Review</h2>
                    <form method="POST" class="review-form">
                        <!-- Scoring Rubric -->
                        <div class="rubric-section">
                            <h3><i class="fas fa-star"></i> Scoring Rubric (Optional)</h3>

                            <div class="rubric-item">
                                <label>
                                    <span>Completeness</span>
                                    <span class="score-display" id="completeness-value">5</span>
                                </label>
                                <input type="range" name="completeness" class="score-slider" min="1" max="10" value="5"
                                       oninput="document.getElementById('completeness-value').textContent = this.value">
                            </div>

                            <div class="rubric-item">
                                <label>
                                    <span>Quality</span>
                                    <span class="score-display" id="quality-value">5</span>
                                </label>
                                <input type="range" name="quality" class="score-slider" min="1" max="10" value="5"
                                       oninput="document.getElementById('quality-value').textContent = this.value">
                            </div>

                            <div class="rubric-item">
                                <label>
                                    <span>Clarity</span>
                                    <span class="score-display" id="clarity-value">5</span>
                                </label>
                                <input type="range" name="clarity" class="score-slider" min="1" max="10" value="5"
                                       oninput="document.getElementById('clarity-value').textContent = this.value">
                            </div>

                            <div class="rubric-item">
                                <label>
                                    <span>Innovation</span>
                                    <span class="score-display" id="innovation-value">5</span>
                                </label>
                                <input type="range" name="innovation" class="score-slider" min="1" max="10" value="5"
                                       oninput="document.getElementById('innovation-value').textContent = this.value">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status"><i class="fas fa-check-circle"></i> Decision *</label>
                            <select name="status" id="status" required>
                                <option value="">-- Select Decision --</option>
                                <option value="approved">✓ Approve</option>
                                <option value="revision_needed">⚠ Request Revision</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="feedback"><i class="fas fa-comment"></i> Feedback *</label>
                            <textarea name="feedback" id="feedback" placeholder="Provide detailed feedback to the team..." required><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Submit Review
                            </button>
                            <a href="incubation-reviews.php" class="btn btn-warning">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Quick Stats -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-chart-line"></i> Quick Stats</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $submission['version'] ?? 1; ?></div>
                            <div class="stat-label">Version</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo count($submission_history); ?></div>
                            <div class="stat-label">Previous Versions</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        <button class="quick-action-btn" onclick="window.open('incubation-team-detail.php?id=<?php echo $submission['team_id']; ?>', '_blank')">
                            <i class="fas fa-users"></i> View Team Details
                        </button>
                        <button class="quick-action-btn" onclick="document.getElementById('feedback').value = 'Great work! Approved.'">
                            <i class="fas fa-check"></i> Quick Approve Text
                        </button>
                        <button class="quick-action-btn" onclick="document.getElementById('feedback').value = 'Please revise and resubmit with the following improvements:\n\n1. \n2. \n3. '">
                            <i class="fas fa-edit"></i> Quick Revision Text
                        </button>
                    </div>
                </div>

                <!-- Submission History -->
                <?php if (count($submission_history) > 0): ?>
                <div class="sidebar-card">
                    <h3><i class="fas fa-history"></i> Submission History</h3>
                    <ul class="history-list">
                        <?php foreach ($submission_history as $hist): ?>
                        <li class="history-item">
                            <div class="version">Version <?php echo $hist['version']; ?></div>
                            <div class="date">
                                <?php echo $hist['submitted_at'] ? date('M d, Y H:i', strtotime($hist['submitted_at'])) : 'Draft'; ?>
                            </div>
                            <span class="badge status-badge <?php
                                echo $hist['status'] === 'approved' ? 'badge-success' :
                                     ($hist['status'] === 'revision_needed' ? 'badge-warning' : 'badge-info');
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $hist['status'])); ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- File Preview Modal -->
    <div id="filePreviewModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePreview()">&times;</span>
            <div id="previewContent"></div>
        </div>
    </div>

    <script>
        function previewFile(filePath) {
            const modal = document.getElementById('filePreviewModal');
            const content = document.getElementById('previewContent');
            const ext = filePath.split('.').pop().toLowerCase();

            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                content.innerHTML = '<img src="' + filePath + '" style="max-width: 100%; height: auto;">';
            } else if (ext === 'pdf') {
                content.innerHTML = '<iframe src="' + filePath + '" style="width: 100%; height: 70vh; border: none;"></iframe>';
            } else {
                content.innerHTML = '<p>Preview not available for this file type. <a href="' + filePath + '" target="_blank">Download file</a></p>';
            }

            modal.style.display = 'block';
        }

        function closePreview() {
            document.getElementById('filePreviewModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('filePreviewModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
