# 🔐 ADMIN SYSTEM - COMPLETE GUIDE

## Overview

The Bihak Center admin system is a **professional, enterprise-grade admin dashboard** with secure authentication, profile management, and activity tracking.

---

## ✨ Features

### Authentication & Security
- ✅ **Secure Login System** - Bcrypt password hashing (cost 12)
- ✅ **Session Management** - Secure, expiring sessions with database tracking
- ✅ **Remember Me** - 30-day persistent login
- ✅ **Rate Limiting** - Prevents brute force attacks (5 attempts per 15 minutes)
- ✅ **Account Lockout** - Automatic lockout after 5 failed attempts
- ✅ **CSRF Protection** - All forms protected
- ✅ **Security Headers** - CSP, HSTS, X-Frame-Options
- ✅ **Activity Logging** - All admin actions tracked

### Dashboard Features
- ✅ **Statistics Overview** - Pending, approved, rejected profiles
- ✅ **Recent Profiles** - Latest submissions at a glance
- ✅ **Quick Actions** - One-click access to common tasks
- ✅ **Recent Activity** - Track all admin actions
- ✅ **Responsive Design** - Works on desktop, tablet, and mobile

### Profile Management
- ✅ **Profile Review** - Full profile details with all information
- ✅ **Approve/Reject** - One-click approval or rejection with reason
- ✅ **Publish/Unpublish** - Control visibility on website
- ✅ **Search & Filter** - Find profiles by status, name, email
- ✅ **Pagination** - Easy navigation through all profiles
- ✅ **Bulk Operations** - Future feature

---

## 🚀 Quick Start

### 1. Run Setup Script

```bash
EASY-SETUP.bat
```

This automatically:
- Creates admin database tables
- Sets up default admin account
- Imports all necessary SQL

### 2. Access Admin Panel

Open: **http://localhost/bihak-center/public/admin/login.php**

### 3. Default Login Credentials

```
Username: admin
Password: Admin@123
```

**⚠ IMPORTANT: Change this password immediately after first login!**

---

## 📋 Admin Pages

### Login Page
- **URL**: `/public/admin/login.php`
- **Features**:
  - Secure authentication
  - Remember me checkbox
  - Password visibility toggle
  - Rate limiting
  - Account lockout protection

### Dashboard
- **URL**: `/public/admin/dashboard.php`
- **Features**:
  - Statistics cards (pending, approved, new submissions)
  - Recent profiles list
  - Quick action buttons
  - Recent activity log
  - Real-time notifications

### Profiles Management
- **URL**: `/public/admin/profiles.php`
- **Features**:
  - View all profiles in table format
  - Filter by status (all, pending, approved, rejected)
  - Search by name, email, or title
  - Pagination (20 profiles per page)
  - Status badges
  - Quick review button

### Profile Review
- **URL**: `/public/admin/profile-review.php?id=X`
- **Features**:
  - Full profile details view
  - Approve/Reject buttons
  - Rejection reason textarea (required for reject)
  - Publish/Unpublish toggle
  - Profile metadata (views, submission date)
  - Preview on website link

### Logout
- **URL**: `/public/admin/logout.php`
- **Action**: Securely logs out admin and clears session

---

## 🗄️ Database Structure

### Admin Tables

#### `admin_sessions`
```sql
- id (Primary Key)
- admin_id (Foreign Key to admins)
- session_token (Unique, 128 chars)
- ip_address
- user_agent
- remember_token (Optional, 128 chars)
- last_activity (Auto-updates)
- expires_at (1 hour default)
- created_at
```

#### `admin_activity_log`
```sql
- id (Primary Key)
- admin_id (Foreign Key to admins)
- action (e.g., 'profile_approved')
- entity_type (e.g., 'profile')
- entity_id (e.g., profile ID)
- details (JSON or text)
- ip_address
- user_agent
- created_at
```

#### `admins` (Enhanced)
```sql
- id (Primary Key)
- username (Unique)
- email (Unique)
- password (Bcrypt hashed)
- full_name
- role (super_admin, admin, moderator)
- is_active (Boolean)
- last_login
- last_login_ip
- failed_login_attempts
- locked_until
- password_changed_at
- two_factor_secret (Future)
- two_factor_enabled (Future)
- created_at
```

#### `rate_limits`
```sql
- id (Primary Key)
- identifier (IP address or user ID)
- action (e.g., 'admin_login')
- attempts (Counter)
- window_start (Timestamp)
```

