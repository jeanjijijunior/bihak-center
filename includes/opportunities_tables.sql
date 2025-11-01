-- ========================================
-- OPPORTUNITIES SYSTEM
-- Scholarships, Jobs, Internships, and Grants
-- ========================================

-- Main opportunities table
CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('scholarship', 'job', 'internship', 'grant') NOT NULL,
    organization VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    country VARCHAR(100),
    deadline DATE,
    application_url VARCHAR(500),
    requirements TEXT,
    benefits TEXT,
    eligibility TEXT,
    amount VARCHAR(100),
    currency VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    source_url VARCHAR(500),
    source_name VARCHAR(100),
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    views_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    INDEX idx_type (type),
    INDEX idx_deadline (deadline),
    INDEX idx_is_active (is_active),
    INDEX idx_country (country),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opportunity categories/tags
CREATE TABLE IF NOT EXISTS opportunity_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE,
    tag_type ENUM('field', 'level', 'duration', 'other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tag_type (tag_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction table for opportunities and tags
CREATE TABLE IF NOT EXISTS opportunity_tag_relations (
    opportunity_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (opportunity_id, tag_id),
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES opportunity_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User saved opportunities (favorites)
CREATE TABLE IF NOT EXISTS user_saved_opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    opportunity_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    UNIQUE KEY unique_user_opportunity (user_id, opportunity_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_opportunity_id (opportunity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scraper log for tracking scraping activities
CREATE TABLE IF NOT EXISTS scraper_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL,
    scraper_type ENUM('scholarship', 'job', 'internship', 'grant') NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    items_scraped INT DEFAULT 0,
    items_added INT DEFAULT 0,
    items_updated INT DEFAULT 0,
    error_message TEXT,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time INT,
    INDEX idx_source_name (source_name),
    INDEX idx_scraper_type (scraper_type),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA FOR TESTING
-- ========================================

-- Insert common tags
INSERT INTO opportunity_tags (tag_name, tag_type) VALUES
    ('Technology', 'field'),
    ('Healthcare', 'field'),
    ('Education', 'field'),
    ('Business', 'field'),
    ('Engineering', 'field'),
    ('Arts', 'field'),
    ('Science', 'field'),
    ('Undergraduate', 'level'),
    ('Graduate', 'level'),
    ('Postgraduate', 'level'),
    ('Entry Level', 'level'),
    ('Mid Level', 'level'),
    ('Senior Level', 'level'),
    ('Full-time', 'duration'),
    ('Part-time', 'duration'),
    ('Remote', 'other'),
    ('International', 'other'),
    ('Fully Funded', 'other')
ON DUPLICATE KEY UPDATE tag_name = tag_name;

-- Sample scholarship opportunities
INSERT INTO opportunities (
    title, description, type, organization, location, country, deadline,
    application_url, requirements, benefits, eligibility, amount, currency,
    source_name, is_active
) VALUES
(
    'MasterCard Foundation Scholars Program',
    'The MasterCard Foundation Scholars Program provides financial support, mentorship, and community engagement opportunities for academically talented yet economically disadvantaged young people from Africa.',
    'scholarship',
    'MasterCard Foundation',
    'Various Universities',
    'Multiple Countries',
    '2025-03-31',
    'https://mastercardfdn.org/scholars-program',
    'Academic excellence, demonstrated financial need, commitment to giving back to community',
    'Full tuition, accommodation, books, travel, stipend',
    'African youth, Secondary or university level',
    'Full Scholarship',
    'USD',
    'MasterCard Foundation',
    TRUE
),
(
    'DAAD Scholarships for Development-Related Postgraduate Courses',
    'DAAD offers scholarships for international graduates from developing countries to pursue a Masters or PhD degree in Germany.',
    'scholarship',
    'DAAD (German Academic Exchange Service)',
    'Germany',
    'Germany',
    '2025-05-15',
    'https://www.daad.de/en/study-and-research-in-germany/scholarships',
    'Bachelors degree, at least 2 years professional experience, excellent academic record',
    'Monthly stipend, travel allowance, health insurance',
    'Graduates from developing countries',
    '850-1200',
    'EUR',
    'DAAD',
    TRUE
),
(
    'Chevening Scholarships',
    'UK government global scholarship program for outstanding emerging leaders to pursue one-year Masters degrees in the UK.',
    'scholarship',
    'UK Foreign, Commonwealth & Development Office',
    'United Kingdom',
    'United Kingdom',
    '2025-11-01',
    'https://www.chevening.org',
    'Leadership potential, academic excellence, networking skills',
    'Full tuition, living expenses, travel costs',
    'Citizens of Chevening-eligible countries with work experience',
    'Full Scholarship',
    'GBP',
    'Chevening',
    TRUE
);

-- Sample job opportunities
INSERT INTO opportunities (
    title, description, type, organization, location, country, deadline,
    application_url, requirements, benefits, eligibility, amount, currency,
    source_name, is_active
) VALUES
(
    'Junior Software Developer',
    'Join our development team to build innovative web applications using modern technologies.',
    'job',
    'Tech Innovation Rwanda',
    'Kigali',
    'Rwanda',
    '2025-02-28',
    'https://example.com/apply/junior-dev',
    'Bachelors in Computer Science, knowledge of JavaScript, PHP, MySQL',
    'Competitive salary, health insurance, professional development',
    'Recent graduates or 1-2 years experience',
    '500-800',
    'USD',
    'Company Website',
    TRUE
),
(
    'Digital Marketing Specialist',
    'Seeking a creative digital marketer to manage social media campaigns and online presence.',
    'job',
    'Creative Agency Africa',
    'Nairobi',
    'Kenya',
    '2025-03-15',
    'https://example.com/apply/marketing',
    'Degree in Marketing, 2+ years experience, social media expertise',
    'Flexible hours, remote work options, training',
    'Experienced marketing professionals',
    '600-1000',
    'USD',
    'LinkedIn',
    TRUE
);

-- Sample internship opportunities
INSERT INTO opportunities (
    title, description, type, organization, location, country, deadline,
    application_url, requirements, benefits, eligibility, amount, currency,
    source_name, is_active
) VALUES
(
    'Software Engineering Internship',
    '3-month paid internship working on real-world projects with mentorship from senior developers.',
    'internship',
    'African Tech Hub',
    'Accra',
    'Ghana',
    '2025-02-15',
    'https://example.com/apply/intern-swe',
    'Currently enrolled in Computer Science or related field',
    'Monthly stipend, certificate, networking opportunities',
    'University students',
    '300',
    'USD',
    'Company Website',
    TRUE
),
(
    'UN Internship Programme',
    'Gain practical experience in the United Nations system working on international development projects.',
    'internship',
    'United Nations',
    'Various Locations',
    'Multiple Countries',
    '2025-04-30',
    'https://careers.un.org/internships',
    'Enrolled in graduate program or recently graduated',
    'Monthly stipend (location dependent), certificate',
    'Graduate students in relevant fields',
    '1000-1500',
    'USD',
    'UN Careers',
    TRUE
);

-- Sample grant opportunities
INSERT INTO opportunities (
    title, description, type, organization, location, country, deadline,
    application_url, requirements, benefits, eligibility, amount, currency,
    source_name, is_active
) VALUES
(
    'Youth Innovation Grant',
    'Funding for young entrepreneurs developing innovative solutions to community challenges.',
    'grant',
    'African Development Bank',
    'Africa-wide',
    'Multiple Countries',
    '2025-03-20',
    'https://example.com/apply/innovation-grant',
    'Innovative business idea, business plan, age 18-35',
    'Seed funding, mentorship, networking',
    'Young African entrepreneurs',
    '5000-25000',
    'USD',
    'AfDB',
    TRUE
),
(
    'Community Development Grant',
    'Support for grassroots organizations working on youth empowerment and education initiatives.',
    'grant',
    'Global Fund for Community Foundations',
    'Various',
    'Multiple Countries',
    '2025-06-30',
    'https://example.com/apply/community-grant',
    'Registered organization, clear project plan, community impact',
    'Project funding, capacity building',
    'Non-profit organizations',
    '10000-50000',
    'USD',
    'GFCF',
    TRUE
);

-- ========================================
-- USEFUL VIEWS
-- ========================================

-- Active opportunities with tag information
CREATE OR REPLACE VIEW active_opportunities_view AS
SELECT
    o.id,
    o.title,
    o.description,
    o.type,
    o.organization,
    o.location,
    o.country,
    o.deadline,
    o.application_url,
    o.amount,
    o.currency,
    o.views_count,
    o.applications_count,
    o.created_at,
    GROUP_CONCAT(DISTINCT ot.tag_name ORDER BY ot.tag_name SEPARATOR ', ') as tags,
    DATEDIFF(o.deadline, CURDATE()) as days_remaining
FROM opportunities o
LEFT JOIN opportunity_tag_relations otr ON o.id = otr.opportunity_id
LEFT JOIN opportunity_tags ot ON otr.tag_id = ot.id
WHERE o.is_active = TRUE
  AND (o.deadline IS NULL OR o.deadline >= CURDATE())
GROUP BY o.id
ORDER BY o.deadline ASC, o.created_at DESC;

-- ========================================
-- CLEANUP PROCEDURES
-- ========================================

-- Archive expired opportunities (run daily)
-- UPDATE opportunities SET is_active = FALSE WHERE deadline < CURDATE() AND is_active = TRUE;

-- Delete old scraper logs (keep 90 days)
-- DELETE FROM scraper_log WHERE completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- ========================================
-- NOTES
-- ========================================
-- 1. Opportunities are marked inactive when deadline passes
-- 2. Scraper runs daily to fetch new opportunities
-- 3. User can save unlimited opportunities
-- 4. Tags are reusable across opportunities
-- 5. View counts tracked for analytics
-- ========================================
