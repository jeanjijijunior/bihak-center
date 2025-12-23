<?php
/**
 * Interactive Incubation Exercise Page
 * Handles all interactive exercise types based on template
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai-provider.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$conn = getDatabaseConnection();
$user_id = $_SESSION['user_id'];

// Get exercise ID from URL
$exercise_id = isset($_GET['exercise_id']) ? intval($_GET['exercise_id']) : 0;

if (!$exercise_id) {
    die('Invalid exercise ID');
}

// Get exercise details
$exercise_query = $conn->prepare("
    SELECT ie.*, ip.phase_name
    FROM incubation_exercises ie
    JOIN incubation_phases ip ON ie.phase_id = ip.id
    WHERE ie.id = ?
");
$exercise_query->bind_param('i', $exercise_id);
$exercise_query->execute();
$exercise = $exercise_query->get_result()->fetch_assoc();

if (!$exercise) {
    die('Exercise not found');
}

// Get user's team
$team_query = $conn->prepare("
    SELECT it.*
    FROM incubation_teams it
    JOIN incubation_team_members itm ON it.id = itm.team_id
    WHERE itm.user_id = ?
    LIMIT 1
");
$team_query->bind_param('i', $user_id);
$team_query->execute();
$team = $team_query->get_result()->fetch_assoc();

if (!$team) {
    die('You are not part of any incubation team');
}

$team_id = $team['id'];

// Get existing interactive data
$data_query = $conn->prepare("
    SELECT *
    FROM incubation_interactive_data
    WHERE team_id = ? AND exercise_id = ? AND is_current = 1
    ORDER BY version DESC
    LIMIT 1
");
$data_query->bind_param('ii', $team_id, $exercise_id);
$data_query->execute();
$existing_data = $data_query->get_result()->fetch_assoc();

// Get exercise metrics
$metrics_query = $conn->prepare("
    SELECT *
    FROM incubation_exercise_metrics
    WHERE team_id = ? AND exercise_id = ?
");
$metrics_query->bind_param('ii', $team_id, $exercise_id);
$metrics_query->execute();
$metrics = $metrics_query->get_result()->fetch_assoc();

// Get recent AI feedback
$feedback_query = $conn->prepare("
    SELECT *
    FROM incubation_ai_feedback
    WHERE team_id = ? AND exercise_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$feedback_query->bind_param('ii', $team_id, $exercise_id);
$feedback_query->execute();
$recent_feedback = $feedback_query->get_result();

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exercise['exercise_title']); ?> - Interactive Exercise</title>
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <link rel="stylesheet" href="../assets/css/incubation-interactive.css">
    <link rel="icon" type="image/png" href="../assets/images/logob.png">

    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Konva.js for canvas manipulation -->
    <script src="https://unpkg.com/konva@9.2.0/konva.min.js"></script>

    <!-- html2canvas for screenshots -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <!-- jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        .interactive-container {
            display: flex;
            min-height: calc(100vh - 80px);
            background: #f3f4f6;
        }

        .main-workspace {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .ai-sidebar {
            width: 350px;
            background: white;
            border-left: 1px solid #e5e7eb;
            padding: 1.5rem;
            overflow-y: auto;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
        }

        .exercise-header {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .exercise-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .exercise-meta {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .progress-indicator {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .progress-bar {
            width: 150px;
            height: 8px;
            background: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: width 0.3s ease;
        }

        .canvas-workspace {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .canvas-container {
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            background: #fafafa;
            position: relative;
        }

        .toolbar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .toolbar-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .toolbar-btn:hover {
            background: #f9fafb;
            border-color: #667eea;
        }

        .toolbar-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .ai-assistant-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .ai-assistant-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .ai-credits {
            margin-left: auto;
            background: #fef3c7;
            color: #92400e;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .ai-section {
            margin-bottom: 1.5rem;
        }

        .ai-section-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feedback-card {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .feedback-score {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .score-badge {
            background: #3b82f6;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 1.125rem;
        }

        .feedback-text {
            color: #1e40af;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .ai-chat-box {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 0.5rem;
            background: #fafafa;
        }

        .chat-message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 0.375rem;
        }

        .chat-message.user {
            background: #e0e7ff;
            text-align: right;
        }

        .chat-message.ai {
            background: white;
            border: 1px solid #e5e7eb;
        }

        .chat-input-container {
            display: flex;
            gap: 0.5rem;
        }

        .chat-input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        .checklist {
            list-style: none;
            padding: 0;
        }

        .checklist li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checklist li:last-child {
            border-bottom: none;
        }

        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .checkbox.checked {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 1rem;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .help-text {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #92400e;
        }

        @media (max-width: 1024px) {
            .interactive-container {
                flex-direction: column;
            }

            .ai-sidebar {
                width: 100%;
                border-left: none;
                border-top: 1px solid #e5e7eb;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header_new.php'; ?>

    <div class="interactive-container">
        <!-- Main Workspace -->
        <div class="main-workspace">
            <!-- Exercise Header -->
            <div class="exercise-header">
                <h1><?php echo htmlspecialchars($exercise['exercise_title']); ?></h1>
                <div class="exercise-meta">
                    <span><strong>Phase:</strong> <?php echo htmlspecialchars($exercise['phase_name']); ?></span>
                    <span><strong>Team:</strong> <?php echo htmlspecialchars($team['team_name']); ?></span>
                    <div class="progress-indicator">
                        <span>Progress:</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $metrics ? $metrics['completeness_score'] : 0; ?>%"></div>
                        </div>
                        <span><?php echo $metrics ? $metrics['completeness_score'] : 0; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Help Text -->
            <div class="help-text">
                <?php
                switch ($exercise['interactive_template']) {
                    case 'problem_tree':
                        echo '🌳 <strong>Problem Tree:</strong> Identify the core problem, its root causes, and effects. Drag and connect boxes to build your tree.';
                        break;
                    case 'business_model_canvas':
                        echo '💼 <strong>Business Model Canvas:</strong> Fill in all 9 blocks to visualize your business model.';
                        break;
                    case 'persona':
                        echo '👤 <strong>Persona:</strong> Create detailed user personas representing your target audience.';
                        break;
                    case 'stakeholder_map':
                        echo '🗺️ <strong>Stakeholder Map:</strong> Map stakeholders based on their influence and interest.';
                        break;
                    default:
                        echo '📝 Complete this interactive exercise to develop your project.';
                }
                ?>
            </div>

            <!-- Canvas Workspace -->
            <div class="canvas-workspace">
                <!-- Toolbar -->
                <div class="toolbar">
                    <?php if ($exercise['interactive_template'] === 'problem_tree'): ?>
                        <button class="toolbar-btn" id="add-problem-btn" title="Add Core Problem">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                            </svg>
                            Problem
                        </button>
                        <button class="toolbar-btn" id="add-cause-btn" title="Add Root Cause">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                            </svg>
                            Cause
                        </button>
                        <button class="toolbar-btn" id="add-effect-btn" title="Add Effect">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                            </svg>
                            Effect
                        </button>
                        <button class="toolbar-btn" id="add-arrow-btn" title="Connect Boxes">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Arrow
                        </button>
                        <button class="toolbar-btn" id="delete-btn" title="Delete Selected">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Delete
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Canvas Container -->
                <div id="canvas-container" class="canvas-container">
                    <!-- Interactive canvas will be rendered here -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-secondary" id="save-draft-btn">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save Draft
                    </button>
                    <button class="btn btn-secondary" id="export-pdf-btn">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"/>
                            <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                        </svg>
                        Export PDF
                    </button>
                </div>
                <button class="btn btn-success" id="submit-btn">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Submit for Review
                </button>
            </div>

            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loading-spinner">
                <div class="spinner"></div>
                <p>Processing...</p>
            </div>
        </div>

        <!-- AI Assistant Sidebar -->
        <div class="ai-sidebar">
            <div class="ai-assistant-header">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                    <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                </svg>
                <h2>AI Assistant</h2>
                <div class="ai-credits"><?php echo $team['ai_credits']; ?> credits</div>
            </div>

            <!-- Get AI Feedback Button -->
            <button class="btn btn-primary" id="get-ai-feedback-btn" style="width: 100%; margin-bottom: 1.5rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                </svg>
                Get AI Feedback
            </button>

            <!-- Recent Feedback -->
            <?php if ($recent_feedback->num_rows > 0): ?>
            <div class="ai-section">
                <div class="ai-section-title">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    Recent Feedback
                </div>
                <?php while ($feedback = $recent_feedback->fetch_assoc()): ?>
                <div class="feedback-card">
                    <div class="feedback-score">
                        <span class="score-badge"><?php echo $feedback['completeness_score']; ?>%</span>
                        <span style="font-size: 0.75rem; color: #6b7280;">
                            <?php echo date('M j, g:i A', strtotime($feedback['created_at'])); ?>
                        </span>
                    </div>
                    <div class="feedback-text">
                        <?php echo nl2br(htmlspecialchars(substr($feedback['feedback_text'], 0, 150))); ?>...
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>

            <!-- Checklist -->
            <div class="ai-section">
                <div class="ai-section-title">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    Completion Checklist
                </div>
                <ul class="checklist" id="checklist">
                    <?php if ($exercise['interactive_template'] === 'problem_tree'): ?>
                    <li>
                        <div class="checkbox" data-check="problem"></div>
                        <span>Define core problem</span>
                    </li>
                    <li>
                        <div class="checkbox" data-check="causes"></div>
                        <span>Add 3+ root causes</span>
                    </li>
                    <li>
                        <div class="checkbox" data-check="effects"></div>
                        <span>Identify effects</span>
                    </li>
                    <li>
                        <div class="checkbox" data-check="connections"></div>
                        <span>Connect with arrows</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- AI Chat -->
            <div class="ai-section">
                <div class="ai-section-title">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                    </svg>
                    Ask AI Assistant
                </div>
                <div class="ai-chat-box" id="ai-chat-box">
                    <!-- Chat messages will appear here -->
                </div>
                <div class="chat-input-container">
                    <input type="text" id="chat-input" class="chat-input" placeholder="Ask a question...">
                    <button class="btn btn-primary" id="send-chat-btn">Send</button>
                </div>
            </div>
        </div>
    </div>

    <?php exportAIConfigToJS(); ?>

    <script>
        // Global variables
        const exerciseId = <?php echo $exercise_id; ?>;
        const teamId = <?php echo $team_id; ?>;
        const exerciseTemplate = '<?php echo $exercise['interactive_template']; ?>';
        const existingData = <?php echo $existing_data ? json_encode(json_decode($existing_data['data_json'])) : 'null'; ?>;

        // Initialize based on exercise type
        document.addEventListener('DOMContentLoaded', function() {
            if (exerciseTemplate === 'problem_tree') {
                initProblemTree();
            } else if (exerciseTemplate === 'business_model_canvas') {
                initBusinessModelCanvas();
            } else if (exerciseTemplate === 'persona') {
                initPersonaBuilder();
            }

            // Initialize event listeners
            initEventListeners();
        });
    </script>

    <!-- Load exercise-specific script -->
    <?php if ($exercise['interactive_template'] === 'problem_tree'): ?>
        <script src="../assets/js/incubation/problem-tree.js"></script>
    <?php elseif ($exercise['interactive_template'] === 'business_model_canvas'): ?>
        <script src="../assets/js/incubation/business-model-canvas.js"></script>
    <?php elseif ($exercise['interactive_template'] === 'persona'): ?>
        <script src="../assets/js/incubation/persona-builder.js"></script>
    <?php endif; ?>

    <!-- Common AI Assistant script -->
    <script src="../assets/js/incubation/ai-assistant.js"></script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
