<?php
/**
 * Stories Page - Showcasing All Youth Profiles
 * Displays all approved and published profiles from the database
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Fetch all published profiles
$profiles = [];
try {
    $conn = getDatabaseConnection();

    $query = "SELECT
        id, full_name, title, short_description, profile_image,
        media_type, media_url, city, district, field_of_study,
        created_at, view_count
    FROM profiles
    WHERE status = 'approved' AND is_published = TRUE
    ORDER BY created_at DESC";

    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $profiles[] = $row;
        }
    }

    closeDatabaseConnection($conn);
} catch (Exception $e) {
    error_log('Stories Page Error: ' . $e->getMessage());
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success Stories - Bihak Center | Youth Making a Difference</title>
    <meta name="description" content="Read inspiring stories from young people supported by Bihak Center. Learn about their achievements, dreams, and journeys.">
    <link rel="icon" type="image/png" href="../assets/images/logob.png">
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: #f7fafc;
        }

        /* Hero Section */
        .stories-hero {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 80px 20px 60px;
            text-align: center;
        }

        .stories-hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .stories-hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.95;
        }

        /* Stats Bar */
        .stats-bar {
            background: white;
            padding: 30px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            gap: 60px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 2.5rem;
            font-weight: 700;
            color: #1cabe2;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            display: block;
            font-size: 0.95rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Stories Grid Section */
        .stories-section {
            max-width: 1400px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 12px;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #718096;
        }

        /* Profiles Grid */
        .profiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .profile-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .profile-media {
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .profile-media img,
        .profile-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-content {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .profile-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .profile-title {
            font-size: 1rem;
            color: #1cabe2;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .profile-description {
            font-size: 0.95rem;
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 16px;
            flex: 1;
        }

        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 16px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }

        .profile-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .profile-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #1cabe2;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .profile-link:hover {
            color: #147ba5;
            gap: 10px;
        }

        /* Empty State */
        .no-stories {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .no-stories h3 {
            font-size: 1.8rem;
            color: #2d3748;
            margin-bottom: 12px;
        }

        .no-stories p {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 24px;
        }

        .btn-cta {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(28, 171, 226, 0.3);
        }

        /* Footer styles now handled by footer_new.php */

        /* Responsive Design */
        @media (max-width: 768px) {
            .stories-hero h1 {
                font-size: 2rem;
            }

            .stories-hero p {
                font-size: 1rem;
            }

            .profiles-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .stats-container {
                gap: 30px;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        /* Scroll to Top */
        #myBtn {
            display: none;
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            border: none;
            outline: none;
            background-color: #1cabe2;
            color: white;
            cursor: pointer;
            padding: 15px 18px;
            border-radius: 50%;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(28, 171, 226, 0.3);
            transition: all 0.3s ease;
        }

        #myBtn:hover {
            background-color: #147ba5;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(28, 171, 226, 0.4);
        }
    </style>
</head>
<body>
    <?php include '../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="stories-hero">
        <h1 data-translate="successStories">Success Stories</h1>
        <p data-translate="storiesSubtitle">Meet the inspiring young people we support and the incredible journeys they're on</p>
    </section>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stats-container">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($profiles); ?></span>
                <span class="stat-label" data-translate="totalStories">Total Stories</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo array_sum(array_column($profiles, 'view_count')); ?></span>
                <span class="stat-label" data-translate="totalViews">Total Views</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count(array_unique(array_column($profiles, 'district'))); ?></span>
                <span class="stat-label" data-translate="districts">Districts</span>
            </div>
        </div>
    </div>

    <!-- Stories Section -->
    <section class="stories-section">
        <div class="section-header">
            <h2 data-translate="allStories">All Stories</h2>
            <p data-translate="inspireByStories">Be inspired by the stories of young people making a difference</p>
        </div>

        <?php if (count($profiles) > 0): ?>
            <div class="profiles-grid">
                <?php foreach ($profiles as $profile): ?>
                    <div class="profile-card">
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
                                <?php
                                $description = $profile['short_description'];
                                echo htmlspecialchars(strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description);
                                ?>
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
                                            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917l-7.5-3.5Z"/>
                                        </svg>
                                        <?php echo htmlspecialchars($profile['field_of_study']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a href="profile.php?id=<?php echo $profile['id']; ?>" class="profile-link">
                                <span data-translate="readStory">Read Story</span> →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-stories">
                <h3 data-translate="noStoriesYet">No Stories Yet</h3>
                <p data-translate="beFirstToShare">Be the first to share your inspiring story!</p>
                <a href="signup.php" class="btn-cta" data-translate="shareStory">Share Your Story</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer_new.php'; ?>

    <!-- Scroll to Top Button -->
    <button id="myBtn" aria-label="Scroll to top">↑</button>

    <script>
        // Scroll to top functionality
        const scrollBtn = document.getElementById('myBtn');

        window.onscroll = function() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        };

        scrollBtn.onclick = function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
    </script>
</body>
</html>
