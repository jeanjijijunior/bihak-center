<?php
/**
 * Mentorship Workspace
 * Collaboration space for mentor-mentee pairs
 * Includes goals tracking, activity log, and relationship management
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MentorshipManager.php';

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get relationship ID
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$relationship_id = intval($_GET['id']);
$conn = getDatabaseConnection();

// Get relationship details and verify access
$rel_query = $conn->prepare("
    SELECT mr.*,
           s.full_name as mentor_name,
           s.email as mentor_email,
           u.full_name as mentee_name,
           u.email as mentee_email
    FROM mentorship_relationships mr
    JOIN sponsors s ON s.id = mr.mentor_id
    JOIN users u ON u.id = mr.mentee_id
    WHERE mr.id = ?
");
$rel_query->bind_param('i', $relationship_id);
$rel_query->execute();
$relationship = $rel_query->get_result()->fetch_assoc();

if (!$relationship) {
    die('Relationship not found');
}

// Verify user has access
$user_id = $_SESSION['user_id'] ?? $_SESSION['sponsor_id'];
$is_mentor = ($relationship['mentor_id'] == $user_id);
$is_mentee = ($relationship['mentee_id'] == $user_id);

if (!$is_mentor && !$is_mentee) {
    die('Access denied');
}

// Get goals
$goals_query = $conn->prepare("
    SELECT * FROM mentorship_goals
    WHERE relationship_id = ?
    ORDER BY
        CASE status
            WHEN 'in_progress' THEN 1
            WHEN 'not_started' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'cancelled' THEN 4
        END,
        CASE priority
            WHEN 'high' THEN 1
            WHEN 'medium' THEN 2
            WHEN 'low' THEN 3
        END,
        target_date ASC
");
$goals_query->bind_param('i', $relationship_id);
$goals_query->execute();
$goals = $goals_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Get activities
$activities_query = $conn->prepare("
    SELECT ma.*, mg.title as goal_title
    FROM mentorship_activities ma
    LEFT JOIN mentorship_goals mg ON mg.id = ma.goal_id
    WHERE ma.relationship_id = ?
    ORDER BY ma.activity_date DESC, ma.created_at DESC
    LIMIT 20
");
$activities_query->bind_param('i', $relationship_id);
$activities_query->execute();
$activities = $activities_query->get_result()->fetch_all(MYSQLI_ASSOC);

closeDatabaseConnection($conn);

// Determine who the "other party" is
$other_party_name = $is_mentor ? $relationship['mentee_name'] : $relationship['mentor_name'];
$other_party_email = $is_mentor ? $relationship['mentee_email'] : $relationship['mentor_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace - <?php echo htmlspecialchars($other_party_name); ?> - Bihak Center</title>
    <link rel="icon" type="image/png" href="../../assets/images/favimg.png">
    <link rel="stylesheet" href="../../assets/css/header_new.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f7fafc;
            color: #2d3748;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .workspace-header {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .workspace-info h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .workspace-info p {
            opacity: 0.9;
        }

        .workspace-actions {
            display: flex;
            gap: 10px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-header h2 {
            color: #2d3748;
            font-size: 1.4rem;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #1cabe2;
            border: 2px solid #1cabe2;
        }

        .btn-secondary:hover {
            background: #1cabe2;
            color: white;
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        /* Goals Section */
        .goal-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            transition: all 0.3s;
        }

        .goal-card:hover {
            border-color: #1cabe2;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .goal-card.completed {
            opacity: 0.7;
            background: #f7fafc;
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .goal-title {
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
            flex: 1;
        }

        .goal-title.completed {
            text-decoration: line-through;
            color: #a0aec0;
        }

        .goal-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-status {
            background: #e8f4fd;
            color: #1e88e5;
        }

        .badge-status.in_progress {
            background: #fff3cd;
            color: #d68910;
        }

        .badge-status.completed {
            background: #d5f4e6;
            color: #0f5132;
        }

        .badge-priority {
            background: #f0f0f0;
            color: #666;
        }

        .badge-priority.high {
            background: #fee;
            color: #c33;
        }

        .badge-priority.medium {
            background: #ffeaa7;
            color: #856404;
        }

        .goal-description {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .goal-actions {
            display: flex;
            gap: 8px;
        }

        .goal-actions button {
            font-size: 0.85rem;
            padding: 5px 12px;
        }

        /* Activity Timeline */
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }

        .activity-item {
            position: relative;
            margin-bottom: 25px;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #1cabe2;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #1cabe2;
        }

        .activity-header {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .activity-description {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .activity-meta {
            color: #a0aec0;
            font-size: 0.8rem;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }

        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #2d3748;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #1cabe2;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .workspace-header {
                flex-direction: column;
                gap: 15px;
            }

            .workspace-actions {
                width: 100%;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <!-- Workspace Header -->
        <div class="workspace-header">
            <div class="workspace-info">
                <h1>🤝 Mentorship with <?php echo htmlspecialchars($other_party_name); ?></h1>
                <p>
                    Started <?php echo date('F j, Y', strtotime($relationship['accepted_at'])); ?>
                    • <?php echo $is_mentor ? 'Your Mentee' : 'Your Mentor'; ?>
                </p>
            </div>
            <div class="workspace-actions">
                <button class="btn btn-secondary" onclick="window.location.href='/messages/inbox.php'">
                    <i class="fas fa-comment-dots"></i> Message
                </button>
                <button class="btn btn-danger" onclick="showEndModal()">
                    End Relationship
                </button>
            </div>
        </div>

        <div class="main-grid">
            <!-- Left Column: Goals -->
            <div>
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-bullseye" style="color: #1cabe2;"></i> Goals</h2>
                        <button class="btn btn-primary btn-sm" onclick="showGoalModal()">+ Add Goal</button>
                    </div>

                    <?php if (empty($goals)): ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p>No goals set yet. Add your first goal to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($goals as $goal): ?>
                        <div class="goal-card <?php echo $goal['status'] === 'completed' ? 'completed' : ''; ?>" data-goal-id="<?php echo $goal['id']; ?>">
                            <div class="goal-header">
                                <div class="goal-title <?php echo $goal['status'] === 'completed' ? 'completed' : ''; ?>">
                                    <?php echo htmlspecialchars($goal['title']); ?>
                                </div>
                            </div>

                            <div class="goal-meta">
                                <span class="badge badge-status <?php echo $goal['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $goal['status'])); ?>
                                </span>
                                <span class="badge badge-priority <?php echo $goal['priority']; ?>">
                                    <?php echo ucfirst($goal['priority']); ?> Priority
                                </span>
                                <?php if ($goal['target_date']): ?>
                                <span class="badge">
                                    Due: <?php echo date('M j', strtotime($goal['target_date'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($goal['description']): ?>
                            <div class="goal-description">
                                <?php echo htmlspecialchars($goal['description']); ?>
                            </div>
                            <?php endif; ?>

                            <div class="goal-actions">
                                <?php if ($goal['status'] !== 'completed'): ?>
                                <button class="btn btn-primary btn-sm" onclick="updateGoalStatus(<?php echo $goal['id']; ?>, 'completed')">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-secondary btn-sm" onclick="editGoal(<?php echo $goal['id']; ?>)">
                                    Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteGoal(<?php echo $goal['id']; ?>)">
                                    Delete
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Activity Timeline -->
            <div>
                <div class="section">
                    <div class="section-header">
                        <h2>📝 Activity Log</h2>
                        <button class="btn btn-primary btn-sm" onclick="showActivityModal()">+ Log Activity</button>
                    </div>

                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>No activities logged yet</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-timeline">
                            <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-header">
                                    <?php echo htmlspecialchars($activity['title']); ?>
                                </div>
                                <?php if ($activity['description']): ?>
                                <div class="activity-description">
                                    <?php echo htmlspecialchars($activity['description']); ?>
                                </div>
                                <?php endif; ?>
                                <div class="activity-meta">
                                    <?php echo ucfirst($activity['activity_type']); ?>
                                    <?php if ($activity['goal_title']): ?>
                                    • Goal: <?php echo htmlspecialchars($activity['goal_title']); ?>
                                    <?php endif; ?>
                                    • <?php echo date('M j, Y', strtotime($activity['activity_date'])); ?>
                                    • by <?php echo ucfirst($activity['created_by']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Goal Modal -->
    <div id="goalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="goalModalTitle">Add New Goal</h3>
            </div>
            <form id="goalForm">
                <input type="hidden" id="goalId" name="id">
                <div class="form-group">
                    <label for="goalTitle">Goal Title *</label>
                    <input type="text" id="goalTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="goalDescription">Description</label>
                    <textarea id="goalDescription" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="goalPriority">Priority</label>
                    <select id="goalPriority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="goalTargetDate">Target Date</label>
                    <input type="date" id="goalTargetDate" name="target_date">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('goalModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Goal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Modal -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Log Activity</h3>
            </div>
            <form id="activityForm">
                <div class="form-group">
                    <label for="activityType">Activity Type *</label>
                    <select id="activityType" name="activity_type" required>
                        <option value="meeting">Meeting</option>
                        <option value="note">Note</option>
                        <option value="milestone">Milestone</option>
                        <option value="resource">Resource Shared</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="activityTitle">Title *</label>
                    <input type="text" id="activityTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="activityDescription">Description</label>
                    <textarea id="activityDescription" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="activityGoal">Related Goal (Optional)</label>
                    <select id="activityGoal" name="goal_id">
                        <option value="">-- None --</option>
                        <?php foreach ($goals as $goal): ?>
                        <option value="<?php echo $goal['id']; ?>"><?php echo htmlspecialchars($goal['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="activityDate">Date</label>
                    <input type="datetime-local" id="activityDate" name="activity_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('activityModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Activity</button>
                </div>
            </form>
        </div>
    </div>

    <!-- End Relationship Modal -->
    <div id="endModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>End Mentorship Relationship</h3>
            </div>
            <form id="endForm">
                <p style="margin-bottom: 20px; color: #718096;">
                    Are you sure you want to end this mentorship? Please provide a reason (this will be shared with the other party).
                </p>
                <div class="form-group">
                    <label for="endReason">Reason *</label>
                    <textarea id="endReason" name="reason" required placeholder="e.g., Goals achieved, time constraints, etc."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('endModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">End Relationship</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const relationshipId = <?php echo $relationship_id; ?>;

        // Modal functions
        function showGoalModal() {
            document.getElementById('goalModalTitle').textContent = 'Add New Goal';
            document.getElementById('goalForm').reset();
            document.getElementById('goalId').value = '';
            document.getElementById('goalModal').classList.add('show');
        }

        function showActivityModal() {
            document.getElementById('activityForm').reset();
            document.getElementById('activityDate').value = new Date().toISOString().slice(0, 16);
            document.getElementById('activityModal').classList.add('show');
        }

        function showEndModal() {
            document.getElementById('endModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Goal Form Submit
        document.getElementById('goalForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                relationship_id: relationshipId,
                title: formData.get('title'),
                description: formData.get('description'),
                priority: formData.get('priority'),
                target_date: formData.get('target_date')
            };

            const goalId = formData.get('id');
            const method = goalId ? 'PUT' : 'POST';
            if (goalId) data.id = parseInt(goalId);

            fetch('../../api/mentorship/goals.php', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(goalId ? 'Goal updated!' : 'Goal created!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        });

        // Activity Form Submit
        document.getElementById('activityForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                relationship_id: relationshipId,
                activity_type: formData.get('activity_type'),
                title: formData.get('title'),
                description: formData.get('description'),
                goal_id: formData.get('goal_id') || null,
                activity_date: formData.get('activity_date')
            };

            fetch('../../api/mentorship/activities.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Activity logged!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        });

        // End Relationship Form Submit
        document.getElementById('endForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const reason = document.getElementById('endReason').value;

            fetch('../../api/mentorship/end.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    relationship_id: relationshipId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Relationship ended. You will both be notified.');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        });

        // Update Goal Status
        function updateGoalStatus(goalId, status) {
            if (!confirm('Mark this goal as completed?')) return;

            fetch('../../api/mentorship/goals.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: goalId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Goal completed! 🎉');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }

        // Delete Goal
        function deleteGoal(goalId) {
            if (!confirm('Delete this goal? This action cannot be undone.')) return;

            fetch('../../api/mentorship/goals.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: goalId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Goal deleted');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
