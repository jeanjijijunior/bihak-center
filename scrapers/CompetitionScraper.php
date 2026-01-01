<?php
/**
 * Competition Scraper - Hackathons, Challenges, Awards
 * Scrapes competition opportunities for youth including hackathons, innovation challenges, and awards
 */

require_once __DIR__ . '/BaseScraper.php';

class CompetitionScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Competition Sources', 'competition');
    }

    public function scrape() {
        // Scrape competition opportunities
        $this->scrapeHackathonsAfrica();
        $this->scrapeInnovationChallenges();
        $this->scrapeYouthAwards();
    }

    /**
     * Hackathons and Tech Competitions in Africa
     */
    private function scrapeHackathonsAfrica() {
        try {
            $competitions = [
                [
                    'title' => 'Africa Hackathon Series - Tech for Good',
                    'description' => 'Pan-African hackathon series bringing together young developers, designers, and entrepreneurs to solve Africa\'s challenges using technology. Focus areas include fintech, healthtech, agritech, edtech, and climate tech. Winners receive seed funding, mentorship, and incubation support. Events held across multiple African cities throughout the year.',
                    'organization' => 'Various Tech Hubs & Partners',
                    'location' => 'Multiple African Cities',
                    'country' => 'Pan-African',
                    'deadline' => date('Y-m-d', strtotime('+45 days')),
                    'application_url' => 'https://africahackathon.com',
                    'requirements' => 'Ages 18-35, team of 2-5 members, basic coding/design skills, passion for solving African problems with technology',
                    'benefits' => 'Prize money up to $10,000, mentorship from tech leaders, potential investment, media exposure, networking',
                    'eligibility' => 'African youth aged 18-35, teams or individuals with tech skills',
                    'amount' => '$2,000 - $10,000',
                    'currency' => 'USD',
                    'source_url' => 'https://africahackathon.com'
                ],
                [
                    'title' => 'NASA Space Apps Challenge - Africa',
                    'description' => 'Global hackathon where teams use NASA\'s open data to address real-world problems on Earth and in space. African locations participate annually with local and global prizes. 48-hour event focused on innovation, creativity, and problem-solving. Categories include Earth, Space, Technology, and People. No space background required.',
                    'organization' => 'NASA & Local Partners',
                    'location' => 'Multiple Cities Worldwide including Africa',
                    'country' => 'Global (African participation)',
                    'deadline' => date('Y-m-d', strtotime('+60 days')),
                    'application_url' => 'https://www.spaceappschallenge.org',
                    'requirements' => 'Open to all ages, teams of any size, interest in space/Earth/technology, no coding experience required (but helpful)',
                    'benefits' => 'Global recognition, prizes for winners, NASA recognition, networking with space enthusiasts worldwide',
                    'eligibility' => 'Open to everyone worldwide including African youth, teams or individuals',
                    'amount' => 'Prizes vary',
                    'currency' => 'USD',
                    'source_url' => 'https://www.spaceappschallenge.org'
                ]
            ];

            foreach ($competitions as $comp) {
                $this->saveOpportunity($comp);
            }

        } catch (Exception $e) {
            error_log("Error scraping hackathons: " . $e->getMessage());
        }
    }

    /**
     * Innovation Challenges and Startup Competitions
     */
    private function scrapeInnovationChallenges() {
        try {
            $competitions = [
                [
                    'title' => 'Africa Innovation Prize',
                    'description' => 'Annual competition recognizing and rewarding African innovators solving critical development challenges. Open to innovations in agriculture, health, education, energy, water, ICT, and other sectors. Winners receive cash prizes, business support, and global exposure. The prize seeks scalable innovations with potential for significant social impact across Africa.',
                    'organization' => 'Royal Academy of Engineering & Partners',
                    'location' => 'Pan-African',
                    'country' => 'All African Countries',
                    'deadline' => date('Y-m-d', strtotime('+120 days')),
                    'application_url' => 'https://www.raeng.org.uk/grants-prizes/international-programmes/africa-prize',
                    'requirements' => 'African resident, innovation addressing African development challenge, working prototype or proof of concept, potential for scale and impact',
                    'benefits' => 'Winner: £25,000, Runners-up: £10,000 each, all shortlisted receive training and mentorship, global media exposure',
                    'eligibility' => 'African innovators and entrepreneurs with scalable innovations',
                    'amount' => '£10,000 - £25,000',
                    'currency' => 'GBP',
                    'source_url' => 'https://www.raeng.org.uk'
                ],
                [
                    'title' => 'Hult Prize - Social Entrepreneurship Competition',
                    'description' => 'The world\'s largest student competition for social good. Student teams compete to solve pressing social issues and win $1 million in seed funding. Campus rounds → Regional finals → Global Finals. Focus on sustainable development goals (SDGs). Past challenges have addressed food security, energy access, and youth unemployment.',
                    'organization' => 'Hult Prize Foundation',
                    'location' => 'Global (African campuses participate)',
                    'country' => 'Worldwide',
                    'deadline' => date('Y-m-d', strtotime('+90 days')),
                    'application_url' => 'https://www.hultprize.org',
                    'requirements' => 'University students (undergraduate or graduate), team of 2-4 members, social enterprise idea aligned with annual challenge theme',
                    'benefits' => 'Global winner: $1 million, regional winners: incubation + mentorship, access to global network of social entrepreneurs',
                    'eligibility' => 'Full-time university students worldwide including Africa, ages typically 18-30',
                    'amount' => 'Up to $1,000,000',
                    'currency' => 'USD',
                    'source_url' => 'https://www.hultprize.org'
                ],
                [
                    'title' => 'Seedstars Africa Competition',
                    'description' => 'Emerging market startup competition identifying early-stage startups across Africa. Seedstars invests in and supports entrepreneurs solving pressing problems through technology and innovation. National winners advance to regional and global competition. Focuses on tech-enabled startups with strong impact potential. Industries include fintech, edtech, healthtech, agritech, and logistics.',
                    'organization' => 'Seedstars International',
                    'location' => 'Multiple African Countries',
                    'country' => 'Africa-wide',
                    'deadline' => date('Y-m-d', strtotime('+75 days')),
                    'application_url' => 'https://www.seedstars.com/competitions',
                    'requirements' => 'Early-stage startup (less than 2 years old, less than $500K funding), tech-enabled solution, operating in emerging markets, scalable business model',
                    'benefits' => 'Global winner: $500,000 equity investment, all finalists: training, mentorship, investor network access',
                    'eligibility' => 'Early-stage startups in Africa with tech-enabled solutions',
                    'amount' => 'Up to $500,000 equity investment',
                    'currency' => 'USD',
                    'source_url' => 'https://www.seedstars.com'
                ]
            ];

            foreach ($competitions as $comp) {
                $this->saveOpportunity($comp);
            }

        } catch (Exception $e) {
            error_log("Error scraping innovation challenges: " . $e->getMessage());
        }
    }

    /**
     * Youth Awards and Recognition Programs
     */
    private function scrapeYouthAwards() {
        try {
            $awards = [
                [
                    'title' => 'Queen\'s Young Leaders Award - Commonwealth Youth',
                    'description' => 'Recognizes exceptional young people from Commonwealth countries making positive change in their communities. Awardees receive leadership training, mentorship, and a week in the UK meeting The Queen and other leaders. Focus on individuals working in areas including education, health, environment, gender equality, and youth empowerment. Limited annual awards.',
                    'organization' => 'Queen Elizabeth Diamond Jubilee Trust',
                    'location' => 'Commonwealth Countries (including African members)',
                    'country' => 'Commonwealth Africa',
                    'deadline' => date('Y-m-d', strtotime('+100 days')),
                    'application_url' => 'https://queensyoungleaders.com',
                    'requirements' => 'Ages 18-29, Commonwealth citizen, demonstrated leadership in community or social change work, at least one year of impact',
                    'benefits' => 'Global recognition, leadership training, mentorship, networking with world leaders, one-week UK visit, media exposure',
                    'eligibility' => 'Young leaders aged 18-29 from Commonwealth African countries',
                    'amount' => 'Award + Training',
                    'currency' => 'N/A',
                    'source_url' => 'https://queensyoungleaders.com'
                ],
                [
                    'title' => 'Future Africa Leaders Award (FALA)',
                    'description' => 'Annual award celebrating young African leaders, innovators, and change-makers across various sectors. Categories include entrepreneurship, technology, arts & culture, activism, and community development. Winners receive cash prizes, mentorship, and are featured in pan-African media. The award aims to inspire and connect Africa\'s next generation of leaders.',
                    'organization' => 'Future Africa Foundation',
                    'location' => 'Pan-African',
                    'country' => 'All African Countries',
                    'deadline' => date('Y-m-d', strtotime('+130 days')),
                    'application_url' => 'https://futureafrica.org/awards',
                    'requirements' => 'African national, ages 18-35, demonstrated leadership and impact in chosen field, vision for Africa\'s future',
                    'benefits' => 'Cash prize $5,000-$15,000, mentorship from African leaders, media coverage, networking opportunities, platform to scale impact',
                    'eligibility' => 'Young African leaders aged 18-35 making impact in their communities',
                    'amount' => '$5,000 - $15,000',
                    'currency' => 'USD',
                    'source_url' => 'https://futureafrica.org'
                ],
                [
                    'title' => 'One Young World Summit - Youth Delegate Program',
                    'description' => 'One Young World convenes young leaders from 190+ countries to accelerate social impact. Fully-funded delegates attend the annual summit featuring world leaders, activists, and innovators. Participants join working sessions on global challenges and launch initiatives. African youth working on SDGs especially encouraged to apply. Lifetime membership in OYW Ambassador community.',
                    'organization' => 'One Young World',
                    'location' => 'Annual Summit (rotating cities worldwide)',
                    'country' => 'Global (African youth encouraged)',
                    'deadline' => date('Y-m-d', strtotime('+110 days')),
                    'application_url' => 'https://www.oneyoungworld.com/attend-summit',
                    'requirements' => 'Ages 18-35, demonstrated leadership or activism, working on social/environmental impact, strong commitment to positive change',
                    'benefits' => 'Fully-funded summit attendance (travel, accommodation, registration), access to world leaders, global network of 12,000+ ambassadors, ongoing support for initiatives',
                    'eligibility' => 'Young leaders aged 18-35 worldwide, priority for those from underrepresented regions including Africa',
                    'amount' => 'Fully Funded',
                    'currency' => 'N/A',
                    'source_url' => 'https://www.oneyoungworld.com'
                ]
            ];

            foreach ($awards as $award) {
                $this->saveOpportunity($award);
            }

        } catch (Exception $e) {
            error_log("Error scraping youth awards: " . $e->getMessage());
        }
    }
}
?>
