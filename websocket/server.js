/**
 * Bihak Center WebSocket Server
 *
 * Real-time messaging server for:
 * - Direct messages
 * - Team chats
 * - Typing indicators
 * - Online status
 * - Message notifications
 *
 * @author Claude
 * @version 1.0
 * @date 2025-11-20
 */

const WebSocket = require('ws');
const mysql = require('mysql2/promise');
const http = require('http');
require('dotenv').config();

// Configuration
const PORT = process.env.WS_PORT || 8080;
const HOST = process.env.WS_HOST || 'localhost';

// Database configuration
const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'bihak',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

// Create database connection pool
const pool = mysql.createPool(dbConfig);

// Create HTTP server
const server = http.createServer((req, res) => {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('Bihak Center WebSocket Server Running\n');
});

// Create WebSocket server with configuration
const wss = new WebSocket.Server({
    server,
    clientTracking: true,
    perMessageDeflate: false, // Disable compression for lower latency
    maxPayload: 100 * 1024 // 100KB max message size
});

// Connected clients map: { userId: { ws: WebSocket, type: 'user'|'admin'|'mentor', id: number, isAlive: boolean } }
const clients = new Map();

// Conversation subscriptions: { conversationId: Set<userId> }
const conversationSubscriptions = new Map();

console.log(`🚀 Starting Bihak Center WebSocket Server...`);

/**
 * Broadcast message to specific conversation participants
 */
function broadcastToConversation(conversationId, message, excludeUserId = null) {
    const subscribers = conversationSubscriptions.get(conversationId);
    if (!subscribers) return;

    const messageStr = JSON.stringify(message);

    subscribers.forEach(userId => {
        if (userId === excludeUserId) return;

        const client = clients.get(userId);
        if (client && client.ws.readyState === WebSocket.OPEN) {
            client.ws.send(messageStr);
        }
    });
}

/**
 * Send message to specific user
 */
function sendToUser(userId, message) {
    const client = clients.get(userId);
    if (client && client.ws.readyState === WebSocket.OPEN) {
        client.ws.send(JSON.stringify(message));
    }
}

/**
 * Get user's conversations from database
 */
async function getUserConversations(participantType, participantId) {
    const userId = (participantType === 'user') ? participantId : null;
    const adminId = (participantType === 'admin') ? participantId : null;
    const mentorId = (participantType === 'mentor') ? participantId : null;

    const [rows] = await pool.query(`
        SELECT DISTINCT c.id
        FROM conversations c
        INNER JOIN conversation_participants cp ON cp.conversation_id = c.id
        WHERE cp.participant_type = ?
        AND cp.user_id <=> ?
        AND cp.admin_id <=> ?
        AND cp.mentor_id <=> ?
    `, [participantType, userId, adminId, mentorId]);

    return rows.map(row => row.id);
}

/**
 * Update user's online status in database
 */
async function updateOnlineStatus(participantType, participantId, status) {
    const userId = (participantType === 'user') ? participantId : null;
    const adminId = (participantType === 'admin') ? participantId : null;
    const mentorId = (participantType === 'mentor') ? participantId : null;
    const isOnline = (status === 'online') ? 1 : 0;

    await pool.query(`
        INSERT INTO user_online_status
        (status_type, user_id, admin_id, mentor_id, is_online, last_activity)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE is_online = ?, last_activity = NOW()
    `, [participantType, userId, adminId, mentorId, isOnline, isOnline]);
}

/**
 * Get participants of a conversation
 */
async function getConversationParticipants(conversationId) {
    const [rows] = await pool.query(`
        SELECT
            cp.participant_type,
            CASE
                WHEN cp.participant_type = 'user' THEN cp.user_id
                WHEN cp.participant_type = 'admin' THEN cp.admin_id
                WHEN cp.participant_type = 'mentor' THEN cp.mentor_id
            END as participant_id
        FROM conversation_participants cp
        WHERE cp.conversation_id = ?
    `, [conversationId]);

    return rows.map(row => `${row.participant_type}_${row.participant_id}`);
}

/**
 * Notify conversation participants about status change
 */
async function notifyParticipantsStatusChange(userId, status) {
    const client = clients.get(userId);
    if (!client) return;

    // Get all conversations this user is part of
    const conversationIds = await getUserConversations(client.type, client.id);

    // For each conversation, notify other participants
    for (const convId of conversationIds) {
        broadcastToConversation(convId, {
            type: 'status_change',
            user_id: userId,
            participant_type: client.type,
            participant_id: client.id,
            status: status,
            timestamp: new Date().toISOString()
        }, userId);
    }
}

