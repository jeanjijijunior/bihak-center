<?php
/**
 * Bihak Center - Profile Detail Page
 * Displays full profile information
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Get profile ID
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($profileId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch profile
$profile = null;
$canOfferMentorship = false;
$canRequestMentorship = false;
$existingRelationship = null;

try {
    $conn = getDatabaseConnection();

    $query = "SELECT * FROM profiles WHERE id = ? AND status = 'approved' AND is_published = TRUE";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $profileId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();

        // Increment view count
        $updateQuery = "UPDATE profiles SET view_count = view_count + 1 WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('i', $profileId);
        $updateStmt->execute();
        $updateStmt->close();

        // Check mentorship options
        if ($profile['user_id']) {
            // If viewer is a mentor (sponsor), show "Offer Mentorship" button
            if (isset($_SESSION['sponsor_id']) && $_SESSION['sponsor_id'] != $profile['user_id']) {
                // Check if there's already a relationship
                $relStmt = $conn->prepare("
                    SELECT * FROM mentorship_relationships
                    WHERE mentor_id = ? AND mentee_id = ?
                ");
                $relStmt->bind_param('ii', $_SESSION['sponsor_id'], $profile['user_id']);
                $relStmt->execute();
                $relResult = $relStmt->get_result();

                if ($relResult->num_rows > 0) {
                    $existingRelationship = $relResult->fetch_assoc();
                } else {
                    $canOfferMentorship = true;
                }
                $relStmt->close();
            }
        }
    }

    $stmt->close();
    closeDatabaseConnection($conn);
} catch (Exception $e) {
    error_log('Profile Error: ' . $e->getMessage());
}

if (!$profile) {
    header('Location: index.php');
    exit;
}

// Calculate age
$dob = new DateTime($profile['date_of_birth']);
$now = new DateTime();
$age = $now->diff($dob)->y;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($profile['short_description']); ?>">
    <title><?php echo htmlspecialchars($profile['full_name']); ?> - Bihak Center</title>

    <link rel="icon" type="image/png" href="../assets/images/favimg.png">
    <link rel="stylesheet" type="text/css" href="../assets/css/header_new.css">
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

        /* Profile Hero */
        .profile-hero {
            position: relative;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            padding: 60px 20px 40px;
            color: white;
            overflow: hidden;
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background: url('../assets/images/Designer.jpeg') center/cover;
        }

        .hero-content-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        .profile-image-container {
            flex-shrink: 0;
        }

        .profile-image-large {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .profile-hero-info {
            flex: 1;
        }

        .profile-title-large {
            font-size: 2.5rem;
            margin: 0 0 15px 0;
            font-weight: 700;
        }

        .profile-subtitle {
            font-size: 1.3rem;
            margin: 0 0 20px 0;
            opacity: 0.95;
            font-weight: 400;
        }

        .profile-quick-info {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 1rem;
            opacity: 0.9;
        }

        .profile-quick-info span {
            display: inline-block;
        }

        /* Main Content */
        .profile-main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 40px;
        }

        /* Content Sections */
        .profile-content-section {
            background: white;
        }

        .story-section,
        .goals-section,
        .achievements-section,
        .media-section {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #1cabe2;
        }

        .story-section h2,
        .goals-section h2,
        .achievements-section h2,
        .media-section h2 {
            color: #1cabe2;
            font-size: 1.8rem;
            margin: 0 0 20px 0;
        }

        .story-content,
        .goals-content,
        .achievements-content {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #333;
        }

        .profile-video,
        .profile-additional-image {
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Sidebar */
        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .info-card,
        .social-card,
        .support-card,
        .stats-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .info-card h3,
        .social-card h3,
        .support-card h3 {
            color: #1cabe2;
            font-size: 1.3rem;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item strong {
            color: #666;
            font-weight: 600;
        }

        .info-item span {
            color: #333;
            text-align: right;
        }

        /* Social Links Sidebar */
        .social-links-sidebar {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .social-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .social-link.facebook {
            background: #1877f2;
            color: white;
        }

        .social-link.twitter {
            background: #1da1f2;
            color: white;
        }

        .social-link.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: white;
        }

        .social-link.linkedin {
            background: #0077b5;
            color: white;
        }

        .social-link:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Support Card */
        .support-card {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            text-align: center;
        }

        .support-card h3 {
            color: white;
            border-bottom-color: rgba(255, 255, 255, 0.3);
        }

        .support-card p {
            margin: 15px 0 20px;
            opacity: 0.95;
        }

        .btn-support {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: #1cabe2;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-support:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Contact Details */
        .contact-details {
            margin-top: 20px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .contact-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 20px 0;
        }

        .contact-info {
            text-align: left;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: background 0.3s;
        }

        .contact-item:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .contact-item:last-child {
            margin-bottom: 0;
        }

        .contact-item svg {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .contact-item div {
            flex: 1;
        }

        .contact-item strong {
            display: block;
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 4px;
        }

        .contact-item a {
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            word-break: break-all;
            transition: opacity 0.3s;
        }

        .contact-item a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        /* Stats Card */
        .stats-card {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .stat-item strong {
            font-size: 1.8rem;
            color: #1cabe2;
            font-weight: 700;
        }

        .stat-item span {
            font-size: 0.9rem;
            color: #666;
        }

        /* Back Link */
        .back-link-container {
            text-align: center;
            margin: 40px 0;
        }

        .back-link {
            display: inline-block;
            padding: 12px 30px;
            background: #f0f0f0;
            color: #1cabe2;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: #1cabe2;
            color: white;
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
            .profile-container {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .hero-content-wrapper {
                flex-direction: column;
                text-align: center;
                gap: 25px;
            }

            .profile-image-large {
                width: 150px;
                height: 150px;
            }

            .profile-title-large {
                font-size: 2rem;
            }

            .profile-subtitle {
                font-size: 1.1rem;
            }

            .profile-quick-info {
                justify-content: center;
            }

            .story-section,
            .goals-section,
            .achievements-section,
            .media-section {
                padding: 20px;
            }

            .info-card,
            .social-card,
            .support-card,
            .stats-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .profile-hero {
                padding: 40px 15px 30px;
            }

            .profile-image-large {
                width: 120px;
                height: 120px;
            }

            .profile-title-large {
                font-size: 1.6rem;
            }

            .profile-subtitle {
                font-size: 1rem;
            }

            .profile-quick-info {
                font-size: 0.9rem;
            }

            .story-section h2,
            .goals-section h2,
            .achievements-section h2,
            .media-section h2 {
                font-size: 1.4rem;
            }

            .story-content,
            .goals-content,
            .achievements-content {
                font-size: 0.95rem;
            }

            .stats-card {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header_new.php'; ?>

    <!-- Profile Hero -->
    <section class="profile-hero">
        <div class="hero-background"></div>
        <div class="hero-content-wrapper">
            <div class="profile-image-container">
                <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="<?php echo htmlspecialchars($profile['full_name']); ?>" class="profile-image-large">
            </div>
            <div class="profile-hero-info">
                <h1 class="profile-title-large"><?php echo htmlspecialchars($profile['full_name']); ?></h1>
                <p class="profile-subtitle"><?php echo htmlspecialchars($profile['title']); ?></p>
                <div class="profile-quick-info">
                    <span><?php echo $age; ?> years old</span>
                    <span>•</span>
                    <span><?php echo htmlspecialchars($profile['city'] . ', ' . $profile['district']); ?></span>
                    <?php if (!empty($profile['field_of_study'])): ?>
                        <span>•</span>
                        <span><?php echo htmlspecialchars($profile['field_of_study']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Content -->
    <main class="profile-main">
        <div class="profile-container">
            <!-- Main Story -->
            <div class="profile-content-section">
                <div class="story-section">
                    <h2>My Story</h2>
                    <div class="story-content">
                        <?php echo nl2br(htmlspecialchars($profile['full_story'])); ?>
                    </div>
                </div>

                <?php if (!empty($profile['goals'])): ?>
                    <div class="goals-section">
                        <h2>My Goals</h2>
                        <div class="goals-content">
                            <?php echo nl2br(htmlspecialchars($profile['goals'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profile['achievements'])): ?>
                    <div class="achievements-section">
                        <h2>My Achievements</h2>
                        <div class="achievements-content">
                            <?php echo nl2br(htmlspecialchars($profile['achievements'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profile['media_url'])): ?>
                    <div class="media-section">
                        <h2>Media</h2>
                        <?php if ($profile['media_type'] === 'video'): ?>
                            <video src="<?php echo htmlspecialchars($profile['media_url']); ?>" controls class="profile-video"></video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($profile['media_url']); ?>" alt="Additional media" class="profile-additional-image">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <!-- Info Card -->
                <div class="info-card">
                    <h3>About</h3>
                    <div class="info-item">
                        <strong>Location:</strong>
                        <span><?php echo htmlspecialchars($profile['city'] . ', ' . $profile['district']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Age:</strong>
                        <span><?php echo $age; ?> years</span>
                    </div>
                    <?php if (!empty($profile['education_level'])): ?>
                        <div class="info-item">
                            <strong>Education:</strong>
                            <span><?php echo htmlspecialchars($profile['education_level']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['current_institution'])): ?>
                        <div class="info-item">
                            <strong>Institution:</strong>
                            <span><?php echo htmlspecialchars($profile['current_institution']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['field_of_study'])): ?>
                        <div class="info-item">
                            <strong>Field:</strong>
                            <span><?php echo htmlspecialchars($profile['field_of_study']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Social Media -->
                <?php if (!empty($profile['facebook_url']) || !empty($profile['twitter_url']) || !empty($profile['instagram_url']) || !empty($profile['linkedin_url'])): ?>
                    <div class="social-card">
                        <h3>Connect</h3>
                        <div class="social-links-sidebar">
                            <?php if (!empty($profile['facebook_url'])): ?>
                                <a href="<?php echo htmlspecialchars($profile['facebook_url']); ?>" target="_blank" rel="noopener noreferrer" class="social-link facebook">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    Facebook
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($profile['twitter_url'])): ?>
                                <a href="<?php echo htmlspecialchars($profile['twitter_url']); ?>" target="_blank" rel="noopener noreferrer" class="social-link twitter">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                    </svg>
                                    Twitter
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($profile['instagram_url'])): ?>
                                <a href="<?php echo htmlspecialchars($profile['instagram_url']); ?>" target="_blank" rel="noopener noreferrer" class="social-link instagram">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                    </svg>
                                    Instagram
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($profile['linkedin_url'])): ?>
                                <a href="<?php echo htmlspecialchars($profile['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer" class="social-link linkedin">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                    LinkedIn
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Mentorship CTA (for mentors viewing user profiles) -->
                <?php if ($canOfferMentorship): ?>
                <div class="support-card" style="background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);">
                    <h3 style="color: white; border-bottom-color: rgba(255, 255, 255, 0.3);">🤝 Mentorship Opportunity</h3>
                    <p>Offer mentorship to <?php echo htmlspecialchars(explode(' ', $profile['full_name'])[0]); ?> and help guide their journey</p>
                    <button onclick="offerMentorship(<?php echo $profile['user_id']; ?>, '<?php echo htmlspecialchars($profile['full_name']); ?>')" class="btn-support">
                        Offer Mentorship
                    </button>
                </div>
                <?php elseif ($existingRelationship): ?>
                <div class="support-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <h3 style="color: white; border-bottom-color: rgba(255, 255, 255, 0.3);">✓ Mentorship Status</h3>
                    <p>
                        <?php if ($existingRelationship['status'] === 'active'): ?>
                            You are mentoring <?php echo htmlspecialchars(explode(' ', $profile['full_name'])[0]); ?>
                        <?php elseif ($existingRelationship['status'] === 'pending'): ?>
                            Mentorship request pending
                        <?php else: ?>
                            Previous mentorship relationship
                        <?php endif; ?>
                    </p>
                    <?php if ($existingRelationship['status'] === 'active'): ?>
                    <a href="mentorship/workspace.php?id=<?php echo $existingRelationship['id']; ?>" class="btn-support">
                        Open Workspace
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Support CTA -->
                <div class="support-card">
                    <h3>Support This Story</h3>
                    <p>Want to help <?php echo htmlspecialchars(explode(' ', $profile['full_name'])[0]); ?> achieve their dreams?</p>
                    <button onclick="toggleContactDetails()" class="btn-support" id="contactToggleBtn">Get in Touch</button>

                    <!-- Contact Details (Initially Hidden) -->
                    <div id="contactDetails" class="contact-details" style="display: none;">
                        <div class="contact-divider"></div>
                        <div class="contact-info">
                            <?php if (!empty($profile['email'])): ?>
                            <div class="contact-item">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                <div>
                                    <strong>Email:</strong>
                                    <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>"><?php echo htmlspecialchars($profile['email']); ?></a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($profile['phone'])): ?>
                            <div class="contact-item">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                <div>
                                    <strong>Phone:</strong>
                                    <a href="tel:<?php echo htmlspecialchars($profile['phone']); ?>"><?php echo htmlspecialchars($profile['phone']); ?></a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-card">
                    <div class="stat-item">
                        <strong><?php echo number_format($profile['view_count']); ?></strong>
                        <span>Views</span>
                    </div>
                    <div class="stat-item">
                        <strong><?php echo date('M d, Y', strtotime($profile['created_at'])); ?></strong>
                        <span>Joined</span>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Back to Stories -->
        <div class="back-link-container">
            <a href="stories.php" class="back-link">← Back to All Stories</a>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer_new.php'; ?>

    <!-- Scroll to Top -->
    <button id="myBtn" aria-label="Scroll to top">↑</button>

    <script src="../assets/js/scroll-to-top.js"></script>

    <script>
        // Toggle contact details visibility
        function toggleContactDetails() {
            const contactDetails = document.getElementById('contactDetails');
            const toggleBtn = document.getElementById('contactToggleBtn');

            if (contactDetails.style.display === 'none') {
                contactDetails.style.display = 'block';
                toggleBtn.textContent = 'Hide Contact Info';
            } else {
                contactDetails.style.display = 'none';
                toggleBtn.textContent = 'Get in Touch';
            }
        }

        // Offer mentorship to user
        function offerMentorship(menteeId, menteeName) {
            if (!confirm(`Offer mentorship to ${menteeName}?\n\nThey will receive a notification and can accept or decline your offer.`)) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Sending...';

            fetch('../api/mentorship/request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mentee_id: menteeId,
                    requested_by: 'mentor'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ Mentorship offer sent successfully!\n\nThe user will be notified and you\'ll receive an update when they respond.');
                    btn.textContent = '✓ Offer Sent';
                    btn.style.background = '#10b981';
                    location.reload(); // Reload to show updated status
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.textContent = 'Offer Mentorship';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Offer Mentorship';
            });
        }
    </script>

    <!-- Chat Widget is now included automatically via footer_new.php -->
</body>
</html>
