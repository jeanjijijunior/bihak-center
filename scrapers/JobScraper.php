<?php
/**
 * Job Scraper - Live Web Scraping
 * Scrapes job opportunities from real websites targeting African youth
 */

require_once __DIR__ . '/BaseScraper.php';

class JobScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Job Sources', 'job');
    }

    public function scrape() {
        // Scrape from multiple live sources
        $this->scrapeReliefWeb();
        $this->scrapeAfricanDevelopmentBank();
        $this->scrapeAfricaJobsNet();
    }

    /**
     * Scrape from ReliefWeb - Africa jobs
     */
    private function scrapeReliefWeb() {
        try {
            $url = 'https://reliefweb.int/jobs?search=africa';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            // Find job listings
            $jobs = $xpath->query("//article[contains(@class, 'job-item')] | //div[contains(@class, 'job-card')]");

            foreach ($jobs as $job) {
                try {
                    // Extract title
                    $titleNode = $xpath->query(".//h3//a | .//h4//a | .//*[contains(@class, 'job-title')]//a", $job)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    if ($detailUrl && strpos($detailUrl, 'http') === false) {
                        $detailUrl = 'https://reliefweb.int' . $detailUrl;
                    }

                    // Extract organization
                    $orgNode = $xpath->query(".//*[contains(@class, 'organization') or contains(@class, 'employer')]", $job)->item(0);
                    $organization = $orgNode ? $this->cleanText($orgNode->textContent) : 'Various Organizations';

                    // Extract location
                    $locationNode = $xpath->query(".//*[contains(@class, 'location') or contains(@class, 'country')]", $job)->item(0);
                    $location = $locationNode ? $this->cleanText($locationNode->textContent) : 'Africa';

                    // Extract description
                    $descNode = $xpath->query(".//*[contains(@class, 'description') or contains(@class, 'excerpt')]", $job)->item(0);
                    $description = $descNode ? $this->cleanText($descNode->textContent) : '';

                    // Extract deadline
                    $deadline = null;
                    $deadlineNode = $xpath->query(".//*[contains(@class, 'deadline') or contains(text(), 'Deadline')]", $job)->item(0);
                    if ($deadlineNode) {
                        $deadlineText = $this->cleanText($deadlineNode->textContent);
                        if (preg_match('/\d{1,2}\s+[A-Za-z]+\s+\d{4}/', $deadlineText, $matches)) {
                            $deadline = $this->parseDate($matches[0]);
                        }
                    }

                    $opportunity = [
                        'title' => $title,
                        'description' => $description ?: 'Job opportunity in Africa. Visit the application page for complete details.',
                        'organization' => $organization,
                        'location' => $location,
                        'country' => $this->extractCountryFromLocation($location),
                        'deadline' => $deadline ?: date('Y-m-d', strtotime('+30 days')),
                        'application_url' => $detailUrl,
                        'requirements' => 'See application page for detailed requirements',
                        'benefits' => 'Competitive salary and benefits package',
                        'eligibility' => 'Eligible for African residents and international applicants',
                        'amount' => 'Competitive',
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping ReliefWeb job: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping ReliefWeb: " . $e->getMessage());
        }
    }

    /**
     * Scrape African Development Bank careers
     */
    private function scrapeAfricanDevelopmentBank() {
        try {
            $url = 'https://www.afdb.org/en/careers/current-opportunities';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            // Find job listings
            $jobs = $xpath->query("//div[contains(@class, 'job-listing')] | //tr[contains(@class, 'job')]");

            foreach ($jobs as $job) {
                try {
                    // Extract title and link
                    $titleNode = $xpath->query(".//a[contains(@href, 'job') or contains(@href, 'career')]", $job)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    if ($detailUrl && strpos($detailUrl, 'http') === false) {
                        $detailUrl = 'https://www.afdb.org' . $detailUrl;
                    }

                    // Extract location
                    $locationNode = $xpath->query(".//*[contains(@class, 'location')]", $job)->item(0);
                    $location = $locationNode ? $this->cleanText($locationNode->textContent) : 'Africa';

                    $opportunity = [
                        'title' => $title . ' - African Development Bank',
                        'description' => 'Career opportunity at the African Development Bank. The AfDB is a multilateral development finance institution working to spur sustainable economic development in Africa.',
                        'organization' => 'African Development Bank',
                        'location' => $location,
                        'country' => $this->extractCountryFromLocation($location),
                        'deadline' => date('Y-m-d', strtotime('+45 days')),
                        'application_url' => $detailUrl,
                        'requirements' => 'Professional qualifications, relevant experience, commitment to African development',
                        'benefits' => 'International organization benefits, competitive compensation',
                        'eligibility' => 'Professionals committed to African development',
                        'amount' => 'Competitive',
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping AfDB job: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping AfDB careers: " . $e->getMessage());
        }
    }

    /**
     * Scrape from OpportunityDesk - Jobs section
     */
    private function scrapeAfricaJobsNet() {
        try {
            $url = 'https://www.opportunitydesk.org/category/jobs/';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            // Find job listings
            $articles = $xpath->query("//article");

            foreach ($articles as $article) {
                try {
                    // Extract title and link
                    $titleNode = $xpath->query(".//h2//a | .//h3//a", $article)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    // Filter for job-related content
                    if (!$this->isJobRelated($title)) continue;

                    // Filter for African relevance
                    if (!$this->isRelevantToAfrica($title)) continue;

                    // Extract description
                    $descNode = $xpath->query(".//*[contains(@class, 'entry-content') or contains(@class, 'excerpt')]", $article)->item(0);
                    $description = $descNode ? $this->cleanText($descNode->textContent) : '';

                    // Get deadline if visible
                    $deadline = null;
                    $deadlineNode = $xpath->query(".//*[contains(text(), 'Deadline') or contains(text(), 'deadline')]", $article)->item(0);
                    if ($deadlineNode) {
                        $deadlineText = $this->cleanText($deadlineNode->textContent);
                        if (preg_match('/\d{1,2}\s+[A-Za-z]+\s+\d{4}/', $deadlineText, $matches)) {
                            $deadline = $this->parseDate($matches[0]);
                        }
                    }

                    $opportunity = [
                        'title' => $title,
                        'description' => $description ?: 'Job opportunity in Africa. Visit the application page for complete details.',
                        'organization' => $this->extractOrganization($title, $description),
                        'location' => $this->extractLocation($description . ' ' . $title),
                        'country' => 'Multiple Countries',
                        'deadline' => $deadline ?: date('Y-m-d', strtotime('+30 days')),
                        'application_url' => $detailUrl,
                        'requirements' => 'Check application page for detailed requirements',
                        'benefits' => 'Competitive salary and benefits',
                        'eligibility' => 'Eligibility criteria available on application page',
                        'amount' => 'Competitive',
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping opportunity desk job: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping OpportunityDesk jobs: " . $e->getMessage());
        }
    }

    /**
     * Helper: Check if title is job-related
     */
    private function isJobRelated($title) {
        $keywords = ['job', 'career', 'position', 'vacancy', 'hiring', 'employment', 'opening', 'recruitment', 'work'];
        $lowerTitle = strtolower($title);

        foreach ($keywords as $keyword) {
            if (strpos($lowerTitle, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Check if relevant to Africa
     */
    private function isRelevantToAfrica($text) {
        $keywords = [
            'africa', 'african', 'kenya', 'nigeria', 'ghana', 'rwanda',
            'uganda', 'tanzania', 'ethiopia', 'south africa', 'international',
            'regional', 'continental'
        ];

        $lowerText = strtolower($text);

        foreach ($keywords as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Extract organization from title/description
     */
    private function extractOrganization($title, $description = '') {
        $text = $title . ' ' . $description;

        // Try to extract organization name
        if (preg_match('/\b(?:at|with|for)\s+([A-Z][A-Za-z\s&]+(?:Bank|Foundation|Institute|Organization|NGO|UN|Agency|Company|Corporation))/i', $text, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/^([A-Z][A-Za-z\s]+?)(?:\s+Job|\s+Position|\s+Career|\s+-)/i', $title, $matches)) {
            return trim($matches[1]);
        }

        return 'Various Organizations';
    }

    /**
     * Helper: Extract country from location
     */
    private function extractCountryFromLocation($location) {
        $countries = [
            'Kenya', 'Nigeria', 'Ghana', 'Rwanda', 'Uganda', 'Tanzania',
            'Ethiopia', 'South Africa', 'Egypt', 'Morocco', 'Senegal'
        ];

        foreach ($countries as $country) {
            if (stripos($location, $country) !== false) {
                return $country;
            }
        }

        return 'Multiple Countries';
    }

    /**
     * Helper: Extract location from text
     */
    private function extractLocation($text) {
        $locations = [
            'Nairobi', 'Lagos', 'Accra', 'Kigali', 'Kampala', 'Dar es Salaam',
            'Addis Ababa', 'Cape Town', 'Johannesburg', 'Cairo', 'Rabat',
            'Dakar', 'Abuja', 'Abidjan'
        ];

        $lowerText = strtolower($text);

        foreach ($locations as $location) {
            if (strpos($lowerText, strtolower($location)) !== false) {
                return $location;
            }
        }

        return 'Africa';
    }
}
?>
