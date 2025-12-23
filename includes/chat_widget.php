<?php
/**
 * Chat Widget Component
 * WhatsApp/Messenger-style floating chat interface
 * Can be included in any page for users, mentors, and admins
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine participant type and ID
$chat_participant_type = null;
$chat_participant_id = null;
$chat_participant_name = '';

if (isset($_SESSION['user_id'])) {
    // For users, check if profile is approved
    $user_status = $_SESSION['user_status'] ?? 'pending';
    if ($user_status !== 'approved') {
        return; // Don't show chat for non-approved users
    }
    $chat_participant_type = 'user';
    $chat_participant_id = $_SESSION['user_id'];
    $chat_participant_name = $_SESSION['user_name'] ?? 'User';
} elseif (isset($_SESSION['admin_id'])) {
    $chat_participant_type = 'admin';
    $chat_participant_id = $_SESSION['admin_id'];
    $chat_participant_name = $_SESSION['admin_name'] ?? 'Admin';
} elseif (isset($_SESSION['sponsor_id'])) {
    $chat_participant_type = 'mentor';
    $chat_participant_id = $_SESSION['sponsor_id'];
    $chat_participant_name = $_SESSION['sponsor_name'] ?? 'Mentor';
}

// Only show chat widget if user is authenticated
if (!$chat_participant_type) {
    return;
}

// Get base path for assets and API
$current_dir = dirname($_SERVER['SCRIPT_FILENAME']);
$is_in_public = (basename($current_dir) === 'public');
$is_in_admin = (basename($current_dir) === 'admin');

if ($is_in_admin) {
    $widget_assets_path = '../../assets/';
    $widget_api_path = '../../api/messaging/';
} elseif ($is_in_public) {
    $widget_assets_path = '../assets/';
    $widget_api_path = '../api/messaging/';
} else {
    $widget_assets_path = 'assets/';
    $widget_api_path = 'api/messaging/';
}
?>

<!-- Chat Widget Container -->
<div id="chatWidget" class="chat-widget">
    <!-- Chat Button (minimized state) -->
    <button id="chatToggle" class="chat-toggle" onclick="toggleChatWidget()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
        </svg>
        <span id="chatUnreadBadge" class="chat-unread-badge" style="display: none;">0</span>
    </button>

    <!-- Chat Window (expanded state) -->
    <div id="chatWindow" class="chat-window" style="display: none;">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-info">
                <h3>Messages</h3>
                <span id="chatConnectionStatus" class="chat-status">●</span>
            </div>
            <div class="chat-header-actions">
                <button onclick="openFullInbox()" class="chat-action-btn" title="Open full inbox">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                    </svg>
                </button>
                <button onclick="toggleChatWidget()" class="chat-action-btn" title="Minimize">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13H5v-2h14v2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Tabs -->
        <div class="chat-tabs">
            <button class="chat-tab active" data-tab="conversations" onclick="switchChatTab('conversations')">
                Conversations
                <span id="conversationsUnreadBadge" class="tab-badge" style="display: none;">0</span>
            </button>
            <button class="chat-tab" data-tab="active-chat" onclick="switchChatTab('active-chat')" style="display: none;">
                <span id="activeChatName">Chat</span>
            </button>
        </div>

        <!-- Conversations List View -->
        <div id="conversationsView" class="chat-view active">
            <div class="chat-search">
                <input type="text" id="chatSearchInput" placeholder="Search conversations..." onkeyup="filterConversations()">
                <button class="new-conversation-btn" onclick="showNewConversationView()" title="Start new conversation">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                </button>
            </div>
            <div id="conversationsList" class="conversations-list">
                <div class="chat-loading">Loading conversations...</div>
            </div>
        </div>

        <!-- New Conversation View -->
        <div id="newConversationView" class="chat-view">
            <div class="new-conversation-header">
                <button class="back-btn" onclick="backToConversations()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                </button>
                <h3>New Conversation</h3>
            </div>
            <div class="chat-search">
                <input type="text" id="newChatSearchInput" placeholder="Search people..." onkeyup="searchUsers()" autocomplete="off">
            </div>
            <div id="suggestedContacts" class="contacts-list">
                <div class="contacts-section-title">Suggested Contacts</div>
                <div class="chat-loading">Loading contacts...</div>
            </div>
        </div>

        <!-- Active Chat View -->
        <div id="activeChatView" class="chat-view">
            <div id="messagesContainer" class="messages-container">
                <div class="no-chat-selected">
                    Select a conversation to start messaging
                </div>
            </div>

            <!-- Typing Indicator -->
            <div id="typingIndicator" class="typing-indicator" style="display: none;">
                <span class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span id="typingUserName">Someone</span> is typing...
            </div>

            <!-- Message Input -->
            <div id="messageInputContainer" class="message-input-container" style="display: none;">
                <textarea
                    id="messageInput"
                    placeholder="Type a message..."
                    rows="1"
                    onkeydown="handleMessageKeydown(event)"
                    oninput="handleTyping()"
                ></textarea>
                <button id="sendMessageBtn" onclick="sendMessage()" class="send-btn" title="Send message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Chat Widget Styles -->
<style>
.chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.chat-toggle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.3s ease;
}

.chat-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.chat-unread-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #f56565;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    border: 2px solid white;
}

.chat-window {
    width: 380px;
    height: 600px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header-info h3 {
    margin: 0;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.chat-status {
    font-size: 0.6rem;
    color: #48bb78;
}

.chat-status.disconnected {
    color: #f56565;
}

.chat-header-actions {
    display: flex;
    gap: 8px;
}

.chat-action-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.chat-action-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.chat-tabs {
    display: flex;
    background: #f7fafc;
    border-bottom: 1px solid #e2e8f0;
}

.chat-tab {
    flex: 1;
    padding: 12px;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 500;
    color: #718096;
    position: relative;
    transition: all 0.2s;
}

.chat-tab.active {
    color: #667eea;
    background: white;
}

.chat-tab:hover {
    background: #edf2f7;
}

.tab-badge {
    display: inline-block;
    background: #f56565;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.7rem;
    margin-left: 4px;
}

.chat-view {
    flex: 1;
    display: none;
    flex-direction: column;
    overflow: hidden;
}

.chat-view.active {
    display: flex;
}

.chat-search {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    align-items: center;
}

.chat-search input {
    flex: 1;
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
}

.chat-search input:focus {
    outline: none;
    border-color: #667eea;
}

.new-conversation-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.new-conversation-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.new-conversation-header {
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.new-conversation-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #2d3748;
}

.back-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f7fafc;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a5568;
    transition: all 0.2s;
}

.back-btn:hover {
    background: #e2e8f0;
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
}

.contacts-section-title {
    padding: 12px 16px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #f7fafc;
}

.contact-item {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    gap: 12px;
    align-items: center;
}

.contact-item:hover {
    background: #f7fafc;
}

.contact-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
    flex-shrink: 0;
}

.contact-info {
    flex: 1;
    min-width: 0;
}

.contact-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.95rem;
    margin-bottom: 2px;
}

.contact-role {
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 12px;
    display: inline-block;
    font-weight: 500;
}

.contact-email {
    font-size: 0.8rem;
    color: #718096;
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.conversation-item:hover {
    background: #f7fafc;
}

.conversation-item.unread {
    background: #edf5ff;
    border-left: 4px solid #667eea;
}

.conversation-item.unread .conversation-name {
    font-weight: 700;
    color: #667eea;
}

.conversation-unread-badge {
    min-width: 24px;
    height: 24px;
    border-radius: 12px;
    background: #f59e0b;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    flex-shrink: 0;
}

.conversation-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
    flex-shrink: 0;
    position: relative;
}

.online-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #48bb78;
    border: 2px solid white;
}

.conversation-content {
    flex: 1;
    min-width: 0;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.conversation-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.95rem;
}

.conversation-time {
    font-size: 0.75rem;
    color: #a0aec0;
}

.conversation-preview {
    font-size: 0.85rem;
    color: #718096;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-item.unread .conversation-preview {
    font-weight: 600;
    color: #2d3748;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f7fafc;
}

.no-chat-selected {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #a0aec0;
    text-align: center;
    padding: 20px;
}

.message-group {
    margin-bottom: 16px;
}

.message-date-separator {
    text-align: center;
    color: #a0aec0;
    font-size: 0.75rem;
    margin: 16px 0;
}

.message {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message.sent {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    flex-shrink: 0;
}

.message-bubble {
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 16px;
    background: white;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.message.sent .message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.message-text {
    font-size: 0.9rem;
    line-height: 1.4;
    word-wrap: break-word;
}

.message-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
    font-size: 0.7rem;
    opacity: 0.7;
}

.message.sent .message-meta {
    justify-content: flex-end;
}

.message-status {
    display: flex;
    align-items: center;
}

.typing-indicator {
    padding: 8px 16px;
    background: white;
    border-top: 1px solid #e2e8f0;
    font-size: 0.85rem;
    color: #718096;
    display: flex;
    align-items: center;
    gap: 8px;
}

.typing-dots {
    display: flex;
    gap: 3px;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #cbd5e0;
    animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-10px);
    }
}

.message-input-container {
    padding: 12px;
    background: white;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

#messageInput {
    flex: 1;
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 20px;
    font-size: 0.9rem;
    font-family: inherit;
    resize: none;
    max-height: 100px;
    overflow-y: auto;
}

#messageInput:focus {
    outline: none;
    border-color: #667eea;
}

.send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
}

.send-btn:hover {
    transform: scale(1.1);
}

.send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.chat-loading {
    padding: 40px 20px;
    text-align: center;
    color: #a0aec0;
}

/* Scrollbar styling */
.conversations-list::-webkit-scrollbar,
.messages-container::-webkit-scrollbar {
    width: 6px;
}

