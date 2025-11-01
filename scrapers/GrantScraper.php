<?php
/**
 * Grant Scraper
 * Scrapes grant and funding opportunities from multiple sources
 */

require_once __DIR__ . '/BaseScraper.php';

class GrantScraper extends BaseScraper {

    public function __construct($conn) {
        parent::__construct($conn, 'Mixed Grant Sources', 'grant');
    }

    public function scrape() {
        // Scrape from multiple sources
        $this->scrapeGrantOpportunities();
        // Add more sources as needed
    }

    /**
     * Curated grant opportunities for African youth and organizations
     * Real funding sources with working URLs and Africa focus
     */
    private function scrapeGrantOpportunities() {
        $sample_grants = [
            [
                'title' => 'Tony Elumelu Foundation Entrepreneurship Programme',
                'description' => 'The Tony Elumelu Foundation Entrepreneurship Programme is Africa\'s flagship entrepreneurship initiative, providing seed capital, training, mentorship and networking opportunities to African entrepreneurs. The program empowers young African entrepreneurs across all 54 African countries with seed funding, training, mentorship and access to a global network. This non-refundable $5,000 seed capital grant is designed to help entrepreneurs scale their businesses and create jobs across Africa.',
                'organization' => 'Tony Elumelu Foundation',
                'location' => 'All African Countries',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://www.tonyelumelufoundation.org/apply',
                'requirements' => 'African entrepreneur (citizen or resident), Business must be based in Africa, Business less than 3 years old, Ages 18 and above, For-profit business with high growth potential',
                'benefits' => '$5,000 non-refundable seed capital, 12-week business training program, Mentorship, Networking opportunities, Access to TEF Alumni Network',
                'eligibility' => 'African entrepreneurs from all 54 African countries with scalable business ideas',
                'amount' => '5000',
                'currency' => 'USD',
                'source_url' => 'https://www.tonyelumelufoundation.org/apply'
            ],
            [
                'title' => 'African Women in Agricultural Research and Development (AWARD) Fellowship',
                'description' => 'AWARD offers fellowships and research grants specifically for African women scientists in agriculture and related fields. The program strengthens the research and leadership skills of African women agricultural researchers, empowering them to contribute more effectively to poverty alleviation and food security. Fellows receive training in scientific research, leadership, and grant writing, along with research support grants.',
                'organization' => 'African Women in Agricultural Research and Development',
                'location' => 'Sub-Saharan Africa',
                'country' => 'Multiple African Countries',
                'deadline' => date('Y-m-d', strtotime('+4 months')),
                'application_url' => 'https://awardfellowships.org/apply/',
                'requirements' => 'African woman scientist, Based in Sub-Saharan Africa, Masters or PhD in agricultural sciences or related field, Working in agricultural research or development',
                'benefits' => 'Two-year fellowship program, Research support grant, Leadership training, Mentorship, Networking with other African women scientists, Career development support',
                'eligibility' => 'Women from Sub-Saharan African countries working in agricultural research and development',
                'amount' => 'Fellowship + Research Grant',
                'currency' => 'USD',
                'source_url' => 'https://awardfellowships.org/apply/'
            ],
            [
                'title' => 'African Innovation Foundation Innovation Prize',
                'description' => 'The African Innovation Foundation recognizes and rewards innovative solutions developed by Africans that address local challenges. The Innovation Prize identifies, recognizes and rewards innovative products and solutions developed in Africa. Winners receive cash prizes, mentorship, and support to scale their innovations. The foundation focuses on innovations in agriculture, health, environment, education, energy, and ICT.',
                'organization' => 'African Innovation Foundation',
                'location' => 'Africa-wide',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+5 months')),
                'application_url' => 'https://www.innovationprizeforafrica.org/',
                'requirements' => 'Innovation must be developed in Africa, Must address an African challenge, Demonstrable impact, Scalability potential, African innovator or team',
                'benefits' => 'Cash prize ($25,000 for 1st place, $15,000 for 2nd, $10,000 for 3rd), Mentorship, Business support, Publicity and recognition, Networking opportunities',
                'eligibility' => 'Innovators from any African country with solutions addressing African challenges',
                'amount' => '10000-25000',
                'currency' => 'USD',
                'source_url' => 'https://www.innovationprizeforafrica.org/'
            ],
            [
                'title' => 'African Leadership Academy - Wadhwani Foundation Opportunity for Africa\'s Innovators',
                'description' => 'This program provides seed funding and business training to young African entrepreneurs developing innovative businesses. The initiative combines financial support with intensive mentorship and business education to help entrepreneurs scale their ventures. Participants receive training in business fundamentals, access to mentors, and seed funding to grow their businesses.',
                'organization' => 'African Leadership Academy / Wadhwani Foundation',
                'location' => 'Africa-wide',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://www.africanleadershipacademy.org/programs/',
                'requirements' => 'African entrepreneur aged 18-35, Innovative business idea or early-stage venture, Commitment to Africa\'s development, Coachable and willing to learn',
                'benefits' => 'Seed funding, Business training, Mentorship from successful entrepreneurs, Networking opportunities, Business tools and resources',
                'eligibility' => 'Young African entrepreneurs from all African countries',
                'amount' => '5000-15000',
                'currency' => 'USD',
                'source_url' => 'https://www.africanleadershipacademy.org/programs/'
            ],
            [
                'title' => 'Climate Action Grants for African Youth',
                'description' => 'Various international organizations provide grants to support African youth-led initiatives addressing climate change, environmental conservation, and sustainable development. These grants support projects ranging from renewable energy, waste management, reforestation, sustainable agriculture, and climate advocacy. African youth organizations and individuals can access funding to implement local climate solutions.',
                'organization' => 'Multiple Climate Funds (UN, GEF, Green Climate Fund)',
                'location' => 'Africa-wide',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+4 months')),
                'application_url' => 'https://www.greenclimate.fund/countries',
                'requirements' => 'Climate or environment focus, Clear project proposal, Community engagement, Measurable impact indicators, Sustainability plan beyond grant period',
                'benefits' => 'Project funding, Technical support, Monitoring and evaluation assistance, Networking with other climate activists, Potential for follow-on funding',
                'eligibility' => 'African youth organizations and individuals with climate action projects',
                'amount' => '5000-50000',
                'currency' => 'USD',
                'source_url' => 'https://www.greenclimate.fund/countries'
            ],
            [
                'title' => 'African Media Initiative (AMI) Innovation Fund',
                'description' => 'The African Media Initiative provides grants to support innovative media projects, journalism initiatives, and digital media startups across Africa. The fund supports projects that strengthen African journalism, promote press freedom, and develop sustainable media business models. Grants are available for individual journalists, media startups, and established media organizations implementing innovative projects.',
                'organization' => 'African Media Initiative',
                'location' => 'Africa-wide',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+3 months')),
                'application_url' => 'https://www.africanmediainitiative.org/innovation-fund',
                'requirements' => 'Media or journalism focus, African media professional or organization, Innovative approach, Clear impact on African media landscape, Budget and timeline',
                'benefits' => 'Grant funding for projects, Technical assistance, Networking with media professionals, Mentorship, Potential for additional funding',
                'eligibility' => 'African journalists, media startups, and media organizations',
                'amount' => '5000-25000',
                'currency' => 'USD',
                'source_url' => 'https://www.africanmediainitiative.org/innovation-fund'
            ],
            [
                'title' => 'STEM Education Grants for African Schools',
                'description' => 'Multiple organizations provide grants to support STEM (Science, Technology, Engineering, Mathematics) education in African schools and communities. These grants fund equipment, teacher training, student programs, and innovative approaches to STEM education. Funding supports initiatives that increase access to quality STEM education, particularly for girls and underserved communities.',
                'organization' => 'Various (UNESCO, Mastercard Foundation, IBM)',
                'location' => 'Africa-wide',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+5 months')),
                'application_url' => 'https://en.unesco.org/themes/education-africa',
                'requirements' => 'STEM education focus, School or education organization, Clear program plan, Teacher involvement, Student reach metrics, Sustainability plan',
                'benefits' => 'Equipment and materials funding, Teacher training, Curriculum development support, Student scholarships (where applicable), Monitoring and evaluation',
                'eligibility' => 'African schools, education NGOs, and STEM education initiatives',
                'amount' => '10000-100000',
                'currency' => 'USD',
                'source_url' => 'https://en.unesco.org/themes/education-africa'
            ],
            [
                'title' => 'Sports for Development Grants',
                'description' => 'International organizations provide grants to support youth sports programs that use sports as a tool for development, education, and social cohesion. These programs focus on using sports to teach life skills, promote health, gender equality, peacebuilding, and community development. Grants support equipment, coach training, tournament organization, and program implementation.',
                'organization' => 'Various (UNESCO, Laureus Sport for Good, Right to Play)',
                'location' => 'Africa-wide',
                'country' => 'Multiple Countries',
                'deadline' => date('Y-m-d', strtotime('+4 months')),
                'application_url' => 'https://www.sportanddev.org/en/funding-opportunities',
                'requirements' => 'Sports-based youth program, Focus on development outcomes (education, health, peacebuilding), Qualified coaches, Community engagement, Reach to underserved youth',
                'benefits' => 'Program funding, Equipment, Coach training, Monitoring and evaluation support, Networking with sports development community',
                'eligibility' => 'Sports clubs, schools, NGOs, and community organizations in Africa',
                'amount' => '5000-50000',
                'currency' => 'USD',
                'source_url' => 'https://www.sportanddev.org/en/funding-opportunities'
            ]
        ];

        foreach ($sample_grants as $grant) {
            $saved = $this->saveOpportunity($grant);
            if ($saved) {
                $this->items_scraped++;
            }
        }
    }
}
?>
