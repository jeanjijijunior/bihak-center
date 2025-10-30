-- Bihak Center - Profiles and Admin Schema
-- Complete database structure for user profiles and admin management

-- Drop existing tables if they exist (for fresh setup)
DROP TABLE IF EXISTS profile_media;
DROP TABLE IF EXISTS profiles;
DROP TABLE IF EXISTS admins;

-- Admin users table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin (password: admin123 - CHANGE THIS IN PRODUCTION!)
INSERT INTO admins (username, email, password_hash, full_name) VALUES
('admin', 'admin@bihakcenter.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- User profiles table
CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say') DEFAULT 'Prefer not to say',

    -- Location
    city VARCHAR(100),
    district VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Rwanda',

    -- Education
    education_level ENUM('Primary', 'Secondary', 'University', 'Graduate', 'Other'),
    current_institution VARCHAR(200),
    field_of_study VARCHAR(200),

    -- Profile content
    title VARCHAR(200) NOT NULL,
    short_description TEXT NOT NULL,
    full_story TEXT NOT NULL,
    goals TEXT,
    achievements TEXT,

    -- Media
    profile_image VARCHAR(255),
    media_type ENUM('image', 'video', 'document') DEFAULT 'image',
    media_url VARCHAR(255),

    -- Social media
    facebook_url VARCHAR(255),
    twitter_url VARCHAR(255),
    instagram_url VARCHAR(255),
    linkedin_url VARCHAR(255),

    -- Status
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_published BOOLEAN DEFAULT FALSE,
    rejection_reason TEXT,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    approved_by INT,

    -- Views counter
    view_count INT DEFAULT 0,

    FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_is_published (is_published),
    INDEX idx_created_at (created_at),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additional media files table (for multiple images/videos per profile)
CREATE TABLE profile_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    media_type ENUM('image', 'video', 'document') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    caption TEXT,
    display_order INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    INDEX idx_profile_id (profile_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert fictive profiles for demonstration
INSERT INTO profiles (
    full_name, email, phone, date_of_birth, gender, city, district, country,
    education_level, current_institution, field_of_study,
    title, short_description, full_story, goals, achievements,
    profile_image, media_type, status, is_published, approved_at, view_count
) VALUES
(
    'Amara Uwase', 'amara.uwase@example.com', '+250788123456', '2002-05-15', 'Female',
    'Kigali', 'Gasabo', 'Rwanda',
    'University', 'University of Rwanda', 'Computer Science',
    'Aspiring Software Developer Building Solutions for Rural Communities',
    'A passionate tech enthusiast from Kigali working on mobile apps to connect rural farmers with markets...',
    'Growing up in a rural area, I witnessed firsthand the challenges farmers face in accessing markets. After receiving a scholarship to study Computer Science at the University of Rwanda, I decided to use my skills to make a difference. I am currently developing a mobile application that connects farmers directly with buyers, eliminating middlemen and increasing profits for small-scale farmers.\n\nMy journey has not been easy. Coming from a low-income family, I had to work part-time jobs while studying to support myself. But every challenge has made me stronger and more determined to succeed. I believe technology can transform lives, especially in underserved communities.\n\nThrough Bihak Center''s mentorship program, I have received invaluable guidance on project management, entrepreneurship, and software development best practices. I am now preparing to launch my app in pilot communities.',
    'Complete my degree with honors\nLaunch the farmers marketplace app in 5 districts\nSecure funding to scale the project nationally\nMentor other young women in technology',
    'Dean''s List for 3 consecutive semesters\nWinner of University Innovation Challenge 2024\nCompleted Google Africa Developer Scholarship\nBuilt 3 mobile apps for local NGOs',
    '../assets/images/Designer_1.jpeg', 'image', 'approved', TRUE, NOW(), 45
),
(
    'Jean Paul Nkunda', 'jeanpaul.nkunda@example.com', '+250788234567', '2001-08-22', 'Male',
    'Huye', 'Huye', 'Rwanda',
    'University', 'University of Rwanda - Huye Campus', 'Environmental Science',
    'Environmental Activist Fighting Climate Change Through Youth Action',
    'Leading climate change awareness campaigns and tree planting initiatives across Southern Province...',
    'Climate change is the defining challenge of our generation, and I believe young people must lead the charge. After witnessing devastating floods in my community that destroyed crops and homes, I decided to dedicate my life to environmental conservation.\n\nI founded the "Green Youth Rwanda" movement in 2023, which has mobilized over 500 young people to plant more than 10,000 trees across the Southern Province. We also conduct workshops in schools to educate students about sustainable practices, waste management, and renewable energy.\n\nBihak Center has been instrumental in my journey, providing training on leadership, project management, and grant writing. With their support, I secured funding from an international environmental organization to expand our tree-planting program.\n\nMy vision is to create a generation of environmentally conscious young Rwandans who understand that protecting our planet is not just a responsibility—it is a necessity for our survival.',
    'Plant 50,000 trees by 2026\nEstablish environmental clubs in 100 schools\nTrain 1,000 young environmental ambassadors\nAdvocate for stronger environmental policies',
    'Founded Green Youth Rwanda with 500+ members\nPlanted 10,000 trees in 18 months\nAwarded Young Environmental Leader 2024\nSpoke at Rwanda Youth Climate Summit',
    '../assets/images/Designer_2.jpeg', 'image', 'approved', TRUE, NOW(), 38
),
(
    'Grace Mutesi', 'grace.mutesi@example.com', '+250788345678', '2003-03-10', 'Female',
    'Rubavu', 'Rubavu', 'Rwanda',
    'Secondary', 'Lycée de Rubavu', 'Sciences',
    'Young Artist Using Painting to Tell Stories of Hope and Resilience',
    'Creating powerful artworks that showcase the beauty and resilience of African youth...',
    'Art has always been my refuge and my voice. As a young girl from Rubavu, I discovered painting when I was 12 years old. What started as drawing in the margins of my notebooks has evolved into a passion that drives me every day.\n\nMy paintings focus on themes of hope, resilience, and the strength of African youth. Each piece tells a story—stories of young people overcoming adversity, pursuing education despite obstacles, and building better futures for themselves and their communities.\n\nThrough Bihak Center''s arts program, I have received mentorship from established artists and access to quality art supplies. They helped me organize my first art exhibition, where I sold 8 paintings and used the proceeds to support my education and buy materials for other aspiring young artists in my community.\n\nI dream of becoming Rwanda''s next celebrated artist and using my work to inspire young people across Africa. Art is not just about creating beauty—it is about creating change.',
    'Complete secondary school with distinction\nAttend art school or university\nHost exhibitions in Kigali and internationally\nCreate an art program for underprivileged children',
    'First solo exhibition with 8 paintings sold\nArtwork featured in 3 local galleries\nWon Rubavu Youth Art Competition 2024\nCommissioned to create mural for community center',
    '../assets/images/Designer_5.jpeg', 'image', 'approved', TRUE, NOW(), 52
),
(
    'Emmanuel Hakizimana', 'emmanuel.hakizimana@example.com', '+250788456789', '2000-11-30', 'Male',
    'Musanze', 'Musanze', 'Rwanda',
    'University', 'IPRC Musanze', 'Mechanical Engineering',
    'Engineering Student Innovating Solutions for Agriculture',
    'Designing affordable farming equipment to help smallholder farmers increase productivity...',
    'Growing up in a farming community in Musanze, I saw how my parents and neighbors struggled with outdated farming tools and techniques. This inspired me to pursue mechanical engineering, with the goal of creating affordable, locally-made equipment to help farmers.\n\nDuring my studies at IPRC Musanze, I designed a low-cost irrigation system using recycled materials that reduces water usage by 40%. I also developed a prototype for a multi-purpose hand tool that can perform three functions, reducing the need for farmers to buy multiple expensive tools.\n\nBihak Center''s technical skills program provided me with access to workshop facilities and mentorship from experienced engineers. They also helped me apply for a patent for my irrigation system design. I am now working on producing the first batch of 50 units to distribute to farmers in my community at subsidized prices.\n\nMy dream is to establish a social enterprise that manufactures affordable agricultural equipment and creates employment opportunities for other young technical graduates in Rwanda.',
    'Graduate with honors in Mechanical Engineering\nProduce and distribute 500 irrigation systems\nEstablish a manufacturing workshop\nTrain 20 young people in agricultural engineering',
    'Designed 3 innovative farming tools\nPatent pending for irrigation system\nRecipient of Innovation in Agriculture Award\nPrototypes tested by 30+ farmers with positive feedback',
    '../assets/images/Designer_3.jpeg', 'image', 'approved', TRUE, NOW(), 29
),
(
    'Diane Ingabire', 'diane.ingabire@example.com', '+250788567890', '2002-07-18', 'Female',
    'Kigali', 'Kicukiro', 'Rwanda',
    'University', 'Adventist University of Central Africa', 'Business Administration',
    'Young Entrepreneur Empowering Women Through Fashion',
    'Creating employment for young women through a sustainable fashion business...',
    'Fashion is more than just clothing—it is empowerment, culture, and economic opportunity. After learning traditional Rwandan weaving techniques from my grandmother, I decided to create a modern fashion brand that celebrates our heritage while providing income for young women.\n\nI founded "Imbuto Fashion" in 2023, a social enterprise that trains young women in tailoring, weaving, and fashion design, then employs them to create contemporary clothing that blends traditional Rwandan patterns with modern styles. We have trained 15 young women so far, and our products are sold in boutiques in Kigali and online.\n\nBihak Center''s entrepreneurship program was a game-changer for me. They provided business training, helped me develop a business plan, and connected me with potential investors. I also received a small grant that allowed me to purchase sewing machines and establish a workshop.\n\nMy vision is to scale Imbuto Fashion to employ 100 young women within the next three years, export our products internationally, and become a model for youth-led social enterprises in Rwanda.',
    'Train and employ 100 young women by 2027\nOpen retail stores in 3 cities\nExport to regional and international markets\nLaunch a fashion design academy',
    'Founded Imbuto Fashion with 15 employees\nGenerated $12,000 in revenue in first year\nProducts featured in Kigali Fashion Week 2024\nAwarded Best Youth-Led Social Enterprise',
    '../assets/images/Designer_4.jpeg', 'image', 'approved', TRUE, NOW(), 41
),
(
    'Patrick Mugabo', 'patrick.mugabo@example.com', '+250788678901', '2004-01-25', 'Male',
    'Kigali', 'Nyarugenge', 'Rwanda',
    'Secondary', 'Lycée de Kigali', 'Sciences',
    'Young Coder Teaching Programming to Children',
    'Inspiring the next generation of tech leaders through free coding classes...',
    'I taught myself programming at age 13 using free online resources and a borrowed laptop. Five years later, I am on a mission to ensure other young people do not face the same barriers I did in accessing tech education.\n\nI started "Code Kids Rwanda," a volunteer initiative that provides free coding classes to children aged 10-16 in underserved communities. Using donated computers and open-source software, we teach basic programming, web development, and problem-solving skills. So far, we have trained over 200 children, and some have gone on to win national coding competitions.\n\nBihak Center has been crucial in supporting Code Kids Rwanda. They provided us with a space to hold classes, helped us secure computer donations, and trained me in curriculum development and teaching methodologies. They also connected us with tech companies that sponsor our programs.\n\nMy ultimate goal is to establish a full-fledged tech academy for young people, offering courses in programming, robotics, artificial intelligence, and digital entrepreneurship. Every child deserves the chance to become a creator, not just a consumer, of technology.',
    'Train 1,000 children in programming\nEstablish 10 coding clubs in schools\nSecure funding for a permanent tech academy\nPartner with tech companies for mentorship programs',
    'Founded Code Kids Rwanda, training 200+ children\nTrained 5 volunteer instructors\nStudents won 3 national coding competitions\nRecognized as Youth Tech Ambassador 2024',
    '../assets/images/Designer_7.jpeg', 'image', 'approved', TRUE, NOW(), 34
),
(
    'Marie Claire Uwera', 'marieclaire.uwera@example.com', '+250788789012', '2001-09-05', 'Female',
    'Rwamagana', 'Rwamagana', 'Rwanda',
    'University', 'University of Rwanda - College of Agriculture', 'Agribusiness',
    'Agribusiness Leader Promoting Sustainable Farming',
    'Connecting young farmers with modern agricultural techniques and market opportunities...',
    'I come from a family of farmers, and I have witnessed the potential and challenges of agriculture in Rwanda. Instead of leaving farming like many young people, I chose to stay and transform it. I am studying Agribusiness and applying modern techniques to make farming profitable and sustainable.\n\nI established "Youth Agro Connect," a cooperative of 45 young farmers in Eastern Province. We pool resources to buy inputs, share equipment, access training, and sell our produce collectively to get better prices. We focus on organic farming and have successfully entered contracts with hotels and supermarkets in Kigali.\n\nBihak Center''s training in cooperative management, financial literacy, and market linkages has been invaluable. They also helped us access a revolving fund that members use to invest in improved seeds, irrigation, and post-harvest storage.\n\nMy vision is to make agriculture attractive to young people by proving it can be profitable, modern, and dignified. We are creating a model that other youth groups across the country can replicate.',
    'Grow cooperative to 200 members\nGenerate $50,000 in annual revenue\nEstablish a processing facility for value addition\nTrain 500 young farmers in sustainable agriculture',
    'Established Youth Agro Connect with 45 members\nSecured contracts with 5 major buyers\nIncreased members'' income by average 60%\nAwarded Best Youth Cooperative 2024',
    '../assets/images/Designer_6.jpeg', 'image', 'approved', TRUE, NOW(), 27
),
(
    'Samuel Ndayishimiye', 'samuel.ndayishimiye@example.com', '+250788890123', '2003-12-08', 'Male',
    'Nyagatare', 'Nyagatare', 'Rwanda',
    'Secondary', 'GS Nyagatare', 'Sciences',
    'Aspiring Veterinarian Caring for Community Animals',
    'Providing free veterinary services to smallholder farmers in rural areas...',
    'As a young person passionate about animals and community development, I decided to pursue veterinary medicine after seeing many animals in my community die from preventable diseases. While still in secondary school, I have already started making a difference.\n\nWith basic training from local veterinarians and support from Bihak Center, I provide basic animal health services—vaccinations, deworming, wound care—to farmers who cannot afford professional veterinary fees. I also conduct awareness sessions on animal health, nutrition, and hygiene.\n\nI work with over 80 farmers in Nyagatare, treating their cattle, goats, and poultry. Many farmers have told me my interventions have saved their animals and their livelihoods. Bihak Center provided me with a bicycle to reach remote areas and a starter kit of veterinary supplies.\n\nMy dream is to attend veterinary school, become a licensed veterinarian, and establish a community veterinary clinic that offers affordable services and trains other young people interested in animal health.',
    'Complete secondary school with distinction\nAttend veterinary school\nEstablish a community veterinary clinic\nTrain 50 young animal health assistants',
    'Provided services to 80+ farmers\nVaccinated over 500 animals\nReduced livestock mortality by 40% in target areas\nAwarded Young Community Champion 2024',
    '../assets/images/Designer_8.jpeg', 'image', 'approved', TRUE, NOW(), 31
);

-- Create a view for approved and published profiles
CREATE VIEW published_profiles AS
SELECT
    id, full_name, email, date_of_birth, gender, city, district, country,
    education_level, current_institution, field_of_study,
    title, short_description, full_story, goals, achievements,
    profile_image, media_type, media_url,
    facebook_url, twitter_url, instagram_url, linkedin_url,
    created_at, updated_at, approved_at, view_count
FROM profiles
WHERE status = 'approved' AND is_published = TRUE
ORDER BY created_at DESC;