.conversations-list::-webkit-scrollbar-track,
.messages-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.conversations-list::-webkit-scrollbar-thumb,
.messages-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.conversations-list::-webkit-scrollbar-thumb:hover,
.messages-container::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .chat-window {
        width: calc(100vw - 40px);
        height: calc(100vh - 40px);
        border-radius: 12px;
    }
}
</style>

<!-- Chat Widget JavaScript -->
<script>
// Chat Widget State
let chatWidget = {
    isOpen: false,
    activeConversationId: null,
    conversations: [],
    messages: {},
    unreadCount: 0,
    ws: null,
    typingTimeout: null,
    messagePollingInterval: null,
    participantType: '<?php echo $chat_participant_type; ?>',
    participantId: <?php echo $chat_participant_id; ?>,
    participantName: '<?php echo addslashes($chat_participant_name); ?>',
    apiBasePath: '<?php echo $widget_api_path; ?>'
};

// Toggle chat widget
function toggleChatWidget() {
    const toggle = document.getElementById('chatToggle');
    const window = document.getElementById('chatWindow');

    chatWidget.isOpen = !chatWidget.isOpen;

    if (chatWidget.isOpen) {
        toggle.style.display = 'none';
        window.style.display = 'flex';
        loadConversations();
        // Only connect WebSocket if server is available
        // connectWebSocket(); // Disabled by default - uncomment if WebSocket server is running
    } else {
        toggle.style.display = 'flex';
        window.style.display = 'none';
    }
}

