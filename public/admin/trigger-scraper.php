<?php
/**
 * Trigger Opportunity Scraper
 * AJAX endpoint to manually trigger opportunity scrapers
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/user_auth.php';

// Require authentication (admin OR regular user)
Auth::init();
$is_admin = Auth::check();
$is_user = isset($_SESSION['user_id']);

if (!$is_admin && !$is_user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login to refresh opportunities.']);
    exit;
}

// Set long execution time
set_time_limit(600); // 10 minutes

// Get scraper type from request
$scraper_type = isset($_POST['type']) ? $_POST['type'] : 'all';

// Load dependencies
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../scrapers/ScholarshipScraper.php';
require_once __DIR__ . '/../../scrapers/JobScraper.php';
require_once __DIR__ . '/../../scrapers/InternshipScraper.php';
require_once __DIR__ . '/../../scrapers/GrantScraper.php';

$conn = getDatabaseConnection();
$results = [];
$start_time = time();

try {
    // Run Scholarship Scraper
    if ($scraper_type === 'all' || $scraper_type === 'scholarship') {
        $scholarshipScraper = new ScholarshipScraper($conn);
        $result = $scholarshipScraper->run();
        $results['scholarship'] = $result;
    }

    // Run Job Scraper
    if ($scraper_type === 'all' || $scraper_type === 'job') {
        $jobScraper = new JobScraper($conn);
        $result = $jobScraper->run();
        $results['job'] = $result;
    }

    // Run Internship Scraper
    if ($scraper_type === 'all' || $scraper_type === 'internship') {
        $internshipScraper = new InternshipScraper($conn);
        $result = $internshipScraper->run();
        $results['internship'] = $result;
    }

    // Run Grant Scraper
    if ($scraper_type === 'all' || $scraper_type === 'grant') {
        $grantScraper = new GrantScraper($conn);
        $result = $grantScraper->run();
        $results['grant'] = $result;
    }

    $execution_time = time() - $start_time;

    // Calculate totals
    $total_scraped = 0;
    $total_added = 0;
    $total_updated = 0;

    foreach ($results as $type => $result) {
        if ($result['success']) {
            $total_scraped += $result['items_scraped'] ?? 0;
            $total_added += $result['items_added'] ?? 0;
            $total_updated += $result['items_updated'] ?? 0;
        }
    }

    closeDatabaseConnection($conn);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Scraper completed successfully',
        'data' => [
            'execution_time' => $execution_time,
            'total_scraped' => $total_scraped,
            'total_added' => $total_added,
            'total_updated' => $total_updated,
            'results' => $results
        ]
    ]);

} catch (Exception $e) {
    closeDatabaseConnection($conn);

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Scraper failed: ' . $e->getMessage()
    ]);
}
?>
