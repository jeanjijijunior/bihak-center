<?php
/**
 * Incubation Program - Manage Exercises (Enhanced)
 * Admin interface for managing, adding, and editing exercises
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

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $exercise_id = $action === 'edit' ? intval($_POST['exercise_id']) : null;
        $phase_id = intval($_POST['phase_id']);
        $exercise_number = intval($_POST['exercise_number']);
        $exercise_title = trim($_POST['exercise_title']);
        $exercise_title_fr = trim($_POST['exercise_title_fr']);
        $exercise_type = $_POST['exercise_type'];
        $interactive_template = $_POST['interactive_template'] ?? null;
        $ai_enabled = isset($_POST['ai_enabled']) ? 1 : 0;
        $description = trim($_POST['description']);
        $description_fr = trim($_POST['description_fr']);
        $instructions = trim($_POST['instructions']);
        $instructions_fr = trim($_POST['instructions_fr']);
        $display_order = intval($_POST['display_order']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $estimated_time = intval($_POST['estimated_time']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $requires_attachment = isset($_POST['requires_attachment']) ? 1 : 0;
        $attachment_count = intval($_POST['attachment_count']);
        $attachment_formats = trim($_POST['attachment_formats']);

        try {
            if ($action === 'add') {
                $query = "INSERT INTO incubation_exercises
                    (phase_id, exercise_number, exercise_title, exercise_title_fr, exercise_type, interactive_template,
                     ai_enabled, description, description_fr, instructions, instructions_fr, display_order, is_required,
                     estimated_time, is_active, requires_attachment, attachment_count, attachment_formats)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iissssississiiiis',
                    $phase_id, $exercise_number, $exercise_title, $exercise_title_fr, $exercise_type, $interactive_template,
                    $ai_enabled, $description, $description_fr, $instructions, $instructions_fr, $display_order, $is_required,
                    $estimated_time, $is_active, $requires_attachment, $attachment_count, $attachment_formats
                );
                $stmt->execute();
                $success_message = "Exercise added successfully!";
            } else {
                $query = "UPDATE incubation_exercises SET
                    phase_id = ?, exercise_number = ?, exercise_title = ?, exercise_title_fr = ?, exercise_type = ?,
                    interactive_template = ?, ai_enabled = ?, description = ?, description_fr = ?, instructions = ?,
                    instructions_fr = ?, display_order = ?, is_required = ?, estimated_time = ?, is_active = ?,
                    requires_attachment = ?, attachment_count = ?, attachment_formats = ?
                    WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iissssississiiiisi',
                    $phase_id, $exercise_number, $exercise_title, $exercise_title_fr, $exercise_type, $interactive_template,
                    $ai_enabled, $description, $description_fr, $instructions, $instructions_fr, $display_order, $is_required,
                    $estimated_time, $is_active, $requires_attachment, $attachment_count, $attachment_formats, $exercise_id
                );
                $stmt->execute();
                $success_message = "Exercise updated successfully!";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'toggle_status') {
        $exercise_id = intval($_POST['exercise_id']);
        $new_status = intval($_POST['new_status']);
        $query = "UPDATE incubation_exercises SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $new_status, $exercise_id);
        $stmt->execute();
        $success_message = "Exercise status updated!";
    }
}

// Get all phases for the form dropdown
$phases_query = "SELECT * FROM incubation_phases ORDER BY phase_order";
$phases_result = $conn->query($phases_query);
$phases = $phases_result ? $phases_result->fetch_all(MYSQLI_ASSOC) : [];

// Get all exercises
$exercises_query = "
    SELECT
        ie.*,
        ip.phase_name,
        COUNT(DISTINCT es.id) as total_submissions,
        COUNT(DISTINCT CASE WHEN es.status = 'approved' THEN es.id END) as approved_submissions
    FROM incubation_exercises ie
    JOIN incubation_phases ip ON ie.phase_id = ip.id
    LEFT JOIN exercise_submissions es ON ie.id = es.exercise_id
    GROUP BY ie.id
    ORDER BY ie.exercise_number
";
$result = $conn->query($exercises_query);
$exercises = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

closeDatabaseConnection($conn);

$page_title = 'Manage Exercises - Incubation Admin';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .admin-container {
            max-width: 1600px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #6b7280;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
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

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section h2 {
            margin-bottom: 20px;
            color: #1f2937;
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

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-secondary {
            background: #f3f4f6;
            color: #6b7280;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h2 {
            margin: 0;
            color: #1f2937;
        }

        .modal-close {
            font-size: 32px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #1f2937;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-grid-full {
            grid-column: 1 / -1;
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

        .form-group input,
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

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .helper-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="incubation-admin-dashboard.php" class="back-to-dashboard">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-book"></i> Manage Exercises</h1>
                <p>View, add, and edit all incubation program exercises</p>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Exercise
            </button>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>All Exercises (<?php echo count($exercises); ?>)</h2>

            <?php if (empty($exercises)): ?>
                <p style="text-align: center; color: #6b7280; padding: 40px 0;">
                    No exercises found. Click "Add New Exercise" to create one.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Exercise Title</th>
                            <th>Phase</th>
                            <th>Type</th>
                            <th>Time</th>
                            <th>Required</th>
                            <th>Submissions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exercises as $exercise): ?>
                            <tr>
                                <td><strong><?php echo $exercise['exercise_number']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($exercise['exercise_title']); ?></strong>
                                    <?php if ($exercise['ai_enabled']): ?>
                                        <i class="fas fa-robot" style="color: #6366f1; margin-left: 5px;" title="AI Enabled"></i>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($exercise['phase_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($exercise['exercise_type']); ?></td>
                                <td><?php echo $exercise['estimated_time'] ? $exercise['estimated_time'] . ' min' : '-'; ?></td>
                                <td>
                                    <?php if ($exercise['is_required']): ?>
                                        <span class="badge badge-warning">Required</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Optional</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $exercise['total_submissions']; ?>
                                    <small>(<?php echo $exercise['approved_submissions']; ?> approved)</small>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="exercise_id" value="<?php echo $exercise['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $exercise['is_active'] ? 0 : 1; ?>">
                                        <?php if ($exercise['is_active']): ?>
                                            <button type="submit" class="badge badge-success" style="border: none; cursor: pointer;">
                                                Active
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="badge badge-secondary" style="border: none; cursor: pointer;">
                                                Inactive
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick='openEditModal(<?php echo json_encode($exercise); ?>)'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Exercise Modal -->
    <div id="exerciseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Exercise</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>

            <form method="POST" id="exerciseForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="exercise_id" id="exercise_id" value="">

                <div class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-group">
                        <label for="phase_id">Phase *</label>
                        <select name="phase_id" id="phase_id" required>
                            <option value="">Select Phase</option>
                            <?php foreach ($phases as $phase): ?>
                                <option value="<?php echo $phase['id']; ?>"><?php echo htmlspecialchars($phase['phase_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exercise_number">Exercise Number *</label>
                        <input type="number" name="exercise_number" id="exercise_number" required min="1">
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="exercise_title">Exercise Title (English) *</label>
                        <input type="text" name="exercise_title" id="exercise_title" required>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="exercise_title_fr">Exercise Title (French)</label>
                        <input type="text" name="exercise_title_fr" id="exercise_title_fr">
                    </div>

                    <div class="form-group">
                        <label for="exercise_type">Exercise Type *</label>
                        <select name="exercise_type" id="exercise_type" required>
                            <option value="file_upload">File Upload</option>
                            <option value="text_submission">Text Submission</option>
                            <option value="interactive">Interactive</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="interactive_template">Interactive Template</label>
                        <select name="interactive_template" id="interactive_template">
                            <option value="">None</option>
                            <option value="problem_tree">Problem Tree</option>
                            <option value="business_canvas">Business Canvas</option>
                            <option value="persona_mapping">Persona Mapping</option>
                            <option value="stakeholder_map">Stakeholder Map</option>
                        </select>
                        <div class="helper-text">Only for interactive exercises</div>
                    </div>

                    <div class="form-group">
                        <label for="estimated_time">Estimated Time (minutes)</label>
                        <input type="number" name="estimated_time" id="estimated_time" value="30" min="1">
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" name="display_order" id="display_order" value="0" min="0">
                    </div>

                    <!-- Descriptions -->
                    <div class="form-group form-grid-full">
                        <label for="description">Description (English)</label>
                        <textarea name="description" id="description"></textarea>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="description_fr">Description (French)</label>
                        <textarea name="description_fr" id="description_fr"></textarea>
                    </div>

                    <!-- Instructions -->
                    <div class="form-group form-grid-full">
                        <label for="instructions">Instructions (English) *</label>
                        <textarea name="instructions" id="instructions" required></textarea>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="instructions_fr">Instructions (French)</label>
                        <textarea name="instructions_fr" id="instructions_fr"></textarea>
                    </div>

                    <!-- Attachment Settings -->
                    <div class="form-group">
                        <label for="attachment_count">Attachment Count</label>
                        <input type="number" name="attachment_count" id="attachment_count" value="1" min="1" max="10">
                    </div>

                    <div class="form-group">
                        <label for="attachment_formats">Allowed Formats</label>
                        <input type="text" name="attachment_formats" id="attachment_formats" value="pdf,doc,docx,ppt,pptx">
                        <div class="helper-text">Comma-separated file extensions</div>
                    </div>

                    <!-- Checkboxes -->
                    <div class="form-group form-grid-full" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_required" id="is_required" value="1" checked>
                            <label for="is_required">Required</label>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                            <label for="is_active">Active</label>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" name="requires_attachment" id="requires_attachment" value="1" checked>
                            <label for="requires_attachment">Requires Attachment</label>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" name="ai_enabled" id="ai_enabled" value="1" checked>
                            <label for="ai_enabled">AI Enabled</label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Exercise
                    </button>
                    <button type="button" class="btn btn-warning" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Exercise';
            document.getElementById('formAction').value = 'add';
            document.getElementById('exerciseForm').reset();
            document.getElementById('exercise_id').value = '';
            // Set defaults for checkboxes
            document.getElementById('is_required').checked = true;
            document.getElementById('is_active').checked = true;
            document.getElementById('requires_attachment').checked = true;
            document.getElementById('ai_enabled').checked = true;
            document.getElementById('exerciseModal').style.display = 'block';
        }

        function openEditModal(exercise) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Exercise';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('exercise_id').value = exercise.id;

            // Populate form fields
            document.getElementById('phase_id').value = exercise.phase_id;
            document.getElementById('exercise_number').value = exercise.exercise_number;
            document.getElementById('exercise_title').value = exercise.exercise_title;
            document.getElementById('exercise_title_fr').value = exercise.exercise_title_fr || '';
            document.getElementById('exercise_type').value = exercise.exercise_type;
            document.getElementById('interactive_template').value = exercise.interactive_template || '';
            document.getElementById('estimated_time').value = exercise.estimated_time || 30;
            document.getElementById('display_order').value = exercise.display_order || 0;
            document.getElementById('description').value = exercise.description || '';
            document.getElementById('description_fr').value = exercise.description_fr || '';
            document.getElementById('instructions').value = exercise.instructions;
            document.getElementById('instructions_fr').value = exercise.instructions_fr || '';
            document.getElementById('attachment_count').value = exercise.attachment_count || 1;
            document.getElementById('attachment_formats').value = exercise.attachment_formats || 'pdf,doc,docx';

            // Set checkboxes
            document.getElementById('is_required').checked = exercise.is_required == 1;
            document.getElementById('is_active').checked = exercise.is_active == 1;
            document.getElementById('requires_attachment').checked = exercise.requires_attachment == 1;
            document.getElementById('ai_enabled').checked = exercise.ai_enabled == 1;

            document.getElementById('exerciseModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('exerciseModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('exerciseModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
