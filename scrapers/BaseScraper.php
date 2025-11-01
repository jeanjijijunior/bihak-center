<?php
/**
 * Base Scraper Class
 * Parent class for all opportunity scrapers
 */

abstract class BaseScraper {
    protected $conn;
    protected $source_name;
    protected $scraper_type;
    protected $log_id;
    protected $items_scraped = 0;
    protected $items_added = 0;
    protected $items_updated = 0;
    protected $items_rejected = 0;
    protected $started_at;
    protected $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    public function __construct($conn, $source_name, $scraper_type) {
        $this->conn = $conn;
        $this->source_name = $source_name;
        $this->scraper_type = $scraper_type;
        $this->started_at = date('Y-m-d H:i:s');
    }

    /**
     * Main scraping method - must be implemented by child classes
     */
    abstract public function scrape();

    /**
     * Fetch content from URL
     */
    protected function fetchURL($url, $options = []) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);

        // Add custom headers if provided
        if (isset($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception("HTTP Error: $http_code for URL: $url");
        }

        return $response;
    }

    /**
     * Parse HTML content
     */
    protected function parseHTML($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        return $dom;
    }

    /**
     * Validate opportunity URL is working
     */
    protected function validateOpportunityUrl($url) {
        if (empty($url)) {
            return false;
        }

        // Use HEAD request for faster validation
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Quick timeout for validation
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Accept 200 OK and 3xx redirects
        return ($http_code >= 200 && $http_code < 400);
    }

    /**
     * Check if opportunity is eligible for African youth
     */
    protected function isEligibleForAfrica($data) {
        // Keywords indicating Africa eligibility
        $africanKeywords = [
            'africa', 'african', 'sub-saharan', 'sub saharan',
            'rwanda', 'kenya', 'uganda', 'tanzania', 'burundi',
            'congo', 'nigeria', 'ghana', 'ethiopia', 'south africa',
            'worldwide', 'international', 'all countries',
            'developing countries', 'global', 'any country',
            'all nationalities', 'open to all'
        ];

        // Combine all searchable fields
        $searchText = strtolower(
            ($data['description'] ?? '') . ' ' .
            ($data['eligibility'] ?? '') . ' ' .
            ($data['location'] ?? '') . ' ' .
            ($data['country'] ?? '') . ' ' .
            ($data['requirements'] ?? '')
        );

        // Check if any African keyword is present
        foreach ($africanKeywords as $keyword) {
            if (strpos($searchText, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate opportunity quality before saving
     */
    protected function validateOpportunityQuality($data) {
        $errors = [];

        // 1. Application URL must exist and be valid
        if (empty($data['application_url'])) {
            $errors[] = 'Missing application URL';
        } elseif (!$this->validateOpportunityUrl($data['application_url'])) {
            $errors[] = 'Application URL is not accessible';
        }

        // 2. Description must be substantial (minimum 100 characters)
        if (empty($data['description']) || strlen($data['description']) < 100) {
            $errors[] = 'Description too short (minimum 100 characters)';
        }

        // 3. Deadline must be in the future
        if (!empty($data['deadline'])) {
            $deadline_time = strtotime($data['deadline']);
            if ($deadline_time && $deadline_time < time()) {
                $errors[] = 'Deadline has already passed';
            }
        }

        // 4. Organization name is required
        if (empty($data['organization'])) {
            $errors[] = 'Missing organization name';
        }

        // 5. Title is required and should be meaningful
        if (empty($data['title']) || strlen($data['title']) < 10) {
            $errors[] = 'Title missing or too short';
        }

        // 6. Must be eligible for African youth
        if (!$this->isEligibleForAfrica($data)) {
            $errors[] = 'Not eligible for African youth';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Save opportunity to database
     */
    protected function saveOpportunity($data) {
        try {
            // Validate opportunity quality first
            $validation = $this->validateOpportunityQuality($data);

            if (!$validation['valid']) {
                $this->items_rejected++;
                error_log("Opportunity rejected: {$data['title']} - Reasons: " . implode(', ', $validation['errors']));
                return false;
            }

            // Check if opportunity already exists (by title and organization)
            $check_stmt = $this->conn->prepare("
                SELECT id FROM opportunities
                WHERE title = ? AND organization = ?
            ");
            $check_stmt->bind_param('ss', $data['title'], $data['organization']);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing opportunity
                $row = $result->fetch_assoc();
                $opportunity_id = $row['id'];

                $update_stmt = $this->conn->prepare("
                    UPDATE opportunities
                    SET description = ?,
                        location = ?,
                        country = ?,
                        deadline = ?,
                        application_url = ?,
                        requirements = ?,
                        benefits = ?,
                        eligibility = ?,
                        amount = ?,
                        currency = ?,
                        is_active = TRUE,
                        scraped_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                $update_stmt->bind_param('ssssssssssi',
                    $data['description'],
                    $data['location'],
                    $data['country'],
                    $data['deadline'],
                    $data['application_url'],
                    $data['requirements'],
                    $data['benefits'],
                    $data['eligibility'],
                    $data['amount'],
                    $data['currency'],
                    $opportunity_id
                );

                if ($update_stmt->execute()) {
                    $this->items_updated++;
                    return $opportunity_id;
                }

            } else {
                // Insert new opportunity
                $insert_stmt = $this->conn->prepare("
                    INSERT INTO opportunities (
                        title, description, type, organization, location, country,
                        deadline, application_url, requirements, benefits, eligibility,
                        amount, currency, source_url, source_name
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $insert_stmt->bind_param('sssssssssssssss',
                    $data['title'],
                    $data['description'],
                    $this->scraper_type,
                    $data['organization'],
                    $data['location'],
                    $data['country'],
                    $data['deadline'],
                    $data['application_url'],
                    $data['requirements'],
                    $data['benefits'],
                    $data['eligibility'],
                    $data['amount'],
                    $data['currency'],
                    $data['source_url'],
                    $this->source_name
                );

                if ($insert_stmt->execute()) {
                    $this->items_added++;
                    return $this->conn->insert_id;
                }
            }

        } catch (Exception $e) {
            error_log("Error saving opportunity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean text data
     */
    protected function cleanText($text) {
        if (!$text) return '';

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Parse date string to MySQL format
     */
    protected function parseDate($date_string) {
        if (!$date_string) return null;

        try {
            $date = new DateTime($date_string);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Start scraping log
     */
    protected function startLog() {
        $stmt = $this->conn->prepare("
            INSERT INTO scraper_log (source_name, scraper_type, status, started_at)
            VALUES (?, ?, 'running', ?)
        ");
        $stmt->bind_param('sss', $this->source_name, $this->scraper_type, $this->started_at);
        $stmt->execute();
        $this->log_id = $this->conn->insert_id;
    }

    /**
     * Complete scraping log
     */
    protected function completeLog($status = 'success', $error_message = null) {
        if (!$this->log_id) return;

        $execution_time = strtotime('now') - strtotime($this->started_at);

        $stmt = $this->conn->prepare("
            UPDATE scraper_log
            SET status = ?,
                items_scraped = ?,
                items_added = ?,
                items_updated = ?,
                error_message = ?,
                completed_at = CURRENT_TIMESTAMP,
                execution_time = ?
            WHERE id = ?
        ");

        $stmt->bind_param('siiisii',
            $status,
            $this->items_scraped,
            $this->items_added,
            $this->items_updated,
            $error_message,
            $execution_time,
            $this->log_id
        );

        $stmt->execute();
    }

    /**
     * Run the scraper with error handling and logging
     */
    public function run() {
        $this->startLog();

        try {
            $this->scrape();
            $this->completeLog('success');

            return [
                'success' => true,
                'items_scraped' => $this->items_scraped,
                'items_added' => $this->items_added,
                'items_updated' => $this->items_updated
            ];

        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log("Scraper error [{$this->source_name}]: {$error_message}");

            $this->completeLog('failed', $error_message);

            return [
                'success' => false,
                'error' => $error_message
            ];
        }
    }

    /**
     * Get statistics
     */
    public function getStats() {
        return [
            'source_name' => $this->source_name,
            'scraper_type' => $this->scraper_type,
            'items_scraped' => $this->items_scraped,
            'items_added' => $this->items_added,
            'items_updated' => $this->items_updated,
            'items_rejected' => $this->items_rejected
        ];
    }
}
?>
