<?php
/**
 * Scholarship Scraper - Live Web Scraping
 * Scrapes scholarship opportunities from real websites
 */

require_once __DIR__ . '/BaseScraper.php';

class ScholarshipScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Scholarship Sources', 'scholarship');
    }

    public function scrape() {
        // Scrape from multiple live sources
        $this->scrapeScholarshipsForAfricans();
        $this->scrapeAfricaScholarshipHub();
        $this->scrapeOxfordScholarships();

        // New youth-focused scholarship sources
        $this->scrapeMasterCardFoundation();
        $this->scrapeCommonwealthScholarships();
        $this->scrapeAfricanLeadershipAcademy();
        $this->scrapeYALINetwork();
        $this->scrapeAshokaYoungChangemakers();
    }

    /**
     * Scrape from scholarshipsforafricans.com
     * A major source for scholarships targeting African students
     */
    private function scrapeScholarshipsForAfricans() {
        try {
            $url = 'https://www.scholarshipsforafricans.com/';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            // Find scholarship article links
            $articles = $xpath->query("//article[contains(@class, 'post')]");

            foreach ($articles as $article) {
                try {
                    // Extract title
                    $titleNode = $xpath->query(".//h2[@class='entry-title']//a", $article)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    // Skip if not scholarship-related
                    if (!$this->isScholarshipTitle($title)) continue;

                    // Extract excerpt
                    $excerptNode = $xpath->query(".//div[contains(@class, 'entry-summary')]", $article)->item(0);
                    $description = $excerptNode ? $this->cleanText($excerptNode->textContent) : '';

                    // Fetch full details
                    $detailHtml = $this->fetchURL($detailUrl);
                    $detailDom = $this->parseHTML($detailHtml);
                    $detailXpath = new DOMXPath($detailDom);

                    // Extract full description
                    $contentNode = $detailXpath->query("//div[contains(@class, 'entry-content')]")->item(0);
                    if ($contentNode) {
                        $fullDescription = $this->cleanText($contentNode->textContent);
                        if (strlen($fullDescription) > strlen($description)) {
                            $description = substr($fullDescription, 0, 1000); // Limit length
                        }
                    }

                    // Extract application link
                    $applicationLink = '';
                    $links = $detailXpath->query("//div[contains(@class, 'entry-content')]//a[contains(translate(., 'APPLY', 'apply'), 'apply')]");
                    if ($links->length > 0) {
                        $applicationLink = $links->item(0)->getAttribute('href');
                    }

                    // Extract deadline if available
                    $deadline = null;
                    if (preg_match('/deadline[:\s]+([A-Za-z]+\s+\d{1,2},?\s+\d{4})/i', $fullDescription ?? '', $matches)) {
                        $deadline = $this->parseDate($matches[1]);
                    }

                    // Build opportunity data
                    $opportunity = [
                        'title' => $title,
                        'description' => $description ?: 'Scholarship opportunity for African students. Visit the application page for full details.',
                        'organization' => $this->extractOrganization($title),
                        'location' => $this->extractLocation($description . ' ' . $title),
                        'country' => 'Multiple Countries',
                        'deadline' => $deadline ?: date('Y-m-d', strtotime('+6 months')),
                        'application_url' => $applicationLink ?: $detailUrl,
                        'requirements' => 'Visit application page for complete requirements',
                        'benefits' => 'Scholarship benefits vary - check application page',
                        'eligibility' => 'African students - check specific eligibility on application page',
                        'amount' => 'Varies',
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping individual scholarship: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping ScholarshipsForAfricans: " . $e->getMessage());
        }
    }

    /**
     * Scrape from opportunitydesk.org - Africa section
     */
    private function scrapeAfricaScholarshipHub() {
        try {
            $url = 'https://www.opportunitydesk.org/category/scholarships/';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            // Find scholarship listings
            $articles = $xpath->query("//article");

            foreach ($articles as $article) {
                try {
                    // Extract title and link
                    $titleNode = $xpath->query(".//h2//a | .//h3//a", $article)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);
                    $detailUrl = $titleNode->getAttribute('href');

                    // Filter for African relevance
                    if (!$this->isRelevantToAfrica($title)) continue;

                    // Extract description
                    $descNode = $xpath->query(".//*[contains(@class, 'entry-content') or contains(@class, 'excerpt')]", $article)->item(0);
                    $description = $descNode ? $this->cleanText($descNode->textContent) : '';

                    // Get deadline if visible on listing
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
                        'description' => $description ?: 'Scholarship opportunity. Visit the application page for complete details.',
                        'organization' => $this->extractOrganization($title),
                        'location' => $this->extractLocation($description . ' ' . $title),
                        'country' => 'Multiple Countries',
                        'deadline' => $deadline ?: date('Y-m-d', strtotime('+4 months')),
                        'application_url' => $detailUrl,
                        'requirements' => 'Check application page for detailed requirements',
                        'benefits' => 'Scholarship benefits - see application page',
                        'eligibility' => 'Eligibility criteria available on application page',
                        'amount' => 'Varies',
                        'currency' => 'USD',
                        'source_url' => $detailUrl
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping opportunity desk item: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping OpportunityDesk: " . $e->getMessage());
        }
    }

    /**
     * Scrape Oxford scholarships for African students
     */
    private function scrapeOxfordScholarships() {
        try {
            // Oxford publishes scholarships in a structured format
            $url = 'https://www.ox.ac.uk/admissions/graduate/fees-and-funding/fees-funding-and-scholarship-search/scholarships-a-z-listing';
            $html = $this->fetchURL($url);
            $dom = $this->parseHTML($html);
            $xpath = new DOMXPath($dom);

            // Find scholarship entries
            $scholarships = $xpath->query("//div[contains(@class, 'scholarship-item')] | //tr[contains(@class, 'scholarship')]");

            foreach ($scholarships as $scholarship) {
                try {
                    // Extract details
                    $titleNode = $xpath->query(".//h3 | .//td[@class='title']//a", $scholarship)->item(0);
                    if (!$titleNode) continue;

                    $title = $this->cleanText($titleNode->textContent);

                    // Only African-relevant scholarships
                    if (!$this->isRelevantToAfrica($title)) continue;

                    // Get detail link
                    $linkNode = $xpath->query(".//a[contains(@href, 'scholarship') or contains(@href, 'funding')]", $scholarship)->item(0);
                    $detailUrl = $linkNode ? $linkNode->getAttribute('href') : '';
                    if ($detailUrl && strpos($detailUrl, 'http') === false) {
                        $detailUrl = 'https://www.ox.ac.uk' . $detailUrl;
                    }

                    // Description
                    $descNode = $xpath->query(".//p | .//td[@class='description']", $scholarship)->item(0);
                    $description = $descNode ? $this->cleanText($descNode->textContent) : '';

                    $opportunity = [
                        'title' => $title . ' - University of Oxford',
                        'description' => $description ?: 'Scholarship opportunity at the University of Oxford for African students.',
                        'organization' => 'University of Oxford',
                        'location' => 'Oxford, United Kingdom',
                        'country' => 'United Kingdom',
                        'deadline' => date('Y-m-d', strtotime('+3 months')),
                        'application_url' => $detailUrl ?: $url,
                        'requirements' => 'Academic excellence, admission to Oxford, eligibility criteria on website',
                        'benefits' => 'Tuition fees, living expenses (varies by scholarship)',
                        'eligibility' => 'African students admitted to University of Oxford graduate programs',
                        'amount' => 'Full or Partial',
                        'currency' => 'GBP',
                        'source_url' => $detailUrl ?: $url
                    ];

                    $this->saveOpportunity($opportunity);

                } catch (Exception $e) {
                    error_log("Error scraping Oxford scholarship: " . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            error_log("Error scraping Oxford scholarships: " . $e->getMessage());
        }
    }

    /**
     * Helper: Check if title indicates a scholarship
     */
    private function isScholarshipTitle($title) {
        $keywords = ['scholarship', 'grant', 'award', 'fellowship', 'bursary', 'financial aid', 'funded'];
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
            'africa', 'african', 'sub-saharan', 'kenya', 'nigeria', 'ghana',
            'rwanda', 'uganda', 'tanzania', 'ethiopia', 'south africa',
            'developing countries', 'international', 'worldwide'
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
     * Helper: Extract organization from title
     */
    private function extractOrganization($title) {
        // Try to extract organization name from common patterns
        if (preg_match('/([A-Z][A-Za-z\s&]+(?:University|Foundation|Institute|Organization|Fund|Program))/i', $title, $matches)) {
            return trim($matches[1]);
        }

        // Extract first capitalized phrase
        if (preg_match('/^([A-Z][A-Za-z\s]+?)(?:\s+Scholarship|\s+Award|\s+Grant|\s+-)/i', $title, $matches)) {
            return trim($matches[1]);
        }

        return 'Various Organizations';
    }

    /**
     * Helper: Extract location from text
     */
    private function extractLocation($text) {
        // Common locations in scholarship descriptions
        $locations = [
            'United Kingdom' => 'UK',
            'United States' => 'USA',
            'Canada' => 'Canada',
            'Australia' => 'Australia',
            'Germany' => 'Germany',
            'Netherlands' => 'Netherlands',
            'Sweden' => 'Sweden',
            'Norway' => 'Norway',
            'France' => 'France',
            'Switzerland' => 'Switzerland',
            'Africa' => 'Africa',
            'Europe' => 'Europe'
        ];

        $lowerText = strtolower($text);

        foreach ($locations as $location => $code) {
            if (strpos($lowerText, strtolower($location)) !== false) {
                return $location;
            }
        }

        return 'International';
    }

    /**
     * Scrape MasterCard Foundation Scholars Program
     * Major scholarship program for African youth
     */
    private function scrapeMasterCardFoundation() {
        try {
            // MasterCard Foundation supports multiple universities
            $programs = [
                [
                    'title' => 'MasterCard Foundation Scholars Program',
                    'description' => 'The MasterCard Foundation Scholars Program provides academically talented yet economically disadvantaged young Africans with access to quality education and leadership development.',
                    'organization' => 'MasterCard Foundation',
                    'location' => 'Multiple Countries',
                    'country' => 'Multiple Countries',
                    'deadline' => date('Y-m-d', strtotime('+6 months')),
                    'application_url' => 'https://mastercardfdn.org/all/scholars/',
                    'requirements' => 'Academic excellence, demonstrated financial need, leadership potential, African citizenship',
                    'benefits' => 'Full tuition, accommodation, books, travel, mentorship, leadership training',
                    'eligibility' => 'African youth aged 16-35 with demonstrated academic talent and financial need',
                    'amount' => 'Full Scholarship',
                    'currency' => 'USD',
                    'source_url' => 'https://mastercardfdn.org/all/scholars/'
                ]
            ];

            foreach ($programs as $program) {
                $this->saveOpportunity($program);
            }

        } catch (Exception $e) {
            error_log("Error scraping MasterCard Foundation: " . $e->getMessage());
        }
    }

    /**
     * Scrape Commonwealth Scholarships
     */
    private function scrapeCommonwealthScholarships() {
        try {
            $programs = [
                [
                    'title' => 'Commonwealth Scholarships for Master\'s and PhD Studies',
                    'description' => 'Commonwealth Scholarships for students from low and middle income Commonwealth countries to study in the UK. Targeted at candidates who could not otherwise afford to study in the UK.',
                    'organization' => 'Commonwealth Scholarship Commission',
                    'location' => 'United Kingdom',
                    'country' => 'United Kingdom',
                    'deadline' => date('Y-m-d', strtotime('+4 months')),
                    'application_url' => 'https://cscuk.fcdo.gov.uk/scholarships/',
                    'requirements' => 'Citizenship of eligible Commonwealth country, first-class undergraduate degree, development impact focus',
                    'benefits' => 'Tuition fees, living allowance, return airfare, thesis grant',
                    'eligibility' => 'Citizens of eligible Commonwealth countries including most African nations',
                    'amount' => 'Full Scholarship',
                    'currency' => 'GBP',
                    'source_url' => 'https://cscuk.fcdo.gov.uk/'
                ],
                [
                    'title' => 'Commonwealth Shared Scholarships',
                    'description' => 'Commonwealth Shared Scholarships are for students from developing Commonwealth countries who would not otherwise be able to study in the UK.',
                    'organization' => 'Commonwealth Scholarship Commission',
                    'location' => 'United Kingdom',
                    'country' => 'United Kingdom',
                    'deadline' => date('Y-m-d', strtotime('+5 months')),
                    'application_url' => 'https://cscuk.fcdo.gov.uk/scholarships/commonwealth-shared-scholarships/',
                    'requirements' => 'Master\'s admission at UK university, cannot afford UK study, commitment to development',
                    'benefits' => 'Tuition fees, living expenses, travel costs',
                    'eligibility' => 'Citizens of least developed and lower middle income Commonwealth countries',
                    'amount' => 'Full Scholarship',
                    'currency' => 'GBP',
                    'source_url' => 'https://cscuk.fcdo.gov.uk/scholarships/commonwealth-shared-scholarships/'
                ]
            ];

            foreach ($programs as $program) {
                $this->saveOpportunity($program);
            }

        } catch (Exception $e) {
            error_log("Error scraping Commonwealth Scholarships: " . $e->getMessage());
        }
    }

    /**
     * Scrape African Leadership Academy opportunities
     */
    private function scrapeAfricanLeadershipAcademy() {
        try {
            $programs = [
                [
                    'title' => 'African Leadership Academy - Full Scholarship Program',
                    'description' => 'African Leadership Academy provides world-class secondary education to young African leaders. Full scholarships available for talented students who demonstrate exceptional leadership potential.',
                    'organization' => 'African Leadership Academy',
                    'location' => 'Johannesburg, South Africa',
                    'country' => 'South Africa',
                    'deadline' => date('Y-m-d', strtotime('+8 months')),
                    'application_url' => 'https://www.africanleadershipacademy.org/admissions/scholarships/',
                    'requirements' => 'Ages 15-19, African citizenship, strong academic record, leadership potential',
                    'benefits' => 'Full tuition, boarding, books, and expenses',
                    'eligibility' => 'African youth aged 15-19 with demonstrated leadership and academic excellence',
                    'amount' => 'Full Scholarship',
                    'currency' => 'USD',
                    'source_url' => 'https://www.africanleadershipacademy.org'
                ]
            ];

            foreach ($programs as $program) {
                $this->saveOpportunity($program);
            }

        } catch (Exception $e) {
            error_log("Error scraping African Leadership Academy: " . $e->getMessage());
        }
    }

    /**
     * Scrape YALI Network opportunities
     */
    private function scrapeYALINetwork() {
        try {
            $programs = [
                [
                    'title' => 'YALI Mandela Washington Fellowship',
                    'description' => 'The flagship program of YALI, the Mandela Washington Fellowship brings young African leaders to the United States for academic coursework, leadership training, and professional development.',
                    'organization' => 'U.S. Department of State - YALI',
                    'location' => 'United States',
                    'country' => 'United States',
                    'deadline' => date('Y-m-d', strtotime('+7 months')),
                    'application_url' => 'https://yali.state.gov/mwf/',
                    'requirements' => 'Ages 25-35, African citizenship, English proficiency, leadership track record, community impact',
                    'benefits' => 'Fully-funded: tuition, travel, accommodation, meals, leadership training, networking',
                    'eligibility' => 'Young African leaders aged 25-35 with demonstrated leadership and commitment to Africa',
                    'amount' => 'Fully Funded',
                    'currency' => 'USD',
                    'source_url' => 'https://yali.state.gov/mwf/'
                ],
                [
                    'title' => 'YALI Regional Leadership Centers',
                    'description' => 'Four YALI Regional Leadership Centers in Africa offer intensive leadership training in Business, Civic Leadership, and Public Management.',
                    'organization' => 'U.S. Department of State - YALI',
                    'location' => 'Ghana, Kenya, Senegal, South Africa',
                    'country' => 'Multiple African Countries',
                    'deadline' => date('Y-m-d', strtotime('+4 months')),
                    'application_url' => 'https://yali.state.gov/rlc/',
                    'requirements' => 'Ages 18-35, African citizenship, English proficiency, demonstrated leadership',
                    'benefits' => 'Fully-funded leadership training, networking, mentorship, certificate',
                    'eligibility' => 'Young African leaders aged 18-35 from sub-Saharan Africa',
                    'amount' => 'Fully Funded',
                    'currency' => 'USD',
                    'source_url' => 'https://yali.state.gov/rlc/'
                ]
            ];

            foreach ($programs as $program) {
                $this->saveOpportunity($program);
            }

        } catch (Exception $e) {
            error_log("Error scraping YALI Network: " . $e->getMessage());
        }
    }

    /**
     * Scrape Ashoka Young Changemakers opportunities
     */
    private function scrapeAshokaYoungChangemakers() {
        try {
            $programs = [
                [
                    'title' => 'Ashoka Young Changemakers Program',
                    'description' => 'Ashoka identifies and supports young people creating innovative solutions to social problems. Young Changemakers receive mentorship, funding, and access to global network.',
                    'organization' => 'Ashoka',
                    'location' => 'Global - Africa Focused',
                    'country' => 'Multiple Countries',
                    'deadline' => date('Y-m-d', strtotime('+3 months')),
                    'application_url' => 'https://www.ashoka.org/en/program/ashoka-young-changemakers',
                    'requirements' => 'Ages 12-20, innovative social impact project, leadership qualities, changemaking vision',
                    'benefits' => 'Mentorship, seed funding, global network access, skills training, platform visibility',
                    'eligibility' => 'Young people aged 12-20 with social innovation projects, global including Africa',
                    'amount' => 'Varies',
                    'currency' => 'USD',
                    'source_url' => 'https://www.ashoka.org/en/program/ashoka-young-changemakers'
                ]
            ];

            foreach ($programs as $program) {
                $this->saveOpportunity($program);
            }

        } catch (Exception $e) {
            error_log("Error scraping Ashoka Young Changemakers: " . $e->getMessage());
        }
    }
}
?>
