# Mentorship & Messaging System - Database Design

**Date:** November 20, 2025
**Status:** DATABASE SCHEMA IMPLEMENTED ✅ - READY FOR BACKEND DEVELOPMENT

**Latest Update:** All database tables successfully created. Using existing `sponsors` table for mentors (people who registered through "Get Involved" flow with `role_type` IN ('mentor','sponsor','partner')).

---

## Overview

This document describes the database schema and implementation plan for two integrated features:
1. **Mentor-Mentee Relationship System** - Matching mentors with mentees, tracking goals
2. **Internal Messaging System** - Real-time communication platform

### Tables Created (14 tables)

**Mentorship System (5 tables):**
- `mentorship_relationships` - Tracks mentor-mentee pairs
- `mentorship_goals` - Goals set within relationships
- `mentorship_activities` - Activity log/notes
- `mentor_preferences` - Matching preferences for mentors
- `mentee_needs` - What mentees need help with

**Messaging System (9 tables):**
- `conversations` - Chat containers (direct, team, broadcast, exercise)
- `conversation_participants` - Who's in each conversation
- `messages` - Actual messages
- `message_read_receipts` - Who read which message
- `typing_indicators` - Real-time "X is typing..."
- `user_online_status` - Online/offline status
- `notifications` - In-app and push notifications

---

## Table of Contents

