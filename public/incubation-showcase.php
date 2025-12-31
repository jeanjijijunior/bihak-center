<?php
/**
 * Project Showcase - Public voting page
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

$lang = $_SESSION['lang'] ?? 'en';
$user_id = $_SESSION['user_id'] ?? null;
$conn = getDatabaseConnection();

// Get filter parameters
$tag_filter = $_GET['tag'] ?? 'all';
$sort = $_GET['sort'] ?? 'votes'; // votes, recent, views

// Build query
$where_conditions = ["sp.status = 'published'"];
$params = [];
$param_types = '';

if ($tag_filter !== 'all') {
    $where_conditions[] = "ptr.tag_id = ?";
    $params[] = $tag_filter;
    $param_types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$order_by = match($sort) {
    'votes' => 'sp.total_votes DESC, sp.published_at DESC',
    'recent' => 'sp.published_at DESC',
    'views' => 'sp.view_count DESC, sp.published_at DESC',
    default => 'sp.total_votes DESC'
};

// Get projects
$projects_query = "
    SELECT
        sp.*,
        t.team_name,
        COUNT(DISTINCT pv.id) as vote_count
    FROM showcase_projects sp
    JOIN incubation_teams t ON sp.team_id = t.id
    LEFT JOIN project_votes pv ON sp.id = pv.project_id
    LEFT JOIN project_tag_relations ptr ON sp.id = ptr.project_id
    WHERE $where_clause
    GROUP BY sp.id
    ORDER BY $order_by
";

if (!empty($params)) {
    $stmt = $conn->prepare($projects_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $projects_result = $stmt->get_result();
} else {
    $projects_result = $conn->query($projects_query);
}

$projects = $projects_result->fetch_all(MYSQLI_ASSOC);

// Get all tags for filter
$tags_query = "SELECT * FROM project_tags ORDER BY tag_name";
$tags_result = $conn->query($tags_query);
$tags = $tags_result->fetch_all(MYSQLI_ASSOC);

// Get winner project
$winner_query = "
    SELECT sp.*, t.team_name
    FROM showcase_projects sp
    JOIN incubation_teams t ON sp.team_id = t.id
    WHERE sp.status IN ('published', 'winner')
    ORDER BY sp.total_votes DESC
    LIMIT 1
";
$winner_result = $conn->query($winner_query);
$winner = $winner_result->fetch_assoc();

// Handle voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_project'])) {
    $project_id = $_POST['project_id'] ?? 0;
    $voter_ip = $_SERVER['REMOTE_ADDR'];

    if ($project_id) {
        // Check if already voted
        if ($user_id) {
            $check_vote = "SELECT id FROM project_votes WHERE project_id = ? AND user_id = ?";
            $stmt = $conn->prepare($check_vote);
            $stmt->bind_param('ii', $project_id, $user_id);
        } else {
            $check_vote = "SELECT id FROM project_votes WHERE project_id = ? AND voter_ip = ? AND voted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $stmt = $conn->prepare($check_vote);
            $stmt->bind_param('is', $project_id, $voter_ip);
        }

        $stmt->execute();
        $existing_vote = $stmt->get_result()->fetch_assoc();

        if (!$existing_vote) {
            // Cast vote
            if ($user_id) {
                $insert_vote = "INSERT INTO project_votes (project_id, voter_type, user_id, voter_ip) VALUES (?, 'user', ?, ?)";
                $stmt = $conn->prepare($insert_vote);
                $stmt->bind_param('iis', $project_id, $user_id, $voter_ip);
            } else {
                $insert_vote = "INSERT INTO project_votes (project_id, voter_type, voter_ip) VALUES (?, 'guest', ?)";
                $stmt = $conn->prepare($insert_vote);
                $stmt->bind_param('is', $project_id, $voter_ip);
            }

            $stmt->execute();

            // Update project vote count
            $update_count = "UPDATE showcase_projects SET total_votes = total_votes + 1 WHERE id = ?";
            $stmt = $conn->prepare($update_count);
            $stmt->bind_param('i', $project_id);
            $stmt->execute();

            // Reload page
            header("Location: incubation-showcase.php?voted=1");
            exit;
        }
    }
}

closeDatabaseConnection($conn);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'fr' ? 'Projets d\'Innovation' : 'Innovation Projects'; ?> - Bihak Center</title>
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

        .hero {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .winner-section {
            margin: -50px auto 40px;
            max-width: 1200px;
            padding: 0 20px;
        }

        .winner-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .winner-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .winner-content {
            position: relative;
            z-index: 2;
        }

        .winner-content h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .winner-content p {
            font-size: 1.2rem;
            margin-bottom: 20px;
            opacity: 0.95;
        }

        .winner-stats {
            display: flex;
            gap: 40px;
            margin-top: 25px;
        }

        .winner-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .filters-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            align-items: center;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-tag {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-tag:hover {
            border-color: #1cabe2;
            background: #f8f9ff;
        }

        .filter-tag.active {
            background: #1cabe2;
            color: white;
            border-color: #1cabe2;
        }

        .sort-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .project-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .project-image {
            height: 200px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
        }

        .project-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .project-title {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 10px;
        }

        .project-team {
            color: #1cabe2;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .project-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            flex: 1;
        }

        .project-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .project-stats {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .vote-btn {
            padding: 10px 25px;
            background: #1cabe2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .vote-btn:hover {
            background: #5568d3;
            transform: scale(1.05);
        }

        .vote-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .winner-content h2 {
                font-size: 1.8rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/incubation-header.php'; ?>

    <div class="hero">
        <h1><?php echo $lang === 'fr' ? '🏆 Projets d\'Innovation' : '🏆 Innovation Projects'; ?></h1>
        <p><?php echo $lang === 'fr' ? 'Découvrez les projets créatifs de nos équipes et votez pour vos favoris' : 'Discover creative projects from our teams and vote for your favorites'; ?></p>
    </div>

    <?php if ($winner): ?>
    <div class="winner-section">
        <div class="winner-card">
            <div class="winner-badge">
                <?php echo $lang === 'fr' ? '👑 En tête' : '👑 Leading'; ?>
            </div>
            <div class="winner-content">
                <h2><?php echo $lang === 'fr' ? $winner['project_title_fr'] : $winner['project_title']; ?></h2>
                <div class="project-team"><?php echo $lang === 'fr' ? 'Par' : 'By'; ?> <?php echo htmlspecialchars($winner['team_name']); ?></div>
                <p><?php echo $lang === 'fr' ? $winner['short_description_fr'] : $winner['short_description']; ?></p>
                <div class="winner-stats">
                    <div class="winner-stat">
                        <span>❤️</span>
                        <strong><?php echo $winner['total_votes']; ?></strong>
                        <?php echo $lang === 'fr' ? 'votes' : 'votes'; ?>
                    </div>
                    <div class="winner-stat">
                        <span>👁️</span>
                        <strong><?php echo $winner['view_count']; ?></strong>
                        <?php echo $lang === 'fr' ? 'vues' : 'views'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container">
        <!-- Filters -->
        <div class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <a href="?tag=all&sort=<?php echo $sort; ?>"
                       class="filter-tag <?php echo $tag_filter === 'all' ? 'active' : ''; ?>">
                        <?php echo $lang === 'fr' ? 'Tous' : 'All'; ?>
                    </a>
                    <?php foreach ($tags as $tag): ?>
                        <a href="?tag=<?php echo $tag['id']; ?>&sort=<?php echo $sort; ?>"
                           class="filter-tag <?php echo $tag_filter == $tag['id'] ? 'active' : ''; ?>">
                            <?php echo $lang === 'fr' ? $tag['tag_name_fr'] : $tag['tag_name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <select class="sort-select" onchange="window.location.href='?tag=<?php echo $tag_filter; ?>&sort=' + this.value">
                    <option value="votes" <?php echo $sort === 'votes' ? 'selected' : ''; ?>>
                        <?php echo $lang === 'fr' ? 'Plus de votes' : 'Most Votes'; ?>
                    </option>
                    <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>
                        <?php echo $lang === 'fr' ? 'Plus récents' : 'Most Recent'; ?>
                    </option>
                    <option value="views" <?php echo $sort === 'views' ? 'selected' : ''; ?>>
                        <?php echo $lang === 'fr' ? 'Plus vus' : 'Most Viewed'; ?>
                    </option>
                </select>

                <div style="color: #666;">
                    <?php echo count($projects); ?>
                    <?php echo $lang === 'fr' ? 'projets' : 'projects'; ?>
                </div>
            </div>
        </div>

        <!-- Projects Grid -->
        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h2><?php echo $lang === 'fr' ? 'Aucun projet trouvé' : 'No Projects Found'; ?></h2>
                <p><?php echo $lang === 'fr' ? 'Aucun projet publié pour le moment.' : 'No published projects yet.'; ?></p>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="project-image">
                            💡
                        </div>
                        <div class="project-content">
                            <h3 class="project-title">
                                <?php echo $lang === 'fr' ? htmlspecialchars($project['project_title_fr']) : htmlspecialchars($project['project_title']); ?>
                            </h3>
                            <div class="project-team">
                                <?php echo $lang === 'fr' ? 'Par' : 'By'; ?> <?php echo htmlspecialchars($project['team_name']); ?>
                            </div>
                            <p class="project-description">
                                <?php echo $lang === 'fr' ? htmlspecialchars($project['short_description_fr']) : htmlspecialchars($project['short_description']); ?>
                            </p>
                            <div class="project-footer">
                                <div class="project-stats">
                                    <div class="stat-item">
                                        <span>❤️</span>
                                        <span><?php echo $project['total_votes']; ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span>👁️</span>
                                        <span><?php echo $project['view_count']; ?></span>
                                    </div>
                                </div>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <button type="submit" name="vote_project" class="vote-btn">
                                        <?php echo $lang === 'fr' ? 'Voter' : 'Vote'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
