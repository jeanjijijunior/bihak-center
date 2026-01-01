<?php
/**
 * Run All Scrapers
 * Execute all opportunity scrapers and log results
 *
 * Usage:
 * - Manual: php run_scrapers.php
 * - Scheduled: Set up Windows Task Scheduler or cron job
 * - Command: php run_scrapers.php [scholarship|job|internship|grant|competition|all]
 */

// Set execution time limit (scrapers may take a while)
set_time_limit(600); // 10 minutes

// Determine which scrapers to run
$scraper_type = isset($argv[1]) ? strtolower($argv[1]) : 'all';

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ScholarshipScraper.php';
require_once __DIR__ . '/JobScraper.php';
require_once __DIR__ . '/InternshipScraper.php';
require_once __DIR__ . '/GrantScraper.php';
require_once __DIR__ . '/CompetitionScraper.php';

// Output formatting
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
}

// Start scraping
logMessage("=== Starting Opportunity Scrapers ===");
logMessage("Mode: " . ($scraper_type === 'all' ? 'ALL SCRAPERS' : strtoupper($scraper_type)));

$conn = getDatabaseConnection();
$results = [];
$start_time = time();

try {
    // Run Scholarship Scraper
    if ($scraper_type === 'all' || $scraper_type === 'scholarship') {
        logMessage("\n--- Running Scholarship Scraper ---");
        $scholarshipScraper = new ScholarshipScraper($conn);
        $result = $scholarshipScraper->run();
        $results['scholarship'] = $result;

        if ($result['success']) {
            logMessage("✓ Scholarship Scraper completed successfully");
            logMessage("  - Items scraped: {$result['items_scraped']}");
            logMessage("  - Items added: {$result['items_added']}");
            logMessage("  - Items updated: {$result['items_updated']}");
            logMessage("  - Expired deleted: {$result['expired_deleted']}");
        } else {
            logMessage("✗ Scholarship Scraper failed: {$result['error']}");
        }
    }

    // Run Job Scraper
    if ($scraper_type === 'all' || $scraper_type === 'job') {
        logMessage("\n--- Running Job Scraper ---");
        $jobScraper = new JobScraper($conn);
        $result = $jobScraper->run();
        $results['job'] = $result;

        if ($result['success']) {
            logMessage("✓ Job Scraper completed successfully");
            logMessage("  - Items scraped: {$result['items_scraped']}");
            logMessage("  - Items added: {$result['items_added']}");
            logMessage("  - Items updated: {$result['items_updated']}");
            logMessage("  - Expired deleted: {$result['expired_deleted']}");
        } else {
            logMessage("✗ Job Scraper failed: {$result['error']}");
        }
    }

    // Run Internship Scraper
    if ($scraper_type === 'all' || $scraper_type === 'internship') {
        logMessage("\n--- Running Internship Scraper ---");
        $internshipScraper = new InternshipScraper($conn);
        $result = $internshipScraper->run();
        $results['internship'] = $result;

        if ($result['success']) {
            logMessage("✓ Internship Scraper completed successfully");
            logMessage("  - Items scraped: {$result['items_scraped']}");
            logMessage("  - Items added: {$result['items_added']}");
            logMessage("  - Items updated: {$result['items_updated']}");
            logMessage("  - Expired deleted: {$result['expired_deleted']}");
        } else {
            logMessage("✗ Internship Scraper failed: {$result['error']}");
        }
    }

    // Run Grant Scraper
    if ($scraper_type === 'all' || $scraper_type === 'grant') {
        logMessage("\n--- Running Grant Scraper ---");
        $grantScraper = new GrantScraper($conn);
        $result = $grantScraper->run();
        $results['grant'] = $result;

        if ($result['success']) {
            logMessage("✓ Grant Scraper completed successfully");
            logMessage("  - Items scraped: {$result['items_scraped']}");
            logMessage("  - Items added: {$result['items_added']}");
            logMessage("  - Items updated: {$result['items_updated']}");
            logMessage("  - Expired deleted: {$result['expired_deleted']}");
        } else {
            logMessage("✗ Grant Scraper failed: {$result['error']}");
        }
    }

    // Run Competition Scraper
    if ($scraper_type === 'all' || $scraper_type === 'competition') {
        logMessage("\n--- Running Competition Scraper ---");
        $competitionScraper = new CompetitionScraper($conn);
        $result = $competitionScraper->run();
        $results['competition'] = $result;

        if ($result['success']) {
            logMessage("✓ Competition Scraper completed successfully");
            logMessage("  - Items scraped: {$result['items_scraped']}");
            logMessage("  - Items added: {$result['items_added']}");
            logMessage("  - Items updated: {$result['items_updated']}");
            logMessage("  - Expired deleted: {$result['expired_deleted']}");
        } else {
            logMessage("✗ Competition Scraper failed: {$result['error']}");
        }
    }

} catch (Exception $e) {
    logMessage("✗ Fatal error: " . $e->getMessage());
    error_log("Scraper fatal error: " . $e->getMessage());
}

closeDatabaseConnection($conn);

// Calculate totals
$total_scraped = 0;
$total_added = 0;
$total_updated = 0;
$failed_scrapers = 0;

foreach ($results as $type => $result) {
    if ($result['success']) {
        $total_scraped += $result['items_scraped'];
        $total_added += $result['items_added'];
        $total_updated += $result['items_updated'];
    } else {
        $failed_scrapers++;
    }
}

$execution_time = time() - $start_time;

// Summary
logMessage("\n=== Scraping Summary ===");
logMessage("Total execution time: {$execution_time} seconds");
logMessage("Total items scraped: {$total_scraped}");
logMessage("Total items added: {$total_added}");
logMessage("Total items updated: {$total_updated}");
logMessage("Failed scrapers: {$failed_scrapers}");
logMessage("\n=== Scraping Complete ===");

// Return success/failure exit code
exit($failed_scrapers > 0 ? 1 : 0);
?>