1. [Mentor-Mentee System](#mentor-mentee-system)
2. [Messaging System](#messaging-system)
3. [Integration Points](#integration-points)
4. [Implementation Plan](#implementation-plan)
5. [API Endpoints](#api-endpoints)
6. [Security Considerations](#security-considerations)

---

## Mentor-Mentee System

### Core Concept

- **Mentors:** People registered through "Get Involved" flow (volunteers, partners, donors) - uses existing `get_involved` table
- **Mentees:** All registered users - uses existing `users` table
- **Matching:** Bidirectional (mentors browse mentees, mentees request mentors) with algorithm suggestions
- **Relationships:** One mentee can have ONE active mentor, one mentor can have MULTIPLE mentees

### Database Tables

#### 1. mentorship_relationships

Tracks all mentor-mentee relationships.

**Key Fields:**
- `mentor_id` → FK to `get_involved.id`
- `mentee_id` → FK to `users.id`
- `status` → `pending`, `active`, `ended`, `rejected`
- `requested_by` → `mentor` or `mentee` (who initiated)
- `match_score` → Algorithm confidence (0-100)
- `end_reason` → Mandatory when ending relationship

**Business Rules:**
- UNIQUE constraint on `(mentor_id, mentee_id, status='active')` - prevents duplicate active relationships
- When mentee accepts: `status='pending'` → `status='active'`
- When relationship ends: both parties must provide reason
- Only ONE active relationship per mentee (enforced in application logic)

**Workflow:**
```
Mentor browses profiles → Requests mentorship → status='pending' (requested_by='mentor')
Mentee receives notification → Accepts/Rejects
  - Accept: status='active', accepted_at=NOW()
  - Reject: status='rejected'

OR

Mentee browses mentors → Requests mentor → status='pending' (requested_by='mentee')
Mentor receives notification → Accepts/Rejects
```

#### 2. mentorship_goals

Goals and milestones set within the relationship.

**Key Fields:**
- `relationship_id` → FK to `mentorship_relationships.id`
- `title`, `description` → What to achieve
- `target_date` → Deadline
- `status` → `not_started`, `in_progress`, `completed`, `cancelled`
- `priority` → `low`, `medium`, `high`
- `created_by` → `mentor` or `mentee`

**Use Case:**
```
Mentor and mentee set goals together:
- "Complete business plan by end of month"
- "Learn Python basics"
- "Prepare pitch deck for investors"

Track progress:
- Mark as in_progress when working on it
- Mark as completed when done
- View all goals in workspace
```

#### 3. mentorship_activities

Activity log / notes / meeting summaries.

**Key Fields:**
- `relationship_id` → FK to relationship
- `goal_id` → Optional link to specific goal
- `activity_type` → `meeting`, `note`, `milestone`, `resource`, `other`
- `title`, `description` → Activity details
- `created_by` → `mentor` or `mentee`
- `activity_date` → When it happened

**Use Case:**
```
Log mentorship activities:
- "Had 1-hour video call to discuss marketing strategy"
- "Shared resource: Marketing 101 course"
- "Milestone: Completed first customer interview"
```

#### 4. mentor_preferences

Mentor's preferences for matching algorithm.

**Key Fields:**
- `mentor_id` → FK to `get_involved.id`
- `preferred_sectors` → JSON: `["technology", "agriculture", "education"]`
- `preferred_skills` → JSON: `["business planning", "marketing", "fundraising"]`
- `max_mentees` → Maximum active mentees (default: 3)
- `availability_hours` → Hours per month
- `preferred_languages` → JSON: `["en", "fr"]`

**Matching Logic:**
- Compare mentor's `preferred_sectors` with mentee's `needed_sectors`
- Compare mentor's `preferred_skills` with mentee's `needed_skills`
- Check if mentor has capacity (active mentees < max_mentees)
- Match languages
- Calculate score (0-100)

#### 5. mentee_needs

Mentee's needs and goals for matching.

**Key Fields:**
- `mentee_id` → FK to `users.id`
- `needed_sectors` → JSON: `["technology", "social entrepreneurship"]`
- `needed_skills` → JSON: `["pitching", "financial planning"]`
- `goals` → Free text: "I want to launch my startup within 6 months"
- `preferred_languages` → JSON: `["en", "fr"]`

### Matching Algorithm

**Score Calculation (0-100):**

```php
function calculateMatchScore($mentor_prefs, $mentee_needs) {
    $score = 0;

    // Sector match (40 points max)
    $sector_match = count(array_intersect($mentor_prefs['sectors'], $mentee_needs['sectors']));
    $score += min($sector_match * 20, 40);

    // Skills match (40 points max)
    $skills_match = count(array_intersect($mentor_prefs['skills'], $mentee_needs['skills']));
    $score += min($skills_match * 20, 40);

    // Language match (20 points max)
    $lang_match = count(array_intersect($mentor_prefs['languages'], $mentee_needs['languages']));
    $score += min($lang_match * 10, 20);

    return min($score, 100);
}
```

**Suggestions:**
- Show top 10 matches to mentors (sorted by score DESC)
- Show top 10 mentors to mentees (sorted by score DESC)
- Highlight shared sectors/skills in UI

---

## Messaging System

### Core Concept

Real-time messaging system like Slack/WhatsApp with:
- One-on-one direct messages
- Team group chats
- Broadcast messages (admin → all)
- Exercise-specific conversations
- Read receipts, typing indicators, online status
- In-app and push notifications

### Database Tables

#### 1. conversations

Container for messages.

**Key Fields:**
- `conversation_type`:
  - `direct` → One-on-one (user↔user, user↔admin, mentor↔mentee, etc.)
  - `team` → Team group chat (all team members)
  - `broadcast` → Admin announcement to all teams
  - `exercise` → Exercise-specific feedback thread
- `name` → Display name for group conversations
- `team_id` → FK if team conversation
- `exercise_id` → FK if exercise-specific
- `submission_id` → FK if submission feedback
- `last_message_at` → For sorting conversations by recent activity

**Examples:**
```sql
-- Direct message between user and admin
INSERT INTO conversations (conversation_type, created_by)
VALUES ('direct', 123);

-- Team group chat
INSERT INTO conversations (conversation_type, name, team_id, created_by)
VALUES ('team', 'Team Bihak Chat', 1, 456);

-- Exercise feedback thread
INSERT INTO conversations (conversation_type, name, exercise_id, submission_id, created_by)
VALUES ('exercise', 'Exercise 1.2 Feedback', 2, 789, 1);
```

#### 2. conversation_participants

Who is in each conversation.

**Key Fields:**
- `conversation_id` → FK to `conversations.id`
- `user_id`, `admin_id`, `mentor_id` → One must be set
- `participant_type` → `user`, `admin`, `mentor`
- `last_read_at` → Last time they read messages (for unread badges)
- `is_active` → Can leave group conversations

**Business Rules:**
- Direct conversations: exactly 2 participants
- Team conversations: all team members + can invite admins
- Broadcast: admin creates, all teams auto-joined
- Exercise conversations: team members + reviewing admin

**Unread Count:**
```sql
-- Count unread messages for a user
SELECT COUNT(*)
FROM messages m
WHERE m.conversation_id IN (
    SELECT conversation_id FROM conversation_participants
    WHERE user_id = ? AND participant_type = 'user'
)
AND m.created_at > COALESCE(
    (SELECT last_read_at FROM conversation_participants
     WHERE conversation_id = m.conversation_id
     AND user_id = ? AND participant_type = 'user'),
    '1970-01-01'
)
AND m.sender_id != ?;
```

#### 3. messages

Actual message content.

**Key Fields:**
- `conversation_id` → FK to conversation
- `sender_id`, `sender_admin_id`, `sender_mentor_id` → One must be set
- `sender_type` → `user`, `admin`, `mentor`
- `message_text` → The message content
- `parent_message_id` → For threaded replies
- `is_edited`, `edited_at` → Edit tracking
- `is_deleted`, `deleted_at`, `deleted_by` → Soft delete

**Features:**
- **Threading:** Reply to specific message using `parent_message_id`
- **Edit:** Update `message_text`, set `is_edited=1`, `edited_at=NOW()`
- **Delete:** Soft delete: `is_deleted=1`, show "[Message deleted]" in UI
- **Search:** Full-text search on `message_text`

#### 4. message_read_receipts

Track who read which message.

**Key Fields:**
- `message_id` → FK to message
- `user_id`, `admin_id`, `mentor_id` → Who read it
- `reader_type` → Type of reader
- `read_at` → When they read it

**Display:**
```
Your message:
  "Hello, how's the business plan going?"
  ✓✓ Read by Alice (2 hours ago)
  ✓✓ Read by Bob (5 minutes ago)
```

#### 5. typing_indicators

Show "X is typing..." in real-time.

**Key Fields:**
- `conversation_id` → Where they're typing
- `user_id`, `admin_id`, `mentor_id` → Who is typing
- `started_at` → When they started

**Mechanism:**
- When user starts typing: `INSERT INTO typing_indicators ...`
- WebSocket broadcasts to conversation participants
- Auto-delete after 30 seconds (cleanup via cron)
- Delete when message sent or user stops typing

#### 6. user_online_status

Track who is online.

**Key Fields:**
- `user_id`, `admin_id`, `mentor_id`
- `status_type` → Type
- `is_online` → TRUE if online
- `last_activity` → Auto-updated on any action

**Display:**
```
👤 Alice Johnson  🟢 Online
👤 Bob Smith     ⚫ Last seen 2 hours ago
```

**Mechanism:**
- Update `last_activity` on every API call
- Cron job: Mark offline if `last_activity < NOW() - 5 minutes`

#### 7. notifications

In-app and push notifications.

**Key Fields:**
- `recipient_type`, `user_id`, `admin_id`, `mentor_id` → Who receives it
- `notification_type`:
  - `message` → New message
  - `mention` → @mentioned in message
  - `mentorship_request` → Someone wants mentorship
  - `mentorship_accepted` → Request accepted
  - `mentorship_ended` → Relationship ended
  - `goal_completed` → Goal marked complete
  - `exercise_feedback` → Admin commented on submission
- `title`, `message` → Notification content
- `link_url` → Where to navigate when clicked
- `is_read` → For badge count
- `is_pushed` → Push notification sent

**Badge Count:**
```sql
SELECT COUNT(*) FROM notifications
WHERE user_id = ?
AND recipient_type = 'user'
AND is_read = 0;
```

---

## Integration Points

### 1. Messaging ↔ Mentorship

**Automatic Conversation Creation:**
```sql
-- When mentorship becomes active, create direct conversation
INSERT INTO conversations (conversation_type, created_by)
VALUES ('direct', mentor_id);

INSERT INTO conversation_participants (conversation_id, mentor_id, participant_type)
VALUES (LAST_INSERT_ID(), mentor_id, 'mentor');

INSERT INTO conversation_participants (conversation_id, user_id, participant_type)
VALUES (LAST_INSERT_ID(), mentee_id, 'user');
```

**Notifications:**
- New mentorship request → Notification
- Mentorship accepted → Notification + conversation created
- New goal added → Notification
- Goal completed → Notification to both parties

### 2. Messaging ↔ Incubation

**Automatic Team Conversations:**
```sql
-- When team is created, create team group chat
INSERT INTO conversations (conversation_type, name, team_id, created_by)
VALUES ('team', CONCAT('Team ', team_name, ' Chat'), team_id, leader_id);

-- Add all team members as participants
INSERT INTO conversation_participants (conversation_id, user_id, participant_type)
SELECT LAST_INSERT_ID(), user_id, 'user'
FROM incubation_team_members
WHERE team_id = ?;
```

**Exercise Feedback Conversations:**
```sql
-- When admin reviews submission, create feedback thread
INSERT INTO conversations (conversation_type, name, exercise_id, submission_id, created_by)
VALUES ('exercise', CONCAT('Exercise ', exercise_number, ' Feedback'), exercise_id, submission_id, admin_id);

-- Add admin and all team members
```

**Admin Review Page Integration:**
- "Message Team" button on review page
- Opens conversation linked to that submission
- Team sees context: "Admin commented on Exercise 1.2 submission"

### 3. Profiles ↔ Mentorship

**Stories Page Integration:**
- Each user profile card shows "Request Mentorship" button
- Mentors see "Offer Mentorship" button on profiles
- Display match score: "85% match based on your profile"

---

## Implementation Plan

### Phase 1: Database Setup ✅ COMPLETED
- [x] Design schema
- [x] Run migration script (mentorship_messaging_schema.sql)
- [x] Verify table creation (14 tables created successfully)
- [x] Update documentation with implementation status
- [ ] Add sample data for testing (optional - can be done during frontend testing)

### Phase 2: Mentorship Backend
1. **Matching API:**
   - `GET /api/mentorship/suggestions` → Get suggested mentors/mentees
   - `POST /api/mentorship/request` → Request mentorship
   - `POST /api/mentorship/respond` → Accept/reject request
   - `POST /api/mentorship/end` → End relationship

2. **Goals API:**
   - `GET /api/mentorship/goals/:relationship_id` → List goals
   - `POST /api/mentorship/goals` → Create goal
   - `PUT /api/mentorship/goals/:id` → Update goal status
   - `DELETE /api/mentorship/goals/:id` → Delete goal

3. **Activities API:**
   - `GET /api/mentorship/activities/:relationship_id` → List activities
   - `POST /api/mentorship/activities` → Log activity

### Phase 3: Mentorship Frontend
1. **Mentor Dashboard:**
   - Browse mentee profiles with match scores
   - View active mentorships
   - Manage requests
   - Workspace for each mentorship

2. **Mentee Dashboard:**
   - Browse mentors with match scores
   - Request mentorship
   - View active mentorship
   - Workspace with goals

3. **Workspace:**
   - Goals list with progress
   - Activity timeline
   - Meeting scheduler
   - Resource sharing

### Phase 4: Messaging Backend
1. **WebSocket Server (Node.js + Socket.IO):**
   - Real-time message delivery
   - Typing indicators broadcast
   - Online status updates
   - Push notification triggers

2. **REST API:**
   - `GET /api/conversations` → List user's conversations
   - `GET /api/conversations/:id/messages` → Load messages
   - `POST /api/conversations/:id/messages` → Send message
   - `PUT /api/messages/:id` → Edit message
   - `DELETE /api/messages/:id` → Delete message
   - `POST /api/conversations/:id/read` → Mark as read
   - `POST /api/typing` → Update typing indicator

### Phase 5: Messaging Frontend
1. **Chat Interface:**
   - Conversation list (sidebar)
   - Message thread (main)
   - Compose box
   - Unread badges
   - Online indicators

2. **Features:**
   - Real-time message updates (WebSocket)
   - Typing indicators
   - Read receipts
   - Search messages
   - Edit/delete messages
   - @mentions

### Phase 6: Integration & Testing
1. **Incubation Integration:**
   - Add "Message" buttons to team pages
   - Add "Message" button to admin review pages
   - Create exercise feedback threads automatically

2. **Profile Integration:**
   - Add mentorship request buttons to stories page
   - Show mentor-mentee relationships on profiles

3. **Testing:**
   - Test all user flows
   - Load testing (100 concurrent users)
   - WebSocket stability testing
   - Notification delivery testing

---

## API Endpoints

### Mentorship API

#### GET /api/mentorship/suggestions
**Purpose:** Get suggested matches
**Auth:** Required
**Query Params:**
- `as` → `mentor` or `mentee`
- `limit` → Number of results (default: 10)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "John Doe",
      "profile_picture": "...",
      "sectors": ["technology", "education"],
      "skills": ["business planning", "marketing"],
      "match_score": 85,
      "bio": "Experienced entrepreneur...",
      "active_mentees": 2,
      "max_mentees": 5
    }
  ]
}
```

#### POST /api/mentorship/request
**Purpose:** Request mentorship
**Auth:** Required
**Body:**
```json
{
  "mentor_id": 123,  // if requesting as mentee
  "mentee_id": 456   // if requesting as mentor
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "relationship_id": 789,
    "status": "pending"
  }
}
```

#### POST /api/mentorship/respond
**Purpose:** Accept/reject mentorship request
**Auth:** Required
**Body:**
```json
{
  "relationship_id": 789,
  "action": "accept"  // or "reject"
}
```

#### POST /api/mentorship/end
**Purpose:** End active relationship
**Auth:** Required
**Body:**
```json
{
  "relationship_id": 789,
  "reason": "Goals achieved, mentee is ready to move forward independently"
}
```

### Messaging API

#### GET /api/conversations
**Purpose:** List user's conversations
**Auth:** Required
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "direct",
      "name": null,
      "participants": [
        {"id": 123, "name": "Alice", "type": "user", "online": true}
      ],
      "last_message": {
        "text": "Thanks for the feedback!",
        "sender": "Alice",
        "created_at": "2025-11-20 14:30:00"
      },
      "unread_count": 3,
      "updated_at": "2025-11-20 14:30:00"
    }
  ]
}
```

#### GET /api/conversations/:id/messages
**Purpose:** Load messages in conversation
**Auth:** Required
**Query Params:**
- `limit` → Number of messages (default: 50)
- `before` → Load messages before this ID (pagination)

**Response:**
```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 456,
        "sender": {"id": 123, "name": "Alice", "type": "user"},
        "text": "Hello, I need help with exercise 2",
        "created_at": "2025-11-20 14:00:00",
        "is_edited": false,
        "read_by": [
          {"id": 1, "name": "Admin", "type": "admin", "read_at": "2025-11-20 14:01:00"}
        ]
      }
    ],
    "has_more": false
  }
}
```

#### POST /api/conversations/:id/messages
**Purpose:** Send message
**Auth:** Required
**Body:**
```json
{
  "message": "This is my message",
  "parent_message_id": 123  // optional, for threaded replies
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 789,
    "created_at": "2025-11-20 14:35:00"
  }
}
```

---

## Security Considerations

### Authentication & Authorization

**Mentorship:**
- Users can only view their own active mentorships
- Mentors can only accept requests if they have capacity
- Mentees can only have ONE active mentor
- Both parties required to end relationship

**Messaging:**
- Users can only access conversations they're participants of
- Admins have access to all team/exercise conversations
- Can't send messages to conversations you're not in
- Soft delete (messages remain in database)

### Data Privacy

**Profile Visibility:**
- Mentee profiles visible to all potential mentors (per stories page)
- Mentor profiles visible to all users
- No sensitive data exposed in matching

**Message Privacy:**
- Messages only visible to conversation participants
- Deleted messages show "[Message deleted]", original text retained for audit
- Read receipts can be disabled (future enhancement)

### Rate Limiting

- Max 100 messages per user per minute
- Max 10 mentorship requests per user per day
- WebSocket connection limit: 5 per user

### SQL Injection Prevention

- All queries use prepared statements
- Parameter binding for all inputs
- No direct SQL concatenation

---

## WebSocket Events

### Client → Server

```javascript
// Connect
socket.emit('authenticate', { token: 'JWT_TOKEN' });

// Send message
socket.emit('send_message', {
  conversation_id: 123,
  message: 'Hello'
});

// Typing indicator
socket.emit('typing_start', { conversation_id: 123 });
socket.emit('typing_stop', { conversation_id: 123 });

// Mark as read
socket.emit('mark_read', { conversation_id: 123 });
```

### Server → Client

```javascript
// New message
socket.on('new_message', (data) => {
  // data = { conversation_id, message, sender }
});

// Typing indicator
socket.on('user_typing', (data) => {
  // data = { conversation_id, user }
});

// User online/offline
socket.on('user_status', (data) => {
  // data = { user_id, is_online }
});

// New notification
socket.on('notification', (data) => {
  // data = { title, message, link }
});
```

---

## Performance Optimization

### Database Indexes

✅ Already included in schema:
- `idx_conversation` on messages for fast conversation loading
- `idx_recipient_user` on notifications for fast badge count
- `idx_online` on user_online_status for fast status checks
- `idx_relationship` on mentorship tables

### Caching Strategy

**Redis Cache:**
- Online users list (TTL: 5 minutes)
- Unread counts (invalidate on new message)
- Active conversations list (TTL: 10 minutes)
- Typing indicators (TTL: 30 seconds)

### Query Optimization

**Load messages:**
```sql
-- Use pagination with LIMIT + OFFSET
SELECT * FROM messages
WHERE conversation_id = ?
AND is_deleted = 0
ORDER BY created_at DESC
LIMIT 50 OFFSET 0;
```

**Count unread:**
```sql
-- Use covering index
SELECT COUNT(*) FROM messages m
INNER JOIN conversation_participants cp
  ON m.conversation_id = cp.conversation_id
WHERE cp.user_id = ?
AND m.created_at > COALESCE(cp.last_read_at, '1970-01-01')
AND m.sender_id != ?;
```

---

## Deployment Checklist

### Database
- [ ] Run migration script on production
- [ ] Verify all tables created
- [ ] Verify foreign keys intact
- [ ] Add sample data for testing
- [ ] Set up automated backups

### Backend
- [ ] Deploy WebSocket server (Node.js)
- [ ] Configure SSL for WebSocket (wss://)
- [ ] Set up Redis for caching
- [ ] Deploy REST API endpoints
- [ ] Configure CORS properly
- [ ] Set up monitoring (errors, WebSocket connections)

### Frontend
- [ ] Build messaging UI components
- [ ] Integrate WebSocket client
- [ ] Add notification permission request
- [ ] Test on mobile devices
- [ ] Cross-browser testing

### Integration
- [ ] Add "Message" buttons to incubation pages
- [ ] Add mentorship buttons to stories page
- [ ] Test all user flows
- [ ] Load testing (100 concurrent users)

---

**Status:** Schema complete, ready to implement Phase 1
**Next Step:** Run migration script to create tables
