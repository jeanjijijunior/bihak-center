<?php
/**
 * Admin Tool: Set Passwords for Existing Sponsors
 * Allows admin to set passwords for sponsors who don't have login credentials
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

// Handle password set request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sponsor_id'])) {
    $sponsor_id = intval($_POST['sponsor_id']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error_message = 'Password cannot be empty.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Hash password and update
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE sponsors SET password_hash = ? WHERE id = ?");
        $stmt->bind_param('si', $password_hash, $sponsor_id);

        if ($stmt->execute()) {
            $success_message = "Password set successfully for sponsor ID: $sponsor_id";
        } else {
            $error_message = "Failed to set password.";
        }
        $stmt->close();
    }
}

// Handle bulk password generation
if (isset($_POST['generate_all'])) {
    $default_password = 'Welcome@2025';
    $password_hash = password_hash($default_password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("UPDATE sponsors SET password_hash = ? WHERE password_hash IS NULL OR password_hash = ''");
    $stmt->bind_param('s', $password_hash);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $success_message = "Generated default passwords for $affected_rows sponsors. Default password: <strong>$default_password</strong>";
    } else {
        $error_message = "Failed to generate passwords.";
    }
    $stmt->close();
}

// Get all sponsors without passwords
$query = "
    SELECT id, full_name, email, role_type, status, created_at
    FROM sponsors
    WHERE password_hash IS NULL OR password_hash = ''
    ORDER BY created_at DESC
";
$result = $conn->query($query);
$sponsors_without_passwords = [];
while ($row = $result->fetch_assoc()) {
    $sponsors_without_passwords[] = $row;
}

// Get sponsors with passwords (for reference)
$query2 = "
    SELECT id, full_name, email, role_type, status, created_at
    FROM sponsors
    WHERE password_hash IS NOT NULL AND password_hash != ''
    ORDER BY created_at DESC
    LIMIT 10
";
$result2 = $conn->query($query2);
$sponsors_with_passwords = [];
while ($row = $result2->fetch_assoc()) {
    $sponsors_with_passwords[] = $row;
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Sponsor Passwords - Admin</title>
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
            max-width: 1200px;
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

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-approved {
            background: #d1fae5;
            color: #065f46;
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
            background: #1cabe2;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
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
            color: #1cabe2;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="page-header">
            <h1>🔐 Set Sponsor Passwords</h1>
            <p>Manage login credentials for sponsors, mentors, and donors</p>
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

        <?php if (count($sponsors_without_passwords) > 0): ?>
            <div class="bulk-action">
                <h3>⚠️ Bulk Action: Set Default Password for All</h3>
                <p>Set default password "<strong>Welcome@2025</strong>" for all sponsors without passwords.</p>
                <form method="POST" onsubmit="return confirm('Are you sure? This will set the same password for <?php echo count($sponsors_without_passwords); ?> sponsors.');">
                    <button type="submit" name="generate_all" class="btn btn-success">Generate Default Passwords</button>
                </form>
            </div>

            <div class="section">
                <h2>📝 Sponsors Without Passwords (<?php echo count($sponsors_without_passwords); ?>)</h2>
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
                                    <button onclick="toggleForm(<?php echo $sponsor['id']; ?>)" class="btn btn-primary">
                                        Set Password
                                    </button>
                                </td>
                            </tr>
                            <tr id="form-<?php echo $sponsor['id']; ?>" style="display: none;">
                                <td colspan="7">
                                    <div class="password-form">
                                        <form method="POST">
                                            <input type="hidden" name="sponsor_id" value="<?php echo $sponsor['id']; ?>">
                                            <div class="form-group">
                                                <label>New Password (min 8 characters)</label>
                                                <input type="password" name="password" required minlength="8">
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm Password</label>
                                                <input type="password" name="confirm_password" required minlength="8">
                                            </div>
                                            <button type="submit" class="btn btn-success">Save Password</button>
                                            <button type="button" onclick="toggleForm(<?php echo $sponsor['id']; ?>)" class="btn btn-danger">Cancel</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="section">
                <h2>✅ All Sponsors Have Passwords</h2>
                <p>All sponsors have login credentials set. No action needed!</p>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>✓ Sponsors With Passwords (Recent 10)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sponsors_with_passwords as $sponsor): ?>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleForm(sponsorId) {
            const row = document.getElementById('form-' + sponsorId);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }
    </script>
</body>
</html>
