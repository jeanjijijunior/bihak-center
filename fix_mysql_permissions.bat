@echo off
echo ========================================
echo MySQL Permission Fix Script
echo ========================================
echo.

echo Step 1: Stopping MySQL service...
cd C:\xampp
mysql_stop.bat
timeout /t 3 /nobreak >nul

echo.
echo Step 2: Starting MySQL with skip-grant-tables...
cd C:\xampp\mysql\bin
start "MySQL Safe Mode" mysqld.exe --skip-grant-tables --skip-networking
timeout /t 5 /nobreak >nul

echo.
echo Step 3: Fixing root user permissions...
mysql -u root -e "FLUSH PRIVILEGES; GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION; GRANT ALL PRIVILEGES ON *.* TO 'root'@'::1' WITH GRANT OPTION; UPDATE mysql.user SET plugin='mysql_native_password' WHERE user='root'; FLUSH PRIVILEGES;"

if %errorlevel% equ 0 (
    echo.
    echo SUCCESS! Permissions fixed.
    echo.
    echo Step 4: Restarting MySQL normally...
    taskkill /F /IM mysqld.exe >nul 2>&1
    timeout /t 2 /nobreak >nul
    cd C:\xampp
    mysql_start.bat
    timeout /t 3 /nobreak >nul

    echo.
    echo ========================================
    echo MySQL is now ready!
    echo You can now run: http://localhost/bihak-center/install_incubation.php
    echo ========================================
) else (
    echo.
    echo ERROR: Failed to fix permissions.
    echo Please stop MySQL and try again manually.
)

echo.
pause
