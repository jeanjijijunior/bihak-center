<?php
/**
 * Mentorship Dashboard
 * Main hub for mentorship activities
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MentorshipManager.php';

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php');
    exit;
}

$conn = getDatabaseConnection();
$mentorshipManager = new MentorshipManager($conn);

// Determine if user is mentor, mentee, or both
$is_mentor = isset($_SESSION['sponsor_id']);
$is_mentee = isset($_SESSION['user_id']);

$mentor_id = $_SESSION['sponsor_id'] ?? null;
$mentee_id = $_SESSION['user_id'] ?? null;

// Get active relationships
$active_as_mentor = $is_mentor ? $mentorshipManager->getActiveRelationships($mentor_id, 'mentor') : [];
$active_as_mentee = $is_mentee ? $mentorshipManager->getActiveRelationships($mentee_id, 'mentee') : [];

// Get pending requests
$pending_as_mentor = $is_mentor ? $mentorshipManager->getPendingRequests($mentor_id, 'mentor') : [];
$pending_as_mentee = $is_mentee ? $mentorshipManager->getPendingRequests($mentee_id, 'mentee') : [];

// Check for success messages
$success_message = '';
if (isset($_GET['preferences_saved'])) {
    $success_message = 'Your preferences have been saved successfully!';
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentorship Dashboard - Bihak Center</title>
    <link rel="icon" type="image/png" href="../../assets/images/favimg.png">
    <link rel="stylesheet" href="../../assets/css/header_new.css">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            color: #1cabe2;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #718096;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card .number {
            font-size: 3rem;
            font-weight: bold;
            color: #1cabe2;
        }

        .stat-card .label {
            color: #718096;
            font-size: 1rem;
            margin-top: 8px;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            color: #2d3748;
            font-size: 1.5rem;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
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
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .relationship-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .relationship-card:hover {
            border-color: #1cabe2;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .relationship-info {
            flex: 1;
        }

        .relationship-info h3 {
            color: #2d3748;
            margin-bottom: 8px;
        }

        .relationship-info p {
            color: #718096;
            font-size: 0.9rem;
        }

        .relationship-actions {
            display: flex;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin-bottom: 20px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #fef5e7;
            color: #d68910;
        }

        .badge-active {
            background: #d5f4e6;
            color: #0f5132;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="container">
        <div class="dashboard-header">
            <h1>🤝 Mentorship Dashboard</h1>
            <p>Connect, learn, and grow together</p>
        </div>

        <?php if ($success_message): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #10b981;">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo count($active_as_mentor); ?></div>
                <div class="label">Active Mentees</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo count($active_as_mentee); ?></div>
                <div class="label">Active Mentors</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo count($pending_as_mentor) + count($pending_as_mentee); ?></div>
                <div class="label">Pending Requests</div>
            </div>
        </div>

        <!-- Active Mentorships as Mentor -->
        <?php if ($is_mentor): ?>
        <div class="section">
            <div class="section-header">
                <h2>Your Mentees</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="preferences.php" class="btn btn-secondary">⚙️ Preferences</a>
                    <a href="browse-mentees.php" class="btn btn-secondary">Find Mentees</a>
                </div>
            </div>

            <?php if (empty($active_as_mentor)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3>No Active Mentees Yet</h3>
                    <p>Start making an impact by offering mentorship to aspiring entrepreneurs</p>
                    <a href="browse-mentees.php" class="btn btn-primary">Browse Mentees</a>
                </div>
            <?php else: ?>
                <?php foreach ($active_as_mentor as $rel): ?>
                <div class="relationship-card">
                    <div class="relationship-info">
                        <h3><?php echo htmlspecialchars($rel['mentee_name']); ?></h3>
                        <p>
                            <?php echo htmlspecialchars($rel['mentee_email']); ?>
                            • Started <?php echo date('M j, Y', strtotime($rel['accepted_at'])); ?>
                        </p>
                    </div>
                    <div class="relationship-actions">
                        <a href="workspace.php?id=<?php echo $rel['id']; ?>" class="btn btn-primary">Open Workspace</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Active Mentorships as Mentee -->
        <?php if ($is_mentee): ?>
        <div class="section">
            <div class="section-header">
                <h2>Your Mentors</h2>
                <a href="browse-mentors.php" class="btn btn-secondary">Find Mentors</a>
            </div>

            <?php if (empty($active_as_mentee)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <h3>No Active Mentor Yet</h3>
                    <p>Find an experienced mentor to guide you on your entrepreneurial journey</p>
                    <a href="browse-mentors.php" class="btn btn-primary">Find a Mentor</a>
                </div>
            <?php else: ?>
                <?php foreach ($active_as_mentee as $rel): ?>
                <div class="relationship-card">
                    <div class="relationship-info">
                        <h3><?php echo htmlspecialchars($rel['mentor_name']); ?></h3>
                        <p>
                            <?php echo htmlspecialchars($rel['mentor_email']); ?>
                            • Started <?php echo date('M j, Y', strtotime($rel['accepted_at'])); ?>
                        </p>
                    </div>
                    <div class="relationship-actions">
                        <a href="workspace.php?id=<?php echo $rel['id']; ?>" class="btn btn-primary">Open Workspace</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pending Requests -->
        <?php if (!empty($pending_as_mentor) || !empty($pending_as_mentee)): ?>
        <div class="section">
            <div class="section-header">
                <h2>Pending Requests</h2>
                <a href="requests.php" class="btn btn-secondary">View All</a>
            </div>

            <?php foreach ($pending_as_mentor as $req): ?>
            <div class="relationship-card">
                <div class="relationship-info">
                    <h3><?php echo htmlspecialchars($req['mentee_name']); ?></h3>
                    <p>
                        Requested your mentorship • <?php echo date('M j, Y', strtotime($req['requested_at'])); ?>
                    </p>
                </div>
                <div class="relationship-actions">
                    <span class="badge badge-pending">Pending</span>
                    <a href="requests.php#request-<?php echo $req['id']; ?>" class="btn btn-primary">Respond</a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php foreach ($pending_as_mentee as $req): ?>
            <div class="relationship-card">
                <div class="relationship-info">
                    <h3><?php echo htmlspecialchars($req['mentor_name']); ?></h3>
                    <p>
                        Offered to mentor you • <?php echo date('M j, Y', strtotime($req['requested_at'])); ?>
                    </p>
                </div>
                <div class="relationship-actions">
                    <span class="badge badge-pending">Pending</span>
                    <a href="requests.php#request-<?php echo $req['id']; ?>" class="btn btn-primary">Respond</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="action-buttons">
            <?php if ($is_mentee): ?>
            <a href="browse-mentors.php" class="btn btn-primary">Find a Mentor</a>
            <?php endif; ?>

            <?php if ($is_mentor): ?>
            <a href="browse-mentees.php" class="btn btn-primary">Find Mentees</a>
            <?php endif; ?>

            <?php if (!empty($pending_as_mentor) || !empty($pending_as_mentee)): ?>
            <a href="requests.php" class="btn btn-secondary">View Requests (<?php echo count($pending_as_mentor) + count($pending_as_mentee); ?>)</a>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Include chat widget
    include __DIR__ . '/../../includes/chat_widget.php';
    ?>

    <?php include __DIR__ . '/../../includes/footer_new.php'; ?>
</body>
</html>
