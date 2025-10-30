@echo off
REM Bihak Center - Windows Setup Script
REM This script will help you set up the website on Windows

echo ========================================
echo Bihak Center Website Setup
echo ========================================
echo.

REM Check for PHP
echo [1/4] Checking for PHP...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP from: https://windows.php.net/download/
    echo.
    echo After installation, add PHP to your system PATH
    pause
    exit /b 1
) else (
    echo PHP found!
    php --version
)
echo.

REM Check for MySQL
echo [2/4] Checking for MySQL...
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo WARNING: MySQL command not found
    echo Please ensure MySQL/MariaDB is installed
    echo Download from: https://dev.mysql.com/downloads/installer/
    echo.
    echo If MySQL is installed, add it to your PATH:
    echo Typically: C:\Program Files\MySQL\MySQL Server 8.0\bin
    echo.
    pause
) else (
    echo MySQL found!
    mysql --version
)
echo.

REM Create database
echo [3/4] Setting up database...
echo.
echo Please enter your MySQL root password when prompted:
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS bihak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo ERROR: Failed to create database
    echo Please create it manually:
    echo   mysql -u root -p
    echo   CREATE DATABASE bihak;
    pause
) else (
    echo Database 'bihak' created successfully!
)
echo.

REM Import schema
echo Importing database schema...
mysql -u root -p bihak < includes\database.sql
if %errorlevel% neq 0 (
    echo WARNING: Failed to import schema
    echo You may need to import manually
) else (
    echo Database schema imported successfully!
)
echo.

REM Create config file
echo [4/4] Creating configuration file...
if not exist config\config.local.php (
    copy config\config.example.php config\config.local.php
    echo Configuration file created: config\config.local.php
    echo.
    echo IMPORTANT: Edit config\config.local.php and update your database credentials!
    notepad config\config.local.php
) else (
    echo Configuration file already exists
)
echo.

echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Edit config\config.local.php with your database password
echo 2. Run: start-server.bat
echo 3. Visit: http://localhost:8000
echo.
pause
