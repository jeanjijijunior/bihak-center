@echo off
REM Bihak Center - Development Server Starter

echo ========================================
echo Bihak Center - Starting Development Server
echo ========================================
echo.

REM Check if PHP is available
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please run setup-windows.bat first
    pause
    exit /b 1
)

REM Start the PHP development server
echo Starting PHP development server...
echo Server will run on: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
echo ========================================
echo.

cd public
php -S localhost:8000

pause
