# Incubation Platform Integration Guide

## Overview
The UPSHIFT Social Innovation Incubation Platform has been successfully installed and is ready to be integrated into the main Bihak Center website.

## Installation Status: ✅ COMPLETE

### What's Been Installed:
- ✅ Database schema (26 tables)
- ✅ UPSHIFT program data (4 phases, 19 exercises)
- ✅ Session configuration ([config/session.php](config/session.php))
- ✅ All platform pages and features
- ✅ MySQL permissions fixed

### Platform Pages Created:
1. [incubation-program.php](public/incubation-program.php) - Landing page
2. [incubation-dashboard-v2.php](public/incubation-dashboard-v2.php) - Main dashboard with phase locking
3. [incubation-team-create.php](public/incubation-team-create.php) - Team creation
4. [incubation-exercise.php](public/incubation-exercise.php) - Exercise submission
5. [incubation-self-assess.php](public/incubation-self-assess.php) - Self-assessment tool
6. [business-model-canvas.php](public/business-model-canvas.php) - Interactive canvas
7. [incubation-showcase.php](public/incubation-showcase.php) - Project voting
8. [ai-assistant.php](public/ai-assistant.php) - AI guidance widget
9. [admin/incubation-reviews.php](public/admin/incubation-reviews.php) - Admin review panel

---

## 🔗 Integration Tasks

### 1. Add "Incubation Program" Button to Header

**File:** `includes/header_new.php`

**Location:** In the header-right section, add BEFORE "Get Involved" button:

```php
<!-- Incubation Program Button -->
<a href="<?php echo $base_path; ?>incubation-program.php" class="btn-incubation" title="Join our UPSHIFT Social Innovation Program">
    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
    </svg>
    <span>Incubation Program</span>
</a>
```

**CSS Styling:**

Add to your main CSS file (e.g., `assets/css/style.css`):

```css
/* Incubation Program Button */
.btn-incubation {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); /* Purple gradient */
    color: white !important;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    white-space: nowrap;
}

.btn-incubation:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}

.btn-incubation svg {
    flex-shrink: 0;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .btn-incubation span {
        display: none; /* Show only icon on mobile */
    }

    .btn-incubation {
        padding: 10px;
    }
}
```

---

### 2. Add Incubation Program Section to "Our Work" Page

**File:** `public/work.php`

**Add this section** after the existing program sections:

```php
<!-- Incubation Program Section -->
<section class="program-section">
    <div class="program-header">
        <div class="program-icon">
            <svg width="48" height="48" viewBox="0 0 20 20" fill="#6366f1">
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
            </svg>
        </div>
        <div>
            <h2>UPSHIFT Social Innovation Incubation Program</h2>
            <p class="program-subtitle">Transform your innovative ideas into impactful social ventures</p>
        </div>
    </div>

    <div class="program-content">
        <div class="program-description">
            <p>Our interactive incubation program guides young innovators through a comprehensive design thinking process. Through 4 phases and 19 hands-on exercises, participants develop their social innovation projects from concept to launch.</p>

            <h3>Program Highlights:</h3>
            <ul class="program-features">
                <li>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="#6366f1">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Self-Paced Learning:</strong> Progress at your own speed through structured phases
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="#6366f1">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Team Collaboration:</strong> Work with 3-5 team members on shared projects
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="#6366f1">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <strong>AI Assistant Support:</strong> Get contextual guidance throughout your journey
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="#6366f1">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Project Showcase:</strong> Present your work and compete for recognition
                </li>
            </ul>

            <h3>4 Program Phases:</h3>
            <div class="phases-grid">
                <div class="phase-card">
                    <div class="phase-number">1</div>
                    <h4>Understand & Observe</h4>
                    <p>Discover challenges and understand your community's needs</p>
                </div>
                <div class="phase-card">
                    <div class="phase-number">2</div>
                    <h4>Ideate & Design</h4>
                    <p>Generate creative solutions and design your approach</p>
                </div>
                <div class="phase-card">
                    <div class="phase-number">3</div>
                    <h4>Prototype & Test</h4>
                    <p>Build prototypes and gather feedback from users</p>
                </div>
                <div class="phase-card">
                    <div class="phase-number">4</div>
                    <h4>Implement & Scale</h4>
                    <p>Launch your project and develop a sustainable business model</p>
                </div>
            </div>
        </div>

        <div class="program-cta">
            <a href="incubation-program.php" class="btn-primary-large">
                Explore the Program
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </a>
            <p class="program-stats">
                <span><strong>16 weeks</strong> program duration</span> •
                <span><strong>19 exercises</strong> to complete</span> •
                <span><strong>Free</strong> to join</span>
            </p>
        </div>
    </div>
</section>
```

