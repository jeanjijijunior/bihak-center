# 🤖 Automatic Daily Scraper Setup

## Quick Setup (2 Minutes)

### Option 1: Use the Automatic Script (EASIEST)

1. **Right-click** on: `SETUP-AUTO-SCRAPER.bat`
2. Click **"Run as administrator"**
3. Click **"Yes"** when prompted
4. Wait for it to complete
5. **Done!** ✅

The scraper will now run **automatically every day at 2:00 AM**.

---

### Option 2: Manual Setup (If script doesn't work)

#### Step 1: Open Task Scheduler
1. Press `Win + R`
2. Type: `taskschd.msc`
3. Press Enter

#### Step 2: Create New Task
1. Click "Create Basic Task..." in the right panel
2. Name: `BihakCenter-DailyScraper`
3. Description: `Automatically scrape opportunities daily`
4. Click "Next"

#### Step 3: Set Trigger
1. Select: **"Daily"**
2. Click "Next"
3. Start date: Today
4. Start time: **02:00:00** (2:00 AM)
5. Recur every: **1 days**
6. Click "Next"

#### Step 4: Set Action
1. Select: **"Start a program"**
2. Click "Next"
3. Program/script: `C:\xampp\php\php.exe`
4. Add arguments: `scrapers\run_scrapers.php`
5. Start in: `C:\xampp\htdocs\bihak-center`
6. Click "Next"

#### Step 5: Finish
1. Check: **"Open the Properties dialog..."**
2. Click "Finish"

#### Step 6: Advanced Settings
1. In the Properties dialog:
2. Go to "General" tab
3. Check: **"Run whether user is logged on or not"**
4. Check: **"Run with highest privileges"**
5. Go to "Conditions" tab
6. Uncheck: **"Start the task only if the computer is on AC power"**
7. Click "OK"
8. Enter your Windows password if prompted
9. Click "OK"

---

## ✅ Verify It's Working

### Check Task Status
1. Open Task Scheduler (`taskschd.msc`)
2. Find: `BihakCenter-DailyScraper`
3. Look at "Last Run Result" (should be `0x0` = success)
4. Look at "Next Run Time" (should show tomorrow at 2:00 AM)

### Test Run Manually
1. In Task Scheduler, right-click your task
2. Click **"Run"**
3. Check the database - new opportunities should appear

### View Logs
Check the log file: `C:\xampp\htdocs\bihak-center\logs\scraper.log`

---

## 📊 What Happens Daily

Every day at 2:00 AM, the scraper will:

1. ✅ Connect to the database
2. ✅ Run all 4 scrapers (scholarship, job, internship, grant)
3. ✅ Add new opportunities (or update existing ones)
4. ✅ Log the results
5. ✅ Mark expired opportunities as inactive

**Result:** Your opportunities database stays fresh with up to 40+ opportunities!

---

## 🔧 Customization

### Change the Schedule

**Run Multiple Times Per Day:**
```
Edit the task trigger to run:
- Every 12 hours: 02:00 and 14:00
- Every 6 hours: 02:00, 08:00, 14:00, 20:00
```

**Run Weekly Instead:**
```
Change trigger type to "Weekly"
Select which day(s) to run
```

**Run at Different Time:**
```
Edit trigger
Change start time (e.g., 01:00 AM, 03:00 AM, etc.)
```

### Run Only Specific Scrapers

Edit the task arguments to:
```
scrapers\run_scrapers.php scholarship
scrapers\run_scrapers.php job
scrapers\run_scrapers.php internship
scrapers\run_scrapers.php grant
```

Create separate tasks for each type if you want different schedules.

---

## 📝 Monitoring

### Check Scraper Performance

**In phpMyAdmin:**
```sql
-- View recent scraper activity
SELECT * FROM scraper_log
ORDER BY completed_at DESC
LIMIT 20;

-- Count opportunities by type
SELECT type, COUNT(*) as count
FROM opportunities
WHERE is_active = TRUE
GROUP BY type;

-- See newest opportunities
SELECT title, type, organization, created_at
FROM opportunities
ORDER BY created_at DESC
LIMIT 10;
```

### Email Notifications (Optional)

Add this to the end of `run_scrapers.php`:
```php
// Send email notification
$to = 'admin@bihakcenter.org';
$subject = 'Daily Scraper Report';
$message = "Scraper completed:\n";
$message .= "- Scraped: $total_scraped\n";
$message .= "- Added: $total_added\n";
$message .= "- Updated: $total_updated\n";
mail($to, $subject, $message);
```

---

## ❌ Troubleshooting

### Task Shows "Running" But Nothing Happens
**Solution:** The task user might not have permissions.
1. Edit task properties
2. Change user to "SYSTEM"
3. Check "Run with highest privileges"

### "0x1" Error (Failed)
**Solution:** Check the log file for errors:
```
C:\xampp\htdocs\bihak-center\logs\scraper.log
```

Common issues:
- MySQL not running
- Database connection failed
- PHP errors in scraper code

### Task Doesn't Run at Scheduled Time
**Solution:**
1. Make sure computer is on at 2:00 AM (or change time)
2. Uncheck "Start only if on AC power"
3. Check Task Scheduler service is running:
   - Press Win+R
   - Type: services.msc
   - Find "Task Scheduler"
   - Should be "Running"

### No New Opportunities Added
**Possible reasons:**
1. Scrapers found duplicates (they update instead of add)
2. All opportunities already exist
3. Source websites changed (scraper needs updating)

Check `scraper_log` table:
```sql
SELECT * FROM scraper_log
WHERE status = 'failed'
ORDER BY completed_at DESC;
```

---

## 🔄 Disable/Enable Auto-Scraper

### Disable Temporarily
1. Open Task Scheduler
2. Right-click task
3. Click **"Disable"**

### Re-enable
1. Open Task Scheduler
2. Right-click task
3. Click **"Enable"**

### Delete Completely
1. Open Task Scheduler
2. Right-click task
3. Click **"Delete"**
4. Click "Yes" to confirm

---

## 📈 Performance Tips

### Optimize Scraper Speed
- Run scrapers separately at different times
- Increase/decrease scraping frequency based on source update patterns
- Add caching to avoid re-scraping unchanged data

### Database Maintenance
Run weekly to keep database clean:
```sql
-- Archive old expired opportunities
UPDATE opportunities
SET is_active = FALSE
WHERE deadline < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Clean old scraper logs
DELETE FROM scraper_log
WHERE completed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## ✅ Success Checklist

- [ ] Task created in Task Scheduler
- [ ] Task shows "Ready" status
- [ ] Next run time is correct (tomorrow at 2:00 AM)
- [ ] Manual test run succeeded
- [ ] Log file created and contains output
- [ ] Database shows scraper_log entries
- [ ] Opportunities are being added/updated

---

## 📞 Need Help?

If you have issues:
1. Check the log file: `logs/scraper.log`
2. Check Task Scheduler history
3. Check `scraper_log` table in phpMyAdmin
4. Make sure MySQL is always running
5. Verify PHP path is correct: `C:\xampp\php\php.exe`

---

**🎉 Your scraper is now fully automated! New opportunities will be added every day automatically!**
