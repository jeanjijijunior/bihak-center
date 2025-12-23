# Automatic Opportunity Scraper Setup

## 📋 Overview

The opportunity scraper can run automatically in the background to fetch new opportunities without manual intervention. This keeps your database fresh with the latest opportunities.

---

## ✅ What's Available

1. **Manual Trigger** - Users can click refresh button on opportunities page
2. **Scheduled Task** - Automatic scraping every 6 hours (this guide)
3. **Command Line** - Manual execution via PHP script

---

## 🚀 Quick Setup (Windows)

### Method 1: Automated Setup Script (Recommended)

1. **Right-click** `setup_scheduled_scraper.bat`
2. Select **"Run as administrator"**
3. Done! Task is scheduled automatically

The scraper will now run every 6 hours:
- 🕑 2:00 AM
- 🕗 8:00 AM
- 🕑 2:00 PM
- 🕗 8:00 PM

---

### Method 2: Manual Task Scheduler Setup

1. **Open Task Scheduler:**
   - Press `Win + R`
   - Type `taskschd.msc`
   - Press Enter

2. **Create Basic Task:**
   - Click "Create Basic Task" in right panel
   - Name: `BihakCenter_OpportunityScraper`
   - Description: `Automatically scrapes opportunities every 6 hours`
   - Click Next

3. **Trigger:**
   - Select "Daily"
   - Start date: Today
   - Start time: 02:00 AM
   - Recur every: 1 day
   - Click Next

4. **Action:**
   - Select "Start a program"
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `C:\xampp\htdocs\bihak-center\includes\run_scheduled_scraper.php`
   - Click Next

5. **Advanced Settings:**
   - Open properties after creation
   - Go to "Triggers" tab
   - Edit trigger
   - Enable "Repeat task every: 6 hours"
   - For a duration of: 24 hours
   - Click OK

6. **Security:**
   - In "General" tab
   - Select "Run whether user is logged on or not"
   - Check "Run with highest privileges"
   - Click OK

---

## 🔧 Manual Execution

### Run Immediately (Command Line)

```bash
cd c:\xampp\htdocs\bihak-center\includes
php run_scheduled_scraper.php
```

### Run via Task Scheduler

```bash
schtasks /run /tn "BihakCenter_OpportunityScraper"
```

---

## 📊 Monitoring

### View Task Status

```bash
schtasks /query /tn "BihakCenter_OpportunityScraper" /fo LIST /v
```

### Check Last Run

Look in database table `scraper_logs`:

```sql
SELECT * FROM scraper_logs
WHERE source = 'scheduled'
ORDER BY created_at DESC
LIMIT 10;
```

### View Logs

The script creates logs in the temp folder and database.

---

## 🔄 Customizing Schedule

Want different timing? Edit the task trigger:

### Every 12 hours:
```bash
schtasks /create /tn "BihakCenter_OpportunityScraper" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\bihak-center\includes\run_scheduled_scraper.php" /sc daily /st 02:00 /ri 720 /du 24:00 /f
```

### Every 3 hours:
```bash
schtasks /create /tn "BihakCenter_OpportunityScraper" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\bihak-center\includes\run_scheduled_scraper.php" /sc daily /st 02:00 /ri 180 /du 24:00 /f
```

### Once daily (2 AM only):
```bash
schtasks /create /tn "BihakCenter_OpportunityScraper" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\bihak-center\includes\run_scheduled_scraper.php" /sc daily /st 02:00 /f
```

---

## 🛑 Stopping Automatic Scraping

### Disable Task (Keep but don't run):
```bash
schtasks /change /tn "BihakCenter_OpportunityScraper" /disable
```

### Enable Task:
```bash
schtasks /change /tn "BihakCenter_OpportunityScraper" /enable
```

### Delete Task Completely:
```bash
schtasks /delete /tn "BihakCenter_OpportunityScraper" /f
```

---

## 🔒 Security Features

### Lock File System
The scraper uses a lock file to prevent multiple instances running simultaneously:
- Lock created at: `temp/scraper.lock`
- Automatic cleanup of stale locks (> 1 hour old)
- Prevents server overload

