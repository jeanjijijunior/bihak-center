-- Sponsors/Mentors/Donors Table
-- Stores information about people who want to get involved with Bihak Center

CREATE TABLE IF NOT EXISTS sponsors (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Personal Information
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    organization VARCHAR(200),
    website VARCHAR(255),

    -- Location
    country VARCHAR(100),
    city VARCHAR(100),

    -- Involvement Details
    role_type ENUM('mentor', 'donor', 'sponsor', 'volunteer', 'partner') NOT NULL,
    expertise_domain VARCHAR(255),
    involvement_areas TEXT COMMENT 'Comma-separated: coaching, mentoring, funding, talent_support, internships, equipment, etc.',

    -- Additional Information
    message TEXT,
    availability VARCHAR(100) COMMENT 'e.g., weekly, monthly, one-time',
    preferred_contact ENUM('email', 'phone', 'both') DEFAULT 'email',

    -- Status & Approval
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_active BOOLEAN DEFAULT TRUE,
    rejection_reason TEXT,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    approved_by INT,

    -- Social Media (Optional)
    linkedin_url VARCHAR(255),
    facebook_url VARCHAR(255),
    twitter_url VARCHAR(255),

    -- Indexes
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role_type (role_type),
    INDEX idx_created_at (created_at),

    -- Foreign Key
    FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing
INSERT INTO sponsors (full_name, email, phone, organization, role_type, expertise_domain, involvement_areas, message, status) VALUES
('Dr. Sarah Johnson', 'sarah.johnson@example.com', '+250788123456', 'Tech for Good Foundation', 'mentor', 'Software Engineering', 'coaching,mentoring,internships', 'I would love to mentor young developers and provide internship opportunities at our organization.', 'approved'),
('Michael Chen', 'mchen@globalgiving.org', '+1-555-0123', 'Global Giving', 'donor', 'Education Funding', 'funding,equipment', 'Interested in funding education projects and providing learning equipment.', 'pending'),
('Amina Uwase', 'amina.uwase@rwandacorp.rw', '+250789654321', 'Rwanda Corp', 'sponsor', 'Business Management', 'funding,coaching,talent_support', 'Our company wants to support talented youth through scholarships and mentorship programs.', 'pending');
