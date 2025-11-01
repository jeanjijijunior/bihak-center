<?php
/**
 * Sponsors Management Page
 * Admin interface to view and manage sponsor/mentor/donor applications
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();

$admin = Auth::user();
$conn = getDatabaseConnection();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$role_filter = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM sponsors WHERE 1=1";
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($role_filter !== 'all') {
    $query .= " AND role_type = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR organization LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$sponsors = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_query = "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM sponsors
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsors Management - Bihak Center Admin</title>
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
                    <h1>Sponsors & Partners</h1>
                    <p>Manage mentor, donor, and sponsor interest submissions</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Submissions</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7; color: #f59e0b;">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['pending']); ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['approved']); ?></h3>
                        <p>Approved</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['rejected']); ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card">
                <form method="GET" action="" class="filters-form">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Role Type</label>
                            <select name="role" class="form-control">
                                <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                                <option value="mentor" <?php echo $role_filter === 'mentor' ? 'selected' : ''; ?>>Mentor</option>
                                <option value="donor" <?php echo $role_filter === 'donor' ? 'selected' : ''; ?>>Donor</option>
                                <option value="sponsor" <?php echo $role_filter === 'sponsor' ? 'selected' : ''; ?>>Sponsor</option>
                                <option value="volunteer" <?php echo $role_filter === 'volunteer' ? 'selected' : ''; ?>>Volunteer</option>
                                <option value="partner" <?php echo $role_filter === 'partner' ? 'selected' : ''; ?>>Partner</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, email, organization..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sponsors Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Organization</th>
                                <th>Expertise</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sponsors)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <p style="color: #9ca3af;">No sponsors found matching your criteria.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($sponsors as $sponsor): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sponsor['full_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($sponsor['email']); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo ucfirst($sponsor['role_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($sponsor['organization'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span style="font-size: 0.85rem; color: #6b7280;">
                                            <?php echo htmlspecialchars($sponsor['expertise_domain'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $sponsor['status']; ?>">
                                            <?php echo ucfirst($sponsor['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($sponsor['created_at'])); ?></td>
                                    <td>
                                        <a href="sponsor-review.php?id=<?php echo $sponsor['id']; ?>" class="btn-icon" title="View Details">
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </a>
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

    <style>
        .filters-form {
            padding: 0;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 0.875rem;
            color: var(--gray-700);
        }

        .badge-info {
            background: #e0f2fe;
            color: #0369a1;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--gray-100);
            color: var(--gray-600);
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: var(--primary-color);
            color: white;
        }
    </style>
</body>
</html>
