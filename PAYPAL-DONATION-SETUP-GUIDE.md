# PayPal Donation Tracking System - Setup Guide

## Overview

This guide will help you set up automatic PayPal donation tracking using IPN (Instant Payment Notification). When donors make a contribution via PayPal, their donation data will be automatically recorded in your database with 100% accuracy.

---

## ✅ What's Already Done

1. **Database Table Created**: `donations` table with full transaction tracking
2. **IPN Webhook**: `api/paypal-ipn.php` - Receives and verifies PayPal notifications
3. **Donation Stats API**: `api/donation-stats.php` - Provides real-time statistics
4. **Admin Dashboard**: View, search, and manage all donations
5. **PayPal Button**: Updated with IPN tracking parameters
6. **Success Page**: Shows donor confirmation and stats

---

## 🔧 Setup Steps

### Step 1: Get Your Website Domain

You need a **public domain with HTTPS** (SSL certificate) for PayPal IPN to work properly.

**Options:**
- Production domain: `https://yourdomain.com`
- Testing: Use ngrok to expose localhost (see Testing section)

### Step 2: Update PayPal IPN Notification URL

Open `public/get-involved.php` and find line 697:

```php
<input type="hidden" name="notify_url" value="https://yourdomain.com/api/paypal-ipn.php">
```

**Replace** `https://yourdomain.com` with your actual domain:

```php
<input type="hidden" name="notify_url" value="https://bihakcenter.org/api/paypal-ipn.php">
```

### Step 3: Configure PayPal Account

1. Log in to your PayPal account (jijiniyo@gmail.com)
2. Go to **Profile** → **My selling tools** → **Instant payment notifications**
3. Click **Update** or **Choose IPN Settings**
4. Enter your IPN listener URL: `https://yourdomain.com/api/paypal-ipn.php`
5. Click **Receive IPN messages (Enabled)**
6. Save settings

### Step 4: Set Correct File Permissions

Ensure the logs directory is writable:

```bash
# Windows (XAMPP)
mkdir c:\xampp\htdocs\bihak-center\logs
icacls "c:\xampp\htdocs\bihak-center\logs" /grant Users:(OI)(CI)F

# Linux/Mac
mkdir -p /path/to/bihak-center/logs
chmod 755 /path/to/bihak-center/logs
```

### Step 5: Test the System

See "Testing" section below for complete testing instructions.

---

## 🧪 Testing the Donation System

### Option 1: Using PayPal Sandbox (Recommended for Development)

1. **Create Sandbox Account**:
   - Go to https://developer.paypal.com
   - Log in with your PayPal account
   - Create a Business sandbox account (for receiving)
   - Create a Personal sandbox account (for donating)

2. **Update Donation Button for Sandbox**:
   ```php
   <!-- Change action URL to sandbox -->
   <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">

   <!-- Use sandbox business email -->
   <input type="hidden" name="business" value="sandbox-business@example.com">
   ```

3. **Set Sandbox IPN URL**:
   - Use ngrok for local testing: `ngrok http 80`
   - Update notify_url: `https://abc123.ngrok.io/api/paypal-ipn.php`

4. **Make Test Donation**:
   - Go to your donation page
   - Click donate button
   - Log in with sandbox Personal account
   - Complete donation
   - Check logs and database

### Option 2: Testing with Real PayPal (Small Amount)

1. Make sure your production domain is live with HTTPS
2. Update the notify_url with your production domain
3. Make a $1 donation from a personal PayPal account
4. Monitor the IPN log file: `logs/paypal-ipn.log`
5. Check the admin dashboard: `admin/donations.php`

### Using ngrok for Local Testing

If you're developing locally and want to test IPN:

```bash
# Install ngrok: https://ngrok.com/download

# Start ngrok tunnel
ngrok http 80

# Copy the HTTPS URL (e.g., https://abc123.ngrok.io)
# Update notify_url in get-involved.php
<input type="hidden" name="notify_url" value="https://abc123.ngrok.io/api/paypal-ipn.php">

# PayPal will now be able to send IPN to your local machine
```

---

## 📊 How It Works

### Donation Flow

1. **Donor clicks "Donate" button** → Redirected to PayPal
2. **Donor completes payment** → PayPal processes transaction
3. **PayPal sends IPN** → POST request to `api/paypal-ipn.php`
4. **IPN Handler verifies** → Sends verification request back to PayPal
5. **Data is stored** → Donation saved to database with verification status
6. **Donor returns** → Sees success page at `donation-success.php`
7. **Stats auto-update** → Real-time figures on website and dashboard

### IPN Verification Process

```
1. PayPal completes transaction
2. PayPal POST → your IPN endpoint (api/paypal-ipn.php)
3. Your server receives IPN data
4. Your server POST back → PayPal with "cmd=_notify-validate"
5. PayPal responds → "VERIFIED" or "INVALID"
6. If VERIFIED → Store in database with ipn_verified=TRUE
7. If INVALID → Log as potential fraud, ipn_verified=FALSE
```

---

## 🔍 Monitoring & Debugging

### Check IPN Log

Location: `logs/paypal-ipn.log`

```bash
# View recent IPN activity
tail -f logs/paypal-ipn.log

# Windows
type logs\paypal-ipn.log
```

### Check Database

```sql
-- View recent donations
SELECT * FROM donations ORDER BY created_at DESC LIMIT 10;

-- View statistics
SELECT * FROM donation_stats;

-- Check verification status
SELECT
    transaction_id,
    payment_status,
    ipn_verified,
    amount
FROM donations
WHERE ipn_verified = FALSE;
```

