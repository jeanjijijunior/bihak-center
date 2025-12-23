<?php
/**
 * Incubation Module Header
 * Consistent header for all incubation pages with navigation and logout
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get language
$lang = $_SESSION['lang'] ?? 'en';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['admin_id']);
$user_name = '';

if ($is_logged_in && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_name = $row['full_name'];
    }
    $stmt->close();
    closeDatabaseConnection($conn);
}
?>

<style>
    /*
    Incubation Module Color Scheme:
    - Primary Blue: #6366f1 (Indigo - main brand color, headers, buttons)
    - Secondary Blue: #8b5cf6 (Purple - gradients, accents)
    - Accent Orange: #f59e0b (Amber - admin features, highlights)
    - Success Green: #10b981 (Emerald - success states, completed items)
    - Light variants for backgrounds and hover states
    */

    .incubation-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        padding: 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .incubation-header-top {
        background: rgba(0, 0, 0, 0.1);
        padding: 10px 0;
    }

    .incubation-header-main {
        padding: 20px 0;
    }

    .incubation-header-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .incubation-brand {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .incubation-brand h1 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .incubation-brand a {
        color: white;
        text-decoration: none;
        transition: opacity 0.3s;
    }

    .incubation-brand a:hover {
        opacity: 0.8;
    }

    .incubation-nav {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .incubation-nav-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 8px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border: 2px solid transparent;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .incubation-nav-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: white;
    }

    .incubation-nav-btn.primary {
        background: #f59e0b;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }

    .incubation-nav-btn.primary:hover {
        background: #d97706;
        border-color: #f59e0b;
    }

    .incubation-nav-btn.logout {
        background: transparent;
        border: 2px solid rgba(255, 255, 255, 0.5);
    }

    .incubation-nav-btn.logout:hover {
        background: rgba(231, 76, 60, 0.2);
        border-color: #e74c3c;
    }

    .user-welcome {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-welcome strong {
        color: white;
    }

    @media (max-width: 768px) {
        .incubation-header-container {
            flex-direction: column;
            gap: 15px;
        }

        .incubation-nav {
            flex-wrap: wrap;
            justify-content: center;
        }

        .incubation-brand h1 {
            font-size: 1.2rem;
        }

        .incubation-nav-btn {
            padding: 6px 15px;
            font-size: 0.85rem;
        }
    }
</style>

<header class="incubation-header">
    <div class="incubation-header-top">
        <div class="incubation-header-container">
            <a href="index.php" class="incubation-nav-btn">
                <span>🏠</span>
                <?php echo $lang === 'fr' ? 'Retour au site principal' : 'Return to Main Website'; ?>
            </a>

            <?php if ($is_logged_in): ?>
                <div class="user-welcome">
                    <span>👤</span>
                    <?php echo $lang === 'fr' ? 'Bienvenue,' : 'Welcome,'; ?>
                    <strong><?php echo htmlspecialchars($user_name); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="incubation-header-main">
        <div class="incubation-header-container">
            <div class="incubation-brand">
                <a href="incubation-program.php">
                    <h1>🚀 <?php echo $lang === 'fr' ? 'Programme d\'Incubation' : 'Incubation Program'; ?></h1>
                </a>
            </div>

            <nav class="incubation-nav">
                <?php if ($is_admin): ?>
                    <a href="admin/incubation-admin-dashboard.php" class="incubation-nav-btn primary">
                        <span>⚙️</span>
                        <?php echo $lang === 'fr' ? 'Administration' : 'Admin Dashboard'; ?>
                    </a>

                    <a href="admin/logout.php" class="incubation-nav-btn logout">
                        <span>🚪</span>
                        <?php echo $lang === 'fr' ? 'Déconnexion' : 'Logout'; ?>
                    </a>
                <?php elseif ($is_logged_in): ?>
                    <a href="incubation-dashboard.php" class="incubation-nav-btn">
                        <span>📊</span>
                        <?php echo $lang === 'fr' ? 'Mon Tableau de Bord' : 'My Dashboard'; ?>
                    </a>

                    <a href="incubation-program.php" class="incubation-nav-btn">
                        <span>📚</span>
                        <?php echo $lang === 'fr' ? 'Programme' : 'Program'; ?>
                    </a>

                    <a href="logout.php" class="incubation-nav-btn logout">
                        <span>🚪</span>
                        <?php echo $lang === 'fr' ? 'Déconnexion' : 'Logout'; ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="incubation-nav-btn primary">
                        <span>🔐</span>
                        <?php echo $lang === 'fr' ? 'Connexion' : 'Login'; ?>
                    </a>

                    <a href="signup.php" class="incubation-nav-btn">
                        <span>✨</span>
                        <?php echo $lang === 'fr' ? 'Inscription' : 'Sign Up'; ?>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>
