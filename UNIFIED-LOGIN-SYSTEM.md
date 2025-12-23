# Unified Login System

## Overview

The Bihak Center now uses a **single unified login page** for all user types:
- Regular users (young people)
- Mentors and sponsors
- Administrators

**Login URL:** `http://localhost/bihak-center/public/login.php`

---

## How It Works

### Authentication Flow

When a user submits the login form with email and password:

1. **Check Admin Account First**
   - Query `admins` table for email or username match
   - If found, use `Auth::login()` system
   - Redirect to: `admin/dashboard.php`
   - Sets session: `$_SESSION['admin_id']`

2. **Check Mentor/Sponsor Account Second**
   - Query `sponsors` table for email match (where `password_hash IS NOT NULL`)
   - Verify account is approved and active
   - Verify password with `password_verify()`
   - Redirect to: `mentorship/dashboard.php`
   - Sets session: `$_SESSION['sponsor_id']`, `sponsor_name`, `sponsor_email`, `sponsor_role`

3. **Check Regular User Account Last**
   - Use `UserAuth::login()` system
   - Query `users` table
   - Redirect to: `my-account.php` (or requested page)
   - Sets session: `$_SESSION['user_id']`

### Security Features

- ✅ **CSRF Protection** - Token validation on form submission
- ✅ **Password Hashing** - BCrypt for all passwords
- ✅ **Rate Limiting** - Prevents brute force attacks
- ✅ **Activity Logging** - Tracks all login attempts
- ✅ **Account Status Checks** - Verifies approval and active status
- ✅ **XSS Prevention** - Input sanitization
- ✅ **SQL Injection Prevention** - Prepared statements

---

## Test Accounts

### 1. Regular User
```
Email: demo@bihakcenter.org
Password: Demo@123
Redirects to: my-account.php
```

### 2. Mentor/Sponsor
```
Email: mentor@bihakcenter.org
Password: Mentor@123
Redirects to: mentorship/dashboard.php
```

### 3. Administrator
```
Username/Email: admin
Password: Admin@123
Redirects to: admin/dashboard.php
```

---

## Database Schema

### Sponsors Table (for Mentors/Sponsors)
```sql
ALTER TABLE sponsors
ADD COLUMN password_hash VARCHAR(255) NULL AFTER email;
```

Key columns:
- `id` - Primary key
- `email` - Login identifier
- `password_hash` - BCrypt hashed password
- `status` - Must be 'approved' to login
- `is_active` - Must be 1 to login
- `role_type` - 'mentor', 'sponsor', or 'donor'

---

## Session Variables

Each user type sets different session variables:

### Regular Users:
```php
$_SESSION['user_id']
$_SESSION['user_email']
$_SESSION['user_name']
```

### Mentors/Sponsors:
```php
$_SESSION['sponsor_id']
$_SESSION['sponsor_name']
$_SESSION['sponsor_email']
$_SESSION['sponsor_role']  // 'mentor', 'sponsor', or 'donor'
```

### Administrators:
```php
$_SESSION['admin_id']
$_SESSION['admin_username']
$_SESSION['admin_email']
```

---

## Protecting Pages

To protect pages for specific user types:

### For Mentors/Sponsors Only:
```php
session_start();
if (!isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php');
    exit;
}
```

### For Regular Users Only:
```php
require_once __DIR__ . '/../config/user_auth.php';
UserAuth::init();
if (!UserAuth::check()) {
    header('Location: login.php');
    exit;
}
```

### For Admins Only:
```php
require_once __DIR__ . '/../../config/auth.php';
Auth::init();
if (!Auth::check()) {
    header('Location: ../login.php');
    exit;
}
```

---

## Adding New Mentors/Sponsors

### Option 1: Via Get Involved Form
1. User fills out "Get Involved" form at `get-involved.php`
2. Admin approves in admin panel
3. Admin manually sets password in database:
```sql
UPDATE sponsors
SET password_hash = '$2y$10$...'  -- Use password_hash() in PHP
WHERE id = ?;
```

### Option 2: Direct Database Insert
```sql
INSERT INTO sponsors
(full_name, email, password_hash, role_type, status, is_active, created_at)
VALUES
('John Doe', 'john@example.com', '$2y$10$...', 'mentor', 'approved', 1, NOW());
```

To generate password hash in PHP:
```php
$password = 'YourSecurePassword123!';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo $hash;
```

---

## Activity Logging

All logins are logged to `activity_log` table:

```sql
INSERT INTO activity_log
(user_type, user_id, action, description, ip_address, created_at)
VALUES
('sponsor', ?, 'login', 'Mentor/Sponsor logged in', ?, NOW());
```

User types:
- `'user'` - Regular users
- `'admin'` - Administrators
- `'sponsor'` - Mentors/Sponsors

---

## Error Messages

### Account Not Approved
> "Your account is pending approval. Please wait for admin confirmation."

### Account Deactivated
> "Your account has been deactivated. Please contact support."

### Invalid Credentials
> "Invalid email or password."

---

## Benefits of Unified Login

1. **User Experience**
   - Single login page for everyone
   - No confusion about which login to use
   - Automatic detection of user type

2. **Maintenance**
   - Single authentication codebase
   - Easier to update security features
   - Consistent error handling

3. **Security**
   - Centralized CSRF protection
   - Single point for rate limiting
   - Unified activity logging

---

## Migration Notes

### Old System (Before Unification)
- Users logged in at: `public/login.php`
- Admins logged in at: `public/admin/login.php`
- Mentors had NO login system

### New System (After Unification)
- Everyone logs in at: `public/login.php`
- `public/admin/login.php` can be kept for backward compatibility (optional)
- `public/mentor-login.php` is obsolete (can be deleted)

---

## Testing Checklist

- [x] Regular user can login with email/password
- [x] Mentor can login with email/password
- [x] Admin can login with username/password
- [x] Each type redirects to correct dashboard
- [x] Session variables are set correctly
- [x] Activity is logged
- [x] CSRF protection works
- [x] Invalid credentials show error
- [x] Pending accounts cannot login
- [x] Deactivated accounts cannot login

---

## Troubleshooting

### Issue: Mentor cannot login
**Check:**
1. Does the sponsor have `password_hash` set?
   ```sql
   SELECT email, password_hash IS NOT NULL as has_password FROM sponsors WHERE email = ?;
   ```
2. Is `status = 'approved'`?
3. Is `is_active = 1`?

### Issue: Login redirects to wrong page
**Check session variables:**
```php
session_start();
print_r($_SESSION);
```

### Issue: "Invalid email or password" for valid credentials
**Check:**
1. Password hash is correct
2. Account exists in correct table
3. No typos in email

---

## Future Enhancements

Potential improvements:
- [ ] Add "Remember Me" for mentors/sponsors
- [ ] Add password reset for mentors/sponsors
- [ ] Add 2FA for all user types
- [ ] Add social login (Google, Facebook)
- [ ] Add biometric login support

---

**Created:** November 25, 2025
**Status:** ✅ Production Ready
**File:** `UNIFIED-LOGIN-SYSTEM.md`