### Admin Dashboard

Navigate to: `admin/donations.php`

Features:
- Real-time donation statistics
- Filter by status, date range
- Search by email, name, transaction ID
- View detailed transaction data
- View raw IPN data
- Add admin notes

---

## 🚨 Troubleshooting

### IPN Not Receiving Data

**Problem**: No entries in `logs/paypal-ipn.log` after donation

**Solutions**:
1. Check that `logs/` directory exists and is writable
2. Verify notify_url is correct in the donation form
3. Check PayPal IPN settings are enabled
4. Ensure HTTPS is working on your domain
5. Check server firewall isn't blocking PayPal IPs

### IPN Shows "INVALID"

**Problem**: Donations logged with `ipn_verified = FALSE`

**Solutions**:
1. Check that you're using the correct PayPal URL (live vs sandbox)
2. Verify your PayPal account is in good standing
3. Check the raw IPN data in admin for error details
4. Ensure you're not modifying IPN data before verification

### Stats Not Updating

**Problem**: Donation counts/amounts not showing on website

**Solutions**:
1. Check browser console for JavaScript errors
2. Verify `api/donation-stats.php` is accessible
3. Check database view `donation_stats` exists
4. Clear browser cache
5. Check that donations have `payment_status = 'Completed'`

### Manual Entry Needed

If IPN fails, you can manually enter donations:

```sql
INSERT INTO donations (
    transaction_id, payment_status, donor_email, donor_name,
    amount, currency, net_amount, payment_date,
    ipn_verified, created_at
) VALUES (
    'MANUAL-001', 'Completed', 'donor@example.com', 'John Doe',
    50.00, 'USD', 48.50, NOW(),
    FALSE, NOW()
);
```

---

## 🔒 Security Notes

1. **IPN Verification**: Always verify IPN messages with PayPal before trusting data
2. **HTTPS Required**: PayPal requires SSL for production IPN
3. **Log Monitoring**: Regularly review IPN logs for suspicious activity
4. **Test Mode**: Use sandbox for all development/testing
5. **Raw Data Storage**: IPN raw data is stored for audit trail

---

## 📈 Features

### For Donors
- Simple one-click donation via PayPal
- Instant confirmation page
- Email receipt from PayPal
- Transparent impact information

### For Admins
- Automatic donation tracking (no manual entry)
- Real-time statistics dashboard
- Search and filter donations
- View complete transaction details
- Add notes to donations
- Export capabilities (via SQL)
- Activity logging

### For Website Visitors
- Real-time donation stats on Get Involved page
- Transparent fund usage on Impact page
- Donor count and total raised displayed

---

## 📝 Database Schema

### donations table

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| transaction_id | VARCHAR(100) | Unique transaction identifier |
| paypal_transaction_id | VARCHAR(100) | PayPal's txn_id |
| payment_status | VARCHAR(50) | Completed, Pending, Refunded, Failed |
| donor_email | VARCHAR(100) | Donor's email |
| donor_name | VARCHAR(200) | Donor's full name |
| amount | DECIMAL(10,2) | Gross donation amount |
| currency | VARCHAR(10) | Currency code (USD) |
| fee_amount | DECIMAL(10,2) | PayPal processing fee |
| net_amount | DECIMAL(10,2) | Net received (amount - fee) |
| payment_date | TIMESTAMP | When payment was made |
| ipn_verified | BOOLEAN | Whether IPN was verified |
| ipn_raw_data | TEXT | Complete IPN data (JSON) |
| admin_notes | TEXT | Admin notes about donation |
| created_at | TIMESTAMP | When record was created |

### donation_stats view

Provides aggregate statistics:
- total_donations
- unique_donors
- total_raised
- net_raised
- average_donation
- raised_this_year
- raised_this_month
- pending_donations
- refunded_donations

---

## 🎯 Next Steps

1. **Test thoroughly** using PayPal Sandbox
2. **Update production URLs** when going live
3. **Enable PayPal IPN** in account settings
4. **Monitor logs** for first few donations
5. **Set up email alerts** for failed verifications (optional)
6. **Regular backups** of donations table
7. **Review statistics** weekly/monthly

---

## 📞 Support Resources

- PayPal IPN Guide: https://developer.paypal.com/docs/api-basics/notifications/ipn/
- PayPal Sandbox: https://developer.paypal.com/developer/accounts/
- IPN Simulator: https://developer.paypal.com/developer/ipnSimulator
- IPN History: Login → Profile → My selling tools → IPN History

---

## ✅ Final Checklist

Before going live:

- [ ] Database table `donations` exists
- [ ] `logs/` directory exists and is writable
- [ ] Production domain has HTTPS (SSL certificate)
- [ ] Updated `notify_url` with production domain
- [ ] PayPal IPN enabled in account settings
- [ ] Tested with sandbox donations successfully
- [ ] Verified donations appear in admin dashboard
- [ ] Confirmed stats update on website
- [ ] IPN log file is being written to
- [ ] Made a small test donation in production

---

## 💡 Tips

1. **Start Small**: Test with $1 donations first
2. **Monitor Closely**: Watch IPN logs for first week
3. **Keep Backups**: Regular database backups
4. **Document Issues**: Note any problems for troubleshooting
5. **Update Regularly**: Keep PayPal documentation bookmarked

---

**Created**: 2025-10-31
**System Version**: 1.0
**PayPal Account**: jijiniyo@gmail.com
