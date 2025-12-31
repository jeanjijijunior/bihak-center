<?php
// Determine base path based on current file location
$current_dir = dirname($_SERVER['SCRIPT_FILENAME']);
$dir_name = basename($current_dir);
$parent_dir = basename(dirname($current_dir));

// Check if we're in a subdirectory of public (like mentorship, messages, etc.)
$is_in_public_subdir = ($parent_dir === 'public');
$is_in_public = ($dir_name === 'public');
$is_in_admin = ($dir_name === 'admin');

// Set base path for navigation links
if ($is_in_admin) {
    // In public/admin/ directory
    $base_path = '../';
    $assets_path = '../../assets/';
} elseif ($is_in_public_subdir) {
    // In public/mentorship/ or public/messages/ etc.
    $base_path = '../';
    $assets_path = '../../assets/';
} elseif ($is_in_public) {
    // In public/ directory
    $base_path = '';
    $assets_path = '../assets/';
} else {
    // In root directory
    $base_path = 'public/';
    $assets_path = 'assets/';
}
?>
<!-- Enhanced Header Component - Fixed and Improved -->
<header id="main-header">
    <div class="header-container">
        <!-- Logo (Links to Homepage) -->
        <div class="logo">
            <a href="<?php echo $base_path; ?>index.php" title="Bihak Center - Home">
                <img src="<?php echo $assets_path; ?>images/logob.png" alt="Bihak Center Logo">
            </a>
        </div>

        <!-- Navigation Menu -->
        <nav class="navbar" id="main-navbar">
            <ul class="nav-links">
                <li><a href="<?php echo $base_path; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo $base_path; ?>about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a></li>
                <li><a href="<?php echo $base_path; ?>stories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'stories.php' ? 'active' : ''; ?>">Stories</a></li>
                <li><a href="<?php echo $base_path; ?>work.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'work.php' ? 'active' : ''; ?>">Our Work</a></li>
                <li><a href="<?php echo $base_path; ?>opportunities.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'opportunities.php' ? 'active' : ''; ?>">Opportunities</a></li>
                <li><a href="<?php echo $base_path; ?>contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
            </ul>
        </nav>

        <!-- Right Section (Language, Auth, Admin) -->
        <div class="header-right">
            <!-- Incubation Program Button -->
            <a href="<?php echo $base_path; ?>incubation-program.php" class="btn-incubation" title="Join our innovation Social Innovation Program">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
                <span>Incubation</span>
            </a>

            <!-- Get Involved Button -->
            <a href="<?php echo $base_path; ?>get-involved.php" class="btn-get-involved">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
                <span>Get Involved</span>
            </a>

            <!-- Share Story Button -->
            <a href="<?php echo $base_path; ?>signup.php" class="btn-share-story">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                </svg>
                <span>Share Story</span>
            </a>

            <!-- User Account / Login -->
            <?php
            // Check if user, sponsor/mentor, or admin is logged in
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $is_logged_in = isset($_SESSION['user_id']);
            $is_admin = isset($_SESSION['admin_id']);
            $is_sponsor = isset($_SESSION['sponsor_id']);

            // Get display name for admin
            if ($is_admin && !$is_logged_in && !$is_sponsor) {
                // Admin logged in but not as user or sponsor - get admin name
                if (!isset($_SESSION['user_name'])) {
                    require_once __DIR__ . '/../config/database.php';
                    $conn = getDatabaseConnection();
                    $stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
                    $stmt->bind_param('i', $_SESSION['admin_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $_SESSION['user_name'] = $row['username'];
                    }
                    $stmt->close();
                    closeDatabaseConnection($conn);
                }
                $is_logged_in = true; // Treat admin as logged in
            }

            // Get display name for sponsor/mentor
            if ($is_sponsor && !$is_logged_in) {
                // Sponsor logged in - use their name from session
                if (isset($_SESSION['sponsor_name'])) {
                    $_SESSION['user_name'] = $_SESSION['sponsor_name'];
                }
                $is_logged_in = true; // Treat sponsor as logged in
            }
            ?>

            <?php if ($is_logged_in): ?>
                <!-- Logged In User/Admin -->
                <div class="user-menu">
                    <button class="user-button" id="userMenuToggle">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <?php if ($is_admin && !isset($_SESSION['user_id']) && !isset($_SESSION['sponsor_id'])): ?>
                            <!-- Admin-specific menu -->
                            <a href="<?php echo $base_path; ?>admin/dashboard.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                </svg>
                                Admin Dashboard
                            </a>
                            <a href="<?php echo $base_path; ?>admin/incubation-admin-dashboard.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                                </svg>
                                Incubation Admin
                            </a>
                            <a href="<?php echo $base_path; ?>admin/logout.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                                </svg>
                                Logout
                            </a>
                        <?php elseif ($is_sponsor && !isset($_SESSION['user_id'])): ?>
                            <!-- Mentor/Sponsor-specific menu -->
                            <a href="<?php echo $base_path; ?>mentorship/dashboard.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                </svg>
                                Mentorship Dashboard
                            </a>
                            <a href="<?php echo $base_path; ?>mentorship/preferences.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                </svg>
                                Preferences
                            </a>
                            <a href="<?php echo $base_path; ?>logout.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                                </svg>
                                Logout
                            </a>
                        <?php else: ?>
                            <!-- Regular user menu -->
                            <a href="<?php echo $base_path; ?>my-account.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                My account
                            </a>
                            <a href="<?php echo $base_path; ?>profile.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                                My profile
                            </a>
                            <a href="<?php echo $base_path; ?>logout.php">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                                </svg>
                                Logout
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not Logged In -->
                <a href="<?php echo $base_path; ?>login.php" class="btn-login">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    <span>Login</span>
                </a>
            <?php endif; ?>

            <!-- Language Switcher -->
            <div class="language-switcher">
                <button onclick="switchLanguage('en')" id="lang-en" class="lang-btn active" title="English" data-lang="en">
                    EN
                </button>
                <span class="separator">|</span>
                <button onclick="switchLanguage('fr')" id="lang-fr" class="lang-btn" title="Français" data-lang="fr">
                    FR
                </button>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle navigation menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

<!-- Load Header JavaScript -->
<script src="<?php echo $assets_path; ?>js/header_new.js"></script>

<!-- Load Extended Translation System (ALL modules) -->
<script src="<?php echo $assets_path; ?>js/translations-extended.js"></script>
