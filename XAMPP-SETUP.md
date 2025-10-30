# XAMPP Setup Guide (EASIEST for Windows)

XAMPP is an all-in-one package with PHP, MySQL, and Apache. **One installer, everything works!**

## Why XAMPP?

✅ One installer has everything (PHP, MySQL, Apache)
✅ No complex configuration needed
✅ Easy control panel to start/stop services
✅ Perfect for Windows
✅ Free and open-source

## Installation (10 Minutes)

### Step 1: Download XAMPP

Go to: **https://www.apachefriends.org/download.html**

- Click "Download" for Windows
- Choose the latest version (PHP 8.1+)
- File size: ~150 MB

### Step 2: Install XAMPP

1. Run the downloaded `.exe` file
2. Click "Next" through the installer
3. **Installation folder:** Keep default `C:\xampp`
4. Uncheck "Learn more about Bitnami" (optional)
5. Click "Finish"

### Step 3: Start Services

1. Open **XAMPP Control Panel** (search in Windows Start Menu)
2. Click **"Start"** next to:
   - ✅ **Apache** (for PHP/web server)
   - ✅ **MySQL** (for database)
3. Wait until both show **green "Running"** status

**Troubleshooting:** If port 80 is busy:
- Click "Config" → "Apache (httpd.conf)"
- Change `Listen 80` to `Listen 8080`
- Save and restart Apache

### Step 4: Test XAMPP

Open browser and go to: **http://localhost**

You should see the XAMPP welcome page!

## Setup Bihak Center Website

### Step 1: Copy Project Files

Copy your entire project folder to:
```
C:\xampp\htdocs\bihak-center
```

So the structure is:
```
C:\xampp\htdocs\bihak-center\
├── public\
├── assets\
├── config\
└── includes\
```

### Step 2: Create Database

1. Open browser: **http://localhost/phpmyadmin**
2. Click "New" (left sidebar)
3. Database name: `bihak`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### Step 3: Import Schema

1. Click on `bihak` database (left sidebar)
2. Click "Import" tab
3. Click "Choose File"
4. Select: `C:\xampp\htdocs\bihak-center\includes\profiles_schema.sql`
5. Click "Go" at bottom
6. Wait for success message ✅

### Step 4: Configure Database Connection

Open: `C:\xampp\htdocs\bihak-center\config\config.local.php`

Make sure it has:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // XAMPP default: no password
define('DB_NAME', 'bihak');
```

**Note:** XAMPP MySQL default password is empty (no password)

### Step 5: Update File Paths

Edit: `C:\xampp\htdocs\bihak-center\public\index.php`

Change the include path at top:
```php
require_once __DIR__ . '/../config/database.php';
```

### Step 6: Test Website

Open browser: **http://localhost/bihak-center/public/**

You should see the Bihak Center homepage! 🎉

## Quick Commands

**Start/Stop Services:**
- Open XAMPP Control Panel
- Click "Start" or "Stop" for Apache/MySQL

**Access phpMyAdmin (Database Manager):**
```
http://localhost/phpmyadmin
```

**Access Your Website:**
```
http://localhost/bihak-center/public/
```

**Check Errors:**
View `C:\xampp\apache\logs\error.log`

## Default Credentials

**MySQL:**
- Username: `root`
- Password: *(empty)*

**Admin Login (after setup):**
- Username: `admin`
- Password: `admin123` (CHANGE THIS!)

## Common Issues

### Apache won't start (Port 80 busy)

**Solution 1:** Stop Skype/IIS
- Skype: Tools → Options → Advanced → Connection → Uncheck "Use port 80"
- IIS: Open Services → Stop "World Wide Web Publishing Service"

**Solution 2:** Change Apache port
1. XAMPP Control → Apache Config → httpd.conf
2. Change `Listen 80` to `Listen 8080`
3. Restart Apache
4. Access via: `http://localhost:8080`

### MySQL won't start (Port 3306 busy)

Check if another MySQL is running:
1. Open Task Manager
2. Find and end "mysql" processes
3. Restart MySQL in XAMPP

### Database connection failed

Check:
- MySQL is running (green in XAMPP Control)
- Database name is `bihak`
- Username is `root`
- Password is empty
- config.local.php has correct settings

### Page shows PHP code instead of running

- Make sure Apache is running
- Access via `http://localhost/...` NOT by opening file directly
- File must have `.php` extension

## File Upload Configuration

For profile image/video uploads, check PHP settings:

Edit: `C:\xampp\php\php.ini`

Find and update:
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 5
```

Restart Apache after changes.

## Security for Production

**Before deploying online:**

1. **Change default admin password:**
   ```sql
   UPDATE admins SET password_hash = PASSWORD('new_secure_password') WHERE username = 'admin';
   ```

2. **Set MySQL password:**
   - Open phpMyAdmin
   - Click "User accounts" → "root"
   - Click "Change password"
   - Set a strong password

3. **Update config.local.php** with new password

4. **Hide phpMyAdmin** in production

## Next Steps

1. ✅ XAMPP installed and running
2. ✅ Database created and imported
3. ✅ Website accessible at localhost
4. ➡️ Create user registration page
5. ➡️ Create admin dashboard
6. ➡️ Test on mobile phone (use your local IP)

## Testing on Mobile Phone

1. Get your computer's IP:
   - Open CMD: `ipconfig`
   - Look for "IPv4 Address" (e.g., `192.168.1.10`)

2. Make sure computer and phone are on **same WiFi**

3. On phone browser, go to:
   ```
   http://192.168.1.10/bihak-center/public/
   ```

4. Your mobile-responsive website will load! 📱

## Backup Database

```sql
-- Export from phpMyAdmin: Export tab → Go
-- Or via command:
C:\xampp\mysql\bin\mysqldump -u root bihak > backup.sql
```

---

**XAMPP is now ready!** Let's build the registration and admin pages! 🚀
