# Bihak Center - Youth Empowerment Platform

A comprehensive web platform designed to empower young people in Africa by showcasing talent, providing information, and aggregating opportunities.

## 🌟 Overview

Bihak Center is a youth-focused platform with three core objectives:
1. **Showcase talented young people** - Share your story and get discovered
2. **Provide information** - Learn about programs, impact, and how to get involved
3. **Find opportunities** - Access scholarships, jobs, internships, and grants

**Project Completion: 85%** | **40+ Opportunities Available** | **Bilingual (EN/FR)**

## 🚀 Features

### For Young People
- 📝 **Create Profile** - Share your story and accomplishments
- 🔍 **Browse Opportunities** - 40+ scholarships, jobs, internships, grants
- 💾 **Save Favorites** - Bookmark opportunities for later
- 🔔 **Track Applications** - Monitor your profile approval status
- 🌐 **Bilingual** - Full support for English and French

### For Administrators
- ✅ **Approve Profiles** - Review and approve submitted profiles
- 📊 **Analytics Dashboard** - View statistics and user activity
- 🔧 **Manage Content** - Control what appears on the website
- 📈 **Monitor System** - Track scraper performance and logs

### Automated Systems
- 🤖 **Web Scraper** - Automatically collects new opportunities daily
- 🔄 **Auto-Update** - Keeps opportunity database fresh
- 📝 **Activity Logging** - Tracks all user and admin actions
- 🔐 **Security** - Rate limiting, CSRF protection, session management

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Fonts**: Google Fonts (Rubik, Poppins)
- **Translation**: Google Translate API

## Project Structure

```
.
├── assets/
│   ├── css/           # Stylesheets
│   │   ├── style.css      # Main styles
│   │   └── user-tiles.css # User content tiles
│   ├── images/        # Images and logos
│   └── js/            # JavaScript files
│       ├── translate.js      # Language translation
│       └── scroll-to-top.js  # Scroll functionality
├── config/
│   ├── database.php         # Database connection
│   ├── config.example.php   # Example configuration
│   └── *.php               # Other PHP backend files
├── includes/
│   └── *.sql              # Database schemas
├── public/
│   ├── index.php          # Homepage
│   ├── about.html         # About page
│   ├── work.html          # Our work page
│   ├── contact.html       # Contact page
│   ├── opportunities.html # Opportunities page
│   └── login-join.html    # User account page
├── .gitignore
├── .htaccess
└── README.md
```

## 🛠️ Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Apache (XAMPP recommended)
- cURL, mysqli, dom extensions enabled

### Quick Start (5 Minutes)

#### Step 1: Import Database

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create database: `bihak`
3. Import SQL files **in this order:**
   - `includes/admin_tables.sql`
   - ⚠️ **`FIX-ADMIN-PASSWORD.sql`** (CRITICAL!)
   - `includes/user_auth_tables.sql`
   - `includes/opportunities_tables.sql`

#### Step 2: Configure Database (if needed)

Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bihak');
```

#### Step 3: Run Scraper

Populate opportunities database with 40 sample opportunities:
```bash
cd path/to/bihak-center
php scrapers/run_scrapers.php
```

#### Step 4: Test the System

**Visit these URLs:**
- Homepage: http://localhost/bihak-center/public/index.php
- Opportunities: http://localhost/bihak-center/public/opportunities.php
- User Login: http://localhost/bihak-center/public/login.php
- Admin Login: http://localhost/bihak-center/public/admin/login.php

**Default Credentials:**

**Admin:**
- Username: `admin`
- Password: `Admin@123`

**Demo User:**
- Email: `demo@bihakcenter.org`
- Password: `Demo@123`

#### Step 5: Setup Automatic Scraping (Optional)

See [SCRAPER-SETUP-GUIDE.md](SCRAPER-SETUP-GUIDE.md) for Windows Task Scheduler setup.

## Database Schema

The main database table used:

### `usagers` table
```sql
CREATE TABLE usagers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

See `includes/database.sql` for complete schema.

## Development

### Adding New Pages

1. Create HTML file in `public/` directory
2. Include consistent header and footer
3. Link CSS from `../assets/css/`
4. Link JavaScript from `../assets/js/`

### Modifying Styles

- Main styles: `assets/css/style.css`
- User tiles: `assets/css/user-tiles.css`
- Add new CSS files and link in HTML `<head>`

### Adding JavaScript Features

1. Create new JS file in `assets/js/`
2. Include proper documentation
3. Link in HTML before `</body>`

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security Considerations

- Database credentials are stored in `config.local.php` (not tracked by Git)
- User input is sanitized using `htmlspecialchars()`
- SQL queries use prepared statements where applicable
- Error reporting is disabled in production mode

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## License

Copyright © 2025 Bihak Center. All Rights Reserved.

## Contact

- **Website**: [bihakcenter.org](http://bihakcenter.org)
- **Email**: contact@bihakcenter.org
- **Facebook**: [Bihak Center](https://facebook.com/bihakcenter)
- **Instagram**: [@bihakcenter](https://instagram.com/bihakcenter)
- **Twitter**: [@bihak_center](https://twitter.com/bihak_center)

## Acknowledgments

- Google Fonts for typography
- Google Translate API for multilingual support
- All contributors and supporters of Bihak Center

---

## 🎯 What's New in This Version

### ✅ Completed Features (85% Overall)

1. **Fixed Header System**
   - No more overlapping issues
   - Working language switcher (EN/FR)
   - Admin portal button for admins
   - Proper spacing and responsive design

2. **User Authentication System** ⭐ NEW!
   - Secure login/registration
   - Remember me (30 days)
   - Account lockout protection
   - Activity logging
   - Profile integration

3. **Opportunities System** ⭐ NEW!
   - Browse 40+ opportunities
   - Search and filter
   - Save favorites
   - Deadline tracking
   - View analytics

4. **Web Scraper System** ⭐ NEW!
   - Auto-collect opportunities
   - 4 scraper types (scholarship, job, internship, grant)
   - Scheduled updates
   - Activity logging

5. **Reworked Pages**
   - About page with mission focus
   - Our Work page with impact timeline
   - Contact page with working form
   - All pages bilingual (EN/FR)

### 📚 Documentation

- [COMPLETE-PROJECT-STATUS.md](COMPLETE-PROJECT-STATUS.md) - Full status (85% complete)
- [SCRAPER-SETUP-GUIDE.md](SCRAPER-SETUP-GUIDE.md) - Scraper instructions
- [QUICK-FIX-ADMIN-LOGIN.md](QUICK-FIX-ADMIN-LOGIN.md) - Troubleshooting
- [SESSION-CONTINUATION-GUIDE.md](SESSION-CONTINUATION-GUIDE.md) - Continue development

### ⏳ Coming Soon

- Email notification system
- More scraper sources
- Application tracking
- Recommendation engine

---

**🎉 The Bihak Center platform is ready to empower young people across Africa!**

Visit: http://localhost/bihak-center/public/index.php

Explore opportunities: http://localhost/bihak-center/public/opportunities.php

**Built with care for the youth of tomorrow** ✨
