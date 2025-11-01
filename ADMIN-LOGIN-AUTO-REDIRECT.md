# Admin Login Auto-Redirect Feature

**Date:** October 31, 2025
**Feature:** Automatic Admin Detection and Dashboard Redirect

---

## Overview

The login system now intelligently detects whether a user is an admin or a regular user and redirects them to the appropriate dashboard automatically.

---

## How It Works

### Smart Detection Flow

When someone logs in through the main website login page ([/public/login.php](public/login.php)):

1. **Email/Username Check**: System checks if the email/username exists in the `admins` table
2. **Auto-Detection**:
   - If admin account found → Uses admin authentication → Redirects to admin dashboard
   - If user account found → Uses user authentication → Redirects to user account page
3. **Seamless Experience**: Users don't need to know which login page to use

### Technical Implementation

```php
// Check if email/username belongs to admin
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM admins WHERE email = ? OR username = ?");
$stmt->bind_param('ss', $email, $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$is_admin = $row['count'] > 0;

if ($is_admin) {
    // Admin login flow → admin/dashboard.php
    $adminResult = Auth::login($email, $password, $remember);
} else {
    // User login flow → my-account.php
    $userResult = UserAuth::login($email, $password, $remember);
}
```

---

## User Experience

### For Administrators

**Option 1: Use Main Login Page**
1. Go to website homepage
2. Click "Login" button
3. Enter admin email/username and password
4. System automatically detects admin account
5. Redirected to `/public/admin/dashboard.php`

**Option 2: Use Admin Portal Link**
1. Go to login page
2. See "Are you an administrator?" section at bottom
3. Click "Admin Portal Login" link (golden color)
4. Opens direct admin login page
5. Login and access dashboard

**Option 3: Direct Admin Login URL**
- Navigate directly to `/public/admin/login.php`
- Traditional admin login page

### For Regular Users

1. Go to website homepage
2. Click "Login" button
3. Enter email and password
4. System detects user account
5. Redirected to `/public/my-account.php`

---

## Security Features

### ✅ Account Type Isolation
- Admin accounts: Stored in `admins` table
- User accounts: Stored in `users` table
- No overlap or conflicts possible

### ✅ Secure Authentication
- CSRF token protection on all forms
- Rate limiting prevents brute force attacks
- Account lockout after failed attempts
- Password hashing with bcrypt (cost 12)

### ✅ Session Management
- Separate session handling for admins and users
- HttpOnly and Secure cookie flags
- SameSite cookie protection
- Session expiration after inactivity

### ✅ Redirect Validation
- Only relative URLs allowed (no external redirects)
- XSS sanitization on redirect parameters
- Default safe redirects if validation fails

---

## Files Modified

### [public/login.php](public/login.php)
**Changes:**
- Added admin detection query
- Imported `Auth` class and `database.php`
- Added conditional login flow (admin vs user)
- Added "Admin Portal Login" link in UI
- Added redirect check for already-logged-in admins

**New Dependencies:**
```php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
```

**UI Enhancement:**
```html
<!-- Admin Login Link -->
<div class="signup-link">
    <p>Are you an administrator?</p>
    <a href="admin/login.php">
        <svg>...</svg>
        Admin Portal Login
    </a>
</div>
```

---

## Testing the Feature

### Test Case 1: Admin Login via Main Page
1. Go to `http://localhost/bihak-center/public/login.php`
2. Enter: `admin@bihakcenter.org` or `admin`
3. Enter: `Admin@123` (or current admin password)
4. Click "Sign In"
5. **Expected:** Redirect to `admin/dashboard.php`

### Test Case 2: User Login via Main Page
1. Go to `http://localhost/bihak-center/public/login.php`
2. Enter: `testuser@example.com`
3. Enter: User password
4. Click "Sign In"
5. **Expected:** Redirect to `my-account.php`

### Test Case 3: Admin Portal Link
1. Go to `http://localhost/bihak-center/public/login.php`
2. Scroll to bottom
3. Click "Admin Portal Login" link
4. **Expected:** Redirect to `admin/login.php`

