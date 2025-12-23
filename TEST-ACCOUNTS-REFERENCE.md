# Test Accounts Reference

**Date:** November 28, 2025
**Status:** ✅ All accounts active and ready for testing

---

## 🔐 TEST CREDENTIALS

### Universal Test Password
**All accounts use:** `Test@123`

---

## 👤 REGULAR USER ACCOUNTS

### Test User (Primary)
- **URL:** http://localhost/bihak-center/public/login.php
- **Email:** `testuser@bihakcenter.org`
- **Password:** `Test@123`
- **Name:** Test User
- **ID:** 4
- **🎓 Has Active Mentor:** John Mentor (mentor@bihakcenter.org)
- **Match Score:** 85.50%

### Sarah Uwase (Secondary)
- **URL:** http://localhost/bihak-center/public/login.php
- **Email:** `sarah.uwase@demo.rw`
- **Password:** `Test@123`
- **Name:** Sarah Uwase
- **ID:** 3
- **📋 Pending Request:** With Jean Jiji (jijiniyo@gmail.com)

---

## 🎓 MENTOR/SPONSOR ACCOUNTS

### John Mentor (Primary)
- **URL:** http://localhost/bihak-center/public/login.php
- **Email:** `mentor@bihakcenter.org`
- **Password:** `Test@123`
- **Name:** John Mentor
- **Role:** Mentor
- **Status:** Approved
- **ID:** 5
- **🎓 Active Mentee:** Test User (testuser@bihakcenter.org)
- **Match Score:** 85.50%

### Jean Jiji (Secondary)
- **URL:** http://localhost/bihak-center/public/login.php
- **Email:** `jijiniyo@gmail.com`
- **Password:** `Test@123`
- **Name:** Jean Jiji Junior Niyonkuru
- **Role:** Mentor
- **Status:** Approved
- **ID:** 4
- **📋 Pending Request:** From Sarah Uwase (sarah.uwase@demo.rw)

---

## 🔧 ADMIN ACCOUNTS

### Test Admin (Primary)
- **URL:** http://localhost/bihak-center/public/admin/login.php
- **Username:** `testadmin`
- **Password:** `Test@123`
- **Email:** testadmin@bihakcenter.org
- **ID:** 4

### Main Admin (Secondary)
- **URL:** http://localhost/bihak-center/public/admin/login.php
- **Username:** `admin`
- **Password:** `Test@123`
- **Email:** admin@bihakcenter.org
- **ID:** 2

---

## 🧪 TESTING WORKFLOW

### A. Test Regular User Session

**Steps:**
1. Go to http://localhost/bihak-center/public/login.php
2. Login with `testuser@bihakcenter.org` / `Test@123`
3. **Verify Header:**
   - Shows avatar with "T" initial
   - Shows "Test User" name
   - No "Login" button visible
4. **Test User Dropdown:**
   - Click on username/avatar
   - Should see: My Account, Profile, Logout
   - Click outside - dropdown closes
5. **Test Navigation:**
   - Click navbar links (Home, About, Stories, etc.)
   - Header should persist on all pages
   - User menu should remain functional
6. **Test Language Switcher:**
   - Click EN/FR buttons
   - Language should switch
   - Active language highlighted
7. **Test Logout:**
   - Click Logout in dropdown
   - Should redirect to login page
   - Session should be cleared

---

### B. Test Mentor Session

**Steps:**
1. Go to http://localhost/bihak-center/public/login.php
2. Login with `mentor@bihakcenter.org` / `Test@123`
3. **Verify Header:**
   - Shows avatar with "J" initial
   - Shows "John Mentor" name
   - No "Login" button visible
4. **Test Mentor Dropdown:**
   - Click on username/avatar
   - Should see: Mentorship Dashboard, Preferences, Logout
   - Click outside - dropdown closes
5. **Navigate to Mentor Dashboard:**
   - Go to http://localhost/bihak-center/public/mentorship/dashboard.php
   - OR click "Mentorship Dashboard" in dropdown
6. **Test Navbar from Mentor Pages:**
   - ✅ Click "Home" → Should go to index.php
   - ✅ Click "About" → Should go to about.php
   - ✅ Click "Stories" → Should go to stories.php
   - ✅ Click "Our Work" → Should go to work.php
   - ✅ Click "Opportunities" → Should go to opportunities.php
   - ✅ Click "Contact" → Should go to contact.php
7. **Test Action Buttons:**
   - ✅ Click "Incubation" → Should go to incubation-program.php
   - ✅ Click "Get Involved" → Should go to get-involved.php
   - ✅ Click "Share Story" → Should go to signup.php
8. **Test Mentorship Pages:**
   - Browse Mentees
   - Preferences
   - All should maintain header functionality
9. **Test Logout:**
   - Click Logout in dropdown
   - Should redirect to login page
   - Session should be cleared

---

### C. Test Admin Session

**Steps:**
1. Go to http://localhost/bihak-center/public/admin/login.php
2. Login with `testadmin` / `Test@123`
3. **Verify Header:**
   - Shows avatar with "T" initial
   - Shows "testadmin" name
4. **Test Admin Dropdown:**
   - Click on username/avatar
   - Should see: Admin Dashboard, Incubation Admin, Logout
   - Click outside - dropdown closes
