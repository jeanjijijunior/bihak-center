<?php
/**
 * Admin Users Management Page
 * Manage administrator accounts
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();
$admin = Auth::user();
$conn = getDatabaseConnection();

// Get all admins
$query = "SELECT id, username, email, full_name, role, is_active, created_at, last_login FROM admins ORDER BY created_at DESC";
$result = $conn->query($query);
$admins = [];
if ($result) {
    $admins = $result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$stats = [
    'total' => count($admins),
    'active' => count(array_filter($admins, fn($a) => $a['is_active'])),
    'super_admin' => count(array_filter($admins, fn($a) => $a['role'] === 'super_admin')),
    'admin' => count(array_filter($admins, fn($a) => $a['role'] === 'admin'))
];

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - Bihak Center Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="../../assets/images/logob.png">
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
                    <h1>Admin users</h1>
                    <p>Manage administrator accounts and permissions</p>
                </div>
                <button class="btn btn-primary" onclick="alert('Add Admin functionality - Coming soon!')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Add new admin
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                    <div class="stat-details">
                        <p class="stat-label">Total admins</p>
                        <h3 class="stat-value"><?php echo $stats['total']; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-details">
                        <p class="stat-label">Active</p>
                        <h3 class="stat-value"><?php echo $stats['active']; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm7-1a1 1 0 11-2 0 1 1 0 012 0zm-7.536 5.879a1 1 0 001.415 0 3 3 0 014.242 0 1 1 0 001.415-1.415 5 5 0 00-7.072 0 1 1 0 000 1.415z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-details">
                        <p class="stat-label">Super admins</p>
                        <h3 class="stat-value"><?php echo $stats['super_admin']; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                    </div>
                    <div class="stat-details">
                        <p class="stat-label">Regular admins</p>
                        <h3 class="stat-value"><?php echo $stats['admin']; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Admins Table -->
            <div class="card">
                <div class="card-header">
                    <h2>All administrators</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No administrators found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin_user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-sm">
                                                    <?php echo strtoupper(substr($admin_user['full_name'], 0, 1)); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($admin_user['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($admin_user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin_user['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $admin_user['role'] === 'super_admin' ? 'primary' : 'secondary'; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $admin_user['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $admin_user['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $admin_user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if ($admin_user['last_login']) {
                                                echo date('M d, Y H:i', strtotime($admin_user['last_login']));
                                            } else {
                                                echo 'Never';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon" title="Edit" onclick="alert('Edit admin functionality - Coming soon!')">
                                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                                    </svg>
                                                </button>
                                                <?php if ($admin_user['id'] != Auth::user()['id']): ?>
                                                    <button class="btn-icon btn-icon-danger" title="Delete" onclick="if(confirm('Are you sure you want to delete this administrator?')) alert('Delete admin functionality - Coming soon!')">
                                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/admin-dashboard.js"></script>
</body>
</html>
