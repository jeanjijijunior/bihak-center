# Opportunities Scraper System - Setup Guide

## Overview

The Bihak Center Opportunities Scraper automatically collects scholarships, jobs, internships, and grants from various sources and populates your database.

## Files Created

### Database
- `includes/opportunities_tables.sql` - Database schema for opportunities

### Scrapers
- `scrapers/BaseScraper.php` - Base class for all scrapers
- `scrapers/ScholarshipScraper.php` - Scrapes scholarship opportunities
- `scrapers/JobScraper.php` - Scrapes job opportunities
- `scrapers/InternshipScraper.php` - Scrapes internship opportunities
- `scrapers/GrantScraper.php` - Scrapes grant opportunities
- `scrapers/run_scrapers.php` - Main runner script

### Frontend
- `public/opportunities.php` - Display page with filters and search
- `api/save_opportunity.php` - API for saving opportunities
- `api/track_opportunity_view.php` - API for tracking views

## Setup Instructions

### Step 1: Import Database Tables

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select the `bihak` database
3. Click "Import" tab
4. Choose file: `includes/opportunities_tables.sql`
5. Click "Go"

This creates:
- `opportunities` table (main opportunities data)
- `opportunity_tags` table (tags/categories)
- `opportunity_tag_relations` table (junction table)
- `user_saved_opportunities` table (user favorites)
- `scraper_log` table (scraping activity logs)

### Step 2: Test the Scraper

Run the scraper manually to populate initial data:

```bash
# Open Command Prompt in project directory
cd "C:\Users\JeanJuniorNiyonkuru\Downloads\Bihak site - Copie\Bihak site - Copie"

# Run all scrapers
php scrapers/run_scrapers.php

# Or run specific scraper
php scrapers/run_scrapers.php scholarship
php scrapers/run_scrapers.php job
php scrapers/run_scrapers.php internship
php scrapers/run_scrapers.php grant
```

You should see output like:
```
[2025-01-30 10:00:00] === Starting Opportunity Scrapers ===
[2025-01-30 10:00:00] Mode: ALL SCRAPERS
[2025-01-30 10:00:01] --- Running Scholarship Scraper ---
[2025-01-30 10:00:02] ✓ Scholarship Scraper completed successfully
[2025-01-30 10:00:02]   - Items scraped: 8
[2025-01-30 10:00:02]   - Items added: 8
[2025-01-30 10:00:02]   - Items updated: 0
...
```

### Step 3: Schedule Automatic Scraping

#### Option A: Windows Task Scheduler (Recommended for Windows)

1. Open "Task Scheduler" from Windows Start menu

2. Click "Create Basic Task"

3. Set task details:
   - **Name**: Bihak Opportunities Scraper
   - **Description**: Automatically scrape opportunities daily
   - **Trigger**: Daily at 2:00 AM

4. Set action:
   - **Action**: Start a program
   - **Program/script**: `C:\xampp\php\php.exe`
   - **Arguments**: `scrapers/run_scrapers.php`
   - **Start in**: `C:\Users\JeanJuniorNiyonkuru\Downloads\Bihak site - Copie\Bihak site - Copie`

5. Finish and test the task

#### Option B: Manual Cron-like Script

Create a batch file `run_scraper_daily.bat`:

```batch
@echo off
cd /d "C:\Users\JeanJuniorNiyonkuru\Downloads\Bihak site - Copie\Bihak site - Copie"
C:\xampp\php\php.exe scrapers/run_scrapers.php >> logs/scraper.log 2>&1
```

Then schedule this batch file in Task Scheduler.

### Step 4: View Opportunities

1. Visit: http://localhost/bihak-center/public/opportunities.php

2. You should see:
   - Filter tabs (All, Scholarships, Jobs, Internships, Grants)
   - Search bar
   - Country filter
   - Sort options
   - Opportunity cards with details

3. Test features:
   - Search for specific keywords
   - Filter by type
   - Sort by deadline/newest/popular
   - Save opportunities (requires login)

## Current Status

### Sample Data Loaded

The scrapers currently load **sample/seed data** to populate the database:

- **8 Scholarships** - MasterCard Foundation, DAAD, Chevening, Erasmus Mundus, etc.
- **10 Jobs** - Software Engineer, Data Analyst, Marketing Manager, etc.
- **10 Internships** - UN Youth Volunteer, World Bank, Software Development, etc.
- **12 Grants** - Youth Innovation, Women Entrepreneurs, Climate Action, etc.

**Total: 40 opportunities** ready to display

