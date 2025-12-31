<?php
/**
 * Signup Page - Profile Creation Form
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();

// Get available security questions
$conn = getDatabaseConnection();
$questionsStmt = $conn->query("SELECT id, question_text FROM security_questions WHERE is_active = 1 ORDER BY display_order");
$available_questions = $questionsStmt->fetch_all(MYSQLI_ASSOC);
closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Join Bihak Center - Share your story and get support for your dreams">
    <title>Sign Up - Bihak Center</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/favimg.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/header_new.css">

    <!-- Google Fonts -->
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
            background: #f5f7fa;
        }

        /* Signup Container */
        .signup-container {
            max-width: 900px;
            margin: 120px auto 60px; /* Top margin for header spacing */
            padding: 40px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        /* Header */
        .signup-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .signup-header h1 {
            font-size: 2.5rem;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .signup-header p {
            font-size: 1.1rem;
            color: #718096;
        }

        /* Messages */
        #message-container {
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert ul {
            margin: 10px 0 0 20px;
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 40px;
        }

        .form-section h2 {
            font-size: 1.5rem;
            color: #1a202c;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-description {
            color: #718096;
            margin-bottom: 20px;
        }

        /* Form Groups */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }

        .required {
            color: #e53e3e;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1cabe2;
            box-shadow: 0 0 0 3px rgba(28, 171, 226, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            font-size: 0.875rem;
            color: #718096;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* File Upload */
        .form-group input[type="file"] {
            padding: 10px;
            border: 2px dashed #e2e8f0;
            background: #f7fafc;
        }

        .form-group input[type="file"]:hover {
            border-color: #cbd5e0;
            background: #edf2f7;
        }

        /* Image Preview */
        .media-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .image-preview-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .remove-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            transition: background 0.2s;
        }

        .remove-image-btn:hover {
            background: #dc2626;
        }

        /* Checkboxes */
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .checkbox-group label {
            font-weight: 400;
            cursor: pointer;
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-submit,
        .btn-reset {
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(28, 171, 226, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-reset {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-reset:hover {
            background: #cbd5e0;
        }

        /* Loading State */
        .form-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-submit.loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-left: 10px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* After Signup Info */
        .after-signup-info {
            margin-top: 40px;
            padding: 30px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #1cabe2;
        }

        .after-signup-info h3 {
            color: #1a202c;
            margin-bottom: 15px;
        }

        .after-signup-info ol {
            margin-left: 20px;
        }

        .after-signup-info li {
            margin-bottom: 10px;
            color: #4a5568;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .signup-container {
                margin: 100px 15px 40px;
                padding: 25px 20px;
            }

            .signup-header h1 {
                font-size: 2rem;
            }

            .signup-header p {
                font-size: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-section h2 {
                font-size: 1.3rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-reset {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .signup-container {
                margin: 90px 10px 30px;
                padding: 20px 15px;
            }

            .signup-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header_new.php'; ?>

    <!-- Main Content -->
    <div class="signup-container">
        <div class="signup-header">
            <h1 data-translate="shareYourStory">Share your story</h1>
            <p data-translate="joinBihakCenter">Join Bihak Center and let us support your journey to success</p>
        </div>

        <div id="message-container"></div>

        <form id="signupForm" method="POST" action="process_signup.php" enctype="multipart/form-data" class="signup-form">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Personal Information -->
            <div class="form-section">
                <h2 data-translate="personalInformation">Personal information</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name"><span data-translate="fullName">Full Name</span> <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email"><span data-translate="emailAddress">Email Address</span> <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password"><span data-translate="password">Password</span> <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
                        <small style="color: #718096; font-size: 0.85rem;">Must be at least 8 characters long</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm"><span data-translate="confirmPassword">Confirm Password</span> <span class="required">*</span></label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                    </div>
                </div>

                <!-- Security Questions for Password Recovery -->
                <div class="form-section" style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h3 style="font-size: 1.2rem; color: #2d3748; margin-bottom: 10px;">Security Questions for Password Recovery</h3>
                    <p style="color: #718096; font-size: 0.9rem; margin-bottom: 20px;">Choose 3 security questions and provide answers. These will be used if you forget your password.</p>

                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="security_question_<?php echo $i; ?>">
                            <span>Security Question <?php echo $i; ?></span> <span class="required">*</span>
                        </label>
                        <select id="security_question_<?php echo $i; ?>" name="security_question_<?php echo $i; ?>" required>
                            <option value="">Select a security question</option>
                            <?php foreach ($available_questions as $question): ?>
                            <option value="<?php echo htmlspecialchars($question['id']); ?>">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label for="security_answer_<?php echo $i; ?>">
                            <span>Answer <?php echo $i; ?></span> <span class="required">*</span>
                        </label>
                        <input type="text" id="security_answer_<?php echo $i; ?>" name="security_answer_<?php echo $i; ?>" required placeholder="Enter your answer">
                        <small>Answers are not case-sensitive</small>
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone" data-translate="phoneNumber">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+250 788 123 456">
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth"><span data-translate="dateOfBirth">Date of Birth</span> <span class="required">*</span></label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender" data-translate="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                            <option value="Prefer not to say">Prefer not to say</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="form-section">
                <h2 data-translate="location">Location</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city"><span data-translate="city">City</span> <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required>
                    </div>

                    <div class="form-group">
                        <label for="district"><span data-translate="district">District</span> <span class="required">*</span></label>
                        <input type="text" id="district" name="district" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="country"><span data-translate="country">Country</span> <span class="required">*</span></label>
                        <select id="country" name="country" required>
                            <option value="">Select your country</option>
                            <option value="Afghanistan">Afghanistan</option>
                            <option value="Albania">Albania</option>
                            <option value="Algeria">Algeria</option>
                            <option value="Andorra">Andorra</option>
                            <option value="Angola">Angola</option>
                            <option value="Argentina">Argentina</option>
                            <option value="Armenia">Armenia</option>
                            <option value="Australia">Australia</option>
                            <option value="Austria">Austria</option>
                            <option value="Azerbaijan">Azerbaijan</option>
                            <option value="Bahamas">Bahamas</option>
                            <option value="Bahrain">Bahrain</option>
                            <option value="Bangladesh">Bangladesh</option>
                            <option value="Barbados">Barbados</option>
                            <option value="Belarus">Belarus</option>
                            <option value="Belgium">Belgium</option>
                            <option value="Belize">Belize</option>
                            <option value="Benin">Benin</option>
                            <option value="Bhutan">Bhutan</option>
                            <option value="Bolivia">Bolivia</option>
                            <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                            <option value="Botswana">Botswana</option>
                            <option value="Brazil">Brazil</option>
                            <option value="Brunei">Brunei</option>
                            <option value="Bulgaria">Bulgaria</option>
                            <option value="Burkina Faso">Burkina Faso</option>
                            <option value="Burundi">Burundi</option>
                            <option value="Cambodia">Cambodia</option>
                            <option value="Cameroon">Cameroon</option>
                            <option value="Canada">Canada</option>
                            <option value="Cape Verde">Cape Verde</option>
                            <option value="Central African Republic">Central African Republic</option>
                            <option value="Chad">Chad</option>
                            <option value="Chile">Chile</option>
                            <option value="China">China</option>
                            <option value="Colombia">Colombia</option>
                            <option value="Comoros">Comoros</option>
                            <option value="Congo">Congo</option>
                            <option value="Costa Rica">Costa Rica</option>
                            <option value="Croatia">Croatia</option>
                            <option value="Cuba">Cuba</option>
                            <option value="Cyprus">Cyprus</option>
                            <option value="Czech Republic">Czech Republic</option>
                            <option value="Denmark">Denmark</option>
                            <option value="Djibouti">Djibouti</option>
                            <option value="Dominica">Dominica</option>
                            <option value="Dominican Republic">Dominican Republic</option>
                            <option value="Ecuador">Ecuador</option>
                            <option value="Egypt">Egypt</option>
                            <option value="El Salvador">El Salvador</option>
                            <option value="Equatorial Guinea">Equatorial Guinea</option>
                            <option value="Eritrea">Eritrea</option>
                            <option value="Estonia">Estonia</option>
                            <option value="Ethiopia">Ethiopia</option>
                            <option value="Fiji">Fiji</option>
                            <option value="Finland">Finland</option>
                            <option value="France">France</option>
                            <option value="Gabon">Gabon</option>
                            <option value="Gambia">Gambia</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Germany">Germany</option>
                            <option value="Ghana">Ghana</option>
                            <option value="Greece">Greece</option>
                            <option value="Grenada">Grenada</option>
                            <option value="Guatemala">Guatemala</option>
                            <option value="Guinea">Guinea</option>
                            <option value="Guinea-Bissau">Guinea-Bissau</option>
                            <option value="Guyana">Guyana</option>
                            <option value="Haiti">Haiti</option>
                            <option value="Honduras">Honduras</option>
                            <option value="Hungary">Hungary</option>
                            <option value="Iceland">Iceland</option>
                            <option value="India">India</option>
                            <option value="Indonesia">Indonesia</option>
                            <option value="Iran">Iran</option>
                            <option value="Iraq">Iraq</option>
                            <option value="Ireland">Ireland</option>
                            <option value="Israel">Israel</option>
                            <option value="Italy">Italy</option>
                            <option value="Jamaica">Jamaica</option>
                            <option value="Japan">Japan</option>
                            <option value="Jordan">Jordan</option>
                            <option value="Kazakhstan">Kazakhstan</option>
                            <option value="Kenya">Kenya</option>
                            <option value="Kiribati">Kiribati</option>
                            <option value="Kuwait">Kuwait</option>
                            <option value="Kyrgyzstan">Kyrgyzstan</option>
                            <option value="Laos">Laos</option>
                            <option value="Latvia">Latvia</option>
                            <option value="Lebanon">Lebanon</option>
                            <option value="Lesotho">Lesotho</option>
                            <option value="Liberia">Liberia</option>
                            <option value="Libya">Libya</option>
                            <option value="Liechtenstein">Liechtenstein</option>
                            <option value="Lithuania">Lithuania</option>
                            <option value="Luxembourg">Luxembourg</option>
                            <option value="Madagascar">Madagascar</option>
                            <option value="Malawi">Malawi</option>
                            <option value="Malaysia">Malaysia</option>
                            <option value="Maldives">Maldives</option>
                            <option value="Mali">Mali</option>
                            <option value="Malta">Malta</option>
                            <option value="Marshall Islands">Marshall Islands</option>
                            <option value="Mauritania">Mauritania</option>
                            <option value="Mauritius">Mauritius</option>
                            <option value="Mexico">Mexico</option>
                            <option value="Micronesia">Micronesia</option>
                            <option value="Moldova">Moldova</option>
                            <option value="Monaco">Monaco</option>
                            <option value="Mongolia">Mongolia</option>
                            <option value="Montenegro">Montenegro</option>
                            <option value="Morocco">Morocco</option>
                            <option value="Mozambique">Mozambique</option>
                            <option value="Myanmar">Myanmar</option>
                            <option value="Namibia">Namibia</option>
                            <option value="Nauru">Nauru</option>
                            <option value="Nepal">Nepal</option>
                            <option value="Netherlands">Netherlands</option>
                            <option value="New Zealand">New Zealand</option>
                            <option value="Nicaragua">Nicaragua</option>
                            <option value="Niger">Niger</option>
                            <option value="Nigeria">Nigeria</option>
                            <option value="North Korea">North Korea</option>
                            <option value="North Macedonia">North Macedonia</option>
                            <option value="Norway">Norway</option>
                            <option value="Oman">Oman</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="Palau">Palau</option>
                            <option value="Palestine">Palestine</option>
                            <option value="Panama">Panama</option>
                            <option value="Papua New Guinea">Papua New Guinea</option>
                            <option value="Paraguay">Paraguay</option>
                            <option value="Peru">Peru</option>
                            <option value="Philippines">Philippines</option>
                            <option value="Poland">Poland</option>
                            <option value="Portugal">Portugal</option>
                            <option value="Qatar">Qatar</option>
                            <option value="Romania">Romania</option>
                            <option value="Russia">Russia</option>
                            <option value="Rwanda" selected>Rwanda</option>
                            <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                            <option value="Saint Lucia">Saint Lucia</option>
                            <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
                            <option value="Samoa">Samoa</option>
                            <option value="San Marino">San Marino</option>
                            <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                            <option value="Saudi Arabia">Saudi Arabia</option>
                            <option value="Senegal">Senegal</option>
                            <option value="Serbia">Serbia</option>
                            <option value="Seychelles">Seychelles</option>
                            <option value="Sierra Leone">Sierra Leone</option>
                            <option value="Singapore">Singapore</option>
                            <option value="Slovakia">Slovakia</option>
                            <option value="Slovenia">Slovenia</option>
                            <option value="Solomon Islands">Solomon Islands</option>
                            <option value="Somalia">Somalia</option>
                            <option value="South Africa">South Africa</option>
                            <option value="South Korea">South Korea</option>
                            <option value="South Sudan">South Sudan</option>
                            <option value="Spain">Spain</option>
                            <option value="Sri Lanka">Sri Lanka</option>
                            <option value="Sudan">Sudan</option>
                            <option value="Suriname">Suriname</option>
                            <option value="Sweden">Sweden</option>
                            <option value="Switzerland">Switzerland</option>
                            <option value="Syria">Syria</option>
                            <option value="Taiwan">Taiwan</option>
                            <option value="Tajikistan">Tajikistan</option>
                            <option value="Tanzania">Tanzania</option>
                            <option value="Thailand">Thailand</option>
                            <option value="Timor-Leste">Timor-Leste</option>
                            <option value="Togo">Togo</option>
                            <option value="Tonga">Tonga</option>
                            <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                            <option value="Tunisia">Tunisia</option>
                            <option value="Turkey">Turkey</option>
                            <option value="Turkmenistan">Turkmenistan</option>
                            <option value="Tuvalu">Tuvalu</option>
                            <option value="Uganda">Uganda</option>
                            <option value="Ukraine">Ukraine</option>
                            <option value="United Arab Emirates">United Arab Emirates</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="United States">United States</option>
                            <option value="Uruguay">Uruguay</option>
                            <option value="Uzbekistan">Uzbekistan</option>
                            <option value="Vanuatu">Vanuatu</option>
                            <option value="Vatican City">Vatican City</option>
                            <option value="Venezuela">Venezuela</option>
                            <option value="Vietnam">Vietnam</option>
                            <option value="Yemen">Yemen</option>
                            <option value="Zambia">Zambia</option>
                            <option value="Zimbabwe">Zimbabwe</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Education -->
            <div class="form-section">
                <h2 data-translate="education">Education</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="education_level"><span data-translate="educationLevel">Education Level</span> <span class="required">*</span></label>
                        <select id="education_level" name="education_level" required>
                            <option value="">Select level</option>
                            <option value="Primary">Primary School</option>
                            <option value="Secondary">Secondary School</option>
                            <option value="University">University/College</option>
                            <option value="Graduate">Graduate</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="current_institution" data-translate="currentInstitution">Current Institution</label>
                        <input type="text" id="current_institution" name="current_institution" placeholder="School or University name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="field_of_study" data-translate="fieldOfStudy">Field of Study</label>
                        <input type="text" id="field_of_study" name="field_of_study" placeholder="e.g., Computer Science, Business">
                    </div>
                </div>
            </div>

            <!-- Your Story -->
            <div class="form-section">
                <h2 data-translate="yourStory">Your story</h2>

                <div class="form-group">
                    <label for="title"><span data-translate="profileTitle">Profile Title</span> <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Aspiring Software Developer Building Solutions for Rural Communities">
                    <small>This will be the headline of your profile (max 200 characters)</small>
                </div>

                <div class="form-group">
                    <label for="short_description"><span data-translate="shortDescription">Short Description</span> <span class="required">*</span></label>
                    <textarea id="short_description" name="short_description" rows="3" required placeholder="Write a brief summary of your story (2-3 sentences)"></textarea>
                    <small>This appears on your profile card on the homepage</small>
                </div>

                <div class="form-group">
                    <label for="full_story"><span data-translate="fullStory">Full Story</span> <span class="required">*</span></label>
                    <textarea id="full_story" name="full_story" rows="8" required placeholder="Tell us your story in detail: your background, challenges, dreams, and why you need support..."></textarea>
                    <small>Share your journey, challenges, achievements, and aspirations (minimum 200 words)</small>
                </div>

                <div class="form-group">
                    <label for="goals" data-translate="yourGoals">Your Goals</label>
                    <textarea id="goals" name="goals" rows="4" placeholder="What are your short-term and long-term goals?"></textarea>
                </div>

                <div class="form-group">
                    <label for="achievements" data-translate="yourAchievements">Your Achievements</label>
                    <textarea id="achievements" name="achievements" rows="4" placeholder="Share any accomplishments, awards, or milestones"></textarea>
                </div>
            </div>

            <!-- Media Upload -->
            <div class="form-section">
                <h2 data-translate="profileMedia">Profile media</h2>
                <p class="section-description">Upload photos that represent you and your story (you can upload multiple images)</p>

                <div class="form-group">
                    <label for="profile_images"><span data-translate="profilePhotos">Profile photos</span> <span class="required">*</span></label>
                    <input type="file" id="profile_images" name="profile_images[]" accept="image/*" multiple required>
                    <small>Accepted formats: JPG, PNG (max size: 2MB per image). You can select up to 3 images.</small>
                    <div id="images-preview-container" class="media-preview-grid"></div>
                </div>

                <div id="image-descriptions-container" style="margin-top: 20px; display: none;">
                    <!-- Image description fields will be added here dynamically -->
                </div>
            </div>

            <!-- Social Media -->
            <div class="form-section">
                <h2 data-translate="socialMediaOptional">Social media (optional)</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="facebook_url" data-translate="facebookUrl">Facebook URL</label>
                        <input type="url" id="facebook_url" name="facebook_url" placeholder="https://facebook.com/yourprofile">
                    </div>

                    <div class="form-group">
                        <label for="twitter_url" data-translate="twitterUrl">Twitter/X URL</label>
                        <input type="url" id="twitter_url" name="twitter_url" placeholder="https://twitter.com/yourhandle">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="instagram_url" data-translate="instagramUrl">Instagram URL</label>
                        <input type="url" id="instagram_url" name="instagram_url" placeholder="https://instagram.com/yourprofile">
                    </div>

                    <div class="form-group">
                        <label for="linkedin_url" data-translate="linkedinUrl">LinkedIn URL</label>
                        <input type="url" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/yourprofile">
                    </div>
                </div>
            </div>

            <!-- Terms and Submit -->
            <div class="form-section">
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="terms" name="terms" required>
                        <span data-translate="agreeTermsCheckbox">I understand my story will be reviewed before being published</span> <span class="required">*</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="data_consent" name="data_consent" required>
                        <span data-translate="agreePrivacyCheckbox">I consent to the use of my information according to the privacy policy</span> <span class="required">*</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit" data-translate="submitMyStory">Submit My Story</button>
                <button type="reset" class="btn-reset" data-translate="clearForm">Clear Form</button>
            </div>
        </form>

        <div class="after-signup-info">
            <h3 data-translate="whatHappensNext">What happens next?</h3>
            <ol>
                <li>Our team will review your submission within 2-3 business days</li>
                <li>We may contact you for additional information</li>
                <li>Once approved, your profile will be published on our website</li>
                <li>You'll be notified via email when your profile goes live</li>
            </ol>
        </div>
    </div>

    <?php include '../includes/footer_new.php'; ?>

    <!-- JavaScript -->
    <script src="../assets/js/signup-validation.js"></script>
</body>
</html>
