<?php
/**
 * Conversation Page
 * Real-time messaging interface for a specific conversation
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MessagingManager.php';

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get conversation ID
if (!isset($_GET['id'])) {
    header('Location: inbox.php');
    exit;
}

$conversation_id = intval($_GET['id']);

$conn = getDatabaseConnection();
$messagingManager = new MessagingManager($conn);

// Determine participant type and ID
$participant_type = null;
$participant_id = null;

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

// Check authorization
if (!$messagingManager->isParticipant($conversation_id, $participant_type, $participant_id)) {
    header('Location: inbox.php');
    exit;
}

// Get conversation details
$conversations = $messagingManager->getUserConversations($participant_type, $participant_id);
$current_conversation = null;
foreach ($conversations as $conv) {
    if ($conv['id'] == $conversation_id) {
        $current_conversation = $conv;
        break;
    }
}

if (!$current_conversation) {
    header('Location: inbox.php');
    exit;
}

// Get messages
$messages = $messagingManager->getMessages($conversation_id, $participant_type, $participant_id, 100);

// Get participants
$participants = $messagingManager->getConversationParticipants($conversation_id);

closeDatabaseConnection($conn);

$display_name = $current_conversation['display_name'] ?? $current_conversation['title'] ?? 'Chat';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($display_name); ?> - Messages</title>
    <link rel="icon" type="image/png" href="../../assets/images/favimg.png">
    <link rel="stylesheet" href="../../assets/css/header_new.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f7fafc;
            color: #2d3748;
        }

        .chat-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            color: #667eea;
            text-decoration: none;
            font-size: 1.5rem;
            transition: all 0.3s;
        }

        .back-btn:hover {
            color: #764ba2;
        }

        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.3rem;
            position: relative;
        }

        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid white;
            background: #a0aec0;
        }

        .status-dot.online {
            background: #48bb78;
        }

        .chat-info h2 {
            font-size: 1.3rem;
            color: #2d3748;
            margin-bottom: 3px;
        }

        .typing-indicator {
            color: #667eea;
            font-size: 0.9rem;
            font-style: italic;
            display: none;
        }

        .typing-indicator.visible {
            display: block;
        }

        .chat-messages {
            flex: 1;
            background: white;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            display: flex;
            gap: 12px;
            max-width: 70%;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.sent {
            margin-left: auto;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .message.sent .message-avatar {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .message-sender {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .message-time {
            color: #a0aec0;
            font-size: 0.85rem;
        }

        .message-bubble {
            background: #f7fafc;
            padding: 12px 16px;
            border-radius: 12px;
            color: #2d3748;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .message.sent .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .message-edited {
            font-size: 0.8rem;
            color: #a0aec0;
            font-style: italic;
            margin-top: 3px;
        }

        .message.sent .message-edited {
            color: rgba(255, 255, 255, 0.8);
        }

        .chat-input-container {
            background: white;
            padding: 20px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
        }

        .chat-input {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .input-wrapper {
            flex: 1;
            position: relative;
        }

        #messageInput {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            resize: none;
            max-height: 120px;
            min-height: 45px;
            transition: all 0.3s;
        }

        #messageInput:focus {
            outline: none;
            border-color: #667eea;
        }

        .send-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            height: 45px;
        }

        .send-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-messages {
            text-align: center;
            color: #a0aec0;
            padding: 60px 20px;
        }

        .empty-messages svg {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .date-divider {
            text-align: center;
            color: #a0aec0;
            font-size: 0.85rem;
            margin: 20px 0;
            position: relative;
        }

        .date-divider::before,
        .date-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e2e8f0;
        }

        .date-divider::before {
            left: 0;
        }

        .date-divider::after {
            right: 0;
        }

        @media (max-width: 768px) {
            .message {
                max-width: 85%;
            }

            .chat-header {
                padding: 15px;
            }

            .chat-messages {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="chat-container">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="header-left">
                <a href="inbox.php" class="back-btn">←</a>
                <div class="chat-avatar">
                    <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                    <div class="status-dot" id="statusDot"></div>
                </div>
                <div class="chat-info">
                    <h2><?php echo htmlspecialchars($display_name); ?></h2>
                    <div class="typing-indicator" id="typingIndicator">
                        <span id="typingText">Typing...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" id="messagesContainer">
            <?php if (empty($messages)): ?>
                <div class="empty-messages">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <h3>No messages yet</h3>
                    <p>Start the conversation by sending a message</p>
                </div>
            <?php else: ?>
                <?php
                $last_date = null;
                foreach ($messages as $msg):
                    $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                    $is_sent = ($msg['sender_type'] === $participant_type && $msg['user_id'] == ($participant_type === 'user' ? $participant_id : null) &&
                                $msg['admin_id'] == ($participant_type === 'admin' ? $participant_id : null) &&
                                $msg['mentor_id'] == ($participant_type === 'mentor' ? $participant_id : null));

                    // Show date divider
                    if ($msg_date !== $last_date):
                        $last_date = $msg_date;
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));

                        if ($msg_date === $today) {
                            $date_label = 'Today';
                        } elseif ($msg_date === $yesterday) {
                            $date_label = 'Yesterday';
                        } else {
                            $date_label = date('F j, Y', strtotime($msg_date));
                        }
                ?>
                <div class="date-divider"><?php echo $date_label; ?></div>
                <?php endif; ?>

                <div class="message <?php echo $is_sent ? 'sent' : ''; ?>" data-id="<?php echo $msg['id']; ?>">
                    <div class="message-avatar">
                        <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                            <span class="message-time"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></span>
                        </div>
                        <div class="message-bubble">
                            <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
                        </div>
                        <?php if ($msg['edited_at']): ?>
                        <div class="message-edited">Edited</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Input Area -->
        <div class="chat-input-container">
            <form class="chat-input" id="messageForm" onsubmit="sendMessage(event)">
                <div class="input-wrapper">
                    <textarea
                        id="messageInput"
                        placeholder="Type a message..."
                        rows="1"
                        onkeydown="handleKeyDown(event)"
                        oninput="handleInput()"
                    ></textarea>
                </div>
                <button type="submit" class="send-btn" id="sendBtn">Send</button>
            </form>
        </div>
    </div>

    <script>
        const conversationId = <?php echo $conversation_id; ?>;
        const participantType = '<?php echo $participant_type; ?>';
        const participantId = <?php echo $participant_id; ?>;

        let ws = null;
        let typingTimeout = null;
        let isTyping = false;

        // Connect to WebSocket
        function connectWebSocket() {
            ws = new WebSocket('ws://localhost:8080');

            ws.onopen = () => {
                console.log('✅ WebSocket connected');

                // Authenticate
                ws.send(JSON.stringify({
                    type: 'auth',
                    participant_type: participantType,
                    participant_id: participantId
                }));
            };

            ws.onmessage = (event) => {
                const message = JSON.parse(event.data);
                console.log('📩 Received:', message);

                switch (message.type) {
                    case 'auth_success':
                        console.log('✅ Authenticated');
                        // Subscribe to this conversation
                        ws.send(JSON.stringify({
                            type: 'subscribe_conversation',
                            conversation_id: conversationId
                        }));
                        break;

                    case 'new_message':
                        if (message.conversation_id === conversationId) {
                            appendMessage(message);
                            scrollToBottom();
                        }
                        break;

                    case 'user_typing':
                        if (message.conversation_id === conversationId &&
                            message.user_id !== `${participantType}_${participantId}`) {
                            showTypingIndicator(message.is_typing);
                        }
                        break;

                    case 'status_change':
                        updateStatusIndicator(message.status);
                        break;

                    case 'message_sent':
                        // Update temp message with real ID
                        console.log('✅ Message sent:', message.message_id);
                        break;
                }
            };

            ws.onclose = () => {
                console.log('❌ WebSocket disconnected');
                setTimeout(connectWebSocket, 3000);
            };

            ws.onerror = (error) => {
                console.error('❌ WebSocket error:', error);
            };
        }

        // Send message
        function sendMessage(event) {
            event.preventDefault();

            const input = document.getElementById('messageInput');
            const content = input.value.trim();

            if (!content) return;

            const tempId = Date.now();

            // Try WebSocket first
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'message',
                    conversation_id: conversationId,
                    content: content,
                    temp_id: tempId
                }));

                // Clear input
                input.value = '';
                input.style.height = 'auto';

                // Stop typing indicator
                stopTyping();
            } else {
                // Fallback to HTTP API
                sendMessageViaHTTP(content, tempId, input);
            }
        }

        // Send message via HTTP API (fallback)
        function sendMessageViaHTTP(content, tempId, input) {
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';

            fetch('../../api/messaging/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add message to UI
                    appendMessage({
                        message_id: data.message_id,
                        sender_type: participantType,
                        sender_id: participantId,
                        sender_name: '<?php echo $_SESSION['user_name'] ?? $_SESSION['sponsor_name'] ?? $_SESSION['admin_username'] ?? 'You'; ?>',
                        content: content,
                        created_at: new Date().toISOString()
                    });

                    // Clear input
                    input.value = '';
                    input.style.height = 'auto';

                    // Scroll to bottom
                    scrollToBottom();

                    // Stop typing
                    stopTyping();
                } else {
                    alert('Failed to send message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send';
            });
        }

        // Append message to UI
        function appendMessage(message) {
            const container = document.getElementById('messagesContainer');

            // Remove empty state if exists
            const emptyState = container.querySelector('.empty-messages');
            if (emptyState) {
                emptyState.remove();
            }

            const isSent = message.sender_type === participantType && message.sender_id === participantId;

            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isSent ? 'sent' : ''}`;
            messageDiv.dataset.id = message.message_id;

            const time = new Date(message.created_at);
            const timeStr = time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

            messageDiv.innerHTML = `
                <div class="message-avatar">
                    ${message.sender_name.charAt(0).toUpperCase()}
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">${escapeHtml(message.sender_name)}</span>
                        <span class="message-time">${timeStr}</span>
                    </div>
                    <div class="message-bubble">
                        ${escapeHtml(message.content).replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;

            container.appendChild(messageDiv);
        }

        // Handle keyboard shortcuts
        function handleKeyDown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage(event);
            }
        }

        // Handle input changes
        function handleInput() {
            const input = document.getElementById('messageInput');

            // Auto-resize textarea
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';

            // Typing indicator
            if (input.value.trim().length > 0) {
                startTyping();
            } else {
                stopTyping();
            }
        }

        // Start typing indicator
        function startTyping() {
            if (!isTyping && ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'typing_start',
                    conversation_id: conversationId
                }));
                isTyping = true;
            }

            // Reset timeout
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(stopTyping, 3000);
        }

        // Stop typing indicator
        function stopTyping() {
            if (isTyping && ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'typing_stop',
                    conversation_id: conversationId
                }));
                isTyping = false;
            }
            clearTimeout(typingTimeout);
        }

        // Show/hide typing indicator
        function showTypingIndicator(visible) {
            const indicator = document.getElementById('typingIndicator');
            if (visible) {
                indicator.classList.add('visible');
            } else {
                indicator.classList.remove('visible');
            }
        }

        // Update status indicator
        function updateStatusIndicator(status) {
            const dot = document.getElementById('statusDot');
            dot.className = 'status-dot ' + status;
        }

        // Scroll to bottom
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Poll for new messages (fallback when WebSocket unavailable)
        let lastMessageId = <?php echo !empty($messages) ? max(array_column($messages, 'id')) : 0; ?>;
        let pollInterval = null;

        function startPolling() {
            // Only poll if WebSocket is not connected
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                pollInterval = setInterval(pollMessages, 3000); // Poll every 3 seconds
            }
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        function pollMessages() {
            // Don't poll if WebSocket is connected
            if (ws && ws.readyState === WebSocket.OPEN) {
                stopPolling();
                return;
            }

            fetch(`../../api/messaging/messages.php?conversation_id=${conversationId}&limit=50`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        // Find new messages (messages with ID > lastMessageId)
                        const newMessages = data.data.filter(msg => msg.id > lastMessageId);

                        if (newMessages.length > 0) {
                            newMessages.forEach(msg => {
                                appendMessage({
                                    message_id: msg.id,
                                    sender_type: msg.sender_type,
                                    sender_id: msg.user_id || msg.admin_id || msg.mentor_id,
                                    sender_name: msg.sender_name,
                                    content: msg.content,
                                    created_at: msg.created_at
                                });
                            });

                            // Update last message ID
                            lastMessageId = Math.max(...newMessages.map(msg => msg.id));

                            // Scroll to bottom
                            scrollToBottom();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error polling messages:', error);
                });
        }

        // Initialize
        connectWebSocket();
        scrollToBottom();

        // Start polling as fallback
        setTimeout(startPolling, 2000); // Give WebSocket 2 seconds to connect

        // Heartbeat
        setInterval(() => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ type: 'ping' }));
                stopPolling(); // Stop polling if WebSocket is active
            } else {
                // Restart polling if WebSocket disconnected
                if (!pollInterval) {
                    startPolling();
                }
            }
        }, 30000);
    </script>
</body>
</html>
