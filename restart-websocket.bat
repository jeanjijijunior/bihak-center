@echo off
echo ================================================
echo   Restarting Bihak Center WebSocket Server
echo ================================================
echo.

echo Step 1: Stopping existing Node.js processes...
taskkill /F /IM node.exe 2>nul
if %errorlevel% == 0 (
    echo ✓ Node.js processes stopped
) else (
    echo ℹ No running Node.js processes found
)
echo.

echo Step 2: Waiting 2 seconds...
timeout /t 2 /nobreak >nul
echo.

echo Step 3: Starting WebSocket server...
cd /d "%~dp0websocket"
echo Current directory: %CD%
echo.

start "Bihak WebSocket Server" cmd /k "node server.js"

echo.
echo ================================================
echo   WebSocket server is starting...
echo   Check the new window for server status
echo ================================================
echo.
pause
