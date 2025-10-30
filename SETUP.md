# Quick Setup Guide

This guide will help you get the Bihak Center website running locally in minutes.

## Prerequisites

Before you begin, ensure you have:
- PHP 7.4+ installed
- MySQL/MariaDB installed
- A web server (Apache/Nginx) or PHP built-in server
- Git (already initialized)

## Quick Start (5 Minutes)

### Step 1: Database Setup (2 minutes)

```bash
# Create the database
mysql -u root -p -e "CREATE DATABASE bihak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import the schema
mysql -u root -p bihak < includes/database.sql
```

### Step 2: Configuration (1 minute)

```bash
# Copy the example config
cp config/config.example.php config/config.local.php

# Edit with your credentials (use any text editor)
nano config/config.local.php
```

Update these lines:
```php
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', 'your_password'); // Your MySQL password
```

### Step 3: Run the Website (1 minute)

**Option A: PHP Built-in Server (Easiest)**
```bash
cd public
php -S localhost:8000
```
Then visit: http://localhost:8000

**Option B: Apache**
```bash
# Point DocumentRoot to the public/ directory
# Example Apache config:
<VirtualHost *:80>
    DocumentRoot "/path/to/bihak-center/public"
    ServerName bihak.local
    <Directory "/path/to/bihak-center/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Step 4: Test (1 minute)

Visit http://localhost:8000 (or your configured URL)

You should see:
- The Bihak Center homepage
- Navigation working
- Images loading
- Language switcher functional

## Optional: Opportunity Scraper Setup

If you want to enable automated opportunity scraping:

### Install Python Dependencies
```bash
pip install -r scripts/scrapers/requirements.txt
```

### Download ChromeDriver
1. Check Chrome version: `google-chrome --version`
2. Download matching ChromeDriver from https://chromedriver.chromium.org/downloads
3. Add to PATH or place in project root

### Configure Sources
Edit `scripts/scrapers/opportunities_scraper.py` and update the `SOURCES` list with actual websites.

### Test the Scraper
```bash
cd scripts/scrapers
python opportunities_scraper.py
```

### Schedule Daily Runs

**Linux/Mac (cron):**
```bash
crontab -e
# Add this line to run daily at 6 AM:
0 6 * * * cd /path/to/scripts/scrapers && python opportunities_scraper.py
```

**Windows (Task Scheduler):**
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger: Daily at 6:00 AM
4. Action: `python.exe C:\path\to\scripts\scrapers\opportunities_scraper.py`

## Troubleshooting

### Database Connection Failed
```bash
# Check MySQL is running
sudo service mysql status  # Linux
# or
mysql.server status        # Mac

# Verify credentials
mysql -u root -p
```

### Images Not Loading
```bash
# Check file permissions
chmod 755 assets/images/
chmod 644 assets/images/*
```

### Pages Not Found (404)
- Ensure you're in the `public/` directory
- Check `.htaccess` is present
- Enable `mod_rewrite` for Apache

### CSS Not Loading
- Clear browser cache (Ctrl+F5)
- Check file paths are relative: `../assets/css/`
- Verify files exist: `ls assets/css/`

## Next Steps

1. **Customize Content**: Edit HTML files in `public/` directory
2. **Update Styles**: Modify CSS files in `assets/css/`
3. **Add Features**: See [CHANGELOG.md](CHANGELOG.md) for planned features
4. **Contribute**: Read [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines

## Development Workflow

```bash
# Create a new branch for your feature
git checkout -b feature/your-feature-name

# Make your changes
# ... edit files ...

# Test your changes
php -S localhost:8000

# Commit your changes
git add .
git commit -m "Add: your feature description"

# Push to remote (when ready)
git push origin feature/your-feature-name
```

## Need Help?

- Check [README.md](README.md) for detailed documentation
- Review [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines
- Open an issue on GitHub (if available)
- Contact: contact@bihakcenter.org

## Common Commands Reference

```bash
# Start development server
cd public && php -S localhost:8000

# Check Git status
git status

# View commit history
git log --oneline

# Create new branch
git checkout -b branch-name

# Database backup
mysqldump -u root -p bihak > backup.sql

# Database restore
mysql -u root -p bihak < backup.sql

# Run scraper
cd scripts/scrapers && python opportunities_scraper.py

# Check file structure
tree -L 2 -I '.git'
```

---

**You're all set!** The Bihak Center website is now running locally. Start building amazing features!
