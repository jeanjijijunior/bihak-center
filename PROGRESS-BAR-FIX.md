# Progress Bar Fix

**Date:** November 19, 2025
**Status:** FIXED ✅

---

## Problem

Team "Bihak" had completed Exercise 1 (approved by admin), but the progress bar still showed **0%** and team status was still "forming".

---

## Root Cause

Exercise 1 was approved **before** the automatic progress calculation system was implemented. When the admin approved it, the approval was only recorded in the `exercise_submissions` table with `status='approved'`, but:

1. The `team_exercise_progress` table was NOT updated
2. The team `completion_percentage` was NOT calculated
3. The team `status` remained 'forming'

The progress bar displays the `completion_percentage` field from the `incubation_teams` table, which was never updated for this old approval.

---

## Database State Before Fix

### incubation_teams
```sql
id | team_name | completion_percentage | status
1  | Bihak     | 0.00                 | forming
```

### exercise_submissions
```sql
team_id | exercise_id | status   | submitted_at
1       | 1           | draft    | 2025-11-19 10:00:00
1       | 1           | approved | 2025-11-19 10:30:00  ← Approved!
```

### team_exercise_progress
```sql
(empty - no records)
```

**Result:** Progress bar showed 0% even though Exercise 1 was approved.

---

## Solution

Created a two-step fix:

### Step 1: Recalculate Progress Script

**File:** [includes/recalculate_team_progress.php](includes/recalculate_team_progress.php)

**What It Does:**
1. Counts total required exercises (19)
2. For each team, counts completed exercises from BOTH tables:
   - `team_exercise_progress` where `status='completed'`
   - `exercise_submissions` where `status='approved'`
3. Uses UNION to get distinct exercises (no double counting)
4. Calculates new percentage: `(completed / total) * 100`
5. Updates team status based on percentage:
   - `0%` → 'forming'
   - `0.01-99.99%` → 'in_progress'
   - `100%` → 'completed'
6. Updates `incubation_teams` table

**Execution:**
```bash
php c:\xampp\htdocs\bihak-center\includes\recalculate_team_progress.php
```

**Output:**
```
=== Recalculating Team Progress ===

Total required exercises: 19

Processing 1 teams...

Team: Bihak (ID: 1)
  Old: 0.00% - forming
  Completed exercises: 1
    - From team_exercise_progress: 0
    - From exercise_submissions: 1
  New: 5.26% - in_progress
  ✅ Updated!

=== Done! ===
```

### Step 2: Backfill Missing Records

**Query:**
```sql
INSERT INTO team_exercise_progress (team_id, exercise_id, status, submitted_at, reviewed_at)
SELECT es.team_id, es.exercise_id, 'completed', es.submitted_at, es.reviewed_at
FROM exercise_submissions es
WHERE es.status = 'approved'
AND NOT EXISTS (
    SELECT 1 FROM team_exercise_progress tep
    WHERE tep.team_id = es.team_id
    AND tep.exercise_id = es.exercise_id
);
```

**What It Does:**
- Finds all approved submissions in `exercise_submissions`
- Checks if corresponding record exists in `team_exercise_progress`
- If not, creates the record with `status='completed'`
- Ensures data consistency between both tables

**Result:**
- Created 1 record for Exercise 1 in `team_exercise_progress`

---

## Database State After Fix

### incubation_teams
```sql
id | team_name | completion_percentage | status
1  | Bihak     | 5.26                 | in_progress  ✅
```

### exercise_submissions
```sql
team_id | exercise_id | status   | submitted_at
1       | 1           | approved | 2025-11-19 10:30:00
```

### team_exercise_progress
```sql
team_id | exercise_id | status    | submitted_at
1       | 1           | completed | 2025-11-19 10:30:00  ✅ Now present!
```

**Result:** Progress bar now shows **5.26%** (1/19 exercises)

---

## Calculation Breakdown

**Formula:**
```
completion_percentage = (completed_exercises / total_exercises) * 100
```

**For Team "Bihak":**
```
= (1 / 19) * 100
= 5.26315...
= 5.26%
```

**Progress for 19 Exercises:**
- 1 exercise = 5.26%
- 2 exercises = 10.53%
- 3 exercises = 15.79%
- ...
- 19 exercises = 100%

---

## Visual Verification

### Before Fix
```
Team: Bihak
Status: forming
Progress: [                    ] 0%
```

### After Fix
```
Team: Bihak
Status: in_progress
Progress: [█                   ] 5.26%
```

---

## Files Created

| File | Purpose | Lines |
|------|---------|-------|
| `includes/recalculate_team_progress.php` | Recalculate progress for all teams | 129 |
| `PROGRESS-BAR-FIX.md` | Documentation | - |

---

## Prevention for Future

The current review system (in `incubation-reviews.php` and `incubation-review-submission.php`) now properly:

1. ✅ Updates `exercise_submissions` status to 'approved'
2. ✅ Updates `team_exercise_progress` status to 'completed'
3. ✅ Recalculates team completion percentage
4. ✅ Updates team status based on percentage

**This script was only needed for old approvals that happened before this system was in place.**

---

## When to Run This Script

Run `recalculate_team_progress.php` if:
- Progress bars show 0% but exercises are approved
- Team status is incorrect (forming when should be in_progress)
- Data was imported from another system
- Manual database updates were made
- Progress seems out of sync

**It's safe to run multiple times** - it only updates if changes are needed.

---

## Technical Details

### Query Logic

**Distinct Completed Exercises:**
```sql
SELECT COUNT(DISTINCT exercise_id) as count
FROM (
    -- From team_exercise_progress
    SELECT exercise_id
    FROM team_exercise_progress
    WHERE team_id = ? AND status = 'completed'

    UNION

    -- From exercise_submissions
    SELECT exercise_id
    FROM exercise_submissions
    WHERE team_id = ? AND status = 'approved'
) AS completed_exercises
```

**Why UNION?**
- Prevents double counting if exercise is in both tables
- UNION automatically eliminates duplicates
- Gives accurate count of unique completed exercises

### Status Logic

```php
if ($completion_percentage >= 100) {
    $status = 'completed';
} elseif ($completion_percentage > 0) {
    $status = 'in_progress';
} else {
    $status = 'forming';
}
```

**Status Transitions:**
- `forming` → Team created, no exercises completed
- `in_progress` → At least 1 exercise completed
- `completed` → All 19 exercises completed

---

## Testing Results

### Team "Bihak"
- ✅ Progress bar shows 5.26%
- ✅ Status changed to 'in_progress'
- ✅ Visual progress bar displays correctly
- ✅ Exercise 2 now unlocked (sequential locking works)
- ✅ Data consistent in both tables

### Verification Queries

```sql
-- Check team progress
SELECT id, team_name, completion_percentage, status
FROM incubation_teams
WHERE id = 1;

-- Check approved exercises
SELECT COUNT(*) FROM exercise_submissions
WHERE team_id = 1 AND status = 'approved';

-- Check completed exercises
SELECT COUNT(*) FROM team_exercise_progress
WHERE team_id = 1 AND status = 'completed';

-- Both should return 1
```

---

## Summary

✅ **Issue Fixed:**
- Progress bar now shows correct percentage (5.26%)
- Team status updated (forming → in_progress)
- Data consistency restored
- Script created for future use

**Root Cause:**
- Old approval before automatic system implemented
- Missing progress calculation
- Missing team_exercise_progress record

**Solution:**
- Created recalculation script
- Backfilled missing records
- Updated team progress
- Documented for future reference

---

**Fixed By:** Claude
**Completion Date:** November 19, 2025
**Status:** Production Ready ✅
