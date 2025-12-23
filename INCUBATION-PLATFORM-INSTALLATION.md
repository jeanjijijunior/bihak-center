# Incubation Platform - Installation & Testing Guide

## Date: 2025-11-17
## Status: ✅ READY FOR INSTALLATION

---

## Overview

Complete interactive incubation platform built for the Bihak Center website, based on the UPSHIFT social innovation program. The platform enables teams to go through a structured design thinking process, complete exercises, submit work, and showcase their projects with public voting.

---

## Features Built

### 1. Program Landing Page ✅
**File:** `public/incubation-program.php`

**Features:**
- Beautiful hero section with gradient background
- Program overview and description (bilingual EN/FR)
- Statistics display (teams, participants, completed teams, published projects)
- 4 phases overview with exercise counts
- Dynamic CTAs based on user status (not logged in, logged in, has team, etc.)
- Responsive design

**Access:** `http://localhost/bihak-center/public/incubation-program.php`

---

### 2. Team Creation Interface ✅
**File:** `public/incubation-team-create.php`

**Features:**
- Team formation form
- Team name and description fields
- Member invitation system (email-based)
- Dynamic email fields (add/remove)
- User becomes team leader automatically
- Invitation tokens generated
- Activity logging

**Access:** `http://localhost/bihak-center/public/incubation-team-create.php` (requires login)

---

### 3. Team Dashboard ✅
**File:** `public/incubation-dashboard.php`

**Features:**
- Team progress overview with percentage
- Phase navigation tabs
- Exercise list with completion status
- Status badges (Not Started, Draft, Submitted, Approved, Needs Revision)
- Team members sidebar with avatars
- Recent activity feed
- Responsive grid layout

**Access:** `http://localhost/bihak-center/public/incubation-dashboard.php` (requires team membership)

---

### 4. Exercise Page & Submission System ✅
**File:** `public/incubation-exercise.php`

**Features:**
- Exercise details display (number, title, description)
- Instructions in bilingual format
- Materials needed section
- Duration and deliverable type display
- Text submission (textarea)
- File upload functionality (PDF, Word, PowerPoint, Images)
- Save draft feature
- Submit for review
- View submission status
- Admin feedback display
- Breadcrumb navigation

**Access:** `http://localhost/bihak-center/public/incubation-exercise.php?id=[exercise_id]`

---

### 5. Project Showcase & Voting Page ✅
**File:** `public/incubation-showcase.php`

**Features:**
- Winning project highlight (hero card)
- Tag-based filtering (10 categories)
- Sorting options (Most Votes, Most Recent, Most Viewed)
- Project cards with thumbnails
- Vote button functionality
- Vote count display
- View count tracking
- Duplicate vote prevention (user-based and IP-based)
- Responsive grid layout
- Empty state handling

**Access:** `http://localhost/bihak-center/public/incubation-showcase.php` (public access)

---

### 6. Admin Review Dashboard ✅
**File:** `public/admin/incubation-reviews.php`

**Features:**
- Tabbed interface (Pending Review, Approved, Needs Revision, Drafts)
- Submission counts per status
- Detailed submission display
- Text and file viewing
- Review form with approve/revision options
- Feedback textarea
- Activity logging on approval
- Integrated with admin panel

**Access:** `http://localhost/bihak-center/public/admin/incubation-reviews.php` (requires admin login)

---

## Database Schema

### Total: 26 Tables

**Location:** `includes/incubation_platform_schema.sql`

**Categories:**
1. Program Structure (4 tables) - Programs, phases, exercises, resources
2. Team Management (3 tables) - Teams, members, invitations
3. Progress Tracking (3 tables) - Submissions, phase completions, activity log
4. Business Canvas (1 table) - 9-block canvas storage
5. Showcase & Voting (5 tables) - Projects, votes, tags, comments
6. Mentorship (2 tables) - Mentor assignments, sessions
7. Notifications (1 table) - Team notifications
8. Supporting (2 tables) - Milestones, milestone progress

**Initial Data Included:**
- UPSHIFT program definition
- 4 phases (Understand & Observe, Design, Build & Test, Make It Real)
- 19 exercises with full instructions
- 5 mentorship milestones (Conception, Validation, MVP, Pilot, Perfect)
- 10 project tags (Education, Health, Environment, etc.)

---

## Installation Steps

### Step 1: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start Apache
3. Start MySQL
4. Verify both are running (green indicators)

---

### Step 2: Install Database Schema

**Option 1: MySQL Command Line**
```bash
cd c:\xampp\htdocs\bihak-center
"C:\xampp\mysql\bin\mysql.exe" -u root bihak < includes/incubation_platform_schema.sql
```

