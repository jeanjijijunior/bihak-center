@echo off
REM Bihak Center - Complete Automated Setup
REM This script does EVERYTHING possible automatically

echo ╔════════════════════════════════════════════════╗
echo ║   BIHAK CENTER - AUTOMATIC SETUP              ║
echo ║   This will set up your website automatically ║
echo ╚════════════════════════════════════════════════╝
echo.

REM ==========================================
REM STEP 1: Check if XAMPP is installed
REM ==========================================
echo [Step 1/6] Checking for XAMPP...

if not exist "C:\xampp" (
    echo.
    echo ❌ XAMPP NOT FOUND!
    echo.
    echo Please install XAMPP first:
    echo 1. Go to: https://www.apachefriends.org/
    echo 2. Download XAMPP for Windows
    echo 3. Install to C:\xampp
    echo 4. Run this script again
    echo.
    echo Opening download page in browser...
    start https://www.apachefriends.org/download.html
    pause
    exit /b 1
)

echo ✓ XAMPP found!
echo.

REM ==========================================
REM STEP 2: Copy files to XAMPP
REM ==========================================
echo [Step 2/6] Copying files to XAMPP...

set "DEST=C:\xampp\htdocs\bihak-center"

REM Remove old installation if exists
if exist "%DEST%" (
    echo Removing old installation...
    rmdir /S /Q "%DEST%" 2>nul
)

echo Creating directory...
mkdir "%DEST%" 2>nul

echo Copying all files (this may take a minute)...
xcopy /E /I /H /Y /Q "%cd%" "%DEST%" >nul 2>&1

if %errorlevel% neq 0 (
    echo ❌ Failed to copy files
    pause
    exit /b 1
)

echo ✓ Files copied successfully!
echo   Location: %DEST%
echo.

REM ==========================================
REM STEP 3: Check if MySQL is running
REM ==========================================
echo [Step 3/6] Checking MySQL...

REM Try to connect to MySQL
mysql --version >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ MySQL command found!
) else (
    echo ⚠ MySQL command not found in PATH
    echo   You'll need to start it from XAMPP Control Panel
)
echo.

REM ==========================================
REM STEP 4: Create database and import schema
REM ==========================================
echo [Step 4/6] Setting up database...

REM Check if XAMPP MySQL is running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✓ MySQL is running!
    echo.
    echo Creating database...

    REM Try to create database
    "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS bihak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul

    if %errorlevel% equ 0 (
        echo ✓ Database created!
        echo.
        echo Importing schema...
        "C:\xampp\mysql\bin\mysql.exe" -u root bihak < "%DEST%\includes\profiles_schema.sql" 2>nul

        if %errorlevel% equ 0 (
            echo ✓ Schema imported successfully!
            echo ✓ 8 fictive profiles loaded!
        ) else (
            echo ⚠ Schema import may have failed
            echo   You can import manually from phpMyAdmin
        )
    ) else (
        echo ⚠ Database creation failed (may need password)
    )
) else (
    echo ⚠ MySQL not running yet
    echo.
    echo Please:
    echo 1. Open XAMPP Control Panel
    echo 2. Click "Start" for MySQL
    echo 3. Then run database setup manually or re-run this script
)
echo.

REM ==========================================
REM STEP 5: Create config file
REM ==========================================
echo [Step 5/6] Creating configuration...

if not exist "%DEST%\config\config.local.php" (
    copy /Y "%DEST%\config\config.example.php" "%DEST%\config\config.local.php" >nul 2>&1
    echo ✓ Configuration file created
) else (
    echo ✓ Configuration file already exists
)
echo.

REM ==========================================
REM STEP 6: Check if Apache is running
REM ==========================================
echo [Step 6/6] Checking Apache...

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✓ Apache is running!
) else (
    echo ⚠ Apache not running yet
    echo   Starting XAMPP Control Panel...
    start "" "C:\xampp\xampp-control.exe"
    echo.
    echo Please click "Start" for Apache and MySQL
)
echo.

REM ==========================================
REM SETUP COMPLETE!
REM ==========================================
echo ╔════════════════════════════════════════════════╗
echo ║          SETUP COMPLETE! 🎉                   ║
echo ╚════════════════════════════════════════════════╝
echo.
echo Your website is at:
echo   📁 %DEST%
echo.
echo 🌐 Access your website:
echo   http://localhost/bihak-center/public/index_new.php
echo.
echo 🗄️ Database management:
echo   http://localhost/phpmyadmin
echo.
echo ✨ What's included:
echo   ✓ User registration system
echo   ✓ Dynamic profiles homepage
echo   ✓ 8 pre-loaded demo profiles
echo   ✓ Mobile-responsive design
echo   ✓ Profile detail pages
echo   ✓ Load More functionality
echo.
echo 📋 Next steps:
echo   1. Make sure Apache and MySQL are running (XAMPP Control)
echo   2. Visit: http://localhost/phpmyadmin
echo      - Check if 'bihak' database exists
echo      - If not, import: %DEST%\includes\profiles_schema.sql
echo   3. Visit your website!
echo      http://localhost/bihak-center/public/index_new.php
echo.
echo 💡 Test on phone:
echo   1. Get your PC's IP: ipconfig
echo   2. On phone (same WiFi): http://YOUR_IP/bihak-center/public/index_new.php
echo.

choice /C YN /M "Open your website in browser now"
if not errorlevel 2 (
    start http://localhost/bihak-center/public/index_new.php
)

echo.
echo ════════════════════════════════════════════════
echo Need help? Check these files:
echo   - COMPLETE-GUIDE.md
echo   - XAMPP-SETUP.md
echo   - DONT-WORRY-GUIDE.md
echo ════════════════════════════════════════════════
echo.
pause
