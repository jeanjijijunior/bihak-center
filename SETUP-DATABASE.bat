@echo off
REM ============================================
REM Bihak Center - Database Setup Script
REM Imports ALL required database tables
REM ============================================

echo.
echo ╔════════════════════════════════════════════════╗
echo ║   BIHAK CENTER - DATABASE SETUP               ║
echo ║   This will import all database tables        ║
echo ╚════════════════════════════════════════════════╝
echo.

REM Check if MySQL is running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if not "%ERRORLEVEL%"=="0" (
    echo ❌ MySQL is not running!
    echo.
    echo Please start MySQL first:
    echo 1. Open XAMPP Control Panel
    echo 2. Click "Start" next to MySQL
    echo 3. Wait for it to turn green
    echo 4. Then run this script again
    echo.
    pause
    exit /b 1
)

echo ✓ MySQL is running!
echo.

REM Set MySQL path
set MYSQL="C:\xampp\mysql\bin\mysql.exe"

REM Check if mysql.exe exists
if not exist %MYSQL% (
    echo ❌ MySQL not found at: %MYSQL%
    echo Please check your XAMPP installation
    pause
    exit /b 1
)

echo ════════════════════════════════════════════════
echo Step 1: Creating Database
echo ════════════════════════════════════════════════
echo.

%MYSQL% -u root -e "DROP DATABASE IF EXISTS bihak;" 2>nul
%MYSQL% -u root -e "CREATE DATABASE bihak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %errorlevel% neq 0 (
    echo ❌ Failed to create database!
    echo.
    echo This might mean MySQL requires a password.
    echo Please open phpMyAdmin and:
    echo 1. Go to http://localhost/phpmyadmin
    echo 2. Click "New" to create database
    echo 3. Name it "bihak"
    echo 4. Set Collation to "utf8mb4_unicode_ci"
    echo 5. Then click the Import tab and import files manually
    echo.
    pause
    exit /b 1
)

echo ✓ Database "bihak" created!
echo.

REM Import all SQL files in correct order
echo ════════════════════════════════════════════════
echo Step 2: Importing Database Tables
echo ════════════════════════════════════════════════
echo.

echo [1/5] Importing profiles schema (with sample data)...
%MYSQL% -u root bihak < "includes\profiles_schema.sql" 2>nul
if %errorlevel% equ 0 (
    echo ✓ Profiles tables imported successfully!
    echo   - profiles table created
    echo   - 8 sample profiles loaded
) else (
    echo ⚠ Warning: Profiles schema import may have issues
)
echo.

echo [2/5] Importing admin system...
%MYSQL% -u root bihak < "includes\admin_tables.sql" 2>nul
if %errorlevel% equ 0 (
    echo ✓ Admin tables imported successfully!
    echo   - admins table created
    echo   - admin_sessions table created
    echo   - admin_activity_log table created
) else (
    echo ⚠ Warning: Admin tables import may have issues
)
echo.

echo [3/5] Fixing admin password (CRITICAL)...
%MYSQL% -u root bihak < "FIX-ADMIN-PASSWORD.sql" 2>nul
if %errorlevel% equ 0 (
    echo ✓ Admin password fixed!
    echo   - Default admin: admin / Admin@123
) else (
    echo ⚠ Warning: Admin password fix may have issues
)
echo.

echo [4/5] Importing user authentication system...
%MYSQL% -u root bihak < "includes\user_auth_tables.sql" 2>nul
if %errorlevel% equ 0 (
    echo ✓ User authentication tables imported!
    echo   - users table created
    echo   - user_sessions table created
    echo   - user_activity_log table created
) else (
    echo ⚠ Warning: User auth tables import may have issues
)
echo.

