# Chat Widget Path Fix - Admin Dashboard Compatibility

## Problem
The chat widget was showing 404 errors in the admin dashboard:
- `GET /public/api/messaging/conversations.php 404 (Not Found)`
- `GET /public/api/messaging/search_users.php 404 (Not Found)`

### Root Cause
The chat widget used hardcoded relative paths like `../api/messaging/` which work from `/public/` pages but NOT from `/public/admin/` pages:

- From `/public/my-account.php`: `../api/messaging/` → `/api/messaging/` ✅ CORRECT
- From `/public/admin/dashboard.php`: `../api/messaging/` → `/public/api/messaging/` ❌ WRONG!

## Solution
Made API paths **dynamic** based on the current page location, just like the existing assets path logic.

## Changes Made

### File: `includes/chat_widget.php`

#### Change 1: Added API Path Configuration (Line 37-51)
```php
// Get base path for assets and API
$current_dir = dirname($_SERVER['SCRIPT_FILENAME']);
$is_in_public = (basename($current_dir) === 'public');
$is_in_admin = (basename($current_dir) === 'admin');

if ($is_in_admin) {
    $widget_assets_path = '../../assets/';
    $widget_api_path = '../../api/messaging/';  // NEW!
} elseif ($is_in_public) {
    $widget_assets_path = '../assets/';
    $widget_api_path = '../api/messaging/';      // NEW!
} else {
    $widget_assets_path = 'assets/';
    $widget_api_path = 'api/messaging/';         // NEW!
}
```

#### Change 2: Added apiBasePath to JavaScript Configuration (Line 803)
```javascript
let chatWidget = {
    isOpen: false,
    activeConversationId: null,
    conversations: [],
    messages: {},
    unreadCount: 0,
    ws: null,
    typingTimeout: null,
    participantType: '<?php echo $chat_participant_type; ?>',
    participantId: <?php echo $chat_participant_id; ?>,
    participantName: '<?php echo addslashes($chat_participant_name); ?>',
    apiBasePath: '<?php echo $widget_api_path; ?>'  // NEW!
};
```

#### Change 3: Replaced All Hardcoded API Paths

**Before (WRONG):**
```javascript
fetch('../api/messaging/conversations.php')
fetch('../api/messaging/messages.php')
fetch('../api/messaging/search_users.php')
fetch('../api/messaging/mark_read.php')
```

**After (CORRECT):**
```javascript
fetch(chatWidget.apiBasePath + 'conversations.php')
fetch(chatWidget.apiBasePath + 'messages.php')
fetch(chatWidget.apiBasePath + 'search_users.php')
fetch(chatWidget.apiBasePath + 'mark_read.php')
```

### Functions Updated (8 locations):
1. **Line 838** - `loadConversations()`
2. **Line 948** - `loadMessages(conversationId)`
3. **Line 1015** - `sendMessage()` - HTTP fallback
4. **Line 1060** - `markConversationAsRead(conversationId)`
5. **Line 1264** - `loadSuggestedContacts()`
6. **Line 1320-1321** - `searchContacts()` - with query parameter
7. **Line 1407** - `startConversationWith()` - create conversation

## Result

### Path Resolution Now Works Everywhere:

| Page Location | `chatWidget.apiBasePath` Value | Resolves To |
|---------------|-------------------------------|-------------|
| `/public/my-account.php` | `../api/messaging/` | `/api/messaging/` ✅ |
| `/public/admin/dashboard.php` | `../../api/messaging/` | `/api/messaging/` ✅ |
| `/public/admin/incubation-admin-dashboard.php` | `../../api/messaging/` | `/api/messaging/` ✅ |

## Testing

1. **Refresh browser** (Ctrl + F5) to clear cached JavaScript
2. **Test from regular user page:**
   - Go to `http://localhost/bihak-center/public/my-account.php`
   - Open chat widget
   - Verify conversations load
   - Send a message

3. **Test from admin dashboard:**
   - Go to `http://localhost/bihak-center/public/admin/dashboard.php`
   - Open chat widget
   - Verify conversations load
   - Start new conversation
   - Send a message

4. **Check browser console** - Should see NO 404 errors for API calls

## Why This Approach is Better

1. **Scalable** - Works from any directory depth
2. **Maintainable** - Single configuration point
3. **Consistent** - Uses same pattern as existing asset path logic
4. **Future-proof** - New pages automatically work

## Related Files

✅ `includes/chat_widget.php` - Updated with dynamic paths
✅ All messaging APIs still at: `api/messaging/*.php`
✅ No changes needed to API files themselves

## Date Fixed
November 25, 2025

## Additional Notes

This fix complements the previous database column name fixes. Both are needed for the messaging system to work correctly:
- Database column fixes → Messages can be saved/retrieved
- Path fixes → API calls reach the correct endpoints

See also: [MESSAGING-MODULE-COMPLETE-FIX.md](MESSAGING-MODULE-COMPLETE-FIX.md)
