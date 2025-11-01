<?php
/**
 * Get Involved Page
 * For mentors, donors, sponsors, and volunteers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $role_type = $_POST['role_type'] ?? '';
        $expertise_domain = trim($_POST['expertise_domain'] ?? '');
        $involvement_areas = isset($_POST['involvement_areas']) ? implode(',', $_POST['involvement_areas']) : '';
        $message = trim($_POST['message'] ?? '');
        $availability = trim($_POST['availability'] ?? '');
        $preferred_contact = $_POST['preferred_contact'] ?? 'email';
        $linkedin_url = trim($_POST['linkedin_url'] ?? '');
        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $twitter_url = trim($_POST['twitter_url'] ?? '');

        // Validation
        if (empty($full_name) || empty($email) || empty($role_type)) {
            $error = 'Please fill in all required fields (Name, Email, and Role).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Insert into database
            $conn = getDatabaseConnection();

            $stmt = $conn->prepare("
                INSERT INTO sponsors (
                    full_name, email, phone, organization, website, country, city,
                    role_type, expertise_domain, involvement_areas, message,
                    availability, preferred_contact, linkedin_url, facebook_url, twitter_url,
                    status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->bind_param(
                'ssssssssssssssss',
                $full_name, $email, $phone, $organization, $website, $country, $city,
                $role_type, $expertise_domain, $involvement_areas, $message,
                $availability, $preferred_contact, $linkedin_url, $facebook_url, $twitter_url
            );

            if ($stmt->execute()) {
                $success = 'Thank you for your interest! Your submission has been received and will be reviewed by our team.';
                // Clear form data
                $_POST = [];
            } else {
                $error = 'An error occurred. Please try again later.';
            }

            $stmt->close();
            closeDatabaseConnection($conn);
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
    <title>Get Involved - Bihak Center</title>
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
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
        }

        /* Form Section */
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-section h2 {
            color: #1cabe2;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .form-section .subtitle {
            color: #718096;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2d3748;
        }

        .form-group label .required {
            color: #f56565;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1cabe2;
            box-shadow: 0 0 0 3px rgba(28, 171, 226, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Checkbox Group */
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 400;
        }

        /* Radio Group */
        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .radio-item:hover {
            border-color: #1cabe2;
            background: #f0f9ff;
        }

        .radio-item input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
        }

        .radio-item input[type="radio"]:checked + label {
            color: #1cabe2;
            font-weight: 600;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        /* Button */
        .btn {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(28, 171, 226, 0.3);
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .sidebar-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .sidebar-card h3 {
            color: #1cabe2;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }

        .sidebar-card p {
            color: #718096;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        /* Donation Section */
        .donation-card {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            text-align: center;
        }

        .donation-card h3 {
            color: white;
        }

        .donation-card p {
            color: rgba(255, 255, 255, 0.95);
        }

        .paypal-button-container {
            margin-top: 20px;
        }

        /* Donation Stats */
        .donation-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .donation-stat-item {
            text-align: center;
        }

        .donation-stat-item strong {
            display: block;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 4px;
        }

        .donation-stat-item span {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Impact Link */
        .impact-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .impact-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .impact-link svg {
            flex-shrink: 0;
        }

        /* Impact Stats */
        .impact-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 8px;
        }

        .stat-box strong {
            display: block;
            font-size: 1.8rem;
            color: #1cabe2;
            margin-bottom: 5px;
        }

        .stat-box span {
            font-size: 0.9rem;
            color: #718096;
        }

        /* Ways to Help */
        .ways-list {
            list-style: none;
            margin-top: 15px;
        }

        .ways-list li {
            padding: 12px 0;
            padding-left: 30px;
            position: relative;
            color: #4a5568;
        }

        .ways-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #1cabe2;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .form-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/header_new.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Get Involved</h1>
        <p>Join us in empowering young people to reach their full potential. Whether you're a mentor, donor, or sponsor, your contribution makes a difference.</p>
    </section>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="content-grid">
            <!-- Application Form -->
            <div class="form-section">
                <h2>Join Our Community</h2>
                <p class="subtitle">Fill out the form below to get involved with Bihak Center</p>

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

                    <!-- Personal Information -->
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Organization</label>
                            <input type="text" name="organization" class="form-control" value="<?php echo htmlspecialchars($_POST['organization'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" class="form-control" placeholder="https://" value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Role Selection -->
                    <div class="form-group">
                        <label>I want to be a <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="role_type" value="mentor" id="role_mentor" required <?php echo (isset($_POST['role_type']) && $_POST['role_type'] === 'mentor') ? 'checked' : ''; ?>>
                                <label for="role_mentor">Mentor</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="role_type" value="donor" id="role_donor" <?php echo (isset($_POST['role_type']) && $_POST['role_type'] === 'donor') ? 'checked' : ''; ?>>
                                <label for="role_donor">Donor</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="role_type" value="sponsor" id="role_sponsor" <?php echo (isset($_POST['role_type']) && $_POST['role_type'] === 'sponsor') ? 'checked' : ''; ?>>
                                <label for="role_sponsor">Sponsor</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="role_type" value="volunteer" id="role_volunteer" <?php echo (isset($_POST['role_type']) && $_POST['role_type'] === 'volunteer') ? 'checked' : ''; ?>>
                                <label for="role_volunteer">Volunteer</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="role_type" value="partner" id="role_partner" <?php echo (isset($_POST['role_type']) && $_POST['role_type'] === 'partner') ? 'checked' : ''; ?>>
                                <label for="role_partner">Partner</label>
                            </div>
                        </div>
                    </div>

                    <!-- Expertise Domain -->
                    <div class="form-group">
                        <label>Area of Expertise</label>
                        <input type="text" name="expertise_domain" class="form-control" placeholder="e.g., Software Engineering, Business, Education" value="<?php echo htmlspecialchars($_POST['expertise_domain'] ?? ''); ?>">
                    </div>

                    <!-- Involvement Areas -->
                    <div class="form-group">
                        <label>How would you like to help?</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="coaching" id="area_coaching">
                                <label for="area_coaching">Coaching</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="mentoring" id="area_mentoring">
                                <label for="area_mentoring">Mentoring</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="funding" id="area_funding">
                                <label for="area_funding">Funding Projects</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="talent_support" id="area_talent">
                                <label for="area_talent">Supporting Talents</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="internships" id="area_internships">
                                <label for="area_internships">Internships</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="equipment" id="area_equipment">
                                <label for="area_equipment">Equipment/Resources</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="networking" id="area_networking">
                                <label for="area_networking">Networking</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="involvement_areas[]" value="workshops" id="area_workshops">
                                <label for="area_workshops">Workshops/Training</label>
                            </div>
                        </div>
                    </div>

                    <!-- Availability -->
                    <div class="form-group">
                        <label>Availability</label>
                        <select name="availability" class="form-control">
                            <option value="">Select your availability</option>
                            <option value="weekly">Weekly</option>
                            <option value="bi-weekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="one-time">One-time</option>
                            <option value="flexible">Flexible</option>
                        </select>
                    </div>

                    <!-- Preferred Contact -->
                    <div class="form-group">
                        <label>Preferred Contact Method</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="preferred_contact" value="email" id="contact_email" checked>
                                <label for="contact_email">Email</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="preferred_contact" value="phone" id="contact_phone">
                                <label for="contact_phone">Phone</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="preferred_contact" value="both" id="contact_both">
                                <label for="contact_both">Both</label>
                            </div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="form-group">
                        <label>Tell us more about your interest</label>
                        <textarea name="message" class="form-control" placeholder="Share your motivations, goals, or any specific ideas you have..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <!-- Social Media (Optional) -->
                    <div class="form-group">
                        <label>LinkedIn Profile (Optional)</label>
                        <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/yourprofile" value="<?php echo htmlspecialchars($_POST['linkedin_url'] ?? ''); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Facebook (Optional)</label>
                            <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/yourprofile" value="<?php echo htmlspecialchars($_POST['facebook_url'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Twitter (Optional)</label>
                            <input type="url" name="twitter_url" class="form-control" placeholder="https://twitter.com/yourhandle" value="<?php echo htmlspecialchars($_POST['twitter_url'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn">Submit Your Interest</button>
                </form>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Donation Card -->
                <div class="sidebar-card donation-card">
                    <h3>Make a Donation</h3>
                    <p>Your donation directly supports young people in achieving their dreams.</p>
                    <p style="font-size: 1.1rem; font-weight: 600; margin: 15px 0; opacity: 1;">Donate as little as $1 to make a difference in someone's life</p>

                    <!-- PayPal Button with IPN Tracking -->
                    <div class="paypal-button-container">
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                            <!-- PayPal Configuration -->
                            <input type="hidden" name="cmd" value="_donations">
                            <input type="hidden" name="business" value="jijiniyo@gmail.com">
                            <input type="hidden" name="item_name" value="Support Bihak Center Youth Programs">
                            <input type="hidden" name="item_number" value="BIHAK-DONATION">
                            <input type="hidden" name="currency_code" value="USD">

                            <!-- IPN Notification URL - REPLACE WITH YOUR ACTUAL DOMAIN -->
                            <input type="hidden" name="notify_url" value="https://yourdomain.com/api/paypal-ipn.php">

                            <!-- Return URLs -->
                            <input type="hidden" name="return" value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/public/donation-success.php">
                            <input type="hidden" name="cancel_return" value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/public/get-involved.php">

                            <!-- Allow user to enter custom amount -->
                            <input type="hidden" name="no_shipping" value="1">
                            <input type="hidden" name="no_note" value="0">
                            <input type="hidden" name="lc" value="US">
                            <input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHosted">

                            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button">
                        </form>
                    </div>

                    <!-- Donation Stats - Real-time -->
                    <div class="donation-stats">
                        <div class="donation-stat-item">
                            <strong id="total-raised-display">$0</strong>
                            <span>Raised This Year</span>
                        </div>
                        <div class="donation-stat-item">
                            <strong id="unique-donors-display">0</strong>
                            <span>Generous Donors</span>
                        </div>
                    </div>

                    <!-- Impact Link -->
                    <a href="donation-impact.php" class="impact-link">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                        How Your Donation Changes Lives
                    </a>
                </div>

                <!-- Impact Stats -->
                <div class="sidebar-card">
                    <h3>Our Impact</h3>
                    <p>Together, we're making a difference:</p>
                    <div class="impact-stats">
                        <div class="stat-box">
                            <strong>50+</strong>
                            <span>Youth Supported</span>
                        </div>
                        <div class="stat-box">
                            <strong>30+</strong>
                            <span>Mentors</span>
                        </div>
                        <div class="stat-box">
                            <strong>20+</strong>
                            <span>Projects Funded</span>
                        </div>
                        <div class="stat-box">
                            <strong>100%</strong>
                            <span>Impact Rate</span>
                        </div>
                    </div>
                </div>

                <!-- Ways to Help -->
                <div class="sidebar-card">
                    <h3>Ways to Help</h3>
                    <ul class="ways-list">
                        <li>Mentor young people in your field</li>
                        <li>Provide financial support for education</li>
                        <li>Offer internship opportunities</li>
                        <li>Donate equipment or resources</li>
                        <li>Share your professional network</li>
                        <li>Conduct workshops and training</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer_new.php'; ?>

    <!-- Scroll to Top -->
    <button id="myBtn" aria-label="Scroll to top">↑</button>
    <script src="../assets/js/scroll-to-top.js"></script>

    <!-- Real-time Donation Stats -->
    <script>
        // Fetch real-time donation statistics
        fetch('../api/donation-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update raised this year
                    const raisedDisplay = document.getElementById('total-raised-display');
                    if (raisedDisplay) {
                        const amount = data.raised_this_year || 0;
                        raisedDisplay.textContent = '$' + amount.toLocaleString('en-US', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }) + (amount > 0 ? '+' : '');
                    }

                    // Update unique donors
                    const donorsDisplay = document.getElementById('unique-donors-display');
                    if (donorsDisplay) {
                        const donors = data.unique_donors || 0;
                        donorsDisplay.textContent = donors + (donors > 0 ? '+' : '');
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching donation stats:', error);
                // Keep default values on error
            });
    </script>
</body>
</html>
