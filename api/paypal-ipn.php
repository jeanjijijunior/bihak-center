<?php
/**
 * PayPal IPN (Instant Payment Notification) Handler
 *
 * This endpoint receives automatic notifications from PayPal when donations are made.
 * It verifies the authenticity of the notification and stores donation data.
 *
 * PayPal IPN URL: https://yourdomain.com/api/paypal-ipn.php
 *
 * IMPORTANT: This file must be accessible via HTTPS for production use.
 */

// Prevent direct access and output
if (basename($_SERVER['PHP_SELF']) !== 'paypal-ipn.php') {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Set content type and disable output buffering
header('Content-Type: text/plain');
ob_end_clean();

// Log file for debugging
define('IPN_LOG_FILE', __DIR__ . '/../logs/paypal-ipn.log');

// Ensure logs directory exists
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

/**
 * Log IPN activity
 */
function logIPN($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";

    if ($data !== null) {
        $logMessage .= "\n" . print_r($data, true);
    }

    $logMessage .= "\n" . str_repeat('-', 80) . "\n";

    file_put_contents(IPN_LOG_FILE, $logMessage, FILE_APPEND);
}

/**
 * Verify IPN with PayPal
 */
function verifyIPN($postData) {
    // PayPal URLs
    $paypalURL = 'https://ipnpb.paypal.com/cgi-bin/webscr'; // Live
    $paypalSandboxURL = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'; // Sandbox

    // Determine if this is a sandbox transaction
    $isSandbox = isset($postData['test_ipn']) && $postData['test_ipn'] == 1;
    $url = $isSandbox ? $paypalSandboxURL : $paypalURL;

    // Build verification request
    $req = 'cmd=_notify-validate';
    foreach ($postData as $key => $value) {
        $value = urlencode(stripslashes($value));
        $req .= "&{$key}={$value}";
    }

    // Send verification request to PayPal
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    logIPN("IPN Verification Response", ['url' => $url, 'response' => $response, 'http_code' => $httpCode]);

    return $response === 'VERIFIED';
}

/**
 * Store donation in database
 */
function storeDonation($ipnData, $verified) {
    require_once __DIR__ . '/../config/database.php';

    try {
        $conn = getDatabaseConnection();

        // Extract relevant data
        $transactionId = $ipnData['txn_id'] ?? uniqid('txn_', true);
        $paypalTxnId = $ipnData['txn_id'] ?? null;
        $paymentStatus = $ipnData['payment_status'] ?? 'Unknown';
        $paymentType = $ipnData['payment_type'] ?? null;

        $donorEmail = $ipnData['payer_email'] ?? null;
        $donorFirstName = $ipnData['first_name'] ?? null;
        $donorLastName = $ipnData['last_name'] ?? null;
        $donorName = trim(($donorFirstName ?? '') . ' ' . ($donorLastName ?? ''));

        $amount = floatval($ipnData['mc_gross'] ?? 0);
        $currency = $ipnData['mc_currency'] ?? 'USD';
        $feeAmount = floatval($ipnData['mc_fee'] ?? 0);
        $netAmount = $amount - $feeAmount;

        $receiverEmail = $ipnData['receiver_email'] ?? null;
        $businessEmail = $ipnData['business'] ?? null;
        $itemName = $ipnData['item_name'] ?? 'Donation';
        $itemNumber = $ipnData['item_number'] ?? null;

        $paymentDate = isset($ipnData['payment_date']) ? date('Y-m-d H:i:s', strtotime($ipnData['payment_date'])) : null;
        $pendingReason = $ipnData['pending_reason'] ?? null;
        $reasonCode = $ipnData['reason_code'] ?? null;

        $ipnRawData = json_encode($ipnData);
        $verificationStatus = $verified ? 'VERIFIED' : 'INVALID';

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $notifyVersion = $ipnData['notify_version'] ?? null;
        $charset = $ipnData['charset'] ?? null;

        $isTest = isset($ipnData['test_ipn']) && $ipnData['test_ipn'] == 1;

        // Check if transaction already exists
        $stmt = $conn->prepare("SELECT id FROM donations WHERE transaction_id = ?");
        $stmt->bind_param('s', $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing transaction
            $row = $result->fetch_assoc();
            $donationId = $row['id'];

            $stmt = $conn->prepare("
                UPDATE donations SET
                    payment_status = ?,
                    donor_email = ?,
                    donor_name = ?,
                    donor_first_name = ?,
                    donor_last_name = ?,
                    amount = ?,
                    fee_amount = ?,
                    net_amount = ?,
                    payment_date = ?,
                    ipn_verified = ?,
                    verification_status = ?,
                    ipn_raw_data = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param(
                'sssssdddssssi',
                $paymentStatus, $donorEmail, $donorName, $donorFirstName, $donorLastName,
                $amount, $feeAmount, $netAmount, $paymentDate,
                $verified, $verificationStatus, $ipnRawData, $donationId
            );

            $stmt->execute();
            logIPN("Donation Updated", ['id' => $donationId, 'transaction_id' => $transactionId]);

        } else {
            // Insert new transaction
            $stmt = $conn->prepare("
                INSERT INTO donations (
                    transaction_id, paypal_transaction_id, payment_status, payment_type,
                    donor_email, donor_name, donor_first_name, donor_last_name,
                    amount, currency, fee_amount, net_amount,
                    receiver_email, business_email, item_name, item_number,
                    payment_date, pending_reason, reason_code,
                    ipn_verified, ipn_raw_data, verification_status,
                    ip_address, user_agent, notify_version, charset,
                    is_test, verified_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, NOW()
                )
            ");

            $stmt->bind_param(
                'ssssssssdsddsssssssssssssss',
                $transactionId, $paypalTxnId, $paymentStatus, $paymentType,
                $donorEmail, $donorName, $donorFirstName, $donorLastName,
                $amount, $currency, $feeAmount, $netAmount,
                $receiverEmail, $businessEmail, $itemName, $itemNumber,
                $paymentDate, $pendingReason, $reasonCode,
                $verified, $ipnRawData, $verificationStatus,
                $ipAddress, $userAgent, $notifyVersion, $charset,
                $isTest
            );

            $stmt->execute();
            $donationId = $conn->insert_id;
            logIPN("Donation Inserted", ['id' => $donationId, 'transaction_id' => $transactionId]);
        }

        $conn->close();
        return $donationId;

    } catch (Exception $e) {
        logIPN("Database Error", ['error' => $e->getMessage()]);
        return false;
    }
}

// Main IPN Processing
try {
    logIPN("=== IPN Request Received ===");

    // Read POST data
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $ipnData = array();

    foreach ($raw_post_array as $keyval) {
        $keyval = explode('=', $keyval);
        if (count($keyval) == 2) {
            $ipnData[$keyval[0]] = urldecode($keyval[1]);
        }
    }

    logIPN("IPN Data Received", $ipnData);

    // Verify IPN with PayPal
    $verified = verifyIPN($ipnData);
    logIPN("IPN Verification Result", ['verified' => $verified]);

    if ($verified) {
        // Store donation in database
        $donationId = storeDonation($ipnData, true);

        if ($donationId) {
            logIPN("SUCCESS: Donation processed", ['donation_id' => $donationId]);
            http_response_code(200);
            echo "SUCCESS";
        } else {
            logIPN("ERROR: Failed to store donation");
            http_response_code(500);
            echo "ERROR: Database storage failed";
        }
    } else {
        // Log as invalid IPN
        storeDonation($ipnData, false);
        logIPN("WARNING: IPN verification failed - possible fraud attempt");
        http_response_code(200); // Still return 200 to prevent PayPal retries
        echo "INVALID";
    }

} catch (Exception $e) {
    logIPN("FATAL ERROR", ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo "ERROR: " . $e->getMessage();
}
