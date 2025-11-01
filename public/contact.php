<?php
/**
 * Contact Page - Get in Touch
 * Contact form, location map, and social media links
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

$success = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = Security::sanitizeInput($_POST['name'] ?? '', 'string');
        $email = Security::sanitizeInput($_POST['email'] ?? '', 'email');
        $subject = Security::sanitizeInput($_POST['subject'] ?? '', 'string');
        $message = Security::sanitizeInput($_POST['message'] ?? '', 'string');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Save contact submission to database
            try {
                $conn = getDatabaseConnection();
                $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, subject, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $stmt->bind_param("ssssss", $name, $email, $subject, $message, $ip_address, $user_agent);

                if ($stmt->execute()) {
                    $success = 'Thank you for your message! We\'ll get back to you soon.';

                    // Log the contact attempt
                    Security::logSecurityEvent('contact_form_submitted', [
                        'name' => $name,
                        'email' => $email,
                        'subject' => $subject
                    ]);
                } else {
                    $error = 'An error occurred. Please try again later.';
                }

                $stmt->close();
                closeDatabaseConnection($conn);
            } catch (Exception $e) {
                error_log("Contact form error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Bihak Center</title>
    <meta name="description" content="Get in touch with Bihak Center. We're here to help young people achieve their dreams.">
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
            background: #f7fafc;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 80px 20px 60px;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Contact Container */
        .contact-container {
            max-width: 1200px;
            margin: -40px auto 80px;
            padding: 0 20px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .contact-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .contact-card h2 {
            font-size: 1.8rem;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .contact-card > p {
            color: #718096;
            margin-bottom: 30px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #1cabe2;
            box-shadow: 0 0 0 3px rgba(28, 171, 226, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #1cabe2, #147ba5);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(28, 171, 226, 0.4);
        }

        /* Contact Info */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .info-item {
            display: flex;
            gap: 15px;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1cabe2, #147ba5);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }

        .info-content h3 {
            font-size: 1.1rem;
            color: #1a202c;
            margin-bottom: 5px;
        }

        .info-content p {
            color: #718096;
            font-size: 0.95rem;
        }

        .info-content a {
            color: #1cabe2;
            text-decoration: none;
        }

        .info-content a:hover {
            text-decoration: underline;
        }

        /* Social Links */
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .social-link {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #f7fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1cabe2;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-link:hover {
            background: linear-gradient(135deg, #1cabe2, #147ba5);
            color: white;
            transform: translateY(-3px);
        }

        /* Map Section */
        .map-section {
            grid-column: 1 / -1;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 16px;
            overflow: hidden;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* FAQ Section */
        .faq-section {
            max-width: 800px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .faq-section h2 {
            font-size: 2rem;
            color: #1a202c;
            text-align: center;
            margin-bottom: 40px;
        }

        .faq-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .faq-question {
            padding: 20px 25px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #1a202c;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: #f7fafc;
        }

        .faq-question svg {
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-question svg {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-item.active .faq-answer {
            max-height: 500px;
        }

        .faq-answer p {
            padding: 0 25px 20px;
            color: #718096;
            line-height: 1.7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .contact-card {
                padding: 30px 25px;
            }

            .map-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1 id="hero-title">Get in Touch</h1>
        <p id="hero-subtitle">We're here to help. Send us a message and we'll respond as soon as possible.</p>
    </section>

    <!-- Contact Container -->
    <div class="contact-container">
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="contact-card">
                <h2 id="form-title">Send Us a Message</h2>
                <p id="form-subtitle">Fill out the form below and we'll get back to you within 24 hours.</p>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="name" id="label-name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email" id="label-email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject" id="label-subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" required
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="message" id="label-message">Message</label>
                        <textarea id="message" name="message" class="form-control" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span id="btn-submit">Send Message</span>
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="contact-card">
                <h2 id="info-title">Contact Information</h2>
                <p id="info-subtitle">Reach out to us through any of these channels.</p>

                <div class="contact-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3 id="info-email-title">Email</h3>
                            <p><a href="mailto:info@bihakcenter.org">info@bihakcenter.org</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3 id="info-phone-title">Phone</h3>
                            <p><a href="tel:+250788000000">+250 788 000 000</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3 id="info-address-title">Address</h3>
                            <p id="info-address">Kigali, Rwanda<br>KG 123 St</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3 id="info-hours-title">Office Hours</h3>
                            <p id="info-hours">Monday - Friday: 9:00 AM - 5:00 PM<br>Saturday - Sunday: Closed</p>
                        </div>
                    </div>
                </div>

                <div class="social-links">
                    <a href="https://facebook.com/bihakcenter" class="social-link" title="Facebook" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="https://twitter.com/bihakcenter" class="social-link" title="Twitter" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com/bihakcenter" class="social-link" title="Instagram" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                        </svg>
                    </a>
                    <a href="https://linkedin.com/company/bihakcenter" class="social-link" title="LinkedIn" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Map Section -->
            <div class="contact-card map-section">
                <h2 id="map-title">Find Us</h2>
                <div class="map-container">
                    <!-- Google Maps Embed - Replace with actual coordinates -->
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31899.434024265964!2d30.058611486706543!3d-1.9535800000000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19dca4258ed8e797%3A0x4280b7162f8c799a!2sKigali%2C%20Rwanda!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h2 id="faq-title">Frequently Asked Questions</h2>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span id="faq1-q">How can I submit my profile?</span>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="faq-answer">
                <p id="faq1-a">Click on "Share Your Story" in the navigation menu and fill out the profile submission form with your information, story, and achievements. Our team will review your submission within 3-5 business days.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span id="faq2-q">Are the opportunities free to apply?</span>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="faq-answer">
                <p id="faq2-a">Yes! All opportunities listed on our platform are free to access and apply. We curate legitimate opportunities from trusted sources to help young people grow and succeed.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span id="faq3-q">How long does profile approval take?</span>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="faq-answer">
                <p id="faq3-a">Our team typically reviews and approves profiles within 3-5 business days. You'll receive an email notification once your profile is reviewed, whether it's approved or if we need additional information.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span id="faq4-q">Can I edit my profile after submission?</span>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="faq-answer">
                <p id="faq4-a">Yes, once your profile is approved, you can log in to your account and update your information, add new achievements, or upload new photos. Changes will need to be re-reviewed for approval.</p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer_new.php'; ?>

    <script src="../assets/js/header_new.js"></script>
    <script>
        function toggleFAQ(element) {
            const faqItem = element.parentElement;
            faqItem.classList.toggle('active');
        }

        // Contact page translations
        const contactTranslations = {
            en: {
                'hero-title': 'Get in Touch',
                'hero-subtitle': 'We\'re here to help. Send us a message and we\'ll respond as soon as possible.',
                'form-title': 'Send Us a Message',
                'info-title': 'Contact Information',
                'map-title': 'Find Us',
                'faq-title': 'Frequently Asked Questions',
                'btn-submit': 'Send Message'
            },
            fr: {
                'hero-title': 'Contactez-Nous',
                'hero-subtitle': 'Nous sommes là pour vous aider. Envoyez-nous un message et nous vous répondrons dès que possible.',
                'form-title': 'Envoyez-Nous un Message',
                'info-title': 'Informations de Contact',
                'map-title': 'Trouvez-Nous',
                'faq-title': 'Questions Fréquemment Posées',
                'btn-submit': 'Envoyer le Message'
            }
        };

        document.addEventListener('languageChanged', function(e) {
            const lang = e.detail.language;
            const translations = contactTranslations[lang];
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
