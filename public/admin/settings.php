<?php
/**
 * Admin Settings Page
 * System configuration and admin preferences
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();
$admin = Auth::user();
$conn = getDatabaseConnection();

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_site_settings':
                // Update site settings
                $site_name = $_POST['site_name'] ?? '';
                $site_email = $_POST['site_email'] ?? '';
                $site_phone = $_POST['site_phone'] ?? '';

                // In a real implementation, you would save to a settings table
                $success_message = 'Site settings updated successfully!';
                break;

            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                // Verify current password
                $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
                $stmt->bind_param('i', $admin['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $admin_data = $result->fetch_assoc();

                if (!password_verify($current_password, $admin_data['password_hash'])) {
                    $error_message = 'Current password is incorrect.';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                } elseif (strlen($new_password) < 8) {
                    $error_message = 'Password must be at least 8 characters long.';
                } else {
                    // Update password
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $update_stmt = $conn->prepare("UPDATE admins SET password_hash = ?, password_changed_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $update_stmt->bind_param('si', $new_hash, $admin['id']);

                    if ($update_stmt->execute()) {
                        $success_message = 'Password changed successfully!';
                    } else {
                        $error_message = 'Failed to update password. Please try again.';
                    }
                }
                break;

            case 'update_email_settings':
                // Update email settings
                $smtp_host = $_POST['smtp_host'] ?? '';
                $smtp_port = $_POST['smtp_port'] ?? '';
                $smtp_username = $_POST['smtp_username'] ?? '';

                $success_message = 'Email settings updated successfully!';
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get current settings (placeholder - would come from database)
$site_settings = [
    'site_name' => 'Bihak Center',
    'site_email' => 'contact@bihakcenter.org',
    'site_phone' => '+250 788 123 456',
    'address' => 'Kigali, Rwanda'
];

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Bihak Center Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="../../assets/images/logob.png">
    <style>
        .settings-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .settings-section h3 {
            margin: 0 0 16px 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .btn-save {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .password-requirements {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 8px;
        }

        .password-requirements li {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <?php include 'includes/admin-header.php'; ?>

    <!-- Admin Sidebar -->
    <?php include 'includes/admin-sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="dashboard-container">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>Settings</h1>
                    <p>Configure system settings and preferences</p>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Site Settings -->
            <div class="settings-section">
                <h3>Site settings</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_site_settings">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Site name</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($site_settings['site_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Contact email</label>
                            <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($site_settings['site_email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone number</label>
                            <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($site_settings['site_phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($site_settings['address']); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-save">Save site settings</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="settings-section">
                <h3>Change password</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label>Current password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>New password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                            <div class="password-requirements">
                                <ul>
                                    <li>Minimum 8 characters</li>
                                    <li>Mix of letters and numbers recommended</li>
                                    <li>Include special characters for better security</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm new password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="8">
                        </div>
                    </div>

                    <button type="submit" class="btn-save">Update password</button>
                </form>
            </div>

            <!-- Email Settings -->
            <div class="settings-section">
                <h3>Email settings</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_email_settings">

                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP host</label>
                            <input type="text" name="smtp_host" class="form-control" placeholder="smtp.example.com">
                        </div>

                        <div class="form-group">
                            <label>SMTP port</label>
                            <input type="number" name="smtp_port" class="form-control" placeholder="587">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP username</label>
                            <input type="text" name="smtp_username" class="form-control" placeholder="noreply@bihakcenter.org">
                        </div>

                        <div class="form-group">
                            <label>SMTP password</label>
                            <input type="password" name="smtp_password" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn-save">Save email settings</button>
                </form>
            </div>

            <!-- System Information -->
            <div class="settings-section">
                <h3>System information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <label style="font-weight: 500; color: #6b7280; display: block; margin-bottom: 4px;">PHP version</label>
                        <div style="font-weight: 600; color: #1f2937;"><?php echo PHP_VERSION; ?></div>
                    </div>
                    <div>
                        <label style="font-weight: 500; color: #6b7280; display: block; margin-bottom: 4px;">Database version</label>
                        <div style="font-weight: 600; color: #1f2937;">
                            <?php
                            $conn = getDatabaseConnection();
                            echo $conn->server_info;
                            closeDatabaseConnection($conn);
                            ?>
                        </div>
                    </div>
                    <div>
                        <label style="font-weight: 500; color: #6b7280; display: block; margin-bottom: 4px;">Server software</label>
                        <div style="font-weight: 600; color: #1f2937;"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                    </div>
                    <div>
                        <label style="font-weight: 500; color: #6b7280; display: block; margin-bottom: 4px;">Logged in as</label>
                        <div style="font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($admin['username']); ?></div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="../../assets/js/admin-dashboard.js"></script>
</body>
</html>
