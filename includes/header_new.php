<?php
// Determine base path based on current file location
$current_dir = dirname($_SERVER['SCRIPT_FILENAME']);
$is_in_public = (basename($current_dir) === 'public');
$is_in_admin = (basename($current_dir) === 'admin');

// Set base path for navigation links
if ($is_in_admin) {
    $base_path = '../';
    $assets_path = '../../assets/';
} elseif ($is_in_public) {
    $base_path = '';
    $assets_path = '../assets/';
} else {
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

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle navigation menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

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
            <!-- Get Involved Button -->
            <a href="<?php echo $base_path; ?>get-involved.php" class="btn-get-involved">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
                <span>Get involved</span>
            </a>

            <!-- Share Story Button -->
            <a href="<?php echo $base_path; ?>signup.php" class="btn-share-story">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                </svg>
                <span>Share your story</span>
            </a>

            <!-- User Account / Login -->
            <?php
            // Check if user is logged in
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $is_logged_in = isset($_SESSION['user_id']);
            ?>

            <?php if ($is_logged_in): ?>
                <!-- Logged In User -->
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

            <!-- Admin Portal Link (for admins only) -->
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="<?php echo $base_path; ?>admin/dashboard.php" class="btn-admin" title="Admin Portal">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Admin</span>
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
        </div>
    </div>
</header>

<!-- Load Centralized Translation System -->
<script src="<?php echo $assets_path; ?>js/translations.js"></script>
