<!-- Enhanced Header Component -->
<header id="main-header">
    <div class="header-container">
        <!-- Logo (Links to Homepage) -->
        <div class="logo">
            <a href="index.php" title="Bihak Center - Home">
                <img src="../assets/images/logob.png" alt="Bihak Center Logo">
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
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a></li>
                <li><a href="work.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'work.php' ? 'active' : ''; ?>">Our Work</a></li>
                <li><a href="contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
                <li><a href="opportunities.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'opportunities.php' ? 'active' : ''; ?>">Opportunities</a></li>
                <li><a href="signup.php" class="cta-nav <?php echo basename($_SERVER['PHP_SELF']) == 'signup.php' ? 'active' : ''; ?>">Share Your Story</a></li>
            </ul>
        </nav>

        <!-- Language Switcher -->
        <div class="language-switcher">
            <a href="#" onclick="changeLanguage('en'); return false;" id="lang-en" class="active" title="Switch to English">
                <span>EN</span>
            </a>
            <span class="separator">|</span>
            <a href="#" onclick="changeLanguage('fr'); return false;" id="lang-fr" title="Switch to French">
                <span>FR</span>
            </a>
        </div>
    </div>
</header>

<!-- Google Translate Element (Hidden) -->
<div id="google_translate_element" style="display: none;"></div>