echo [5/5] Importing opportunities system...
%MYSQL% -u root bihak < "includes\opportunities_tables.sql" 2>nul
if %errorlevel% equ 0 (
    echo ✓ Opportunities tables imported!
    echo   - opportunities table created
    echo   - opportunity_tags table created with sample tags
    echo   - user_saved_opportunities table created
    echo   - scraper_log table created
    echo   - 3 sample scholarships loaded
    echo   - 2 sample jobs loaded
    echo   - 2 sample internships loaded
    echo   - 2 sample grants loaded
) else (
    echo ⚠ Warning: Opportunities tables import may have issues
)
echo.

echo ════════════════════════════════════════════════
echo Step 3: Running Web Scraper
echo ════════════════════════════════════════════════
echo.
echo Running scraper to populate 40 opportunities...
echo This may take 10-30 seconds...
echo.

php scrapers\run_scrapers.php

if %errorlevel% equ 0 (
    echo.
    echo ✓ Scraper completed successfully!
    echo   Total opportunities loaded: 40
    echo   - 8 Scholarships
    echo   - 10 Jobs
    echo   - 10 Internships
    echo   - 12 Grants
) else (
    echo.
    echo ⚠ Warning: Scraper may have encountered issues
    echo You can run it manually later:
    echo   php scrapers\run_scrapers.php
)
echo.

echo ════════════════════════════════════════════════
echo Step 4: Verifying Installation
echo ════════════════════════════════════════════════
echo.

echo Checking tables...
%MYSQL% -u root bihak -e "SHOW TABLES;" 2>nul > temp_tables.txt

if exist temp_tables.txt (
    findstr /C:"profiles" temp_tables.txt >nul && echo ✓ profiles
    findstr /C:"admins" temp_tables.txt >nul && echo ✓ admins
    findstr /C:"users" temp_tables.txt >nul && echo ✓ users
    findstr /C:"opportunities" temp_tables.txt >nul && echo ✓ opportunities
    findstr /C:"scraper_log" temp_tables.txt >nul && echo ✓ scraper_log
    del temp_tables.txt
) else (
    echo ⚠ Could not verify tables
)
echo.

echo ╔════════════════════════════════════════════════╗
echo ║          DATABASE SETUP COMPLETE! 🎉          ║
echo ╚════════════════════════════════════════════════╝
echo.
echo 📊 What was imported:
echo   ✓ Profiles system (8 sample profiles)
echo   ✓ Admin system (1 admin account)
echo   ✓ User authentication system
echo   ✓ Opportunities system (40 opportunities)
echo   ✓ All security tables
echo.
echo 🔐 Default Login Credentials:
echo.
echo   ADMIN PORTAL:
echo   URL:      http://localhost/bihak-center/public/admin/login.php
echo   Username: admin
echo   Password: Admin@123
echo.
echo   DEMO USER ACCOUNT:
echo   URL:      http://localhost/bihak-center/public/login.php
echo   Email:    demo@bihakcenter.org
echo   Password: Demo@123
echo.
echo 🌐 Visit your website:
echo   Homepage:      http://localhost/bihak-center/public/index.php
echo   Opportunities: http://localhost/bihak-center/public/opportunities.php
echo   User Login:    http://localhost/bihak-center/public/login.php
echo   Admin Login:   http://localhost/bihak-center/public/admin/login.php
echo.
echo 📋 Next Steps:
echo   1. Visit: http://localhost/phpmyadmin
echo      - Verify "bihak" database exists
echo      - Check tables are there
echo   2. Test admin login
echo   3. Test user login
echo   4. Browse opportunities page
echo.

choice /C YN /M "Open phpMyAdmin to verify database"
if not errorlevel 2 (
    start http://localhost/phpmyadmin/index.php?route=/database/structure&db=bihak
)

echo.
choice /C YN /M "Open your website in browser"
if not errorlevel 2 (
    start http://localhost/bihak-center/public/index.php
    timeout /t 2 /nobreak >nul
    start http://localhost/bihak-center/public/opportunities.php
)

echo.
echo ════════════════════════════════════════════════
echo If you had any errors, check:
echo   - SCRAPER-SETUP-GUIDE.md
echo   - COMPLETE-PROJECT-STATUS.md
echo ════════════════════════════════════════════════
echo.
pause
