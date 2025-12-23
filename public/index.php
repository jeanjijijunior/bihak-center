<?php
/**
 * Bihak Center - Home Page (New Version)
 * Displays dynamic profiles from database
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    <link rel="stylesheet" href="../assets/css/header_new.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Poppins', sans-serif;
            line-height: 1.6;
            color: #2d3748;
        }

        /* Hero Section */
        .hero {
            position: relative;
            min-height: 600px;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 650px;
            padding: 80px 20px 80px 60px;
            background: linear-gradient(to right, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0.95) 70%, rgba(255, 255, 255, 0) 100%);
        }

        .hero-content::before {
            content: '';
            position: absolute;
            top: 0;
            right: -100px;
            bottom: 0;
            width: 200px;
            background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.3) 100%);
            transform: skewX(-15deg);
            z-index: -1;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 40px;
            color: #4a5568;
            line-height: 1.8;
            font-weight: 400;
        }

        .hero-actions {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .hero-image {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 60%;
            z-index: 1;
        }

        .hero-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150px;
            bottom: 0;
            width: 200px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, transparent 100%);
            transform: skewX(-15deg);
            z-index: 2;
        }

        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            clip-path: polygon(15% 0, 100% 0, 100% 100%, 0 100%);
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 18px 36px;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.02em;
            position: relative;
            overflow: hidden;
        }

        .cta-button.primary {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(28, 171, 226, 0.4);
        }

        .cta-button.primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #147ba5 0%, #0d5a7a 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .cta-button.primary:hover::before {
            opacity: 1;
        }

        .cta-button.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(28, 171, 226, 0.5);
        }

        .cta-button.secondary {
            background: transparent;
            color: #1cabe2;
            border: 2px solid #1cabe2;
            box-shadow: 0 4px 15px rgba(28, 171, 226, 0.15);
        }

        .cta-button.secondary:hover {
            background: #1cabe2;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(28, 171, 226, 0.3);
        }

        .cta-button span {
            position: relative;
            z-index: 1;
        }

        /* Profiles Section */
        .profiles-section {
            padding: 60px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            color: #1cabe2;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Profiles Grid */
        .profiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        /* Profile Card */
        .profile-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Featured Card */
        .profile-card.featured {
            grid-column: span 2;
            grid-row: span 2;
        }

        .featured-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Profile Media */
        .profile-media {
            width: 100%;
            height: 250px;
            overflow: hidden;
            background: #f5f5f5;
            position: relative;
        }

        .featured .profile-media {
            height: 400px;
        }

        .profile-media img,
        .profile-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .profile-card:hover .profile-media img,
        .profile-card:hover .profile-media video {
            transform: scale(1.05);
        }

        /* Profile Content */
        .profile-content {
            padding: 20px;
        }

        .featured .profile-content {
            padding: 30px;
        }

        .profile-name {
            font-size: 1.3rem;
            color: #1cabe2;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .featured .profile-name {
            font-size: 1.8rem;
        }

        .profile-title {
            font-size: 1rem;
            color: #333;
            font-weight: 600;
            margin: 0 0 15px 0;
            line-height: 1.4;
        }

        .featured .profile-title {
            font-size: 1.2rem;
        }

        .profile-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .featured .profile-description {
            font-size: 1.05rem;
        }

        /* Profile Meta */
        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: #777;
        }

        .profile-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .profile-link {
            display: inline-block;
            color: #1cabe2;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .profile-link:hover {
            color: #147ba5;
            text-decoration: underline;
        }

        /* No Profiles */
        .no-profiles {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 15px;
        }

        .no-profiles h3 {
            color: #1cabe2;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .no-profiles p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        /* Load More */
        .load-more-container {
            text-align: center;
            padding: 20px 0;
        }

        .btn-load-more {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-load-more:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 171, 226, 0.3);
        }

        .btn-load-more:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Loading Spinner */
        .loading-spinner {
            text-align: center;
            padding: 20px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1cabe2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
            margin: 40px 0 0 0;
        }

        .cta-content h2 {
            font-size: 2.2rem;
            margin-bottom: 15px;
        }

        .cta-content p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 30px;
            opacity: 0.95;
        }

        .cta-button.large {
            padding: 18px 45px;
            font-size: 1.2rem;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #0d4d6b 0%, #1cabe2 50%, #147ba5 100%);
            color: white;
            padding: 0;
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 25%, #1cabe2 50%, #147ba5 75%, #fbbf24 100%);
            animation: shimmer 3s linear infinite;
            background-size: 200% 100%;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 50px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 40px 40px;
            position: relative;
        }

        .footer-section, .about-us, .social-links {
            position: relative;
        }

        .footer-section h3, .about-us h3, .social-links h3 {
            font-size: 1.4rem;
            margin-bottom: 25px;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }

        .footer-section h3::after, .about-us h3::after, .social-links h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 2px;
        }

        .footer-section ul, .about-us ul, .social-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li, .about-us ul li {
            margin-bottom: 14px;
        }

        .social-links ul {
            margin-top: 10px;
        }

        .social-links ul li {
            margin-bottom: 18px;
        }

        .footer-section a, .about-us a, .social-links a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
            position: relative;
            padding-left: 20px;
        }

        .footer-section a::before, .about-us a::before {
            content: '→';
            position: absolute;
            left: 0;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
            color: #fbbf24;
            font-weight: bold;
        }

        .footer-section a:hover, .about-us a:hover, .social-links a:hover {
            color: white;
            padding-left: 25px;
            transform: translateX(5px);
        }

        .footer-section a:hover::before, .about-us a:hover::before {
            opacity: 1;
            transform: translateX(0);
        }

        .social-links a {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 18px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px) translateX(0);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            padding-left: 20px;
        }

        .social-links img {
            width: 36px;
            height: 36px;
            object-fit: contain;
            transition: transform 0.3s ease;
            background: white;
            border-radius: 8px;
            padding: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .social-links a:hover img {
            transform: scale(1.15) rotate(5deg);
        }

        .footer-bottom {
            background: rgba(0, 0, 0, 0.2);
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom p {
            margin: 0;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 0.5px;
        }

        /* Scroll to Top Button */
        #myBtn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 30px;
            z-index: 99;
            border: none;
            outline: none;
            background-color: #1cabe2;
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            transition: all 0.3s;
        }

        #myBtn:hover {
            background-color: #147ba5;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .profile-card.featured {
                grid-column: span 2;
                grid-row: span 1;
            }

            .featured .profile-media {
                height: 300px;
            }

            .section-header h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .hero {
                min-height: auto;
                flex-direction: column;
            }

            .hero-content {
                max-width: 100%;
                padding: 60px 20px;
                text-align: center;
                background: rgba(255, 255, 255, 0.95);
            }

            .hero-content::before {
                display: none;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.15rem;
            }

            .hero-image {
                position: relative;
                width: 100%;
                height: 400px;
                order: -1;
            }

            .hero-image::before {
                display: none;
            }

            .hero-image img {
                clip-path: none;
                border-radius: 0;
            }

            .hero-actions {
                justify-content: center;
                flex-direction: column;
            }

            .cta-button {
                width: 100%;
                max-width: 400px;
            }

            .profiles-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .profile-card.featured {
                grid-column: span 1;
                grid-row: span 1;
            }

            .featured .profile-media {
                height: 250px;
            }

            .featured .profile-name {
                font-size: 1.5rem;
            }

            .featured .profile-title {
                font-size: 1.1rem;
            }

            .featured .profile-content {
                padding: 20px;
            }

            .section-header h2 {
                font-size: 1.8rem;
            }

            .cta-button {
                width: 100%;
                text-align: center;
            }

            .cta-section {
                padding: 40px 20px;
            }

            .cta-content h2 {
                font-size: 1.8rem;
            }

            .footer-container {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 60px 30px 30px;
                text-align: center;
            }

            .footer-section h3::after, .about-us h3::after, .social-links h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .footer-section a::before, .about-us a::before {
                display: none;
            }

            .footer-section a, .about-us a {
                justify-content: center;
                padding-left: 0;
            }

            .footer-section a:hover, .about-us a:hover {
                padding-left: 0;
                transform: translateX(0) translateY(-2px);
            }

            .social-links a {
                justify-content: center;
            }

            .footer-bottom {
                padding: 25px 30px;
            }
        }

        @media (max-width: 480px) {
            .hero-content {
                padding: 40px 15px;
            }

            .hero-content h1 {
                font-size: 2rem;
                margin-bottom: 16px;
            }

            .hero-content p {
                font-size: 1.05rem;
                margin-bottom: 30px;
            }

            .hero-image {
                height: 300px;
            }

            .cta-button {
                padding: 16px 32px;
                font-size: 1rem;
            }

            .profiles-section {
                padding: 40px 15px;
            }

            .section-header {
                margin-bottom: 30px;
            }

            .section-header h2 {
                font-size: 1.5rem;
            }

            .section-header p {
                font-size: 1rem;
            }

            .profile-media {
                height: 200px;
            }

            .profile-name {
                font-size: 1.1rem;
            }

            .featured-badge {
                font-size: 0.75rem;
                padding: 6px 12px;
            }

            .btn-load-more {
                padding: 12px 30px;
                font-size: 1rem;
            }

            .footer-container {
                padding: 50px 20px 20px;
                gap: 35px;
            }

            .footer-section h3, .about-us h3, .social-links h3 {
                font-size: 1.2rem;
            }

            .footer-section a, .about-us a, .social-links a {
                font-size: 0.95rem;
            }

            .footer-bottom {
                padding: 20px;
            }

            .footer-bottom p {
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 data-translate="empoweringYoungPeople">Empowering Young People</h1>
            <p data-translate="homeHeroText">Share your story. Get support. Inspire others. Join our community of youth making a difference.</p>
            <div class="hero-actions">
                <a href="signup.php" class="cta-button primary">
                    <span data-translate="shareYourStory">Share Your Story</span>
                </a>
                <a href="#stories" class="cta-button secondary">
                    <span data-translate="viewStories">View Stories</span>
                </a>
            </div>
        </div>
        <div class="hero-image">
            <img src="../assets/images/Designer.jpeg" alt="Bihak Center Activities">
        </div>
    </section>

    <!-- Stories Section -->
    <section id="stories" class="profiles-section">
        <div class="section-header">
            <h2 data-translate="youthChangingWorld">Youth Changing the World</h2>
            <p data-translate="youthChangingWorldSubtitle">Meet the young people we support and the incredible things they're achieving</p>
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
                    <li><a href="work.php#orientation">Academic & Professional Orientation</a></li>
                    <li><a href="work.php#coaching">Project Development Coaching</a></li>
                    <li><a href="work.php#financial">Financial Support</a></li>
                    <li><a href="work.php#technology">Technology for Development</a></li>
                </ul>
            </div>

            <div class="about-us">
                <h3>About Us</h3>
                <ul>
                    <li><a href="about.php#vision">Our Vision</a></li>
                    <li><a href="about.php#mission">Our Mission</a></li>
                    <li><a href="about.php#motivation">Our Motivation</a></li>
                </ul>
            </div>

            <div class="social-links">
                <h3>Follow Us</h3>
                <ul>
                    <li>
                        <a href="https://facebook.com/bihakcenter" target="_blank" rel="noopener noreferrer">
                            <img src="/bihak-center/assets/images/facebook-icon.png" alt="Facebook">
                            <span>Bihak Center</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://instagram.com/bihakcenter" target="_blank" rel="noopener noreferrer">
                            <img src="/bihak-center/assets/images/instagram-icon.png" alt="Instagram">
                            <span>Bihak Center</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://x.com/bihak_center" target="_blank" rel="noopener noreferrer">
                            <img src="/bihak-center/assets/images/x-logo.png" alt="X (Twitter)">
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
    <script src="../assets/js/header_new.js"></script>
    <script src="../assets/js/scroll-to-top.js"></script>
    <script src="../assets/js/profiles-loader.js"></script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
