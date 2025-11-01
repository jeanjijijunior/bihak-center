# PayPal Donation Tracking - Quick Start

## 🚀 What You Need to Do NOW

### 1. Update the IPN Notification URL

**File**: `public/get-involved.php` (Line 697)

**Current**:
```php
<input type="hidden" name="notify_url" value="https://yourdomain.com/api/paypal-ipn.php">
```

**Change to**:
```php
<input type="hidden" name="notify_url" value="https://YOUR-ACTUAL-DOMAIN.com/api/paypal-ipn.php">
```

### 2. Enable IPN in PayPal

1. Log in to https://www.paypal.com with `jijiniyo@gmail.com`
2. Go to: **Profile** → **My selling tools** → **Instant payment notifications**
3. Click **Update**
4. Enter: `https://YOUR-ACTUAL-DOMAIN.com/api/paypal-ipn.php`
5. Check: **Receive IPN messages (Enabled)**
6. Click **Save**

### 3. Test with Small Donation

1. Make a $1 test donation from another PayPal account
2. Check the log file: `logs/paypal-ipn.log`
3. Check admin dashboard: `admin/donations.php`
4. Verify stats update on `get-involved.php` page

---

## 📂 Files Created

### Database
- ✅ `includes/donations_table.sql` - Database schema
- ✅ Table `donations` - Stores all transactions
- ✅ View `donation_stats` - Real-time statistics

### Backend
- ✅ `api/paypal-ipn.php` - IPN webhook (receives PayPal notifications)
- ✅ `api/donation-stats.php` - Statistics API endpoint

### Frontend
- ✅ `public/donation-success.php` - Thank you page
- ✅ `public/get-involved.php` - Updated with IPN tracking

### Admin
- ✅ `public/admin/donations.php` - Donations dashboard
- ✅ `public/admin/donation-details.php` - Individual donation view
- ✅ Admin sidebar - Added "Donations" menu item

### Logs
- ✅ `logs/paypal-ipn.log` - Auto-created on first IPN

---

## 🎯 How to Access

### For Admins
- **Dashboard**: http://localhost/bihak-center/public/admin/donations.php
- **View Details**: Click any donation to see full transaction data
- **IPN Log**: http://localhost/bihak-center/logs/paypal-ipn.log

### For Donors
- **Donate**: http://localhost/bihak-center/public/get-involved.php
- **Success Page**: Automatic redirect after donation

### API Endpoints
- **Stats**: http://localhost/bihak-center/api/donation-stats.php
- **IPN Webhook**: http://localhost/bihak-center/api/paypal-ipn.php

---

## 🔍 Quick Checks

### Is IPN Working?
```bash
# Check log file
type logs\paypal-ipn.log

# Should show IPN requests from PayPal
```

### Are Donations Being Recorded?
```sql
-- Run in MySQL
SELECT COUNT(*) FROM donations;
SELECT * FROM donations ORDER BY created_at DESC LIMIT 5;
```

### Are Stats Updating?
```sql
-- Check the view
SELECT * FROM donation_stats;
```

---

## ⚠️ Important Notes

1. **HTTPS Required**: PayPal IPN only works with HTTPS in production
2. **Testing Locally**: Use ngrok or PayPal Sandbox for local testing
3. **Monitor Logs**: Check `logs/paypal-ipn.log` after first donations
4. **Verify Status**: Only donations with `payment_status='Completed'` count toward totals
5. **IPN Verification**: System automatically verifies all IPN messages with PayPal

---

## 🛠️ Troubleshooting

### Problem: No donations showing up

**Check**:
1. Is IPN URL correct in `get-involved.php`?
2. Is `logs/` directory writable?
3. Did PayPal send the IPN? (Check PayPal IPN History)
4. Any errors in `logs/paypal-ipn.log`?

### Problem: Stats showing $0

**Check**:
1. Do donations have `payment_status = 'Completed'`?
2. Are donations marked as `is_test = FALSE`?
3. Browser cache cleared?
4. Check database: `SELECT * FROM donation_stats;`

---

## 📞 Need Help?

See full guide: `PAYPAL-DONATION-SETUP-GUIDE.md`

**PayPal Developer Docs**: https://developer.paypal.com/docs/api-basics/notifications/ipn/

---

**Quick Start Complete!** 🎉

The system is ready - just update the IPN URL and test!
