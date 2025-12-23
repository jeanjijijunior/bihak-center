<?php
/**
 * WebSocket Connection Test Page
 * Tests real-time messaging functionality
 */
session_start();

// For testing purposes, set a test user session if not logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['sponsor_id'])) {
    // Set a test user (change as needed)
    $_SESSION['user_id'] = 1; // Test as user ID 1
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Test - Bihak Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .test-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .test-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .test-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #f56565;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        .status-indicator.connected {
            background: #48bb78;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .test-content {
            padding: 30px;
        }

        .info-section {
            background: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-section h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #718096;
            font-weight: 600;
        }

        .info-value {
            color: #2d3748;
            font-family: monospace;
        }

        .log-section {
            background: #2d3748;
            border-radius: 8px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .log-entry {
            padding: 5px 0;
            color: #e2e8f0;
            border-bottom: 1px solid #4a5568;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .log-entry.success {
            color: #48bb78;
        }

        .log-entry.error {
            color: #f56565;
        }

        .log-entry.info {
            color: #4299e1;
        }

        .log-timestamp {
            color: #a0aec0;
            margin-right: 10px;
        }

        .test-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .test-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .test-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .test-btn.success {
            background: #48bb78;
            color: white;
        }

        .test-btn.danger {
            background: #f56565;
            color: white;
        }

        .test-btn.secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .test-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .test-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .message-input {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .message-input input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .message-input input:focus {
            outline: none;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>🧪 WebSocket Test Suite</h1>
            <p>
                <span class="status-indicator" id="statusIndicator"></span>
                <span id="connectionStatus">Disconnected</span>
            </p>
        </div>

        <div class="test-content">
            <!-- Connection Info -->
            <div class="info-section">
                <h3>📊 Connection Information</h3>
                <div class="info-row">
                    <span class="info-label">WebSocket URL:</span>
                    <span class="info-value">ws://localhost:8080</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User Type:</span>
                    <span class="info-value" id="userType">
                        <?php
                        if (isset($_SESSION['user_id'])) echo 'user';
                        elseif (isset($_SESSION['admin_id'])) echo 'admin';
                        elseif (isset($_SESSION['sponsor_id'])) echo 'mentor';
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">User ID:</span>
                    <span class="info-value" id="userId">
                        <?php
                        if (isset($_SESSION['user_id'])) echo $_SESSION['user_id'];
                        elseif (isset($_SESSION['admin_id'])) echo $_SESSION['admin_id'];
                        elseif (isset($_SESSION['sponsor_id'])) echo $_SESSION['sponsor_id'];
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Messages Sent:</span>
                    <span class="info-value" id="messagesSent">0</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Messages Received:</span>
                    <span class="info-value" id="messagesReceived">0</span>
                </div>
            </div>

            <!-- Test Actions -->
            <div class="test-actions">
                <button class="test-btn primary" onclick="connect()" id="connectBtn">
                    Connect
                </button>
                <button class="test-btn danger" onclick="disconnect()" id="disconnectBtn" disabled>
                    Disconnect
                </button>
                <button class="test-btn success" onclick="sendTestMessage()" id="sendBtn" disabled>
                    Send Test Message
                </button>
                <button class="test-btn success" onclick="startTyping()" id="typingBtn" disabled>
                    Test Typing Indicator
                </button>
                <button class="test-btn secondary" onclick="updateStatus()" id="statusBtn" disabled>
                    Update Status
                </button>
                <button class="test-btn secondary" onclick="clearLog()">
                    Clear Log
                </button>
            </div>

            <!-- Custom Message -->
            <div class="message-input">
                <input type="number" id="conversationId" placeholder="Conversation ID (e.g., 1)" value="1">
                <input type="text" id="customMessage" placeholder="Type a custom message...">
                <button class="test-btn primary" onclick="sendCustomMessage()" id="customSendBtn" disabled>
                    Send
                </button>
            </div>

            <!-- Event Log -->
            <div class="info-section" style="margin-top: 20px;">
                <h3>📝 Event Log</h3>
            </div>
            <div class="log-section" id="eventLog">
                <div class="log-entry info">
                    <span class="log-timestamp">[Ready]</span>
                    Test suite initialized. Click "Connect" to start.
                </div>
            </div>
        </div>
    </div>

    <script>
        const participantType = document.getElementById('userType').textContent.trim();
        const participantId = parseInt(document.getElementById('userId').textContent.trim());

        let ws = null;
        let messagesSent = 0;
        let messagesReceived = 0;

        function log(message, type = 'info') {
            const logDiv = document.getElementById('eventLog');
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;

            const timestamp = new Date().toLocaleTimeString();
            entry.innerHTML = `<span class="log-timestamp">[${timestamp}]</span>${message}`;

            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function updateUI(connected) {
            const statusIndicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('connectionStatus');
            const connectBtn = document.getElementById('connectBtn');
            const disconnectBtn = document.getElementById('disconnectBtn');
            const sendBtn = document.getElementById('sendBtn');
            const typingBtn = document.getElementById('typingBtn');
            const statusBtn = document.getElementById('statusBtn');
            const customSendBtn = document.getElementById('customSendBtn');

            if (connected) {
                statusIndicator.classList.add('connected');
                statusText.textContent = 'Connected ✓';
                connectBtn.disabled = true;
                disconnectBtn.disabled = false;
                sendBtn.disabled = false;
                typingBtn.disabled = false;
                statusBtn.disabled = false;
                customSendBtn.disabled = false;
            } else {
                statusIndicator.classList.remove('connected');
                statusText.textContent = 'Disconnected';
                connectBtn.disabled = false;
                disconnectBtn.disabled = true;
                sendBtn.disabled = true;
                typingBtn.disabled = true;
                statusBtn.disabled = true;
                customSendBtn.disabled = true;
            }
        }

        function connect() {
            log('🔌 Connecting to WebSocket server...', 'info');

            ws = new WebSocket('ws://localhost:8080');

            ws.onopen = () => {
                log('✅ WebSocket connection opened', 'success');
                updateUI(true);

                // Authenticate
                log('🔐 Sending authentication...', 'info');
                ws.send(JSON.stringify({
                    type: 'auth',
                    participant_type: participantType,
                    participant_id: participantId
                }));
            };

            ws.onmessage = (event) => {
                messagesReceived++;
                document.getElementById('messagesReceived').textContent = messagesReceived;

                try {
                    const data = JSON.parse(event.data);
                    log(`📩 Received: ${data.type} - ${JSON.stringify(data).substring(0, 100)}`, 'success');

                    // Handle specific message types
                    switch (data.type) {
                        case 'auth_success':
                            log('✅ Authentication successful!', 'success');
                            log(`📋 Subscribed to ${data.conversations.length} conversations`, 'info');
                            break;
                        case 'new_message':
                            log(`💬 New message in conversation ${data.conversation_id}: "${data.content}"`, 'success');
                            break;
                        case 'user_typing':
                            log(`⌨️ User typing: ${data.is_typing ? 'started' : 'stopped'}`, 'info');
                            break;
                        case 'status_change':
                            log(`👤 Status changed to: ${data.status}`, 'info');
                            break;
                        case 'message_sent':
                            log(`✓ Message sent successfully (ID: ${data.message_id})`, 'success');
                            break;
                        case 'pong':
                            log('🏓 Pong received', 'info');
                            break;
                        case 'error':
                            log(`❌ Error: ${data.message}`, 'error');
                            break;
                    }
                } catch (e) {
                    log(`⚠️ Could not parse message: ${event.data}`, 'error');
                }
            };

            ws.onclose = () => {
                log('❌ WebSocket connection closed', 'error');
                updateUI(false);
            };

            ws.onerror = (error) => {
                log('❌ WebSocket error occurred', 'error');
                console.error('WebSocket error:', error);
            };
        }

        function disconnect() {
            if (ws) {
                log('🔌 Disconnecting...', 'info');
                ws.close();
                ws = null;
            }
        }

        function sendTestMessage() {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                log('❌ Not connected!', 'error');
                return;
            }

            const conversationId = parseInt(document.getElementById('conversationId').value);
            const testMessage = `Test message #${messagesSent + 1} at ${new Date().toLocaleTimeString()}`;

            log(`📤 Sending test message to conversation ${conversationId}...`, 'info');

            ws.send(JSON.stringify({
                type: 'message',
                conversation_id: conversationId,
                content: testMessage,
                temp_id: Date.now()
            }));

            messagesSent++;
            document.getElementById('messagesSent').textContent = messagesSent;
        }

        function sendCustomMessage() {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                log('❌ Not connected!', 'error');
                return;
            }

            const conversationId = parseInt(document.getElementById('conversationId').value);
            const content = document.getElementById('customMessage').value.trim();

            if (!content) {
                log('❌ Please enter a message', 'error');
                return;
            }

            log(`📤 Sending custom message to conversation ${conversationId}...`, 'info');

            ws.send(JSON.stringify({
                type: 'message',
                conversation_id: conversationId,
                content: content,
                temp_id: Date.now()
            }));

            messagesSent++;
            document.getElementById('messagesSent').textContent = messagesSent;
            document.getElementById('customMessage').value = '';
        }

        function startTyping() {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                log('❌ Not connected!', 'error');
                return;
            }

            const conversationId = parseInt(document.getElementById('conversationId').value);

            log(`⌨️ Sending typing_start to conversation ${conversationId}...`, 'info');

            ws.send(JSON.stringify({
                type: 'typing_start',
                conversation_id: conversationId
            }));

            // Stop after 2 seconds
            setTimeout(() => {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    log(`⌨️ Sending typing_stop to conversation ${conversationId}...`, 'info');
                    ws.send(JSON.stringify({
                        type: 'typing_stop',
                        conversation_id: conversationId
                    }));
                }
            }, 2000);
        }

        function updateStatus() {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                log('❌ Not connected!', 'error');
                return;
            }

            log('📤 Sending ping...', 'info');

            ws.send(JSON.stringify({
                type: 'ping'
            }));
        }

        function clearLog() {
            document.getElementById('eventLog').innerHTML = '';
            log('Log cleared', 'info');
        }

        // Auto-connect on page load
        log('🚀 Auto-connecting in 1 second...', 'info');
        setTimeout(connect, 1000);
    </script>
</body>
</html>
