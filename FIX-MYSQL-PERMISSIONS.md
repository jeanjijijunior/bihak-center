# Fix MySQL/MariaDB Permission Issue

## Problem
Your MariaDB server has a critical permission issue preventing all connections:
```
ERROR 1130 (HY000): Host 'localhost' is not allowed to connect to this MariaDB server
```

This affects:
- ❌ phpMyAdmin
- ❌ All website pages (admin, user pages, etc.)
- ❌ Command line connections
- ❌ The incubation platform installation

## Solution: Reset Root Permissions

### Option 1: Using XAMPP Control Panel (Easiest)

1. **Stop MySQL:**
   - Open XAMPP Control Panel
   - Click "Stop" next to MySQL
   - Wait for it to show as stopped (gray/white background)

2. **Open XAMPP Shell:**
   - In XAMPP Control Panel, click the "Shell" button
   - A black command window will open

3. **Start MySQL in Safe Mode:**
   ```bash
   cd C:\xampp\mysql\bin
   mysqld.exe --skip-grant-tables --skip-networking
   ```
   - Leave this window open (MySQL is now running in safe mode)

4. **Open a SECOND Shell window:**
   - Click "Shell" button again in XAMPP Control Panel
   - In this NEW window, run:
   ```bash
   cd C:\xampp\mysql\bin
   mysql -u root
   ```

5. **Fix Permissions (in the MySQL prompt):**
   ```sql
   FLUSH PRIVILEGES;
   GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION;
   GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY '' WITH GRANT OPTION;
   GRANT ALL PRIVILEGES ON *.* TO 'root'@'::1' IDENTIFIED BY '' WITH GRANT OPTION;
   UPDATE mysql.user SET plugin='mysql_native_password' WHERE user='root';
   FLUSH PRIVILEGES;
   EXIT;
   ```

6. **Restart MySQL Normally:**
   - Close both shell windows
   - In XAMPP Control Panel, click "Stop" next to MySQL (force stop)
   - Wait a moment
   - Click "Start" next to MySQL
   - Wait for it to show green

7. **Test the Fix:**
   - Open: http://localhost/bihak-center/test_connection.php
   - You should see green checkmarks ✓
   - Or open: http://localhost/phpmyadmin/
   - Should load without errors

### Option 2: Quick Batch File (Automated)

1. **Stop MySQL in XAMPP Control Panel**

2. **Run the fix script:**
   - Navigate to: `C:\xampp\htdocs\bihak-center`
   - Double-click: `fix_mysql_permissions.bat`
   - Follow the prompts

3. **Test the fix** (see step 7 above)

### Option 3: Manual Config Edit (Advanced)

Only if above methods don't work:

1. Stop MySQL in XAMPP Control Panel

2. Edit the config file:
   - Open: `C:\xampp\mysql\bin\my.ini`
   - Add under `[mysqld]` section:
   ```ini
   skip-grant-tables
   skip-networking
   ```
   - Save the file

3. Start MySQL in XAMPP Control Panel

4. Open XAMPP Shell and run:
   ```bash
   cd C:\xampp\mysql\bin
   mysql -u root
   ```

5. Run the permission fix commands (see Option 1, step 5)

6. Remove the lines you added to `my.ini`

7. Restart MySQL in XAMPP Control Panel

## After Fixing Permissions

Once MySQL permissions are fixed, run the incubation platform installation:

**http://localhost/bihak-center/install_via_admin.php**

This will install all the incubation platform tables and data.

## Verification

After fixing, these should all work:
- ✓ http://localhost/phpmyadmin/
- ✓ http://localhost/bihak-center/test_connection.php
- ✓ http://localhost/bihak-center/public/admin/dashboard.php
- ✓ All website pages

## Need Help?

If you're still having issues:
1. Check that MySQL is actually running (green in XAMPP Control Panel)
2. Check the MySQL error log: `C:\xampp\mysql\data\mysql_error.log`
3. Try restarting your computer and XAMPP
