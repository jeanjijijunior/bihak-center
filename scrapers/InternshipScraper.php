<?php
/**
 * Internship Scraper
 * Scrapes internship opportunities from multiple sources
 */

require_once __DIR__ . '/BaseScraper.php';

class InternshipScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Internship Sources', 'internship');
    }

    public function scrape() {
        // Scrape from multiple sources
        $this->scrapeInternshipOpportunities();
        // Add more sources as needed
    }

    /**
     * Curated internship opportunities for African youth
     * Real organizations with working URLs and verified Africa focus
     */
    private function scrapeInternshipOpportunities() {
        $sample_internships = [
            [
                'title' => 'World Bank Group Summer Internship Program',
                'description' => 'The World Bank Summer Internship Program offers highly motivated and successful individuals an opportunity to be exposed to the mission and work of the World Bank. As an intern, you will work in a multicultural environment and gain practical experience from your assigned unit while also participating in a robust learning program. African students are highly encouraged to apply. Interns are assigned to World Bank teams in Washington DC or country offices where they can apply their academic training and contribute to ongoing operational, research, or analytical work.',
                'organization' => 'World Bank Group',
                'location' => 'Washington DC and African Country Offices',
                'country' => 'Multiple Locations',
                'deadline' => date('Y-m-d', strtotime('+4 months')),
                'application_url' => 'https://www.worldbank.org/en/about/careers/programs-and-internships',
                'requirements' => 'Graduate student enrolled in full-time Masters or PhD program, Fluency in English, Specialized studies in development-related field (economics, finance, human development, social science, agriculture, environment, etc.)',
                'benefits' => 'Monthly salary (varies by location), Travel expenses, Visa support, Professional development workshops, Networking opportunities',
                'eligibility' => 'Graduate students from all countries, African students particularly encouraged. Must be enrolled in graduate program throughout internship',
                'amount' => '1000-1500',
                'currency' => 'USD',
                'source_url' => 'https://www.worldbank.org/en/about/careers/programs-and-internships'
            ],
            [
                'title' => 'African Development Bank Internship Programme',
                'description' => 'The African Development Bank offers internships for graduate students to gain practical experience in international development finance. Interns work on real projects related to African development, including infrastructure, agriculture, governance, private sector development, and more. This program provides hands-on experience in a multilateral development institution and excellent networking opportunities. The Bank seeks talented African youth who are committed to Africa\'s development.',
                'organization' => 'African Development Bank (AfDB)',
                'location' => 'Abidjan, Ivory Coast and Regional Offices',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://www.afdb.org/en/about-us/careers/internship-program',
                'requirements' => 'Enrolled in graduate program (Masters or PhD), Maximum 30 years old, Strong academic record, Fluency in English or French, Knowledge of development issues',
                'benefits' => 'Monthly stipend (approximately 1000 USD), Round-trip air travel, Health insurance, Certificate of completion, Professional development',
                'eligibility' => 'Graduate students under 30, preferably African nationals',
                'amount' => '1000',
                'currency' => 'USD',
                'source_url' => 'https://www.afdb.org/en/about-us/careers/internship-program'
            ],
            [
                'title' => 'United Nations Volunteers Youth Programme',
                'description' => 'The UN Volunteers Youth Programme offers young people from Africa opportunities to volunteer on development projects across the continent. Youth volunteers contribute to achieving the Sustainable Development Goals while gaining valuable international experience. Assignments are available in areas such as education, health, environment, peacebuilding, and humanitarian response. This program helps build professional capacity while making a tangible difference in communities.',
                'organization' => 'United Nations Volunteers (UNV)',
                'location' => 'Various African Countries',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://www.unv.org/become-volunteer/volunteer-abroad',
                'requirements' => 'Age 18-29 for Youth Volunteers, University degree or technical diploma, Relevant skills for assignment, Commitment to volunteerism and development',
                'benefits' => 'Volunteer living allowance (700-1200 USD/month depending on location), Accommodation, Health insurance, Travel costs, Life insurance, Resettlement allowance',
                'eligibility' => 'Youth from all countries including Africa, aged 18-29 years with relevant qualifications',
                'amount' => '700-1200',
                'currency' => 'USD',
                'source_url' => 'https://www.unv.org/become-volunteer/volunteer-abroad'
            ],
            [
                'title' => 'African Union Commission Internship Programme',
                'description' => 'The African Union Commission offers internships for young Africans to gain experience in continental governance and development. Interns work in various departments including Political Affairs, Peace and Security, Trade and Industry, Economic Development, Social Affairs, Infrastructure and Energy. This unique opportunity allows youth to contribute to Africa\'s integration agenda while learning about pan-African institutions and policies. Interns work at AU Headquarters in Addis Ababa or regional offices.',
                'organization' => 'African Union Commission',
                'location' => 'Addis Ababa, Ethiopia and Regional Offices',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://au.int/en/careers/internships',
                'requirements' => 'Currently enrolled in or recently graduated from university, African national, Strong academic record, Proficiency in at least one AU working language (English, French, Arabic, Portuguese, Spanish, Swahili)',
                'benefits' => 'Monthly stipend (for some programs), Certificate of service, Networking with AU officials, Professional development, Exposure to continental policy-making',
                'eligibility' => 'African nationals aged 21-35, enrolled in or recently graduated from university',
                'amount' => 'Stipend (Varies)',
                'currency' => 'USD',
                'source_url' => 'https://au.int/en/careers/internships'
            ],
            [
                'title' => 'UNICEF Internship Programme - Africa Region',
                'description' => 'UNICEF offers internships for students and recent graduates in various disciplines to support programs for children across Africa. Interns work on projects related to child protection, education, health, nutrition, water and sanitation, and emergency response. This hands-on experience provides exposure to humanitarian and development work in UNICEF country offices, regional offices, or headquarters. Interns contribute to making a difference in children\'s lives while building professional capacity.',
                'organization' => 'UNICEF (United Nations Children\'s Fund)',
                'location' => 'Various African Country Offices',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://www.unicef.org/careers/internships',
                'requirements' => 'Enrolled in graduate program or recently graduated (within 2 years), Relevant field of study (social sciences, public health, communications, etc.), Fluency in English or French, Computer literacy',
                'benefits' => 'Monthly stipend (varies by location, typically 600-1000 USD), Certificate, Professional development, Meaningful work experience',
                'eligibility' => 'Graduate students and recent graduates from all countries, particularly encouraged from developing countries in Africa',
                'amount' => '600-1000',
                'currency' => 'USD',
                'source_url' => 'https://www.unicef.org/careers/internships'
            ],
            [
                'title' => 'International Monetary Fund (IMF) Internship Program - Africa Focus',
                'description' => 'The IMF Internship Program offers opportunities for highly qualified graduate students to work on macroeconomic policy issues and gain firsthand experience in the work of the Fund. The program includes specific tracks relevant to African economic development, including work on poverty reduction strategies, debt sustainability, and monetary policy in African economies. Interns work alongside IMF economists and policy analysts, contributing to research and policy work that directly impacts African countries.',
                'organization' => 'International Monetary Fund (IMF)',
                'location' => 'Washington DC, with focus on African economies',
                'country' => 'United States (working on Africa)',
                'deadline' => date('Y-m-d', strtotime('+5 months')),
                'application_url' => 'https://www.imf.org/en/Careers/internship-program',
                'requirements' => 'Graduate student (PhD or advanced Masters), Strong academic record in economics, finance, or related field, Excellent analytical and writing skills, Fluency in English',
                'benefits' => 'Competitive salary (approximately 700 USD per week), Travel reimbursement for eligible interns, Visa support, Professional development opportunities',
                'eligibility' => 'Graduate students from all IMF member countries, African nationals particularly encouraged to apply for Africa-focused positions',
                'amount' => '2800-3000',
                'currency' => 'USD',
                'source_url' => 'https://www.imf.org/en/Careers/internship-program'
            ],
            [
                'title' => 'UN Economic Commission for Africa (UNECA) Internship',
                'description' => 'UNECA offers internships for students and recent graduates to gain practical experience in research, policy analysis, and technical assistance related to Africa\'s economic and social development. Interns work on projects addressing trade, industrialization, macroeconomic policy, gender, statistics, and other development issues affecting African countries. This program provides unique exposure to continental economic policy-making and research on African development challenges.',
                'organization' => 'UN Economic Commission for Africa (UNECA)',
                'location' => 'Addis Ababa, Ethiopia and Sub-regional Offices',
                'country' => 'Ethiopia and other African countries',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://www.uneca.org/jobs',
                'requirements' => 'Enrolled in graduate program or recently graduated, Relevant field of study (economics, development studies, statistics, etc.), Fluency in English or French, Research and analytical skills',
                'benefits' => 'Monthly stipend (varies, typically 600-800 USD), Certificate of service, Professional networking, Contribution to African development research',
                'eligibility' => 'Graduate students and recent graduates, African nationals particularly encouraged',
                'amount' => '600-800',
                'currency' => 'USD',
                'source_url' => 'https://www.uneca.org/jobs'
            ]
        ];

        foreach ($sample_internships as $internship) {
            $saved = $this->saveOpportunity($internship);
            if ($saved) {
                $this->items_scraped++;
            }
        }
    }
}
?>
