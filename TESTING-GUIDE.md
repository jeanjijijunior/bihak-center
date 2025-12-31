# Bihak Center - Testing Guide

## 🌐 Test Website Access

**Website URL**: http://155.248.239.239

The website is now live on Oracle Cloud and accessible to anyone with internet access.

---

## 👥 Test Accounts

### Regular User Account
- **Email**: `test@example.com`
- **Password**: `test123`
- **Purpose**: Test general user features

### Admin Account
- **Email**: `admin@bihak.com`
- **Password**: `admin123`
- **Purpose**: Test admin panel features
- **Admin URL**: http://155.248.239.239/admin/

### Mentor/Sponsor Account
- **Login URL**: http://155.248.239.239/mentor-login.php
- Check with admin for credentials

---

## 🧪 What to Test

### 1. **Homepage & Navigation**
- [ ] Homepage loads properly
- [ ] Navigation menu works in both English and French
- [ ] Language switcher works
- [ ] All links are clickable
- [ ] Images load correctly

### 2. **User Registration & Login**
- [ ] Sign up page works (http://155.248.239.239/signup.php)
- [ ] Create a new account with your email
- [ ] Receive email verification (if enabled)
- [ ] Login with new account
- [ ] Logout functionality works

### 3. **Opportunities Section**
- [ ] Browse scholarships, jobs, internships, grants
- [ ] Search and filter features work
- [ ] Click "Refresh Opportunities" button (admin only)
- [ ] Opportunity details pages load

### 4. **Incubation Program**
- [ ] Access incubation landing page
- [ ] Create a team
- [ ] Submit exercises
- [ ] View team dashboard
- [ ] Check progress tracking

### 5. **Mentorship System**
- [ ] Browse mentors
- [ ] Request mentorship
- [ ] View mentorship workspace
- [ ] Test messaging between mentor and mentee

### 6. **Messaging System**
- [ ] Access inbox
- [ ] Send messages to other users
- [ ] Receive notifications
- [ ] Chat widget appears on pages

### 7. **User Profile**
- [ ] View your profile
- [ ] Edit profile information
- [ ] Upload profile picture
- [ ] Add skills and experience

### 8. **Admin Panel** (Admin account only)
- [ ] Login to admin panel
- [ ] View dashboard statistics
- [ ] Manage user profiles
- [ ] Review incubation submissions
- [ ] Manage opportunities
- [ ] View activity logs

---

## 🐛 What to Report

Please report any issues you find:

### Bug Report Template:
```
**Page/Feature**: (e.g., Login page, Opportunities section)
**What happened**: (Describe the bug)
**Expected behavior**: (What should happen)
**Steps to reproduce**:
1. Go to...
2. Click on...
3. See error...
**Browser**: (Chrome, Firefox, Safari, etc.)
**Device**: (Windows PC, Mac, iPhone, Android, etc.)
**Screenshot**: (If possible)
```

### Common Things to Look For:
- ❌ Broken links
- ❌ Missing images
- ❌ Pages not loading
- ❌ Forms not submitting
- ❌ Error messages
- ❌ Styling issues (CSS not loading)
- ❌ Mobile responsiveness problems
- ❌ Language translation errors (French/English)
- ❌ Slow performance
- ❌ Security issues

---

## 📱 Device Testing

Please test on multiple devices if possible:
- [ ] **Desktop**: Windows, Mac, Linux
- [ ] **Mobile**: iOS (iPhone/iPad), Android
- [ ] **Browsers**: Chrome, Firefox, Safari, Edge

---

## 🔒 Security Testing

**DO NOT** try to:
- ❌ Hack or exploit the website
- ❌ Access admin areas without permission
- ❌ Upload malicious files
- ❌ Spam the system
- ❌ Delete other users' data

**DO** report if you accidentally find:
- ✅ SQL injection vulnerabilities
- ✅ XSS (cross-site scripting) issues
- ✅ Unauthorized access to admin areas
- ✅ Password security weaknesses
- ✅ Data exposure issues

---

## 📊 Performance Testing

Try these scenarios:
- Upload large files (images, documents)
- Create multiple team members
- Submit multiple exercises
- Send many messages
- Search with different keywords
- Filter opportunities with various criteria

Report if anything is:
- Unusually slow
- Crashes or times out
- Consumes excessive bandwidth

---

## 💬 Feedback Categories

### 1. **Usability**
- Is it easy to use?
- Is navigation intuitive?
- Are instructions clear?

### 2. **Design**
- Does it look professional?
- Are colors and fonts consistent?
- Is spacing appropriate?
- Does it work well on mobile?

### 3. **Content**
- Is text clear and understandable?
- Are translations accurate (French)?
- Are error messages helpful?

### 4. **Functionality**
- Do all features work as expected?
- Are there missing features you expected?
- Any broken or incomplete features?

---

## 📝 How to Submit Feedback

**Send your feedback to**: [Your email or feedback form]

**Or create an issue on GitHub**: https://github.com/jeanjijijunior/bihak-center/issues

**Include**:
1. Your name and role
2. Date and time of testing
3. Device/browser used
4. List of issues found
5. Suggestions for improvement
6. Overall impression (1-5 stars)

---

## ⏱️ Testing Timeline

**Testing Period**: [Start Date] - [End Date]

**Please complete testing by**: [Deadline]

**Priority areas**:
1. **Critical**: User registration, login, core features
2. **High**: Incubation program, opportunities, mentorship
3. **Medium**: Profile management, messaging
4. **Low**: Admin panel, advanced features

---

## ✅ Testing Checklist

Use this checklist to ensure comprehensive testing:

### Basic Functionality
- [ ] Website loads
- [ ] All pages accessible
- [ ] No 404 errors
- [ ] Images display
- [ ] CSS styling works

### User Journey
- [ ] Complete signup process
- [ ] Login successfully
- [ ] Browse opportunities
- [ ] Apply to opportunity
- [ ] Join incubation program
- [ ] Complete an exercise
- [ ] Request mentorship
- [ ] Send a message
- [ ] Update profile
- [ ] Logout

### Language Support
- [ ] Switch to French
- [ ] All text translated
- [ ] Switch back to English
- [ ] Language persists across pages

### Mobile Experience
- [ ] Responsive design works
- [ ] Touch interactions work
- [ ] No horizontal scrolling
- [ ] Text is readable
- [ ] Buttons are clickable

---

## 🎯 Success Criteria

The website is ready for launch when:
- ✅ No critical bugs
- ✅ All core features work
- ✅ Mobile-friendly
- ✅ Fast loading times
- ✅ Secure
- ✅ Bilingual support works
- ✅ Positive user feedback

---

## 🆘 Need Help?

**Technical Issues**: Contact [Admin name/email]

**Questions about Testing**: Contact [Project manager/email]

**Urgent Bugs**: Report immediately to [Contact]

---

## 🙏 Thank You!

Your feedback is invaluable in making Bihak Center the best platform for empowering African youth. Thank you for taking the time to test and provide feedback!

---

**Happy Testing! 🚀**
