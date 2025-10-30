<!-- Enhanced Header Component -->
<header id="main-header">
    <div class="header-container">
        <!-- Logo (Links to Homepage) -->
        <div class="logo">
            <a href="index_new.php" title="Bihak Center - Home">
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
                <li><a href="index_new.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index_new.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="about.html" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.html' ? 'active' : ''; ?>">About</a></li>
                <li><a href="work.html" class="<?php echo basename($_SERVER['PHP_SELF']) == 'work.html' ? 'active' : ''; ?>">Our Work</a></li>
                <li><a href="contact.html" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.html' ? 'active' : ''; ?>">Contact</a></li>
                <li><a href="opportunities.html" class="<?php echo basename($_SERVER['PHP_SELF']) == 'opportunities.html' ? 'active' : ''; ?>">Opportunities</a></li>
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
