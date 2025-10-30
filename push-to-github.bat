@echo off
REM Bihak Center - GitHub Push Script

echo ========================================
echo Push Bihak Center to GitHub
echo ========================================
echo.

REM Check if git is available
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Git is not installed
    echo Please install Git from: https://git-scm.com/download/win
    pause
    exit /b 1
)

echo This script will help you push your code to GitHub
echo.
echo First, create a new repository on GitHub:
echo 1. Go to: https://github.com/new
echo 2. Repository name: bihak-center-website
echo 3. Description: Official website for Bihak Center
echo 4. Keep it Public or Private (your choice)
echo 5. Do NOT initialize with README, .gitignore, or license
echo 6. Click "Create repository"
echo.

set /p GITHUB_USERNAME="Enter your GitHub username: "
set /p REPO_NAME="Enter repository name (default: bihak-center-website): "

if "%REPO_NAME%"=="" set REPO_NAME=bihak-center-website

echo.
echo Setting up remote...
git remote remove origin 2>nul
git remote add origin https://github.com/%GITHUB_USERNAME%/%REPO_NAME%.git

echo.
echo Current branch:
git branch

echo.
echo Renaming branch to 'main' if needed...
git branch -M main

echo.
echo Pushing to GitHub...
echo You may be prompted for your GitHub credentials or token
echo.
git push -u origin main

if %errorlevel% neq 0 (
    echo.
    echo ========================================
    echo ERROR: Push failed
    echo ========================================
    echo.
    echo Possible solutions:
    echo.
    echo 1. If authentication failed:
    echo    - Use a Personal Access Token instead of password
    echo    - Create token at: https://github.com/settings/tokens
    echo    - Use token as password when prompted
    echo.
    echo 2. If repository doesn't exist:
    echo    - Make sure you created the repository on GitHub
    echo    - Check the username and repository name
    echo.
    echo 3. Try manual push:
    echo    git remote add origin https://github.com/%GITHUB_USERNAME%/%REPO_NAME%.git
    echo    git branch -M main
    echo    git push -u origin main
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo Success!
echo ========================================
echo.
echo Your code has been pushed to GitHub!
echo View it at: https://github.com/%GITHUB_USERNAME%/%REPO_NAME%
echo.
pause