// WebSocket connection handler
wss.on('connection', (ws, req) => {
    console.log('📱 New WebSocket connection');

    let userId = null;
    let authenticated = false;

    // Handle incoming messages
    ws.on('message', async (data) => {
        try {
            const message = JSON.parse(data.toString());
            console.log('📩 Received:', message.type);

            switch (message.type) {
                case 'auth':
                    // Authenticate user
                    if (!message.participant_type || !message.participant_id) {
                        ws.send(JSON.stringify({
                            type: 'error',
                            message: 'Invalid authentication data'
                        }));
                        break;
                    }

                    userId = `${message.participant_type}_${message.participant_id}`;

                    // Store client connection
                    clients.set(userId, {
                        ws: ws,
                        type: message.participant_type,
                        id: message.participant_id,
                        isAlive: true
                    });

                    authenticated = true;

                    // Get user's conversations and subscribe
                    const conversationIds = await getUserConversations(
                        message.participant_type,
                        message.participant_id
                    );

                    for (const convId of conversationIds) {
                        if (!conversationSubscriptions.has(convId)) {
                            conversationSubscriptions.set(convId, new Set());
                        }
                        conversationSubscriptions.get(convId).add(userId);
                    }

                    // Update online status
                    await updateOnlineStatus(message.participant_type, message.participant_id, 'online');

                    // Notify others
                    await notifyParticipantsStatusChange(userId, 'online');

                    ws.send(JSON.stringify({
                        type: 'auth_success',
                        user_id: userId,
                        conversations: conversationIds
                    }));

                    console.log(`✅ User authenticated: ${userId}`);
                    break;

                case 'message':
                    // Handle new message
                    if (!authenticated) {
                        ws.send(JSON.stringify({ type: 'error', message: 'Not authenticated' }));
                        break;
                    }

                    const { conversation_id, content, reply_to_id } = message;

                    // Save message to database
                    const client = clients.get(userId);
                    const user_id = (client.type === 'user') ? client.id : null;
                    const admin_id = (client.type === 'admin') ? client.id : null;
                    const mentor_id = (client.type === 'mentor') ? client.id : null;

                    const [result] = await pool.query(`
                        INSERT INTO messages
                        (conversation_id, sender_type, sender_id, sender_admin_id, sender_mentor_id, message_text, parent_message_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    `, [conversation_id, client.type, user_id, admin_id, mentor_id, content, reply_to_id]);

                    const messageId = result.insertId;

                    // Get sender name
                    let senderName = '';
                    if (client.type === 'user') {
                        const [userRows] = await pool.query('SELECT full_name FROM users WHERE id = ?', [client.id]);
                        senderName = userRows[0]?.full_name || 'Unknown';
                    } else if (client.type === 'admin') {
                        const [adminRows] = await pool.query('SELECT full_name FROM admins WHERE id = ?', [client.id]);
                        senderName = adminRows[0]?.full_name || 'Unknown';
                    } else if (client.type === 'mentor') {
                        const [mentorRows] = await pool.query('SELECT full_name FROM sponsors WHERE id = ?', [client.id]);
                        senderName = mentorRows[0]?.full_name || 'Unknown';
                    }

                    // Broadcast to conversation participants
                    broadcastToConversation(conversation_id, {
                        type: 'new_message',
                        message_id: messageId,
                        conversation_id: conversation_id,
                        sender_type: client.type,
                        sender_id: client.id,
                        sender_name: senderName,
                        content: content,
                        reply_to_id: reply_to_id,
                        created_at: new Date().toISOString()
                    });

                    // Confirm to sender
                    ws.send(JSON.stringify({
                        type: 'message_sent',
                        message_id: messageId,
                        temp_id: message.temp_id // Echo back for client-side matching
                    }));

                    console.log(`💬 Message sent in conversation ${conversation_id}`);
                    break;

                case 'typing_start':
                    if (!authenticated) break;

                    broadcastToConversation(message.conversation_id, {
                        type: 'user_typing',
                        conversation_id: message.conversation_id,
                        user_id: userId,
                        participant_type: clients.get(userId).type,
                        participant_id: clients.get(userId).id,
                        is_typing: true
                    }, userId);
                    break;

                case 'typing_stop':
                    if (!authenticated) break;

                    broadcastToConversation(message.conversation_id, {
                        type: 'user_typing',
                        conversation_id: message.conversation_id,
                        user_id: userId,
                        participant_type: clients.get(userId).type,
                        participant_id: clients.get(userId).id,
                        is_typing: false
                    }, userId);
                    break;

                case 'subscribe_conversation':
                    // Subscribe to a conversation (for newly created conversations)
                    if (!authenticated) break;

                    const convId = message.conversation_id;
                    if (!conversationSubscriptions.has(convId)) {
                        conversationSubscriptions.set(convId, new Set());
                    }
                    conversationSubscriptions.get(convId).add(userId);

                    ws.send(JSON.stringify({
                        type: 'subscribed',
                        conversation_id: convId
                    }));
                    break;

                case 'ping':
                    // Heartbeat to keep connection alive
                    ws.send(JSON.stringify({ type: 'pong' }));
                    break;

                default:
                    ws.send(JSON.stringify({
                        type: 'error',
                        message: 'Unknown message type'
                    }));
            }

        } catch (error) {
            console.error('❌ Error processing message:', error);
            ws.send(JSON.stringify({
                type: 'error',
                message: 'Error processing message'
            }));
        }
    });

    // Handle connection close
    ws.on('close', async () => {
        console.log('📴 WebSocket connection closed');

        if (authenticated && userId) {
            const client = clients.get(userId);

            // Update status to offline
            if (client) {
                await updateOnlineStatus(client.type, client.id, 'offline');
                await notifyParticipantsStatusChange(userId, 'offline');
            }

            // Remove from clients
            clients.delete(userId);

            // Remove from conversation subscriptions
            conversationSubscriptions.forEach((subscribers, convId) => {
                subscribers.delete(userId);
                if (subscribers.size === 0) {
                    conversationSubscriptions.delete(convId);
                }
            });
        }
    });

    // Handle errors
    ws.on('error', (error) => {
        console.error('❌ WebSocket error:', error);
    });

    // Handle WebSocket-level pong (response to ping)
    ws.on('pong', () => {
        if (userId && clients.has(userId)) {
            const client = clients.get(userId);
            client.isAlive = true;
        }
    });
});

