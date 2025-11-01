<?php
/**
 * Our Work Page - Showcase Impact and Programs
 * Highlighting Bihak Center's programs, success stories, and achievements
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
    <title>Our Work - Bihak Center | Programs & Impact</title>
    <meta name="description" content="Discover Bihak Center's programs, success stories, and the impact we're making in empowering young people across Africa.">
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
            padding: 100px 20px 80px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.95;
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
            margin-bottom: 50px;
        }

        /* Programs Grid */
        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .program-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .program-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .program-image {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .program-image svg {
            width: 80px;
            height: 80px;
            color: white;
        }

        .program-content {
            padding: 30px;
        }

        .program-content h3 {
            font-size: 1.5rem;
            color: #1a202c;
            margin-bottom: 15px;
        }

        .program-content p {
            color: #718096;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .program-stats {
            display: flex;
            gap: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1cabe2;
            display: block;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #718096;
        }

        /* Success Stories */
        .success-stories {
            background: #f7fafc;
        }

        .stories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .story-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .story-quote {
            font-size: 1.1rem;
            font-style: italic;
            color: #4a5568;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .story-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1cabe2, #147ba5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .author-info h4 {
            color: #1a202c;
            font-size: 1rem;
            margin-bottom: 3px;
        }

        .author-info p {
            color: #718096;
            font-size: 0.85rem;
        }

        /* Impact Timeline */
        .timeline {
            position: relative;
            padding: 40px 0;
        }

        .timeline-item {
            display: grid;
            grid-template-columns: 1fr 50px 1fr;
            gap: 30px;
            margin-bottom: 50px;
            align-items: center;
        }

        .timeline-item:nth-child(even) .timeline-content:first-child {
            order: 3;
        }

        .timeline-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .timeline-content h3 {
            color: #1a202c;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .timeline-content p {
            color: #718096;
            line-height: 1.7;
        }

        .timeline-dot {
            width: 20px;
            height: 20px;
            background: #1cabe2;
            border-radius: 50%;
            border: 5px solid #e6f7ff;
            position: relative;
        }

        .timeline-dot::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            background: rgba(28, 171, 226, 0.2);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.5);
                opacity: 0;
            }
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
            background: white;
            color: #667eea;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .section {
                padding: 50px 20px;
            }

            .timeline-item {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .timeline-dot {
                display: none;
            }

            .timeline-item:nth-child(even) .timeline-content:first-child {
                order: 1;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1 id="hero-title">Making Impact Through Action</h1>
        <p id="hero-subtitle">Discover our programs and the tangible difference we're making in the lives of young people across Africa.</p>
    </section>

    <!-- Programs Section -->
    <section class="section">
        <h2 class="section-title" id="programs-title">Our Programs</h2>

        <div class="programs-grid">
            <div class="program-card">
                <div class="program-image">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
                <div class="program-content">
                    <h3 id="program1-title">Talent Showcase Platform</h3>
                    <p id="program1-text">A digital platform where young innovators, artists, entrepreneurs, and change-makers can share their inspiring stories and showcase their achievements to a global audience.</p>
                    <div class="program-stats">
                        <div class="stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label" id="program1-stat1">Profiles</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">50K+</span>
                            <span class="stat-label" id="program1-stat2">Views</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="program-card">
                <div class="program-image" style="background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                    </svg>
                </div>
                <div class="program-content">
                    <h3 id="program2-title">Opportunities Hub</h3>
                    <p id="program2-text">Curated opportunities including scholarships, internships, grants, and jobs specifically tailored for young people to grow their careers and pursue their dreams.</p>
                    <div class="program-stats">
                        <div class="stat">
                            <span class="stat-number">1,200+</span>
                            <span class="stat-label" id="program2-stat1">Opportunities</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">300+</span>
                            <span class="stat-label" id="program2-stat2">Placements</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="program-card">
                <div class="program-image" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                </div>
                <div class="program-content">
                    <h3 id="program3-title">Mentorship Network</h3>
                    <p id="program3-text">Connecting young people with experienced professionals and successful entrepreneurs who provide guidance, advice, and support for personal and professional growth.</p>
                    <div class="program-stats">
                        <div class="stat">
                            <span class="stat-number">150+</span>
                            <span class="stat-label" id="program3-stat1">Mentors</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">400+</span>
                            <span class="stat-label" id="program3-stat2">Mentees</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Success Stories -->
    <section class="section success-stories">
        <h2 class="section-title" id="stories-title">Success Stories</h2>

        <div class="stories-grid">
            <div class="story-card">
                <p class="story-quote" id="story1-quote">"Bihak Center gave me the platform to share my innovation. Within months, I connected with investors who believed in my vision. Today, my startup employs 15 people!"</p>
                <div class="story-author">
                    <div class="author-avatar">A</div>
                    <div class="author-info">
                        <h4 id="story1-name">Amara Uwase</h4>
                        <p id="story1-title">Tech Entrepreneur, Rwanda</p>
                    </div>
                </div>
            </div>

            <div class="story-card">
                <p class="story-quote" id="story2-quote">"Through the Opportunities Hub, I found a fully-funded scholarship to study environmental science. This platform changed my life trajectory completely."</p>
                <div class="story-author">
                    <div class="author-avatar">J</div>
                    <div class="author-info">
                        <h4 id="story2-name">Jean Paul Nkunda</h4>
                        <p id="story2-title">Environmental Scientist, Kenya</p>
                    </div>
                </div>
            </div>

            <div class="story-card">
                <p class="story-quote" id="story3-quote">"The mentorship I received through Bihak was invaluable. My mentor helped me refine my business model and introduced me to key industry connections."</p>
                <div class="story-author">
                    <div class="author-avatar">G</div>
                    <div class="author-info">
                        <h4 id="story3-name">Grace Mutesi</h4>
                        <p id="story3-title">Social Entrepreneur, Uganda</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Impact Timeline -->
    <section class="section">
        <h2 class="section-title" id="timeline-title">Our Impact Journey</h2>

        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3 id="timeline1-year">2020 - Founded</h3>
                    <p id="timeline1-text">Bihak Center was established with a vision to empower young people across Africa by providing them with a platform to showcase their talents and access opportunities.</p>
                </div>
                <div class="timeline-dot"></div>
                <div></div>
            </div>

            <div class="timeline-item">
                <div></div>
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3 id="timeline2-year">2021 - First 100 Profiles</h3>
                    <p id="timeline2-text">Reached our first milestone with 100 young innovators sharing their inspiring stories. Launched the Opportunities Hub with 200+ curated opportunities.</p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <h3 id="timeline3-year">2022 - Regional Expansion</h3>
                    <p id="timeline3-text">Expanded to 10 African countries. Launched mentorship program connecting 150+ experienced professionals with young entrepreneurs and innovators.</p>
                </div>
                <div class="timeline-dot"></div>
                <div></div>
            </div>

            <div class="timeline-item">
                <div></div>
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3 id="timeline4-year">2023 - 500+ Success Stories</h3>
                    <p id="timeline4-text">Celebrated 500+ profiles on our platform. Facilitated 300+ placements through scholarships, internships, and job opportunities. Reached 50,000+ monthly visitors.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2 id="cta-title">Be Part of Our Success Story</h2>
        <p id="cta-text">Join our community of young innovators making a difference.</p>
        <a href="signup.php" class="btn">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
            </svg>
            <span id="cta-btn">Share Your Story Today</span>
        </a>
    </section>

    <?php include '../includes/footer_new.php'; ?>

    <script src="../assets/js/header_new.js"></script>
    <script>
        // Work page translations
        const workTranslations = {
            en: {
                'hero-title': 'Making Impact Through Action',
                'hero-subtitle': 'Discover our programs and the tangible difference we\'re making in the lives of young people across Africa.',
                'programs-title': 'Our Programs',
                'program1-title': 'Talent Showcase Platform',
                'program1-text': 'A digital platform where young innovators, artists, entrepreneurs, and change-makers can share their inspiring stories and showcase their achievements to a global audience.',
                'program2-title': 'Opportunities Hub',
                'program2-text': 'Curated opportunities including scholarships, internships, grants, and jobs specifically tailored for young people to grow their careers and pursue their dreams.',
                'program3-title': 'Mentorship Network',
                'program3-text': 'Connecting young people with experienced professionals and successful entrepreneurs who provide guidance, advice, and support for personal and professional growth.',
                'stories-title': 'Success Stories',
                'timeline-title': 'Our Impact Journey',
                'cta-title': 'Be Part of Our Success Story',
                'cta-text': 'Join our community of young innovators making a difference.',
                'cta-btn': 'Share Your Story Today'
            },
            fr: {
                'hero-title': 'Créer un Impact par l\'Action',
                'hero-subtitle': 'Découvrez nos programmes et la différence tangible que nous faisons dans la vie des jeunes à travers l\'Afrique.',
                'programs-title': 'Nos Programmes',
                'program1-title': 'Plateforme de Mise en Valeur des Talents',
                'program1-text': 'Une plateforme numérique où les jeunes innovateurs, artistes, entrepreneurs et acteurs du changement peuvent partager leurs histoires inspirantes et présenter leurs réalisations à un public mondial.',
                'program2-title': 'Hub des Opportunités',
                'program2-text': 'Opportunités organisées incluant des bourses, stages, subventions et emplois spécifiquement adaptés aux jeunes pour développer leurs carrières et poursuivre leurs rêves.',
                'program3-title': 'Réseau de Mentorat',
                'program3-text': 'Connecter les jeunes avec des professionnels expérimentés et des entrepreneurs à succès qui fournissent des conseils, des orientations et un soutien pour la croissance personnelle et professionnelle.',
                'stories-title': 'Histoires de Réussite',
                'timeline-title': 'Notre Parcours d\'Impact',
                'cta-title': 'Faites Partie de Notre Histoire de Réussite',
                'cta-text': 'Rejoignez notre communauté de jeunes innovateurs qui font la différence.',
                'cta-btn': 'Partagez Votre Histoire Aujourd\'hui'
            }
        };

        document.addEventListener('languageChanged', function(e) {
            const lang = e.detail.language;
            const translations = workTranslations[lang];
            if (translations) {
                Object.keys(translations).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) element.textContent = translations[key];
                });
            }
        });
    </script>
</body>
</html>
