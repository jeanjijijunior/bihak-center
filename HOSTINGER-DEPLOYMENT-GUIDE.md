# Hostinger Deployment Guide - Bihak Center Platform

**Date:** November 30, 2025
**Version:** 1.0
**Project:** Bihak Center - Complete Platform Deployment

---

## 📋 TABLE OF CONTENTS

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Hostinger Account Setup](#hostinger-account-setup)
3. [Database Export and Preparation](#database-export-and-preparation)
4. [File Upload to Hostinger](#file-upload-to-hostinger)
5. [Database Import on Hostinger](#database-import-on-hostinger)
6. [Configuration File Updates](#configuration-file-updates)
7. [Permissions and Security](#permissions-and-security)
8. [Feature Testing Checklist](#feature-testing-checklist)
9. [Performance Optimization](#performance-optimization)
10. [Troubleshooting Common Issues](#troubleshooting-common-issues)

---

## 🔍 PRE-DEPLOYMENT CHECKLIST

### Local Environment Verification

Before deploying, verify all features work locally:

```bash
# 1. Test database connection
C:\xampp\mysql\bin\mysql.exe -u root bihak -e "SELECT COUNT(*) FROM users;"

# 2. Check all tables exist
C:\xampp\mysql\bin\mysql.exe -u root bihak -e "SHOW TABLES;"

# 3. Verify critical data
C:\xampp\mysql\bin\mysql.exe -u root bihak -e "
SELECT
    (SELECT COUNT(*) FROM users) as users,
    (SELECT COUNT(*) FROM profiles) as profiles,
    (SELECT COUNT(*) FROM sponsors) as mentors,
    (SELECT COUNT(*) FROM admins) as admins,
    (SELECT COUNT(*) FROM conversations) as conversations,
    (SELECT COUNT(*) FROM messages) as messages;
"
```

### Files to Review Before Upload

**DO NOT UPLOAD:**
- ❌ `.git/` folder
- ❌ `node_modules/` (if any)
- ❌ Local test files (`test_*.php`)
- ❌ `.env.local` or local config files
- ❌ Database dumps (`.sql` files)
- ❌ All `.md` documentation files (unless you want them on server)

**MUST UPLOAD:**
- ✅ `public/` - All public-facing pages
- ✅ `includes/` - Core PHP classes and functions
- ✅ `config/` - Configuration files (will be updated)
- ✅ `api/` - API endpoints for messaging and mentorship
- ✅ `assets/` - CSS, JS, images
- ✅ `websocket/` - WebSocket server (optional)
- ✅ `.htaccess` - URL rewriting and security

### Create Backup

```bash
# Export current database
C:\xampp\mysql\bin\mysqldump.exe -u root bihak > bihak_backup_2025-11-30.sql

# Create zip of entire project (excluding unnecessary files)
# Use 7-Zip or WinRAR to create: bihak-center-production.zip
# Exclude: .git, *.md, test_*.php, create_*.php, fix_*.php, diagnose_*.php
```

---

## 🌐 HOSTINGER ACCOUNT SETUP

### Step 1: Create Hosting Account

1. Go to [Hostinger.com](https://www.hostinger.com)
2. Choose a hosting plan (recommend **Business Plan** for full features)
3. Register domain or use existing
4. Complete payment and account setup

### Step 2: Access Control Panel

1. Login to Hostinger dashboard
2. Navigate to **hPanel** (Hostinger's control panel)
3. Select your domain/hosting account

### Step 3: Check PHP Version

**Required:** PHP 7.4 or higher (recommend PHP 8.1)

```
hPanel → Advanced → PHP Configuration
```

**Enable these PHP extensions:**
- ✅ `mysqli` - MySQL database connection
- ✅ `pdo_mysql` - PDO MySQL driver
- ✅ `mbstring` - Multi-byte string support
- ✅ `json` - JSON encoding/decoding
- ✅ `gd` - Image processing
- ✅ `fileinfo` - File type detection
- ✅ `openssl` - Encryption
- ✅ `curl` - HTTP requests

### Step 4: Create MySQL Database

```
hPanel → Databases → MySQL Databases → Create New Database
```

**Details to note:**
- Database name: `u123456789_bihak` (Hostinger adds prefix)
- Database user: `u123456789_bihak_user`
- Database password: **Create strong password** (save this!)
- Database host: `localhost` (usually)

**Example:**
```
Database Name: u123456789_bihak
Username: u123456789_bihak_user
Password: Str0ng!P@ssw0rd2025
Host: localhost
```

### Step 5: Enable SSL Certificate

```
hPanel → Security → SSL → Install Free SSL
```

This enables HTTPS for your domain.

---

## 💾 DATABASE EXPORT AND PREPARATION

### Step 1: Export Database from XAMPP

```bash
# Full database export
C:\xampp\mysql\bin\mysqldump.exe -u root bihak > bihak_production.sql

# If database is large, compress it
# Use 7-Zip: Right-click → 7-Zip → Add to archive
# Result: bihak_production.sql.zip
```

### Step 2: Clean Sensitive Data (Optional)

Before uploading, you may want to:

**Remove test accounts:**
```sql
-- Open bihak_production.sql in text editor
-- Find and remove test data if needed

DELETE FROM users WHERE email LIKE '%@test.com';
DELETE FROM sponsors WHERE email LIKE '%@test.com';
DELETE FROM admins WHERE username = 'testadmin';
```

**Reset admin passwords:**
```sql
-- You'll set new passwords after deployment
UPDATE admins SET password_hash = '';
```

### Step 3: Update Database-Specific Values

**Search and replace in SQL file:**

```
Find: `bihak` (database name)
Replace: `u123456789_bihak` (your Hostinger database name)
```

**Only if needed** - Hostinger usually handles this automatically.

---

## 📤 FILE UPLOAD TO HOSTINGER

### Method 1: File Manager (Recommended for Small Sites)

```
hPanel → Files → File Manager
```

1. Navigate to `public_html/` folder
2. Delete default `index.html` if exists
3. Click **Upload Files**
4. Upload `bihak-center-production.zip`
5. Right-click zip → Extract
6. Move files from `bihak-center/` to root `public_html/`

**Final structure should be:**
```
public_html/
├── public/
│   ├── index.php
│   ├── login.php
│   ├── signup.php
│   └── ...
├── includes/
├── config/
├── api/
├── assets/
└── .htaccess
```

### Method 2: FTP/SFTP (Recommended for Large Sites)

**Using FileZilla:**

1. Download [FileZilla](https://filezilla-project.org/)
2. Get FTP credentials from Hostinger:
   ```
   hPanel → Files → FTP Accounts
   ```
3. Create FTP account or use main account
4. Connect via FileZilla:
   - Host: `ftp.yourdomain.com` or IP address
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21 (FTP) or 22 (SFTP - more secure)

5. Upload all files to `public_html/`

**Upload time estimate:**
- Small site (<100MB): 5-10 minutes
- Medium site (100-500MB): 10-30 minutes
- Large site (>500MB): 30+ minutes

---

## 🗄️ DATABASE IMPORT ON HOSTINGER

### Step 1: Access phpMyAdmin

```
hPanel → Databases → Manage → phpMyAdmin
```

### Step 2: Select Your Database

Click on your database name in left sidebar (e.g., `u123456789_bihak`)

### Step 3: Import SQL File

1. Click **Import** tab
2. Click **Choose File**
3. Select `bihak_production.sql`
4. **Important Settings:**
   - Format: SQL
   - Character set: utf8mb4_unicode_ci
   - Max file size: Check limit (usually 50-128MB)
5. Click **Go**

**If file is too large:**

Split into smaller files:
```bash
# On Windows, use Git Bash or WSL
split -l 10000 bihak_production.sql bihak_part_

# This creates:
# bihak_part_aa (first 10000 lines)
# bihak_part_ab (next 10000 lines)
# etc.
```

Import each file separately in order.

### Step 4: Verify Import

```sql
-- Run in phpMyAdmin SQL tab
SHOW TABLES;

SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM profiles;
SELECT COUNT(*) FROM conversations;
SELECT COUNT(*) FROM messages;
```

---

## ⚙️ CONFIGURATION FILE UPDATES

### File 1: config/database.php

**Local version:**
```php
<?php
function getDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'bihak';
    $username = 'root';
    $password = '';

    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}
```

**Production version (update this):**
```php
<?php
function getDatabaseConnection() {
    $host = 'localhost';  // Usually localhost on Hostinger
    $dbname = 'u123456789_bihak';  // ← UPDATE: Your Hostinger database name
    $username = 'u123456789_bihak_user';  // ← UPDATE: Your database username
    $password = 'Str0ng!P@ssw0rd2025';  // ← UPDATE: Your database password

    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please contact administrator.");
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

function closeDatabaseConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
```

### File 2: config/security.php

**Update these values:**

```php
<?php
// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);  // ← IMPORTANT: Set to 1 for HTTPS

// Base URL
define('BASE_URL', 'https://yourdomain.com/');  // ← UPDATE: Your actual domain

// File upload paths
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email configuration (if using)
define('SMTP_HOST', 'smtp.hostinger.com');  // ← Hostinger SMTP
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@yourdomain.com');  // ← UPDATE
define('SMTP_PASSWORD', 'your_email_password');  // ← UPDATE
define('SMTP_FROM', 'noreply@yourdomain.com');  // ← UPDATE
define('SMTP_FROM_NAME', 'Bihak Center');

// Encryption key (generate new one)
define('ENCRYPTION_KEY', 'GENERATE_NEW_KEY_HERE');  // ← UPDATE: Use random 32-character string
?>
```

**Generate encryption key:**
```php
<?php
// Run this once, copy the output, then delete this file
echo bin2hex(random_bytes(16));
?>
```

### File 3: config/session.php

```php
<?php
// Production session configuration
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);  // ← Must be 1 for HTTPS
ini_set('session.cookie_samesite', 'Lax');

session_name('BIHAK_SESSION');
session_start();
?>
```

### File 4: includes/chat_widget.php

**Update API base path:**

```javascript
let chatWidget = {
    isOpen: false,
    activeConversationId: null,
    conversations: [],
    messages: {},
    unreadCount: 0,
    messagePollingInterval: null,
    apiBasePath: '/api/messaging/',  // ← Should work with current setup
    // ... rest of configuration
};
```

**If your domain is in a subdirectory:**
```javascript
apiBasePath: '/bihak/api/messaging/',  // Example if in subdirectory
```

### File 5: websocket/server.js (If using WebSocket)

**Update configuration:**

```javascript
const WebSocket = require('ws');
const port = 8080;  // Make sure this port is open on Hostinger

const wss = new WebSocket.Server({
    port: port,
    host: '0.0.0.0'  // Allow external connections
});

console.log(`WebSocket server running on port ${port}`);
```

**Important:** Check with Hostinger if WebSocket ports are allowed. Business plans usually support this.

---

## 🔐 PERMISSIONS AND SECURITY

### Step 1: Set File Permissions

**Via File Manager or FTP:**

```
Folders: 755 (rwxr-xr-x)
Files: 644 (rw-r--r--)

Exception - Upload directories: 777 (rwxrwxrwx)
```

**Upload folders that need write permission:**
```
assets/uploads/profiles/ → 777
assets/uploads/documents/ → 777
assets/uploads/team_files/ → 777
```

**Protect config files:**
```
config/ → Folder: 755
config/*.php → Files: 644
```

### Step 2: Create/Update .htaccess

**Root .htaccess (public_html/.htaccess):**

```apache
# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable directory browsing
Options -Indexes

# Protect config files
<FilesMatch "^(config|database|security)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect .htaccess itself
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>

# PHP security settings
<IfModule mod_php7.c>
    php_flag display_errors Off
    php_flag log_errors On
    php_value error_log /home/username/logs/php_errors.log
</IfModule>

# Enable HTTPS redirect
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# URL Rewriting (if needed)
RewriteEngine On
RewriteBase /

# Redirect all requests to public/ folder
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L]
```

### Step 3: Secure Database Credentials

**Option 1: Environment Variables (Recommended)**

Create `config/.env` (if supported):
```
DB_HOST=localhost
DB_NAME=u123456789_bihak
DB_USER=u123456789_bihak_user
DB_PASS=Str0ng!P@ssw0rd2025
```

Then in `config/database.php`:
```php
<?php
// Load environment variables
$env = parse_ini_file(__DIR__ . '/.env');

function getDatabaseConnection() {
    global $env;
    $conn = new mysqli(
        $env['DB_HOST'],
        $env['DB_USER'],
        $env['DB_PASS'],
        $env['DB_NAME']
    );
    // ... rest of code
}
?>
```

**Option 2: Outside Web Root**

Move `config/` folder outside `public_html/`:
```
home/
├── public_html/
│   └── public/
└── config/  ← Move here
    └── database.php
```

Update includes:
```php
require_once __DIR__ . '/../../config/database.php';
```

---

## ✅ FEATURE TESTING CHECKLIST

### Test 1: Basic Functionality

**Homepage:**
```
✅ Visit https://yourdomain.com
✅ Logo and header display correctly
✅ All CSS/JS files load (check browser console)
✅ No 404 errors
✅ Footer displays
```

### Test 2: User Registration

```
✅ Visit /public/signup.php
✅ Fill form with test data:
   - Full Name: Test User Production
   - Email: test@yourdomain.com
   - Password: Test@123
   - Country: Select from dropdown (195 countries)
   - Phone: +250 123 456 789
✅ Upload profile picture
✅ Set security questions
✅ Submit form
✅ Check database: New user created with status 'pending'
✅ Email verification sent (if configured)
```

### Test 3: User Login

```
✅ Visit /public/login.php
✅ Login with test account
✅ Redirect to my-account.php
✅ Session persists across pages
✅ User profile loads correctly
```

### Test 4: Admin Login

```
✅ Visit /public/admin/login.php
✅ Login as admin
✅ Dashboard loads
✅ Can view users, profiles, mentors
✅ Can approve/reject profiles
✅ All admin tabs work (Content, Donations, Activity Log)
```

### Test 5: Profile Approval

```
Admin Panel:
✅ Navigate to Users → Profiles
✅ Find test user (status: pending)
✅ Click "Approve"
✅ Status changes to "approved"

User Account:
✅ Logout and login again as test user
✅ Chat widget now appears (was hidden before approval)
✅ Can access all features
```

### Test 6: Messaging System

**Test as User:**
```
✅ Chat widget appears in bottom-right
✅ Click chat icon to open
✅ Click + to start new conversation
✅ Search for "Admin" or mentor name
✅ Click to start conversation
✅ Send message: "Hello, testing chat system"
✅ Message appears in conversation
✅ Conversation list auto-refreshes (every 3 seconds)
```

**Test as Admin/Mentor:**
```
✅ Login as admin or mentor
✅ Open chat widget
✅ See conversation with test user
✅ Unread badge shows "1" (orange badge)
✅ Conversation highlighted with blue background
✅ Click conversation
✅ See "Hello, testing chat system" message
✅ Badge disappears (marked as read)
✅ Reply: "Hi! Chat system working perfectly"
✅ Message sends successfully
```

**Test Real-Time Updates:**
```
✅ Open two browsers side-by-side
✅ Browser A: Login as user
✅ Browser B: Login as admin
✅ Send message from A to B
✅ Within 3 seconds, message appears in B
✅ Send reply from B to A
✅ Within 3 seconds, message appears in A
✅ No page refresh needed
✅ No blinking or jumping
```

### Test 7: Unread Message Indicators

```
✅ Browser A: Send message to user in Browser B
✅ Browser B: Don't open conversation yet
✅ Wait 3 seconds
✅ Conversation appears with:
   - Blue background
   - Blue left border
   - Bold conversation name
   - Orange badge showing "1"
✅ Click conversation
✅ View messages
✅ Go back to conversation list
✅ Badge gone, blue highlighting removed
```

### Test 8: Mentorship System

```
✅ Admin: Assign mentor to test user
✅ User: View mentor in my-account.php
✅ User: Can message mentor via chat
✅ Mentor: Can message user
✅ Mentor: Can view mentee profile
✅ Goals and milestones tracking works
```

### Test 9: Incubation Platform

```
✅ User: Enroll in incubation program
✅ Create or join team
✅ View incubation dashboard
✅ Access exercises
✅ Submit exercise responses
✅ Upload team files
✅ Progress bar updates correctly
✅ Admin: Can review submissions
✅ Admin: Can provide feedback
```

### Test 10: File Uploads

```
✅ Profile pictures upload successfully
✅ Files save to assets/uploads/profiles/
✅ Images display correctly
✅ File size limits enforced (2MB for profiles)
✅ Only allowed file types accepted (jpg, png, gif, pdf)
✅ Team document uploads work
✅ Multiple file uploads work
```

### Test 11: Security Features

```
✅ Non-approved users don't see chat widget
✅ SQL injection protection (try in login form)
✅ XSS prevention (try <script> in message)
✅ CSRF tokens on forms
✅ Failed login attempts tracked
✅ Account locks after 5 failed attempts
✅ Password reset works
✅ Security questions work
```

### Test 12: Performance

```
✅ Page load time < 3 seconds
✅ Chat widget doesn't slow down page
✅ Image optimization working
✅ Database queries optimized
✅ No memory leaks (check browser DevTools)
```

---

## 🚀 PERFORMANCE OPTIMIZATION

### Step 1: Enable PHP OpCache

**In hPanel → PHP Configuration:**

```
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
```

### Step 2: Enable Gzip Compression

**Add to .htaccess:**

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### Step 3: Browser Caching

**Add to .htaccess:**

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Step 4: Optimize Database

**Run in phpMyAdmin:**

```sql
-- Optimize all tables
OPTIMIZE TABLE users, profiles, sponsors, admins, conversations, messages, message_read_receipts;

-- Add indexes for better performance
ALTER TABLE messages ADD INDEX idx_conversation_id (conversation_id);
ALTER TABLE messages ADD INDEX idx_created_at (created_at);
ALTER TABLE message_read_receipts ADD INDEX idx_message_reader (message_id, reader_type);
ALTER TABLE conversations ADD INDEX idx_created_at (created_at);
```

### Step 5: Image Optimization

**Optimize images before upload:**
- Use TinyPNG or ImageOptim
- Target: <200KB per image
- Format: JPG for photos, PNG for logos

### Step 6: Minify CSS/JS (Optional)

Use online tools:
- [CSS Minifier](https://cssminifier.com/)
- [JavaScript Minifier](https://javascript-minifier.com/)

Or use build tools like Gulp/Webpack.

---

## 🔧 TROUBLESHOOTING COMMON ISSUES

### Issue 1: White Screen / 500 Error

**Possible causes:**
1. PHP syntax error
2. Missing PHP extensions
3. Wrong file permissions

**Solution:**

**Enable error reporting temporarily:**

Create `public/test.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
phpinfo();
?>
```

Visit: `https://yourdomain.com/public/test.php`

Check for:
- PHP version (need 7.4+)
- mysqli extension enabled
- Error messages

**Check error logs:**
```
hPanel → Advanced → Error Logs
```

**Fix permissions:**
```bash
# Via SSH (if available)
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 777 assets/uploads/profiles/
```

---

### Issue 2: Database Connection Failed

**Error:** "Database connection error"

**Solutions:**

1. **Verify database credentials:**
   - Check `config/database.php`
   - Match with Hostinger database settings

2. **Check database exists:**
   ```
   hPanel → Databases → MySQL Databases
   ```
   Verify database name and user

3. **Test connection:**

Create `test_db.php`:
```php
<?php
$conn = new mysqli('localhost', 'u123456789_bihak_user', 'your_password', 'u123456789_bihak');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connected successfully!";
}

$result = $conn->query("SHOW TABLES");
echo "<br>Tables: " . $result->num_rows;
?>
```

4. **Check MySQL version:**
   ```sql
   SELECT VERSION();
   ```
   Must be 5.6+ (preferably 8.0)

---

### Issue 3: Chat Widget Not Appearing

**Possible causes:**
1. User profile not approved
2. JavaScript errors
3. API path incorrect

**Solution:**

1. **Check browser console:**
   - F12 → Console tab
   - Look for errors like "404 Not Found" or "Failed to fetch"

2. **Verify profile status:**
   ```sql
   SELECT u.email, p.status
   FROM users u
   LEFT JOIN profiles p ON u.profile_id = p.id
   WHERE u.email = 'test@yourdomain.com';
   ```
   Must be 'approved'

3. **Check API paths:**
   ```javascript
   // In includes/chat_widget.php
   apiBasePath: '/api/messaging/',  // Verify this path
   ```

4. **Test API directly:**
   Visit: `https://yourdomain.com/api/messaging/conversations.php`
   Should return JSON (not 404)

---

### Issue 4: File Upload Fails

**Error:** "Failed to upload file"

**Solutions:**

1. **Check upload folder permissions:**
   ```
   assets/uploads/profiles/ → 777
   ```

2. **Check PHP upload settings:**
   ```
   hPanel → PHP Configuration
   ```
   - `upload_max_filesize` = 10M
   - `post_max_size` = 10M
   - `max_file_uploads` = 20

3. **Check disk space:**
   ```
   hPanel → Dashboard → Disk Usage
   ```

4. **Verify folder exists:**
   ```
   public_html/assets/uploads/profiles/
   ```

---

### Issue 5: Messages Not Updating

**Problem:** Chat not showing new messages

**Solutions:**

1. **Check polling is working:**
   - F12 → Network tab
   - Filter: "messages.php"
   - Should see requests every 3 seconds

2. **Verify API response:**
   - Click on request in Network tab
   - Check Response tab
   - Should be JSON with messages

3. **Check JavaScript errors:**
   - Console tab should be clean
   - No "Uncaught" errors

4. **Clear browser cache:**
   - Ctrl+Shift+Delete
   - Clear cached images and files

---

### Issue 6: SSL/HTTPS Issues

**Error:** "Not Secure" warning

**Solutions:**

1. **Install SSL certificate:**
   ```
   hPanel → Security → SSL → Install Free SSL
   ```
   Wait 10-15 minutes for activation

2. **Force HTTPS redirect:**
   Add to `.htaccess`:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Update config:**
   ```php
   // config/security.php
   ini_set('session.cookie_secure', 1);
   define('BASE_URL', 'https://yourdomain.com/');
   ```

---

### Issue 7: Email Not Sending

**Problem:** Email verification/reset emails not received

**Solutions:**

1. **Configure SMTP settings:**
   ```php
   // config/security.php
   define('SMTP_HOST', 'smtp.hostinger.com');
   define('SMTP_PORT', 587);
   define('SMTP_USERNAME', 'noreply@yourdomain.com');
   define('SMTP_PASSWORD', 'your_email_password');
   ```

2. **Create email account in Hostinger:**
   ```
   hPanel → Email → Email Accounts → Create
   ```

3. **Test with simple mail:**
   ```php
   <?php
   $to = 'your@email.com';
   $subject = 'Test Email';
   $message = 'Test message from Bihak Center';
   $headers = 'From: noreply@yourdomain.com';

   if (mail($to, $subject, $message, $headers)) {
       echo "✅ Email sent!";
   } else {
       echo "❌ Email failed!";
   }
   ?>
   ```

4. **Check spam folder**

---

### Issue 8: Session Lost on Page Change

**Problem:** User logged out after clicking link

**Solutions:**

1. **Check session settings:**
   ```php
   // config/session.php
   ini_set('session.cookie_secure', 1);  // Must be 1 for HTTPS
   ini_set('session.cookie_samesite', 'Lax');
   ```

2. **Verify all pages start session:**
   ```php
   <?php
   session_start();  // Must be at top of every page
   ?>
   ```

3. **Check session path:**
   ```php
   ini_set('session.save_path', '/tmp');
   ```

4. **Test session:**
   Create `test_session.php`:
   ```php
   <?php
   session_start();
   $_SESSION['test'] = 'Session working!';
   echo $_SESSION['test'];
   ?>
   ```

---

## 📊 POST-DEPLOYMENT CHECKLIST

### Immediate (Within 1 Hour)

```
✅ All pages load without errors
✅ Database connection working
✅ User registration works
✅ User login works
✅ Admin login works
✅ Chat widget appears for approved users
✅ Messages send successfully
✅ File uploads work
✅ SSL certificate active (HTTPS)
✅ No PHP errors in logs
```

### Short-term (Within 24 Hours)

```
✅ Test all user roles (user, admin, mentor)
✅ Test messaging between all user types
✅ Test incubation platform features
✅ Test mentorship system
✅ Verify email sending
✅ Performance check (page load times)
✅ Mobile responsiveness
✅ Cross-browser testing (Chrome, Firefox, Safari, Edge)
```

### Ongoing (First Week)

```
✅ Monitor error logs daily
✅ Check database performance
✅ Monitor disk usage
✅ User feedback collection
✅ Security audit
✅ Backup verification
✅ API performance monitoring
```

---

## 🔄 BACKUP STRATEGY

### Daily Backups

**Database:**
```bash
# Via cron job (if SSH access available)
0 2 * * * mysqldump -u username -p'password' u123456789_bihak > /backups/daily/bihak_$(date +\%Y\%m\%d).sql
```

**Via Hostinger:**
```
hPanel → Backups → Create Backup
Frequency: Daily (automatic on Business plans)
```

### Weekly File Backups

```
hPanel → File Manager → Select all → Compress → Download
```

Save to local machine or cloud storage (Google Drive, Dropbox)

### Backup Retention

- Daily backups: Keep 7 days
- Weekly backups: Keep 4 weeks
- Monthly backups: Keep 12 months

---

## 📈 MONITORING AND ANALYTICS

### Setup Google Analytics

1. Create Google Analytics account
2. Get tracking ID (e.g., G-XXXXXXXXXX)
3. Add to `includes/header_new.php`:

```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>
```

### Monitor Uptime

Use services like:
- [UptimeRobot](https://uptimerobot.com/) (Free)
- [Pingdom](https://www.pingdom.com/)
- [StatusCake](https://www.statuscake.com/)

### Database Monitoring

**Create monitoring query:**

```sql
-- Check system health
SELECT
    (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
    (SELECT COUNT(*) FROM conversations WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as recent_conversations,
    (SELECT COUNT(*) FROM messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as messages_today;
```

---

## 🎯 FINAL VERIFICATION

Run through this complete checklist before going live:

### Technical Checklist

```
✅ PHP version 7.4+ installed
✅ All required PHP extensions enabled
✅ Database imported successfully
✅ All configuration files updated
✅ File permissions set correctly
✅ SSL certificate active
✅ HTTPS redirect working
✅ Error logs clean
✅ No 404 errors on any page
✅ All API endpoints responding
```

### Security Checklist

```
✅ Database credentials secured
✅ Config files protected via .htaccess
✅ Error display disabled in production
✅ Session security enabled (secure cookies)
✅ File upload restrictions in place
✅ SQL injection protection verified
✅ XSS protection verified
✅ CSRF tokens implemented
✅ Password hashing using bcrypt
✅ Admin panel password protected
```

### Feature Checklist

```
✅ User registration working
✅ Email verification working (if enabled)
✅ User login working
✅ Admin login working
✅ Mentor login working
✅ Profile approval workflow working
✅ Chat widget functional
✅ Real-time messaging working
✅ Unread message badges working
✅ Conversation highlighting working
✅ Mentorship system working
✅ Incubation platform working
✅ File uploads working
✅ All forms submitting correctly
```

### Performance Checklist

```
✅ Homepage loads in < 3 seconds
✅ Database queries optimized
✅ Images optimized
✅ Gzip compression enabled
✅ Browser caching configured
✅ OpCache enabled
✅ No memory leaks
✅ Mobile performance acceptable
```

---

## 📞 SUPPORT RESOURCES

### Hostinger Support

- **Knowledge Base:** https://support.hostinger.com
- **Live Chat:** Available 24/7
- **Email:** support@hostinger.com
- **Phone:** Check your region

### Common Hostinger Docs

- [How to upload website](https://support.hostinger.com/en/articles/1583245-how-to-upload-a-website)
- [How to create database](https://support.hostinger.com/en/articles/1583188-how-to-create-a-mysql-database)
- [How to install SSL](https://support.hostinger.com/en/articles/1583357-how-to-install-ssl-certificate)
- [How to configure email](https://support.hostinger.com/en/articles/1583214-how-to-create-an-email-account)

---

## ✅ DEPLOYMENT COMPLETE!

Once all tests pass, your Bihak Center platform is live and ready for users!

**Next Steps:**

1. **Announce Launch** - Inform stakeholders
2. **User Training** - Provide documentation
3. **Monitor Closely** - Watch for issues in first 48 hours
4. **Collect Feedback** - Improve based on real usage
5. **Regular Maintenance** - Keep platform updated and secure

---

**Document Version:** 1.0
**Last Updated:** November 30, 2025
**Prepared for:** Bihak Center Platform Deployment

---

**Need help?** Contact your development team or Hostinger support.

**Good luck with your deployment! 🚀**
