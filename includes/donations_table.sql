-- Donations tracking table for PayPal IPN integration
-- This table stores all donation transactions received via PayPal

CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Transaction Information
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    paypal_transaction_id VARCHAR(100) UNIQUE,
    payment_status VARCHAR(50) NOT NULL,
    payment_type VARCHAR(50),

    -- Donor Information
    donor_email VARCHAR(100),
    donor_name VARCHAR(200),
    donor_first_name VARCHAR(100),
    donor_last_name VARCHAR(100),

    -- Amount Information
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    fee_amount DECIMAL(10, 2),
    net_amount DECIMAL(10, 2),

    -- PayPal Details
    receiver_email VARCHAR(100),
    business_email VARCHAR(100),
    item_name VARCHAR(255),
    item_number VARCHAR(100),

    -- Payment Details
    payment_date TIMESTAMP NULL,
    pending_reason VARCHAR(100),
    reason_code VARCHAR(100),

    -- IPN Verification
    ipn_verified BOOLEAN DEFAULT FALSE,
    ipn_raw_data TEXT,
    verification_status VARCHAR(50),

    -- Metadata
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    notify_version VARCHAR(20),
    charset VARCHAR(20),

    -- Status and Notes
    is_test BOOLEAN DEFAULT FALSE,
    is_refunded BOOLEAN DEFAULT FALSE,
    refund_amount DECIMAL(10, 2),
    admin_notes TEXT,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,

    -- Indexes for performance
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_paypal_transaction_id (paypal_transaction_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_donor_email (donor_email),
    INDEX idx_payment_date (payment_date),
    INDEX idx_created_at (created_at),
    INDEX idx_ipn_verified (ipn_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a view for quick stats
CREATE OR REPLACE VIEW donation_stats AS
SELECT
    COUNT(*) as total_donations,
    COUNT(DISTINCT donor_email) as unique_donors,
    SUM(CASE WHEN payment_status = 'Completed' THEN amount ELSE 0 END) as total_raised,
    SUM(CASE WHEN payment_status = 'Completed' THEN net_amount ELSE 0 END) as net_raised,
    AVG(CASE WHEN payment_status = 'Completed' THEN amount ELSE NULL END) as average_donation,
    MIN(CASE WHEN payment_status = 'Completed' THEN amount ELSE NULL END) as smallest_donation,
    MAX(CASE WHEN payment_status = 'Completed' THEN amount ELSE NULL END) as largest_donation,
    SUM(CASE WHEN payment_status = 'Completed' AND YEAR(payment_date) = YEAR(CURDATE()) THEN amount ELSE 0 END) as raised_this_year,
    SUM(CASE WHEN payment_status = 'Completed' AND YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE()) THEN amount ELSE 0 END) as raised_this_month,
    COUNT(CASE WHEN payment_status = 'Pending' THEN 1 ELSE NULL END) as pending_donations,
    COUNT(CASE WHEN is_refunded = TRUE THEN 1 ELSE NULL END) as refunded_donations,
    SUM(CASE WHEN is_refunded = TRUE THEN refund_amount ELSE 0 END) as total_refunded
FROM donations
WHERE is_test = FALSE;
