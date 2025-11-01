<?php
/**
 * Activity Log Page
 * View all admin activity and actions
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();
$admin = Auth::user();
$conn = getDatabaseConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filters
$action_filter = $_GET['action'] ?? 'all';
$admin_filter = $_GET['admin'] ?? 'all';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if ($action_filter !== 'all') {
    $where_conditions[] = "action = ?";
    $params[] = $action_filter;
    $types .= 's';
}

if ($admin_filter !== 'all') {
    $where_conditions[] = "admin_id = ?";
    $params[] = $admin_filter;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM admin_activity_log $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_activities = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_activities / $per_page);

// Get activity log
$query = "
    SELECT
        aal.*,
        a.username,
        a.full_name
    FROM admin_activity_log aal
    LEFT JOIN admins a ON aal.admin_id = a.id
    $where_clause
    ORDER BY aal.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$activities = [];
if ($result) {
    $activities = $result->fetch_all(MYSQLI_ASSOC);
}

// Get unique actions for filter
$actions_query = "SELECT DISTINCT action FROM admin_activity_log ORDER BY action";
$actions_result = $conn->query($actions_query);
$actions = [];
if ($actions_result) {
    while ($row = $actions_result->fetch_assoc()) {
        $actions[] = $row['action'];
    }
}

// Get admins for filter
$admins_query = "SELECT id, username, full_name FROM admins ORDER BY full_name";
$admins_result = $conn->query($admins_query);
$admins_list = [];
if ($admins_result) {
    $admins_list = $admins_result->fetch_all(MYSQLI_ASSOC);
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity log - Bihak Center Admin</title>
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
                    <h1>Activity log</h1>
                    <p>Monitor all administrator actions and system events</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-body">
                    <form method="GET" action="" class="filters-form" style="display: flex; gap: 16px; align-items: end;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500;">Action</label>
                            <select name="action" class="form-select" style="width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px;">
                                <option value="all">All actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(str_replace('_', ' ', $action)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500;">Administrator</label>
                            <select name="admin" class="form-select" style="width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px;">
                                <option value="all">All admins</option>
                                <?php foreach ($admins_list as $adm): ?>
                                    <option value="<?php echo $adm['id']; ?>" <?php echo $admin_filter == $adm['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($adm['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">Apply filters</button>
                            <a href="activity-log.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activity Log Table -->
            <div class="card">
                <div class="card-header">
                    <h2>Recent activity (<?php echo number_format($total_activities); ?> total)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Administrator</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Details</th>
                                <th>IP address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No activity found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            <?php echo date('M d, Y H:i:s', strtotime($activity['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-sm">
                                                    <?php echo strtoupper(substr($activity['full_name'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($activity['full_name'] ?? 'Unknown'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($activity['entity_type'] && $activity['entity_id']): ?>
                                                <?php echo ucfirst($activity['entity_type']); ?> #<?php echo $activity['entity_id']; ?>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($activity['details'] ?? '-'); ?>
                                        </td>
                                        <td style="font-family: monospace; font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($activity['ip_address'] ?? '-'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer" style="display: flex; justify-content: center; gap: 8px;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&action=<?php echo $action_filter; ?>&admin=<?php echo $admin_filter; ?>" class="btn btn-secondary">Previous</a>
                        <?php endif; ?>

                        <span style="padding: 8px 16px; color: #6b7280;">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&action=<?php echo $action_filter; ?>&admin=<?php echo $admin_filter; ?>" class="btn btn-secondary">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/admin-dashboard.js"></script>
</body>
</html>
