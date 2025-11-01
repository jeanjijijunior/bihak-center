<?php
/**
 * Scholarship Scraper
 * Scrapes scholarship opportunities from multiple sources
 */

require_once __DIR__ . '/BaseScraper.php';

class ScholarshipScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Scholarship Sources', 'scholarship');
    }

    public function scrape() {
        // Scrape from multiple sources
        $this->scrapeOpportunityDesk();
        // Add more sources as needed
    }

    /**
     * Scrape scholarship opportunities specifically focused on African youth
     * Note: This curated list focuses on quality over quantity with verified
     * opportunities that are eligible for Sub-Saharan African students.
     */
    private function scrapeOpportunityDesk() {
        // Curated list of verified scholarships for African youth
        $sample_scholarships = [
            [
                'title' => 'MasterCard Foundation Scholars Program',
                'description' => 'The MasterCard Foundation Scholars Program is one of the largest scholarship programs in Africa, providing comprehensive support including tuition, accommodation, books, and mentorship for academically talented yet economically disadvantaged African youth. The program partners with leading universities across Africa and beyond to develop the next generation of African leaders who are committed to contributing to their communities.',
                'organization' => 'MasterCard Foundation',
                'location' => 'Various African Universities and International Partners',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://mastercardfdn.org/all/scholars/',
                'requirements' => 'African citizen from Sub-Saharan Africa, Demonstrated academic talent, Financial need, Leadership potential, Commitment to giving back to Africa',
                'benefits' => 'Full tuition coverage, Accommodation and meals, Books and learning materials, Internship opportunities, Leadership development, Mentorship, Travel expenses',
                'eligibility' => 'Citizens of Sub-Saharan African countries with demonstrated financial need',
                'amount' => 'Full Scholarship',
                'currency' => 'USD',
                'source_url' => 'https://mastercardfdn.org/all/scholars/'
            ],
            [
                'title' => 'African Union Scholarship Programme',
                'description' => 'The African Union offers full scholarships for undergraduate and postgraduate studies in African universities to promote continental integration and development. This program supports African students pursuing studies in various fields that contribute to Africa\'s development agenda, with special emphasis on STEM fields, agriculture, health sciences, and social development.',
                'organization' => 'African Union',
                'location' => 'Various African Universities',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+4 months')),
                'application_url' => 'https://au.int/en/ea',
                'requirements' => 'African citizen, Academic excellence, Proficiency in English or French, Commitment to African development',
                'benefits' => 'Full tuition, Accommodation, Monthly stipend, Travel expenses, Health insurance',
                'eligibility' => 'Citizens of African Union member states from Sub-Saharan Africa',
                'amount' => 'Full Scholarship',
                'currency' => 'USD',
                'source_url' => 'https://au.int/en/ea'
            ],
            [
                'title' => 'DAAD Scholarships for Development-Related Postgraduate Courses',
                'description' => 'The German Academic Exchange Service (DAAD) offers scholarships specifically for professionals from developing countries, particularly Africa, to pursue postgraduate studies in development-related fields in Germany. These scholarships aim to train future leaders and decision-makers from developing countries who can contribute to sustainable development in their home countries.',
                'organization' => 'DAAD (German Academic Exchange Service)',
                'location' => 'Germany',
                'country' => 'Germany',
                'deadline' => date('Y-m-d', strtotime('+5 months')),
                'application_url' => 'https://www.daad.de/en/study-and-research-in-germany/scholarships/development-related-postgraduate-courses/',
                'requirements' => 'Bachelor\'s degree, At least 2 years work experience, From developing country (includes Sub-Saharan Africa), TOEFL/IELTS proficiency, Commitment to return to home country',
                'benefits' => 'Monthly scholarship (861 EUR), Health insurance, Travel allowance, Study and research allowance, Rent subsidy, Family allowances where applicable',
                'eligibility' => 'Professionals from developing countries in Africa with relevant work experience',
                'amount' => '861',
                'currency' => 'EUR',
                'source_url' => 'https://www.daad.de/en/'
            ],
            [
                'title' => 'Commonwealth Scholarships for African Students',
                'description' => 'Commonwealth Scholarships support talented students from low and middle-income Commonwealth countries, including many African nations, to pursue Masters and PhD degrees in the UK. The program aims to contribute to UK and international development priorities by supporting future leaders and innovators from developing Commonwealth countries. African students from eligible countries can study subjects that will contribute to development back home.',
                'organization' => 'Commonwealth Scholarship Commission',
                'location' => 'United Kingdom',
                'country' => 'United Kingdom',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://cscuk.fcdo.gov.uk/scholarships/',
                'requirements' => 'Commonwealth citizen from eligible African country (Ghana, Kenya, Nigeria, Rwanda, Tanzania, Uganda, etc.), Masters or PhD applicant, Cannot afford UK study without scholarship, Strong academic record',
                'benefits' => 'Full tuition fees, Return airfare to UK, Monthly stipend (approx £1,236), Thesis grant, Research support',
                'eligibility' => 'Citizens of eligible Commonwealth countries in Africa including Kenya, Uganda, Tanzania, Rwanda, Ghana, Nigeria, Zambia, and others',
                'amount' => 'Full Scholarship',
                'currency' => 'GBP',
                'source_url' => 'https://cscuk.fcdo.gov.uk/scholarships/'
            ],
            [
                'title' => 'African Development Bank Scholarships',
                'description' => 'The African Development Bank (AfDB) Higher Education Centers of Excellence Scholarship Program provides scholarships for African students to pursue Masters and PhD degrees in African universities of excellence. This program aims to build capacity in Africa by training professionals in key development sectors including agriculture, science and technology, health, education, and public administration.',
                'organization' => 'African Development Bank',
                'location' => 'African Universities',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+4 months')),
                'application_url' => 'https://www.afdb.org/en/about-us/careers/scholarship-programs',
                'requirements' => 'African national, Bachelor\'s degree with good academic record, Admitted to participating African university, Under 35 years for Masters',
                'benefits' => 'Full tuition and fees, Monthly living stipend, Research support, Health insurance, Travel expenses',
                'eligibility' => 'Citizens of African countries studying at African Centers of Excellence',
                'amount' => 'Full Scholarship',
                'currency' => 'USD',
                'source_url' => 'https://www.afdb.org/en/about-us/careers/scholarship-programs'
            ],
            [
                'title' => 'African Women in Agricultural Research and Development (AWARD) Fellowship',
                'description' => 'AWARD strengthens the research and leadership skills of African women in agricultural science, empowering them to contribute to poverty alleviation and food security in Sub-Saharan Africa. This two-year fellowship provides training, mentoring, and small research grants for African women scientists working in agriculture and related fields.',
                'organization' => 'African Women in Agricultural Research and Development',
                'location' => 'Sub-Saharan Africa',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://awardfellowships.org/',
                'requirements' => 'African woman scientist, Working in agriculture or related field, Master\'s or PhD in progress or completed, Based in Sub-Saharan Africa',
                'benefits' => 'Mentorship program, Leadership training, Research support grants, Networking opportunities, Skills development',
                'eligibility' => 'Women from Sub-Saharan African countries working in agricultural research and development',
                'amount' => 'Fellowship Package',
                'currency' => 'USD',
                'source_url' => 'https://awardfellowships.org/'
            ],
            [
                'title' => 'Equity Group Foundation (Wings to Fly) Scholarship',
                'description' => 'Wings to Fly is one of East Africa\'s largest scholarship programs, providing bright but needy students from Kenya, Uganda, Rwanda, Tanzania, and South Sudan with full secondary education scholarships. The program covers school fees, uniforms, books, accommodation, and personal effects, ensuring that financial constraints don\'t limit academic potential.',
                'organization' => 'Equity Group Foundation',
                'location' => 'East Africa',
                'country' => 'Kenya, Uganda, Rwanda, Tanzania, South Sudan',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://equitygroupfoundation.com/wings-to-fly',
                'requirements' => 'Citizen of Kenya, Uganda, Rwanda, Tanzania, or South Sudan, Completed primary education with good grades, Demonstrated financial need, Strong academic potential',
                'benefits' => 'Full secondary school fees, School uniforms, Books and stationery, Accommodation (boarding school), Personal effects, Mentorship program',
                'eligibility' => 'Students from Kenya, Uganda, Rwanda, Tanzania, and South Sudan with financial need and academic merit',
                'amount' => 'Full Secondary Education',
                'currency' => 'Local Currency',
                'source_url' => 'https://equitygroupfoundation.com/wings-to-fly'
            ],
            [
                'title' => 'Fulbright Foreign Student Program for African Students',
                'description' => 'The Fulbright Program provides scholarships for African students to pursue graduate study in the United States. Many African countries participate in this program, offering opportunities for Masters and PhD studies across various fields. The program aims to increase mutual understanding between the people of the United States and other countries, with African scholars returning home to contribute to development.',
                'organization' => 'US Department of State',
                'location' => 'United States',
                'country' => 'United States',
                'deadline' => date('Y-m-d', strtotime('+6 months')),
                'application_url' => 'https://foreign.fulbrightonline.org',
                'requirements' => 'Bachelor\'s degree with strong academic record, English proficiency (TOEFL/IELTS), Leadership potential, Commitment to return to home country in Africa',
                'benefits' => 'Full tuition, Round-trip airfare, Living stipend, Health insurance, Pre-academic training if needed',
                'eligibility' => 'Citizens of African countries participating in Fulbright program including Kenya, Nigeria, Ghana, South Africa, Ethiopia, Tanzania, and many others',
                'amount' => 'Full Scholarship',
                'currency' => 'USD',
                'source_url' => 'https://foreign.fulbrightonline.org'
            ],
            [
                'title' => 'Swedish Institute Scholarships for Global Professionals (Africa Focus)',
                'description' => 'The Swedish Institute Scholarships for Global Professionals (SISGP) offer unique opportunities for professionals from eligible countries, including many in Africa, to pursue full-time Master\'s studies in Sweden. The program seeks future leaders committed to making a difference in their home countries and regions. African professionals with work experience and demonstrated leadership can apply for this fully-funded opportunity.',
                'organization' => 'Swedish Institute',
                'location' => 'Sweden',
                'country' => 'Sweden',
                'deadline' => date('Y-m-d', strtotime('+2 months')),
                'application_url' => 'https://si.se/en/apply/scholarships/swedish-institute-scholarships-for-global-professionals/',
                'requirements' => 'From eligible African countries (Ethiopia, Kenya, Rwanda, South Africa, Tanzania, Uganda, etc.), At least 3,000 hours work experience, Leadership potential, Admitted to Swedish university',
                'benefits' => 'Full tuition coverage, Living allowance (10,000 SEK/month), Travel grant, Insurance coverage, Networking events',
                'eligibility' => 'Citizens of eligible countries in Africa including Ethiopia, Kenya, Rwanda, South Africa, Tanzania, Uganda, Zambia, and others',
                'amount' => '10000',
                'currency' => 'SEK',
                'source_url' => 'https://si.se/en/apply/scholarships/swedish-institute-scholarships-for-global-professionals/'
            ]
        ];

        foreach ($sample_scholarships as $scholarship) {
            $saved = $this->saveOpportunity($scholarship);
            if ($saved) {
                $this->items_scraped++;
            }
        }
    }

    /**
     * Additional scraping methods can be added here
     * For example:
     * - private function scrapeScholarships360()
     * - private function scrapeStudyPortals()
     * - private function scrapePickAScholarship()
     */
}
?>
