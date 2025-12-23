# Mentorship System - Complete User Guide

## ✅ STATUS: FULLY IMPLEMENTED AND READY TO USE!

The mentor-mentee connection system is **100% complete** with all features working. Here's how to use it:

---

## 🎯 System Overview

The Bihak Center mentorship system allows:
- **Mentees (Users)** to browse and request mentors
- **Mentors (Sponsors)** to offer mentorship to users
- **Both parties** to manage relationships, set goals, and track progress

---

## 📱 **For MENTEES (Regular Users)**

### Step 1: Access the Mentorship Dashboard
**URL:** `http://localhost/bihak-center/public/mentorship/dashboard.php`

You must be logged in as a **regular user** (not sponsor/admin).

### Step 2: Browse Available Mentors
**URL:** `http://localhost/bihak-center/public/mentorship/browse-mentors.php`

Features:
- ✅ View all available mentors
- ✅ See mentor profiles (expertise, organization, bio)
- ✅ See how many mentees each mentor has
- ✅ Search by name, organization, or expertise
- ✅ Filter by sector or skill

### Step 3: Request a Mentor
1. Click on a mentor card
2. Review their profile
3. Click **"Request Mentorship"** button
4. Confirm the request
5. Wait for mentor to accept/reject

**What happens next:**
- ✅ Mentor receives notification
- ✅ Your request appears in your dashboard as "Pending"
- ✅ Mentor can accept or reject from their dashboard

### Step 4: After Acceptance
Once accepted, you can:
- ✅ View relationship in your dashboard
- ✅ Set goals together
- ✅ Track activities
- ✅ Message your mentor directly (via chat widget)
- ✅ Access workspace for collaboration

**Access workspace:**
`http://localhost/bihak-center/public/mentorship/workspace.php?id=[relationship_id]`

---

## 👔 **For MENTORS (Sponsors)**

### Step 1: Register as Mentor
1. Go to "Get Involved" page
2. Fill out sponsor form
3. Select **role_type = 'mentor'**
4. Admin approves you

### Step 2: Access Mentor Dashboard
**URL:** `http://localhost/bihak-center/public/mentorship/dashboard.php`

You must be logged in as a **sponsor** with role_type 'mentor'.

### Step 3: Review Incoming Requests
**URL:** `http://localhost/bihak-center/public/mentorship/requests.php`

Features:
- ✅ See all pending mentorship requests
- ✅ View mentee profiles
- ✅ See their goals and needs
- ✅ Accept or reject requests with message

### Step 4: Offer Mentorship (Proactive)
**URL:** `http://localhost/bihak-center/public/mentorship/browse-mentees.php`

Features:
- ✅ Browse available mentees
- ✅ View their profiles and needs
- ✅ Offer to mentor them directly
- ✅ Search and filter mentees

### Step 5: Manage Active Mentees
From your dashboard, you can:
- ✅ View all active mentorships
- ✅ Set and track goals
- ✅ Log activities and notes
- ✅ Message mentees
- ✅ End relationship when appropriate

---

## 🔗 **All Available Pages**

### Main Pages
1. **Dashboard** - `public/mentorship/dashboard.php`
   - Overview of all relationships
   - Pending requests
   - Quick actions

2. **Browse Mentors** - `public/mentorship/browse-mentors.php`
   - For mentees to find mentors
   - Search and filter
   - Request mentorship

3. **Browse Mentees** - `public/mentorship/browse-mentees.php`
   - For mentors to find mentees
   - Offer mentorship proactively

4. **Requests** - `public/mentorship/requests.php`
   - View all pending requests
   - Accept/reject with message

5. **Workspace** - `public/mentorship/workspace.php`
   - Collaborative space for active relationships
   - Goals, activities, notes
   - Progress tracking

---

## 🔌 **API Endpoints**

All API endpoints are in `api/mentorship/`:

1. **suggestions.php** - GET
   - Get suggested mentors for a mentee
   - Uses matching algorithm

2. **request.php** - POST
   - Request mentorship relationship
   - Can be initiated by mentee or mentor

3. **respond.php** - POST
   - Accept or reject pending request
   - Include optional message

4. **end.php** - POST
   - End an active mentorship
   - Requires reason

5. **goals.php** - GET/POST/PUT
   - Manage mentorship goals
   - Track progress

6. **activities.php** - GET/POST
   - Log activities and notes
   - Track meetings

---

## 💾 **Database Tables**

All tables in the `mentorship_messaging_schema.sql`:

1. **mentorship_relationships**
   - Tracks all mentor-mentee pairs
   - Status: pending, active, ended, rejected

2. **mentorship_goals**
   - Goals set within relationships
   - Priority and completion tracking

3. **mentorship_activities**
   - Activity log and notes
   - Meeting history

4. **mentor_preferences**
   - Mentor availability and skills
   - Maximum mentees allowed

5. **mentee_needs**
   - What mentees need help with
   - Sectors and skills of interest

---

## 🎨 **User Interface Features**

### Beautiful Design
- ✅ Modern card-based layout
- ✅ Responsive (mobile-friendly)
- ✅ Avatar initials for profiles
- ✅ Color-coded status badges
- ✅ Real-time updates