**CSS for the section:**

```css
/* Incubation Program Section on Work Page */
.program-section {
    padding: 60px 20px;
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    margin: 40px 0;
    border-radius: 16px;
}

.program-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.program-icon {
    flex-shrink: 0;
}

.program-subtitle {
    color: #6b7280;
    font-size: 18px;
    margin-top: 8px;
}

.program-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
}

.program-features li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
    font-size: 16px;
    line-height: 1.6;
}

.program-features svg {
    flex-shrink: 0;
    margin-top: 2px;
}

.phases-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.phase-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.phase-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.2);
}

.phase-number {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 16px;
}

.phase-card h4 {
    color: #1f2937;
    margin-bottom: 8px;
}

.phase-card p {
    color: #6b7280;
    font-size: 14px;
}

.program-cta {
    text-align: center;
    margin-top: 40px;
}

.btn-primary-large {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 32px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
}

.btn-primary-large:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.program-stats {
    margin-top: 16px;
    color: #6b7280;
    font-size: 14px;
}
```

---

## 📝 Quick Reference Links

### Main Entry Points:
- **Landing Page:** http://localhost/bihak-center/public/incubation-program.php
- **Dashboard:** http://localhost/bihak-center/public/incubation-dashboard-v2.php
- **Admin Review:** http://localhost/bihak-center/public/admin/incubation-reviews.php

### User Flow:
1. User clicks "Incubation Program" button in header
2. Views program overview on landing page
3. Signs up/logs in (if not already)
4. Creates or joins a team
5. Progresses through 4 phases completing 19 exercises
6. Completes Business Model Canvas
7. Submits project to showcase
8. Public votes on projects

### Authentication:
- **Test User:** `testuser@example.com` / `TestUser123`
- **Admin:** `admin@bihakcenter.org` / (your admin password)
- **Create New:** http://localhost/bihak-center/public/signup.php

---

## ✅ Post-Integration Checklist

- [ ] Add "Incubation Program" button to header
- [ ] Add incubation section to work.php page
- [ ] Test the button link works from homepage
- [ ] Verify mobile responsiveness of new button
- [ ] Test user flow: landing → signup → team create → dashboard
- [ ] Delete temporary installation files:
  - [ ] install_via_admin.php
  - [ ] install_incubation.php
  - [ ] diagnose_db.php
  - [ ] test_connection.php
  - [ ] fix_mysql_permissions.bat
  - [ ] fix-mysql.ps1

---

## 🎨 Design Notes

The incubation program uses a **purple gradient theme** (#6366f1 to #8b5cf6) to differentiate it from other sections of the site while maintaining visual harmony.

The button placement in the header makes it prominently visible and easily accessible from any page.

---

## 📚 Documentation References

- [Database Design](INCUBATION-PLATFORM-DATABASE-DESIGN.md)
- [Installation Guide](INCUBATION-PLATFORM-INSTALLATION.md)
- [Feature Summary](INCUBATION-PLATFORM-SUMMARY.md)
- [Enhancements](INCUBATION-PLATFORM-ENHANCEMENTS.md)

---

**Integration Status:** Ready for final implementation
**Created:** November 18, 2025
**Platform Version:** 1.0
