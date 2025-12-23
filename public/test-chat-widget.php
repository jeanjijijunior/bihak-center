<?php
/**
 * Test Chat Widget
 * Simple page to test if chat widget appears
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user_auth.php';

// Get current user
$user = UserAuth::user();

// For testing - you can manually set session to test
// Uncomment these lines if you want to test as a specific user:
// $_SESSION['user_id'] = 2;
// $_SESSION['user_name'] = 'Test User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Chat Widget</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f7fafc;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status.success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }
        .status.error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        .info-box {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .checklist {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            padding: 10px;
            margin-bottom: 5px;
            background: #f7fafc;
            border-radius: 6px;
        }
        .checklist li:before {
            content: '✓ ';
            color: #10b981;
            font-weight: bold;
            margin-right: 8px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Chat Widget Test Page</h1>

        <!-- Session Status -->
        <?php if ($user): ?>
            <div class="status success">
                <strong>✓ You are logged in!</strong><br>
                User ID: <?php echo $user['id']; ?><br>
                Name: <?php echo htmlspecialchars($user['name']); ?><br>
                Email: <?php echo htmlspecialchars($user['email']); ?>
            </div>

            <div class="info-box">
                <strong>💬 Chat Widget Status:</strong><br>
                The floating chat button should appear in the <strong>bottom-right corner</strong> of this page.<br>
                Look for a purple circular button with a chat icon (💬).
            </div>

            <div class="info-box">
                <strong>🔍 Troubleshooting Checklist:</strong>
                <ul class="checklist">
                    <li>You are logged in (verified above)</li>
                    <li>Chat widget file exists at: includes/chat_widget.php</li>
                    <li>Widget is included at bottom of this page</li>
                    <li>Check browser console (F12) for JavaScript errors</li>
                    <li>Look in bottom-right corner of the page</li>
                    <li>Try scrolling down if page is too small</li>
                </ul>
            </div>

            <h3>🧪 Test Actions:</h3>
            <a href="profile.php" class="btn">Go to Profile Page</a>
            <a href="messages/inbox.php" class="btn">Open Full Inbox</a>
            <a href="logout.php" class="btn" style="background: #dc2626;">Logout</a>

            <h3>📊 Debug Information:</h3>
            <div class="info-box">
                <strong>Session Variables:</strong><br>
                <pre><?php
                echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
                echo "user_name: " . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'NOT SET') . "\n";
                echo "admin_id: " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'NOT SET') . "\n";
                echo "sponsor_id: " . (isset($_SESSION['sponsor_id']) ? $_SESSION['sponsor_id'] : 'NOT SET') . "\n";
                ?></pre>
            </div>

        <?php else: ?>
            <div class="status error">
                <strong>✗ You are NOT logged in</strong><br>
                The chat widget only appears for authenticated users.
            </div>

            <div class="info-box">
                <strong>To test the chat widget:</strong>
                <ol>
                    <li>Login to your account</li>
                    <li>Return to this page</li>
                    <li>Look for the floating chat button in bottom-right corner</li>
                </ol>
            </div>

            <a href="login.php" class="btn">Go to Login Page</a>
        <?php endif; ?>

        <h3>📖 Instructions:</h3>
        <div class="info-box">
            <ol>
                <li><strong>Make sure you're logged in</strong> (see status above)</li>
                <li><strong>Look at the bottom-right corner</strong> of this page</li>
                <li><strong>You should see a purple circular button</strong> with a chat icon</li>
                <li><strong>Click the button</strong> to open the chat widget</li>
                <li><strong>If you don't see it:</strong>
                    <ul>
                        <li>Check if you're logged in</li>
                        <li>Press F12 to open browser console</li>
                        <li>Look for any JavaScript errors</li>
                        <li>Try a hard refresh (Ctrl+Shift+R)</li>
                        <li>Check if WebSocket server is running (see below)</li>
                    </ul>
                </li>
            </ol>
        </div>

        <h3>🔧 WebSocket Server Check:</h3>
        <div class="info-box">
            The chat widget needs the WebSocket server to be running for real-time features.<br><br>
            <strong>To start the server:</strong>
            <pre style="background: #2d3748; color: #f7fafc; padding: 15px; border-radius: 6px; overflow-x: auto;">
cd c:\xampp\htdocs\bihak-center\websocket
npm start</pre>
            <strong>To check if it's running:</strong>
            <pre style="background: #2d3748; color: #f7fafc; padding: 15px; border-radius: 6px; overflow-x: auto;">
netstat -an | findstr ":8080"</pre>
            You should see: <code>LISTENING</code>
        </div>
    </div>

    <!-- Include Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>

    <script>
        // Debug script to check if widget loaded
        window.addEventListener('DOMContentLoaded', () => {
            const widget = document.getElementById('chatWidget');
            if (widget) {
                console.log('✓ Chat widget element found!');
                console.log('Widget:', widget);
            } else {
                console.error('✗ Chat widget element NOT found!');
                console.log('Check if user is logged in');
            }

            // Log chat widget state
            if (typeof chatWidget !== 'undefined') {
                console.log('Chat widget state:', chatWidget);
            }
        });
    </script>
</body>
</html>
