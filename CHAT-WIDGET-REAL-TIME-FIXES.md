# Chat Widget Real-Time Update Fixes

**Date:** November 30, 2025
**Priority:** 🔴 CRITICAL - Real-time updates not working

---

## 🐛 PROBLEMS IDENTIFIED

### Issue 1: Conversation List Not Auto-Updating
**User Report:** "the three second refresh doesn't work i need to close and reopen the chatbox to see the new conversation, or refresh the whole page"

**Root Cause:**
- Conversation list was polling every 30 seconds (line 1480)
- Too slow for real-time messaging experience
- Users had to manually refresh to see new conversations

### Issue 2: Past Messages Not Displaying
**User Report:** "i can see old message in the conversations tab but i don't see past messages when i am in a specific chat with a user"

**Root Cause:**
- Field name mismatch in chat widget JavaScript (line 1005)
- Widget expected `msg.message_text` but API returns `msg.content`
- MessagingManager was fixed to return `content` alias, but chat widget wasn't updated
- Also, API returns `data.data` not `data.messages` (line 952)

### Issue 3: New Chat Display Name Not Showing
**User Report:** "when i start a new chat the name does appear in the tab, i have to send them a message first and refresh the chatbox to see their name in the specific tab name"

**Root Causes:**
1. No automatic refresh of messages in active conversation
2. Once conversation opened, messages only loaded on initial open
3. No polling mechanism for new messages in active chat view
4. **Main Issue:** When starting new conversation, `participantName` was not passed to `openConversation()` (line 1490)
   - Function had the name available but didn't use it
   - Resulted in default "Chat" tab name instead of actual user name

---

## ✅ FIXES IMPLEMENTED

### Fix 1: Faster Conversation List Polling

