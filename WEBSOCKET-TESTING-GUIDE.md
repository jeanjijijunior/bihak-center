# WebSocket Testing Guide

## ✅ Server Status

Your WebSocket server is **RUNNING** on `ws://localhost:8080`

---

## 🧪 Test Data Available

- **Conversation ID:** 1
- **Type:** Direct conversation
- **Participants:** 2 (User + Mentor)
- **Messages:** 1 test message

---

## 🚀 Testing Options

### Option 1: Interactive Test Page (Recommended)

1. **Open the test page:**
   ```
   http://localhost/public/test-websocket.php
   ```

2. **What to expect:**
   - ✅ Auto-connects to WebSocket server
   - ✅ Shows connection status (green dot when connected)
   - ✅ Displays authentication success
   - ✅ Lists all subscribed conversations
   - ✅ Real-time event log

3. **Test Actions:**
   - **Send Test Message**: Sends a timestamped message to Conversation ID 1
   - **Test Typing Indicator**: Triggers typing indicator for 2 seconds
   - **Update Status**: Sends a ping to check server response
   - **Custom Message**: Type your own message and send it

4. **Multi-User Testing:**
   - Open the same page in a **second browser window** (or incognito mode)
   - Send a message from one window
   - Watch it appear **instantly** in the other window
   - See typing indicators in real-time

---

### Option 2: Real Messaging UI

1. **Open Inbox:**
   ```
   http://localhost/public/messages/inbox.php
   ```

2. **Click on the conversation** to open chat

3. **Open Chat:**
   ```
   http://localhost/public/messages/conversation.php?id=1
   ```

4. **Test features:**
   - ✅ Send messages
   - ✅ See typing indicators
   - ✅ Real-time delivery
   - ✅ Online status

---

## 🔍 What to Test

### 1. Connection ✅
- [ ] Page connects automatically
- [ ] Green status indicator appears
- [ ] "Connected ✓" message shown
- [ ] Authentication succeeds
- [ ] Conversation subscriptions listed

### 2. Send Messages ✅
- [ ] Click "Send Test Message"
- [ ] Message appears in event log
- [ ] `message_sent` confirmation received
- [ ] Message saved to database

### 3. Real-Time Delivery ✅
- [ ] Open 2 browser windows
- [ ] Send message from window 1
- [ ] Message appears instantly in window 2
- [ ] No page refresh needed

### 4. Typing Indicators ✅
- [ ] Click "Test Typing Indicator"
- [ ] `typing_start` sent
- [ ] After 2 seconds, `typing_stop` sent
- [ ] Other users see "Typing..." (in real UI)

### 5. Heartbeat ✅
- [ ] Click "Update Status"
- [ ] `ping` sent
- [ ] `pong` received
- [ ] Connection stays alive

### 6. Reconnection ✅
- [ ] Click "Disconnect"
- [ ] Connection closes (red indicator)
- [ ] Click "Connect"
- [ ] Reconnects successfully
- [ ] Resubscribes to conversations

---

## 📊 Monitoring

### Watch Server Logs

If you're running the server in console:
```bash
# You should see:
✅ WebSocket server is running on ws://localhost:8080
📱 New WebSocket connection
✅ User authenticated: user_1
💬 Message sent in conversation 1
```

### Database Changes

