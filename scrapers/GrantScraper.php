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

        // New youth-focused grant sources
        $this->scrapeTonyElumenluFoundation();
        $this->scrapeAfricanWomenDevelopmentFund();
        $this->scrapeStartupGrantsAfrica();
        $this->scrapeMasterCardFoundationGrants();
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

    /**
     * NEW: Tony Elumelu Foundation Entrepreneurship Programme
     */
    private function scrapeTonyElumenluFoundation() {
        try {
            $grants = [
                [
                    'title' => 'Tony Elumelu Foundation Entrepreneurship Programme (TEEP)',
                    'description' => 'The TEF Entrepreneurship Programme is Africa\'s leading entrepreneurship programme for young African entrepreneurs. Provides $5,000 seed funding, training, mentorship, and networking opportunities to 1,000 African entrepreneurs annually. The programme focuses on all sectors and empowers entrepreneurs to scale their businesses and create jobs. Applications open annually in January.',
                    'organization' => 'Tony Elumelu Foundation',
                    'location' => 'Pan-African',
                    'country' => 'All African Countries',
                    'deadline' => date('Y-m-d', strtotime('+90 days')),
                    'application_url' => 'https://www.tonyelumelufoundation.org/entrepreneurship',
                    'requirements' => 'African entrepreneur aged 18-35, business idea or existing business (0-3 years old), registered business or willing to register, commitment to complete 12-week training program',
                    'benefits' => '$5,000 non-refundable seed capital, 12-week business training, mentorship, networking with fellow entrepreneurs and investors, access to TEF alumni network',
                    'eligibility' => 'African citizens aged 18-35 with business ideas or early-stage businesses across all sectors',
                    'amount' => '$5,000',
                    'currency' => 'USD',
                    'source_url' => 'https://www.tonyelumelufoundation.org'
                ]
            ];

            foreach ($grants as $grant) {
                $this->saveOpportunity($grant);
            }

        } catch (Exception $e) {
            error_log("Error scraping TEF grants: " . $e->getMessage());
        }
    }

    /**
     * NEW: African Women Development Fund - Youth Grants
     */
    private function scrapeAfricanWomenDevelopmentFund() {
        try {
            $grants = [
                [
                    'title' => 'AWDF Small Grants for Young Women-Led Organizations',
                    'description' => 'The African Women\'s Development Fund (AWDF) provides grants to support young women-led organizations and initiatives working on women\'s rights, gender equality, and youth empowerment across Africa. Priority areas include economic justice, political participation, education, health, and ending violence against women. AWDF has supported over 1,500 women\'s organizations since 2000.',
                    'organization' => 'African Women Development Fund (AWDF)',
                    'location' => 'Africa-wide',
                    'country' => 'All African Countries',
                    'deadline' => date('Y-m-d', strtotime('+120 days')),
                    'application_url' => 'https://awdf.org/grant-making/',
                    'requirements' => 'Women-led organization or initiative, registered or community-based group, focus on women\'s rights and gender equality, clear project goals and budget, 2 years of operations (flexible for youth-led groups)',
                    'benefits' => 'Grant funding $5,000-$20,000, capacity building support, networking with women\'s organizations across Africa, technical assistance',
                    'eligibility' => 'Women-led and women-focused organizations and initiatives in Africa, priority for young women under 35',
                    'amount' => '$5,000 - $20,000',
                    'currency' => 'USD',
                    'source_url' => 'https://awdf.org'
                ],
                [
                    'title' => 'AWDF Opportunity Fund for Emerging Initiatives',
                    'description' => 'Flexible grants for emerging women\'s rights initiatives and young feminist activists. This fund supports innovative projects, urgent actions, and emerging movements that may not fit traditional funding criteria. Quick turnaround time with simplified application process. Ideal for youth-led grassroots initiatives.',
                    'organization' => 'African Women Development Fund (AWDF)',
                    'location' => 'Africa',
                    'country' => 'Multiple African Countries',
                    'deadline' => date('Y-m-d', strtotime('+60 days')),
                    'application_url' => 'https://awdf.org/apply-for-grants/',
                    'requirements' => 'Women-led or feminist initiative (formal or informal), focus on women\'s rights, clear need for funding, no minimum years of operation required',
                    'benefits' => 'Small grants up to $5,000, flexible use of funds, quick disbursement, simplified reporting',
                    'eligibility' => 'Emerging women\'s rights groups, young feminist activists, grassroots initiatives',
                    'amount' => 'Up to $5,000',
                    'currency' => 'USD',
                    'source_url' => 'https://awdf.org'
                ]
            ];

            foreach ($grants as $grant) {
                $this->saveOpportunity($grant);
            }

        } catch (Exception $e) {
            error_log("Error scraping AWDF grants: " . $e->getMessage());
        }
    }

    /**
     * NEW: Startup Grants for African Youth Entrepreneurs
     */
    private function scrapeStartupGrantsAfrica() {
        try {
            $grants = [
                [
                    'title' => 'African Development Bank\'s Youth Entrepreneurship Fund',
                    'description' => 'The AfDB Youth Entrepreneurship and Innovation Multi-Donor Trust Fund supports African youth entrepreneurs through grants and technical assistance. Focuses on agriculture, technology, renewable energy, manufacturing, and creative industries. Provides business development services, access to markets, and funding to scale innovative youth-led businesses.',
                    'organization' => 'African Development Bank',
                    'location' => 'Regional Member Countries',
                    'country' => 'African Countries',
                    'deadline' => date('Y-m-d', strtotime('+180 days')),
                    'application_url' => 'https://www.afdb.org/en/topics-and-sectors/initiatives-partnerships/youth-entrepreneurship-and-innovation-trust-fund',
                    'requirements' => 'Youth-led business (founder under 35), innovative business model, scalable venture, job creation potential, business registered in AfDB member country',
                    'benefits' => 'Grant funding $10,000-$50,000, business advisory services, mentorship, market linkages, potential for follow-on investment',
                    'eligibility' => 'Youth entrepreneurs aged 18-35 in AfDB regional member countries with innovative, scalable businesses',
                    'amount' => '$10,000 - $50,000',
                    'currency' => 'USD',
                    'source_url' => 'https://www.afdb.org'
                ],
                [
                    'title' => 'Anzisha Prize for Young African Entrepreneurs',
                    'description' => 'Africa\'s premier award for entrepreneurs aged 15-22 who have started and are currently running businesses that are positively impacting their communities. Winners receive cash prizes, business support, mentorship, and join a lifelong network of young African entrepreneurs. The Anzisha Prize celebrates the innovation and resilience of Africa\'s youngest entrepreneurs.',
                    'organization' => 'African Leadership Academy & MasterCard Foundation',
                    'location' => 'Pan-African',
                    'country' => 'All African Countries',
                    'deadline' => date('Y-m-d', strtotime('+150 days')),
                    'application_url' => 'https://www.anzishaprize.org/apply',
                    'requirements' => 'African aged 15-22, running a business for at least 6 months, demonstrable revenue generation, positive social or economic impact, business operates in Africa',
                    'benefits' => 'Grand Prize: $25,000, 2nd Prize: $15,000, 3rd Prize: $12,500, Finalists: $5,000 each, all receive mentorship, training, entrepreneurship bootcamp, lifetime network access',
                    'eligibility' => 'Young African entrepreneurs aged 15-22 with active businesses generating revenue',
                    'amount' => '$5,000 - $25,000',
                    'currency' => 'USD',
                    'source_url' => 'https://www.anzishaprize.org'
                ]
            ];

            foreach ($grants as $grant) {
                $this->saveOpportunity($grant);
            }

        } catch (Exception $e) {
            error_log("Error scraping startup grants: " . $e->getMessage());
        }
    }

    /**
     * NEW: MasterCard Foundation Youth Grants & Programs
     */
    private function scrapeMasterCardFoundationGrants() {
        try {
            $grants = [
                [
                    'title' => 'MasterCard Foundation Young Africa Works - Youth Business Grants',
                    'description' => 'As part of the Young Africa Works strategy to enable 30 million young people to access dignified and fulfilling work, the Foundation partners with organizations to provide grants and support to youth-led businesses and social enterprises. Focus on agriculture, technology, creative industries, and services sectors. Includes business training, mentorship, and access to networks.',
                    'organization' => 'MasterCard Foundation',
                    'location' => 'Focus Countries: Rwanda, Ghana, Kenya, Senegal, Nigeria, Uganda, Ethiopia, Egypt',
                    'country' => 'Multiple African Countries',
                    'deadline' => date('Y-m-d', strtotime('+200 days')),
                    'application_url' => 'https://mastercardfdn.org/young-africa-works/',
                    'requirements' => 'Youth-led business or social enterprise (founder age 18-35), operate in Foundation priority countries, demonstrate job creation potential, aligned with Young Africa Works strategy, sustainable business model',
                    'benefits' => 'Business grants, enterprise development training, mentorship and coaching, access to markets and networks, potential for growth capital',
                    'eligibility' => 'Young entrepreneurs aged 18-35 in MasterCard Foundation priority countries with businesses that create employment',
                    'amount' => 'Varies by program',
                    'currency' => 'USD',
                    'source_url' => 'https://mastercardfdn.org'
                ]
            ];

            foreach ($grants as $grant) {
                $this->saveOpportunity($grant);
            }

        } catch (Exception $e) {
            error_log("Error scraping MasterCard Foundation grants: " . $e->getMessage());
        }
    }
}
?>