### Test Case 4: Wrong Credentials
1. Enter non-existent email
2. Enter wrong password
3. **Expected:** Error message displayed, no redirect

### Test Case 5: Already Logged In
1. Login as admin
2. Try to visit `login.php`
3. **Expected:** Auto-redirect to dashboard
4. Repeat for user account
5. **Expected:** Auto-redirect to my-account

---

## Admin Account Details

### Default Admin Account
- **Username:** `admin`
- **Email:** `admin@bihakcenter.org`
- **Password:** `Admin@123` (change in production!)
- **Role:** `super_admin`
- **Access:** Full system access

### Login Methods (All work the same)
- Email: `admin@bihakcenter.org`
- Username: `admin`
- Either can be used in the login field

---

## Database Tables

### admins Table
```sql
SELECT id, username, email, password_hash, full_name, role, is_active
FROM admins
WHERE username = ? OR email = ?
```

**Fields Used:**
- `username` - Admin username
- `email` - Admin email address
- `password_hash` - Bcrypt hashed password
- `is_active` - Account active status
- `role` - Permission role (super_admin, admin, etc.)

### users Table
```sql
SELECT id, email, password_hash, full_name, is_active
FROM users
WHERE email = ?
```

**Fields Used:**
- `email` - User email address
- `password_hash` - Bcrypt hashed password
- `is_active` - Account active status

---

## Benefits

### 1. **User Convenience**
- Single login page for everyone
- No confusion about which login to use
- Automatic routing to correct dashboard

### 2. **Simplified Access**
- Admins can use any login page
- Direct link to admin portal still available
- Bookmarkable admin login URL

### 3. **Maintained Security**
- No security compromise from unified login
- All authentication methods remain secure
- Session isolation between admin and user accounts

### 4. **Better UX**
- Less clicks to reach dashboard
- Visual indicator for admin portal
- Clear separation of user types

---

## Troubleshooting

### Issue: Admin Redirects to User Account
**Cause:** Admin account not in `admins` table or using user email
**Solution:** Verify admin account exists in admins table:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root bihak -e "SELECT username, email FROM admins WHERE email = 'admin@bihakcenter.org';"
```

### Issue: User Redirects to Admin Dashboard
**Cause:** User email exists in both tables (shouldn't happen)
**Solution:** Ensure unique emails across tables:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root bihak -e "SELECT email FROM admins UNION SELECT email FROM users GROUP BY email HAVING COUNT(*) > 1;"
```

### Issue: Login Fails for Admin
**Cause:** Incorrect password or account locked
**Solution:** Check account status:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root bihak -e "SELECT username, is_active, failed_login_attempts, locked_until FROM admins WHERE username = 'admin';"
```

### Issue: "Invalid request" Error
**Cause:** CSRF token validation failure
**Solution:** Clear browser cache/cookies and try again

---

## Future Enhancements

### Possible Additions
- [ ] Remember last login type preference
- [ ] Visual indicator during login (detecting account type)
- [ ] Multi-factor authentication for admins
- [ ] Login activity dashboard
- [ ] IP whitelist for admin logins
- [ ] Email notification on admin login

---

## Related Files

### Authentication System
- [config/auth.php](config/auth.php) - Admin authentication class
- [config/user_auth.php](config/user_auth.php) - User authentication class
- [config/security.php](config/security.php) - Security functions
- [config/database.php](config/database.php) - Database connection

### Login Pages
- [public/login.php](public/login.php) - Main login page (smart detection)
- [public/admin/login.php](public/admin/login.php) - Admin-only login page
- [public/signup.php](public/signup.php) - User registration

### Dashboard Pages
- [public/admin/dashboard.php](public/admin/dashboard.php) - Admin dashboard
- [public/my-account.php](public/my-account.php) - User account page

---

## Summary

The admin login system now provides:
- ✅ Automatic admin detection from main login page
- ✅ Smart routing to admin dashboard or user account
- ✅ Direct admin portal link for convenience
- ✅ Full security maintained across both flows
- ✅ Seamless user experience for all account types

**No configuration needed** - The system works automatically!

---

**Created:** October 31, 2025
**Version:** 1.0
**Status:** Production Ready
