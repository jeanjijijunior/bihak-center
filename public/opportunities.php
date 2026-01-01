<?php
/**
 * Opportunities Page
 * Display scholarships, jobs, internships, and grants
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/security.php';

// Get filters from query parameters
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$country_filter = isset($_GET['country']) ? $_GET['country'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'deadline';

// Get current user (check both regular user and admin sessions)
$user = UserAuth::user();
$is_admin = isset($_SESSION['admin_id']);

// If no regular user but admin is logged in, create a user-like object for the page
if (!$user && $is_admin) {
    $user = [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'] ?? 'Admin',
        'is_admin' => true
    ];
}

// Build SQL query
$conn = getDatabaseConnection();

$sql = "SELECT o.*, DATEDIFF(o.deadline, CURDATE()) as days_remaining
        FROM opportunities o
        WHERE o.is_active = TRUE
        AND (o.deadline IS NULL OR o.deadline >= CURDATE())
        AND o.application_url IS NOT NULL
        AND o.application_url != ''
        AND o.application_url NOT LIKE '%example.com%'
        AND o.application_url NOT LIKE '%test%'
        AND o.application_url NOT LIKE '%localhost%'";

$params = [];
$types = '';

// Apply filters
if ($type_filter !== 'all') {
    $sql .= " AND o.type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if (!empty($search_query)) {
    $sql .= " AND (o.title LIKE ? OR o.description LIKE ? OR o.organization LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($country_filter)) {
    $sql .= " AND o.country = ?";
    $params[] = $country_filter;
    $types .= 's';
}

// Apply sorting
switch ($sort_by) {
    case 'deadline':
        $sql .= " ORDER BY o.deadline ASC, o.created_at DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY o.created_at DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY o.views_count DESC, o.applications_count DESC";
        break;
    default:
        $sql .= " ORDER BY o.deadline ASC";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$opportunities = $result->fetch_all(MYSQLI_ASSOC);

// Get available countries for filter
$countries_sql = "SELECT DISTINCT country FROM opportunities WHERE is_active = TRUE AND country IS NOT NULL ORDER BY country";
$countries_result = $conn->query($countries_sql);
$countries = $countries_result->fetch_all(MYSQLI_ASSOC);

// Get user's saved opportunities if logged in (only for regular users, not admins)
$saved_opportunity_ids = [];
if ($user && !isset($user['is_admin'])) {
    $saved_sql = "SELECT opportunity_id FROM user_saved_opportunities WHERE user_id = ?";
    $saved_stmt = $conn->prepare($saved_sql);
    $saved_stmt->bind_param('i', $user['id']);
    $saved_stmt->execute();
    $saved_result = $saved_stmt->get_result();
    while ($row = $saved_result->fetch_assoc()) {
        $saved_opportunity_ids[] = $row['opportunity_id'];
    }
}

closeDatabaseConnection($conn);

// Generate CSRF token for save actions
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opportunities - Bihak Center</title>
    <link rel="icon" type="image/png" href="../assets/images/logob.png">
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: #f7fafc;
        }

        /* Hero Section */
        .opportunities-hero {
            background: linear-gradient(135deg, #1cabe2 0%, #0e7fa5 100%);
            color: white;
            padding: 80px 20px 60px;
            text-align: center;
        }

        .opportunities-hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: fadeInDown 0.8s ease;
        }

        .opportunities-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 40px;
            opacity: 0.95;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        /* Search and Filters */
        .filters-section {
            background: white;
            padding: 30px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }

        .filters-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #1cabe2;
            box-shadow: 0 0 0 3px rgba(28, 171, 226, 0.1);
        }

        .search-btn {
            padding: 12px 30px;
            background: #1cabe2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: #0e7fa5;
            transform: translateY(-2px);
        }

        .filter-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            color: #4a5568;
        }

        .filter-tab:hover {
            border-color: #1cabe2;
            color: #1cabe2;
        }

        .filter-tab.active {
            background: #1cabe2;
            border-color: #1cabe2;
            color: white;
        }

        .filter-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-select {
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            background: white;
        }

        .filter-select:focus {
            outline: none;
            border-color: #1cabe2;
        }

        /* Opportunities Grid */
        .opportunities-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }

        .opportunities-count {
            margin-bottom: 30px;
            font-size: 1.1rem;
            color: #4a5568;
        }

        .opportunities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .opportunity-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            border: 2px solid transparent;
        }

        .opportunity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-color: #1cabe2;
        }

        .opportunity-type {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .type-scholarship {
            background: #e6f7ff;
            color: #0e7fa5;
        }

        .type-job {
            background: #f0fdf4;
            color: #15803d;
        }

        .type-internship {
            background: #fef3c7;
            color: #d97706;
        }

        .type-grant {
            background: #fce7f3;
            color: #be185d;
        }

        .type-competition {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .opportunity-card h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: #1a202c;
        }

        .opportunity-organization {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .opportunity-description {
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .opportunity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .meta-item svg {
            width: 16px;
            height: 16px;
            color: #1cabe2;
        }

        .opportunity-deadline {
            background: #fff5f5;
            color: #c53030;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 16px;
            display: inline-block;
        }

        .opportunity-deadline.urgent {
            background: #fed7d7;
            animation: pulse 2s infinite;
        }

        .opportunity-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1cabe2;
            margin-bottom: 16px;
        }

        .opportunity-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: #1cabe2;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #0e7fa5;
            transform: translateY(-2px);
        }

        .btn-save {
            background: white;
            color: #4a5568;
            border: 2px solid #e2e8f0;
            padding: 10px 16px;
        }

        .btn-save:hover {
            border-color: #1cabe2;
            color: #1cabe2;
        }

        .btn-save.saved {
            background: #1cabe2;
            color: white;
            border-color: #1cabe2;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state svg {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4a5568;
        }

        .empty-state p {
            color: #718096;
            font-size: 1.1rem;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .opportunities-hero h1 {
                font-size: 2rem;
            }

            .opportunities-hero p {
                font-size: 1rem;
            }

            .opportunities-grid {
                grid-template-columns: 1fr;
            }

            .filter-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 10px;
            }

            .filter-tab {
                white-space: nowrap;
            }
        }

        /* Refresh Button */
        .refresh-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .refresh-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .refresh-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .refresh-btn svg {
            transition: transform 0.6s;
        }

        .refresh-btn.loading svg {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        #scraperStatus.success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }

        #scraperStatus.error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }

        #scraperStatus.info {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            color: #1e40af;
        }

        /* Fix header dropdown z-index and positioning */
        .header-new {
            position: relative !important;
            z-index: 1000 !important;
        }

        .user-menu {
            position: relative !important;
        }

        .dropdown-menu {
            position: absolute !important;
            z-index: 1001 !important;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="opportunities-hero">
        <h1 id="hero-title" data-translate="discoverOpportunity">Discover Your Next Opportunity</h1>
        <p id="hero-description" data-translate="findScholarshipsJobs">
            Find scholarships, jobs, internships, grants, and competitions curated specifically for young people.
            Your future starts here.
        </p>

        <?php if ($user): ?>
        <!-- Refresh Button (Available for all logged-in users) -->
        <div style="margin-top: 20px;">
            <button id="refreshOpportunitiesBtn" class="refresh-btn">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 8px;">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                </svg>
                <span id="btnText" data-translate="refreshOpportunities">Refresh Opportunities</span>
            </button>
            <div id="scraperStatus" style="display: none; margin-top: 10px; padding: 10px; border-radius: 8px;"></div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
        <div class="filters-container">
            <!-- Search Bar -->
            <form action="" method="GET" class="search-bar">
                <input
                    type="text"
                    name="search"
                    class="search-input"
                    data-translate="searchOpportunities"
                    placeholder="Search opportunities..."
                    value="<?php echo htmlspecialchars($search_query); ?>"
                    id="search-input"
                >
                <button type="submit" class="search-btn" id="search-btn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate="search">Search</span>
                </button>
            </form>

            <!-- Type Filter Tabs -->
            <div class="filter-tabs">
                <a href="?type=all&search=<?php echo urlencode($search_query); ?>&country=<?php echo urlencode($country_filter); ?>&sort=<?php echo urlencode($sort_by); ?>"
                   class="filter-tab <?php echo $type_filter === 'all' ? 'active' : ''; ?>"
                   data-type="all">
                    <span id="filter-all">All Opportunities</span>
                </a>
                <a href="?type=scholarship&search=<?php echo urlencode($search_query); ?>&country=<?php echo urlencode($country_filter); ?>&sort=<?php echo urlencode($sort_by); ?>"
                   class="filter-tab <?php echo $type_filter === 'scholarship' ? 'active' : ''; ?>"
                   data-type="scholarship">
                    <span id="filter-scholarship">Scholarships</span>
                </a>
                <a href="?type=job&search=<?php echo urlencode($search_query); ?>&country=<?php echo urlencode($country_filter); ?>&sort=<?php echo urlencode($sort_by); ?>"
                   class="filter-tab <?php echo $type_filter === 'job' ? 'active' : ''; ?>"
                   data-type="job">
                    <span id="filter-job">Jobs</span>
                </a>
                <a href="?type=internship&search=<?php echo urlencode($search_query); ?>&country=<?php echo urlencode($country_filter); ?>&sort=<?php echo urlencode($sort_by); ?>"
                   class="filter-tab <?php echo $type_filter === 'internship' ? 'active' : ''; ?>"
                   data-type="internship">
                    <span id="filter-internship">Internships</span>
                </a>
                <a href="?type=grant&search=<?php echo urlencode($search_query); ?>&country=<?php echo urlencode($country_filter); ?>&sort=<?php echo urlencode($sort_by); ?>"
                   class="filter-tab <?php echo $type_filter === 'grant' ? 'active' : ''; ?>"
                   data-type="grant">
                    <span id="filter-grant">Grants</span>
                </a>
                <a href="?type=competition&search=<?php echo urlencode($search_query); ?>&country=<?php echo urlencode($country_filter); ?>&sort=<?php echo urlencode($sort_by); ?>"
                   class="filter-tab <?php echo $type_filter === 'competition' ? 'active' : ''; ?>"
                   data-type="competition">
                    <span id="filter-competition">Competitions</span>
                </a>
            </div>

            <!-- Country and Sort Filters -->
            <form action="" method="GET">
                <div class="filter-row">
                    <select name="country" class="filter-select" onchange="this.form.submit()">
                        <option value="" id="country-all">All Countries</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country['country']); ?>"
                                    <?php echo $country_filter === $country['country'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($country['country']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="deadline" <?php echo $sort_by === 'deadline' ? 'selected' : ''; ?> id="sort-deadline">Sort by Deadline</option>
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?> id="sort-newest">Newest First</option>
                        <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?> id="sort-popular">Most Popular</option>
                    </select>

                    <!-- Hidden fields to preserve other filters -->
                    <?php if ($type_filter !== 'all'): ?>
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($search_query)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <!-- Opportunities Grid -->
    <section class="opportunities-container">
        <div class="opportunities-count">
            <strong><?php echo count($opportunities); ?></strong> <span id="opportunities-found">opportunities found</span>
        </div>

        <?php if (count($opportunities) > 0): ?>
            <div class="opportunities-grid">
                <?php foreach ($opportunities as $opp): ?>
                    <div class="opportunity-card" data-id="<?php echo $opp['id']; ?>">
                        <span class="opportunity-type type-<?php echo $opp['type']; ?>">
                            <?php echo ucfirst($opp['type']); ?>
                        </span>

                        <h3><?php echo htmlspecialchars($opp['title']); ?></h3>

                        <div class="opportunity-organization">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo htmlspecialchars($opp['organization']); ?>
                        </div>

                        <p class="opportunity-description">
                            <?php echo htmlspecialchars($opp['description']); ?>
                        </p>

                        <div class="opportunity-meta">
                            <?php if ($opp['location']): ?>
                                <div class="meta-item">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo htmlspecialchars($opp['location']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($opp['country']): ?>
                                <div class="meta-item">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo htmlspecialchars($opp['country']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($opp['deadline']): ?>
                            <div class="opportunity-deadline <?php echo $opp['days_remaining'] <= 7 ? 'urgent' : ''; ?>">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <?php
                                    if ($opp['days_remaining'] == 0) {
                                        echo 'Deadline Today!';
                                    } elseif ($opp['days_remaining'] == 1) {
                                        echo 'Deadline Tomorrow';
                                    } else {
                                        echo $opp['days_remaining'] . ' days remaining';
                                    }
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($opp['amount']): ?>
                            <div class="opportunity-amount">
                                <?php echo htmlspecialchars($opp['amount']); ?>
                                <?php if ($opp['currency']): ?>
                                    <?php echo htmlspecialchars($opp['currency']); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="opportunity-actions">
                            <a href="<?php echo htmlspecialchars($opp['application_url']); ?>"
                               target="_blank"
                               class="btn btn-primary"
                               onclick="trackView(<?php echo $opp['id']; ?>)">
                                <span class="apply-btn-text">Apply Now</span>
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </a>

                            <?php if ($user): ?>
                                <button class="btn btn-save <?php echo in_array($opp['id'], $saved_opportunity_ids) ? 'saved' : ''; ?>"
                                        onclick="toggleSave(<?php echo $opp['id']; ?>, this)"
                                        title="<?php echo in_array($opp['id'], $saved_opportunity_ids) ? 'Saved' : 'Save for later'; ?>">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                </svg>
                <h3 id="no-results-title">No Opportunities Found</h3>
                <p id="no-results-text">Try adjusting your filters or search terms</p>
            </div>
        <?php endif; ?>
    </section>

    <?php include __DIR__ . '/../includes/footer_new.php'; ?>

    <!-- Note: header_new.js is already loaded in header_new.php include -->

    <script>
        // Track opportunity view
        function trackView(opportunityId) {
            fetch('../api/track_opportunity_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ opportunity_id: opportunityId })
            });
        }

        // Toggle save opportunity
        function toggleSave(opportunityId, button) {
            const isSaved = button.classList.contains('saved');

            fetch('../api/save_opportunity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    opportunity_id: opportunityId,
                    action: isSaved ? 'unsave' : 'save',
                    csrf_token: '<?php echo $csrf_token; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('saved');
                    button.title = isSaved ? 'Save for later' : 'Saved';
                } else {
                    alert(data.message || 'Failed to save opportunity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Translations
        const opportunitiesTranslations = {
            en: {
                'hero-title': 'Discover Your Next Opportunity',
                'hero-description': 'Find scholarships, jobs, internships, grants, and competitions curated specifically for young people. Your future starts here.',
                'search-input': 'Search opportunities...',
                'search-btn': 'Search',
                'filter-all': 'All Opportunities',
                'filter-scholarship': 'Scholarships',
                'filter-job': 'Jobs',
                'filter-internship': 'Internships',
                'filter-grant': 'Grants',
                'filter-competition': 'Competitions',
                'country-all': 'All Countries',
                'sort-deadline': 'Sort by Deadline',
                'sort-newest': 'Newest First',
                'sort-popular': 'Most Popular',
                'opportunities-found': 'opportunities found',
                'apply-btn-text': 'Apply Now',
                'no-results-title': 'No Opportunities Found',
                'no-results-text': 'Try adjusting your filters or search terms'
            },
            fr: {
                'hero-title': 'Découvrez Votre Prochaine Opportunité',
                'hero-description': 'Trouvez des bourses, emplois, stages, subventions et compétitions sélectionnés spécifiquement pour les jeunes. Votre avenir commence ici.',
                'search-input': 'Rechercher des opportunités...',
                'search-btn': 'Rechercher',
                'filter-all': 'Toutes les Opportunités',
                'filter-scholarship': 'Bourses',
                'filter-job': 'Emplois',
                'filter-internship': 'Stages',
                'filter-grant': 'Subventions',
                'filter-competition': 'Compétitions',
                'country-all': 'Tous les Pays',
                'sort-deadline': 'Trier par Date Limite',
                'sort-newest': 'Plus Récent',
                'sort-popular': 'Plus Populaire',
                'opportunities-found': 'opportunités trouvées',
                'apply-btn-text': 'Postuler',
                'no-results-title': 'Aucune Opportunité Trouvée',
                'no-results-text': 'Essayez d\'ajuster vos filtres ou termes de recherche'
            }
        };

        // Listen for language changes
        document.addEventListener('languageChanged', function(e) {
            const lang = e.detail.language;
            const translations = opportunitiesTranslations[lang];

            if (translations) {
                Object.keys(translations).forEach(key => {
                    const elements = document.querySelectorAll(`#${key}, .${key}`);
                    elements.forEach(element => {
                        if (element.tagName === 'INPUT' && element.type === 'text') {
                            element.placeholder = translations[key];
                        } else {
                            element.textContent = translations[key];
                        }
                    });
                });
            }
        });

        // Set initial language (use existing currentLang from translations-extended.js)
        const savedLang = localStorage.getItem('language') || 'en';
        if (savedLang !== 'en') {
            document.dispatchEvent(new CustomEvent('languageChanged', {
                detail: { language: savedLang }
            }));
        }

        // Scraper trigger function using event listener
        function triggerScraper() {
            console.log('🚀 triggerScraper() called');
            const btn = document.getElementById('refreshOpportunitiesBtn');
            const btnText = document.getElementById('btnText');
            const status = document.getElementById('scraperStatus');

            console.log('Button element:', btn);
            console.log('Button text element:', btnText);
            console.log('Status element:', status);

            // Disable button and show loading
            btn.disabled = true;
            btn.classList.add('loading');
            btnText.textContent = 'Searching...';
            console.log('✓ Button disabled, loading class added');

            // Show info message
            status.style.display = 'block';
            status.className = 'info';
            status.textContent = 'Searching for new opportunities... This may take a few minutes.';
            console.log('✓ Status message displayed');

            // Make AJAX request
            fetch('admin/trigger-scraper.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'type=all'
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Scraper response:', data); // Debug log

                if (data.success) {
                    status.className = 'success';
                    const added = data.data?.total_added || 0;
                    const updated = data.data?.total_updated || 0;
                    const time = data.data?.execution_time || 0;
                    status.textContent = `✓ Success! Added ${added} new opportunities, updated ${updated}. Execution time: ${time}s`;

                    // Reload page after 2 seconds to show new opportunities
                    setTimeout(() => location.reload(), 2000);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Scraper error:', error); // Debug log
                status.className = 'error';
                status.textContent = '✗ Error: ' + error.message + ' - Please make sure you are logged in and try again.';
                btn.disabled = false;
                btn.classList.remove('loading');
                btnText.textContent = 'Refresh Opportunities';
            });
        }

        // Attach event listener to the button
        const refreshBtn = document.getElementById('refreshOpportunitiesBtn');
        if (refreshBtn) {
            console.log('✓ Refresh button found, attaching event listener');
            refreshBtn.addEventListener('click', function() {
                console.log('🔄 Button clicked! Starting scraper...');
                triggerScraper();
            });
        } else {
            console.log('❌ Refresh button NOT found - are you logged in?');
        }
    </script>
</body>
</html>
