<?php
/**
 * Admin Donations Management
 * View and manage all PayPal donations
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();
$admin = Auth::user();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all'; // all, today, week, month, year
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["is_test = FALSE"];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_clauses[] = "payment_status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $where_clauses[] = "DATE(payment_date) = CURDATE()";
            break;
        case 'week':
            $where_clauses[] = "payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_clauses[] = "payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $where_clauses[] = "YEAR(payment_date) = YEAR(CURDATE())";
            break;
    }
}

if (!empty($search)) {
    $where_clauses[] = "(donor_email LIKE ? OR donor_name LIKE ? OR transaction_id LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_sql = implode(' AND ', $where_clauses);

// Get statistics
$conn = getDatabaseConnection();
$stats_query = "SELECT * FROM donation_stats LIMIT 1";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get donations
$donations_query = "
    SELECT
        id, transaction_id, payment_status, donor_email, donor_name,
        amount, currency, payment_date, ipn_verified, created_at
    FROM donations
    WHERE {$where_sql}
    ORDER BY payment_date DESC, created_at DESC
    LIMIT 100
";

if (!empty($params)) {
    $stmt = $conn->prepare($donations_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $donations_result = $stmt->get_result();
} else {
    $donations_result = $conn->query($donations_query);
}

$donations = [];
while ($row = $donations_result->fetch_assoc()) {
    $donations[] = $row;
}

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations Management - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="../../assets/images/favimg.png">
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
</head>
<body>
    <?php include 'includes/admin-sidebar.php'; ?>

    <div class="admin-main">
        <div class="dashboard-header">
            <div>
                <h1>Donations Management</h1>
                <p>View and track all PayPal donations</p>
            </div>
            <div class="header-actions">
                <a href="donation-settings.php" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    Settings
                </a>
                <a href="../../logs/paypal-ipn.log" class="btn btn-secondary" target="_blank">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    View IPN Log
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Raised</span>
                    <span class="stat-value">$<?php echo number_format($stats['total_raised'] ?? 0, 2); ?></span>
                    <span class="stat-meta">Net: $<?php echo number_format($stats['net_raised'] ?? 0, 2); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #d1fae5; color: #065f46;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Unique Donors</span>
                    <span class="stat-value"><?php echo $stats['unique_donors'] ?? 0; ?></span>
                    <span class="stat-meta"><?php echo $stats['total_donations'] ?? 0; ?> total donations</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">This Year</span>
                    <span class="stat-value">$<?php echo number_format($stats['raised_this_year'] ?? 0, 2); ?></span>
                    <span class="stat-meta">This month: $<?php echo number_format($stats['raised_this_month'] ?? 0, 2); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #e0e7ff; color: #3730a3;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Average Donation</span>
                    <span class="stat-value">$<?php echo number_format($stats['average_donation'] ?? 0, 2); ?></span>
                    <span class="stat-meta">Range: $<?php echo number_format($stats['smallest_donation'] ?? 0, 0); ?> - $<?php echo number_format($stats['largest_donation'] ?? 0, 0); ?></span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="dashboard-container">
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label>Payment Status:</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Refunded" <?php echo $status_filter === 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                            <option value="Failed" <?php echo $status_filter === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Date Range:</label>
                        <select name="date" onchange="this.form.submit()">
                            <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="year" <?php echo $date_filter === 'year' ? 'selected' : ''; ?>>This Year</option>
                        </select>
                    </div>

                    <div class="filter-group search-group">
                        <input type="text" name="search" placeholder="Search by email, name, or transaction ID..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($search) || $status_filter !== 'all' || $date_filter !== 'all'): ?>
                            <a href="donations.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Donations Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Donor</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($donations)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <p style="color: #718096; font-size: 1.1rem;">No donations found matching your filters.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($donations as $donation): ?>
                                <tr>
                                    <td>
                                        <code style="font-size: 0.85rem;"><?php echo htmlspecialchars(substr($donation['transaction_id'], 0, 20)); ?>...</code>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($donation['donor_name'] ?: 'Anonymous'); ?></strong>
                                            <br>
                                            <small style="color: #718096;"><?php echo htmlspecialchars($donation['donor_email'] ?: 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: #10b981;">
                                            <?php echo $donation['currency']; ?> $<?php echo number_format($donation['amount'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'Completed' => '#10b981',
                                            'Pending' => '#f59e0b',
                                            'Refunded' => '#ef4444',
                                            'Failed' => '#ef4444'
                                        ];
                                        $color = $status_colors[$donation['payment_status']] ?? '#6b7280';
                                        ?>
                                        <span class="status-badge" style="background: <?php echo $color; ?>;">
                                            <?php echo htmlspecialchars($donation['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($donation['payment_date']) {
                                            echo date('M j, Y', strtotime($donation['payment_date']));
                                            echo '<br><small style="color: #718096;">' . date('g:i A', strtotime($donation['payment_date'])) . '</small>';
                                        } else {
                                            echo '<span style="color: #718096;">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($donation['ipn_verified']): ?>
                                            <span style="color: #10b981;">✓ Verified</span>
                                        <?php else: ?>
                                            <span style="color: #ef4444;">✗ Not Verified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="donation-details.php?id=<?php echo $donation['id']; ?>" class="btn-link">
                                            View Details
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
</body>
</html>
