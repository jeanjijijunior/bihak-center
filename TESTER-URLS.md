# Bihak Center - Testing URLs

## Main Website URL
```
http://155.248.239.239
```
**Note:** Just use the IP address without `/index.php` - it will load automatically.

---

## All Available Pages for Testing

### Public Pages

| Page | URL | What to Test |
|------|-----|--------------|
| **Homepage** | http://155.248.239.239 | Main landing page, navigation, hero section |
| **About Us** | http://155.248.239.239/about.php | Vision, mission, team information |
| **Our Programs** | http://155.248.239.239/work.php | Program details, orientation, coaching, support |
| **Success Stories** | http://155.248.239.239/stories.php | Youth profiles and achievements |
| **Contact** | http://155.248.239.239/contact.php | Contact form and information |
| **Sign Up** | http://155.248.239.239/signup.php | **NEW: Now includes security questions!** |
| **Login** | http://155.248.239.239/login.php | User authentication |

### Mentorship Program
| Page | URL |
|------|-----|
| Mentorship Overview | http://155.248.239.239/mentorship/ |
| Become a Mentor | http://155.248.239.239/mentorship/become-mentor.php |
| Mentor Login | http://155.248.239.239/mentorship/mentor-login.php |

### Incubation Program
| Page | URL |
|------|-----|
| Incubation Overview | http://155.248.239.239/incubation/ |
| Join Incubation | http://155.248.239.239/incubation/join.php |

### Admin Panel (Staff Only)
| Page | URL |
|------|-----|
| Admin Login | http://155.248.239.239/admin/login.php |

---

## What's New in This Version

### ✅ Security Questions Added to Signup
- Users now register 3 security questions during signup
- These are used for password recovery
- Answers are securely hashed (not stored as plain text)

### ✅ Brand Consistency
- All purple colors replaced with Bihak brand colors (blue/gold)
- Fancy icons removed across the site
- Consistent color scheme on all pages

### ✅ Mobile Optimizations
- Improved responsiveness on all devices
- Better touch targets for mobile users
- Reduced animations for better performance
- Fixed hamburger menu behavior

### ✅ File Upload Improvements
- Better error messages for upload failures
- Supports up to 3 images (2MB each)
- Detailed diagnostics for troubleshooting

---

## Testing Checklist

### 1. Homepage Testing
- [ ] Page loads without errors
- [ ] Navigation menu works (desktop)
- [ ] Hamburger menu works (mobile)
- [ ] All images load correctly
- [ ] Footer social media links work
- [ ] Brand colors (blue/gold) are consistent

### 2. Signup Process Testing
- [ ] Form loads with all fields
- [ ] Security questions dropdown appears (3 questions)
- [ ] Can select different security questions
- [ ] Form validation works (required fields)
- [ ] Can upload profile images (max 3, 2MB each)
- [ ] Success message appears after submission
- [ ] User account is created in database

### 3. Login Testing
- [ ] Can login with registered credentials
- [ ] "Forgot Password" link works
- [ ] Security questions appear during password reset
- [ ] "Remember Me" checkbox functions
- [ ] Redirects to dashboard after login

### 4. Mobile Responsiveness
- [ ] Test on phone (iPhone/Android)
- [ ] Test on tablet (iPad/Android)
- [ ] Navigation menu collapses properly
- [ ] Forms are usable on small screens
- [ ] Images scale correctly
- [ ] Text is readable (not too small)

### 5. Password Recovery
- [ ] Click "Forgot Password" on login page
- [ ] Enter registered email
- [ ] Security questions appear correctly
- [ ] Answer validation works
- [ ] Can reset password successfully

### 6. Cross-Browser Testing
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge
- [ ] Mobile browsers

### 7. Performance Testing
- [ ] Page loads in under 3 seconds
- [ ] No console errors (F12 developer tools)
- [ ] Images load properly
- [ ] No broken links

---

## Common Issues and Solutions

### Issue: "This site can't be reached"
**Solution:**
- Try accessing: `http://155.248.239.239` (without `/index.php`)
- Clear your browser cache (Ctrl+Shift+Delete)
- Try a different browser
- Check if you can access other websites (verify internet connection)

### Issue: Page shows "/index.php" in URL
**This is normal!**
- `http://155.248.239.239/` automatically redirects to `/index.php`
- Both URLs work the same way
- Just share `http://155.248.239.239` with users (cleaner)

### Issue: Security questions not visible on signup
**This means:**
- Database connection issue
- Security questions table not populated
- Contact admin immediately

### Issue: Images not uploading
**Check:**
- File size (max 2MB per image)
- File type (only JPG, PNG allowed)
- Maximum 3 images total
- Error message will indicate the specific issue

---

## How to Report Issues

When reporting bugs, please include:

1. **URL** - Which page has the issue?
2. **Browser** - Chrome, Firefox, Safari, etc.?
3. **Device** - Desktop, mobile phone, tablet?
4. **Screenshot** - If possible
5. **Steps to reproduce** - What did you do before the error?
6. **Error message** - Exact text of any error messages

### Example Bug Report:
```
URL: http://155.248.239.239/signup.php
Browser: Chrome 120
Device: iPhone 14
Issue: Cannot upload profile image
Error: "Image 1 is too large. Maximum file size allowed by server is 2MB."
Steps:
1. Filled out signup form
2. Selected 3MB image from gallery
3. Clicked submit
4. Got error message
```

---

## Testing Priority

### High Priority (Test First):
1. Homepage loads correctly
2. Signup with security questions works
3. Login works
4. Mobile responsiveness
5. Brand colors are consistent (no purple)

### Medium Priority:
1. All pages accessible
2. Navigation menu works
3. Forms validate properly
4. Images load correctly

### Low Priority:
1. Social media links work
2. Footer displays correctly
3. Minor styling issues

---

## Contact for Issues

If you encounter any issues during testing:
1. Take a screenshot
2. Note the exact URL
3. Document steps to reproduce
4. Report to development team

---

## System Requirements

### Recommended Browsers:
- Chrome 100+
- Firefox 100+
- Safari 15+
- Edge 100+

### Recommended Devices:
- Desktop: 1366x768 or higher
- Tablet: iPad or equivalent
- Mobile: iPhone 8 or newer, Android 10+

### Internet Connection:
- Minimum 2 Mbps for optimal experience

---

**Last Updated:** 2025-12-31
**Server IP:** 155.248.239.239
**Status:** ✅ Live and Accessible
