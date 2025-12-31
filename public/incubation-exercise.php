<?php
/**
 * Individual Exercise Page
 * View instructions, submit work, and track progress
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
$exercise_id = $_GET['id'] ?? 0;

if (!$exercise_id) {
    header('Location: incubation-dashboard.php');
    exit;
}

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
$team = $stmt->get_result()->fetch_assoc();

if (!$team) {
    header('Location: incubation-team-create.php');
    exit;
}

$team_id = $team['id'];

// Get exercise details
$exercise_query = "
    SELECT
        ie.*,
        ip.phase_name,
        ip.phase_name_fr
    FROM incubation_exercises ie
    JOIN incubation_phases ip ON ie.phase_id = ip.id
    WHERE ie.id = ? AND ie.is_active = TRUE
";
$stmt = $conn->prepare($exercise_query);
$stmt->bind_param('i', $exercise_id);
$stmt->execute();
$exercise_result = $stmt->get_result();
$exercise = $exercise_result->fetch_assoc();

if (!$exercise) {
    die("Exercise not found");
}

// Check if previous exercise is completed (sequential locking)
$is_locked = false;
$lock_message = '';

if ($exercise['exercise_number'] > 1) {
    // Get previous exercise
    $prev_exercise_query = "
        SELECT ie.id, ie.exercise_number, ie.exercise_title
        FROM incubation_exercises ie
        WHERE ie.exercise_number = ? AND ie.is_active = TRUE
        LIMIT 1
    ";
    $stmt = $conn->prepare($prev_exercise_query);
    $prev_number = $exercise['exercise_number'] - 1;
    $stmt->bind_param('i', $prev_number);
    $stmt->execute();
    $prev_exercise = $stmt->get_result()->fetch_assoc();

    if ($prev_exercise) {
        // Check if previous exercise is completed
        // Check both team_exercise_progress AND exercise_submissions (for backwards compatibility)
        $prev_completion_query = "
            SELECT
                COALESCE(tep.status, 'not_started') as progress_status,
                COALESCE(MAX(es.status), 'none') as submission_status
            FROM (SELECT ? as team_id, ? as exercise_id) AS base
            LEFT JOIN team_exercise_progress tep ON tep.team_id = base.team_id AND tep.exercise_id = base.exercise_id
            LEFT JOIN exercise_submissions es ON es.team_id = base.team_id AND es.exercise_id = base.exercise_id
        ";
        $stmt = $conn->prepare($prev_completion_query);
        $stmt->bind_param('ii', $team_id, $prev_exercise['id']);
        $stmt->execute();
        $prev_progress = $stmt->get_result()->fetch_assoc();

        // Exercise is completed if either:
        // 1. team_exercise_progress status is 'completed', OR
        // 2. exercise_submissions has status 'approved'
        $is_completed = ($prev_progress['progress_status'] === 'completed') ||
                       ($prev_progress['submission_status'] === 'approved');

        if (!$is_completed) {
            $is_locked = true;
            $lock_message = $lang === 'fr'
                ? "Vous devez d'abord terminer l'exercice #{$prev_number}: {$prev_exercise['exercise_title']}"
                : "You must first complete Exercise #{$prev_number}: {$prev_exercise['exercise_title']}";
        }
    }
}

// Get latest submission
$submission_query = "
    SELECT *
    FROM exercise_submissions
    WHERE team_id = ? AND exercise_id = ?
    ORDER BY version DESC
    LIMIT 1
";
$stmt = $conn->prepare($submission_query);
$stmt->bind_param('ii', $team_id, $exercise_id);
$stmt->execute();
$submission_result = $stmt->get_result();
$submission = $submission_result->fetch_assoc();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Prevent submission if exercise is locked
    if ($is_locked) {
        $error_message = $lock_message;
        goto skip_submission;
    }

    if ($action === 'save_draft' || $action === 'submit') {
        $submission_text = trim($_POST['submission_text'] ?? '');
        $version = ($submission['version'] ?? 0) + 1;
        $status = ($action === 'submit') ? 'submitted' : 'draft';

        // Check if files are required and being submitted (not draft)
        $attachment_count = $exercise['attachment_count'] ?? 1;
        if ($action === 'submit' && $exercise['requires_attachment'] == 1) {
            $has_new_files = isset($_FILES['submission_file']) && isset($_FILES['submission_file']['name'][0]) && $_FILES['submission_file']['error'][0] === UPLOAD_ERR_OK;
            $has_old_files = $submission && $submission['file_path'];

            if (!$has_new_files && !$has_old_files) {
                $error_message = $lang === 'fr'
                    ? 'Vous devez télécharger ' . ($attachment_count > 1 ? "{$attachment_count} fichiers" : 'un fichier') . ' pour soumettre cet exercice.'
                    : 'You must upload ' . ($attachment_count > 1 ? "{$attachment_count} files" : 'a file') . ' to submit this exercise.';
                goto skip_submission;
            }
        }

        // Handle multiple file uploads
        $file_paths = [];
        $file_names = [];
        $total_file_size = 0;

        if (isset($_FILES['submission_file']) && isset($_FILES['submission_file']['name'][0])) {
            $upload_dir = __DIR__ . '/../uploads/exercises/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $uploaded_count = 0;
            foreach ($_FILES['submission_file']['name'] as $key => $name) {
                if ($_FILES['submission_file']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                    $original_name = pathinfo($name, PATHINFO_FILENAME);
                    $file_size = $_FILES['submission_file']['size'][$key];

                    // Check file size (10MB max per file)
                    if ($file_size > 10 * 1024 * 1024) {
                        $error_message = $lang === 'fr'
                            ? "Le fichier '{$name}' dépasse la taille maximale de 10MB."
                            : "File '{$name}' exceeds maximum size of 10MB.";
                        goto skip_submission;
                    }

                    // Validate file format
                    $allowed_formats = explode(',', $exercise['attachment_formats'] ?? 'pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png');
                    $allowed_formats = array_map('trim', $allowed_formats);
                    if (!in_array(strtolower($file_extension), $allowed_formats)) {
                        $error_message = $lang === 'fr'
                            ? "Le format du fichier '{$name}' n'est pas accepté."
                            : "File format of '{$name}' is not accepted.";
                        goto skip_submission;
                    }

                    $unique_name = 'team_' . $team_id . '_ex_' . $exercise_id . '_' . time() . '_' . $uploaded_count . '.' . $file_extension;
                    $file_path_item = 'uploads/exercises/' . $unique_name;

                    if (move_uploaded_file($_FILES['submission_file']['tmp_name'][$key], $upload_dir . $unique_name)) {
                        $file_paths[] = $file_path_item;
                        $file_names[] = $name;
                        $total_file_size += $file_size;
                        $uploaded_count++;
                    }
                }
            }

            // Check if minimum number of files uploaded
            if ($action === 'submit' && $exercise['requires_attachment'] == 1 && $uploaded_count < $attachment_count) {
                // Check if we have old files to make up the difference
                $existing_file_count = 0;
                if ($submission && $submission['file_path']) {
                    $existing_files = explode(',', $submission['file_path']);
                    $existing_file_count = count(array_filter($existing_files, 'trim'));
                }

                if ($uploaded_count + $existing_file_count < $attachment_count) {
                    $error_message = $lang === 'fr'
                        ? "Vous devez télécharger au moins {$attachment_count} fichier(s)."
                        : "You must upload at least {$attachment_count} file(s).";
                    goto skip_submission;
                }
            }
        }

        // Combine new and old files
        if (!empty($file_paths)) {
            $file_path = implode(',', $file_paths);
            $file_name = implode(',', $file_names);
            $file_size = $total_file_size;
        } else {
            // If no new files, preserve the old files from previous submission
            if ($submission && $submission['file_path']) {
                $file_path = $submission['file_path'];
                $file_name = $submission['file_name'];
                $file_size = $submission['file_size'];
            } else {
                $file_path = null;
                $file_name = null;
                $file_size = null;
            }
        }

        // Insert new submission
        $insert_query = "
            INSERT INTO exercise_submissions
            (team_id, exercise_id, submission_text, file_path, file_name, file_size,
             submitted_by, status, version, submitted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($insert_query);
        $submitted_at = ($status === 'submitted') ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param('iissssisss', $team_id, $exercise_id, $submission_text, $file_path,
                         $file_name, $file_size, $user_id, $status, $version, $submitted_at);
        $stmt->execute();

        // Log activity
        $activity_type = ($status === 'submitted') ? 'exercise_submitted' : 'exercise_started';
        $description = ($status === 'submitted')
            ? "Submitted exercise {$exercise['exercise_number']}"
            : "Started working on exercise {$exercise['exercise_number']}";

        $log_query = "
            INSERT INTO team_activity_log (team_id, user_id, activity_type, entity_type, entity_id, description)
            VALUES (?, ?, ?, 'exercise', ?, ?)
        ";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param('iisis', $team_id, $user_id, $activity_type, $exercise_id, $description);
        $stmt->execute();

        $success_message = ($status === 'submitted')
            ? ($lang === 'fr' ? 'Exercice soumis avec succès !' : 'Exercise submitted successfully!')
            : ($lang === 'fr' ? 'Brouillon sauvegardé.' : 'Draft saved.');

        // Refresh submission
        $stmt = $conn->prepare($submission_query);
        $stmt->bind_param('ii', $team_id, $exercise_id);
        $stmt->execute();
        $submission_result = $stmt->get_result();
        $submission = $submission_result->fetch_assoc();
    }

    skip_submission: // Label for file validation error
}

closeDatabaseConnection($conn);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $exercise['exercise_number']; ?> - <?php echo $lang === 'fr' ? ($exercise['exercise_title_fr'] ?? $exercise['exercise_title']) : $exercise['exercise_title']; ?></title>
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

        .breadcrumb-bar {
            background: #fff;
            padding: 15px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .breadcrumb-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breadcrumb {
            font-size: 0.9rem;
            color: #666;
        }

        .breadcrumb a {
            color: #1cabe2;
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: #4f46e5;
            text-decoration: underline;
        }

        .team-badge {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .exercise-header {
            margin-bottom: 30px;
        }

        .exercise-number {
            color: #1cabe2;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 15px;
        }

        .exercise-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 30px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .section-title {
            font-size: 1.3rem;
            color: #333;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1cabe2;
        }

        .instructions {
            line-height: 1.8;
            color: #444;
            white-space: pre-wrap;
        }

        .materials-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            line-height: 1.8;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }

        textarea {
            width: 100%;
            min-height: 200px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
        }

        textarea:focus {
            outline: none;
            border-color: #1cabe2;
        }

        .file-upload {
            border: 2px dashed #e0e0e0;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload:hover {
            border-color: #1cabe2;
            background: #f8f9ff;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .uploaded-file {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 15px;
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

        .feedback-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .feedback-box h4 {
            color: #856404;
            margin-bottom: 10px;
        }

        .submission-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        @media (max-width: 1024px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/incubation-header.php'; ?>

    <div class="breadcrumb-bar">
        <div class="breadcrumb-container">
            <div class="breadcrumb">
                <a href="incubation-dashboard.php">← <?php echo $lang === 'fr' ? 'Retour au tableau de bord' : 'Back to Dashboard'; ?></a>
            </div>
            <div class="team-badge">
                👥 <?php echo htmlspecialchars($team['team_name']); ?>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($is_locked): ?>
            <div class="alert alert-warning" style="background: #fef3c7; border: 2px solid #f59e0b; color: #92400e; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-size: 2rem;">🔒</span>
                    <div>
                        <strong style="font-size: 1.1rem; display: block; margin-bottom: 5px;">
                            <?php echo $lang === 'fr' ? 'Exercice verrouillé' : 'Exercise Locked'; ?>
                        </strong>
                        <p style="margin: 0;"><?php echo $lock_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid">
            <div>
                <div class="card">
                    <div class="exercise-header">
                        <div class="exercise-number">
                            <?php echo htmlspecialchars($exercise['exercise_number']); ?>
                        </div>
                        <h1><?php echo $lang === 'fr' ? $exercise['exercise_title_fr'] : $exercise['exercise_title']; ?></h1>

                        <div class="exercise-meta">
                            <div class="meta-item">
                                <span>📚</span>
                                <span><?php echo $lang === 'fr' ? $exercise['phase_name_fr'] : $exercise['phase_name']; ?></span>
                            </div>
                            <?php if (isset($exercise['estimated_time']) && $exercise['estimated_time']): ?>
                                <div class="meta-item">
                                    <span>⏱️</span>
                                    <span><?php echo $exercise['estimated_time']; ?> <?php echo $lang === 'fr' ? 'minutes' : 'minutes'; ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($exercise['requires_attachment'] == 1): ?>
                                <div class="meta-item">
                                    <span>📎</span>
                                    <span><?php echo $lang === 'fr' ? 'Fichiers requis' : 'Files Required'; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="section-title">
                        <?php echo $lang === 'fr' ? 'Instructions' : 'Instructions'; ?>
                    </div>
                    <div class="instructions">
                        <?php echo nl2br(htmlspecialchars($lang === 'fr' ? ($exercise['instructions_fr'] ?? $exercise['instructions']) : $exercise['instructions'])); ?>
                    </div>

                    <?php if ($is_locked): ?>
                        <!-- Exercise locked - show instructions only -->
                        <div class="section-title" style="color: #92400e;">
                            <?php echo $lang === 'fr' ? '🔒 Cet exercice est verrouillé' : '🔒 This Exercise is Locked'; ?>
                        </div>
                        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; color: #92400e;">
                            <p><?php echo $lang === 'fr' ? 'Vous devez d\'abord terminer l\'exercice précédent avant d\'accéder à celui-ci.' : 'You must first complete the previous exercise before accessing this one.'; ?></p>
                            <a href="incubation-dashboard.php" class="btn btn-primary" style="margin-top: 15px;">
                                <?php echo $lang === 'fr' ? 'Retour au tableau de bord' : 'Back to Dashboard'; ?>
                            </a>
                        </div>
                    <?php elseif ($submission && $submission['status'] === 'approved'): ?>
                        <!-- Exercise approved - show submission -->
                        <div class="section-title">
                            <?php echo $lang === 'fr' ? 'Votre soumission (Approuvée)' : 'Your Submission (Approved)'; ?>
                        </div>
                        <div class="submission-info">
                            <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                            <?php if ($submission['file_name']): ?>
                                <p><strong><?php echo $lang === 'fr' ? 'Fichier :' : 'File:'; ?></strong>
                                <a href="<?php echo $submission['file_path']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($submission['file_name']); ?>
                                </a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Submission form -->
                        <form method="POST" enctype="multipart/form-data" id="submissionForm">
                            <div class="section-title">
                                <?php echo $lang === 'fr' ? 'Votre réponse' : 'Your Response'; ?>
                            </div>

                            <div class="form-group">
                                <label for="submission_text">
                                    <?php echo $lang === 'fr' ? 'Décrivez votre travail' : 'Describe Your Work'; ?>
                                </label>
                                <textarea name="submission_text" id="submission_text"
                                          placeholder="<?php echo $lang === 'fr' ? 'Écrivez votre réponse ici...' : 'Write your response here...'; ?>"><?php echo htmlspecialchars($submission['submission_text'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>
                                    <?php
                                    $attachment_count = $exercise['attachment_count'] ?? 1;
                                    $attachment_formats = $exercise['attachment_formats'] ?? 'pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png';

                                    if ($attachment_count > 1) {
                                        echo $lang === 'fr' ? "Télécharger des fichiers ({$attachment_count} fichiers)" : "Upload Files ({$attachment_count} files)";
                                    } else {
                                        echo $lang === 'fr' ? 'Télécharger un fichier' : 'Upload a File';
                                    }
                                    ?>
                                    <?php if ($exercise['requires_attachment'] == 1): ?>
                                        <span style="color: #e74c3c; font-weight: bold;">*</span>
                                        <span style="color: #e74c3c; font-size: 0.85em;">(<?php echo $lang === 'fr' ? 'Obligatoire' : 'Required'; ?>)</span>
                                    <?php endif; ?>
                                </label>

                                <?php
                                // Build accept attribute from attachment_formats
                                $formats_array = explode(',', $attachment_formats);
                                $accept_attr = '.' . implode(',.', array_map('trim', $formats_array));

                                // Build display format list
                                $formats_display = strtoupper(implode(', ', array_map('trim', $formats_array)));
                                ?>

                                <div class="file-upload" onclick="document.getElementById('submission_file').click()">
                                    <div style="font-size: 2rem; margin-bottom: 10px;">📁</div>
                                    <div>
                                        <?php echo $lang === 'fr' ? 'Cliquez pour sélectionner' : 'Click to select'; ?>
                                        <?php echo $attachment_count > 1 ? ($lang === 'fr' ? 'des fichiers' : 'files') : ($lang === 'fr' ? 'un fichier' : 'a file'); ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666; margin-top: 10px;">
                                        <?php echo $formats_display; ?> (Max 10MB <?php echo $lang === 'fr' ? 'chacun' : 'each'; ?>)
                                    </div>
                                    <input type="file"
                                           name="submission_file[]"
                                           id="submission_file"
                                           accept="<?php echo $accept_attr; ?>"
                                           onchange="showFileName(this)"
                                           multiple
                                           <?php echo ($exercise['requires_attachment'] == 1 && (!$submission || !$submission['file_path'])) ? 'data-required="true"' : ''; ?>>
                                </div>
                                <div id="fileNameDisplay">
                                    <?php if ($submission && $submission['file_path']): ?>
                                        <?php
                                        $existing_files = explode(',', $submission['file_path']);
                                        foreach ($existing_files as $file):
                                            $file = trim($file);
                                            if ($file):
                                        ?>
                                            <div class="uploaded-file">
                                                <span>📎 <?php echo htmlspecialchars(basename($file)); ?></span>
                                                <span style="color: #10b981;"><?php echo $lang === 'fr' ? '(Fichier actuel)' : '(Current file)'; ?></span>
                                            </div>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="button-group">
                                <button type="submit" name="action" value="save_draft" class="btn btn-secondary">
                                    <?php echo $lang === 'fr' ? 'Sauvegarder le brouillon' : 'Save Draft'; ?>
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn-primary">
                                    <?php echo $lang === 'fr' ? 'Soumettre l\'exercice' : 'Submit Exercise'; ?>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <!-- Status Card -->
                <?php if ($submission): ?>
                    <div class="status-card">
                        <h3 style="margin-bottom: 15px;">
                            <?php echo $lang === 'fr' ? 'Statut' : 'Status'; ?>
                        </h3>
                        <?php
                        $status_classes = [
                            'draft' => 'status-draft',
                            'submitted' => 'status-submitted',
                            'approved' => 'status-approved',
                            'revision_needed' => 'status-revision'
                        ];
                        $status_labels = [
                            'draft' => $lang === 'fr' ? 'Brouillon' : 'Draft',
                            'submitted' => $lang === 'fr' ? 'Soumis' : 'Submitted',
                            'approved' => $lang === 'fr' ? 'Approuvé' : 'Approved',
                            'revision_needed' => $lang === 'fr' ? 'Révision nécessaire' : 'Needs Revision'
                        ];
                        ?>
                        <div class="status-badge <?php echo $status_classes[$submission['status']]; ?>">
                            <?php echo $status_labels[$submission['status']]; ?>
                        </div>

                        <?php if ($submission['submitted_at']): ?>
                            <p style="color: #666; font-size: 0.9rem; margin-top: 10px;">
                                <?php echo $lang === 'fr' ? 'Soumis le :' : 'Submitted on:'; ?>
                                <?php echo date('M j, Y', strtotime($submission['submitted_at'])); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($submission['feedback']): ?>
                            <div class="feedback-box">
                                <h4><?php echo $lang === 'fr' ? 'Commentaires de l\'administrateur' : 'Admin Feedback'; ?></h4>
                                <p><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Help Card -->
                <div class="status-card">
                    <h3 style="margin-bottom: 15px;">
                        💡 <?php echo $lang === 'fr' ? 'Conseils' : 'Tips'; ?>
                    </h3>
                    <ul style="line-height: 2; color: #666;">
                        <li><?php echo $lang === 'fr' ? 'Sauvegardez régulièrement votre travail' : 'Save your work regularly'; ?></li>
                        <li><?php echo $lang === 'fr' ? 'Collaborez avec votre équipe' : 'Collaborate with your team'; ?></li>
                        <li><?php echo $lang === 'fr' ? 'Soyez créatif et innovant' : 'Be creative and innovative'; ?></li>
                        <li><?php echo $lang === 'fr' ? 'Demandez de l\'aide si nécessaire' : 'Ask for help if needed'; ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showFileName(input) {
            const display = document.getElementById('fileNameDisplay');
            if (input.files && input.files.length > 0) {
                let html = '';
                let totalSize = 0;

                // Loop through all selected files
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const fileName = file.name;
                    const fileSize = file.size / 1024 / 1024; // Convert to MB

                    // Check individual file size (max 10MB)
                    if (fileSize > 10) {
                        alert('<?php echo $lang === 'fr' ? 'Le fichier' : 'File'; ?> "' + fileName + '" <?php echo $lang === 'fr' ? 'est trop volumineux. Taille maximale : 10 MB' : 'is too large. Maximum size: 10 MB'; ?>');
                        clearFile();
                        return;
                    }

                    totalSize += fileSize;

                    html += `
                        <div class="uploaded-file">
                            <span>📎 ${fileName} (${fileSize.toFixed(2)} MB)</span>
                        </div>
                    `;
                }

                html += `
                    <div style="margin-top: 10px;">
                        <button type="button" onclick="clearFile()" style="background: #e74c3c; color: white; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                            <?php echo $lang === 'fr' ? 'Supprimer les fichiers' : 'Clear Files'; ?>
                        </button>
                        <span style="color: #666; font-size: 0.85rem; margin-left: 10px;">
                            ${input.files.length} <?php echo $lang === 'fr' ? 'fichier(s)' : 'file(s)'; ?> - ${totalSize.toFixed(2)} MB <?php echo $lang === 'fr' ? 'au total' : 'total'; ?>
                        </span>
                    </div>
                `;

                display.innerHTML = html;
            }
        }

        function clearFile() {
            document.getElementById('submission_file').value = '';
            // Show current files from submission if any
            <?php if ($submission && $submission['file_path']): ?>
                const existingFiles = <?php
                    $existing_files = explode(',', $submission['file_path']);
                    $file_list = [];
                    foreach ($existing_files as $file) {
                        $file = trim($file);
                        if ($file) {
                            $file_list[] = basename($file);
                        }
                    }
                    echo json_encode($file_list);
                ?>;

                let html = '';
                existingFiles.forEach(fileName => {
                    html += `
                        <div class="uploaded-file">
                            <span>📎 ${fileName}</span>
                            <span style="color: #10b981;"><?php echo $lang === 'fr' ? '(Fichier actuel)' : '(Current file)'; ?></span>
                        </div>
                    `;
                });
                document.getElementById('fileNameDisplay').innerHTML = html;
            <?php else: ?>
                document.getElementById('fileNameDisplay').innerHTML = '';
            <?php endif; ?>
        }

        // Form validation for file requirement
        document.getElementById('submissionForm').addEventListener('submit', function(e) {
            const submitBtn = e.submitter;
            if (submitBtn && submitBtn.value === 'submit') {
                const fileInput = document.getElementById('submission_file');
                const isRequired = fileInput.getAttribute('data-required') === 'true';
                const hasNewFile = fileInput.files && fileInput.files.length > 0;
                const hasExistingFile = '<?php echo ($submission && $submission['file_path']) ? 'true' : ''; ?>';

                if (isRequired && !hasNewFile && !hasExistingFile) {
                    e.preventDefault();
                    alert('<?php echo $lang === 'fr' ? 'Vous devez télécharger un fichier pour soumettre cet exercice.' : 'You must upload a file to submit this exercise.'; ?>');
                    return false;
                }
            }
        });
    </script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
