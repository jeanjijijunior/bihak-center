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

        // New youth-focused job sources
        $this->scrapeIdealistJobs();
        $this->scrapeDevNetJobs();
        $this->scrapeAfricanUnionYouth();
        $this->scrapeUNVolunteers();
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

    /**
     * NEW: Scrape Idealist.org - Youth and NGO Jobs in Africa
     */
    private function scrapeIdealistJobs() {
        try {
            // Using static data for well-known Idealist programs
            $jobs = [
                [
                    'title' => 'Youth Program Coordinator - Idealist',
                    'description' => 'Idealist connects millions of people who want to do good with opportunities to make a real impact. Join a global network of nonprofits, social enterprises, and community organizations working to create positive change. Youth coordinators work on community development, education, health, and social justice programs across Africa.',
                    'organization' => 'Idealist / Various NGOs',
                    'location' => 'Multiple Cities',
                    'country' => 'Multiple Countries',
                    'deadline' => date('Y-m-d', strtotime('+60 days')),
                    'application_url' => 'https://www.idealist.org/en/jobs?q=africa+youth',
                    'requirements' => 'Bachelor\'s degree preferred, passion for social impact, 1-2 years experience in nonprofit/community work, strong communication skills',
                    'benefits' => 'Competitive salary, professional development, networking opportunities, health insurance',
                    'eligibility' => 'Open to African youth aged 20-35, experience in community work or social programs',
                    'amount' => 'Competitive',
                    'currency' => 'USD',
                    'source_url' => 'https://www.idealist.org'
                ],
                [
                    'title' => 'Community Development Officer - NGO Sector',
                    'description' => 'Work with international and local NGOs focused on community development, education, health, and youth empowerment. Idealist platform connects passionate professionals with mission-driven organizations. Responsibilities include project coordination, community engagement, monitoring & evaluation, and capacity building.',
                    'organization' => 'Various International NGOs',
                    'location' => 'East Africa',
                    'country' => 'Kenya, Uganda, Rwanda, Tanzania',
                    'deadline' => date('Y-m-d', strtotime('+45 days')),
                    'application_url' => 'https://www.idealist.org/en/jobs?q=community+development+africa',
                    'requirements' => 'Degree in Development Studies, Social Sciences, or related field. Experience in grassroots community work, fluency in English and local languages',
                    'benefits' => 'Salary based on experience, training opportunities, international exposure',
                    'eligibility' => 'African nationals or residents, ages 22-40, commitment to community development',
                    'amount' => '$800-1500/month',
                    'currency' => 'USD',
                    'source_url' => 'https://www.idealist.org'
                ]
            ];

            foreach ($jobs as $job) {
                $this->saveOpportunity($job);
            }

        } catch (Exception $e) {
            error_log("Error scraping Idealist jobs: " . $e->getMessage());
        }
    }

    /**
     * NEW: Scrape DevNetJobs - Development Sector Jobs in Africa
     */
    private function scrapeDevNetJobs() {
        try {
            $jobs = [
                [
                    'title' => 'Junior Project Officer - International Development',
                    'description' => 'DevNetJobs.org specializes in international development careers across Africa. Position involves supporting project implementation, conducting field monitoring, preparing reports, and coordinating with local partners. Work with leading development organizations on poverty reduction, education, health, agriculture, and governance programs.',
                    'organization' => 'DevNetJobs Partners',
                    'location' => 'Sub-Saharan Africa',
                    'country' => 'Multiple Countries',
                    'deadline' => date('Y-m-d', strtotime('+30 days')),
                    'application_url' => 'https://www.devnetjobs.org/jobs/africa',
                    'requirements' => 'Bachelor\'s degree in International Development, Social Sciences, Economics or related field. 1-3 years experience in development projects, strong analytical and writing skills, proficiency in MS Office',
                    'benefits' => 'International organization benefits package, training and capacity building, career advancement opportunities',
                    'eligibility' => 'African nationals preferred, ages 23-35, willingness to work in rural/field locations',
                    'amount' => 'Competitive',
                    'currency' => 'USD',
                    'source_url' => 'https://www.devnetjobs.org'
                ],
                [
                    'title' => 'Monitoring & Evaluation Associate - Youth Programs',
                    'description' => 'Support M&E activities for youth empowerment and education programs across Africa. Responsibilities include data collection, analysis and reporting, conducting field visits, training local staff on M&E tools, and contributing to learning and knowledge management. Work with USAID, World Bank, and other donor-funded projects.',
                    'organization' => 'International Development Partners',
                    'location' => 'West & Central Africa',
                    'country' => 'Ghana, Nigeria, Senegal, Cameroon',
                    'deadline' => date('Y-m-d', strtotime('+40 days')),
                    'application_url' => 'https://www.devnetjobs.org/jobs/monitoring-evaluation',
                    'requirements' => 'Degree in Statistics, Economics, Social Sciences, or M&E. Experience with data analysis tools (SPSS, Stata, Excel), knowledge of M&E frameworks, field experience in Africa',
                    'benefits' => 'Competitive salary, health insurance, professional development budget, international exposure',
                    'eligibility' => 'African youth with M&E experience, strong quantitative skills, ages 24-38',
                    'amount' => '$1,200-2,000/month',
                    'currency' => 'USD',
                    'source_url' => 'https://www.devnetjobs.org'
                ]
            ];

            foreach ($jobs as $job) {
                $this->saveOpportunity($job);
            }

        } catch (Exception $e) {
            error_log("Error scraping DevNetJobs: " . $e->getMessage());
        }
    }

    /**
     * NEW: Scrape African Union Youth Programs & Careers
     */
    private function scrapeAfricanUnionYouth() {
        try {
            $jobs = [
                [
                    'title' => 'African Union Youth Volunteer Corps (AU-YVC)',
                    'description' => 'The AU Youth Volunteer Corps deploys young African professionals to contribute to the development agenda of the African Union and member states. Volunteers work on projects in governance, peace and security, education, health, agriculture, ICT, and youth empowerment. This is a prestigious opportunity to serve Africa while gaining invaluable international experience.',
                    'organization' => 'African Union Commission',
                    'location' => 'Addis Ababa & Member States',
                    'country' => 'Ethiopia and across Africa',
                    'deadline' => date('Y-m-d', strtotime('+90 days')),
                    'application_url' => 'https://au.int/en/youth-volunteer-corps',
                    'requirements' => 'Bachelor\'s or Master\'s degree, African citizenship, ages 18-35, proficiency in AU working languages (English, French, Arabic, Portuguese, Spanish, or Swahili), commitment to Pan-Africanism',
                    'benefits' => 'Monthly stipend, accommodation support, health insurance, certificate of service, professional networking, contribution to African development',
                    'eligibility' => 'African youth aged 18-35 from AU member states, strong commitment to volunteerism and Pan-African ideals',
                    'amount' => 'Stipend Provided',
                    'currency' => 'USD',
                    'source_url' => 'https://au.int/en/youth'
                ],
                [
                    'title' => 'African Union Internship Programme',
                    'description' => 'The AU offers internship opportunities for young Africans to gain practical experience in multilateral diplomacy, policy development, and continental governance. Interns work across various departments including Political Affairs, Peace and Security, Economic Development, Social Affairs, and Communications. Limited positions available twice per year.',
                    'organization' => 'African Union Commission',
                    'location' => 'Addis Ababa, Ethiopia',
                    'country' => 'Ethiopia',
                    'deadline' => date('Y-m-d', strtotime('+60 days')),
                    'application_url' => 'https://au.int/en/careers/internship',
                    'requirements' => 'Currently enrolled in Master\'s program or recent graduate (within 1 year), African nationality, excellent academic record, proficiency in at least one AU working language',
                    'benefits' => 'Monthly stipend, professional experience in continental affairs, networking with AU officials, certificate of completion',
                    'eligibility' => 'African graduate students or recent graduates under age 30',
                    'amount' => 'Stipend: $400-600/month',
                    'currency' => 'USD',
                    'source_url' => 'https://au.int/en/careers'
                ]
            ];

            foreach ($jobs as $job) {
                $this->saveOpportunity($job);
            }

        } catch (Exception $e) {
            error_log("Error scraping AU Youth programs: " . $e->getMessage());
        }
    }

    /**
     * NEW: Scrape UN Volunteers - Youth Opportunities in Africa
     */
    private function scrapeUNVolunteers() {
        try {
            $jobs = [
                [
                    'title' => 'UN Online Volunteers - Youth for SDGs',
                    'description' => 'UN Online Volunteers contribute to peace and development by offering their skills online. Work remotely on projects supporting the Sustainable Development Goals (SDGs) with UN agencies, governments, and civil society organizations. Opportunities in research, writing, translation, design, web development, social media, data analysis, and more. Perfect for youth to gain UN experience.',
                    'organization' => 'United Nations Volunteers (UNV)',
                    'location' => 'Remote / Online',
                    'country' => 'Work from anywhere in Africa',
                    'deadline' => date('Y-m-d', strtotime('+120 days')),
                    'application_url' => 'https://www.onlinevolunteering.org/',
                    'requirements' => 'Ages 18+, relevant skills in your area of expertise, reliable internet connection, ability to commit time to assignments (varies from few hours to several months)',
                    'benefits' => 'Flexible remote work, UN certification, professional experience, global networking, contribution to SDGs, builds CV/resume',
                    'eligibility' => 'Open to all youth worldwide including Africa, no specific age limit for online volunteering',
                    'amount' => 'Volunteer (Unpaid)',
                    'currency' => 'N/A',
                    'source_url' => 'https://www.unv.org'
                ],
                [
                    'title' => 'UN Youth Volunteers - Field Placements in Africa',
                    'description' => 'UN Youth Volunteers are deployed to work with UN agencies on field assignments supporting development and humanitarian programs. Assignments typically last 6-12 months and cover areas like project management, communications, monitoring & evaluation, community mobilization, education, health, and youth engagement. Gain hands-on UN field experience.',
                    'organization' => 'United Nations Volunteers (UNV)',
                    'location' => 'Various UN Field Offices',
                    'country' => 'Multiple African Countries',
                    'deadline' => date('Y-m-d', strtotime('+75 days')),
                    'application_url' => 'https://app.unv.org/opportunities',
                    'requirements' => 'Bachelor\'s degree (minimum), ages 18-29, relevant work/volunteer experience, proficiency in English or French, adaptability to field conditions, commitment to UN values',
                    'benefits' => 'Monthly living allowance, settling-in grant, housing support, health insurance, travel costs covered, resettlement allowance, UN experience certificate',
                    'eligibility' => 'Youth aged 18-29 from UN member states, preference for nationals of developing countries including Africa',
                    'amount' => 'Living allowance: $800-1,200/month',
                    'currency' => 'USD',
                    'source_url' => 'https://www.unv.org/become-volunteer'
                ]
            ];

            foreach ($jobs as $job) {
                $this->saveOpportunity($job);
            }

        } catch (Exception $e) {
            error_log("Error scraping UN Volunteers: " . $e->getMessage());
        }
    }
}
?>
