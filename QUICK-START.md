# 🚀 QUICK START - Fix Database in 2 Minutes!

## The Problem
You're seeing errors like:
- `Call to a member function execute() on bool`
- `Call to a member function bind_param() on bool`

**This means the database tables don't exist yet!**

---

## ✅ The Solution (2 Minutes)

### Option 1: Use phpMyAdmin (EASIEST)

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Click "Start" for Apache
   - Click "Start" for MySQL
   - Wait until both are green

2. **Open phpMyAdmin**
   - Go to: http://localhost/phpmyadmin
   - You should see the phpMyAdmin interface

3. **Import the Database**
   - Click "Import" tab at the top
   - Click "Choose File"
   - Navigate to your project folder
   - Select: **`IMPORT-ALL-DATABASE.sql`** ⬅ THIS FILE!
   - Scroll down and click "Go"
   - Wait 5-10 seconds

4. **Done!** You should see:
   ```
   ✓ Import has been successfully finished
   ✓ 15 queries executed
   ```

5. **Verify**
   - Click "bihak" database in the left sidebar
   - You should see tables like:
     - admins
     - opportunities
     - profiles
     - users
     - and more...

6. **Test Your Website**
   - Visit: http://localhost/bihak-center/public/opportunities.php
   - You should see the opportunities page WITHOUT errors!

---

### Option 2: Use Command Line (FASTER)

1. **Open Command Prompt in project folder**
   - Hold Shift + Right-click in folder
   - Click "Open PowerShell window here" or "Open command window here"

2. **Run this command:**
   ```bash
   "C:\xampp\mysql\bin\mysql.exe" -u root < IMPORT-ALL-DATABASE.sql
   ```

3. **Done!** If you see no errors, it worked.

---

### Option 3: Use the Batch Script (AUTOMATED)

1. **Double-click:** `SETUP-DATABASE.bat`
2. **Wait** for it to complete
3. **Done!**

---

## 🎯 What This Does

The `IMPORT-ALL-DATABASE.sql` file creates:

- ✅ **bihak** database
- ✅ **profiles** table (3 sample profiles)
- ✅ **admins** table (1 admin account)
- ✅ **users** table (1 demo user)
- ✅ **opportunities** table (4 sample opportunities)
- ✅ **All other tables** (sessions, logs, tags, etc.)

**Total: 15 tables with sample data**

---

## 🔐 Login After Setup

### Admin Portal
- **URL:** http://localhost/bihak-center/public/admin/login.php
- **Username:** `admin`
- **Password:** `Admin@123`

### User Account
- **URL:** http://localhost/bihak-center/public/login.php
- **Email:** `demo@bihakcenter.org`
- **Password:** `Demo@123`

---

## 📊 Load More Opportunities

After importing the database, run the scraper to load 40 opportunities:

```bash
php scrapers\run_scrapers.php
```

This adds:
- 8 scholarships
- 10 jobs
- 10 internships
- 12 grants

---

## 🌐 Test Your Website

After database import, visit:

1. **Homepage:** http://localhost/bihak-center/public/index.php
2. **Opportunities:** http://localhost/bihak-center/public/opportunities.php
3. **User Login:** http://localhost/bihak-center/public/login.php
4. **Admin Login:** http://localhost/bihak-center/public/admin/login.php

**Everything should work now!** ✨

---

## ❌ Still Having Issues?

### Error: "Database connection failed"
**Fix:** Edit `config/database.php` and check:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default
define('DB_NAME', 'bihak');
```

### Error: "Table doesn't exist"
**Fix:** The import didn't work. Try again:
1. Go to phpMyAdmin
2. Delete "bihak" database if it exists
3. Import `IMPORT-ALL-DATABASE.sql` again

### Error: "Access denied for user"
**Fix:** Your MySQL has a password set.
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Use the Import tab there (it uses your saved credentials)

### MySQL won't start
**Fix:**
1. Close XAMPP
2. Open Task Manager (Ctrl+Shift+Esc)
3. End any "mysqld.exe" processes
4. Open XAMPP again and click Start for MySQL

---

## 📱 Need More Help?

Check these files in your project folder:
- `COMPLETE-PROJECT-STATUS.md` - Full project documentation
- `SCRAPER-SETUP-GUIDE.md` - Scraper details
- `README.md` - Complete README

---

## ✅ Success Checklist

- [ ] XAMPP running (Apache + MySQL green)
- [ ] Database "bihak" exists in phpMyAdmin
- [ ] Tables visible (admins, users, opportunities, profiles, etc.)
- [ ] Can visit http://localhost/bihak-center/public/opportunities.php
- [ ] No errors when loading pages
- [ ] Can login as admin (admin/Admin@123)
- [ ] Can login as user (demo@bihakcenter.org/Demo@123)
- [ ] Opportunities page shows sample data
- [ ] (Optional) Ran scraper to load 40 opportunities

---

**You're all set! Enjoy your Bihak Center website! 🎉**
