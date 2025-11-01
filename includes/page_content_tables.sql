-- Page Content Management System
-- Allows admins to edit static page content without touching code

CREATE TABLE IF NOT EXISTS page_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_name VARCHAR(50) NOT NULL,
    section_key VARCHAR(100) NOT NULL,
    content_type ENUM('text', 'html', 'heading', 'paragraph', 'image_url', 'link_url') DEFAULT 'text',
    content_en TEXT,
    content_fr TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    UNIQUE KEY unique_page_section (page_name, section_key),
    FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_page_name (page_name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default content for existing pages
INSERT INTO page_contents (page_name, section_key, content_type, content_en, content_fr, display_order) VALUES
-- Homepage
('home', 'hero_title', 'heading', 'Empowering Young People', 'Autonomiser les Jeunes', 1),
('home', 'hero_subtitle', 'paragraph', 'Share your story. Get support. Inspire others. Join our community of youth making a difference.', 'Partagez votre histoire. Obtenez du soutien. Inspirez les autres. Rejoignez notre communauté de jeunes qui font la différence.', 2),
('home', 'stories_title', 'heading', 'Youth Changing the World', 'Les Jeunes Qui Changent le Monde', 3),
('home', 'stories_subtitle', 'paragraph', 'Meet the young people we support and the incredible things they''re achieving', 'Rencontrez les jeunes que nous soutenons et les choses incroyables qu''ils accomplissent', 4),
('home', 'cta_title', 'heading', 'Have a Story to Share?', 'Vous avez une histoire à partager ?', 5),
('home', 'cta_text', 'paragraph', 'Join our community of young people making a difference. Share your journey, get support, and inspire others.', 'Rejoignez notre communauté de jeunes qui font la différence. Partagez votre parcours, obtenez du soutien et inspirez les autres.', 6),

-- About Page
('about', 'hero_title', 'heading', 'Empowering Young People to Shape Their Future', 'Autonomiser les Jeunes à Façonner Leur Avenir', 1),
('about', 'hero_subtitle', 'paragraph', 'Bihak Center is a platform dedicated to showcasing talented young people, connecting them with opportunities, and amplifying their voices to create lasting impact.', 'Le Centre Bihak est une plateforme dédiée à la mise en valeur des jeunes talentueux, en les connectant avec des opportunités et en amplifiant leurs voix pour créer un impact durable.', 2),
('about', 'mission_title', 'heading', 'Our Mission', 'Notre Mission', 3),
('about', 'vision_title', 'heading', 'Our Vision', 'Notre Vision', 4),

-- Work Page
('work', 'hero_title', 'heading', 'Making Impact Through Action', 'Créer un Impact Par l''Action', 1),
('work', 'hero_subtitle', 'paragraph', 'Discover our programs designed to empower young people with the skills, resources, and connections they need to succeed.', 'Découvrez nos programmes conçus pour autonomiser les jeunes avec les compétences, ressources et connexions dont ils ont besoin pour réussir.', 2),

-- Contact Page
('contact', 'hero_title', 'heading', 'Get in Touch', 'Contactez-Nous', 1),
('contact', 'hero_subtitle', 'paragraph', 'We''re here to help. Send us a message and we''ll respond as soon as possible.', 'Nous sommes là pour vous aider. Envoyez-nous un message et nous vous répondrons dès que possible.', 2),
('contact', 'form_title', 'heading', 'Send Us a Message', 'Envoyez-nous un Message', 3),

-- Opportunities Page
('opportunities', 'hero_title', 'heading', 'Discover Opportunities', 'Découvrez des Opportunités', 1),
('opportunities', 'hero_subtitle', 'paragraph', 'Explore scholarships, grants, competitions, and programs designed to help you grow and succeed.', 'Explorez des bourses, subventions, concours et programmes conçus pour vous aider à grandir et réussir.', 2);