// Switch between tabs
function switchChatTab(tab) {
    document.querySelectorAll('.chat-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.chat-view').forEach(v => v.classList.remove('active'));

    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    document.getElementById(tab === 'conversations' ? 'conversationsView' : 'activeChatView').classList.add('active');

    // Stop message polling when switching back to conversations
    if (tab === 'conversations') {
        stopMessagePolling();
        chatWidget.activeConversationId = null;
    }
}

// Load conversations
async function loadConversations() {
    try {
        console.log('Loading conversations...');
        const response = await fetch(chatWidget.apiBasePath + 'conversations.php');

        console.log('Conversations response status:', response.status);

        // Check if response is OK
        if (!response.ok) {
            console.error('API response not OK:', response.status, response.statusText);
            const text = await response.text();
            console.error('Response body:', text);
            const container = document.getElementById('conversationsList');
            container.innerHTML = '<div class="chat-loading" style="color: #dc2626;">Error loading conversations</div>';
            return;
        }

        const text = await response.text();
        console.log('Conversations response text:', text.substring(0, 200));

        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('Failed to parse JSON:', parseError);
            console.error('Response text:', text);
            const container = document.getElementById('conversationsList');
            container.innerHTML = '<div class="chat-loading" style="color: #dc2626;">Invalid response format</div>';
            return;
        }

        console.log('Parsed conversations data:', data);

        if (data.success) {
            // API returns 'data' not 'conversations'
            chatWidget.conversations = data.data || [];
            console.log('Found conversations:', chatWidget.conversations.length);
            // Calculate unread count from conversations
            chatWidget.unreadCount = (data.data || []).reduce((total, conv) => {
                return total + (conv.unread_count || 0);
            }, 0);
            renderConversations();
            updateUnreadBadge();
        } else {
            console.error('API returned error:', data.message);
            const container = document.getElementById('conversationsList');
            container.innerHTML = `<div class="chat-loading" style="color: #dc2626;">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Failed to load conversations:', error);
        const container = document.getElementById('conversationsList');
        container.innerHTML = '<div class="chat-loading" style="color: #dc2626;">Network error</div>';
    }
}

// Render conversations list
function renderConversations() {
    const container = document.getElementById('conversationsList');

    if (chatWidget.conversations.length === 0) {
        container.innerHTML = '<div class="chat-loading">No conversations yet. Click + to start!</div>';
        return;
    }

    container.innerHTML = chatWidget.conversations.map(conv => {
        // Get display name from participants
        const otherParticipant = (conv.participants || []).find(p =>
            !(p.participant_type === chatWidget.participantType && p.id == chatWidget.participantId)
        );
        const displayName = (otherParticipant && otherParticipant.name) ? otherParticipant.name : 'Unknown';
        const initial = displayName.charAt(0).toUpperCase();

        return `
            <div class="conversation-item ${conv.unread_count > 0 ? 'unread' : ''}"
                 onclick="openConversation(${conv.id}, '${escapeHtml(displayName)}')">
                <div class="conversation-avatar">${initial}</div>
                <div class="conversation-content">
                    <div class="conversation-header">
                        <span class="conversation-name">${escapeHtml(displayName)}</span>
                        <span class="conversation-time">${conv.last_message_at ? formatTime(conv.last_message_at) : ''}</span>
                    </div>
                    <div class="conversation-preview">${escapeHtml(conv.last_message || 'No messages yet')}</div>
                </div>
                ${conv.unread_count > 0 ? `<div class="conversation-unread-badge">${conv.unread_count}</div>` : ''}
            </div>
        `;
    }).join('');
}

// Open a conversation
async function openConversation(conversationId, displayName = 'Chat') {
    chatWidget.activeConversationId = conversationId;

    // Switch to chat view
    switchChatTab('active-chat');

    // Update active chat name
    document.getElementById('activeChatName').textContent = displayName;
    document.querySelector('[data-tab="active-chat"]').style.display = 'flex';

    // Load messages (initial load - use full render)
    await loadMessages(conversationId, true);

    // Show message input
    document.getElementById('messageInputContainer').style.display = 'flex';

    // Mark as read
    markConversationAsRead(conversationId);

    // Start polling for new messages in this conversation (every 3 seconds)
    startMessagePolling(conversationId);
}

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

// Stop message polling
function stopMessagePolling() {
    if (chatWidget.messagePollingInterval) {
        clearInterval(chatWidget.messagePollingInterval);
        chatWidget.messagePollingInterval = null;
    }
}

// Load messages for a conversation
async function loadMessages(conversationId, isInitialLoad = false) {
    try {
        const response = await fetch(`${chatWidget.apiBasePath}messages.php?conversation_id=${conversationId}`);
        const data = await response.json();

        if (data.success) {
            const newMessages = data.data || data.messages || [];
            const existingMessages = chatWidget.messages[conversationId] || [];

            // Check if there are new messages
            if (newMessages.length > existingMessages.length || isInitialLoad) {
                chatWidget.messages[conversationId] = newMessages;

                if (isInitialLoad) {
                    // Full render on initial load
                    renderMessages(conversationId);
                } else {
                    // Only append new messages
                    const newCount = newMessages.length - existingMessages.length;
                    if (newCount > 0) {
                        appendNewMessages(conversationId, newMessages.slice(-newCount));
                    }
                }
            }
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
    }
}

// Render messages
function renderMessages(conversationId) {
    const container = document.getElementById('messagesContainer');
    const messages = chatWidget.messages[conversationId] || [];

    if (messages.length === 0) {
        container.innerHTML = '<div class="no-chat-selected">No messages yet. Start the conversation!</div>';
        return;
    }

    container.innerHTML = messages.map((msg, index) => {
        // Determine the actual sender ID based on sender_type
        let actualSenderId;
        if (msg.sender_type === 'user') {
            actualSenderId = msg.sender_id;
        } else if (msg.sender_type === 'admin') {
            actualSenderId = msg.sender_admin_id;
        } else if (msg.sender_type === 'mentor') {
            actualSenderId = msg.sender_mentor_id;
        }

        const isSent = (msg.sender_type === chatWidget.participantType && actualSenderId === chatWidget.participantId);

        // Check if we should show avatar (different sender than previous message)
        let prevSenderId;
        if (index > 0) {
            const prevMsg = messages[index - 1];
            if (prevMsg.sender_type === 'user') {
                prevSenderId = prevMsg.sender_id;
            } else if (prevMsg.sender_type === 'admin') {
                prevSenderId = prevMsg.sender_admin_id;
            } else if (prevMsg.sender_type === 'mentor') {
                prevSenderId = prevMsg.sender_mentor_id;
            }
        }
        const showAvatar = index === 0 || actualSenderId !== prevSenderId;

        return `
            <div class="message ${isSent ? 'sent' : 'received'}">
                ${showAvatar ? `
                    <div class="message-avatar">
                        ${msg.sender_name.charAt(0).toUpperCase()}
                    </div>
                ` : '<div style="width: 32px;"></div>'}
                <div class="message-bubble">
                    <div class="message-text">${escapeHtml(msg.content || msg.message_text || '')}</div>
                    <div class="message-meta">
                        <span>${formatTime(msg.created_at)}</span>
                        ${isSent ? `<span class="message-status">${msg.is_read ? '✓✓' : '✓'}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Only auto-scroll to bottom if user was already at bottom (tolerance of 100px)
    const isAtBottom = container.scrollHeight - container.clientHeight - container.scrollTop < 100;
    if (isAtBottom) {
        container.scrollTop = container.scrollHeight;
    }
}

// Append new messages without re-rendering entire conversation (prevents blinking)
function appendNewMessages(conversationId, newMessages) {
    const container = document.getElementById('messagesContainer');
    const allMessages = chatWidget.messages[conversationId] || [];

    // Store scroll position
    const wasAtBottom = container.scrollHeight - container.clientHeight - container.scrollTop < 100;

    // Get the index of the last existing message to determine avatar logic
    const existingCount = allMessages.length - newMessages.length;

    newMessages.forEach((msg, idx) => {
        const globalIndex = existingCount + idx;

        // Determine the actual sender ID based on sender_type
        let actualSenderId;
        if (msg.sender_type === 'user') {
            actualSenderId = msg.sender_id;
        } else if (msg.sender_type === 'admin') {
            actualSenderId = msg.sender_admin_id;
        } else if (msg.sender_type === 'mentor') {
            actualSenderId = msg.sender_mentor_id;
        }

        const isSent = (msg.sender_type === chatWidget.participantType && actualSenderId === chatWidget.participantId);

        // Check if we should show avatar (different sender than previous message)
        let prevSenderId;
        if (globalIndex > 0) {
            const prevMsg = allMessages[globalIndex - 1];
            if (prevMsg.sender_type === 'user') {
                prevSenderId = prevMsg.sender_id;
            } else if (prevMsg.sender_type === 'admin') {
                prevSenderId = prevMsg.sender_admin_id;
            } else if (prevMsg.sender_type === 'mentor') {
                prevSenderId = prevMsg.sender_mentor_id;
            }
        }
        const showAvatar = globalIndex === 0 || actualSenderId !== prevSenderId;

        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
        messageDiv.innerHTML = `
            ${showAvatar ? `
                <div class="message-avatar">
                    ${msg.sender_name.charAt(0).toUpperCase()}
                </div>
            ` : '<div style="width: 32px;"></div>'}
            <div class="message-bubble">
                <div class="message-text">${escapeHtml(msg.content || msg.message_text || '')}</div>
                <div class="message-meta">
                    <span>${formatTime(msg.created_at)}</span>
                    ${isSent ? `<span class="message-status">${msg.is_read ? '✓✓' : '✓'}</span>` : ''}
                </div>
            </div>
        `;

        // Append to container
        container.appendChild(messageDiv);
    });

    // Only auto-scroll if user was at bottom
    if (wasAtBottom) {
        container.scrollTop = container.scrollHeight;
    }
}

// Send message
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message || !chatWidget.activeConversationId) return;

    try {
        // Send via WebSocket if connected
        if (chatWidget.ws && chatWidget.ws.readyState === WebSocket.OPEN) {
            chatWidget.ws.send(JSON.stringify({
                type: 'message',
                conversation_id: chatWidget.activeConversationId,
                content: message,
                reply_to_id: null,
                timestamp: new Date().toISOString()
            }));
        } else {
            // Fallback to HTTP
            await fetch(chatWidget.apiBasePath + 'messages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: chatWidget.activeConversationId,
                    content: message
                })
            });
        }

        input.value = '';
        input.style.height = 'auto';
    } catch (error) {
        console.error('Failed to send message:', error);
        alert('Failed to send message. Please try again.');
    }
}

// Handle message input keydown
function handleMessageKeydown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }

    // Auto-resize textarea
    event.target.style.height = 'auto';
    event.target.style.height = event.target.scrollHeight + 'px';
}

// Handle typing indicator
function handleTyping() {
    if (!chatWidget.activeConversationId) return;

    if (chatWidget.ws && chatWidget.ws.readyState === WebSocket.OPEN) {
        chatWidget.ws.send(JSON.stringify({
            type: 'typing',
            conversation_id: chatWidget.activeConversationId
        }));
    }
}

// Mark conversation as read
async function markConversationAsRead(conversationId) {
    try {
        await fetch(chatWidget.apiBasePath + 'mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: conversationId })
        });

        // Update local state
        const conv = chatWidget.conversations.find(c => c.id === conversationId);
        if (conv) {
            chatWidget.unreadCount -= conv.unread_count;
            conv.unread_count = 0;
            updateUnreadBadge();
        }
    } catch (error) {
        console.error('Failed to mark as read:', error);
    }
}

// Update unread badge
function updateUnreadBadge() {
    const badge = document.getElementById('chatUnreadBadge');
    const convBadge = document.getElementById('conversationsUnreadBadge');

    if (chatWidget.unreadCount > 0) {
        badge.textContent = chatWidget.unreadCount;
        badge.style.display = 'flex';
        convBadge.textContent = chatWidget.unreadCount;
        convBadge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
        convBadge.style.display = 'none';
    }
}

// Filter conversations
function filterConversations() {
    const search = document.getElementById('chatSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');

    items.forEach(item => {
        const name = item.querySelector('.conversation-name').textContent.toLowerCase();
        const preview = item.querySelector('.conversation-preview').textContent.toLowerCase();

        if (name.includes(search) || preview.includes(search)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Open full inbox
function openFullInbox() {
    window.open('../messages/inbox.php', '_blank');
}

// WebSocket connection
function connectWebSocket() {
    if (chatWidget.ws && chatWidget.ws.readyState === WebSocket.OPEN) {
        return;
    }

    chatWidget.ws = new WebSocket('ws://localhost:8080');

    chatWidget.ws.onopen = () => {
        console.log('Chat widget WebSocket connected');
        document.getElementById('chatConnectionStatus').classList.remove('disconnected');

        // Authenticate
        chatWidget.ws.send(JSON.stringify({
            type: 'authenticate',
            participant_type: chatWidget.participantType,
            participant_id: chatWidget.participantId
        }));
    };

    chatWidget.ws.onclose = () => {
        console.log('Chat widget WebSocket disconnected');
        document.getElementById('chatConnectionStatus').classList.add('disconnected');

        // Reconnect after 3 seconds
        setTimeout(connectWebSocket, 3000);
    };

    chatWidget.ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        handleWebSocketMessage(data);
    };
}

// Handle WebSocket messages
function handleWebSocketMessage(data) {
    switch (data.type) {
        case 'new_message':
            handleNewMessage(data.message);
            break;
        case 'typing':
            showTypingIndicator(data.sender_name);
            break;
        case 'online_status':
            updateOnlineStatus(data.participant_id, data.status);
            break;
    }
}

// Handle new message
function handleNewMessage(message) {
    const conversationId = message.conversation_id;

    // Add to messages
    if (!chatWidget.messages[conversationId]) {
        chatWidget.messages[conversationId] = [];
    }
    chatWidget.messages[conversationId].push(message);

    // Update conversation list
    const conv = chatWidget.conversations.find(c => c.id === conversationId);
    if (conv) {
        conv.last_message = message.message_text;
        conv.last_message_at = message.created_at;

        // If not viewing this conversation, increment unread
        if (conversationId !== chatWidget.activeConversationId) {
            conv.unread_count++;
            chatWidget.unreadCount++;
        }
    }

    // Re-render if viewing this conversation
    if (conversationId === chatWidget.activeConversationId) {
        renderMessages(conversationId);
    }

    // Update UI
    renderConversations();
    updateUnreadBadge();

    // Play notification sound (optional)
    // playNotificationSound();
}

// Show typing indicator
function showTypingIndicator(userName) {
    const indicator = document.getElementById('typingIndicator');
    const nameSpan = document.getElementById('typingUserName');

    nameSpan.textContent = userName;
    indicator.style.display = 'flex';

    clearTimeout(chatWidget.typingTimeout);
    chatWidget.typingTimeout = setTimeout(() => {
        indicator.style.display = 'none';
    }, 3000);
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;

    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString();
}

// ===== NEW CONVERSATION FUNCTIONALITY =====

// Show new conversation view
function showNewConversationView() {
    // Hide conversations view
    document.getElementById('conversationsView').classList.remove('active');
    // Show new conversation view
    document.getElementById('newConversationView').classList.add('active');
    // Load suggested contacts
    loadSuggestedContacts();
}

// Back to conversations list
function backToConversations() {
    document.getElementById('newConversationView').classList.remove('active');
    document.getElementById('conversationsView').classList.add('active');
    // Clear search
    document.getElementById('newChatSearchInput').value = '';
}

// Load suggested contacts (admins, mentors by default)
async function loadSuggestedContacts() {
    try {
        console.log('Loading suggested contacts...');
        const response = await fetch(chatWidget.apiBasePath + 'search_users.php');

        console.log('Response status:', response.status);

        if (!response.ok) {
            const text = await response.text();
            console.error('Failed to load contacts:', response.status, text);
            document.getElementById('suggestedContacts').innerHTML =
                '<div class="chat-loading" style="color: #dc2626;">Error loading contacts. Check console for details.</div>';
            return;
        }

        const text = await response.text();
        console.log('Response text:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            document.getElementById('suggestedContacts').innerHTML =
                '<div class="chat-loading" style="color: #dc2626;">Invalid API response</div>';
            return;
        }

        console.log('Parsed data:', data);

        if (data.success) {
            console.log('Found contacts:', data.results.length);
            renderContacts(data.results);
        } else {
            console.error('API error:', data.message);
            document.getElementById('suggestedContacts').innerHTML =
                `<div class="chat-loading" style="color: #dc2626;">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error loading contacts:', error);
        document.getElementById('suggestedContacts').innerHTML =
            '<div class="chat-loading" style="color: #dc2626;">Network error. Check console.</div>';
    }
}

// Search users
let searchTimeout = null;
async function searchUsers() {
    const searchInput = document.getElementById('newChatSearchInput');
    const query = searchInput.value.trim();

    // Clear previous timeout
    clearTimeout(searchTimeout);

    // Wait 300ms after user stops typing
    searchTimeout = setTimeout(async () => {
        try {
            const url = query ?
                `${chatWidget.apiBasePath}search_users.php?q=${encodeURIComponent(query)}` :
                `${chatWidget.apiBasePath}search_users.php`;

            const response = await fetch(url);

            if (!response.ok) {
                console.error('Search failed:', response.status);
                return;
            }

            const data = await response.json();

            if (data.success) {
                renderContacts(data.results, query);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }, 300);
}

// Render contacts list
function renderContacts(contacts, searchQuery = '') {
    const container = document.getElementById('suggestedContacts');

    if (contacts.length === 0) {
        container.innerHTML = `
            <div class="chat-loading">
                ${searchQuery ? 'No users found' : 'No contacts available'}
            </div>
        `;
        return;
    }

    // Group by type
    const admins = contacts.filter(c => c.type === 'admin');
    const mentors = contacts.filter(c => c.type === 'mentor');
    const users = contacts.filter(c => c.type === 'user');

    let html = '';

    // Show admins first
    if (admins.length > 0) {
        html += '<div class="contacts-section-title">Administrators</div>';
        html += admins.map(contact => renderContactItem(contact)).join('');
    }

    // Then mentors
    if (mentors.length > 0) {
        html += '<div class="contacts-section-title">Mentors & Sponsors</div>';
        html += mentors.map(contact => renderContactItem(contact)).join('');
    }

    // Then users
    if (users.length > 0) {
        html += '<div class="contacts-section-title">Users</div>';
        html += users.map(contact => renderContactItem(contact)).join('');
    }

    container.innerHTML = html;
}

// Render a single contact item
function renderContactItem(contact) {
    // Handle empty names
    const name = contact.name || 'Unknown';
    const initial = name.charAt(0).toUpperCase();
    const roleColor = contact.badge_color || '#667eea';

    return `
        <div class="contact-item" onclick="startConversationWith('${contact.type}', ${contact.id}, '${escapeHtml(name)}')">
            <div class="contact-avatar">${initial}</div>
            <div class="contact-info">
                <div class="contact-name">${escapeHtml(name)}</div>
                <span class="contact-role" style="background: ${roleColor}20; color: ${roleColor};">
                    ${contact.label}
                </span>
                ${contact.organization ? `<div class="contact-email">${escapeHtml(contact.organization)}</div>` : ''}
            </div>
        </div>
    `;
}

// Start conversation with a user
async function startConversationWith(participantType, participantId, participantName) {
    try {
        // Create conversation
        const response = await fetch(chatWidget.apiBasePath + 'conversations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type: 'direct',
                participants: [
                    { type: participantType, id: participantId }
                ]
            })
        });

        const data = await response.json();

        if (data.success) {
            // Go back to conversations
            backToConversations();
            // Reload conversations to show the new one
            await loadConversations();
            // Open the new conversation with participant name
            if (data.conversation_id) {
                openConversation(data.conversation_id, participantName);
            }
        } else {
            // If conversation already exists, it should return the existing conversation_id
            if (data.conversation_id) {
                backToConversations();
                await loadConversations();
                openConversation(data.conversation_id, participantName);
            } else {
                alert('Failed to start conversation: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error starting conversation:', error);
        alert('Failed to start conversation. Please try again.');
    }
}

// Auto-load conversations and connect on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        loadConversations();
    });
} else {
    loadConversations();
}

// Periodically refresh conversations (every 3 seconds for real-time updates)
const conversationInterval = setInterval(loadConversations, 3000);

// Cleanup on page unload to prevent "message port closed" errors
window.addEventListener('beforeunload', () => {
    clearInterval(conversationInterval);
});
</script>
