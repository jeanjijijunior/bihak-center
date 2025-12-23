<?php
/**
 * Browse Mentors Page
 * For mentees to find and request mentors
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MentorshipManager.php';

// Check authentication - must be a user (mentee)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$conn = getDatabaseConnection();
$mentorshipManager = new MentorshipManager($conn);
$mentee_id = $_SESSION['user_id'];

// Check if mentee already has active mentor
$active_mentors = $mentorshipManager->getActiveRelationships($mentee_id, 'mentee');
$has_active_mentor = !empty($active_mentors);

// Get suggested mentors
$suggested_mentors = [];
if (!$has_active_mentor) {
    $suggested_mentors = $mentorshipManager->getSuggestedMentors($mentee_id, 20);
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Mentor - Bihak Center</title>
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

        .filters {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filters h3 {
            margin-bottom: 15px;
            color: #2d3748;
        }

        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .mentors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .mentor-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .mentor-card:hover {
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

        .mentor-header {
            margin-bottom: 15px;
        }

        .mentor-name {
            font-size: 1.4rem;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .mentor-role {
            color: #667eea;
            font-weight: 600;
            text-transform: capitalize;
        }

        .mentor-info {
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

        .mentor-expertise {
            margin-bottom: 20px;
        }

        .expertise-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag {
            background: #edf2f7;
            color: #4a5568;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .mentor-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }

        .stat {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 4px;
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

        .alert-info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
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

        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="page-header">
            <h1>🔍 Find Your Mentor</h1>
            <p>Connect with experienced mentors who can guide you on your journey</p>
        </div>

        <?php if ($has_active_mentor): ?>
            <div class="alert">
                <strong>You already have an active mentor!</strong><br>
                You can only have one active mentorship at a time. Visit your <a href="workspace.php?id=<?php echo $active_mentors[0]['id']; ?>">workspace</a> to work with your current mentor.
            </div>
        <?php endif; ?>

        <?php if (!empty($suggested_mentors) && !$has_active_mentor): ?>
            <!-- Filters -->
            <div class="filters">
                <h3>Filter Mentors</h3>
                <div class="filter-group">
                    <select id="sectorFilter" onchange="filterMentors()">
                        <option value="">All Sectors</option>
                        <option value="technology">Technology</option>
                        <option value="education">Education</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="agriculture">Agriculture</option>
                        <option value="social">Social Entrepreneurship</option>
                    </select>
                    <select id="scoreFilter" onchange="filterMentors()">
                        <option value="0">All Match Scores</option>
                        <option value="80">80%+ Match</option>
                        <option value="60">60%+ Match</option>
                        <option value="40">40%+ Match</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Search by name..." onkeyup="filterMentors()">
                </div>
            </div>

            <!-- Mentors Grid -->
            <div class="mentors-grid" id="mentorsGrid">
                <?php foreach ($suggested_mentors as $mentor): ?>
                <div class="mentor-card"
                     data-name="<?php echo htmlspecialchars(strtolower($mentor['full_name'])); ?>"
                     data-score="<?php echo $mentor['match_score']; ?>"
                     data-sector="<?php echo htmlspecialchars(strtolower($mentor['expertise_domain'] ?? '')); ?>">

                    <div class="match-score">
                        <?php echo round($mentor['match_score']); ?>% Match
                    </div>

                    <div class="mentor-header">
                        <h3 class="mentor-name"><?php echo htmlspecialchars($mentor['full_name']); ?></h3>
                        <div class="mentor-role"><?php echo htmlspecialchars($mentor['role_type']); ?></div>
                    </div>

                    <div class="mentor-info">
                        <?php if (!empty($mentor['organization'])): ?>
                        <div class="info-row">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <?php echo htmlspecialchars($mentor['organization']); ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($mentor['expertise_domain'])): ?>
                        <div class="info-row">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            <?php echo htmlspecialchars($mentor['expertise_domain']); ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($mentor['availability'])): ?>
                        <div class="info-row">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php echo htmlspecialchars($mentor['availability']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mentor-stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo $mentor['active_mentees'] ?? 0; ?></div>
                            <div class="stat-label">Active Mentees</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $mentor['max_mentees'] ?? 3; ?></div>
                            <div class="stat-label">Max Capacity</div>
                        </div>
                    </div>

                    <button class="btn btn-primary" onclick="requestMentorship(<?php echo $mentor['id']; ?>, '<?php echo htmlspecialchars($mentor['full_name']); ?>')">
                        Request Mentorship
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="noResults" class="empty-state" style="display: none;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3>No mentors found</h3>
                <p>Try adjusting your filters</p>
            </div>

        <?php elseif (empty($suggested_mentors) && !$has_active_mentor): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3>No mentor matches found</h3>
                <p>We couldn't find any mentors matching your profile. This could mean:</p>
                <ul style="text-align: left; display: inline-block; margin-top: 15px; color: #718096;">
                    <li>You haven't set up your mentee preferences yet</li>
                    <li>No mentors are available in your areas of interest</li>
                    <li>All matching mentors are at capacity</li>
                </ul>
                <br><br>
                <a href="preferences.php" class="btn btn-primary" style="max-width: 300px; display: inline-block;">Set Up Your Preferences</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterMentors() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const sectorFilter = document.getElementById('sectorFilter').value.toLowerCase();
            const scoreFilter = parseInt(document.getElementById('scoreFilter').value);

            const cards = document.querySelectorAll('.mentor-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const score = parseFloat(card.getAttribute('data-score'));
                const sector = card.getAttribute('data-sector');

                let matchesSearch = !searchInput || name.includes(searchInput);
                let matchesSector = !sectorFilter || sector.includes(sectorFilter);
                let matchesScore = score >= scoreFilter;

                if (matchesSearch && matchesSector && matchesScore) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide no results message
            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
            document.getElementById('mentorsGrid').style.display = visibleCount === 0 ? 'none' : 'grid';
        }

        function requestMentorship(mentorId, mentorName) {
            if (!confirm(`Send mentorship request to ${mentorName}?`)) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Sending...';

            fetch('../../api/mentorship/request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mentor_id: mentorId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Mentorship request sent successfully!\n\nThe mentor will be notified and you\'ll receive an update when they respond.');
                    btn.innerHTML = '<i class="fas fa-check"></i> Request Sent';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-disabled');
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.textContent = 'Request Mentorship';
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Request Mentorship';
            });
        }
    </script>
</body>
</html>
