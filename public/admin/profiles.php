<?php
/**
 * Profiles Management Page
 * View, search, and filter all profiles
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();

$admin = Auth::user();
$conn = getDatabaseConnection();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR title LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM profiles {$where_clause}";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Get profiles
$query = "
    SELECT id, full_name, email, title, short_description, profile_image,
           status, is_published, created_at, view_count
    FROM profiles
    {$where_clause}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$profiles = $stmt->get_result();

// Get status counts
$status_counts = [
    'all' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$counts_query = "
    SELECT status, COUNT(*) as count FROM profiles GROUP BY status
    UNION ALL
    SELECT 'all', COUNT(*) FROM profiles
";
$counts_result = $conn->query($counts_query);
while ($row = $counts_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profiles - Bihak Center Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="../../assets/images/logob.png">
    <style>
        .profiles-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-200);
        }

        .table-filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 16px;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 8px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: white;
            color: var(--gray-700);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .filter-tab:hover {
            border-color: var(--primary-color);
            background: var(--gray-50);
        }

        .filter-tab.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: transparent;
        }

        .filter-tab .count {
            display: inline-block;
            margin-left: 6px;
            padding: 2px 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 12px;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            max-width: 400px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 12px 10px 40px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }

        .profiles-table {
            width: 100%;
            border-collapse: collapse;
        }

        .profiles-table thead {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }

        .profiles-table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-600);
        }

        .profiles-table td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
            color: var(--gray-700);
        }

        .profiles-table tbody tr:hover {
            background: var(--gray-50);
        }

        .profile-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-thumb {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
        }

        .profile-thumb-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .profile-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .profile-details p {
            font-size: 12px;
            color: var(--gray-600);
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 20px;
            border-top: 1px solid var(--gray-200);
        }

        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination-btn:hover:not(.disabled) {
            border-color: var(--primary-color);
            background: var(--gray-50);
        }

        .pagination-btn.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: transparent;
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-info {
            font-size: 14px;
            color: var(--gray-600);
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
                    <h1>Manage Profiles</h1>
                    <p>View, search, and manage all profile submissions</p>
                </div>
            </div>

            <!-- Profiles Table -->
            <div class="profiles-table-container">
                <div class="table-header">
                    <div class="table-filters">
                        <!-- Status Filter Tabs -->
                        <div class="filter-tabs">
                            <a href="?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                               class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                                All <span class="count"><?php echo $status_counts['all']; ?></span>
                            </a>
                            <a href="?status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                               class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                Pending <span class="count"><?php echo $status_counts['pending']; ?></span>
                            </a>
                            <a href="?status=approved<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                               class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                                Approved <span class="count"><?php echo $status_counts['approved']; ?></span>
                            </a>
                            <a href="?status=rejected<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                               class="filter-tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                                Rejected <span class="count"><?php echo $status_counts['rejected']; ?></span>
                            </a>
                        </div>

                        <!-- Search Box -->
                        <form method="GET" class="search-box">
                            <?php if ($status_filter !== 'all'): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <svg class="search-icon" width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                            </svg>
                            <input type="text"
                                   name="search"
                                   class="search-input"
                                   placeholder="Search by name, email, or title..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                </div>

                <?php if ($profiles->num_rows > 0): ?>
                    <table class="profiles-table">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th>Status</th>
                                <th>Published</th>
                                <th>Views</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($profile = $profiles->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="profile-cell">
                                            <?php if ($profile['profile_image']): ?>
                                                <img src="../../<?php echo htmlspecialchars($profile['profile_image']); ?>"
                                                     alt="<?php echo htmlspecialchars($profile['full_name']); ?>"
                                                     class="profile-thumb">
                                            <?php else: ?>
                                                <div class="profile-thumb-placeholder">
                                                    <?php echo strtoupper(substr($profile['full_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="profile-details">
                                                <h4><?php echo htmlspecialchars($profile['full_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($profile['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $profile['status']; ?>">
                                            <?php echo ucfirst($profile['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($profile['is_published']): ?>
                                            <span style="color: var(--success-color);">✓ Yes</span>
                                        <?php else: ?>
                                            <span style="color: var(--gray-400);">✗ No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($profile['view_count']); ?></td>
                                    <td>
                                        <span title="<?php echo date('M j, Y g:i A', strtotime($profile['created_at'])); ?>">
                                            <?php echo date('M j, Y', strtotime($profile['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="profile-review.php?id=<?php echo $profile['id']; ?>"
                                               class="btn btn-sm btn-primary">
                                                Review
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"
                                   class="pagination-btn">← Previous</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">← Previous</span>
                            <?php endif; ?>

                            <span class="pagination-info">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                (<?php echo number_format($total); ?> total)
                            </span>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"
                                   class="pagination-btn">Next →</a>
                            <?php else: ?>
                                <span class="pagination-btn disabled">Next →</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state" style="padding: 64px 32px;">
                        <svg width="64" height="64" viewBox="0 0 20 20" fill="currentColor" style="color: var(--gray-300); margin-bottom: 16px;">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <h3 style="color: var(--gray-700); margin-bottom: 8px;">No profiles found</h3>
                        <p style="color: var(--gray-500);">
                            <?php if (!empty($search)): ?>
                                No profiles match your search criteria.
                            <?php elseif ($status_filter !== 'all'): ?>
                                No <?php echo $status_filter; ?> profiles yet.
                            <?php else: ?>
                                No profiles have been submitted yet.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/admin-dashboard.js"></script>
</body>
</html>
