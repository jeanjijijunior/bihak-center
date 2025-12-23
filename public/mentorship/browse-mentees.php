<?php
/**
 * Browse Mentees Page
 * For mentors to find and offer mentorship to mentees
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MentorshipManager.php';

// Check authentication - must be a mentor (sponsor)
if (!isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php');
    exit;
}

$conn = getDatabaseConnection();
$mentorshipManager = new MentorshipManager($conn);
$mentor_id = $_SESSION['sponsor_id'];

// Get mentor info and capacity
$mentor_query = $conn->prepare("
    SELECT s.*, mp.max_mentees,
           (SELECT COUNT(*) FROM mentorship_relationships mr
            WHERE mr.mentor_id = s.id AND mr.status = 'active') as active_mentees
    FROM sponsors s
    LEFT JOIN mentor_preferences mp ON mp.mentor_id = s.id
    WHERE s.id = ?
");
$mentor_query->bind_param('i', $mentor_id);
$mentor_query->execute();
$mentor = $mentor_query->get_result()->fetch_assoc();

$max_mentees = $mentor['max_mentees'] ?? 3;
$active_mentees = $mentor['active_mentees'] ?? 0;
$at_capacity = $active_mentees >= $max_mentees;

// Get suggested mentees
$suggested_mentees = [];
if (!$at_capacity) {
    $suggested_mentees = $mentorshipManager->getSuggestedMentees($mentor_id, 20);
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Mentees - Bihak Center</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #718096;
            font-size: 1.1rem;
        }

        .capacity-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .capacity-info h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .capacity-bar {
            background: rgba(255, 255, 255, 0.3);
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }

        .capacity-fill {
            background: white;
            height: 100%;
            transition: width 0.3s;
        }

        .mentees-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .mentee-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            position: relative;
        }

        .mentee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .match-score {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .mentee-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .mentee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 15px;
        }

        .mentee-name {
            font-size: 1.4rem;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .mentee-email {
            color: #718096;
            font-size: 0.9rem;
        }

        .mentee-bio {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 15px;
            min-height: 60px;
        }

        .mentee-info {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #718096;
            font-size: 0.9rem;
        }

        .info-row svg {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            color: #a0aec0;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-disabled {
            background: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            color: #cbd5e0;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .empty-state p {
            color: #718096;
            margin-bottom: 20px;
        }

        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="page-header">
            <h1>🌟 Find Mentees</h1>
            <p>Make an impact by guiding aspiring entrepreneurs</p>
        </div>

        <!-- Capacity Banner -->
        <div class="capacity-banner">
            <div class="capacity-info">
                <h3>Your Mentorship Capacity</h3>
                <p><?php echo $active_mentees; ?> / <?php echo $max_mentees; ?> active mentees</p>
                <div class="capacity-bar">
                    <div class="capacity-fill" style="width: <?php echo ($active_mentees / $max_mentees) * 100; ?>%"></div>
                </div>
            </div>
            <?php if ($at_capacity): ?>
            <div style="text-align: right;">
                <strong>At Capacity</strong><br>
                <small>Increase limit in preferences</small>
            </div>
            <?php else: ?>
            <div style="text-align: right;">
                <strong><?php echo $max_mentees - $active_mentees; ?> Slots Available</strong><br>
                <small>Ready to mentor more</small>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($at_capacity): ?>
            <div class="alert">
                <strong>You've reached your mentorship capacity!</strong><br>
                You currently have <?php echo $active_mentees; ?> active mentees. To take on more mentees, please increase your capacity limit in your preferences or end an existing mentorship.
            </div>
        <?php endif; ?>

        <?php if (!empty($suggested_mentees) && !$at_capacity): ?>
            <!-- Mentees Grid -->
            <div class="mentees-grid">
                <?php foreach ($suggested_mentees as $mentee): ?>
                <div class="mentee-card">
                    <div class="match-score">
                        <?php echo round($mentee['match_score']); ?>% Match
                    </div>

                    <div class="mentee-header">
                        <div class="mentee-avatar">
                            <?php echo strtoupper(substr($mentee['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="mentee-name"><?php echo htmlspecialchars($mentee['full_name']); ?></h3>
                            <div class="mentee-email"><?php echo htmlspecialchars($mentee['email']); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($mentee['bio'])): ?>
                    <div class="mentee-bio">
                        <?php echo htmlspecialchars(substr($mentee['bio'], 0, 150)); ?><?php echo strlen($mentee['bio']) > 150 ? '...' : ''; ?>
                    </div>
                    <?php endif; ?>

                    <div class="mentee-info">
                        <?php if (!empty($mentee['location'])): ?>
                        <div class="info-row">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <?php echo htmlspecialchars($mentee['location']); ?>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Looking for mentorship
                        </div>
                    </div>

                    <button class="btn btn-primary" onclick="offerMentorship(<?php echo $mentee['id']; ?>, '<?php echo htmlspecialchars($mentee['full_name']); ?>')">
                        Offer Mentorship
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

        <?php elseif (empty($suggested_mentees) && !$at_capacity): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3>No mentee matches found</h3>
                <p>We couldn't find any mentees matching your expertise. This could mean:</p>
                <ul style="text-align: left; display: inline-block; margin-top: 15px; color: #718096;">
                    <li>You haven't set up your mentor preferences yet</li>
                    <li>No mentees are looking for your areas of expertise</li>
                    <li>All matching mentees already have mentors</li>
                </ul>
                <br><br>
                <a href="preferences.php" class="btn btn-primary" style="max-width: 300px; display: inline-block;">Set Up Your Preferences</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function offerMentorship(menteeId, menteeName) {
            if (!confirm(`Offer mentorship to ${menteeName}?\n\nThey will receive a notification and can choose to accept or decline.`)) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Sending...';

            fetch('../../api/mentorship/request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mentee_id: menteeId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Mentorship offer sent successfully!\n\nThe mentee will be notified and you\'ll receive an update when they respond.');
                    btn.innerHTML = '<i class="fas fa-check"></i> Offer Sent';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-disabled');
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.textContent = 'Offer Mentorship';
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Offer Mentorship';
            });
        }
    </script>
</body>
</html>
