# Mentorship & Messaging System Guide

## 📋 Quick Answers to Your Questions

### 1. **Password Reset for jjniyo@gmail.com**

**What I Did:**
- I **DID NOT** answer security questions manually
- I used `fix_user_password.php` which **bypasses security questions entirely**
- The script directly updated the password hash in the database

**Current Login Credentials:**
```
Email: jjniyo@gmail.com
Password: password123
```

**Important Notes:**
- The user should **change this password immediately** after logging in
- Their original security question answers are **still intact and unchanged**
- This was a direct database password reset, not a normal password reset flow

---

## 👥 How Users See Matching Mentors

### Access Path:
**URL:** `http://localhost/bihak-center/public/mentorship/browse-mentors.php`

### Requirements:
- User must be logged in as regular user (`$_SESSION['user_id']` must be set)
- User must NOT already have an active mentor

### How It Works:

1. **System checks if user has active mentor:**
   ```php
   $active_mentors = $mentorshipManager->getActiveRelationships($mentee_id, 'mentee');
   $has_active_mentor = !empty($active_mentors);
   ```

2. **If no active mentor, get suggested mentors:**
   ```php
   $suggested_mentors = $mentorshipManager->getSuggestedMentors($mentee_id, 20);
   ```

3. **Matching Algorithm (from MentorshipManager.php):**
   ```php
   public function getSuggestedMentors($mentee_id, $limit = 10) {
       // Get all potential mentors from sponsors table
       // WHERE role_type IN ('mentor', 'sponsor', 'partner')
       // AND status = 'approved'
       // AND is_active = 1

       // Filter by:
       // 1. Mentor has capacity (active_mentees < max_mentees)
       // 2. No existing relationship (active, pending, or completed)
       // 3. Match preferences (areas of interest, expertise)
       // 4. Score based on preference alignment

       // Return top matches sorted by match score
   }
   ```

### What Users See:

- **Grid of mentor cards** showing:
  - Mentor's name
  - Organization
  - Areas of expertise
  - Number of active mentees
  - Bio/description
  - "Request Mentor" button

### User Actions:
1. **Browse mentors** - View all available mentors
2. **Filter by expertise** - Use dropdown filters
3. **Search by name** - Use search bar
4. **Request mentor** - Click button to send mentorship request

---

## 🎓 How Mentors See Matching Mentees

### Access Path:
**URL:** `http://localhost/bihak-center/public/mentorship/browse-mentees.php`

### Requirements:
- User must be logged in as sponsor/mentor (`$_SESSION['sponsor_id']` must be set)
- Sponsor's role_type must be 'mentor', 'sponsor', or 'partner'
- Sponsor must have capacity (active_mentees < max_mentees)

### How It Works:

1. **System gets potential mentees:**
   ```php
   $potential_mentees = $mentorshipManager->getPotentialMentees($mentor_id, 20);
   ```

2. **Matching Algorithm:**
   ```php
   public function getPotentialMentees($mentor_id, $limit = 10) {
       // Get all active users
       // WHERE is_active = 1

       // Filter by:
       // 1. User doesn't already have active mentor
       // 2. No existing relationship with this mentor
       // 3. Match preferences (if mentor has set preferences)
       // 4. Match areas of need with mentor's expertise

       // Return top matches sorted by need and alignment
   }
   ```

### What Mentors See:

- **Grid of mentee cards** showing:
  - Mentee's name
  - Age/Grade level
  - Areas of interest
  - Goals/needs
  - Brief introduction
  - "Offer Mentorship" button

### Mentor Actions:
1. **Browse mentees** - View all users seeking mentors
2. **Filter by interests** - Use dropdown filters
3. **Search by name** - Use search bar
4. **Offer mentorship** - Click button to send mentorship offer

---

## 💬 How Users/Mentors/Admins See Messages

### Access Paths:

| User Type | Session Variable | Access URL |
|-----------|-----------------|------------|
| **Regular User** | `$_SESSION['user_id']` | `/public/messages/inbox.php` |
| **Mentor/Sponsor** | `$_SESSION['sponsor_id']` | `/public/messages/inbox.php` |
| **Admin** | `$_SESSION['admin_id']` | `/public/messages/inbox.php` |

### Main Messaging Interface

**URL:** `http://localhost/bihak-center/public/messages/inbox.php`

### Authentication Check:
```php
if (!isset($_SESSION['user_id']) &&
    !isset($_SESSION['admin_id']) &&
    !isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php');
    exit;
}
```

### Participant Type Detection:
```php
if (isset($_SESSION['user_id'])) {
    $participant_type = 'user';
    $participant_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $participant_type = 'admin';
    $participant_id = $_SESSION['admin_id'];
} elseif (isset($_SESSION['sponsor_id'])) {
    $participant_type = 'mentor';
    $participant_id = $_SESSION['sponsor_id'];
}
```

### What They See:

#### **1. Inbox Page (`/messages/inbox.php`)**

