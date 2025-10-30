# Windows Setup Guide

Quick guide to get your Bihak Center website running on Windows.

## Prerequisites

You'll need to install these if you haven't already:

### 1. PHP (Required)
**Download:** https://windows.php.net/download/

1. Download **PHP 8.x Thread Safe** (zip file)
2. Extract to `C:\php`
3. Add to PATH:
   - Search "Environment Variables" in Windows
   - Edit "Path" under System Variables
   - Add: `C:\php`
4. Verify: Open CMD and type `php --version`

### 2. MySQL (Required)
**Download:** https://dev.mysql.com/downloads/installer/

1. Download **MySQL Installer**
2. Choose "Developer Default" installation
3. Set a root password (remember this!)
4. Add to PATH: `C:\Program Files\MySQL\MySQL Server 8.0\bin`
5. Verify: Open CMD and type `mysql --version`

### 3. Git (Already installed if you're reading this)
If not: https://git-scm.com/download/win

## Easy Setup (Automated)

### Step 1: Run Setup Script

Double-click `setup-windows.bat` or run in Command Prompt:

```cmd
setup-windows.bat
```

This will:
- Check for PHP and MySQL
- Create the database
- Import the schema
- Create configuration file

**Note:** When prompted, enter your MySQL root password

### Step 2: Configure Database

The script will open `config\config.local.php` in Notepad.

Update this line with your MySQL password:
```php
define('DB_PASS', 'your_mysql_password_here');
```

Save and close.

### Step 3: Start the Server

Double-click `start-server.bat` or run:

```cmd
start-server.bat
```

### Step 4: View Website

Open your browser and go to:
```
http://localhost:8000
```

## Manual Setup (If Scripts Fail)

### Create Database Manually

```cmd
mysql -u root -p
```

Then in MySQL:
```sql
CREATE DATABASE bihak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bihak;
source includes/database.sql;
EXIT;
```

### Start Server Manually

```cmd
cd public
php -S localhost:8000
```

## Push to GitHub

### Step 1: Create GitHub Repository

1. Go to https://github.com/new
2. Repository name: `bihak-center-website`
3. Description: `Official website for Bihak Center`
4. Choose Public or Private
5. **Do NOT** check "Initialize with README"
6. Click "Create repository"

### Step 2: Run Push Script

Double-click `push-to-github.bat` or run:

```cmd
push-to-github.bat
```

Follow the prompts:
- Enter your GitHub username
- Enter repository name (or press Enter for default)
- Enter credentials when prompted

**Important:** If prompted for password, use a **Personal Access Token**:
1. Go to https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Give it a name: "Bihak Center Website"
4. Check `repo` scope
5. Copy the token and use it as your password

### Step 3: Verify

Visit your GitHub repository:
```
https://github.com/YOUR_USERNAME/bihak-center-website
```

## Troubleshooting

### PHP Not Found
```cmd
# Add PHP to PATH or use full path:
C:\php\php.exe -S localhost:8000
```

### MySQL Not Found
```cmd
# Add to PATH:
set PATH=%PATH%;C:\Program Files\MySQL\MySQL Server 8.0\bin
```

### Port 8000 Already in Use
```cmd
# Use a different port:
cd public
php -S localhost:8080
```

### Can't Connect to Database
- Check MySQL is running: Open "Services" (Windows key + R, type `services.msc`)
- Find "MySQL80" and ensure it's running
- Verify password in `config\config.local.php`

### GitHub Push Failed

**Authentication Error:**
- Don't use your GitHub password
- Use a Personal Access Token instead
- Create at: https://github.com/settings/tokens

**Repository Not Found:**
- Make sure you created the repository on GitHub first
- Check username spelling

**Manual Push:**
```cmd
git remote add origin https://github.com/YOUR_USERNAME/bihak-center-website.git
git branch -M main
git push -u origin main
```

## Common Commands

```cmd
# Start server
start-server.bat

# Or manually:
cd public
php -S localhost:8000

# Check git status
git status

# Make a new commit
git add .
git commit -m "Your message"
git push

# Database backup
mysqldump -u root -p bihak > backup.sql

# Database restore
mysql -u root -p bihak < backup.sql
```

## File Structure

```
Bihak site - Copie/
├── setup-windows.bat      ← Run this first
├── start-server.bat       ← Run this to start website
├── push-to-github.bat     ← Run this to push to GitHub
├── public/                ← Website files (start server here)
├── assets/                ← CSS, JS, images
├── config/                ← Configuration files
└── includes/              ← Database schemas
```

## Next Steps After Setup

1. ✓ Website running on http://localhost:8000
2. ✓ Code pushed to GitHub
3. Customize the content in `public/` files
4. Modify styles in `assets/css/`
5. Add your opportunities scraper sources
6. Deploy to production server

## Need Help?

- Check [SETUP.md](SETUP.md) for detailed instructions
- Review [README.md](README.md) for full documentation
- Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md) (if available)

## Important Files

- `config\config.local.php` - Database configuration
- `public\index.php` - Homepage
- `assets\css\style.css` - Main styles
- `assets\css\responsive.css` - Mobile responsive styles

---

**Your website is ready to go!** 🚀
