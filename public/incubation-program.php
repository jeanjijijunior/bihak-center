<?php
/**
 * Innovation Incubation Program - Landing Page
 * Displays program overview and allows users to join or create teams
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Check if user is an admin
$is_admin = false;
if (isset($_SESSION['admin_id'])) {
    $is_admin = true;
}

// If admin is logged in, redirect to admin dashboard immediately
if ($is_admin) {
    header('Location: admin/incubation-admin-dashboard.php');
    exit;
}

$conn = getDatabaseConnection();

// Get program details
$program_query = "SELECT * FROM incubation_programs WHERE is_active = TRUE LIMIT 1";
$program_result = $conn->query($program_query);
$program = $program_result->fetch_assoc();

if (!$program) {
    die("No active incubation program found. Please contact administrator.");
}

// Get phases with exercise counts
$phases_query = "
    SELECT
        pp.*,
        COUNT(pe.id) as exercise_count
    FROM program_phases pp
    LEFT JOIN program_exercises pe ON pp.id = pe.phase_id AND pe.is_active = TRUE
    WHERE pp.program_id = ? AND pp.is_active = TRUE
    GROUP BY pp.id
    ORDER BY pp.display_order
";
$stmt = $conn->prepare($phases_query);
$stmt->bind_param('i', $program['id']);
$stmt->execute();
$phases_result = $stmt->get_result();
$phases = $phases_result->fetch_all(MYSQLI_ASSOC);

// Check if user has a team and/or progress
$user_team = null;
$has_progress = false;

if ($is_logged_in) {
    $team_query = "
        SELECT t.*
        FROM incubation_teams t
        JOIN incubation_team_members tm ON t.id = tm.team_id
        WHERE tm.user_id = ? AND tm.status = 'active'
        LIMIT 1
    ";
    $stmt = $conn->prepare($team_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $team_result = $stmt->get_result();
    $user_team = $team_result->fetch_assoc();

    // If user has a team, check if they have any progress
    if ($user_team) {
        // Check for any exercise progress or submissions
        $progress_query = "
            SELECT COUNT(*) as progress_count
            FROM (
                SELECT id FROM team_exercise_progress WHERE team_id = ?
                UNION
                SELECT id FROM exercise_submissions WHERE team_id = ?
            ) AS combined_progress
        ";
        $progress_stmt = $conn->prepare($progress_query);
        $progress_stmt->bind_param('ii', $user_team['id'], $user_team['id']);
        $progress_stmt->execute();
        $progress_result = $progress_stmt->get_result();
        $progress_data = $progress_result->fetch_assoc();
        $has_progress = $progress_data['progress_count'] > 0;

        // If user has a team (regardless of progress), redirect to dashboard
        // This gives them access to their workspace even if just starting
        header('Location: incubation-dashboard.php');
        exit;
    }
}

// Get statistics
$stats_query = "
    SELECT
        COUNT(DISTINCT t.id) as total_teams,
        COUNT(DISTINCT tm.user_id) as total_participants,
        COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_teams,
        COUNT(DISTINCT sp.id) as published_projects
    FROM incubation_teams t
    LEFT JOIN team_members tm ON t.id = tm.team_id AND tm.is_active = TRUE
    LEFT JOIN showcase_projects sp ON t.id = sp.team_id AND sp.status = 'published'
    WHERE t.program_id = ?
";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param('i', $program['id']);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

closeDatabaseConnection($conn);

// Get current language
$lang = $_SESSION['lang'] ?? 'en';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'fr' ? $program['program_name_fr'] : $program['program_name']; ?> - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }

        .hero-section {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero-section p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 30px;
            opacity: 0.95;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 35px;
            font-size: 1.1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-primary {
            background: white;
            color: #1cabe2;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #1cabe2;
        }

        .btn-admin {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-admin:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .stats-section {
            background: white;
            padding: 60px 20px;
            margin-top: -30px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .stat-card {
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #1cabe2;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section {
            padding: 80px 20px;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 50px;
            color: #333;
        }

        .phases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .phase-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-top: 5px solid #1cabe2;
        }

        .phase-card:nth-child(2) {
            border-top-color: #f093fb;
        }

        .phase-card:nth-child(3) {
            border-top-color: #4facfe;
        }

        .phase-card:nth-child(4) {
            border-top-color: #43e97b;
        }

        .phase-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .phase-number {
            font-size: 3rem;
            font-weight: 700;
            color: #1cabe2;
            opacity: 0.2;
            margin-bottom: -20px;
        }

        .phase-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }

        .phase-description {
            color: #666;
            margin-bottom: 20px;
        }

        .exercise-count {
            display: inline-block;
            background: #f0f0f0;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        .cta-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            margin-top: 50px;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .team-status {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .team-status.active {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }

        .team-status.completed {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .phases-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/incubation-header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1><?php echo $lang === 'fr' ? $program['program_name_fr'] : $program['program_name']; ?></h1>
        <p><?php echo $lang === 'fr' ? $program['description_fr'] : $program['description']; ?></p>

        <?php if ($user_team): ?>
            <!-- User has a team -->
            <div class="team-status <?php echo $user_team['status']; ?>">
                <?php if ($lang === 'fr'): ?>
                    <h3>Votre équipe: <?php echo htmlspecialchars($user_team['team_name']); ?></h3>
                    <p>Statut: <?php
                        $status_fr = [
                            'forming' => 'En formation',
                            'in_progress' => 'En cours',
                            'completed' => 'Terminé',
                            'archived' => 'Archivé'
                        ];
                        echo $status_fr[$user_team['status']];
                    ?></p>
                    <p>Progression: <?php echo number_format($user_team['completion_percentage'], 1); ?>%</p>
                <?php else: ?>
                    <h3>Your Team: <?php echo htmlspecialchars($user_team['team_name']); ?></h3>
                    <p>Status: <?php echo ucfirst(str_replace('_', ' ', $user_team['status'])); ?></p>
                    <p>Progress: <?php echo number_format($user_team['completion_percentage'], 1); ?>%</p>
                <?php endif; ?>
            </div>

            <div class="hero-buttons">
                <a href="incubation-dashboard.php" class="btn btn-primary">
                    <?php echo $lang === 'fr' ? 'Continuer le programme' : 'Continue Program'; ?>
                </a>
                <a href="incubation-showcase.php" class="btn btn-secondary">
                    <?php echo $lang === 'fr' ? 'Voir les projets' : 'View Projects'; ?>
                </a>
                <?php if ($is_admin): ?>
                    <a href="admin/incubation-admin-dashboard.php" class="btn btn-admin">
                        <?php echo $lang === 'fr' ? '⚙️ Administration' : '⚙️ Admin Dashboard'; ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif ($is_logged_in): ?>
            <!-- User logged in but no team -->
            <div class="hero-buttons">
                <a href="incubation-team-create.php" class="btn btn-primary">
                    <?php echo $lang === 'fr' ? 'Créer une équipe' : 'Create a Team'; ?>
                </a>
                <a href="incubation-team-join.php" class="btn btn-secondary">
                    <?php echo $lang === 'fr' ? 'Rejoindre une équipe' : 'Join a Team'; ?>
                </a>
                <?php if ($is_admin): ?>
                    <a href="admin/incubation-admin-dashboard.php" class="btn btn-admin">
                        <?php echo $lang === 'fr' ? '⚙️ Administration' : '⚙️ Admin Dashboard'; ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Not logged in -->
            <div class="hero-buttons">
                <a href="signup.php" class="btn btn-primary">
                    <?php echo $lang === 'fr' ? 'S\'inscrire maintenant' : 'Sign Up Now'; ?>
                </a>
                <a href="login.php" class="btn btn-secondary">
                    <?php echo $lang === 'fr' ? 'Se connecter' : 'Login'; ?>
                </a>
                <?php if ($is_admin): ?>
                    <a href="admin/incubation-admin-dashboard.php" class="btn btn-admin">
                        <?php echo $lang === 'fr' ? '⚙️ Administration' : '⚙️ Admin Dashboard'; ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Statistics Section -->
    <div class="container">
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_teams']; ?></div>
                    <div class="stat-label"><?php echo $lang === 'fr' ? 'Équipes' : 'Teams'; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_participants']; ?></div>
                    <div class="stat-label"><?php echo $lang === 'fr' ? 'Participants' : 'Participants'; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed_teams']; ?></div>
                    <div class="stat-label"><?php echo $lang === 'fr' ? 'Équipes complétées' : 'Completed Teams'; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['published_projects']; ?></div>
                    <div class="stat-label"><?php echo $lang === 'fr' ? 'Projets publiés' : 'Published Projects'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Program Phases Section -->
    <div class="section">
        <div class="container">
            <h2 class="section-title">
                <?php echo $lang === 'fr' ? 'Les 4 Phases du Programme' : 'The 4 Program Phases'; ?>
            </h2>
            <div class="phases-grid">
                <?php foreach ($phases as $index => $phase): ?>
                <div class="phase-card">
                    <div class="phase-number"><?php echo $index + 1; ?></div>
                    <h3 class="phase-title">
                        <?php echo $lang === 'fr' ? $phase['phase_name_fr'] : $phase['phase_name']; ?>
                    </h3>
                    <p class="phase-description">
                        <?php echo $lang === 'fr' ? $phase['description_fr'] : $phase['description']; ?>
                    </p>
                    <span class="exercise-count">
                        <?php echo $phase['exercise_count']; ?>
                        <?php echo $lang === 'fr' ? 'exercices' : 'exercises'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <div class="container">
            <h2><?php echo $lang === 'fr' ? 'Prêt à transformer votre idée en réalité ?' : 'Ready to Turn Your Idea into Reality?'; ?></h2>
            <p>
                <?php echo $lang === 'fr'
                    ? 'Rejoignez notre programme d\'incubation et bénéficiez de mentorat, ressources et d\'une communauté de soutien.'
                    : 'Join our incubation program and get mentorship, resources, and a supportive community.'; ?>
            </p>
            <?php if (!$is_logged_in): ?>
                <a href="signup.php" class="btn btn-primary">
                    <?php echo $lang === 'fr' ? 'Commencer maintenant' : 'Get Started Now'; ?>
                </a>
            <?php elseif (!$user_team): ?>
                <a href="incubation-team-create.php" class="btn btn-primary">
                    <?php echo $lang === 'fr' ? 'Créer votre équipe' : 'Create Your Team'; ?>
                </a>
            <?php else: ?>
                <a href="incubation-showcase.php" class="btn btn-primary">
                    <?php echo $lang === 'fr' ? 'Explorer les projets' : 'Explore Projects'; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
