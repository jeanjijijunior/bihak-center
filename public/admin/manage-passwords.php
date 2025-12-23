<?php
/**
 * Admin Tool: Unified Password Management
 * Allows admin to set passwords for users, sponsors, and admins
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::init();
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$conn = getDatabaseConnection();
$success_message = '';
$error_message = '';
$active_tab = $_GET['tab'] ?? 'users';

// Handle password set request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_type']) && isset($_POST['user_id'])) {
    $user_type = $_POST['user_type'];
    $user_id = intval($_POST['user_id']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error_message = 'Password cannot be empty.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Hash password and update based on user type
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        if ($user_type === 'user') {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        } elseif ($user_type === 'sponsor') {
            $stmt = $conn->prepare("UPDATE sponsors SET password_hash = ? WHERE id = ?");
        } elseif ($user_type === 'admin') {
            $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        }

        if (isset($stmt)) {
            $stmt->bind_param('si', $password_hash, $user_id);
            if ($stmt->execute()) {
                $success_message = "Password set successfully for $user_type ID: $user_id";
            } else {
                $error_message = "Failed to set password.";
            }
            $stmt->close();
        }
    }
}

// Handle bulk password generation
if (isset($_POST['generate_all_type'])) {
    $user_type = $_POST['generate_all_type'];
    $default_password = 'Welcome@2025';
    $password_hash = password_hash($default_password, PASSWORD_BCRYPT);

    if ($user_type === 'users') {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE password IS NULL OR password = ''");
    } elseif ($user_type === 'sponsors') {
        $stmt = $conn->prepare("UPDATE sponsors SET password_hash = ? WHERE password_hash IS NULL OR password_hash = ''");
    } elseif ($user_type === 'admins') {
        $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE password_hash IS NULL OR password_hash = ''");
    }

    if (isset($stmt)) {
        $stmt->bind_param('s', $password_hash);
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $success_message = "Generated default passwords for $affected_rows $user_type. Default password: <strong>$default_password</strong>";
        } else {
            $error_message = "Failed to generate passwords.";
        }
        $stmt->close();
    }
}

// Get users without passwords
$users_query = "
    SELECT id, full_name, email, is_active, created_at
    FROM users
    WHERE password IS NULL OR password = ''
    ORDER BY created_at DESC
";
$users_without_passwords = $conn->query($users_query)->fetch_all(MYSQLI_ASSOC);

// Get sponsors without passwords
$sponsors_query = "
    SELECT id, full_name, email, role_type, status, created_at
    FROM sponsors
    WHERE password_hash IS NULL OR password_hash = ''
    ORDER BY created_at DESC
";
$sponsors_without_passwords = $conn->query($sponsors_query)->fetch_all(MYSQLI_ASSOC);

// Get admins without passwords
$admins_query = "
    SELECT id, username, email, is_active, created_at
    FROM admins
    WHERE password_hash IS NULL OR password_hash = ''
    ORDER BY created_at DESC
";
$admins_without_passwords = $conn->query($admins_query)->fetch_all(MYSQLI_ASSOC);

closeDatabaseConnection($conn);

$total_without_passwords = count($users_without_passwords) + count($sponsors_without_passwords) + count($admins_without_passwords);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Management - Admin</title>
    <link rel="stylesheet" href="../../assets/css/header_new.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            color: #2d3748;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #718096;
        }

        .stats-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item {
            flex: 1;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        .tabs {
            background: white;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tab {
            flex: 1;
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: #718096;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .tab:hover {
            background: #f7fafc;
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .password-form {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
        }

        .password-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2d3748;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 1rem;
        }

        .bulk-action {
            background: #fffbeb;
            border: 2px solid #fbbf24;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .bulk-action h3 {
            color: #92400e;
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #10b981;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="page-header">
            <h1>🔐 Password Management</h1>
            <p>Manage login credentials for all user types</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✅ <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Banner -->
        <div class="stats-banner">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($users_without_passwords); ?></div>
                <div class="stat-label">Users Without Password</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($sponsors_without_passwords); ?></div>
                <div class="stat-label">Sponsors Without Password</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($admins_without_passwords); ?></div>
                <div class="stat-label">Admins Without Password</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_without_passwords; ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab <?php echo $active_tab === 'users' ? 'active' : ''; ?>" onclick="switchTab('users')">
                Regular Users (<?php echo count($users_without_passwords); ?>)
            </button>
            <button class="tab <?php echo $active_tab === 'sponsors' ? 'active' : ''; ?>" onclick="switchTab('sponsors')">
                Sponsors/Mentors (<?php echo count($sponsors_without_passwords); ?>)
            </button>
            <button class="tab <?php echo $active_tab === 'admins' ? 'active' : ''; ?>" onclick="switchTab('admins')">
                Admins (<?php echo count($admins_without_passwords); ?>)
            </button>
        </div>

        <!-- Users Tab -->
        <div class="tab-content <?php echo $active_tab === 'users' ? 'active' : ''; ?>" id="users-tab">
            <?php if (count($users_without_passwords) > 0): ?>
                <div class="bulk-action">
                    <h3>⚠️ Bulk Action: Set Default Password for All Users</h3>
                    <p>Set default password "<strong>Welcome@2025</strong>" for all users without passwords.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure? This will set the same password for <?php echo count($users_without_passwords); ?> users.');">
                        <input type="hidden" name="generate_all_type" value="users">
                        <button type="submit" class="btn btn-success">Generate Default Passwords</button>
                    </form>
                </div>

                <div class="section">
                    <h2>📝 Users Without Passwords</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_without_passwords as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button onclick="toggleForm('user-<?php echo $user['id']; ?>')" class="btn btn-primary">
                                            Set Password
                                        </button>
                                    </td>
                                </tr>
                                <tr id="form-user-<?php echo $user['id']; ?>" style="display: none;">
                                    <td colspan="6">
                                        <div class="password-form">
                                            <form method="POST">
                                                <input type="hidden" name="user_type" value="user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <div class="form-group">
                                                    <label>New Password (min 8 characters)</label>
                                                    <input type="password" name="password" required minlength="8">
                                                </div>
                                                <div class="form-group">
                                                    <label>Confirm Password</label>
                                                    <input type="password" name="confirm_password" required minlength="8">
                                                </div>
                                                <button type="submit" class="btn btn-success">Save Password</button>
                                                <button type="button" onclick="toggleForm('user-<?php echo $user['id']; ?>')" class="btn btn-danger">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="section empty-state">
                    <h3>✅ All Users Have Passwords</h3>
                    <p>All regular users have login credentials set.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sponsors Tab -->
        <div class="tab-content <?php echo $active_tab === 'sponsors' ? 'active' : ''; ?>" id="sponsors-tab">
            <?php if (count($sponsors_without_passwords) > 0): ?>
                <div class="bulk-action">
                    <h3>⚠️ Bulk Action: Set Default Password for All Sponsors</h3>
                    <p>Set default password "<strong>Welcome@2025</strong>" for all sponsors without passwords.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure? This will set the same password for <?php echo count($sponsors_without_passwords); ?> sponsors.');">
                        <input type="hidden" name="generate_all_type" value="sponsors">
                        <button type="submit" class="btn btn-success">Generate Default Passwords</button>
                    </form>
                </div>

                <div class="section">
                    <h2>📝 Sponsors Without Passwords</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sponsors_without_passwords as $sponsor): ?>
                                <tr>
                                    <td><?php echo $sponsor['id']; ?></td>
                                    <td><?php echo htmlspecialchars($sponsor['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sponsor['email']); ?></td>
                                    <td><?php echo ucfirst($sponsor['role_type']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $sponsor['status']; ?>">
                                            <?php echo ucfirst($sponsor['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($sponsor['created_at'])); ?></td>
                                    <td>
                                        <button onclick="toggleForm('sponsor-<?php echo $sponsor['id']; ?>')" class="btn btn-primary">
                                            Set Password
                                        </button>
                                    </td>
                                </tr>
                                <tr id="form-sponsor-<?php echo $sponsor['id']; ?>" style="display: none;">
                                    <td colspan="7">
                                        <div class="password-form">
                                            <form method="POST">
                                                <input type="hidden" name="user_type" value="sponsor">
                                                <input type="hidden" name="user_id" value="<?php echo $sponsor['id']; ?>">
                                                <div class="form-group">
                                                    <label>New Password (min 8 characters)</label>
                                                    <input type="password" name="password" required minlength="8">
                                                </div>
                                                <div class="form-group">
                                                    <label>Confirm Password</label>
                                                    <input type="password" name="confirm_password" required minlength="8">
                                                </div>
                                                <button type="submit" class="btn btn-success">Save Password</button>
                                                <button type="button" onclick="toggleForm('sponsor-<?php echo $sponsor['id']; ?>')" class="btn btn-danger">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="section empty-state">
                    <h3>✅ All Sponsors Have Passwords</h3>
                    <p>All sponsors/mentors have login credentials set.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Admins Tab -->
        <div class="tab-content <?php echo $active_tab === 'admins' ? 'active' : ''; ?>" id="admins-tab">
            <?php if (count($admins_without_passwords) > 0): ?>
                <div class="bulk-action">
                    <h3>⚠️ Bulk Action: Set Default Password for All Admins</h3>
                    <p>Set default password "<strong>Welcome@2025</strong>" for all admins without passwords.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure? This will set the same password for <?php echo count($admins_without_passwords); ?> admins.');">
                        <input type="hidden" name="generate_all_type" value="admins">
                        <button type="submit" class="btn btn-success">Generate Default Passwords</button>
                    </form>
                </div>

                <div class="section">
                    <h2>📝 Admins Without Passwords</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins_without_passwords as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <button onclick="toggleForm('admin-<?php echo $admin['id']; ?>')" class="btn btn-primary">
                                            Set Password
                                        </button>
                                    </td>
                                </tr>
                                <tr id="form-admin-<?php echo $admin['id']; ?>" style="display: none;">
                                    <td colspan="6">
                                        <div class="password-form">
                                            <form method="POST">
                                                <input type="hidden" name="user_type" value="admin">
                                                <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                                <div class="form-group">
                                                    <label>New Password (min 8 characters)</label>
                                                    <input type="password" name="password" required minlength="8">
                                                </div>
                                                <div class="form-group">
                                                    <label>Confirm Password</label>
                                                    <input type="password" name="confirm_password" required minlength="8">
                                                </div>
                                                <button type="submit" class="btn btn-success">Save Password</button>
                                                <button type="button" onclick="toggleForm('admin-<?php echo $admin['id']; ?>')" class="btn btn-danger">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="section empty-state">
                    <h3>✅ All Admins Have Passwords</h3>
                    <p>All admin accounts have login credentials set.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleForm(formId) {
            const row = document.getElementById('form-' + formId);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }

        function switchTab(tabName) {
            // Update URL
            window.history.pushState({}, '', '?tab=' + tabName);

            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
