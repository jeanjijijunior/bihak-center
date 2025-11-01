<?php
/**
 * About Page - Mission-Focused and Inspiring
 * Showcasing Bihak Center's vision, mission, and impact
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Bihak Center | Empowering Young People</title>
    <meta name="description" content="Learn about Bihak Center's mission to empower young people, showcase talent, and provide opportunities for growth and success.">
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <link rel="icon" type="image/png" href="../assets/images/logob.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #2d3748;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 30px;
            opacity: 0.95;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Section Styles */
        .section {
            padding: 80px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a202c;
            text-align: center;
            margin-bottom: 20px;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: #718096;
            text-align: center;
            max-width: 700px;
            margin: 0 auto 50px;
        }

        /* Mission Section */
        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .mission-card {
            background: white;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-align: center;
        }

        .mission-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .mission-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1cabe2, #147ba5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .mission-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        .mission-card h3 {
            font-size: 1.5rem;
            color: #1a202c;
            margin-bottom: 15px;
        }

        .mission-card p {
            color: #718096;
            font-size: 1rem;
            line-height: 1.7;
        }

        /* Impact Numbers */
        .impact-section {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        }

        .impact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .impact-number {
            font-size: 3.5rem;
            font-weight: 700;
            color: #1cabe2;
            display: block;
            margin-bottom: 10px;
        }

        .impact-label {
            font-size: 1.1rem;
            color: #4a5568;
            font-weight: 500;
        }

        /* Values Section */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .value-item {
            background: white;
            padding: 30px;
            border-radius: 12px;
            border-left: 4px solid #1cabe2;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .value-item h3 {
            font-size: 1.3rem;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .value-item p {
            color: #718096;
            line-height: 1.7;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 80px 20px;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 35px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-white {
            background: white;
            color: #667eea;
        }

        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: #667eea;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .section {
                padding: 50px 20px;
            }

            .mission-grid,
            .values-grid {
                grid-template-columns: 1fr;
            }

            .impact-number {
                font-size: 2.5rem;
            }

            .cta-section h2 {
                font-size: 1.8rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1 id="hero-title">Empowering Young People to Shape Their Future</h1>
        <p id="hero-subtitle">Bihak Center is a platform dedicated to showcasing talented young people, connecting them with opportunities, and amplifying their voices to create lasting impact.</p>
    </section>

    <!-- Mission Section -->
    <section class="section">
        <h2 class="section-title" id="mission-title">Our Mission</h2>
        <p class="section-subtitle" id="mission-subtitle">
            We believe every young person has unique talents and potential. Our mission is to provide a platform where they can share their stories, connect with opportunities, and inspire others.
        </p>

        <div class="mission-grid">
            <div class="mission-card">
                <div class="mission-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
                <h3 id="showcase-title">Showcase Talent</h3>
                <p id="showcase-text">We provide a platform for young innovators, artists, entrepreneurs, and change-makers to share their inspiring stories with the world.</p>
            </div>

            <div class="mission-card">
                <div class="mission-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                    </svg>
                </div>
                <h3 id="opportunities-title">Provide Opportunities</h3>
                <p id="opportunities-text">We curate and share scholarships, internships, grants, and job opportunities tailored for young people to grow and succeed.</p>
            </div>

            <div class="mission-card">
                <div class="mission-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                    </svg>
                </div>
                <h3 id="empower-title">Empower Voices</h3>
                <p id="empower-text">We amplify the voices of young people, ensuring their ideas, innovations, and achievements reach a global audience.</p>
            </div>
        </div>
    </section>

    <!-- Impact Numbers -->
    <section class="section impact-section">
        <h2 class="section-title" id="impact-title">Our Impact</h2>
        <p class="section-subtitle" id="impact-subtitle">
            Numbers that tell the story of our growing community and the lives we're touching.
        </p>

        <div class="impact-grid">
            <div>
                <span class="impact-number">500+</span>
                <span class="impact-label" id="impact-profiles">Young Innovators</span>
            </div>
            <div>
                <span class="impact-number">1,200+</span>
                <span class="impact-label" id="impact-opportunities">Opportunities Shared</span>
            </div>
            <div>
                <span class="impact-number">50+</span>
                <span class="impact-label" id="impact-countries">Countries Reached</span>
            </div>
            <div>
                <span class="impact-number">10,000+</span>
                <span class="impact-label" id="impact-visitors">Monthly Visitors</span>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="section">
        <h2 class="section-title" id="values-title">Our Core Values</h2>
        <p class="section-subtitle" id="values-subtitle">
            The principles that guide everything we do at Bihak Center.
        </p>

        <div class="values-grid">
            <div class="value-item">
                <h3 id="value1-title">Inclusivity</h3>
                <p id="value1-text">We celebrate diversity and ensure every young person, regardless of background, has access to our platform and opportunities.</p>
            </div>

            <div class="value-item">
                <h3 id="value2-title">Excellence</h3>
                <p id="value2-text">We strive for excellence in everything we do, from curating opportunities to showcasing exceptional talent.</p>
            </div>

            <div class="value-item">
                <h3 id="value3-title">Empowerment</h3>
                <p id="value3-text">We empower young people with the tools, resources, and connections they need to succeed and make an impact.</p>
            </div>

            <div class="value-item">
                <h3 id="value4-title">Innovation</h3>
                <p id="value4-text">We embrace innovation and constantly evolve to better serve the needs of young people in a rapidly changing world.</p>
            </div>

            <div class="value-item">
                <h3 id="value5-title">Community</h3>
                <p id="value5-text">We build a supportive community where young people can connect, collaborate, and inspire each other.</p>
            </div>

            <div class="value-item">
                <h3 id="value6-title">Transparency</h3>
                <p id="value6-text">We operate with transparency and accountability, ensuring trust and credibility in all our activities.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2 id="cta-title">Ready to Share Your Story?</h2>
        <p id="cta-text">Join thousands of young innovators who are making their mark on the world.</p>
        <div class="cta-buttons">
            <a href="signup.php" class="btn btn-white">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                </svg>
                <span id="cta-btn1">Share Your Story</span>
            </a>
            <a href="opportunities.php" class="btn btn-outline">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                </svg>
                <span id="cta-btn2">Browse Opportunities</span>
            </a>
        </div>
    </section>

    <?php include '../includes/footer_new.php'; ?>

    <script src="../assets/js/header_new.js"></script>
    <script>
        // Language-specific content for About page
        const aboutTranslations = {
            en: {
                'hero-title': 'Empowering Young People to Shape Their Future',
                'hero-subtitle': 'Bihak Center is a platform dedicated to showcasing talented young people, connecting them with opportunities, and amplifying their voices to create lasting impact.',
                'mission-title': 'Our Mission',
                'mission-subtitle': 'We believe every young person has unique talents and potential. Our mission is to provide a platform where they can share their stories, connect with opportunities, and inspire others.',
                'showcase-title': 'Showcase Talent',
                'showcase-text': 'We provide a platform for young innovators, artists, entrepreneurs, and change-makers to share their inspiring stories with the world.',
                'opportunities-title': 'Provide Opportunities',
                'opportunities-text': 'We curate and share scholarships, internships, grants, and job opportunities tailored for young people to grow and succeed.',
                'empower-title': 'Empower Voices',
                'empower-text': 'We amplify the voices of young people, ensuring their ideas, innovations, and achievements reach a global audience.',
                'impact-title': 'Our Impact',
                'impact-subtitle': 'Numbers that tell the story of our growing community and the lives we\'re touching.',
                'impact-profiles': 'Young Innovators',
                'impact-opportunities': 'Opportunities Shared',
                'impact-countries': 'Countries Reached',
                'impact-visitors': 'Monthly Visitors',
                'values-title': 'Our Core Values',
                'values-subtitle': 'The principles that guide everything we do at Bihak Center.',
                'value1-title': 'Inclusivity',
                'value1-text': 'We celebrate diversity and ensure every young person, regardless of background, has access to our platform and opportunities.',
                'value2-title': 'Excellence',
                'value2-text': 'We strive for excellence in everything we do, from curating opportunities to showcasing exceptional talent.',
                'value3-title': 'Empowerment',
                'value3-text': 'We empower young people with the tools, resources, and connections they need to succeed and make an impact.',
                'value4-title': 'Innovation',
                'value4-text': 'We embrace innovation and constantly evolve to better serve the needs of young people in a rapidly changing world.',
                'value5-title': 'Community',
                'value5-text': 'We build a supportive community where young people can connect, collaborate, and inspire each other.',
                'value6-title': 'Transparency',
                'value6-text': 'We operate with transparency and accountability, ensuring trust and credibility in all our activities.',
                'cta-title': 'Ready to Share Your Story?',
                'cta-text': 'Join thousands of young innovators who are making their mark on the world.',
                'cta-btn1': 'Share Your Story',
                'cta-btn2': 'Browse Opportunities'
            },
            fr: {
                'hero-title': 'Autonomiser les Jeunes pour Façonner leur Avenir',
                'hero-subtitle': 'Bihak Center est une plateforme dédiée à mettre en valeur les jeunes talentueux, les connecter avec des opportunités et amplifier leurs voix pour créer un impact durable.',
                'mission-title': 'Notre Mission',
                'mission-subtitle': 'Nous croyons que chaque jeune a des talents et un potentiel uniques. Notre mission est de fournir une plateforme où ils peuvent partager leurs histoires, se connecter avec des opportunités et inspirer les autres.',
                'showcase-title': 'Mettre en Valeur les Talents',
                'showcase-text': 'Nous fournissons une plateforme pour les jeunes innovateurs, artistes, entrepreneurs et acteurs du changement pour partager leurs histoires inspirantes avec le monde.',
                'opportunities-title': 'Fournir des Opportunités',
                'opportunities-text': 'Nous organisons et partageons des bourses, stages, subventions et opportunités d\'emploi adaptées aux jeunes pour grandir et réussir.',
                'empower-title': 'Amplifier les Voix',
                'empower-text': 'Nous amplifions les voix des jeunes, en veillant à ce que leurs idées, innovations et réalisations atteignent un public mondial.',
                'impact-title': 'Notre Impact',
                'impact-subtitle': 'Des chiffres qui racontent l\'histoire de notre communauté croissante et des vies que nous touchons.',
                'impact-profiles': 'Jeunes Innovateurs',
                'impact-opportunities': 'Opportunités Partagées',
                'impact-countries': 'Pays Atteints',
                'impact-visitors': 'Visiteurs Mensuels',
                'values-title': 'Nos Valeurs Fondamentales',
                'values-subtitle': 'Les principes qui guident tout ce que nous faisons au Bihak Center.',
                'value1-title': 'Inclusivité',
                'value1-text': 'Nous célébrons la diversité et veillons à ce que chaque jeune, quelle que soit son origine, ait accès à notre plateforme et à nos opportunités.',
                'value2-title': 'Excellence',
                'value2-text': 'Nous visons l\'excellence dans tout ce que nous faisons, de la sélection des opportunités à la mise en valeur des talents exceptionnels.',
                'value3-title': 'Autonomisation',
                'value3-text': 'Nous donnons aux jeunes les outils, les ressources et les connexions dont ils ont besoin pour réussir et avoir un impact.',
                'value4-title': 'Innovation',
                'value4-text': 'Nous adoptons l\'innovation et évoluons constamment pour mieux servir les besoins des jeunes dans un monde en rapide évolution.',
                'value5-title': 'Communauté',
                'value5-text': 'Nous construisons une communauté solidaire où les jeunes peuvent se connecter, collaborer et s\'inspirer mutuellement.',
                'value6-title': 'Transparence',
                'value6-text': 'Nous opérons avec transparence et responsabilité, garantissant la confiance et la crédibilité dans toutes nos activités.',
                'cta-title': 'Prêt à Partager Votre Histoire?',
                'cta-text': 'Rejoignez des milliers de jeunes innovateurs qui laissent leur empreinte sur le monde.',
                'cta-btn1': 'Partagez Votre Histoire',
                'cta-btn2': 'Parcourir les Opportunités'
            }
        };

        // Update About page content when language changes
        document.addEventListener('languageChanged', function(e) {
            const lang = e.detail.language;
            const translations = aboutTranslations[lang];

            if (translations) {
                Object.keys(translations).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) {
                        element.textContent = translations[key];
                    }
                });
            }
        });
    </script>
</body>
</html>
