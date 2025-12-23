<?php
/**
 * Mentorship Requests Page
 * Manage pending mentorship requests (incoming and outgoing)
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

// Determine user type
$is_mentor = isset($_SESSION['sponsor_id']);
$is_mentee = isset($_SESSION['user_id']);

$mentor_id = $_SESSION['sponsor_id'] ?? null;
$mentee_id = $_SESSION['user_id'] ?? null;

// Get pending requests
$incoming_requests = [];
$outgoing_requests = [];

if ($is_mentor) {
    // Requests where someone wants this mentor
    $incoming_requests = $mentorshipManager->getPendingRequests($mentor_id, 'mentor');
}

if ($is_mentee) {
    // Requests where someone offered to mentor this user
    $incoming_requests = array_merge($incoming_requests, $mentorshipManager->getPendingRequests($mentee_id, 'mentee'));
}

// Get outgoing requests (requests this user made)
$outgoing_query = $conn->prepare("
    SELECT mr.*,
           CASE
               WHEN mr.requested_by = 'mentor' THEN u.full_name
               ELSE s.full_name
           END as other_party_name,
           CASE
               WHEN mr.requested_by = 'mentor' THEN u.email
               ELSE s.email
           END as other_party_email
    FROM mentorship_relationships mr
    LEFT JOIN users u ON u.id = mr.mentee_id
    LEFT JOIN sponsors s ON s.id = mr.mentor_id
    WHERE mr.status = 'pending'
    AND ((mr.requested_by = 'mentor' AND mr.mentor_id = ?)
         OR (mr.requested_by = 'mentee' AND mr.mentee_id = ?))
");
$user_id = $mentor_id ?? $mentee_id;
$outgoing_query->bind_param('ii', $user_id, $user_id);
$outgoing_query->execute();
$outgoing_requests = $outgoing_query->get_result()->fetch_all(MYSQLI_ASSOC);

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentorship Requests - Bihak Center</title>
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
            max-width: 1000px;
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

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .section-header h2 {
            color: #2d3748;
            font-size: 1.5rem;
        }

        .section-header .count {
            color: #667eea;
            font-weight: bold;
        }

        .request-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .request-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .request-info h3 {
            color: #2d3748;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .request-info p {
            color: #718096;
            font-size: 0.9rem;
        }

        .request-meta {
            text-align: right;
            color: #a0aec0;
            font-size: 0.85rem;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 5px;
        }

        .badge-incoming {
            background: #fef5e7;
            color: #d68910;
        }

        .badge-outgoing {
            background: #e8f4fd;
            color: #1e88e5;
        }

        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-accept {
            background: #48bb78;
            color: white;
        }

        .btn-accept:hover {
            background: #38a169;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #f56565;
            color: white;
        }

        .btn-reject:hover {
            background: #e53e3e;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
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

        .match-score-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="page-header">
            <h1>📬 Mentorship Requests</h1>
            <p>Review and respond to mentorship requests</p>
        </div>

        <!-- Incoming Requests -->
        <div class="section">
            <div class="section-header">
                <h2>Incoming Requests <span class="count">(<?php echo count($incoming_requests); ?>)</span></h2>
            </div>

            <?php if (empty($incoming_requests)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3>No incoming requests</h3>
                    <p>You don't have any pending requests at the moment</p>
                </div>
            <?php else: ?>
                <?php foreach ($incoming_requests as $request): ?>
                <div class="request-card" id="request-<?php echo $request['id']; ?>">
                    <div class="request-header">
                        <div class="request-info">
                            <h3>
                                <?php
                                if ($is_mentor && $request['requested_by'] === 'mentee') {
                                    echo htmlspecialchars($request['mentee_name']);
                                    echo ' wants your mentorship';
                                } elseif ($is_mentee && $request['requested_by'] === 'mentor') {
                                    echo htmlspecialchars($request['mentor_name']);
                                    echo ' offered to mentor you';
                                }
                                ?>
                                <?php if (!empty($request['match_score'])): ?>
                                <span class="match-score-badge"><?php echo round($request['match_score']); ?>% Match</span>
                                <?php endif; ?>
                            </h3>
                            <p>
                                <?php
                                if ($is_mentor && $request['requested_by'] === 'mentee') {
                                    echo htmlspecialchars($request['mentee_email']);
                                } elseif ($is_mentee && $request['requested_by'] === 'mentor') {
                                    echo htmlspecialchars($request['mentor_email']);
                                }
                                ?>
                            </p>
                        </div>
                        <div class="request-meta">
                            <span class="badge badge-incoming">Incoming</span><br>
                            <?php echo date('M j, Y', strtotime($request['requested_at'])); ?>
                        </div>
                    </div>

                    <div class="request-actions">
                        <button class="btn btn-accept" onclick="respondToRequest(<?php echo $request['id']; ?>, 'accept')">
                            <i class="fas fa-check"></i> Accept
                        </button>
                        <button class="btn btn-reject" onclick="respondToRequest(<?php echo $request['id']; ?>, 'reject')">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Outgoing Requests -->
        <div class="section">
            <div class="section-header">
                <h2>Outgoing Requests <span class="count">(<?php echo count($outgoing_requests); ?>)</span></h2>
            </div>

            <?php if (empty($outgoing_requests)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <h3>No outgoing requests</h3>
                    <p>You haven't sent any mentorship requests yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($outgoing_requests as $request): ?>
                <div class="request-card">
                    <div class="request-header">
                        <div class="request-info">
                            <h3>
                                <?php
                                if ($request['requested_by'] === 'mentor') {
                                    echo 'You offered mentorship to ';
                                    echo htmlspecialchars($request['other_party_name']);
                                } else {
                                    echo 'You requested mentorship from ';
                                    echo htmlspecialchars($request['other_party_name']);
                                }
                                ?>
                                <?php if (!empty($request['match_score'])): ?>
                                <span class="match-score-badge"><?php echo round($request['match_score']); ?>% Match</span>
                                <?php endif; ?>
                            </h3>
                            <p><?php echo htmlspecialchars($request['other_party_email']); ?></p>
                        </div>
                        <div class="request-meta">
                            <span class="badge badge-outgoing">Outgoing</span><br>
                            <?php echo date('M j, Y', strtotime($request['requested_at'])); ?><br>
                            <small>Waiting for response...</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function respondToRequest(relationshipId, action) {
            const actionText = action === 'accept' ? 'accept' : 'decline';
            if (!confirm(`Are you sure you want to ${actionText} this mentorship request?`)) {
                return;
            }

            // Disable buttons in this card
            const card = document.getElementById('request-' + relationshipId);
            const buttons = card.querySelectorAll('button');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            });

            fetch('../../api/mentorship/respond.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    relationship_id: relationshipId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'accept') {
                        alert('Mentorship accepted!\n\nYou can now work together in your workspace. A direct messaging conversation has been created for you.');
                        // Redirect to workspace or dashboard
                        window.location.href = 'dashboard.php';
                    } else {
                        alert('Request declined.');
                        // Remove the card
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            // Check if section is now empty
                            checkEmptyState();
                        }, 300);
                    }
                } else {
                    alert('Error: ' + data.message);
                    // Re-enable buttons
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    });
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
            });
        }

        function checkEmptyState() {
            const cards = document.querySelectorAll('.request-card');
            if (cards.length === 0) {
                location.reload();
            }
        }
    </script>
</body>
</html>
