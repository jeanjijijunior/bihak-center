@echo off
REM Bihak Center - Safe Move to XAMPP Script
REM This script COPIES (not moves) your project to XAMPP

echo ========================================
echo Bihak Center - Move to XAMPP
echo ========================================
echo.

REM Check if XAMPP exists
if not exist "C:\xampp" (
    echo ERROR: XAMPP not found at C:\xampp
    echo Please install XAMPP first!
    echo Download from: https://www.apachefriends.org/
    pause
    exit /b 1
)

REM Check if htdocs exists
if not exist "C:\xampp\htdocs" (
    echo ERROR: C:\xampp\htdocs not found
    echo XAMPP may not be installed correctly
    pause
    exit /b 1
)

echo XAMPP found! ✓
echo.

REM Get current directory
set "SOURCE=%cd%"
set "DEST=C:\xampp\htdocs\bihak-center"

echo Source: %SOURCE%
echo Destination: %DEST%
echo.

REM Check if destination already exists
if exist "%DEST%" (
    echo WARNING: Destination folder already exists!
    echo.
    choice /C YN /M "Do you want to overwrite it"
    if errorlevel 2 (
        echo Operation cancelled.
        pause
        exit /b 0
    )
    echo Removing old folder...
    rmdir /S /Q "%DEST%"
)

echo.
echo Copying files to XAMPP...
echo This may take a minute...
echo.

REM Create destination folder
mkdir "%DEST%"

REM Copy everything EXCEPT certain folders
xcopy /E /I /H /Y "%SOURCE%" "%DEST%" /EXCLUDE:%TEMP%\xcopy_exclude.txt

REM Create exclude list for next time
(
echo .git\objects\
echo node_modules\
echo .vscode\
) > "%TEMP%\xcopy_exclude.txt"

if %errorlevel% neq 0 (
    echo ERROR: Failed to copy files
    pause
    exit /b 1
)

echo.
echo ========================================
echo SUCCESS! Files copied to XAMPP
echo ========================================
echo.
echo Your project is now at:
echo %DEST%
echo.
echo IMPORTANT:
echo - Your ORIGINAL folder is still in Downloads (safe!)
echo - You can delete the Downloads copy after testing
echo.
echo Next steps:
echo 1. Start XAMPP Control Panel
echo 2. Start Apache and MySQL
echo 3. Open: http://localhost/phpmyadmin
echo 4. Create database: bihak
echo 5. Import: includes/profiles_schema.sql
echo 6. Visit: http://localhost/bihak-center/public/index_new.php
echo.

REM Offer to open XAMPP Control Panel
choice /C YN /M "Do you want to open XAMPP Control Panel now"
if not errorlevel 2 (
    start "" "C:\xampp\xampp-control.exe"
)

echo.
pause
