<?php
/**
 * Grant Scraper - Live Web Scraping
 * Scrapes grant and funding opportunities from real websites
 */

require_once __DIR__ . '/BaseScraper.php';

class GrantScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Grant Sources', 'grant');
    }

    public function scrape() {
        // Scrape from multiple live sources
        $this->scrapeOpportunityDesk();
        $this->scrapeFoundationCenterGrants();
    }

    /**
     * Scrape from OpportunityDesk - Grants section
     */
    private function scrapeOpportunityDesk() {
        try {
            $url = 'https://www.opportunitydesk.org/category/grants/';
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

                    if (!$this->isGrantRelated($title)) continue;
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
                        'description' => $description ?: 'Grant funding opportunity. Visit the application page for complete details.',
                        'organization' => $this->extractOrganization($title),
                        'location' => $this->extractLocation($description . ' ' . $title),
                        'country' => 'Multiple Countries',
                        'deadline' => $deadline ?: date('Y-m-d', strtotime('+90 days')),
                        'application_url' => $detailUrl,
                        'requirements' => 'Check application page for detailed requirements',
                        'benefits' => 'Grant funding for projects and initiatives',
                        'eligibility' => 'NGOs, individuals, and organizations working in Africa',
                        'amount' => 'Varies',
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping grant: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping OpportunityDesk grants: " . $e->getMessage());
        }
    }

    /**
     * Scrape grant opportunities for African projects
     */
    private function scrapeFoundationCenterGrants() {
        try {
            $url = 'https://www.fundsforngos.org/category/africa-funding/';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            $articles = $xpath->query("//article | //div[contains(@class, 'grant-item')]");

            foreach ($articles as $article) {
                try {
                    $titleNode = $xpath->query(".//h2//a | .//h3//a | .//a[contains(@class, 'title')]", $article)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    if (!$this->isGrantRelated($title)) continue;

                    $descNode = $xpath->query(".//*[contains(@class, 'entry-content') or contains(@class, 'excerpt') or .//p]", $article)->item(0);
                    $description = $descNode ? $this->cleanText($descNode->textContent) : '';

                    // Extract amount if mentioned
                    $amount = 'Varies';
                    if (preg_match('/(\$|USD|€|EUR)\s*(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)/i', $description, $matches)) {
                        $amount = $matches[2];
                    }

                    $deadline = null;
                    if (preg_match('/deadline[:\s]+([A-Za-z]+\s+\d{1,2},?\s+\d{4})/i', $description, $matches)) {
                        $deadline = $this->parseDate($matches[1]);
                    }

                    $opportunity = [
                        'title' => $title,
                        'description' => $description ?: 'Grant funding opportunity for African projects and initiatives.',
                        'organization' => $this->extractOrganization($title),
                        'location' => 'Africa',
                        'country' => 'Multiple African Countries',
                        'deadline' => $deadline ?: date('Y-m-d', strtotime('+120 days')),
                        'application_url' => $detailUrl,
                        'requirements' => 'Registered NGOs, social enterprises, community organizations',
                        'benefits' => 'Grant funding for development projects',
                        'eligibility' => 'Organizations and initiatives working in Africa',
                        'amount' => $amount,
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping foundation center grant: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping foundation center grants: " . $e->getMessage());
        }
    }

    private function isGrantRelated($title) {
        $keywords = ['grant', 'funding', 'fund', 'financial support', 'award', 'prize', 'competition', 'call for proposals'];
        $lowerTitle = strtolower($title);
        foreach ($keywords as $keyword) {
            if (strpos($lowerTitle, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isRelevantToAfrica($text) {
        $keywords = [
            'africa', 'african', 'kenya', 'nigeria', 'ghana', 'rwanda',
            'uganda', 'tanzania', 'ethiopia', 'south africa', 'developing countries',
            'international', 'global', 'worldwide'
        ];
        $lowerText = strtolower($text);
        foreach ($keywords as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function extractOrganization($title) {
        if (preg_match('/([A-Z][A-Za-z\s&]+(?:Foundation|Fund|Organization|Initiative|Program|Trust))/i', $title, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/^([A-Z][A-Za-z\s]+?)(?:\s+Grant|\s+Fund|\s+Award|\s+-)/i', $title, $matches)) {
            return trim($matches[1]);
        }
        return 'Various Funding Organizations';
    }

    private function extractLocation($text) {
        $locations = [
            'Africa', 'Sub-Saharan Africa', 'East Africa', 'West Africa',
            'Kenya', 'Nigeria', 'Ghana', 'Rwanda', 'International'
        ];
        foreach ($locations as $location) {
            if (stripos($text, $location) !== false) {
                return $location;
            }
        }
        return 'Africa';
    }
}
?>
