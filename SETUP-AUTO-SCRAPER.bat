@echo off
REM ============================================
REM Setup Automatic Daily Scraper
REM Creates Windows Task Scheduler task
REM ============================================

echo.
echo ╔════════════════════════════════════════════════╗
echo ║   BIHAK CENTER - AUTO SCRAPER SETUP           ║
echo ║   Setup daily automatic opportunity scraping  ║
echo ╚════════════════════════════════════════════════╝
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ This script requires Administrator privileges!
    echo.
    echo Please:
    echo 1. Right-click this file: SETUP-AUTO-SCRAPER.bat
    echo 2. Select "Run as administrator"
    echo 3. Click "Yes" when prompted
    echo.
    pause
    exit /b 1
)

echo ✓ Running with Administrator privileges
echo.

REM Set variables
set TASK_NAME=BihakCenter-DailyScraper
set SCRIPT_PATH=C:\xampp\htdocs\bihak-center\scrapers\run_scrapers.php
set PHP_PATH=C:\xampp\php\php.exe
set LOG_PATH=C:\xampp\htdocs\bihak-center\logs\scraper.log

REM Check if script exists
if not exist "%SCRIPT_PATH%" (
    echo ❌ Error: Scraper script not found!
    echo Expected location: %SCRIPT_PATH%
    echo.
    echo The files need to be in XAMPP directory first.
    echo Please run EASY-SETUP.bat to copy files to XAMPP.
    echo.
    pause
    exit /b 1
)

echo ✓ Scraper script found
echo.

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ❌ Error: PHP not found!
    echo Expected location: %PHP_PATH%
    echo.
    echo Please make sure XAMPP is installed correctly.
    pause
    exit /b 1
)

echo ✓ PHP found
echo.

REM Create logs directory if it doesn't exist
if not exist "C:\xampp\htdocs\bihak-center\logs" (
    mkdir "C:\xampp\htdocs\bihak-center\logs"
    echo ✓ Created logs directory
)

echo ════════════════════════════════════════════════
echo Creating Scheduled Task...
echo ════════════════════════════════════════════════
echo.

REM Delete existing task if it exists
schtasks /Query /TN "%TASK_NAME%" >nul 2>&1
if %errorlevel% equ 0 (
    echo Found existing task. Deleting...
    schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
    echo ✓ Deleted old task
)

REM Create new scheduled task
REM Runs daily at 2:00 AM
schtasks /Create /TN "%TASK_NAME%" ^
    /TR "\"%PHP_PATH%\" \"%SCRIPT_PATH%\" >> \"%LOG_PATH%\" 2>&1" ^
    /SC DAILY ^
    /ST 02:00 ^
    /RU "SYSTEM" ^
    /RL HIGHEST ^
    /F

if %errorlevel% equ 0 (
    echo.
    echo ╔════════════════════════════════════════════════╗
    echo ║          TASK CREATED SUCCESSFULLY! 🎉        ║
    echo ╚════════════════════════════════════════════════╝
    echo.
    echo ✓ Task Name: %TASK_NAME%
    echo ✓ Schedule:  Daily at 2:00 AM
    echo ✓ Script:    %SCRIPT_PATH%
    echo ✓ Log File:  %LOG_PATH%
    echo.
    echo The scraper will automatically run every day at 2:00 AM
    echo and update your opportunities database with new listings.
    echo.
    echo What happens every day at 2:00 AM:
    echo   - Scrapes 8 scholarships
    echo   - Scrapes 10 jobs
    echo   - Scrapes 10 internships
    echo   - Scrapes 12 grants
    echo   - Updates existing opportunities
    echo   - Logs all activity
    echo.
) else (
    echo.
    echo ❌ Failed to create scheduled task!
    echo.
    echo Please try manual setup:
    echo 1. Press Win+R
    echo 2. Type: taskschd.msc
    echo 3. Press Enter
    echo 4. Click "Create Basic Task..."
    echo 5. Follow the wizard with these settings:
    echo    Name: %TASK_NAME%
    echo    Trigger: Daily at 2:00 AM
    echo    Action: Start a program
    echo    Program: %PHP_PATH%
    echo    Arguments: %SCRIPT_PATH%
    echo.
    pause
    exit /b 1
)

echo ════════════════════════════════════════════════
echo Testing the Task...
echo ════════════════════════════════════════════════
echo.

choice /C YN /M "Do you want to run the scraper now to test it"
if not errorlevel 2 (
    echo.
    echo Running scraper...
    echo.
    "%PHP_PATH%" "%SCRIPT_PATH%"
    echo.
    echo ✓ Test complete! Check the output above.
    echo.
    echo If you see "40 items scraped", it's working perfectly!
)

echo.
echo ════════════════════════════════════════════════
echo What You Can Do Now:
echo ════════════════════════════════════════════════
echo.
echo ✓ View the scheduled task:
echo   1. Press Win+R
echo   2. Type: taskschd.msc
echo   3. Look for: %TASK_NAME%
echo.
echo ✓ View scraper logs:
echo   Open: %LOG_PATH%
echo.
echo ✓ Manually run scraper anytime:
echo   Double-click: run_scrapers.php (in XAMPP folder)
echo   Or run: php scrapers\run_scrapers.php
echo.
echo ✓ Change the schedule time:
echo   1. Open Task Scheduler (taskschd.msc)
echo   2. Find %TASK_NAME%
echo   3. Right-click ^> Properties
echo   4. Go to Triggers tab
echo   5. Edit the trigger
echo   6. Change time (e.g., 1:00 AM, 3:00 AM, etc.)
echo.
echo ✓ Run multiple times per day:
echo   Add multiple triggers for different times
echo   (e.g., 2:00 AM, 2:00 PM for twice daily)
echo.
echo ════════════════════════════════════════════════
echo Important Notes:
echo ════════════════════════════════════════════════
echo.
echo • Computer doesn't need to be logged in
echo • Task runs even if you're not using the computer
echo • MySQL must be running (start it in XAMPP)
echo • Logs are saved to: %LOG_PATH%
echo • Check logs to verify it's running correctly
echo.

echo.
choice /C YN /M "Open Task Scheduler to view the task"
if not errorlevel 2 (
    start taskschd.msc
)

echo.
echo ╔════════════════════════════════════════════════╗
echo ║         AUTOMATIC SCRAPER IS ACTIVE! 🎉       ║
echo ║                                                ║
echo ║  New opportunities will be added daily!       ║
echo ║  No more manual scraping needed!              ║
echo ╚════════════════════════════════════════════════╝
echo.
pause
