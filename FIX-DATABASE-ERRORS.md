# 🔧 Fix Database Errors - Step by Step

## Errors You're Seeing

```
Fatal error: Call to a member function execute() on bool
Fatal error: Call to a member function bind_param() on bool
```

## What This Means

Your database tables **don't exist**. The PHP code is trying to use tables that aren't there yet.

---

## 🎯 THE FIX (Choose One Method)

### Method 1: phpMyAdmin (Recommended - Visual & Easy)

#### Step 1: Make Sure MySQL is Running
1. Open **XAMPP Control Panel**
2. Look at MySQL line
3. If it says "Running" or the Start button is grayed out, you're good
4. If not, click **"Start"** next to MySQL

#### Step 2: Open phpMyAdmin
1. In your browser, go to: **http://localhost/phpmyadmin**
2. You should see a database management interface

#### Step 3: Import Database
1. Click the **"Import"** tab at the top
2. Click **"Choose File"** button
3. Browse to your project folder:
   ```
   C:\Users\JeanJuniorNiyonkuru\Downloads\Bihak site - Copie\Bihak site - Copie\
   ```
4. Select the file: **`IMPORT-ALL-DATABASE.sql`**
5. Scroll to the bottom
6. Click **"Go"** button
7. Wait 5-15 seconds

#### Step 4: Verify Success
You should see a green message:
```
✓ Import has been successfully finished
✓ 15 queries executed
```

Click "bihak" in the left sidebar. You should see these tables:
- admins
- admin_activity_log
- admin_sessions
- opportunities
- opportunity_tags
- profiles
- rate_limits
- scraper_log
- users
- user_activity_log
- user_saved_opportunities
- user_sessions

**If you see these tables, YOU'RE DONE! ✅**

#### Step 5: Test Your Website
Go to: http://localhost/bihak-center/public/opportunities.php

**It should work without errors!**

---

### Method 2: Command Line (Faster for Advanced Users)

1. **Open Command Prompt in your project folder**
   - Navigate to: `C:\Users\JeanJuniorNiyonkuru\Downloads\Bihak site - Copie\Bihak site - Copie\`
   - Or: Hold Shift, right-click in folder, click "Open command window here"

2. **Run this command:**
   ```bash
   "C:\xampp\mysql\bin\mysql.exe" -u root < IMPORT-ALL-DATABASE.sql
   ```

3. **Check for errors**
   - If you see nothing, it worked!
   - If you see "ERROR", continue to troubleshooting below

4. **Test your website:**
   http://localhost/bihak-center/public/opportunities.php

---

### Method 3: Use Batch Script (Automated)

1. **Double-click:** `SETUP-DATABASE.bat`
2. **Follow the on-screen instructions**
3. **Done!**

---

## 🔍 Troubleshooting

### Problem: "MySQL is not running"

**Solution:**
1. Open XAMPP Control Panel
2. Click "Start" next to MySQL
3. Wait for it to turn green
4. Try again

### Problem: "Access denied for user 'root'"

This means MySQL has a password set.

**Solution A - Use phpMyAdmin (Easier):**
1. Go to http://localhost/phpmyadmin
2. Click "Import" tab
3. Upload `IMPORT-ALL-DATABASE.sql`
4. Click "Go"

**Solution B - Add Password to Command:**
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root -p < IMPORT-ALL-DATABASE.sql
```
It will ask for your MySQL password. Enter it and press Enter.

### Problem: "Can't open file 'IMPORT-ALL-DATABASE.sql'"

**Solution:**
Make sure you're in the correct directory.

```bash
# First, navigate to your project:
cd "C:\Users\JeanJuniorNiyonkuru\Downloads\Bihak site - Copie\Bihak site - Copie"

# Then run the import:
"C:\xampp\mysql\bin\mysql.exe" -u root < IMPORT-ALL-DATABASE.sql
```

### Problem: "Database already exists"

**Solution:**
The old database needs to be replaced.