**Option 2: phpMyAdmin**
1. Open browser: `http://localhost/phpmyadmin`
2. Select database `bihak`
3. Click "Import" tab
4. Choose file: `includes/incubation_platform_schema.sql`
5. Click "Go"

---

### Step 3: Verify Installation

**Check Tables Created:**
```sql
-- In phpMyAdmin or MySQL command line
USE bihak;

-- Should show 26 new tables
SHOW TABLES LIKE '%incubation%';
SHOW TABLES LIKE '%team%';
SHOW TABLES LIKE '%showcase%';
SHOW TABLES LIKE '%business_model%';
```

**Expected tables:**
```
incubation_programs
program_phases
program_exercises
exercise_resources
incubation_teams
team_members
team_invitations
exercise_submissions
phase_completions
team_activity_log
business_model_canvas
showcase_projects
project_votes
project_tags
project_tag_relations
project_comments
team_mentors
mentorship_sessions
team_notifications
program_milestones
team_milestone_progress
```

**Check Initial Data:**
```sql
-- Check UPSHIFT program
SELECT * FROM incubation_programs;

-- Check 4 phases
SELECT phase_number, phase_name FROM program_phases ORDER BY phase_number;

-- Check 19 exercises
SELECT phase_id, COUNT(*) as count
FROM program_exercises
GROUP BY phase_id;

-- Expected output:
-- phase_id | count
-- 1        | 5
-- 2        | 6
-- 3        | 4
-- 4        | 4

-- Check 5 milestones
SELECT milestone_name, week_number, duration_weeks
FROM program_milestones
ORDER BY week_number;

-- Check 10 tags
SELECT tag_name FROM project_tags;
```

---

### Step 4: Create Upload Directories

The platform needs directories for file uploads:

```bash
# Create directories (run in command prompt)
cd c:\xampp\htdocs\bihak-center
mkdir uploads\exercises
```

**Or manually:**
1. Navigate to `c:\xampp\htdocs\bihak-center\`
2. Create folder `uploads`
3. Inside `uploads`, create folder `exercises`

**Set permissions:**
- Right-click folders → Properties → Security
- Ensure web server has write permissions

---

## Testing Guide

### Test 1: View Landing Page

**URL:** `http://localhost/bihak-center/public/incubation-program.php`

**Expected:**
- ✅ Page loads without errors
- ✅ Hero section with gradient background
- ✅ UPSHIFT program name displayed
- ✅ Statistics show (0 teams, 0 participants, etc.)
- ✅ 4 phase cards displayed with exercise counts
- ✅ Call-to-action buttons (Sign Up / Login if not logged in)

---

### Test 2: Create Account & Login

**URL:** `http://localhost/bihak-center/public/signup.php`

**Steps:**
1. Create a new user account
2. Verify email confirmation
3. Login to the system

**Expected:**
- ✅ Account created successfully
- ✅ Can login
- ✅ Redirected to appropriate page

---

### Test 3: Create a Team

**URL:** `http://localhost/bihak-center/public/incubation-team-create.php`

**Steps:**
1. Login as a user
2. Navigate to team creation page
3. Enter team name: "Test Innovators"
4. Enter team description
5. Add 1-2 member emails (optional)
6. Click "Create Team"

**Expected:**
- ✅ Team created successfully
- ✅ Redirected to dashboard
- ✅ User is team leader
- ✅ Team appears in database
- ✅ Activity log records team creation

**Verify in database:**
```sql
SELECT * FROM incubation_teams WHERE team_name = 'Test Innovators';
SELECT * FROM team_members WHERE team_id = 1;
SELECT * FROM team_activity_log WHERE team_id = 1;
```

---

### Test 4: View Team Dashboard

**URL:** `http://localhost/bihak-center/public/incubation-dashboard.php`

**Expected:**
- ✅ Team name displayed in header
- ✅ Progress bar shows 0% initially
- ✅ Phase tabs visible (4 phases)
- ✅ Phase 1 selected by default
- ✅ 5 exercises listed for Phase 1
- ✅ All exercises show "Not Started" status
- ✅ Team members sidebar shows current user
- ✅ Activity feed shows team creation

---

### Test 5: Start an Exercise

**URL:** Click "View" on Exercise 1.1 (Problem Tree)

**Expected:**
- ✅ Exercise page loads
- ✅ Exercise number "1.1" displayed
- ✅ Exercise title "What is the problem? (Problem Tree)"
- ✅ Instructions visible in English/French
- ✅ Duration shown (60 minutes)
- ✅ Deliverable type shown (Text & File)
- ✅ Materials needed section visible
- ✅ Submission form visible
- ✅ Textarea for text input
- ✅ File upload area
- ✅ "Save Draft" button
- ✅ "Submit Exercise" button

