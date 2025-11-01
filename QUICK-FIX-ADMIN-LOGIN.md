# Quick Fix for Admin Login Error

## Problem
Error when trying to login: `Fatal error: Call to a member function bind_param() on bool`

## Solution

**OPTION 1: Run Setup Script (Easiest)**
```bash
EASY-SETUP.bat
```
This will automatically import all admin tables.

**OPTION 2: Manual Import**
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select `bihak` database
3. Click "Import" tab
4. Choose file: `includes/admin_tables.sql`
5. Click "Go"

## What Was Fixed

I updated `config/security.php` to gracefully handle missing tables. The rate limiting system now:
- Checks if `rate_limits` table exists before using it
- Falls back to allowing the action if table is missing (better UX during setup)
- Won't crash your application

## Test Admin Login

After running the setup:
1. Visit: http://localhost/bihak-center/public/admin/login.php
2. Username: `admin`
3. Password: `Admin@123`
4. Click "Sign In"

Should work perfectly now! ✅
