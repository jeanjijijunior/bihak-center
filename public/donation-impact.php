<?php
/**
 * Donation Impact Page
 * Shows how donations are used and testimonials from beneficiaries
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How Your Donation Changes Lives - Bihak Center</title>
    <link rel="icon" type="image/png" href="../assets/images/favimg.png">
    <link rel="stylesheet" type="text/css" href="../assets/css/header_new.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Poppins', sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: #f7fafc;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 80px 20px 60px;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.95;
        }

        /* Main Content */
        .content-wrapper {
            max-width: 1200px;
            margin: -40px auto 60px;
            padding: 0 20px;
        }

        /* Impact Overview */
        .impact-overview {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .impact-overview h2 {
            color: #1cabe2;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .impact-overview p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
            margin-bottom: 15px;
        }

        /* Impact Areas Grid */
        .impact-areas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }

        .impact-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .impact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .impact-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .impact-card h3 {
            color: #2d3748;
            font-size: 1.3rem;
            margin-bottom: 12px;
        }

        .impact-card p {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .impact-amount {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: #f0f9ff;
            color: #1cabe2;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Testimonials Section */
        .testimonials-section {
            margin: 60px 0;
        }

        .testimonials-section h2 {
            text-align: center;
            color: #1cabe2;
            font-size: 2rem;
            margin-bottom: 40px;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .testimonial-quote {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 4rem;
            color: #f59e0b;
            opacity: 0.2;
            line-height: 1;
        }

        .testimonial-text {
            font-size: 1.05rem;
            font-style: italic;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .author-info {
            flex: 1;
        }

        .author-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.05rem;
        }

        .author-detail {
            font-size: 0.9rem;
            color: #718096;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            border-radius: 15px;
            padding: 50px 40px;
            color: white;
            margin: 40px 0;
            text-align: center;
        }

        .stats-section h2 {
            color: white;
            font-size: 2rem;
            margin-bottom: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }

        .stat-item {
            padding: 20px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.95;
        }

        /* CTA Section */
        .cta-section {
            background: white;
            border-radius: 15px;
            padding: 50px 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: 40px 0;
        }

        .cta-section h2 {
            color: #2d3748;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 30px;
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
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .impact-overview {
                padding: 30px 20px;
            }

            .testimonial-card {
                padding: 25px;
            }

            .stats-section {
                padding: 40px 20px;
            }

            .cta-section {
                padding: 40px 20px;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1>How Your Donation Changes Lives</h1>
        <p>Every dollar you give creates real, lasting impact in the lives of young people pursuing their dreams.</p>
    </section>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Impact Overview -->
        <div class="impact-overview">
            <h2>Where Your Money Goes</h2>
            <p>At Bihak Center, we believe in complete transparency. 100% of your donation goes directly to supporting young people in Rwanda. We don't take administrative fees - every dollar creates impact.</p>
            <p>Your contribution helps break the cycle of poverty by providing opportunities that would otherwise be out of reach. From school fees to startup funding, your generosity transforms dreams into reality.</p>
        </div>

        <!-- Impact Areas -->
        <div class="impact-areas">
            <div class="impact-card">
                <div class="impact-icon" style="background: #dbeafe; color: #1e40af;">
                    <svg width="32" height="32" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                </div>
                <h3>School Fees & Education</h3>
                <p>Covering tuition, books, and supplies for students who can't afford to continue their education.</p>
                <span class="impact-amount">$500/year per student</span>
            </div>

            <div class="impact-card">
                <div class="impact-icon" style="background: #fef3c7; color: #b45309;">
                    <svg width="32" height="32" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                    </svg>
                </div>
                <h3>Uniforms & Supplies</h3>
                <p>Providing school uniforms, shoes, and essential learning materials for students in need.</p>
                <span class="impact-amount">$50/student</span>
            </div>

            <div class="impact-card">
                <div class="impact-icon" style="background: #dcfce7; color: #15803d;">
                    <svg width="32" height="32" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <h3>Project Implementation</h3>
                <p>Funding student projects, research, and practical learning experiences that build skills.</p>
                <span class="impact-amount">$200-$1,000/project</span>
            </div>

            <div class="impact-card">
                <div class="impact-icon" style="background: #ede9fe; color: #6b21a8;">
                    <svg width="32" height="32" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>Skills Training</h3>
                <p>Workshops, mentorship programs, and vocational training to develop marketable skills.</p>
                <span class="impact-amount">$100/workshop</span>
            </div>

            <div class="impact-card">
                <div class="impact-icon" style="background: #fee2e2; color: #991b1b;">
                    <svg width="32" height="32" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>Startup Promotion</h3>
                <p>Seed funding, business mentorship, and resources to help young entrepreneurs launch their ventures.</p>
                <span class="impact-amount">$500-$2,000/startup</span>
            </div>

            <div class="impact-card">
                <div class="impact-icon" style="background: #fce7f3; color: #9f1239;">
                    <svg width="32" height="32" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762z"/>
                    </svg>
                </div>
                <h3>Talent Support</h3>
                <p>Supporting young talents in arts, sports, technology, and other fields to reach their potential.</p>
                <span class="impact-amount">$300-$800/talent</span>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <h2>Our Impact in Numbers</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">$250K+</span>
                    <span class="stat-label">Total Funds Raised</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">200+</span>
                    <span class="stat-label">Students Supported</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Projects Funded</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">25+</span>
                    <span class="stat-label">Startups Launched</span>
                </div>
            </div>
        </div>

        <!-- Testimonials -->
        <div class="testimonials-section">
            <h2>Stories of Impact</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <span class="testimonial-quote">"</span>
                    <p class="testimonial-text">Thanks to Bihak Center, I was able to complete my university education. The scholarship covered my tuition and books. Today, I'm working as a software engineer and giving back to help other students like me.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">JC</div>
                        <div class="author-info">
                            <div class="author-name">Jean Claude M.</div>
                            <div class="author-detail">Software Engineer, 2022 Graduate</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <span class="testimonial-quote">"</span>
                    <p class="testimonial-text">The startup funding I received helped me launch my eco-friendly packaging business. Within a year, we created 15 jobs and are now supplying major retailers. This wouldn't have been possible without the support.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">AM</div>
                        <div class="author-info">
                            <div class="author-name">Aline Mukaruziga</div>
                            <div class="author-detail">Entrepreneur, GreenPack Rwanda</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <span class="testimonial-quote">"</span>
                    <p class="testimonial-text">I couldn't afford school uniforms and supplies. Bihak Center provided everything I needed. Now I can focus on my studies without worrying. I dream of becoming a doctor and helping my community.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">DN</div>
                        <div class="author-info">
                            <div class="author-name">Divine Niyonzima</div>
                            <div class="author-detail">High School Student, Class of 2025</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <span class="testimonial-quote">"</span>
                    <p class="testimonial-text">The mentorship and training programs gave me the confidence and skills I needed. I learned web development and now I'm freelancing, supporting my family while pursuing my degree.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">EP</div>
                        <div class="author-info">
                            <div class="author-name">Eric Ntwali</div>
                            <div class="author-detail">Web Developer & Student</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <span class="testimonial-quote">"</span>
                    <p class="testimonial-text">As a young artist, I didn't think I could make a living from my passion. The talent support program gave me materials, training, and connections. My artwork is now in galleries across East Africa.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">SU</div>
                        <div class="author-info">
                            <div class="author-name">Sarah Uwase</div>
                            <div class="author-detail">Visual Artist</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <span class="testimonial-quote">"</span>
                    <p class="testimonial-text">The project funding allowed me to build a prototype of my solar-powered irrigation system. It won a national innovation award and now I'm working with agricultural communities to scale the solution.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">PM</div>
                        <div class="author-info">
                            <div class="author-name">Patrick Mugabo</div>
                            <div class="author-detail">Engineer & Innovator</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Join Us in Changing Lives</h2>
            <p>Your donation, no matter the size, creates real impact. Even $1 can make a difference.</p>
            <div class="cta-buttons">
                <a href="get-involved.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                    </svg>
                    Donate Now
                </a>
                <a href="get-involved.php#mentor-form" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    Become a Mentor
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer_new.php'; ?>

    <!-- Scroll to Top -->
    <button id="myBtn" aria-label="Scroll to top">↑</button>
    <script src="../assets/js/scroll-to-top.js"></script>
</body>
</html>
