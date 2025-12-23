<?php
/**
 * Internship Scraper - Live Web Scraping
 * Scrapes internship opportunities from real websites
 */

require_once __DIR__ . '/BaseScraper.php';

class InternshipScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Internship Sources', 'internship');
    }

    public function scrape() {
        // Scrape from multiple live sources
        $this->scrapeOpportunityDesk();
        $this->scrapeInternAfricaOpportunities();
    }

    /**
     * Scrape from OpportunityDesk - Internships section
     */
    private function scrapeOpportunityDesk() {
        try {
            $url = 'https://www.opportunitydesk.org/category/internships/';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            $articles = $xpath->query("//article");

            foreach ($articles as $article) {
                try {
                    $titleNode = $xpath->query(".//h2//a | .//h3//a", $article)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    if (!$this->isInternshipRelated($title)) continue;
                    if (!$this->isRelevantToAfrica($title)) continue;

                    $descNode = $xpath->query(".//*[contains(@class, 'entry-content') or contains(@class, 'excerpt')]", $article)->item(0);
                    $description = $descNode ? $this->cleanText($descNode->textContent) : '';

                    $deadline = null;
                    $deadlineNode = $xpath->query(".//*[contains(text(), 'Deadline')]", $article)->item(0);
                    if ($deadlineNode) {
                        $deadlineText = $this->cleanText($deadlineNode->textContent);
                        if (preg_match('/\d{1,2}\s+[A-Za-z]+\s+\d{4}/', $deadlineText, $matches)) {
                            $deadline = $this->parseDate($matches[0]);
                        }
                    }

                    $opportunity = [
                        'title' => $title,
                        'description' => $description ?: 'Internship opportunity. Visit the application page for complete details.',
                        'organization' => $this->extractOrganization($title),
                        'location' => $this->extractLocation($description . ' ' . $title),
                        'country' => 'Multiple Countries',
                        'deadline' => $deadline ?: date('Y-m-d', strtotime('+60 days')),
                        'application_url' => $detailUrl,
                        'requirements' => 'Check application page for requirements',
                        'benefits' => 'Internship benefits - see application page',
                        'eligibility' => 'Students and recent graduates',
                        'amount' => 'Varies',
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping internship: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping OpportunityDesk internships: " . $e->getMessage());
        }
    }

    /**
     * Scrape internship opportunities targeting Africa
     */
    private function scrapeInternAfricaOpportunities() {
        try {
            $url = 'https://www.africanews.com/careers/';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            $listings = $xpath->query("//div[contains(@class, 'job-listing')] | //article[contains(@class, 'career')]");

            foreach ($listings as $listing) {
                try {
                    $titleNode = $xpath->query(".//h2//a | .//h3//a | .//a[contains(@class, 'title')]", $listing)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    if ($detailUrl && strpos($detailUrl, 'http') === false) {
                        $detailUrl = 'https://www.africanews.com' . $detailUrl;
                    }

                    if (!$this->isInternshipRelated($title)) continue;

                    $descNode = $xpath->query(".//p | .//*[contains(@class, 'description')]", $listing)->item(0);
                    $description = $descNode ? $this->cleanText($descNode->textContent) : '';

                    $opportunity = [
                        'title' => $title,
                        'description' => $description ?: 'Internship opportunity in Africa.',
                        'organization' => $this->extractOrganization($title),
                        'location' => 'Africa',
                        'country' => 'Multiple Countries',
                        'deadline' => date('Y-m-d', strtotime('+45 days')),
                        'application_url' => $detailUrl,
                        'requirements' => 'Students or recent graduates',
                        'benefits' => 'Practical experience, mentorship',
                        'eligibility' => 'African youth and international students',
                        'amount' => 'Varies',
                        'currency' => 'Local Currency',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping intern Africa opportunity: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping intern Africa opportunities: " . $e->getMessage());
        }
    }

    private function isInternshipRelated($title) {
        $keywords = ['internship', 'intern', 'trainee', 'placement', 'practicum', 'apprentice'];
        $lowerTitle = strtolower($title);
        foreach ($keywords as $keyword) {
            if (strpos($lowerTitle, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isRelevantToAfrica($text) {
        $keywords = ['africa', 'african', 'kenya', 'nigeria', 'ghana', 'rwanda', 'international', 'worldwide'];
        $lowerText = strtolower($text);
        foreach ($keywords as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function extractOrganization($title) {
        if (preg_match('/([A-Z][A-Za-z\s&]+(?:Organization|Foundation|Institute|Company|Corp))/i', $title, $matches)) {
            return trim($matches[1]);
        }
        return 'Various Organizations';
    }

    private function extractLocation($text) {
        $locations = ['Nairobi', 'Lagos', 'Accra', 'Kigali', 'Africa', 'International'];
        foreach ($locations as $location) {
            if (stripos($text, $location) !== false) {
                return $location;
            }
        }
        return 'International';
    }
}
?>