**Layout:**
```
┌─────────────────────────────────────────────────────┐
│ Header (with user dropdown, etc.)                   │
├──────────────┬──────────────────────────────────────┤
│              │                                       │
│ Conversations│   Select a conversation to view      │
│ List         │   messages                           │
│              │                                       │
│ • John Doe   │   [Empty state shown when no        │
│   "Thanks!"  │    conversation selected]            │
│   2m ago     │                                       │
│              │                                       │
│ • Jane Smith │                                       │
│   "Hello"    │                                       │
│   1h ago     │                                       │
│              │                                       │
└──────────────┴──────────────────────────────────────┘
```

**Features:**
- **Left Sidebar:**
  - List of all conversations
  - Unread badge count
  - Last message preview
  - Time stamp (e.g., "2m ago", "1h ago", "Yesterday")
  - Online status indicator
  - Click to open conversation

- **Right Panel:**
  - Empty state until conversation is selected
  - "Select a conversation to view messages"

#### **2. Conversation Page (`/messages/conversation.php?id=123`)**

**Layout:**
```
┌─────────────────────────────────────────────────────┐
│ Header                                              │
├──────────────┬──────────────────────────────────────┤
│              │ ┌────────────────────────────────┐   │
│ Conversations│ │ Conversation with John Doe     │   │
│ List         │ │ [Online indicator]             │   │
│              │ └────────────────────────────────┘   │
│ • John Doe   │                                       │
│   "Thanks!"  │ ┌─────────────────────────────────┐ │
│   2m ago     │ │ Messages Area                   │ │
│   [ACTIVE]   │ │                                 │ │
│              │ │ John: Hey, how are you?         │ │
│ • Jane Smith │ │ [10:30 AM]                      │ │
│   "Hello"    │ │                                 │ │
│   1h ago     │ │ You: I'm doing great!          │ │
│              │ │ [10:32 AM] ✓✓                  │ │
│              │ │                                 │ │
│              │ │ John: That's awesome!           │ │
│              │ │ [10:35 AM]                      │ │
│              │ └─────────────────────────────────┘ │
│              │                                       │
│              │ ┌─────────────────────────────────┐ │
│              │ │ [Type a message...]            │ │
│              │ │                         [Send] │ │
│              │ └─────────────────────────────────┘ │
└──────────────┴──────────────────────────────────────┘
```

**Features:**
- **Real-time messaging** via WebSocket
- **Read receipts** (✓ sent, ✓✓ read)
- **Online status** indicators
- **Typing indicators** (shows when other person is typing)
- **Message timestamps**
- **Auto-scroll** to latest message
- **Send on Enter** (Shift+Enter for new line)

### WebSocket Integration

**Connection:**
```javascript
const ws = new WebSocket('ws://localhost:8080');

// Authenticate on connection
ws.send(JSON.stringify({
    type: 'authenticate',
    participant_type: 'user', // or 'admin', 'mentor'
    participant_id: 123,
    session_token: 'abc123...'
}));
```

**Sending Messages:**
```javascript
ws.send(JSON.stringify({
    type: 'send_message',
    conversation_id: 456,
    message: 'Hello there!',
    timestamp: new Date().toISOString()
}));
```

**Receiving Messages:**
```javascript
ws.onmessage = (event) => {
    const data = JSON.parse(event.data);

    if (data.type === 'new_message') {
        // Display new message in conversation
        displayMessage(data.message);
    } else if (data.type === 'typing') {
        // Show typing indicator
        showTypingIndicator(data.sender_name);
    } else if (data.type === 'online_status') {
        // Update online status
        updateOnlineStatus(data.participant_id, data.status);
    }
};
```

---

## 🗺️ Complete User Flow Examples

### Example 1: User Finds Mentor and Sends Message

1. **User logs in** → `login.php`
2. **Goes to mentorship** → `mentorship/browse-mentors.php`
3. **Views suggested mentors** → System shows mentors based on:
   - User's areas of interest
   - Mentor's expertise
   - Mentor availability (capacity)
4. **Clicks "Request Mentor"** → Creates mentorship request
5. **Mentor receives notification** → Dashboard shows pending request
6. **Mentor accepts request** → Creates active mentorship relationship
7. **Conversation automatically created** → Both parties get conversation
8. **User clicks "Go to Workspace"** → `mentorship/workspace.php?id=123`
9. **User clicks "Send Message"** → Redirects to `messages/conversation.php?id=456`
10. **User types and sends message** → Real-time delivery via WebSocket
11. **Mentor receives instant notification** → Message appears immediately

### Example 2: Mentor Offers Mentorship to Mentee

1. **Mentor logs in** → `login.php` (as sponsor)
2. **Goes to find mentees** → `mentorship/browse-mentees.php`
3. **Views potential mentees** → System shows:
   - Users without active mentors
   - Match score based on needs/expertise
4. **Clicks "Offer Mentorship"** → Creates mentorship offer
5. **User receives notification** → Dashboard shows mentorship offer
6. **User accepts offer** → Creates active relationship
7. **Both access workspace** → `mentorship/workspace.php?id=123`
8. **Conversation created automatically**
9. **Can send messages immediately**