### Database Views

#### `dashboard_stats`
```sql
- pending_profiles
- approved_profiles
- rejected_profiles
- new_profiles_week
- new_profiles_month
- active_admins
- actions_today
```

#### `recent_admin_activity`
```sql
- All activity log entries
- Joined with admin names
- Limited to 50 most recent
```

---

## 🔒 Security Features

### Password Policy
- **Minimum Length**: 6 characters (configurable)
- **Hashing**: Bcrypt with cost 12
- **Storage**: Never stored in plain text
- **Reset**: Password reset feature (coming soon)

### Session Security
- **Lifetime**: 1 hour (sliding expiration)
- **Storage**: Database-backed sessions
- **Tokens**: Cryptographically random 128-char tokens
- **Regeneration**: On login/logout
- **Validation**: Every request validates session

### Rate Limiting
- **Login Attempts**: 5 per 15 minutes per IP
- **Account Lockout**: 30 minutes after 5 failed attempts
- **Warning**: Shows remaining attempts after 3 failures
- **Bypass**: Never (fail-secure)

### CSRF Protection
- **All Forms**: Protected with CSRF tokens
- **Token Generation**: Cryptographically random
- **Token Validation**: Required for all POST requests
- **Token Refresh**: On each page load

### Activity Logging
All actions are logged:
- Login/Logout
- Profile Approve/Reject
- Profile Publish/Unpublish
- Profile Edit
- User Management
- Settings Changes

Log includes:
- Admin ID and name
- Action type
- Entity affected (profile ID, etc.)
- IP address
- User agent
- Timestamp
- Details/reason

---

## 👤 User Roles

### Super Admin
- Full access to everything
- Can create/delete other admins
- Can change all settings
- Cannot be locked or deleted

### Admin (Default)
- Approve/reject profiles
- Publish/unpublish profiles
- View activity logs
- Manage content

### Moderator (Future)
- Review profiles
- Limited approval permissions
- Cannot delete

---

## 📊 Admin Workflow

### Typical Profile Review Workflow

1. **Login** → Admin logs in to dashboard
2. **Notification** → Dashboard shows pending profiles count
3. **Navigate** → Click "Review Pending" or go to Profiles page
4. **Filter** → Click "Pending" tab to see only pending profiles
5. **Select** → Click "Review" button on a profile
6. **Review** → Read full profile details
7. **Decision**:
   - **Approve**: Click "Approve Profile" button
   - **Reject**: Enter reason, click "Reject Profile" button
8. **Result**: Profile status updated, user notified (email coming soon)
9. **Publish**: If approved, toggle "Publish to Website"
10. **Verify**: Check website to see published profile

---

## 🛠️ Configuration

### Authentication Settings
Edit `config/auth.php`:

```php
private static $session_lifetime = 3600; // 1 hour (in seconds)
private static $remember_lifetime = 2592000; // 30 days (in seconds)
```

### Rate Limiting Settings
Edit `config/security.php`:

```php
Security::checkRateLimit(
    $identifier,
    'admin_login',
    5,   // Max attempts
    900  // Time window (15 minutes)
);
```

### Password Hashing
Edit `config/security.php`:

```php
public static function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

---

## 🎨 Customization

### Branding
1. **Logo**: Replace `/assets/images/logob.png`
2. **Colors**: Edit CSS variables in `/assets/css/admin-dashboard.css`:
   ```css
   :root {
       --primary-color: #667eea;
       --secondary-color: #764ba2;
   }
   ```

### Dashboard Widgets
Add custom widgets to `dashboard.php`:
```php
// Add after existing stats cards
<div class="stat-card">
    <div class="stat-icon"><!-- Icon --></div>
    <div class="stat-content">
        <h3>Custom Stat</h3>
        <div class="stat-value">123</div>
    </div>
