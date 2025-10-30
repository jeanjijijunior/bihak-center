<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Join Bihak Center - Share your story and get support for your dreams">
    <title>Sign Up - Bihak Center</title>

    <link rel="icon" type="image/png" href="../assets/images/favimg.png">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/responsive.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="../assets/images/logob.png" alt="Bihak Center Logo">
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="work.html">Our Work</a></li>
                <li><a href="contact.html">Contact</a></li>
                <li><a href="opportunities.html">Opportunities</a></li>
                <li><a href="signup.php" class="active">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="signup-container">
        <div class="signup-header">
            <h1>Share Your Story</h1>
            <p>Join Bihak Center and let us support your journey to success</p>
        </div>

        <div id="message-container"></div>

        <form id="signupForm" method="POST" action="process_signup.php" enctype="multipart/form-data" class="signup-form">

            <!-- Personal Information -->
            <div class="form-section">
                <h2>Personal Information</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+250 788 123 456">
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
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
                <h2>Location</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required>
                    </div>

                    <div class="form-group">
                        <label for="district">District <span class="required">*</span></label>
                        <input type="text" id="district" name="district" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="Rwanda" readonly>
                    </div>
                </div>
            </div>

            <!-- Education -->
            <div class="form-section">
                <h2>Education</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="education_level">Education Level <span class="required">*</span></label>
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
                        <label for="current_institution">Current Institution</label>
                        <input type="text" id="current_institution" name="current_institution" placeholder="School or University name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="field_of_study">Field of Study</label>
                        <input type="text" id="field_of_study" name="field_of_study" placeholder="e.g., Computer Science, Business">
                    </div>
                </div>
            </div>

            <!-- Your Story -->
            <div class="form-section">
                <h2>Your Story</h2>

                <div class="form-group">
                    <label for="title">Profile Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Aspiring Software Developer Building Solutions for Rural Communities">
                    <small>This will be the headline of your profile (max 200 characters)</small>
                </div>

                <div class="form-group">
                    <label for="short_description">Short Description <span class="required">*</span></label>
                    <textarea id="short_description" name="short_description" rows="3" required placeholder="Write a brief summary of your story (2-3 sentences)"></textarea>
                    <small>This appears on your profile card on the homepage</small>
                </div>

                <div class="form-group">
                    <label for="full_story">Full Story <span class="required">*</span></label>
                    <textarea id="full_story" name="full_story" rows="8" required placeholder="Tell us your story in detail: your background, challenges, dreams, and why you need support..."></textarea>
                    <small>Share your journey, challenges, achievements, and aspirations (minimum 200 words)</small>
                </div>

                <div class="form-group">
                    <label for="goals">Your Goals</label>
                    <textarea id="goals" name="goals" rows="4" placeholder="What are your short-term and long-term goals?"></textarea>
                </div>

                <div class="form-group">
                    <label for="achievements">Your Achievements</label>
                    <textarea id="achievements" name="achievements" rows="4" placeholder="Share any accomplishments, awards, or milestones"></textarea>
                </div>
            </div>

            <!-- Media Upload -->
            <div class="form-section">
                <h2>Profile Media</h2>
                <p class="section-description">Upload a photo or video that represents you and your story</p>

                <div class="form-group">
                    <label for="profile_image">Profile Photo <span class="required">*</span></label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" required>
                    <small>Accepted formats: JPG, PNG (Max size: 5MB)</small>
                    <div id="image-preview" class="media-preview"></div>
                </div>

                <div class="form-group">
                    <label for="media_file">Additional Media (Optional)</label>
                    <input type="file" id="media_file" name="media_file" accept="image/*,video/*">
                    <small>Upload an additional photo or video (Max size: 20MB for video, 5MB for image)</small>
                    <div id="media-preview" class="media-preview"></div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="form-section">
                <h2>Social Media (Optional)</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="facebook_url">Facebook URL</label>
                        <input type="url" id="facebook_url" name="facebook_url" placeholder="https://facebook.com/yourprofile">
                    </div>

                    <div class="form-group">
                        <label for="twitter_url">Twitter/X URL</label>
                        <input type="url" id="twitter_url" name="twitter_url" placeholder="https://twitter.com/yourhandle">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="instagram_url">Instagram URL</label>
                        <input type="url" id="instagram_url" name="instagram_url" placeholder="https://instagram.com/yourprofile">
                    </div>

                    <div class="form-group">
                        <label for="linkedin_url">LinkedIn URL</label>
                        <input type="url" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/yourprofile">
                    </div>
                </div>
            </div>

            <!-- Terms and Submit -->
            <div class="form-section">
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="terms" name="terms" required>
                        I agree to share my story publicly and allow Bihak Center to use it for promotional purposes <span class="required">*</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="data_consent" name="data_consent" required>
                        I consent to the collection and processing of my personal data as described in the Privacy Policy <span class="required">*</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Submit My Story</button>
                <button type="reset" class="btn-reset">Clear Form</button>
            </div>
        </form>

        <div class="after-signup-info">
            <h3>What happens next?</h3>
            <ol>
                <li>Our team will review your submission within 2-3 business days</li>
                <li>We may contact you for additional information</li>
                <li>Once approved, your profile will be published on our website</li>
                <li>You'll be notified via email when your profile goes live</li>
            </ol>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: info@bihakcenter.org</p>
                <p>Phone: +250 788 123 456</p>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 Bihak Center | All Rights Reserved</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../assets/js/signup-validation.js"></script>
</body>
</html>