5. **Navigate Admin Pages:**
   - Admin Dashboard
   - Incubation Admin Dashboard
   - Users Management
   - Content Management
6. **Test Navbar:**
   - All links should work from admin pages
   - Base path should be correct (../)
7. **Test Logout:**
   - Click Logout in dropdown
   - Should redirect to admin/login.php
   - Session should be cleared

---

### D. Test Interactive Features

**User Dropdown:**
- ✅ Click username → Dropdown opens
- ✅ Click outside → Dropdown closes
- ✅ Click menu item → Navigates correctly
- ✅ ESC key → Dropdown closes (if implemented)

**Language Switcher:**
- ✅ Click "EN" → Switches to English
- ✅ Click "FR" → Switches to French
- ✅ Active language has different styling
- ✅ Preference persists across pages

**Mobile Menu:**
- ✅ Resize browser to mobile (< 768px)
- ✅ Click hamburger icon → Menu opens
- ✅ Click outside → Menu closes
- ✅ Click nav link → Menu closes and navigates

**Scroll Effects:**
- ✅ Scroll down 50+ pixels → Header gets shadow
- ✅ Scroll back to top → Shadow removed
- ✅ Header remains sticky

---

## 🐛 KNOWN ISSUES & FIXES

### Issue 1: Mentor Session Not Recognized ✅ FIXED
**File:** [includes/header_new.php](includes/header_new.php:82)
**Fix:** Added `$is_sponsor = isset($_SESSION['sponsor_id']);` check

### Issue 2: Navbar Broken in Mentor Pages ✅ FIXED
**File:** [includes/header_new.php](includes/header_new.php:1-29)
**Fix:** Added parent directory detection for subdirectories

### Issue 3: Dropdown Not Opening ✅ FIXED
**File:** [includes/header_new.php](includes/header_new.php:182)
**Fix:** Added `<script src="header_new.js"></script>` include

### Issue 4: Logout Not Working for Mentors ✅ FIXED
**File:** [public/logout.php](public/logout.php)
**Fix:** Universal logout handling all user types

---

## 📝 SESSION VARIABLES

### Regular Users
```php
$_SESSION['user_id']        // User ID
$_SESSION['user_name']      // User full name
$_SESSION['user_email']     // User email
```

### Mentors/Sponsors
```php
$_SESSION['sponsor_id']     // Sponsor ID
$_SESSION['sponsor_name']   // Sponsor full name
$_SESSION['sponsor_email']  // Sponsor email
$_SESSION['sponsor_role']   // Role (mentor/donor/sponsor)
```

### Admins
```php
$_SESSION['admin_id']       // Admin ID
$_SESSION['admin_username'] // Admin username
$_SESSION['is_admin']       // Boolean flag
```

---

## 🔄 DATABASE TABLES

### Users
- **Table:** `users`
- **Key Fields:** id, email, password, full_name, is_active

### Mentors/Sponsors
- **Table:** `sponsors`
- **Key Fields:** id, email, password_hash, full_name, role_type, status

### Admins
- **Table:** `admins`
- **Key Fields:** id, username, password_hash, email, is_active

---

## ⚙️ SETUP AND RESET SCRIPTS

### Reset Test Account Passwords

To reset all test account passwords to `Test@123`, run:

```bash
php c:\xampp\htdocs\bihak-center\setup_test_accounts.php
```

This will:
- Update all test account passwords
- Ensure accounts are active
- Approve mentor accounts
- Display verification summary

### Create Mentorship Matches

To create/reset mentorship relationships for testing, run:

```bash
php c:\xampp\htdocs\bihak-center\create_mentorship_match.php
```

This will:
- Create ACTIVE mentorship: John Mentor ↔ Test User
- Create PENDING request: Jean Jiji ← Sarah Uwase
- Set realistic match scores
- Display testing workflow guide

## 🎓 MENTORSHIP RELATIONSHIPS

### Active Mentorship (Ready to Test)
- **Mentor:** John Mentor (mentor@bihakcenter.org)
- **Mentee:** Test User (testuser@bihakcenter.org)
- **Status:** ACTIVE ✅
- **Match Score:** 85.50%
- **Accepted:** Yes
- **Relationship ID:** 1

### Pending Request (Test Approval Flow)
- **Mentor:** Jean Jiji (jijiniyo@gmail.com)
- **Mentee:** Sarah Uwase (sarah.uwase@demo.rw)
- **Status:** PENDING ⏳
- **Match Score:** 78.30%
- **Requested By:** Mentee
- **Relationship ID:** 2

---

## 📚 RELATED DOCUMENTATION

- [MENTOR-LOGIN-SESSION-FIX.md](MENTOR-LOGIN-SESSION-FIX.md) - Mentor session management fix
- [NAVBAR-PATH-FIX.md](NAVBAR-PATH-FIX.md) - Navigation path resolution fix
- [DROPDOWN-LANGUAGE-SWITCHER-FIX.md](DROPDOWN-LANGUAGE-SWITCHER-FIX.md) - JavaScript inclusion fix

---

**Last Updated:** November 28, 2025
**Status:** ✅ All systems operational