</div>
```

---

## 🧪 Testing

### Manual Testing Checklist

#### Authentication
- [ ] Login with correct credentials
- [ ] Login with wrong password (should fail)
- [ ] Login with non-existent user (should fail)
- [ ] Try 6 failed logins (should lock account)
- [ ] Check "Remember Me" and verify 30-day persistence
- [ ] Logout and verify session is destroyed

#### Profile Management
- [ ] View pending profiles
- [ ] Approve a profile
- [ ] Reject a profile with reason
- [ ] Publish an approved profile
- [ ] Unpublish a profile
- [ ] Search for profiles
- [ ] Filter by status

#### Security
- [ ] Try CSRF attack (should fail)
- [ ] Check rate limiting (5 attempts)
- [ ] Verify session timeout after 1 hour
- [ ] Check activity log for all actions

---

## 🐛 Troubleshooting

### Can't Login

**Problem**: "Invalid username or password"
- **Solution**: Check username/password spelling
- **Default**: username=`admin`, password=`Admin@123`

**Problem**: "Account locked"
- **Solution**: Wait 30 minutes or reset in database:
  ```sql
  UPDATE admins SET failed_login_attempts = 0, locked_until = NULL WHERE username = 'admin';
  ```

**Problem**: "Too many login attempts"
- **Solution**: Wait 15 minutes or clear rate limits:
  ```sql
  DELETE FROM rate_limits WHERE action = 'admin_login';
  ```

### Session Expires Too Quickly

**Problem**: Logged out after a few minutes
- **Solution**: Increase session lifetime in `config/auth.php`
- **Check**: PHP session settings in `php.ini`

### Dashboard Not Loading

**Problem**: White screen or errors
- **Solution**: Check database connection in `config/database.php`
- **Check**: PHP error log at `C:\xampp\php\logs\php_error_log`
- **Check**: Apache error log at `C:\xampp\apache\logs\error.log`

### Admin Tables Missing

**Problem**: Database errors on admin pages
- **Solution**: Import admin tables:
  ```bash
  mysql -u root bihak < includes/admin_tables.sql
  ```

---

## 🔧 Maintenance

### Regular Tasks

#### Daily
- Check pending profiles
- Review activity log for suspicious activity

#### Weekly
- Clean old sessions:
  ```sql
  DELETE FROM admin_sessions WHERE expires_at < NOW();
  ```
- Review failed login attempts

#### Monthly
- Clean old activity logs (keep 90 days):
  ```sql
  DELETE FROM admin_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
  ```
- Clean old rate limit entries:
  ```sql
  DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 DAY);
  ```

### Backups

**Important**: Backup these regularly:
- Database: `bihak` (all tables)
- Uploaded files: `/uploads/profiles/`
- Configuration: `/config/`

---

## 📈 Future Enhancements

### Phase 4 - Coming Soon
- [ ] Email notifications (user + admin)
- [ ] Two-factor authentication (2FA)
- [ ] Password reset functionality
- [ ] Admin user management page
- [ ] Bulk profile operations
- [ ] Advanced analytics and reports
- [ ] Email queue system
- [ ] Scheduled profile publishing
- [ ] Profile editing by admin
- [ ] Media library management

---

## 🔗 Related Files

- `config/auth.php` - Authentication system
- `config/security.php` - Security utilities
- `includes/admin_tables.sql` - Database schema
- `public/admin/login.php` - Login page
- `public/admin/dashboard.php` - Main dashboard
- `public/admin/profiles.php` - Profile management
- `public/admin/profile-review.php` - Profile review
- `assets/css/admin-dashboard.css` - Admin styles
- `assets/js/admin-dashboard.js` - Admin JavaScript

---

## 📞 Support

### Documentation
- `COMPLETE-GUIDE.md` - Full project guide
- `TRANSFORMATION-PLAN.md` - Development roadmap
- `TRANSFORMATION-STATUS.md` - Current progress

### Issues
If you encounter issues:
1. Check this guide first
2. Review error logs
3. Check database connection
4. Verify all files are in place

---

## ✅ Checklist for Deployment

Before deploying to production:

- [ ] Change default admin password
- [ ] Review and update admin user list
- [ ] Enable HTTPS (required for security headers)
- [ ] Configure proper SMTP for email notifications
- [ ] Set up automated database backups
- [ ] Configure proper file permissions
- [ ] Update security headers for production domain
- [ ] Enable error logging (disable display_errors)
- [ ] Set up monitoring and alerts
- [ ] Test all admin functions on production
- [ ] Document custom configuration
- [ ] Create backup admin account

---

**The admin system is now fully functional and ready to use!** 🎉

Test it out:
1. Run `EASY-SETUP.bat`
2. Visit http://localhost/bihak-center/public/admin/login.php
3. Login with admin / Admin@123
4. Explore the dashboard!