**In phpMyAdmin:**
1. Click "bihak" in the left sidebar
2. Click "Operations" tab
3. Scroll to "Remove database"
4. Click "Drop the database (DROP)"
5. Confirm
6. Now import `IMPORT-ALL-DATABASE.sql` again

**Or via Command Line:**
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS bihak;"
"C:\xampp\mysql\bin\mysql.exe" -u root < IMPORT-ALL-DATABASE.sql
```

### Problem: Still getting errors after import

**Solution:**
Check that the database actually imported correctly.

1. Go to http://localhost/phpmyadmin
2. Click "bihak" database
3. You should see 12+ tables
4. Click on "opportunities" table
5. Click "Browse" tab
6. You should see at least 4 rows of sample data

If tables are empty or missing, try importing again.

---

## 🧪 Verify Everything Works

After importing, test each component:

### 1. Test Opportunities Page
**URL:** http://localhost/bihak-center/public/opportunities.php

**Expected:**
- Page loads without errors
- You see 4 sample opportunities
- Filter tabs work (All, Scholarships, Jobs, etc.)

### 2. Test User Login
**URL:** http://localhost/bihak-center/public/login.php

**Credentials:**
- Email: `demo@bihakcenter.org`
- Password: `Demo@123`

**Expected:**
- Login succeeds
- Redirects to My Account page

### 3. Test Admin Login
**URL:** http://localhost/bihak-center/public/admin/login.php

**Credentials:**
- Username: `admin`
- Password: `Admin@123`

**Expected:**
- Login succeeds
- Shows admin dashboard

---

## 🚀 Load More Opportunities

After database works, load 40 opportunities:

```bash
cd "C:\Users\JeanJuniorNiyonkuru\Downloads\Bihak site - Copie\Bihak site - Copie"
php scrapers\run_scrapers.php
```

This adds:
- 8 Scholarships (MasterCard, DAAD, Chevening, etc.)
- 10 Jobs (Software Engineer, Data Analyst, etc.)
- 10 Internships (UN, World Bank, etc.)
- 12 Grants (Youth Innovation, Climate Action, etc.)

---

## 📋 Quick Checklist

Before asking for help, verify:

- [ ] XAMPP is installed
- [ ] Apache is running (green in XAMPP)
- [ ] MySQL is running (green in XAMPP)
- [ ] phpMyAdmin loads (http://localhost/phpmyadmin)
- [ ] You imported `IMPORT-ALL-DATABASE.sql`
- [ ] "bihak" database appears in phpMyAdmin
- [ ] Tables are visible in the database
- [ ] You can see sample data in the tables

If all checked, your website should work! ✅

---

## 🆘 Still Need Help?

If nothing works:

1. **Check PHP version:**
   ```bash
   php -v
   ```
   Should be PHP 7.4 or higher

2. **Check if database.php has correct settings:**
   Open: `config/database.php`

   Should have:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'bihak');
   ```

3. **Test database connection:**
   ```bash
   php -r "new mysqli('localhost', 'root', '', 'bihak') or die('Failed');"
   ```

   If it says "Failed", your MySQL isn't working correctly.

4. **Restart everything:**
   - Close XAMPP
   - Wait 10 seconds
   - Open XAMPP
   - Start Apache
   - Start MySQL
   - Try again

---

## ✅ Success!

When it works, you'll be able to:
- ✅ Browse opportunities without errors
- ✅ Login as admin
- ✅ Login as user
- ✅ Save favorite opportunities
- ✅ Search and filter opportunities
- ✅ See sample profiles

**Your Bihak Center website is ready! 🎉**

---

## 📚 Next Steps

Once database is working:

1. **Load more opportunities** - Run the scraper
2. **Test all features** - Try logging in, saving opportunities
3. **Customize content** - Add your own profiles
4. **Setup scheduler** - Automate opportunity updates

See `COMPLETE-PROJECT-STATUS.md` for the full guide!
