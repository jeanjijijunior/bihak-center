<?php
/**
 * Donation Details Page
 * View complete details of a single donation
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
Auth::requireAuth();
$admin = Auth::user();

$donation_id = $_GET['id'] ?? null;

if (!$donation_id) {
    header('Location: donations.php');
    exit;
}

// Handle admin notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notes'])) {
    $notes = trim($_POST['admin_notes'] ?? '');

    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("UPDATE donations SET admin_notes = ? WHERE id = ?");
    $stmt->bind_param('si', $notes, $donation_id);

    if ($stmt->execute()) {
        Auth::logActivity($admin['id'], 'donation_note_updated', 'donation', $donation_id,
                         "Updated admin notes for donation ID {$donation_id}");
        $success = 'Notes updated successfully!';
    }
    closeDatabaseConnection($conn);
}

// Get donation details
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT * FROM donations WHERE id = ?");
$stmt->bind_param('i', $donation_id);
$stmt->execute();
$result = $stmt->get_result();
$donation = $result->fetch_assoc();

if (!$donation) {
    header('Location: donations.php');
    exit;
}

// Parse IPN raw data
$ipn_data = json_decode($donation['ipn_raw_data'], true) ?? [];

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Details - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="../../assets/images/favimg.png">
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .detail-card h3 {
            font-size: 1.1rem;
            color: #2d3748;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f7fafc;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #4a5568;
        }

        .detail-value {
            color: #2d3748;
            text-align: right;
        }

        .notes-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
        }

        .ipn-data {
            background: #f7fafc;
            padding: 16px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .ipn-data pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-sidebar.php'; ?>

    <div class="admin-main">
        <div class="dashboard-header">
            <div>
                <h1>Donation Details</h1>
                <p>Transaction ID: <?php echo htmlspecialchars($donation['transaction_id']); ?></p>
            </div>
            <div class="header-actions">
                <a href="donations.php" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                    Back to Donations
                </a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success" style="margin: 20px; padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-container">
            <div class="detail-grid">
                <!-- Transaction Information -->
                <div class="detail-card">
                    <h3>Transaction Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Transaction ID:</span>
                        <span class="detail-value"><code><?php echo htmlspecialchars($donation['transaction_id']); ?></code></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">PayPal Transaction ID:</span>
                        <span class="detail-value"><code><?php echo htmlspecialchars($donation['paypal_transaction_id'] ?: 'N/A'); ?></code></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status:</span>
                        <span class="detail-value">
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
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($donation['payment_type'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">IPN Verified:</span>
                        <span class="detail-value">
                            <?php if ($donation['ipn_verified']): ?>
                                <span style="color: #10b981; font-weight: 600;">✓ Verified</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-weight: 600;">✗ Not Verified</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Donor Information -->
                <div class="detail-card">
                    <h3>Donor Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($donation['donor_name'] ?: 'Anonymous'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">First Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($donation['donor_first_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($donation['donor_last_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($donation['donor_email'] ?: 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Amount Information -->
                <div class="detail-card">
                    <h3>Amount Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Gross Amount:</span>
                        <span class="detail-value" style="font-size: 1.2rem; font-weight: 600; color: #10b981;">
                            <?php echo $donation['currency']; ?> $<?php echo number_format($donation['amount'], 2); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">PayPal Fee:</span>
                        <span class="detail-value" style="color: #ef4444;">
                            -$<?php echo number_format($donation['fee_amount'], 2); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Net Amount:</span>
                        <span class="detail-value" style="font-weight: 600;">
                            $<?php echo number_format($donation['net_amount'], 2); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Currency:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($donation['currency']); ?></span>
                    </div>
                </div>

                <!-- Dates -->
                <div class="detail-card">
                    <h3>Timestamps</h3>
                    <div class="detail-row">
                        <span class="detail-label">Payment Date:</span>
                        <span class="detail-value">
                            <?php echo $donation['payment_date'] ? date('M j, Y g:i A', strtotime($donation['payment_date'])) : 'N/A'; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Created At:</span>
                        <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($donation['created_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($donation['updated_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Verified At:</span>
                        <span class="detail-value">
                            <?php echo $donation['verified_at'] ? date('M j, Y g:i A', strtotime($donation['verified_at'])) : 'Not verified'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Admin Notes -->
            <div class="detail-card" style="margin-top: 20px;">
                <h3>Admin Notes</h3>
                <form method="POST" class="notes-form">
                    <textarea name="admin_notes" placeholder="Add notes about this donation..."><?php echo htmlspecialchars($donation['admin_notes'] ?? ''); ?></textarea>
                    <button type="submit" name="update_notes" class="btn btn-primary" style="margin-top: 12px;">Save Notes</button>
                </form>
            </div>

            <!-- Raw IPN Data -->
            <div class="detail-card" style="margin-top: 20px;">
                <h3>Raw IPN Data</h3>
                <div class="ipn-data">
                    <pre><?php echo htmlspecialchars(json_encode($ipn_data, JSON_PRETTY_PRINT)); ?></pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
