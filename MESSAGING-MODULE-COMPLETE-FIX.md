# Complete Messaging Module Database Column Fixes

## Summary
Fixed **all SQL query errors** across the entire messaging system - PHP backend, WebSocket server, and chat widget frontend. All column names now match the actual database schema.

## Database Schema Reference

### Messages Table (`messages`)
**Actual columns:**
- `sender_id` - User ID (when sender_type = 'user')
- `sender_admin_id` - Admin ID (when sender_type = 'admin')
- `sender_mentor_id` - Mentor/Sponsor ID (when sender_type = 'mentor')
- `sender_type` - ENUM('user', 'admin', 'mentor')
- `message_text` - The actual message content
- `parent_message_id` - For replies/threading

**Wrong columns that were being used:**
- ❌ `user_id`, `admin_id`, `mentor_id` (these don't exist in messages table!)
- ❌ `content` (should be `message_text`)
- ❌ `reply_to_message_id` (should be `parent_message_id`)

### User Tables
- `users.full_name` (not `name`)
- `admins.full_name` (not `name`)
- `sponsors.full_name` (not `name`)

### Conversations Table
- `conversations.name` (not `title`)

---

## Files Fixed

### 1. MessagingManager.php (c:\xampp\htdocs\bihak-center\includes\MessagingManager.php)

#### Fix 1: sendMessage() - Line 355-366
**Problem:** INSERT used wrong column names
```php
// BEFORE (WRONG):
INSERT INTO messages
(conversation_id, sender_type, user_id, admin_id, mentor_id, content, reply_to_message_id, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, NOW())

// AFTER (CORRECT):
INSERT INTO messages
(conversation_id, sender_type, sender_id, sender_admin_id, sender_mentor_id, message_text, parent_message_id, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
```

#### Fix 2: getMessages() - Line 415-417
**Problem:** LEFT JOINs used wrong column names
```php
// BEFORE (WRONG):
LEFT JOIN users u ON u.id = m.user_id
LEFT JOIN admins a ON a.id = m.admin_id
LEFT JOIN sponsors s ON s.id = m.mentor_id

// AFTER (CORRECT):
LEFT JOIN users u ON u.id = m.sender_id AND m.sender_type = 'user'
LEFT JOIN admins a ON a.id = m.sender_admin_id AND m.sender_type = 'admin'
LEFT JOIN sponsors s ON s.id = m.sender_mentor_id AND m.sender_type = 'mentor'
```

#### Fix 3: editMessage() - Line 476-487
**Problem:** UPDATE used wrong column name
```php
// BEFORE (WRONG):
UPDATE messages SET content = ?, edited_at = NOW()

// AFTER (CORRECT):
UPDATE messages SET message_text = ?, edited_at = NOW()
```

#### Fix 4: searchMessages() - Line 553-578
**Problem:** Multiple wrong column names
```php
// FIXED:
// - c.title → c.name
// - m.user_id → m.sender_id (with proper JOIN conditions)
// - m.admin_id → m.sender_admin_id (with proper JOIN conditions)
// - m.mentor_id → m.sender_mentor_id (with proper JOIN conditions)
// - m.content → m.message_text
```

#### Fix 5: markMessagesAsRead() - Line 599-604
**Problem:** WHERE clause used wrong column names
```php
// BEFORE (WRONG):
AND NOT (m.sender_type = ? AND m.user_id <=> ? AND m.admin_id <=> ? AND m.mentor_id <=> ?)

// AFTER (CORRECT):
AND NOT (m.sender_type = ? AND m.sender_id <=> ? AND m.sender_admin_id <=> ? AND m.sender_mentor_id <=> ?)
```

#### Fix 6: getUnreadMessageCount() - Line 649-658
**Problem:** Same WHERE clause issue as markMessagesAsRead()
```php
// FIXED: Changed m.user_id/admin_id/mentor_id to m.sender_id/sender_admin_id/sender_mentor_id
```

#### Fix 7: getConversationParticipants() - Line 268-269
**Problem:** CASE statement used wrong column names
```php
// BEFORE (WRONG):
WHEN cp.participant_type = 'admin' THEN a.name
WHEN cp.participant_type = 'mentor' THEN s.name

// AFTER (CORRECT):
WHEN cp.participant_type = 'admin' THEN a.full_name
WHEN cp.participant_type = 'mentor' THEN s.full_name
```

### 2. WebSocket Server (c:\xampp\htdocs\bihak-center\websocket\server.js)

#### Fix: Message INSERT - Line 249-253
**Problem:** INSERT used wrong column names
```javascript
// BEFORE (WRONG):
INSERT INTO messages
(conversation_id, sender_type, user_id, admin_id, mentor_id, content, reply_to_message_id, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, NOW())

// AFTER (CORRECT):
INSERT INTO messages
(conversation_id, sender_type, sender_id, sender_admin_id, sender_mentor_id, message_text, parent_message_id, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
```

### 3. Chat Widget (c:\xampp\htdocs\bihak-center\includes\chat_widget.php)

#### Fix 1: HTTP Request - Line 1015
**Problem:** POST request sent wrong field name
```javascript
// BEFORE (WRONG):
body: JSON.stringify({
    conversation_id: chatWidget.activeConversationId,
    message: message
})

// AFTER (CORRECT):
body: JSON.stringify({
    conversation_id: chatWidget.activeConversationId,
    content: message
})
```

#### Fix 2: WebSocket Message - Line 1002-1008
**Problem:** WebSocket message had wrong type and field names
```javascript
// BEFORE (WRONG):
chatWidget.ws.send(JSON.stringify({
    type: 'send_message',
    conversation_id: chatWidget.activeConversationId,
    message: message,
    timestamp: new Date().toISOString()
}));

// AFTER (CORRECT):
chatWidget.ws.send(JSON.stringify({
    type: 'message',
    conversation_id: chatWidget.activeConversationId,
    content: message,
    reply_to_id: null,
    timestamp: new Date().toISOString()
}));
```

### 4. Search Users API (c:\xampp\htdocs\bihak-center\api\messaging\search_users.php)

#### Fix: Column Names Throughout
**Problem:** All queries used `name` instead of `full_name`
```php
// FIXED: Changed all a.name → a.full_name, s.name → s.full_name
```

### 5. Created Missing File
**File:** `c:\xampp\htdocs\bihak-center\api\messaging\mark_read.php`
**Purpose:** API endpoint for marking messages as read (was being called but didn't exist)

---

## How to Test - IMPORTANT STEPS

### Step 1: Restart WebSocket Server (REQUIRED!)
The WebSocket server needs to be restarted to load the new code with correct column names:

```bash
# Find and kill existing Node.js processes
tasklist | findstr "node.exe"
# Note the PID numbers and kill them:
taskkill /F /PID <pid_number>

# Navigate to websocket directory
cd c:\xampp\htdocs\bihak-center\websocket

# Start the server fresh
node server.js
```

**Or simply close the terminal window running the WebSocket server and start it again.**

### Step 2: Clear Browser Cache
Press **Ctrl + Shift + Delete** and clear:
- Cached images and files
- Or just do a **Hard Refresh**: Ctrl + F5

### Step 3: Test Message Flow

1. **Open browser** and navigate to `http://localhost/bihak-center/public/my-account.php`
2. **Check browser console** for any errors (F12 → Console tab)
3. **Click the chat widget icon** (Messages button)
4. **Click on an existing conversation** OR **click "+" to start new conversation**
5. **Type a message** and press Enter or click Send
6. **Verify the message appears** in the chat window
7. **Check as admin** - login as admin and verify the message was received

### Step 4: Check Error Logs
```bash
# View latest errors (should be none!)
tail -20 "C:\xampp\apache\logs\error.log"
```

---

## What Should Work Now

✅ **Send messages via HTTP** (when WebSocket not connected)
✅ **Send messages via WebSocket** (real-time)
✅ **Receive messages** in real-time
✅ **Load conversation history** without SQL errors
✅ **View conversation participants** with correct names
✅ **Search for messages** by content
✅ **Edit messages** (if implemented in UI)
✅ **Mark messages as read**
✅ **Get unread message count**
✅ **Search for users** to start conversations

---

## Technical Details

### Why These Errors Occurred
The messaging system was initially developed with assumed column names that didn't match the actual database schema. The database uses:
- Separate columns for each sender type (`sender_id`, `sender_admin_id`, `sender_mentor_id`)
- `message_text` instead of generic `content`
- `parent_message_id` instead of `reply_to_message_id`

### Impact Before Fix
- Messages couldn't be sent (SQL errors)
- Conversation history wouldn't load
- WebSocket messages failed to save
- Message editing failed
- Read receipts didn't work
- Search functionality broken

### After Fix
All database operations use correct column names matching the actual schema.

---

## If Still Having Issues

1. **Check WebSocket server is running:**
   ```bash
   netstat -ano | findstr "8080"
   ```
   Should show LISTENING on port 8080

2. **Check Apache error log for NEW errors:**
   ```bash
   tail -30 "C:\xampp\apache\logs\error.log"
   ```
   Look for timestamps AFTER you made these changes

3. **Check browser console:**
   F12 → Console tab - look for JavaScript errors or failed API calls

4. **Test HTTP fallback directly:**
   ```bash
   curl -X POST http://localhost/bihak-center/api/messaging/messages.php \
     -H "Content-Type: application/json" \
     -d '{"conversation_id":1,"content":"Test message"}'
   ```

---

## Files Modified Summary

✅ `includes/MessagingManager.php` - 7 functions fixed
✅ `websocket/server.js` - 1 INSERT statement fixed
✅ `includes/chat_widget.php` - 2 fixes (HTTP + WebSocket)
✅ `api/messaging/search_users.php` - All queries fixed
✅ `api/messaging/mark_read.php` - **CREATED NEW FILE**

## Date Fixed
November 25, 2025
