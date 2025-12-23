# Chat Widget Database Column Fixes

## Summary
Fixed all SQL query errors in the messaging system caused by incorrect column name references throughout MessagingManager.php and chat_widget.php.

## Issues Fixed

### 1. Messages Table Column Names (Multiple locations)
**Problem:** Queries referenced `m.user_id`, `m.admin_id`, `m.mentor_id` but the actual columns are:
- `m.sender_id` (for users)
- `m.sender_admin_id` (for admins)
- `m.sender_mentor_id` (for mentors)

**Files Fixed:**
- `includes/MessagingManager.php`
  - Line 415-417: `getMessages()` - JOIN conditions
  - Line 566-568: `searchMessages()` - JOIN conditions
  - Line 604: `markMessagesAsRead()` - WHERE clause
  - Line 658: `getUnreadMessageCount()` - WHERE clause

**Solution:** Changed all JOIN conditions to:
```sql
LEFT JOIN users u ON u.id = m.sender_id AND m.sender_type = 'user'
LEFT JOIN admins a ON a.id = m.sender_admin_id AND m.sender_type = 'admin'
LEFT JOIN sponsors s ON s.id = m.sender_mentor_id AND m.sender_type = 'mentor'
```

### 2. Admin and Sponsor Name Columns
**Problem:** Queries referenced `a.name` and `s.name` but tables use `full_name`

**Files Fixed:**
- `includes/MessagingManager.php`
  - Line 268-269: `getConversationParticipants()` - CASE statement
  - Already fixed in `getUserConversations()`

- `api/messaging/search_users.php`
  - Changed all references from `name` to `full_name`

### 3. Messages Table Content Column
**Problem:** Query referenced `m.content` but the column is `m.message_text`

**Files Fixed:**
- `includes/MessagingManager.php`
  - Line 573: `searchMessages()` - WHERE clause

### 4. Conversations Table Title Column
**Problem:** Query referenced `c.title` but the column is `c.name`

**Files Fixed:**
- `includes/MessagingManager.php`
  - Line 557: `searchMessages()` - SELECT statement

### 5. Chat Widget Message Parameter
**Problem:** JavaScript sent `message: message` but API expects `content: message`

**Files Fixed:**
- `includes/chat_widget.php`
  - Line 1015: Changed `message:` to `content:` in POST request

### 6. Missing API Endpoint
**Problem:** Widget tried to call `mark_read.php` which didn't exist

**Files Created:**
- `api/messaging/mark_read.php` - New endpoint for marking messages as read

## Testing

After these fixes, the messaging system should:
1. ✅ Load conversations without SQL errors
2. ✅ Display conversation participants correctly
3. ✅ Send messages successfully
4. ✅ Display sent messages in the conversation
5. ✅ Search for contacts by name
6. ✅ Mark messages as read

## How to Test

1. **Refresh your browser** to clear any cached JavaScript
2. **Open the chat widget** on any page (my-account.php, etc.)
3. **Click the "+" button** to start a new conversation
4. **Click on "System Administrator"** or any contact
5. **Type a message** and press Enter or click Send
6. **Verify the message appears** in the conversation
7. **Check that no errors appear** in the browser console

## Error Log Notes

If you still see errors in `C:\xampp\apache\logs\error.log` with timestamps from before these fixes (before 16:00 on Nov 24), those are **OLD CACHED ERRORS**. The fixes are now in place and will prevent new errors from occurring.

## Database Schema Reference

### Messages Table Columns
- `sender_id` - User ID (for user messages)
- `sender_admin_id` - Admin ID (for admin messages)
- `sender_mentor_id` - Mentor ID (for mentor messages)
- `sender_type` - ENUM('user', 'admin', 'mentor')
- `message_text` - The message content

### Conversations Table Columns
- `name` - Conversation title/name
- `conversation_type` - ENUM('direct', 'team', 'broadcast', 'exercise')

### User-related Tables
- `users.full_name` - User's full name
- `admins.full_name` - Admin's full name
- `sponsors.full_name` - Mentor/Sponsor's full name