---

### Test 6: Submit Exercise Work

**Steps:**
1. On exercise page (1.1)
2. Enter some text in textarea
3. Click "Save Draft"

**Expected:**
- ✅ Success message "Draft saved"
- ✅ Text is saved
- ✅ Status changes to "Draft"
- ✅ Can leave page and return

**Then:**
4. Add more text
5. Upload a file (PDF, image, etc.)
6. Click "Submit Exercise"

**Expected:**
- ✅ Success message "Exercise submitted successfully!"
- ✅ Status changes to "Submitted"
- ✅ File uploaded to `uploads/exercises/`
- ✅ Activity log records submission
- ✅ Back to dashboard shows "Submitted" badge

**Verify in database:**
```sql
SELECT * FROM exercise_submissions WHERE team_id = 1 AND exercise_id = 1;
SELECT * FROM team_activity_log WHERE activity_type = 'exercise_submitted';
```

---

### Test 7: Admin Review Submission

**URL:** `http://localhost/bihak-center/public/admin/incubation-reviews.php`

**Steps:**
1. Logout from user account
2. Login as admin
3. Navigate to admin panel → Incubation Reviews
4. Select "Pending Review" tab

**Expected:**
- ✅ Submitted exercise visible
- ✅ Team name displayed
- ✅ Exercise details shown
- ✅ Submission text visible
- ✅ Uploaded file downloadable
- ✅ Review form visible

**Review Exercise:**
5. Select "Approve" radio button
6. Enter feedback: "Great work! Well done."
7. Click "Submit Review"

**Expected:**
- ✅ Submission approved
- ✅ Status changes to "Approved"
- ✅ Feedback saved
- ✅ Activity log records approval
- ✅ Submission moves to "Approved" tab

**Verify in database:**
```sql
SELECT status, feedback FROM exercise_submissions WHERE id = 1;
SELECT * FROM team_activity_log WHERE activity_type = 'exercise_approved';
```

---

### Test 8: View Approved Exercise

**Steps:**
1. Logout from admin
2. Login as user
3. Go to dashboard
4. View Exercise 1.1

**Expected:**
- ✅ Status badge shows "Approved"
- ✅ Submission text displayed (read-only)
- ✅ Feedback from admin visible
- ✅ File download link available
- ✅ Progress percentage increased on dashboard

---

### Test 9: Complete Multiple Exercises

**Steps:**
1. Complete all 5 exercises in Phase 1
2. Admin approves all submissions
3. View dashboard

**Expected:**
- ✅ Phase 1 shows "5/5 exercises"
- ✅ Phase 1 completion bar at 100%
- ✅ Overall team progress updated
- ✅ Phase 2 unlocked
- ✅ Activity log shows phase completion

---

### Test 10: Project Showcase (Without Projects)

**URL:** `http://localhost/bihak-center/public/incubation-showcase.php`

**Expected:**
- ✅ Page loads
- ✅ Hero section with title
- ✅ No winner card (no projects yet)
- ✅ Filter tags visible (All, Education, Health, etc.)
- ✅ Sort dropdown works
- ✅ Empty state message: "No Projects Found"

---

### Test 11: Add Sample Project (Manual)

**For testing voting, let's manually add a sample project:**

```sql
-- Create a sample showcase project
INSERT INTO showcase_projects (
    team_id,
    canvas_id,
    project_title,
    project_title_fr,
    short_description,
    short_description_fr,
    status,
    total_votes,
    published_at
) VALUES (
    1,
    NULL,
    'Clean Water Solution',
    'Solution d''Eau Propre',
    'An innovative filtration system for rural communities to access clean drinking water.',
    'Un système de filtration innovant pour les communautés rurales pour accéder à l''eau potable.',
    'published',
    0,
    NOW()
);
```

**Then refresh showcase page:**

**Expected:**
- ✅ Project card visible
- ✅ Project title displayed
- ✅ Team name shown
- ✅ Short description visible
- ✅ Vote button clickable
- ✅ Winner card appears (project with most votes)

---

### Test 12: Vote on Project

**Steps:**
1. On showcase page
2. Find project card
3. Click "Vote" button

**Expected:**
- ✅ Page refreshes
- ✅ Vote count increases to 1
- ✅ Vote recorded in database
- ✅ Cannot vote again (duplicate prevention)

**Verify in database:**
```sql
SELECT * FROM project_votes WHERE project_id = 1;
SELECT total_votes FROM showcase_projects WHERE id = 1;
```

**Test duplicate prevention:**
4. Try to vote again
5. Should not allow duplicate vote

---

### Test 13: Filter and Sort Projects

**Add more sample projects for testing:**

