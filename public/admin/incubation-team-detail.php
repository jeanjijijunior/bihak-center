<?php
/**
 * Incubation Program - Team Details (Enhanced)
 * Detailed view with member management and messaging capabilities
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

$team_id = $_GET['id'] ?? 0;
$success_message = '';
$error_message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_member') {
        $user_id = intval($_POST['user_id']);
        $role = $_POST['role'] ?? 'member';

        // Check if user is already a member
        $check_query = "SELECT id FROM incubation_team_members WHERE team_id = ? AND user_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('ii', $team_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "User is already a member of this team.";
        } else {
            $insert_query = "INSERT INTO incubation_team_members (team_id, user_id, role, status) VALUES (?, ?, ?, 'active')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param('iis', $team_id, $user_id, $role);
            if ($stmt->execute()) {
                $success_message = "Member added successfully!";
            } else {
                $error_message = "Failed to add member.";
            }
        }
    } elseif ($action === 'remove_member') {
        $member_id = intval($_POST['member_id']);
        $update_query = "UPDATE incubation_team_members SET status = 'removed' WHERE team_id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ii', $team_id, $member_id);
        if ($stmt->execute()) {
            $success_message = "Member removed successfully!";
        } else {
            $error_message = "Failed to remove member.";
        }
    } elseif ($action === 'change_role') {
        $member_id = intval($_POST['member_id']);
        $new_role = $_POST['new_role'];
        $update_query = "UPDATE incubation_team_members SET role = ? WHERE team_id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('sii', $new_role, $team_id, $member_id);
        if ($stmt->execute()) {
            $success_message = "Member role updated successfully!";
        } else {
            $error_message = "Failed to update role.";
        }
    } elseif ($action === 'send_message') {
        require_once __DIR__ . '/../../includes/MessagingManager.php';
        $admin = Auth::user();
        $message_text = trim($_POST['message_text']);
        $send_to = $_POST['send_to'] ?? 'all';

        if (empty($message_text)) {
            $error_message = "Message cannot be empty.";
        } else {
            $sent_count = 0;
            $failed_count = 0;

            // Get team members based on selection
            if ($send_to === 'all') {
                $recipients_query = "SELECT user_id FROM incubation_team_members WHERE team_id = ? AND status = 'active'";
            } elseif ($send_to === 'leaders') {
                $recipients_query = "SELECT user_id FROM incubation_team_members WHERE team_id = ? AND status = 'active' AND role = 'leader'";
            } else {
                $recipients_query = "SELECT user_id FROM incubation_team_members WHERE team_id = ? AND status = 'active' AND role = 'member'";
            }

            $stmt = $conn->prepare($recipients_query);
            $stmt->bind_param('i', $team_id);
            $stmt->execute();
            $recipients_result = $stmt->get_result();
            $recipients = $recipients_result->fetch_all(MYSQLI_ASSOC);

            foreach ($recipients as $recipient) {
                try {
                    MessagingManager::sendDirectMessage(
                        null,
                        $recipient['user_id'],
                        $message_text,
                        'admin',
                        $admin['id']
                    );
                    $sent_count++;
                } catch (Exception $e) {
                    $failed_count++;
                }
            }

            $success_message = "Message sent to {$sent_count} team member(s)." . ($failed_count > 0 ? " {$failed_count} failed." : "");
        }
    }
}

// Get team details
$team_query = "
    SELECT t.*, u.full_name as leader_name, u.email as leader_email
    FROM incubation_teams t
    LEFT JOIN incubation_team_members tm ON t.id = tm.team_id AND tm.role = 'leader' AND tm.status = 'active'
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE t.id = ?
";
$stmt = $conn->prepare($team_query);
$stmt->bind_param('i', $team_id);
$stmt->execute();
$team_result = $stmt->get_result();
$team = $team_result->fetch_assoc();

if (!$team) {
    header('Location: incubation-teams.php');
    exit;
}

// Get team members
$members_query = "
    SELECT u.id, u.full_name, u.email, tm.role, tm.joined_at, tm.status
    FROM incubation_team_members tm
    JOIN users u ON tm.user_id = u.id
    WHERE tm.team_id = ? AND tm.status = 'active'
    ORDER BY tm.role DESC, tm.joined_at
";
$stmt = $conn->prepare($members_query);
$stmt->bind_param('i', $team_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = $members_result->fetch_all(MYSQLI_ASSOC);

// Get available users (not in this team)
$available_users_query = "
    SELECT u.id, u.full_name, u.email
    FROM users u
    WHERE u.id NOT IN (
        SELECT user_id FROM incubation_team_members WHERE team_id = ? AND status = 'active'
    )
    ORDER BY u.full_name
    LIMIT 100
";
$stmt = $conn->prepare($available_users_query);
$stmt->bind_param('i', $team_id);
$stmt->execute();
$available_users_result = $stmt->get_result();
$available_users = $available_users_result->fetch_all(MYSQLI_ASSOC);

// Get exercise progress
$progress_query = "
    SELECT
        ie.exercise_number,
        ie.exercise_title,
        ip.phase_name,
        tep.status,
        tep.started_at,
        tep.completed_at,
        es.submitted_at,
        es.status as submission_status,
        es.feedback
    FROM incubation_exercises ie
    JOIN incubation_phases ip ON ie.phase_id = ip.id
    LEFT JOIN team_exercise_progress tep ON ie.id = tep.exercise_id AND tep.team_id = ?
    LEFT JOIN exercise_submissions es ON ie.id = es.exercise_id AND es.team_id = ? AND es.id = (
        SELECT MAX(id) FROM exercise_submissions WHERE exercise_id = ie.id AND team_id = ?
    )
    WHERE ie.is_active = 1
    ORDER BY ie.exercise_number
";
$progress_stmt = $conn->prepare($progress_query);
$progress_stmt->bind_param('iii', $team_id, $team_id, $team_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$exercises = $progress_result->fetch_all(MYSQLI_ASSOC);

closeDatabaseConnection($conn);

$page_title = 'Team Details - ' . $team['team_name'];
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

        .back-to-teams {
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

        .back-to-teams:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        .team-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .team-header h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }

        .team-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1cabe2;
        }

        .stat-label {
            color: #6b7280;
            margin-top: 5px;
        }

        .sections-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-full {
            grid-column: 1 / -1;
        }

        .section h2 {
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .member-list {
            list-style: none;
        }

        .member-item {
            padding: 15px;
            background: #f9fafb;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .member-email {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .member-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #1cabe2;
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

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.75rem;
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

        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }

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
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h2 {
            margin: 0;
            color: #1f2937;
        }

        .modal-close {
            font-size: 28px;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #1f2937;
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
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        tr:hover {
            background: #f9fafb;
        }

        @media (max-width: 1024px) {
            .sections-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="incubation-teams.php" class="back-to-teams">
            <i class="fas fa-arrow-left"></i> Back to Teams
        </a>

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

        <!-- Team Header -->
        <div class="team-header">
            <h1><i class="fas fa-users"></i> <?php echo htmlspecialchars($team['team_name']); ?></h1>
            <p><strong>Leader:</strong> <?php echo htmlspecialchars($team['leader_name'] ?? 'No leader assigned'); ?></p>
            <div class="team-stats">
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($members); ?></div>
                    <div class="stat-label">Team Members</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo round($team['completion_percentage'], 1); ?>%</div>
                    <div class="stat-label">Completion</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><span class="badge badge-<?php echo $team['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($team['status']); ?></span></div>
                    <div class="stat-label">Status</div>
                </div>
            </div>
        </div>

        <!-- Sections Grid -->
        <div class="sections-grid">
            <!-- Team Members Section -->
            <div class="section">
                <h2>
                    <i class="fas fa-user-friends"></i> Team Members
                    <button class="btn btn-primary btn-sm" onclick="openAddMemberModal()" style="margin-left: auto;">
                        <i class="fas fa-user-plus"></i> Add Member
                    </button>
                </h2>

                <?php if (empty($members)): ?>
                    <p style="text-align: center; color: #6b7280; padding: 20px 0;">No team members yet.</p>
                <?php else: ?>
                    <ul class="member-list">
                        <?php foreach ($members as $member): ?>
                            <li class="member-item">
                                <div class="member-info">
                                    <div class="member-name">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                        <span class="badge badge-<?php echo $member['role'] === 'leader' ? 'primary' : 'secondary'; ?>">
                                            <?php echo ucfirst($member['role']); ?>
                                        </span>
                                    </div>
                                    <div class="member-email"><?php echo htmlspecialchars($member['email']); ?></div>
                                </div>
                                <div class="member-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                        <input type="hidden" name="new_role" value="<?php echo $member['role'] === 'leader' ? 'member' : 'leader'; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Change role">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this member?');">
                                        <input type="hidden" name="action" value="remove_member">
                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Remove member">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Messaging Section -->
            <div class="section">
                <h2><i class="fas fa-envelope"></i> Send Message to Team</h2>

                <form method="POST">
                    <input type="hidden" name="action" value="send_message">

                    <div class="form-group">
                        <label for="send_to">Send To</label>
                        <select name="send_to" id="send_to">
                            <option value="all">All Team Members</option>
                            <option value="leaders">Leaders Only</option>
                            <option value="members">Members Only</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message_text">Message</label>
                        <textarea name="message_text" id="message_text" placeholder="Type your message here..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Exercise Progress Section -->
        <div class="section section-full">
            <h2><i class="fas fa-tasks"></i> Exercise Progress</h2>

            <?php if (empty($exercises)): ?>
                <p style="text-align: center; color: #6b7280; padding: 20px 0;">No exercises found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Exercise</th>
                            <th>Phase</th>
                            <th>Status</th>
                            <th>Started</th>
                            <th>Completed</th>
                            <th>Submission</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exercises as $exercise): ?>
                            <tr>
                                <td><strong><?php echo $exercise['exercise_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($exercise['exercise_title']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($exercise['phase_name']); ?></span></td>
                                <td>
                                    <?php if ($exercise['status'] === 'completed'): ?>
                                        <span class="badge badge-success">Completed</span>
                                    <?php elseif ($exercise['status'] === 'in_progress'): ?>
                                        <span class="badge badge-warning">In Progress</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Not Started</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $exercise['started_at'] ? date('M d, Y', strtotime($exercise['started_at'])) : '-'; ?></td>
                                <td><?php echo $exercise['completed_at'] ? date('M d, Y', strtotime($exercise['completed_at'])) : '-'; ?></td>
                                <td>
                                    <?php if ($exercise['submission_status']): ?>
                                        <span class="badge badge-<?php echo $exercise['submission_status'] === 'approved' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $exercise['submission_status'])); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add Team Member</h2>
                <span class="modal-close" onclick="closeAddMemberModal()">&times;</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="add_member">

                <div class="form-group">
                    <label for="user_id">Select User *</label>
                    <select name="user_id" id="user_id" required>
                        <option value="">-- Select a user --</option>
                        <?php foreach ($available_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="role">Role *</label>
                    <select name="role" id="role" required>
                        <option value="member">Member</option>
                        <option value="leader">Leader</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Add Member
                    </button>
                    <button type="button" class="btn btn-warning" onclick="closeAddMemberModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddMemberModal() {
            document.getElementById('addMemberModal').style.display = 'block';
        }

        function closeAddMemberModal() {
            document.getElementById('addMemberModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addMemberModal');
            if (event.target == modal) {
                closeAddMemberModal();
            }
        }
    </script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