Check messages are being saved:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root bihak -e "SELECT id, conversation_id, sender_type, LEFT(message_text, 50) as message, created_at FROM messages ORDER BY created_at DESC LIMIT 5;"
```

---

## ✅ Expected Results

### Connection Test
```
✅ WebSocket connection opened
🔐 Sending authentication...
✅ Authentication successful!
📋 Subscribed to 1 conversations
```

### Message Test
```
📤 Sending test message to conversation 1...
✓ Message sent successfully (ID: 2)
💬 New message in conversation 1: "Test message #1 at..."
```

### Typing Test
```
⌨️ Sending typing_start to conversation 1...
⌨️ Sending typing_stop to conversation 1...
```

### Ping Test
```
📤 Sending ping...
🏓 Pong received
```

---

## 🔧 Troubleshooting

### ❌ Connection Refused
**Problem:** Can't connect to ws://localhost:8080

**Solutions:**
1. Check if server is running: `netstat -an | findstr ":8080"`
2. Restart server: `cd websocket && npm start`
3. Check firewall allows port 8080

### ❌ Authentication Failed
**Problem:** "Not authenticated" error

**Solutions:**
1. Make sure you're logged in (or test page sets session)
2. Check browser console for errors
3. Verify session data in PHP

### ❌ Messages Not Delivering
**Problem:** Send message but nothing happens

**Solutions:**
1. Check conversation ID is correct (use 1 for test)
2. Verify you're a participant in the conversation
3. Check database for message insert
4. Look at server console for errors

### ❌ Not Seeing Real-Time Updates
**Problem:** Messages don't appear without refresh

**Solutions:**
1. Verify WebSocket connection is green
2. Check browser console for WebSocket errors
3. Try reconnecting
4. Make sure using same conversation ID

---

## 🎯 Test Scenarios

### Scenario 1: Basic Messaging
1. Open test page
2. Wait for green connection indicator
3. Click "Send Test Message"
4. Check event log for success
5. ✅ Message delivered

### Scenario 2: Multi-User Chat
1. Open test page in Browser 1
2. Open test page in Browser 2 (incognito)
3. Send message from Browser 1
4. Watch it appear in Browser 2
5. ✅ Real-time working

### Scenario 3: Typing Indicators
1. Open test page in 2 browsers
2. Click "Test Typing Indicator" in Browser 1
3. Watch event log in Browser 2
4. See "User typing: started" message
5. ✅ Typing indicators working

### Scenario 4: Production UI Test
1. Open inbox: `http://localhost/public/messages/inbox.php`
2. See conversation list
3. Click conversation to open chat
4. Type and send message
5. ✅ Full UI working

---

## 📈 Performance Tests

### Load Test (Optional)
Open 10+ browser tabs with test page to simulate multiple users:
- ✅ All should connect successfully
- ✅ All should receive messages
- ✅ Server should handle load

### Latency Test
Send message and time delivery:
- ✅ Target: < 100ms delivery time
- ✅ Typical: 10-50ms on localhost

---

## 🎓 Understanding the Logs

### Event Log Colors
- **🟢 Green (Success):** Good events (message sent, authenticated)
- **🔵 Blue (Info):** Informational (connecting, subscribing)
- **🔴 Red (Error):** Problems (not connected, failed)

### Message Types
- `auth_success` - You're authenticated
- `new_message` - Someone sent a message
- `message_sent` - Your message was delivered
- `user_typing` - Someone is typing
- `status_change` - Online status changed
- `pong` - Heartbeat response
- `error` - Something went wrong

---

## 📋 Checklist

Before marking as "TESTED ✅":

- [ ] Connection establishes successfully
- [ ] Authentication works
- [ ] Can send messages
- [ ] Can receive messages in real-time
- [ ] Typing indicators work
- [ ] Heartbeat (ping/pong) works
- [ ] Reconnection works after disconnect
- [ ] Multi-user chat works (2+ windows)
- [ ] Messages save to database
- [ ] Production UI (inbox + conversation) works

---

## 🎉 Success Criteria

Your WebSocket messaging system is working if:

1. ✅ Test page connects and shows green indicator
2. ✅ Sending message shows confirmation
3. ✅ Opening 2 windows shows real-time message delivery
4. ✅ Typing indicators broadcast to other users
5. ✅ Connection stays alive with heartbeat
6. ✅ Production UI works end-to-end

---

## 🚀 Next Steps

After successful testing:

1. ✅ Mark WebSocket system as tested
2. ✅ Update navigation to include Messages link
3. ✅ Add message notification badges
4. ✅ Integrate with mentorship workspace
5. ✅ Deploy to production (configure WSS)
6. ✅ Announce to users

---

## 📞 Quick Reference

**Test Page:** http://localhost/public/test-websocket.php
**Inbox:** http://localhost/public/messages/inbox.php
**Conversation:** http://localhost/public/messages/conversation.php?id=1
**WebSocket:** ws://localhost:8080
**Test Conversation ID:** 1

---

**Happy Testing!** 🧪✨
