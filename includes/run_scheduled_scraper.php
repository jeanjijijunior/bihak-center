<?php
/**
 * Scheduled Scraper Runner
 *
 * This script runs the opportunity scrapers on a schedule.
 * Can be executed via:
 * 1. Windows Task Scheduler (recommended for production)
 * 2. Manual cron-style execution
 * 3. Background PHP process
 *
 * @author Claude
 * @version 1.0
 * @date 2025-11-20
 */

// Prevent direct browser access (optional)
if (php_sapi_name() !== 'cli' && !isset($_GET['allow_web'])) {
    // Allow execution but log it
    error_log('Scheduled scraper executed via web interface at ' . date('Y-m-d H:i:s'));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/scraper.php';

// Log start time
$start_time = microtime(true);
$timestamp = date('Y-m-d H:i:s');

echo "🕐 Scheduled Scraper Run Started: $timestamp\n";
echo str_repeat('-', 60) . "\n";

// Check if scraper is already running (prevent overlap)
$lock_file = __DIR__ . '/../temp/scraper.lock';
if (!is_dir(__DIR__ . '/../temp')) {
    mkdir(__DIR__ . '/../temp', 0755, true);
}

if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    $time_diff = time() - $lock_time;

    // If lock is older than 1 hour, assume stale and remove
    if ($time_diff > 3600) {
        echo "⚠️  Removing stale lock file (age: {$time_diff}s)\n";
        unlink($lock_file);
    } else {
        echo "❌ Scraper is already running (locked {$time_diff}s ago)\n";
        echo "   If this is a mistake, delete: $lock_file\n";
        exit(1);
    }
}

// Create lock file
file_put_contents($lock_file, $timestamp);

try {
    $conn = getDatabaseConnection();

    // Get current opportunity count
    $before_count = $conn->query("SELECT COUNT(*) as count FROM opportunities")->fetch_assoc()['count'];
    echo "📊 Current opportunities in database: $before_count\n\n";

    // Run scrapers
    $results = [];

    // 1. Scrape Wamda
    echo "🔍 Scraping Wamda...\n";
    try {
        $wamda_result = scrapeWamda();
        $results['wamda'] = $wamda_result;
        echo "   ✅ Wamda: {$wamda_result['added']} new opportunities\n";
    } catch (Exception $e) {
        echo "   ❌ Wamda failed: {$e->getMessage()}\n";
        $results['wamda'] = ['added' => 0, 'error' => $e->getMessage()];
    }

    // 2. Scrape Arabnet
    echo "🔍 Scraping Arabnet...\n";
    try {
        $arabnet_result = scrapeArabnet();
        $results['arabnet'] = $arabnet_result;
        echo "   ✅ Arabnet: {$arabnet_result['added']} new opportunities\n";
    } catch (Exception $e) {
        echo "   ❌ Arabnet failed: {$e->getMessage()}\n";
        $results['arabnet'] = ['added' => 0, 'error' => $e->getMessage()];
    }

    // 3. Scrape MIT EF
    echo "🔍 Scraping MIT Enterprise Forum...\n";
    try {
        $mitef_result = scrapeMITEF();
        $results['mitef'] = $mitef_result;
        echo "   ✅ MIT EF: {$mitef_result['added']} new opportunities\n";
    } catch (Exception $e) {
        echo "   ❌ MIT EF failed: {$e->getMessage()}\n";
        $results['mitef'] = ['added' => 0, 'error' => $e->getMessage()];
    }

    // Calculate totals
    $total_added = ($results['wamda']['added'] ?? 0) +
                   ($results['arabnet']['added'] ?? 0) +
                   ($results['mitef']['added'] ?? 0);

    // Get final count
    $after_count = $conn->query("SELECT COUNT(*) as count FROM opportunities")->fetch_assoc()['count'];

    // Log to database
    $success = ($total_added > 0) ? 1 : 0;
    $log_message = "Scheduled run: {$total_added} new opportunities added";

    $stmt = $conn->prepare("
        INSERT INTO scraper_logs
        (source, opportunities_found, status, log_message, created_at)
        VALUES ('scheduled', ?, ?, ?, NOW())
    ");
    $status = $success ? 'success' : 'completed';
    $stmt->bind_param('iss', $total_added, $status, $log_message);
    $stmt->execute();

    closeDatabaseConnection($conn);

    // Summary
    echo "\n" . str_repeat('-', 60) . "\n";
    echo "📊 Summary:\n";
    echo "   Before: $before_count opportunities\n";
    echo "   After:  $after_count opportunities\n";
    echo "   Added:  $total_added new opportunities\n";

    $elapsed = round(microtime(true) - $start_time, 2);
    echo "\n⏱️  Completed in {$elapsed}s\n";
    echo "✅ Scheduled scraper run finished successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Error: {$e->getMessage()}\n";
    exit(1);
} finally {
    // Remove lock file
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}
