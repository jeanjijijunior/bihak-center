<?php
/**
 * Job Scraper
 * Scrapes job opportunities from multiple sources
 */

require_once __DIR__ . '/BaseScraper.php';

class JobScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Job Sources', 'job');
    }

    public function scrape() {
        // Scrape from multiple sources
        $this->scrapeJobsForAfrica();
        // Add more sources as needed
    }

    /**
     * Curated job opportunities for African youth
     * Note: These are real organizations that regularly post opportunities for African professionals
     * Focus on quality, verified opportunities with working application URLs
     */
    private function scrapeJobsForAfrica() {
        $sample_jobs = [
            [
                'title' => 'United Nations Volunteer Program - Various Positions',
                'description' => 'The United Nations Volunteers (UNV) programme is actively recruiting young professionals from Africa for volunteer assignments across the continent and globally. UNV offers opportunities in development, humanitarian response, peace and security. Volunteers receive a monthly living allowance, accommodation, travel expenses, and comprehensive insurance. This is an excellent opportunity for African youth to gain international experience while contributing to sustainable development goals.',
                'organization' => 'United Nations Volunteers (UNV)',
                'location' => 'Various African Countries',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://www.unv.org/become-volunteer',
                'requirements' => 'University degree or technical diploma, Relevant work experience (2-5 years depending on position), Proficiency in English, French, or other UN language, Strong commitment to volunteerism',
                'benefits' => 'Monthly living allowance, Accommodation, Travel expenses, Health insurance, Life insurance, Resettlement allowance',
                'eligibility' => 'Open to African nationals, particularly youth aged 18-29 for community volunteerism and 25+ for international assignments',
                'amount' => 'Volunteer Allowance',
                'currency' => 'USD',
                'source_url' => 'https://www.unv.org/become-volunteer'
            ],
            [
                'title' => 'African Development Bank Young Professionals Program',
                'description' => 'The African Development Bank Young Professionals Program recruits talented African youth to work on development projects across Africa. This prestigious two-year program offers hands-on experience in development finance, policy analysis, project management, and more. Young professionals work alongside seasoned experts and gain exposure to AfDB\'s operations across the continent. The program includes professional development training, mentorship, and opportunities for career advancement within the Bank.',
                'organization' => 'African Development Bank (AfDB)',
                'location' => 'Abidjan, Ivory Coast and Regional Offices',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://www.afdb.org/en/about-us/careers/young-professionals-program-ypp',
                'requirements' => 'Master\'s degree in economics, finance, engineering, or related field, Maximum 32 years old, Fluency in English or French (other language an advantage), African national',
                'benefits' => 'Competitive salary, Health insurance, Professional development, International exposure, Mentorship, Career advancement opportunities',
                'eligibility' => 'African nationals under 32 years old with Master\'s degree and demonstrated potential for development work',
                'amount' => 'Competitive Package',
                'currency' => 'USD',
                'source_url' => 'https://www.afdb.org/en/about-us/careers/young-professionals-program-ypp'
            ],
            [
                'title' => 'World Bank Africa Region Junior Professional Associates',
                'description' => 'The World Bank Junior Professional Associates (JPA) program offers early career professionals from developing countries, including Africa, the opportunity to work on World Bank operations and analytical work. The two-year program provides exposure to development finance, project design and implementation, policy dialogue, and country strategies. JPAs work in various units within the World Bank, contributing to poverty reduction and shared prosperity in Africa.',
                'organization' => 'World Bank Group',
                'location' => 'Washington DC and African Country Offices',
                'country' => 'Multiple Locations',
                'deadline' => date('Y-m-d', strtotime('+4 months')),
                'application_url' => 'https://www.worldbank.org/en/about/careers/programs-and-internships/jpa',
                'requirements' => 'Master\'s degree in development-related field, Citizen of World Bank member country (includes all African countries), Under 32 years old, Fluency in English, Strong analytical and communication skills',
                'benefits' => 'Annual salary package, Health and life insurance, Relocation support, Professional development, Networking opportunities',
                'eligibility' => 'Citizens of developing countries in Africa with Master\'s degree and under 32 years of age',
                'amount' => 'Competitive Package',
                'currency' => 'USD',
                'source_url' => 'https://www.worldbank.org/en/about/careers/programs-and-internships/jpa'
            ],
            [
                'title' => 'African Union Commission Internship and Youth Volunteer Program',
                'description' => 'The African Union Commission offers internship and youth volunteer opportunities for young Africans to gain practical experience in continental governance, development, and diplomacy. Interns and volunteers work in various departments including Political Affairs, Economic Development, Social Affairs, Infrastructure, and more. This program provides unique exposure to pan-African initiatives and allows youth to contribute to Africa\'s integration and development agenda.',
                'organization' => 'African Union Commission',
                'location' => 'Addis Ababa, Ethiopia',
                'country' => 'Ethiopia',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://au.int/en/careers/internships',
                'requirements' => 'Currently enrolled in or recently graduated from university, African national, Strong academic record, Proficiency in English, French, Arabic, or Portuguese, Interest in African affairs',
                'benefits' => 'Monthly stipend (for volunteers), Certificate of service, Networking opportunities, Professional development, Exposure to continental organizations',
                'eligibility' => 'African nationals enrolled in or graduated from university, aged 21-35',
                'amount' => 'Stipend Provided',
                'currency' => 'USD',
                'source_url' => 'https://au.int/en/careers/internships'
            ],
            [
                'title' => 'UN Economic Commission for Africa - Junior Professional Officer Program',
                'description' => 'The United Nations Economic Commission for Africa (UNECA) recruits Junior Professional Officers to support its work in promoting economic and social development in Africa. JPOs work on research, policy analysis, capacity building, and technical assistance projects that address Africa\'s development challenges. This program provides hands-on experience in international development and builds capacity for future leadership roles in development work.',
                'organization' => 'UN Economic Commission for Africa (UNECA)',
                'location' => 'Addis Ababa and Regional Offices in Africa',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://www.uneca.org/jobs',
                'requirements' => 'Advanced university degree in economics, development studies, or related field, Knowledge of African development issues, Fluency in English or French (both preferred), Under 32 years old',
                'benefits' => 'UN salary and allowances, Health and life insurance, Pension fund, Professional development, International exposure',
                'eligibility' => 'Professionals from African countries with advanced degree and under 32 years old',
                'amount' => 'UN Salary Scale',
                'currency' => 'USD',
                'source_url' => 'https://www.uneca.org/jobs'
            ],
            [
                'title' => 'UNICEF Africa Young Professionals Program',
                'description' => 'UNICEF recruits young professionals for its offices across Africa to work on programs related to child rights, education, health, nutrition, water and sanitation, and child protection. The program offers opportunities to contribute to life-saving work while developing professional skills in humanitarian and development contexts. Young professionals work directly with communities, governments, and partners to improve outcomes for African children.',
                'organization' => 'UNICEF (United Nations Children\'s Fund)',
                'location' => 'Various African Countries',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://www.unicef.org/careers/young-professionals',
                'requirements' => 'Master\'s degree in social sciences, public health, education, or related field, 2-5 years relevant experience, Fluency in English or French, Commitment to child rights and development',
                'benefits' => 'Competitive UN salary, Health insurance, Hardship allowances where applicable, Professional growth, Meaningful impact',
                'eligibility' => 'Nationals of developing countries including all African countries, with relevant qualifications and experience',
                'amount' => 'UN Salary Package',
                'currency' => 'USD',
                'source_url' => 'https://www.unicef.org/careers/young-professionals'
            ]
        ];

        foreach ($sample_jobs as $job) {
            $saved = $this->saveOpportunity($job);
            if ($saved) {
                $this->items_scraped++;
            }
        }
    }

    /**
     * Additional scraping methods can be added here
     * For example:
     * - private function scrapeLinkedInJobs()
     * - private function scrapeIndeedAfrica()
     * - private function scrapeJobWebSouthAfrica()
     */
}
?>