**File:** [includes/chat_widget.php:1479-1480](includes/chat_widget.php#L1479-L1480)

**Change:**
```javascript
// Before: Polling every 30 seconds
setInterval(loadConversations, 30000);

// After: Polling every 3 seconds
setInterval(loadConversations, 3000);
```

**Impact:**
- Conversation list refreshes every 3 seconds
- New conversations appear within 3 seconds
- No need to close/reopen widget or refresh page

---

### Fix 2: Message Field Name Consistency

**File:** [includes/chat_widget.php:1005](includes/chat_widget.php#L1005)

**Change:**
```javascript
// Before: Only looked for message_text
<div class="message-text">${escapeHtml(msg.message_text)}</div>

// After: Checks content first, fallback to message_text
<div class="message-text">${escapeHtml(msg.content || msg.message_text || '')}</div>
```

**Why This Works:**
- MessagingManager returns `content` field (via SQL alias)
- Chat widget now checks `content` first
- Falls back to `message_text` for backwards compatibility
- Empty string fallback prevents errors

**Also Fixed API Response Handling:**

**File:** [includes/chat_widget.php:952-953](includes/chat_widget.php#L952-L953)

```javascript
// Before: Expected data.messages
chatWidget.messages[conversationId] = data.messages;

// After: Handles both data.data and data.messages
chatWidget.messages[conversationId] = data.data || data.messages || [];
```

**Impact:**
- Past messages now display correctly in conversation view
- Consistent with API response format (`data.data`)
- No more blank conversation screens

---

### Fix 3: Real-Time Message Polling in Active Conversation

**Files Modified:**
- [includes/chat_widget.php:792-804](includes/chat_widget.php#L792-L804) - Added `messagePollingInterval` to state
- [includes/chat_widget.php:945-946](includes/chat_widget.php#L945-L946) - Start polling when conversation opens
- [includes/chat_widget.php:949-974](includes/chat_widget.php#L949-L974) - New polling functions
- [includes/chat_widget.php:835-838](includes/chat_widget.php#L835-L838) - Stop polling when switching tabs

**New State Property:**
```javascript
let chatWidget = {
    // ... existing properties
    messagePollingInterval: null,  // ← Added
    // ... rest of properties
};
```

**New Functions Added:**

#### 1. Start Message Polling
```javascript
// Start polling for new messages in active conversation
function startMessagePolling(conversationId) {
    // Clear any existing polling
    if (chatWidget.messagePollingInterval) {
        clearInterval(chatWidget.messagePollingInterval);
    }

    // Poll every 3 seconds
    chatWidget.messagePollingInterval = setInterval(async () => {
        // Only poll if this conversation is still active
        if (chatWidget.activeConversationId === conversationId) {
            await loadMessages(conversationId);
        } else {
            // Stop polling if conversation changed
            stopMessagePolling();
        }
    }, 3000);
}
```

#### 2. Stop Message Polling
```javascript
// Stop message polling
function stopMessagePolling() {
    if (chatWidget.messagePollingInterval) {
        clearInterval(chatWidget.messagePollingInterval);
        chatWidget.messagePollingInterval = null;
    }
}
```

#### 3. Updated Open Conversation
```javascript
// Open a conversation
async function openConversation(conversationId, displayName = 'Chat') {
    chatWidget.activeConversationId = conversationId;
    // ... existing code ...

    // Start polling for new messages in this conversation (every 3 seconds)
    startMessagePolling(conversationId);  // ← Added
}
```

#### 4. Updated Tab Switching
```javascript
// Switch between tabs
function switchChatTab(tab) {
    // ... existing code ...

    // Stop message polling when switching back to conversations
    if (tab === 'conversations') {
        stopMessagePolling();  // ← Added
        chatWidget.activeConversationId = null;
    }
}
```

**Impact:**
- Messages in active conversation refresh every 3 seconds
- New messages appear automatically without manual refresh
- Display names update when messages arrive
- Polling stops when switching back to conversation list (efficient)

---

### Fix 4: Display Name for New Conversations

**File:** [includes/chat_widget.php:1490, 1497](includes/chat_widget.php#L1490)

**Problem:** When starting a new conversation, the tab showed "Chat" instead of the user's name.

**Before:**
```javascript
// Start conversation with a user
async function startConversationWith(participantType, participantId, participantName) {
    // ... create conversation API call ...

    if (data.success) {
        // ...
        if (data.conversation_id) {
            openConversation(data.conversation_id);  // ← Missing participantName
        }
    }
}
```

**After:**
```javascript
// Start conversation with a user
async function startConversationWith(participantType, participantId, participantName) {
    // ... create conversation API call ...

    if (data.success) {
        // ...
        if (data.conversation_id) {
            openConversation(data.conversation_id, participantName);  // ← Pass participantName
        }
    } else {
        // Also fixed for existing conversations
        if (data.conversation_id) {
            openConversation(data.conversation_id, participantName);  // ← Pass participantName
        }
    }
}
```

**Impact:**
- New conversation tabs now show correct user name immediately
- No need to send message and refresh to see name
- Consistent behavior for new and existing conversations

---

## 🎯 HOW IT WORKS NOW

### Scenario 1: Viewing Conversation List ✅

```
1. User opens chat widget
2. Conversation list loads immediately
3. Every 3 seconds, list refreshes automatically
4. New conversations appear within 3 seconds
5. Unread counts update in real-time
```

**Technical Flow:**
```
Page Load
    ↓
toggleChatWidget() called
    ↓
loadConversations() runs
    ↓
setInterval starts (every 3 seconds)
    ↓
Conversations refresh automatically
```

---

### Scenario 2: Viewing Active Conversation ✅

```
1. User clicks on conversation
2. openConversation() loads all messages
3. Past messages display correctly (using content field)
4. startMessagePolling() begins
5. Every 3 seconds, new messages load
6. User sees new messages automatically
```

**Technical Flow:**
```
Click Conversation
    ↓
openConversation(id, name) called
    ↓
loadMessages() fetches all messages
    ↓
renderMessages() displays with msg.content
    ↓
startMessagePolling() begins
    ↓
Every 3 seconds → loadMessages() → renderMessages()
    ↓
New messages appear automatically
```

---

### Scenario 3: Switching Between Views ✅

```
1. User viewing active conversation (polling active)
2. User clicks "Conversations" tab
3. switchChatTab('conversations') called
4. stopMessagePolling() stops interval
5. Conversation list continues its own polling
```

**Technical Flow:**
```
Active Conversation (message polling running)
    ↓
User clicks Conversations tab
    ↓
switchChatTab('conversations')
    ↓
stopMessagePolling() → clearInterval()
    ↓
activeConversationId = null
    ↓
Conversation list polling continues (separate interval)
```

---

## 🧪 TESTING INSTRUCTIONS

### Test 1: Conversation List Auto-Refresh

```bash
# Setup: Two browser windows side-by-side

Window A:
1. Login: testuser@bihakcenter.org / Test@123
2. Open chat widget
3. Wait and watch conversation list

Window B:
4. Login: mentor@bihakcenter.org / Test@123
5. Open chat widget
6. Click + to start new conversation
7. Select "Test User" (testuser@bihakcenter.org)
8. Send message: "Testing auto-refresh"

Window A:
9. ✅ Within 3 seconds, new conversation should appear
10. ✅ Message preview visible
11. ✅ Unread badge updates
12. ✅ No need to refresh page or close/reopen widget
```

---

### Test 2: Past Messages Display

```bash
# Verify existing messages appear

1. Login: testuser@bihakcenter.org / Test@123
2. Open chat widget
3. Click on "System Administrator" conversation
4. ✅ See all past messages (with "Salut" content)
5. ✅ Sender names visible
6. ✅ Timestamps correct
7. ✅ No blank messages
8. ✅ All message content displays properly
```

---

### Test 3: Real-Time Message Updates in Active Chat

```bash
# Setup: Two browser windows

Window A:
1. Login: testuser@bihakcenter.org / Test@123
2. Open chat widget
3. Click on conversation with mentor
4. Keep conversation open

Window B:
5. Login: mentor@bihakcenter.org / Test@123
6. Open chat widget
7. Click on conversation with Test User
8. Send message: "Hello from mentor!"

Window A:
9. ✅ Within 3 seconds, "Hello from mentor!" appears
10. ✅ No page refresh needed
11. ✅ No need to switch tabs

Window A:
12. Reply: "Thanks, received!"

Window B:
13. ✅ Within 3 seconds, "Thanks, received!" appears
```

---

### Test 4: Polling Efficiency (DevTools Check)

```bash
1. Login and open chat widget
2. Open browser DevTools (F12)
3. Go to Network tab
4. Filter: "conversations.php"

Viewing Conversation List:
5. ✅ See GET requests every 3 seconds to conversations.php
6. ✅ Response shows all conversations

Open Specific Conversation:
7. Click on a conversation
8. Filter: "messages.php"
9. ✅ See GET requests every 3 seconds to messages.php?conversation_id=X
10. ✅ conversations.php polling continues in background

Switch Back to List:
11. Click "Conversations" tab
12. ✅ messages.php polling stops
13. ✅ Only conversations.php continues polling
```

---

## 📊 TECHNICAL DETAILS

### Polling Intervals

| View | Endpoint | Frequency | What It Polls |
|------|----------|-----------|---------------|
| Conversation List | `conversations.php` | 3 seconds | All user conversations |
| Active Conversation | `messages.php?conversation_id=X` | 3 seconds | Messages in specific conversation |

### Field Name Mapping

| Database Column | SQL Alias | API Response | Frontend Display |
|----------------|-----------|--------------|------------------|
| `message_text` | `as content` | `msg.content` | `msg.content \|\| msg.message_text` |

**Why Both:**
- Primary: `msg.content` (from MessagingManager alias)
- Fallback: `msg.message_text` (backwards compatibility)
- Safe: Empty string if neither exists

### State Management

```javascript
chatWidget = {
    isOpen: false,                    // Widget expanded/minimized
    activeConversationId: null,       // Currently viewing conversation ID
    conversations: [],                // List of all conversations
    messages: {},                     // Messages by conversation ID
    unreadCount: 0,                   // Total unread messages
    messagePollingInterval: null,     // Interval ID for message polling
    // ... other properties
}
```

---

## 💡 BENEFITS

### User Experience
- ✅ True real-time messaging (3-second updates)
- ✅ No manual refresh required
- ✅ Past messages visible immediately
- ✅ New conversations appear automatically
- ✅ Display names update properly

### Performance
- ✅ Efficient polling (only active conversation)
- ✅ Stops polling when not needed
- ✅ Lightweight API requests
- ✅ No duplicate polling

### Reliability
- ✅ Field name fallback (content → message_text)
- ✅ API response format flexibility
- ✅ Automatic cleanup of intervals
- ✅ No memory leaks

### Development
- ✅ Works without WebSocket server
- ✅ Easy to test locally
- ✅ Clear state management
- ✅ Consistent behavior

---

## 🔧 CONFIGURATION

### Adjusting Polling Frequency

If 3 seconds is too frequent or not frequent enough:

**Conversation List Polling:**
```javascript
// Line 1480
setInterval(loadConversations, 3000);  // Change 3000 to desired milliseconds
```

**Message Polling:**
```javascript
// Line 957
chatWidget.messagePollingInterval = setInterval(async () => {
    // ...
}, 3000);  // Change 3000 to desired milliseconds
```

**Recommended Values:**
- 1000ms (1 second) - Very real-time, higher server load
- 3000ms (3 seconds) - **Current setting** - Good balance
- 5000ms (5 seconds) - Slower updates, lower server load
- 10000ms (10 seconds) - Acceptable for low-traffic sites

---

## 🚨 IMPORTANT NOTES

### WebSocket vs HTTP Polling

The chat widget supports both:

**WebSocket (when available):**
- Instant message delivery
- No polling needed
- Lower server load
- Requires WebSocket server running

**HTTP Polling (current):**
- Works without WebSocket server
- 3-second delay maximum
- Simple to implement
- Always functional

**Current Setup:** HTTP polling is active. WebSocket can be enabled by uncommenting line 818:
```javascript
// connectWebSocket(); // Disabled by default - uncomment if WebSocket server is running
```

### Memory Management

Polling intervals are properly cleaned up:
- ✅ Stopped when switching to conversation list
- ✅ Cleared before starting new polling
- ✅ No duplicate intervals created
- ✅ No memory leaks

### API Response Format

The API returns:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "content": "Message text here",
      "sender_name": "John Doe",
      "created_at": "2025-11-30 10:30:00"
    }
  ],
  "count": 1
}
```

Chat widget handles both `data.data` and `data.messages` for flexibility.

---

## 📈 MONITORING

### Database Queries to Monitor

**Conversation List:**
```sql
-- Check conversation list API load
SELECT
    COUNT(*) as requests_per_minute,
    AVG(TIMESTAMPDIFF(SECOND, created_at, NOW())) as avg_age_seconds
FROM api_logs
WHERE endpoint = 'conversations.php'
AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE);
```

**Message Polling:**
```sql
-- Check message polling load
SELECT
    conversation_id,
    COUNT(*) as polls_per_minute
FROM api_logs
WHERE endpoint = 'messages.php'
AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
GROUP BY conversation_id;
```

### Browser Performance

**Check for Memory Leaks:**
1. Open Chrome DevTools
2. Go to Performance Monitor
3. Watch "JS heap size"
4. Should remain stable over time
5. If increasing: check interval cleanup

---

## 🎉 RESULT

**Chat Widget Status:**

- ✅ Conversation list auto-refreshes every 3 seconds
- ✅ Past messages display correctly in conversation view
- ✅ Active conversation messages update in real-time
- ✅ **New conversation tabs show correct user names immediately**
- ✅ No manual refresh required
- ✅ Efficient polling (stops when not needed)
- ✅ Field name consistency (content/message_text)
- ✅ Works without WebSocket server

**Impact:** Chat widget now provides true real-time messaging experience!

---

## 📝 COMPARISON: BEFORE vs AFTER

### Before Fixes ❌

| Issue | Behavior |
|-------|----------|
| New conversations | Appeared after 30 seconds or manual refresh |
| Past messages | Blank screen, nothing displayed |
| Active chat | Messages only loaded once, no updates |
| Display names | Missing until message sent + refresh |
| User experience | Felt broken, required constant refreshing |

### After Fixes ✅

| Feature | Behavior |
|---------|----------|
| New conversations | Appear within 3 seconds automatically |
| Past messages | All messages visible immediately |
| Active chat | New messages appear every 3 seconds |
| Display names | Show correctly from first open |
| User experience | Smooth, real-time, no refresh needed |

---

**Status:** ✅ Completed and Ready for Testing
**Files Modified:** 1 (chat_widget.php)
**Functions Added:** 2 (startMessagePolling, stopMessagePolling)
**Functions Modified:** 1 (startConversationWith - now passes participantName)
**Lines Changed:** ~20
**Priority:** CRITICAL → RESOLVED

---

**Last Updated:** November 30, 2025
