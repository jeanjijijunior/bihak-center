<?php
/**
 * Messages Inbox Page
 * Main messaging interface - list of conversations
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MessagingManager.php';

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php');
    exit;
}

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

// Get conversations
$conversations = $messagingManager->getUserConversations($participant_type, $participant_id);
$unread_count = $messagingManager->getUnreadCount($participant_type, $participant_id);

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Bihak Center</title>
    <link rel="icon" type="image/png" href="../../assets/images/favimg.png">
    <link rel="stylesheet" href="../../assets/css/header_new.css">
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

        .messages-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            gap: 20px;
            height: calc(100vh - 80px);
        }

        .sidebar {
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 2px solid #e2e8f0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .unread-badge {
            display: inline-block;
            background: #f56565;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .search-box {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .conversation-item:hover {
            background: #f7fafc;
        }

        .conversation-item.active {
            background: #edf2f7;
            border-left: 4px solid #667eea;
        }

        .conversation-item.unread {
            background: #fef5e7;
        }

        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            flex-shrink: 0;
            position: relative;
        }

        .status-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .status-indicator.online {
            background: #48bb78;
        }

        .status-indicator.away {
            background: #ed8936;
        }

        .status-indicator.offline {
            background: #a0aec0;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-preview {
            color: #718096;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-item.unread .conversation-name,
        .conversation-item.unread .conversation-preview {
            font-weight: 600;
            color: #2d3748;
        }

        .conversation-meta {
            text-align: right;
            flex-shrink: 0;
        }

        .conversation-time {
            color: #a0aec0;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .unread-count {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .main-content {
            flex: 1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a0aec0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .empty-state p {
            font-size: 1rem;
        }

        .new-conversation-btn {
            margin: 15px;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: calc(100% - 30px);
        }

        .new-conversation-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .messages-container {
                flex-direction: column;
                height: auto;
            }

            .sidebar {
                width: 100%;
                height: auto;
            }

            .main-content {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="messages-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h1>
                    💬 Messages
                    <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h1>
                <p style="font-size: 0.9rem; opacity: 0.9;">Your conversations</p>
            </div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search conversations...">
            </div>

            <button class="new-conversation-btn" onclick="newConversation()">
                + New Conversation
            </button>

            <div class="conversations-list" id="conversationsList">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <h3>No conversations yet</h3>
                        <p>Start a new conversation to begin messaging</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>"
                         data-id="<?php echo $conv['id']; ?>"
                         onclick="openConversation(<?php echo $conv['id']; ?>)">
                        <div class="conversation-avatar">
                            <?php
                            $display_name = $conv['display_name'] ?? $conv['title'] ?? 'Chat';
                            echo strtoupper(substr($display_name, 0, 1));
                            ?>
                            <div class="status-indicator offline"></div>
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <?php echo htmlspecialchars($display_name); ?>
                            </div>
                            <div class="conversation-preview">
                                <?php
                                if ($conv['last_message_content']) {
                                    echo htmlspecialchars(substr($conv['last_message_content'], 0, 50));
                                    if (strlen($conv['last_message_content']) > 50) echo '...';
                                } else {
                                    echo 'No messages yet';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="conversation-meta">
                            <div class="conversation-time">
                                <?php
                                if ($conv['last_message_time']) {
                                    $time = strtotime($conv['last_message_time']);
                                    $diff = time() - $time;
                                    if ($diff < 60) echo 'Just now';
                                    elseif ($diff < 3600) echo floor($diff / 60) . 'm';
                                    elseif ($diff < 86400) echo floor($diff / 3600) . 'h';
                                    else echo date('M j', $time);
                                }
                                ?>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                            <span class="unread-count"><?php echo $conv['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                <h3>Select a conversation</h3>
                <p>Choose a conversation from the list to view messages</p>
            </div>
        </div>
    </div>

    <script>
        // Search conversations
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const conversations = document.querySelectorAll('.conversation-item');

            conversations.forEach(conv => {
                const name = conv.querySelector('.conversation-name').textContent.toLowerCase();
                const preview = conv.querySelector('.conversation-preview').textContent.toLowerCase();

                if (name.includes(searchTerm) || preview.includes(searchTerm)) {
                    conv.style.display = 'flex';
                } else {
                    conv.style.display = 'none';
                }
            });
        });

        // Open conversation
        function openConversation(conversationId) {
            window.location.href = 'conversation.php?id=' + conversationId;
        }

        // New conversation
        function newConversation() {
            // TODO: Implement new conversation modal
            alert('New conversation feature coming soon!\n\nFor now, you can start conversations from:\n- Mentorship workspace (Message button)\n- Team pages\n- User profiles');
        }

        // WebSocket connection for real-time updates
        let ws = null;

        function connectWebSocket() {
            ws = new WebSocket('ws://localhost:8080');

            ws.onopen = () => {
                console.log('✅ WebSocket connected');

                // Authenticate
                ws.send(JSON.stringify({
                    type: 'auth',
                    participant_type: '<?php echo $participant_type; ?>',
                    participant_id: <?php echo $participant_id; ?>
                }));
            };

            ws.onmessage = (event) => {
                const message = JSON.parse(event.data);
                console.log('📩 Received:', message);

                switch (message.type) {
                    case 'auth_success':
                        console.log('✅ Authenticated');
                        break;

                    case 'new_message':
                        // Update conversation preview
                        updateConversationPreview(message);
                        break;

                    case 'status_change':
                        // Update status indicator
                        updateStatusIndicator(message);
                        break;
                }
            };

            ws.onclose = () => {
                console.log('❌ WebSocket disconnected');
                // Reconnect after 3 seconds
                setTimeout(connectWebSocket, 3000);
            };

            ws.onerror = (error) => {
                console.error('❌ WebSocket error:', error);
            };
        }

        function updateConversationPreview(message) {
            const conv = document.querySelector(`[data-id="${message.conversation_id}"]`);
            if (!conv) return;

            const preview = conv.querySelector('.conversation-preview');
            const time = conv.querySelector('.conversation-time');

            preview.textContent = message.content.substring(0, 50);
            if (message.content.length > 50) preview.textContent += '...';

            time.textContent = 'Just now';

            // Move to top
            const list = document.getElementById('conversationsList');
            list.insertBefore(conv, list.firstChild);

            // Add unread indicator if not current conversation
            conv.classList.add('unread');
        }

        function updateStatusIndicator(message) {
            // Find conversation with this participant
            const conversations = document.querySelectorAll('.conversation-item');
            conversations.forEach(conv => {
                // TODO: Match by participant ID
                const indicator = conv.querySelector('.status-indicator');
                if (indicator) {
                    indicator.className = 'status-indicator ' + message.status;
                }
            });
        }

        // Connect on page load
        connectWebSocket();

        // Heartbeat to keep connection alive
        setInterval(() => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ type: 'ping' }));
            }
        }, 30000);
    </script>
</body>
</html>