### Real Scraping (Future Enhancement)

The current implementation uses **sample data** because:
1. Real scraping requires compliance with each website's Terms of Service
2. Many sites have anti-scraping measures (rate limiting, CAPTCHAs)
3. Official APIs are preferred but often require paid subscriptions

**To implement real scraping:**

1. **Use Official APIs** (Recommended):
   - LinkedIn Jobs API
   - Indeed API
   - GitHub Jobs API
   - ScholarshipPortal API

2. **RSS Feeds** (Easier):
   - Many scholarship sites offer RSS feeds
   - Parse XML feeds using SimpleXML

3. **Web Scraping** (Last resort):
   - Check `robots.txt` for permission
   - Respect rate limits
   - Use proper User-Agent headers
   - Cache results to minimize requests

## Customization

### Adding New Scraper Sources

1. Edit the appropriate scraper file (e.g., `ScholarshipScraper.php`)

2. Add a new method:
```php
private function scrapeNewSource() {
    // Fetch data from source
    $html = $this->fetchURL('https://example.com/scholarships');
    $dom = $this->parseHTML($html);

    // Extract opportunities
    foreach ($items as $item) {
        $data = [
            'title' => $this->cleanText($item->title),
            'description' => $this->cleanText($item->description),
            'organization' => 'Organization Name',
            // ... other fields
        ];

        $this->saveOpportunity($data);
        $this->items_scraped++;
    }
}
```

3. Call the method from `scrape()`:
```php
public function scrape() {
    $this->scrapeOpportunityDesk();
    $this->scrapeNewSource(); // Add this line
}
```

### Adjusting Scrape Frequency

Edit Task Scheduler trigger:
- **Daily** - Good for most opportunities
- **Weekly** - For grants and long-term scholarships
- **Twice daily** - For time-sensitive jobs

### Filtering Opportunities

You can add filters in the SQL query in `opportunities.php`:

```php
// Only show opportunities in specific countries
$sql .= " AND o.country IN ('Rwanda', 'Kenya', 'Uganda')";

// Only show fully funded scholarships
$sql .= " AND o.amount = 'Full Scholarship'";

// Only show opportunities with upcoming deadlines
$sql .= " AND o.deadline >= CURDATE() AND o.deadline <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
```

## Monitoring

### Check Scraper Logs

View scraping history in database:

```sql
SELECT * FROM scraper_log
ORDER BY completed_at DESC
LIMIT 20;
```

### Check Opportunity Counts

```sql
SELECT type, COUNT(*) as count
FROM opportunities
WHERE is_active = TRUE
GROUP BY type;
```

### View Most Popular Opportunities

```sql
SELECT title, organization, type, views_count, applications_count
FROM opportunities
WHERE is_active = TRUE
ORDER BY views_count DESC
LIMIT 10;
```

## Troubleshooting

### Scraper Fails to Run

**Issue**: Script times out
**Solution**: Increase timeout in `run_scrapers.php`:
```php
set_time_limit(1200); // 20 minutes
```

**Issue**: Database connection errors
**Solution**: Check `config/database.php` credentials

### No Opportunities Appearing

1. Check if tables were imported:
```sql
SHOW TABLES LIKE 'opportunities';
```

2. Run scraper manually and check for errors:
```bash
php scrapers/run_scrapers.php
```

3. Check if opportunities exist:
```sql
SELECT COUNT(*) FROM opportunities;
```

### Save Feature Not Working

1. Ensure user is logged in
2. Check browser console for errors
3. Verify `user_saved_opportunities` table exists
4. Check `api/save_opportunity.php` for errors

## Security Considerations

1. **Rate Limiting**: The scrapers respect rate limits to avoid being blocked

2. **User-Agent**: Scrapers identify themselves properly

3. **Error Handling**: Failures are logged without crashing

4. **CSRF Protection**: Save functionality requires valid CSRF token

5. **SQL Injection**: All queries use prepared statements

## Next Steps

1. ✅ Import database tables
2. ✅ Run scraper manually to populate data
3. ✅ Visit opportunities page to verify
4. ⏳ Set up Task Scheduler for automatic scraping
5. ⏳ Customize scrapers to add real sources
6. ⏳ Monitor scraper logs for issues

## Support

If you encounter issues:
1. Check scraper logs in `scraper_log` table
2. Review error logs in PHP error log
3. Test database connection
4. Verify file permissions

---

**Ready to start!** Run the scraper now:
```bash
php scrapers/run_scrapers.php
```

Then visit: http://localhost/bihak-center/public/opportunities.php