### Error Handling
- Each scraper source wrapped in try-catch
- Failures logged but don't stop other scrapers
- Detailed error messages in logs

### Resource Management
- Connection pooling
- Proper database closure
- Memory efficient
- No blocking operations

---

## 📈 Performance

### Expected Behavior
- **Duration:** 5-30 seconds per run
- **Memory:** < 50MB
- **CPU:** Minimal (bursts during scraping)
- **Network:** Outbound HTTP requests to source sites

### Optimization Tips
1. Run during low-traffic hours (default: 2 AM, 8 AM, 2 PM, 8 PM)
2. Increase interval if sites get rate-limited
3. Monitor `scraper_logs` table for issues
4. Check server resources during runs

---

## 🐛 Troubleshooting

### Task Doesn't Run

**Check if task exists:**
```bash
schtasks /query /tn "BihakCenter_OpportunityScraper"
```

**Check last result:**
- Open Task Scheduler GUI
- Find task in Task Scheduler Library
- Check "Last Run Result" column
- 0x0 = Success
- Other = Error code

**Common Issues:**
- ❌ PHP not found → Check path in task action
- ❌ Permission denied → Run as administrator
- ❌ Lock file stuck → Delete `temp/scraper.lock`

### No New Opportunities

**Possible Causes:**
1. Sources have no new opportunities (normal)
2. Network connectivity issues
3. Source website changes (scrapers need update)
4. Rate limiting from source sites

**Check logs:**
```sql
SELECT source, opportunities_found, status, log_message, created_at
FROM scraper_logs
WHERE source = 'scheduled'
ORDER BY created_at DESC
LIMIT 5;
```

### Script Takes Too Long

**Solutions:**
1. Reduce scraping depth
2. Add timeout limits
3. Skip sources temporarily
4. Optimize database queries

---

## 🔄 Updating the Script

After modifying `run_scheduled_scraper.php`:

1. No need to recreate the task
2. Changes take effect immediately
3. Test manually first: `php run_scheduled_scraper.php`
4. Monitor next scheduled run

---

## 📊 Alternative: Cron (Linux/Mac)

If deploying to Linux server, use cron instead:

```bash
# Edit crontab
crontab -e

# Add this line (runs every 6 hours)
0 */6 * * * /usr/bin/php /path/to/bihak-center/includes/run_scheduled_scraper.php >> /path/to/scraper.log 2>&1
```

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Task appears in Task Scheduler
- [ ] Task is enabled (not disabled)
- [ ] Correct schedule (every 6 hours)
- [ ] Correct PHP path
- [ ] Correct script path
- [ ] Run with highest privileges
- [ ] Test manual run works
- [ ] Check scraper_logs table updates
- [ ] Opportunities table grows over time

---

## 📞 Support

### Test Manual Run
```bash
php c:\xampp\htdocs\bihak-center\includes\run_scheduled_scraper.php
```

### Expected Output
```
🕐 Scheduled Scraper Run Started: 2025-11-20 14:30:00
------------------------------------------------------------
📊 Current opportunities in database: 150

🔍 Scraping Wamda...
   ✅ Wamda: 5 new opportunities
🔍 Scraping Arabnet...
   ✅ Arabnet: 3 new opportunities
🔍 Scraping MIT Enterprise Forum...
   ✅ MIT EF: 2 new opportunities

------------------------------------------------------------
📊 Summary:
   Before: 150 opportunities
   After:  160 opportunities
   Added:  10 new opportunities

⏱️  Completed in 12.5s
✅ Scheduled scraper run finished successfully!
```

---

## 🎯 Recommended Schedule

Based on source update frequency:

| Source | Update Frequency | Recommendation |
|--------|-----------------|----------------|
| Wamda | 2-3 times/week | Every 6-12 hours |
| Arabnet | Weekly | Every 12-24 hours |
| MIT EF | Monthly | Daily |

**Default: Every 6 hours** strikes a good balance between freshness and server load.

---

**Setup Date:** November 20, 2025
**Status:** Ready to deploy
**Maintenance:** Monitor logs weekly
