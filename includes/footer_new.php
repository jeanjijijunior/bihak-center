<!-- Modern Footer -->
<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h3>Discover Our Programs</h3>
            <ul>
                <li><a href="work.php#orientation">Academic & Professional Orientation</a></li>
                <li><a href="work.php#coaching">Project Development Coaching</a></li>
                <li><a href="work.php#financial">Financial Support</a></li>
                <li><a href="work.php#technology">Technology for Development</a></li>
            </ul>
        </div>

        <div class="about-us">
            <h3>About Us</h3>
            <ul>
                <li><a href="about.php#vision">Our Vision</a></li>
                <li><a href="about.php#mission">Our Mission</a></li>
                <li><a href="about.php#motivation">Our Motivation</a></li>
                <li><a href="contact.php">Contact Us</a></li>
            </ul>
        </div>

        <div class="social-links">
            <h3>Follow Us</h3>
            <ul>
                <li>
                    <a href="https://facebook.com/bihakcenter" target="_blank" rel="noopener noreferrer">
                        <img src="assets/images/facebook-icon.png" alt="Facebook">
                        <span>Bihak Center</span>
                    </a>
                </li>
                <li>
                    <a href="https://instagram.com/bihakcenter" target="_blank" rel="noopener noreferrer">
                        <img src="assets/images/instagram-icon.png" alt="Instagram">
                        <span>Bihak Center</span>
                    </a>
                </li>
                <li>
                    <a href="https://twitter.com/bihak_center" target="_blank" rel="noopener noreferrer">
                        <img src="assets/images/twitter-icon.png" alt="Twitter/X">
                        <span>@bihak_center</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Bihak Center | All Rights Reserved | Empowering Youth, Building Futures</p>
    </div>
</footer>

<style>
    /* Modern Footer Styles */
    footer {
        background: linear-gradient(135deg, #0d4d6b 0%, #1cabe2 50%, #147ba5 100%);
        color: white;
        padding: 0;
        position: relative;
        overflow: hidden;
    }

    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 25%, #1cabe2 50%, #147ba5 75%, #fbbf24 100%);
        animation: shimmer 3s linear infinite;
        background-size: 200% 100%;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    .footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 50px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 80px 40px 40px;
        position: relative;
    }

    .footer-section, .about-us, .social-links {
        position: relative;
    }

    .footer-section h3, .about-us h3, .social-links h3 {
        font-size: 1.4rem;
        margin-bottom: 25px;
        font-weight: 700;
        position: relative;
        padding-bottom: 15px;
    }

    .footer-section h3::after, .about-us h3::after, .social-links h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
        border-radius: 2px;
    }

    .footer-section ul, .about-us ul, .social-links ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-section ul li, .about-us ul li {
        margin-bottom: 14px;
    }

    .social-links ul {
        margin-top: 10px;
    }

    .social-links ul li {
        margin-bottom: 18px;
    }

    .footer-section a, .about-us a, .social-links a {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 1rem;
        position: relative;
        padding-left: 20px;
    }

    .footer-section a::before, .about-us a::before {
        content: '→';
        position: absolute;
        left: 0;
        opacity: 0;
        transform: translateX(-10px);
        transition: all 0.3s ease;
        color: #fbbf24;
        font-weight: bold;
    }

    .footer-section a:hover, .about-us a:hover, .social-links a:hover {
        color: white;
        padding-left: 25px;
        transform: translateX(5px);
    }

    .footer-section a:hover::before, .about-us a:hover::before {
        opacity: 1;
        transform: translateX(0);
    }

    .social-links a {
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 18px;
        border-radius: 50px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .social-links a:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px) translateX(0);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        padding-left: 20px;
    }

    .social-links img {
        width: 28px;
        height: 28px;
        filter: brightness(0) invert(1);
        transition: transform 0.3s ease;
    }

    .social-links a:hover img {
        transform: scale(1.15) rotate(5deg);
    }

    .footer-bottom {
        background: rgba(0, 0, 0, 0.2);
        padding: 30px 40px;
        text-align: center;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .footer-bottom p {
        margin: 0;
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.8);
        letter-spacing: 0.5px;
    }

    /* Responsive Footer Styles */
    @media (max-width: 768px) {
        .footer-container {
            grid-template-columns: 1fr;
            gap: 40px;
            padding: 60px 30px 30px;
            text-align: center;
        }

        .footer-section h3::after, .about-us h3::after, .social-links h3::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .footer-section a::before, .about-us a::before {
            display: none;
        }

        .footer-section a, .about-us a {
            justify-content: center;
            padding-left: 0;
        }

        .footer-section a:hover, .about-us a:hover {
            padding-left: 0;
            transform: translateX(0) translateY(-2px);
        }

        .social-links a {
            justify-content: center;
        }

        .footer-bottom {
            padding: 25px 30px;
        }
    }

    @media (max-width: 480px) {
        .footer-container {
            padding: 50px 20px 20px;
            gap: 35px;
        }

        .footer-section h3, .about-us h3, .social-links h3 {
            font-size: 1.2rem;
        }

        .footer-section a, .about-us a, .social-links a {
            font-size: 0.95rem;
        }

        .footer-bottom {
            padding: 20px;
        }

        .footer-bottom p {
            font-size: 0.85rem;
        }
    }
</style>