```sql
-- Add 2 more projects
INSERT INTO showcase_projects (team_id, canvas_id, project_title, project_title_fr, short_description, short_description_fr, status, total_votes, published_at) VALUES
(1, NULL, 'Solar Power Initiative', 'Initiative Énergie Solaire', 'Bringing solar energy to schools in rural areas.', 'Apporter l''énergie solaire aux écoles dans les zones rurales.', 'published', 5, NOW()),
(1, NULL, 'Youth Job Platform', 'Plateforme Emploi Jeunes', 'Connecting youth with employment opportunities.', 'Connecter les jeunes avec des opportunités d''emploi.', 'published', 3, NOW());
```

**Test filtering:**
1. Click on different tag filters
2. Projects should filter

**Test sorting:**
1. Change sort to "Most Votes"
2. Projects should reorder by vote count
3. Change to "Most Recent"
4. Projects should reorder by date

**Expected:**
- ✅ Filters work correctly
- ✅ Sorting works correctly
- ✅ Winner card shows highest voted project

---

## File Structure

```
c:\xampp\htdocs\bihak-center\
├── includes/
│   └── incubation_platform_schema.sql ✅ (database schema)
├── public/
│   ├── incubation-program.php ✅ (landing page)
│   ├── incubation-team-create.php ✅ (team creation)
│   ├── incubation-dashboard.php ✅ (team dashboard)
│   ├── incubation-exercise.php ✅ (exercise page)
│   ├── incubation-showcase.php ✅ (project showcase)
│   └── admin/
│       └── incubation-reviews.php ✅ (admin reviews)
├── uploads/
│   └── exercises/ (file uploads)
├── INCUBATION-PLATFORM-DATABASE-DESIGN.md ✅ (database docs)
└── INCUBATION-PLATFORM-INSTALLATION.md ✅ (this file)
```

---

## User Journey Summary

### 1. Discovery
- User visits landing page
- Learns about UPSHIFT program
- Views 4 phases and statistics
- Decides to join

### 2. Registration & Team Formation
- Signs up for account
- Creates or joins a team
- Invites team members
- Team forms (3-5 members)

### 3. Program Execution
- Team accesses dashboard
- Views Phase 1 exercises
- Reads instructions
- Works on exercises
- Submits work (text + files)
- Receives admin feedback
- Revises if needed
- Completes Phase 1

### 4. Progression
- Moves to Phase 2
- Continues through Phases 3 & 4
- Builds prototypes
- Tests solutions
- Creates Business Model Canvas

### 5. Showcase
- Completes program
- Publishes project
- Project appears on showcase
- Public can vote
- Winner highlighted

---

## Troubleshooting

### Issue: Tables not created
**Solution:**
- Check MySQL is running
- Verify database name is "bihak"
- Check for SQL errors in phpMyAdmin
- Run schema file again

### Issue: File upload doesn't work
**Solution:**
- Create `uploads/exercises/` directory
- Check folder permissions (write access)
- Check PHP upload settings in `php.ini`
- Verify `upload_max_filesize` and `post_max_size`

### Issue: Page shows "Access Denied"
**Solution:**
- Check if logged in
- Verify user has team membership
- Check session is active

### Issue: Statistics show 0
**Solution:**
- Normal for fresh installation
- Create teams and complete exercises
- Statistics will update automatically

### Issue: Showcase page empty
**Solution:**
- No projects published yet
- Complete program exercises
- Or add sample projects via SQL

---

## Next Steps (Optional Enhancements)

### Features to Add:
1. **Business Model Canvas Tool** - Interactive 9-block canvas editor
2. **Team Chat** - Real-time messaging between team members
3. **File Preview** - View PDFs and images inline
4. **Email Notifications** - Send emails on status changes
5. **Progress Reports** - PDF export of team progress
6. **Mentor Portal** - Dedicated interface for mentors
7. **Mobile App** - Native mobile application
8. **Analytics Dashboard** - Track program metrics

### Admin Features:
1. **Program Management** - Create/edit programs and exercises
2. **Team Management** - Assign mentors, modify teams
3. **Bulk Operations** - Approve multiple submissions
4. **Export Data** - Export teams and progress to CSV
5. **Custom Fields** - Add custom fields to exercises

---

## Support

For issues or questions:
1. Check this documentation
2. Review database schema documentation
3. Check PHP error logs: `c:\xampp\apache\logs\error.log`
4. Check MySQL error logs
5. Contact development team

---

**Installation Guide Created:** 2025-11-17
**Prepared by:** Claude Code
**Project:** Bihak Center Incubation Platform
**Status:** ✅ READY FOR TESTING

Start XAMPP, install the database, and begin testing!