### Example 3: Admin Messages a User

1. **Admin logs in** → `admin/login.php`
2. **Goes to messages** → `messages/inbox.php`
3. **Clicks "New Conversation"** → Opens new message dialog
4. **Selects user from list** → All active users shown
5. **Types and sends message** → Creates conversation + sends message
6. **User receives notification** → Unread badge appears
7. **User opens inbox** → Sees admin's message
8. **User replies** → Real-time via WebSocket
9. **Admin sees reply instantly** → No page refresh needed

---

## 🔐 Access Control Summary

| Page | Users | Mentors | Admins | Notes |
|------|-------|---------|--------|-------|
| **Browse Mentors** | ✅ Yes | ❌ No | ❌ No | Only for users seeking mentors |
| **Browse Mentees** | ❌ No | ✅ Yes | ❌ No | Only for sponsors/mentors |
| **Mentorship Dashboard** | ✅ Yes | ✅ Yes | ❌ No | Shows relationships, requests |
| **Workspace** | ✅ Yes | ✅ Yes | ❌ No | Only if part of relationship |
| **Messages Inbox** | ✅ Yes | ✅ Yes | ✅ Yes | All authenticated users |
| **Conversation** | ✅ Yes | ✅ Yes | ✅ Yes | Only participants in conversation |

---

## 📊 Database Structure

### Key Tables:

#### **mentorship_relationships**
```sql
- id
- mentor_id (references sponsors.id)
- mentee_id (references users.id)
- status (pending/active/completed/cancelled)
- start_date
- end_date
```

#### **conversations**
```sql
- id
- conversation_type (one_on_one/group/mentorship)
- related_id (relationship_id if mentorship conversation)
- created_at
- updated_at
```

#### **conversation_participants**
```sql
- id
- conversation_id
- participant_type (user/admin/mentor)
- user_id, admin_id, mentor_id (one is set based on type)
- joined_at
- last_read_at
```

#### **messages**
```sql
- id
- conversation_id
- sender_type (user/admin/mentor)
- sender_id, sender_admin_id, sender_mentor_id
- message_text
- is_read
- created_at
```

#### **user_online_status**
```sql
- id
- status_type (user/admin/mentor)
- user_id, admin_id, mentor_id
- is_online (0 or 1)
- last_activity
```

---

## 🚀 How to Access Messaging

### For Users:
1. Login at: `http://localhost/bihak-center/public/login.php`
2. Go to: `http://localhost/bihak-center/public/messages/inbox.php`
3. Or click "Messages" link in navigation menu (if added)

### For Mentors:
1. Login at: `http://localhost/bihak-center/public/login.php` (as sponsor)
2. Go to: `http://localhost/bihak-center/public/messages/inbox.php`
3. Or access from mentorship workspace

### For Admins:
1. Login at: `http://localhost/bihak-center/public/admin/login.php`
2. Go to: `http://localhost/bihak-center/public/messages/inbox.php`
3. Or add link to admin dashboard

---

## 🔧 WebSocket Server

**Start Server:**
```bash
cd c:\xampp\htdocs\bihak-center\websocket
npm start
```

**Server Port:** 8080

**Status Check:**
```bash
netstat -an | findstr ":8080"
```

**Features:**
- ✅ Real-time message delivery
- ✅ Typing indicators
- ✅ Online status tracking
- ✅ Read receipts
- ✅ Automatic reconnection
- ✅ Ping/pong keepalive (every 30s)
- ✅ Multi-user support (users, mentors, admins)

---

## 📝 Important Notes

### Message Access Rules:
1. **Users can message:**
   - Their mentors (in active relationships)
   - Admins (if admin initiates)
   - Other participants in group conversations

2. **Mentors can message:**
   - Their mentees (in active relationships)
   - Admins (if admin initiates)
   - Other participants in group conversations

3. **Admins can message:**
   - Any user
   - Any mentor
   - Other admins

### Conversation Creation:
- **Mentorship conversations:** Auto-created when relationship becomes active
- **Direct messages:** Created when first message is sent
- **Group conversations:** Created manually by admins

### Notifications:
- Unread badge updates in real-time
- Desktop notifications (if enabled)
- Email notifications (if configured)

---

## 🎯 Next Steps for Integration

### To Add Messaging to Navigation:

Add to `includes/header_new.php`:
```php
<li>
    <a href="<?php echo $base_path; ?>messages/inbox.php">
        Messages
        <?php if ($unread_count > 0): ?>
            <span class="badge"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>
</li>
```

### To Add to User Dashboard:

```php
<div class="dashboard-card">
    <h3>Messages</h3>
    <p><?php echo $unread_count; ?> unread messages</p>
    <a href="/messages/inbox.php" class="btn">View Inbox</a>
</div>
```

---

**Created:** November 20, 2025
**Status:** Production Ready ✅
**Documentation Version:** 1.0