### Smart Features
- ✅ Shows mentor capacity (e.g., "2/5 mentees")
- ✅ Disables "Request" if mentor is full
- ✅ Shows "Pending" status for sent requests
- ✅ Prevents duplicate requests
- ✅ Search with auto-suggestions

### Integration
- ✅ Chat widget for messaging
- ✅ Links to user profiles
- ✅ Notifications system
- ✅ Activity logging

---

## 🧪 **How to Test the System**

### Test as Mentee (User)
1. **Login as regular user:**
   - Email: `demo@bihakcenter.org`
   - Password: `Demo@123`

2. **Navigate to:**
   `http://localhost/bihak-center/public/mentorship/browse-mentors.php`

3. **Request a mentor:**
   - Click on any mentor card
   - Click "Request Mentorship"
   - Confirm

4. **Check status:**
   - Go to dashboard
   - See "Pending Requests" section

### Test as Mentor (Sponsor)
1. **Login as sponsor/mentor:**
   - You need to create a sponsor account first via "Get Involved"
   - Or use existing sponsor login (if you have one)

2. **Navigate to:**
   `http://localhost/bihak-center/public/mentorship/requests.php`

3. **Accept a request:**
   - View pending requests
   - Click "Accept"
   - Add optional welcome message

4. **Manage relationship:**
   - Go to workspace
   - Set goals
   - Log activities

---

## 🔐 **Access Control**

### Who Can Access What?

| Page/Feature | User (Mentee) | Sponsor (Mentor) | Admin |
|--------------|---------------|------------------|-------|
| Browse Mentors | ✅ | ❌ | ✅ |
| Browse Mentees | ❌ | ✅ | ✅ |
| Request Mentor | ✅ | ❌ | ❌ |
| Offer Mentorship | ❌ | ✅ | ❌ |
| Accept/Reject | ❌ | ✅ | ✅ |
| Workspace | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |

---

## 📊 **Matching Algorithm**

The system uses intelligent matching based on:
- ✅ Mentor expertise vs mentee needs
- ✅ Sector preferences
- ✅ Skills alignment
- ✅ Language preferences
- ✅ Mentor availability

**Match score:** 0-100% (stored in `match_score` column)

---

## 🎯 **Relationship Lifecycle**

```
1. REQUEST
   Mentee requests → Status: "pending"
   ↓
2. REVIEW
   Mentor reviews request
   ↓
3. DECISION
   Accept → Status: "active"
   Reject → Status: "rejected"
   ↓
4. ACTIVE PHASE
   - Set goals
   - Track activities
   - Regular meetings
   - Message via chat
   ↓
5. COMPLETION
   Either party can end
   → Status: "ended"
   → Reason required
```

---

## 🚨 **Common Issues & Solutions**

### Issue 1: "No mentors found"
**Solution:**
- Check if sponsors have `role_type = 'mentor'`
- Verify sponsors are approved (`status = 'approved'`)
- Make sure `is_active = 1`

### Issue 2: "Can't request mentorship"
**Possible reasons:**
- Mentor slots are full
- Already have pending request to this mentor
- Already have active relationship with this mentor

### Issue 3: "Request button disabled"
**Check:**
- Mentor's `max_mentees` in `mentor_preferences` table
- Count of active mentorships for that mentor

### Issue 4: "Can't access mentor dashboard"
**Solution:**
- Must be logged in as sponsor
- `role_type` must include 'mentor'
- Account must be approved

---

## 💡 **Tips for Best Experience**

### For Mentees:
1. Complete your profile fully
2. Fill out `mentee_needs` table
3. Search for mentors in your field
4. Read mentor bios carefully before requesting
5. Be respectful of mentor's time

### For Mentors:
1. Set realistic `max_mentees` limit
2. Fill out mentor preferences
3. Respond to requests promptly
4. Set clear goals with mentees
5. Log activities regularly

---

## 🔗 **Quick Links**

| Action | URL |
|--------|-----|
| **Mentee Dashboard** | `/public/mentorship/dashboard.php` |
| **Find Mentors** | `/public/mentorship/browse-mentors.php` |
| **Mentor Dashboard** | `/public/mentorship/dashboard.php` |
| **Find Mentees** | `/public/mentorship/browse-mentees.php` |
| **My Requests** | `/public/mentorship/requests.php` |
| **Workspace** | `/public/mentorship/workspace.php?id=[ID]` |

---

## 📞 **Support**

If you encounter issues:
1. Check Apache error logs: `C:\xampp\apache\logs\error.log`
2. Verify database tables exist
3. Check session is active
4. Verify user authentication

---

## ✅ **System Status Checklist**

- ✅ Database tables created
- ✅ API endpoints working
- ✅ Browse mentors page functional
- ✅ Browse mentees page functional
- ✅ Request system working
- ✅ Accept/reject system working
- ✅ Dashboard displaying data
- ✅ Workspace for collaboration
- ✅ Goals and activities tracking
- ✅ Chat integration
- ✅ Responsive design
- ✅ Access control implemented

**🎉 The mentorship system is 100% complete and ready to use!**

---

**Last Updated:** November 25, 2025
**Status:** Production Ready ✅
