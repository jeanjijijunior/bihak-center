<?php
/**
 * Translation System Demo Page
 * Shows how the bilingual system works
 */
session_start();
?>
<?php include __DIR__ . '/../includes/header_new.php'; ?>

<style>
    .demo-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
    }
    .demo-section {
        background: white;
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .demo-section h2 {
        color: #2563eb;
        margin-bottom: 1rem;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }
    .demo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    .demo-card {
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }
    .btn {
        padding: 0.5rem 1rem;
        margin: 0.25rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    .btn-primary { background: #2563eb; color: white; }
    .btn-success { background: #10b981; color: white; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-secondary { background: #6b7280; color: white; }
    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin: 0.5rem 0;
    }
    .alert-success { background: #d1fae5; border-left: 4px solid #10b981; }
    .alert-error { background: #fee2e2; border-left: 4px solid #ef4444; }
    .alert-warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background: #f3f4f6; font-weight: 600; }
    input, textarea {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        margin: 0.5rem 0;
    }
    .instructions {
        background: #eff6ff;
        padding: 1.5rem;
        border-radius: 8px;
        border-left: 4px solid #2563eb;
        margin-bottom: 2rem;
    }
</style>

<div class="demo-container">
    <!-- Instructions -->
    <div class="instructions">
        <h1 style="margin-top: 0;">🌐 Translation System Demo</h1>
        <p><strong data-translate="instructions">Instructions</strong>: Click the <strong>EN/FR</strong> buttons in the header above to see ALL content translate instantly!</p>
        <p data-translate="testInstructions">This page demonstrates how every element with a <code>data-translate</code> attribute automatically switches between English and French.</p>
    </div>

    <!-- Section 1: Common Buttons & Actions -->
    <div class="demo-section">
        <h2 data-translate="commonActions">Common Actions</h2>
        <p>All standard buttons translate automatically:</p>
        <div style="margin-top: 1rem;">
            <button class="btn btn-primary" data-translate="submit">Submit</button>
            <button class="btn btn-success" data-translate="save">Save</button>
            <button class="btn btn-danger" data-translate="delete">Delete</button>
            <button class="btn btn-secondary" data-translate="cancel">Cancel</button>
            <button class="btn btn-primary" data-translate="edit">Edit</button>
            <button class="btn btn-secondary" data-translate="back">Back</button>
            <button class="btn btn-primary" data-translate="next">Next</button>
            <button class="btn btn-secondary" data-translate="previous">Previous</button>
            <button class="btn btn-primary" data-translate="search">Search</button>
            <button class="btn btn-primary" data-translate="filter">Filter</button>
            <button class="btn btn-primary" data-translate="download">Download</button>
            <button class="btn btn-primary" data-translate="upload">Upload</button>
        </div>
    </div>

    <!-- Section 2: Status Messages -->
    <div class="demo-section">
        <h2 data-translate="statusMessages">Status Messages</h2>
        <div class="alert alert-success" data-translate="successfullySaved">
            Successfully saved
        </div>
        <div class="alert alert-error" data-translate="errorOccurred">
            An error occurred
        </div>
        <div class="alert alert-warning" data-translate="fillAllFields">
            Please fill all required fields
        </div>
        <p data-translate="loading">Loading...</p>
        <p data-translate="saving">Saving...</p>
        <p data-translate="processing">Processing...</p>
    </div>

    <!-- Section 3: Form Elements -->
    <div class="demo-section">
        <h2 data-translate="formExample">Form Example</h2>
        <form>
            <label>
                <strong data-translate="yourName">Your Name</strong>
                <span data-translate="required">(Required)</span>
            </label>
            <input type="text" data-translate="fullName" placeholder="Full Name">

            <label data-translate="yourEmail">Your Email</label>
            <input type="email" data-translate="emailAddress" placeholder="Email Address">

            <label data-translate="phone">Phone</label>
            <input type="tel" data-translate="phoneNumber" placeholder="Phone Number">

            <label data-translate="yourMessage">Your Message</label>
            <textarea rows="4" data-translate="message" placeholder="Message"></textarea>

            <button type="button" class="btn btn-primary" data-translate="sendMessage">Send Message</button>
        </form>
    </div>

    <!-- Section 4: Table -->
    <div class="demo-section">
        <h2 data-translate="tableExample">Table Example</h2>
        <table>
            <thead>
                <tr>
                    <th data-translate="name">Name</th>
                    <th data-translate="email">Email</th>
                    <th data-translate="status">Status</th>
                    <th data-translate="date">Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>John Doe</td>
                    <td>john@example.com</td>
                    <td><span data-translate="pending">Pending</span></td>
                    <td data-translate="today">Today</td>
                </tr>
                <tr>
                    <td>Jane Smith</td>
                    <td>jane@example.com</td>
                    <td><span data-translate="approved">Approved</span></td>
                    <td data-translate="yesterday">Yesterday</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section 5: Dashboard Cards -->
    <div class="demo-section">
        <h2 data-translate="dashboardExample">Dashboard Example</h2>
        <div class="demo-grid">
            <div class="demo-card">
                <h3 data-translate="totalUsers">Total Users</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #2563eb;">1,234</p>
            </div>
            <div class="demo-card">
                <h3 data-translate="totalProfiles">Total Profiles</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #10b981;">567</p>
            </div>
            <div class="demo-card">
                <h3 data-translate="recentActivity">Recent Activity</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #f59e0b;">89</p>
            </div>
        </div>
    </div>

    <!-- Section 6: Incubation Module -->
    <div class="demo-section">
        <h2 data-translate="incubationProgram">Incubation Program</h2>
        <div style="margin-top: 1rem;">
            <h3 data-translate="myTeam">My Team</h3>
            <p><span data-translate="teamName">Team Name</span>: Innovation Squad</p>
            <p><span data-translate="teamMembers">Team Members</span>: 5</p>
            <p><span data-translate="progress">Progress</span>: 75%</p>

            <h3 style="margin-top: 1.5rem;" data-translate="exercises">Exercises</h3>
            <button class="btn btn-primary" data-translate="startExercise">Start Exercise</button>
            <button class="btn btn-success" data-translate="getAIFeedback">Get AI Feedback</button>
            <button class="btn btn-primary" data-translate="submitForReview">Submit for Review</button>
        </div>
    </div>

    <!-- Section 7: Mentorship Module -->
    <div class="demo-section">
        <h2 data-translate="mentorship">Mentorship</h2>
        <div style="margin-top: 1rem;">
            <h3 data-translate="myMentor">My Mentor</h3>
            <p><span data-translate="name">Name</span>: Sarah Johnson</p>
            <p><span data-translate="expertise">Expertise</span>: Business Development</p>
            <p><span data-translate="availability">Availability</span>: Weekdays</p>

            <button class="btn btn-primary" data-translate="scheduleSession">Schedule Session</button>
            <button class="btn btn-secondary" data-translate="findMentor">Find a Mentor</button>
        </div>
    </div>

    <!-- Section 8: Messaging -->
    <div class="demo-section">
        <h2 data-translate="messages">Messages</h2>
        <div style="margin-top: 1rem;">
            <button class="btn btn-primary" data-translate="newMessage">New Message</button>
            <button class="btn btn-secondary" data-translate="compose">Compose</button>
            <button class="btn btn-secondary" data-translate="reply">Reply</button>
            <button class="btn btn-secondary" data-translate="markAsRead">Mark as Read</button>

            <p style="margin-top: 1rem;">
                <strong data-translate="status">Status</strong>: <span data-translate="online">Online</span>
            </p>
            <p data-translate="noMessages">No messages</p>
        </div>
    </div>

    <!-- Section 9: Admin Panel -->
    <div class="demo-section">
        <h2 data-translate="adminDashboard">Admin Dashboard</h2>
        <div style="margin-top: 1rem;">
            <button class="btn btn-primary" data-translate="manage">Manage</button>
            <button class="btn btn-success" data-translate="addNew">Add New</button>
            <button class="btn btn-secondary" data-translate="exportData">Export Data</button>
            <button class="btn btn-secondary" data-translate="importData">Import Data</button>

            <h3 style="margin-top: 1.5rem;" data-translate="quickActions">Quick Actions</h3>
            <p data-translate="viewAll">View All</p>
            <p data-translate="analytics">Analytics</p>
            <p data-translate="reports">Reports</p>
        </div>
    </div>

    <!-- Instructions Footer -->
    <div class="instructions">
        <h3 data-translate="howItWorks">How It Works</h3>
        <p data-translate="simpleImplementation">Simple Implementation</p>
        <ol>
            <li>Add <code>data-translate="key"</code> to any element</li>
            <li>Use existing translation keys from translations-extended.js</li>
            <li>Language switches automatically when user clicks EN/FR</li>
            <li>User preference is saved in browser localStorage</li>
        </ol>

        <p style="margin-top: 1rem;"><strong data-translate="readMore">Read More</strong>: See <a href="../TRANSLATION-GUIDE.md" style="color: #2563eb;">TRANSLATION-GUIDE.md</a> for complete documentation</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_new.php'; ?>
