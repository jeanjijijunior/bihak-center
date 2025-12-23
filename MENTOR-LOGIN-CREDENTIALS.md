# Mentor/Sponsor Login Credentials

## ✅ Test Mentor Account Created!

### Login Information:

**Login Page (Unified for All User Types):**
```
http://localhost/bihak-center/public/login.php
```

**Credentials:**
- **Email:** `mentor@bihakcenter.org`
- **Password:** `Mentor@123`

> **Note:** All users (regular users, mentors, sponsors, and admins) now login through the SAME page. The system automatically detects your user type and redirects you to the appropriate dashboard.

---

## 🎓 What You Can Do After Login:

Once logged in, you can access:

1. **Mentor Dashboard:**
   ```
   http://localhost/bihak-center/public/mentorship/dashboard.php
   ```
   - View all your mentees
   - See pending requests
   - Quick actions

2. **Preferences:**
   ```
   http://localhost/bihak-center/public/mentorship/preferences.php
   ```
   - Set maximum mentees (default: 5)
   - Set availability hours (default: 10)
   - Choose preferred sectors
   - List skills you can mentor
   - Select languages

3. **Browse Mentees:**
   ```
   http://localhost/bihak-center/public/mentorship/browse-mentees.php
   ```
   - Find users who need mentorship
   - Offer to mentor them
   - View their profiles

4. **Manage Requests:**
   ```
   http://localhost/bihak-center/public/mentorship/requests.php
   ```
   - View incoming mentorship requests
   - Accept or reject with message
   - See mentee details

---

## 📝 Mentor Profile Details:

- **Name:** John Mentor
- **Organization:** Tech Innovations Rwanda
- **Expertise:** Technology, Business, Leadership
- **Max Mentees:** 5
- **Availability:** 10 hours/month
- **Languages:** English, French, Kinyarwanda
- **Skills:** Programming, Leadership, Business Strategy, Public Speaking

---

## 🔐 How Mentor Login Works:

1. Sponsors/mentors register via the **"Get Involved"** form
2. Admin approves them
3. Admin sets password in database (or system sends credentials)
4. They login via **`login.php`** (same page as all users)
5. System automatically detects they are a mentor/sponsor
6. Session stores `$_SESSION['sponsor_id']`
7. They are redirected to mentorship dashboard
8. They can access all mentor features

---

## 🆕 What Was Added:

### Database Changes:
- ✅ Added `password_hash` column to `sponsors` table
- ✅ Created test mentor with hashed password
- ✅ Created mentor preferences entry

### New/Modified Files:
- ✅ `public/login.php` - **UPDATED** to unified login for all user types
- ✅ `add_sponsor_password.sql` - SQL script to add password column
- ✅ `UNIFIED-LOGIN-SYSTEM.md` - Complete documentation of unified login
- ⚠️ `public/mentor-login.php` - OBSOLETE (no longer needed, can be deleted)

---

## 🧪 Testing Steps:

1. **Visit:** `http://localhost/bihak-center/public/login.php`
2. **Enter:**
   - Email: `mentor@bihakcenter.org`
   - Password: `Mentor@123`
3. **Click:** "Sign In"
4. **You should be automatically redirected to:** `mentorship/dashboard.php`
5. **Test:**
   - Click "⚙️ Preferences" to set mentoring preferences
   - Click "Find Mentees" to browse users to mentor
   - Click "Requests" to see incoming requests
   - Send messages to mentees
   - View your mentorship relationships

---

## 👥 Other Test Accounts:

### Regular User (Mentee):
- Email: `demo@bihakcenter.org`
- Password: `Demo@123`
- Can: Request mentors, browse opportunities

### Admin:
- Username: `admin`
- Password: `Admin@123`
- Can: Approve profiles, manage system

---

## 💡 Next Steps:

### To Create More Mentors:
1. Go to "Get Involved" form
2. Fill out as mentor
3. Admin approves
4. Set password in database or during registration

### To Link Existing Mentors:
Run this SQL for each existing mentor:
```sql
UPDATE sponsors
SET password_hash = '$2y$10$kZqFj7VJQxZ6K6aP1/HGNe0nJ5UoGPQx.mQKVX9YzW2LqPmxYw0O2'
WHERE email = 'their_email@example.com';
```
(Password will be: `Mentor@123`)

---

## 🔒 Security Note:

The password `Mentor@123` is for **testing only**. In production:
- Use strong, unique passwords
- Enable email verification
- Add password reset functionality
- Implement 2FA for mentors

---

**Created:** November 25, 2025
**Status:** ✅ Working
**File:** `MENTOR-LOGIN-CREDENTIALS.md`
