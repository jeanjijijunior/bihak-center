<?php
/**
 * Bihak Center - Home Page (New Version)
 * Displays dynamic profiles from database
 */

require_once __DIR__ . '/../config/database.php';

// Fetch profiles from database
$profiles = [];
try {
    $conn = getDatabaseConnection();

    $query = "SELECT
        id, full_name, title, short_description, profile_image,
        media_type, media_url, city, district, field_of_study,
        created_at, view_count
    FROM profiles
    WHERE status = 'approved' AND is_published = TRUE
    ORDER BY created_at DESC
    LIMIT 9";

    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $profiles[] = $row;
        }
    }

    closeDatabaseConnection($conn);
} catch (Exception $e) {
    error_log('Homepage Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bihak Center - Empowering young people through development and education">
    <meta name="keywords" content="education, development, youth empowerment, Rwanda">
    <meta name="author" content="Bihak Center">

    <title>Bihak Center - Empowering Youth</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/favimg.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/header.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/profiles.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/responsive.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Empowering Young People</h1>
            <p>Share your story. Get support. Inspire others. Join our community of youth making a difference.</p>
            <div class="hero-actions">
                <a href="signup.php" class="cta-button primary">Share Your Story</a>
                <a href="#stories" class="cta-button secondary">View Stories</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="../assets/images/Designer.jpeg" alt="Bihak Center Activities">
        </div>
    </section>

    <!-- Stories Section -->
    <section id="stories" class="profiles-section">
        <div class="section-header">
            <h2>Youth Changing the World</h2>
            <p>Meet the young people we support and the incredible things they're achieving</p>
        </div>

        <div id="profiles-container" class="profiles-grid">
            <?php if (count($profiles) > 0): ?>
                <?php foreach ($profiles as $index => $profile): ?>
                    <div class="profile-card <?php echo $index === 0 ? 'featured' : ''; ?>" data-profile-id="<?php echo $profile['id']; ?>">
                        <?php if ($index === 0): ?>
                            <div class="featured-badge">Latest Story</div>
                        <?php endif; ?>

                        <div class="profile-media">
                            <?php if ($profile['media_type'] === 'video' && !empty($profile['media_url'])): ?>
                                <video src="<?php echo htmlspecialchars($profile['media_url']); ?>" controls></video>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="<?php echo htmlspecialchars($profile['full_name']); ?>">
                            <?php endif; ?>
                        </div>

                        <div class="profile-content">
                            <h3 class="profile-name"><?php echo htmlspecialchars($profile['full_name']); ?></h3>

                            <p class="profile-title"><?php echo htmlspecialchars($profile['title']); ?></p>

                            <p class="profile-description">
                                <?php echo htmlspecialchars(substr($profile['short_description'], 0, 150)); ?>
                                <?php echo strlen($profile['short_description']) > 150 ? '...' : ''; ?>
                            </p>

                            <div class="profile-meta">
                                <span class="location">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                    </svg>
                                    <?php echo htmlspecialchars($profile['city'] . ', ' . $profile['district']); ?>
                                </span>
                                <?php if (!empty($profile['field_of_study'])): ?>
                                    <span class="field">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8.5 2a.5.5 0 0 1 .5.5v9.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 12.293V2.5a.5.5 0 0 1 .5-.5z"/>
                                        </svg>
                                        <?php echo htmlspecialchars($profile['field_of_study']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a href="profile.php?id=<?php echo $profile['id']; ?>" class="profile-link">Read Full Story →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-profiles">
                    <h3>No stories yet</h3>
                    <p>Be the first to share your story!</p>
                    <a href="signup.php" class="btn">Share Your Story</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <div class="load-more-container" style="<?php echo count($profiles) < 8 ? 'display: none;' : ''; ?>">
            <button id="load-more-btn" class="btn-load-more">Load More Stories</button>
            <div id="loading-spinner" class="loading-spinner" style="display: none;">
                <div class="spinner"></div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Have a Story to Share?</h2>
            <p>Join our community of young people making a difference. Share your journey, get support, and inspire others.</p>
            <a href="signup.php" class="cta-button large">Share Your Story Today</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Discover Our Programs</h3>
                <ul>
                    <li><a href="work.html#orientation">Academic & Professional Orientation</a></li>
                    <li><a href="work.html#coaching">Project Development Coaching</a></li>
                    <li><a href="work.html#financial">Financial Support</a></li>
                    <li><a href="work.html#technology">Technology for Development</a></li>
                </ul>
            </div>

            <div class="about-us">
                <h3>About Us</h3>
                <ul>
                    <li><a href="about.html#vision">Our Vision</a></li>
                    <li><a href="about.html#mission">Our Mission</a></li>
                    <li><a href="about.html#motivation">Our Motivation</a></li>
                </ul>
            </div>

            <div class="social-links">
                <h3>Follow Us</h3>
                <ul>
                    <li>
                        <a href="https://facebook.com/bihakcenter" target="_blank" rel="noopener noreferrer">
                            <img src="../assets/images/facebk.png" alt="Facebook">
                            <span>Bihak Center</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://instagram.com/bihakcenter" target="_blank" rel="noopener noreferrer">
                            <img src="../assets/images/image.png" alt="Instagram">
                            <span>Bihak Center</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://twitter.com/bihak_center" target="_blank" rel="noopener noreferrer">
                            <img src="../assets/images/xx.webp" alt="Twitter">
                            <span>@bihak_center</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 Bihak Center | All Rights Reserved</p>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="myBtn" aria-label="Scroll to top">↑</button>

    <!-- JavaScript -->
    <script src="../assets/js/translate.js"></script>
    <script src="../assets/js/header.js"></script>
    <script src="../assets/js/scroll-to-top.js"></script>
    <script src="../assets/js/profiles-loader.js"></script>
</body>
</html>