// WebSocket Keepalive - Ping all clients every 30 seconds
const pingInterval = setInterval(() => {
    wss.clients.forEach((ws) => {
        if (ws.readyState === WebSocket.OPEN) {
            // Find the userId for this websocket
            let userIdForWs = null;
            for (const [uid, client] of clients.entries()) {
                if (client.ws === ws) {
                    userIdForWs = uid;
                    break;
                }
            }

            if (userIdForWs) {
                const client = clients.get(userIdForWs);

                // If client didn't respond to last ping, terminate
                if (client.isAlive === false) {
                    console.log(`⚠️ Terminating unresponsive connection: ${userIdForWs}`);
                    return ws.terminate();
                }

                // Mark as waiting for pong and send ping
                client.isAlive = false;
                ws.ping();
            }
        }
    });
}, 30000); // Every 30 seconds

// Cleanup ping interval on server shutdown
wss.on('close', () => {
    clearInterval(pingInterval);
});

// Cleanup stale typing indicators every 10 seconds
setInterval(async () => {
    try {
        await pool.query(`
            DELETE FROM typing_indicators
            WHERE started_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)
        `);
    } catch (error) {
        console.error('Error cleaning typing indicators:', error);
    }
}, 10000);

// Cleanup stale online statuses every minute
setInterval(async () => {
    try {
        await pool.query(`
            UPDATE user_online_status
            SET status = 'offline'
            WHERE status = 'online'
            AND last_seen_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        `);
    } catch (error) {
        console.error('Error cleaning online statuses:', error);
    }
}, 60000);

// Start server
server.listen(PORT, HOST, () => {
    console.log(`✅ WebSocket server is running on ws://${HOST}:${PORT}`);
    console.log(`📊 Database: ${dbConfig.database}@${dbConfig.host}`);
    console.log(`👥 Ready to accept connections`);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
    console.log('🛑 SIGTERM signal received: closing WebSocket server');

    // Notify all clients
    clients.forEach(client => {
        client.ws.close(1000, 'Server shutting down');
    });

    // Close database pool
    await pool.end();

    server.close(() => {
        console.log('✅ WebSocket server closed');
        process.exit(0);
    });
});

process.on('SIGINT', async () => {
    console.log('🛑 SIGINT signal received: closing WebSocket server');

    clients.forEach(client => {
        client.ws.close(1000, 'Server shutting down');
    });

    await pool.end();

    server.close(() => {
        console.log('✅ WebSocket server closed');
        process.exit(0);
    });
});
