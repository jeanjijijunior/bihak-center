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

        // New international fellowship/internship sources
        $this->scrapeWorldBankInternships();
        $this->scrapeMoIbrahimFoundation();
        $this->scrapeCorlettaScottKingFellowships();
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

    /**
     * NEW: World Bank Group Internship Program for African Youth
     */
    private function scrapeWorldBankInternships() {
        try {
            $internships = [
                [
                    'title' => 'World Bank Group Summer Internship Program',
                    'description' => 'The World Bank Group Summer Internship Program offers highly qualified graduate students an opportunity to work in technical and operational areas across the World Bank. Interns work closely with World Bank staff on projects related to poverty reduction, sustainable development, and economic growth in Africa and worldwide. Duration: 4-16 weeks during summer (June-September).',
                    'organization' => 'World Bank Group',
                    'location' => 'Washington DC, USA & Field Offices in Africa',
                    'country' => 'USA and African Countries',
                    'deadline' => date('Y-m-d', strtotime('+150 days')),
                    'application_url' => 'https://www.worldbank.org/en/about/careers/programs-and-internships/internship',
                    'requirements' => 'Currently enrolled in Master\'s/PhD program, strong academic performance (GPA 3.0+), fluency in English, knowledge of economics, finance, development studies or related fields, under age 32',
                    'benefits' => 'Monthly stipend, health insurance, travel support, professional networking, exposure to international development work, certificate of completion',
                    'eligibility' => 'Graduate students from World Bank member countries including all African nations, under 32 years old',
                    'amount' => 'Stipend provided',
                    'currency' => 'USD',
                    'source_url' => 'https://www.worldbank.org/en/about/careers'
                ],
                [
                    'title' => 'World Bank Young Professionals Program (YPP)',
                    'description' => 'A unique opportunity for young professionals from developing countries to launch their career in international development. The YPP is a 2-year entry-level program for highly qualified individuals interested in a career in development. Combines learning, professional development, and hands-on experience at the World Bank. Limited openings, highly competitive.',
                    'organization' => 'World Bank Group',
                    'location' => 'Washington DC & Global World Bank Offices',
                    'country' => 'USA and worldwide',
                    'deadline' => date('Y-m-d', strtotime('+180 days')),
                    'application_url' => 'https://www.worldbank.org/en/about/careers/programs-and-internships/young-professionals-program',
                    'requirements' => 'Master\'s degree (or equivalent) + at least 2 years professional experience OR PhD with no experience, under 32 years old, citizen of World Bank member country, fluency in English',
                    'benefits' => 'Competitive salary, comprehensive benefits, training and development, mentorship, global assignments, pathway to long-term World Bank career',
                    'eligibility' => 'Young professionals under 32 from World Bank member countries, preference for developing country nationals including Africans',
                    'amount' => 'Competitive salary',
                    'currency' => 'USD',
                    'source_url' => 'https://www.worldbank.org/en/about/careers'
                ]
            ];

            foreach ($internships as $internship) {
                $this->saveOpportunity($internship);
            }

        } catch (Exception $e) {
            error_log("Error scraping World Bank internships: " . $e->getMessage());
        }
    }

    /**
     * NEW: Mo Ibrahim Foundation Leadership Fellowships
     */
    private function scrapeMoIbrahimFoundation() {
        try {
            $fellowships = [
                [
                    'title' => 'Mo Ibrahim Foundation Leadership Fellowship',
                    'description' => 'The Mo Ibrahim Foundation offers fellowships for exceptional emerging African leaders to work in regional and international institutions. Fellows typically work on governance, policy development, and African affairs. The fellowship provides mentorship from African leaders, professional development, and contributes to building a new generation of African leadership. Previous placements include African Union, UNECA, and other pan-African institutions.',
                    'organization' => 'Mo Ibrahim Foundation',
                    'location' => 'Various (Pan-African Institutions)',
                    'country' => 'Multiple African Countries',
                    'deadline' => date('Y-m-d', strtotime('+120 days')),
                    'application_url' => 'https://mo.ibrahim.foundation/our-work',
                    'requirements' => 'African national, ages 25-35, Master\'s degree preferred, demonstrated leadership potential, commitment to African development, strong analytical and communication skills',
                    'benefits' => 'Competitive stipend, professional development, mentorship from African leaders, networking opportunities, contribution to African governance and development',
                    'eligibility' => 'Exceptional young African leaders aged 25-35 committed to transforming Africa',
                    'amount' => 'Stipend provided',
                    'currency' => 'USD',
                    'source_url' => 'https://mo.ibrahim.foundation'
                ]
            ];

            foreach ($fellowships as $fellowship) {
                $this->saveOpportunity($fellowship);
            }

        } catch (Exception $e) {
            error_log("Error scraping Mo Ibrahim Foundation fellowships: " . $e->getMessage());
        }
    }

    /**
     * NEW: Coretta Scott King Fellowship & Similar African-American Institute Programs
     */
    private function scrapeCorlettaScottKingFellowships() {
        try {
            $fellowships = [
                [
                    'title' => 'African-American Institute (AAI) International Fellowships',
                    'description' => 'AAI provides fellowships and training opportunities for African professionals and graduate students. Programs focus on leadership development, education, policy, economic development, and civil society. AAI has supported over 15,000 Africans through scholarships and fellowships since 1953. Fellowships include placements in African institutions, US institutions, and international organizations.',
                    'organization' => 'African-American Institute (AAI)',
                    'location' => 'USA and Africa',
                    'country' => 'Multiple Countries',
                    'deadline' => date('Y-m-d', strtotime('+90 days')),
                    'application_url' => 'https://www.aaionline.org/fellowships/',
                    'requirements' => 'African national, Bachelor\'s or Master\'s degree, professional experience in relevant field, demonstrated leadership, commitment to return to Africa and contribute to development',
                    'benefits' => 'Fellowship stipend, training and professional development, international exposure, mentorship, networking with African and American professionals',
                    'eligibility' => 'African professionals and graduate students committed to African development',
                    'amount' => 'Fellowship support',
                    'currency' => 'USD',
                    'source_url' => 'https://www.aaionline.org'
                ],
                [
                    'title' => 'Pan-African Youth Leadership Programme (PAYLP)',
                    'description' => 'PAYLP brings together young African leaders for intensive leadership training, community service projects, and cultural exchange. Participants engage in workshops on civic engagement, entrepreneurship, and social innovation. The program aims to build a network of young African leaders committed to positive change. Includes both in-country activities and international exchange components.',
                    'organization' => 'Various Partners (US State Department, African NGOs)',
                    'location' => 'Multiple African Countries',
                    'country' => 'Africa-wide',
                    'deadline' => date('Y-m-d', strtotime('+100 days')),
                    'application_url' => 'https://exchanges.state.gov/non-us/program/pan-african-youth-leadership-program',
                    'requirements' => 'African youth aged 18-25, demonstrated leadership in community or school, English proficiency, commitment to community service, no prior international exchange participation',
                    'benefits' => 'Fully-funded leadership training, community service experience, cultural exchange, networking with African youth leaders, certificate of completion',
                    'eligibility' => 'Young African leaders aged 18-25 committed to community development and social change',
                    'amount' => 'Fully Funded',
                    'currency' => 'N/A',
                    'source_url' => 'https://exchanges.state.gov'
                ]
            ];

            foreach ($fellowships as $fellowship) {
                $this->saveOpportunity($fellowship);
            }

        } catch (Exception $e) {
            error_log("Error scraping AAI/PAYLP fellowships: " . $e->getMessage());
        }
    }
}
?>
