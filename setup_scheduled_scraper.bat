@echo off
REM Setup Windows Task Scheduler for Automatic Opportunity Scraping
REM This creates a scheduled task that runs the scraper every 6 hours

echo ========================================
echo Bihak Center - Scheduled Scraper Setup
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script must be run as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

echo Setting up scheduled task...
echo.

REM Delete existing task if it exists
schtasks /delete /tn "BihakCenter_OpportunityScraper" /f >nul 2>&1

REM Create new scheduled task
REM Runs every 6 hours, starting at 2 AM
schtasks /create ^
    /tn "BihakCenter_OpportunityScraper" ^
    /tr "C:\xampp\php\php.exe C:\xampp\htdocs\bihak-center\includes\run_scheduled_scraper.php" ^
    /sc daily ^
    /st 02:00 ^
    /ri 360 ^
    /du 24:00 ^
    /f

if %errorLevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! Scheduled task created.
    echo ========================================
    echo.
    echo Task Name: BihakCenter_OpportunityScraper
    echo Schedule: Every 6 hours, starting at 2:00 AM
    echo Script: run_scheduled_scraper.php
    echo.
    echo The scraper will automatically run:
    echo   - 2:00 AM
    echo   - 8:00 AM
    echo   - 2:00 PM
    echo   - 8:00 PM
    echo.
    echo To view the task:
    echo   schtasks /query /tn "BihakCenter_OpportunityScraper" /fo LIST /v
    echo.
    echo To run manually:
    echo   schtasks /run /tn "BihakCenter_OpportunityScraper"
    echo.
    echo To delete the task:
    echo   schtasks /delete /tn "BihakCenter_OpportunityScraper" /f
    echo.
) else (
    echo.
    echo ERROR: Failed to create scheduled task!
    echo Please check if you have administrator privileges.
    echo.
)

pause
