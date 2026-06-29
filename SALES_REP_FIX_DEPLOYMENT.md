# Sales Rep Assignment Fix - Deployment Guide

## Problem Description

Projects assigned to **Dennis Espinar** are appearing in **Melody Nool's** account (and possibly vice versa).

### Root Cause

Mismatch between:
- `projects.assigned_to` (who the project is assigned to)
- `sales_tracking.sales_rep_id` (who has the tracking record)

The LEFT JOIN in the query didn't check if these two values match, causing cross-contamination of data.

---

## Files Changed

### 1. `api/projects/assigned.php`
**Line 85:** Added condition to JOIN clause
```php
// OLD:
LEFT JOIN sales_tracking st ON p.id = st.project_id

// NEW:
LEFT JOIN sales_tracking st ON p.id = st.project_id AND st.sales_rep_id = p.assigned_to
```

This ensures that sales_tracking records only match when the sales_rep_id equals the project's assigned_to.

### 2. New Diagnostic Scripts

**`check-assignments.php`**
- Checks which projects are assigned to Dennis (ID: 6) vs Melody (ID: 10)
- Shows mismatches between `projects.assigned_to` and `sales_tracking.sales_rep_id`
- Non-destructive, read-only

**`fix-sales-tracking-sync.php`**
- Automatically fixes mismatches
- Updates `sales_tracking.sales_rep_id` to match `projects.assigned_to`
- Deletes orphaned sales_tracking records

---

## Deployment Steps

### Step 1: Deploy Code Changes

```bash
# On local machine
git add api/projects/assigned.php
git add check-assignments.php
git add fix-sales-tracking-sync.php
git add SALES_REP_FIX_DEPLOYMENT.md
git commit -m "Fix: Sales rep assignment filtering - prevent cross-user visibility"
git push
```

### Step 2: Pull Changes on Production Server

```bash
# SSH to production
ssh user@production-server
cd /var/www/html/datas  # or your DATAS directory
git pull
```

### Step 3: Diagnose the Issue

**Option A: Via Browser**
```
https://datas.lan/check-assignments.php
```

**Option B: Via CLI**
```bash
cd /var/www/html/datas
php check-assignments.php
```

**Expected Output:**
- List of projects assigned to Dennis (user ID: 6)
- List of projects assigned to Melody (user ID: 10)
- Any mismatches will be highlighted with ⚠️

**Example Output:**
```
=== DENNIS ESPINAR (ID: 6) ===

Projects in 'projects' table (assigned_to = 6): 45

Sales tracking records for Melody:
  - ST #123, Project #456: Some Project Name
    Sales Tracking Rep: Melody Nool (ID: 10)
    Project Assigned To: Dennis Espinar (ID: 6) ⚠️ MISMATCH!
```

### Step 4: Fix Data Mismatches (If Found)

**Option A: Via Browser**
```
https://datas.lan/fix-sales-tracking-sync.php
```

**Option B: Via CLI**
```bash
cd /var/www/html/datas
php fix-sales-tracking-sync.php
```

**Option C: Direct SQL (if you prefer)**
```sql
-- Check mismatches first
SELECT 
    p.id,
    p.project_name,
    p.assigned_to,
    u1.full_name as assigned_to_name,
    st.sales_rep_id,
    u2.full_name as tracking_rep_name
FROM projects p
LEFT JOIN sales_tracking st ON p.id = st.project_id
LEFT JOIN users u1 ON p.assigned_to = u1.id
LEFT JOIN users u2 ON st.sales_rep_id = u2.id
WHERE p.assigned_to IS NOT NULL
AND st.id IS NOT NULL
AND p.assigned_to != st.sales_rep_id;

-- Fix mismatches
UPDATE sales_tracking st
INNER JOIN projects p ON st.project_id = p.id
SET st.sales_rep_id = p.assigned_to,
    st.updated_at = NOW()
WHERE p.assigned_to IS NOT NULL
AND p.assigned_to != st.sales_rep_id;

-- Delete orphaned records
DELETE st FROM sales_tracking st
LEFT JOIN projects p ON st.project_id = p.id
WHERE p.assigned_to IS NULL OR p.assigned_to = 0;
```

### Step 5: Verify the Fix

1. **Test as Melody Nool:**
   - Login: cmnool@tdtpowersteel.com.ph
   - Navigate to "My Projects"
   - Verify: Should only see projects assigned to her
   - Verify: Dennis's projects should NOT appear

2. **Test as Dennis Espinar:**
   - Login: despinar@tdtpowersteel.com.ph
   - Navigate to "My Projects"
   - Verify: Should only see projects assigned to him
   - Verify: Melody's projects should NOT appear

3. **Check API Response:**
   ```bash
   # Test Dennis's endpoint
   curl -X GET "https://datas.lan/api/v1/projects/assigned?sales_rep_id=6" \
     --cookie "PHPSESSID=your_session_id"
   
   # Test Melody's endpoint
   curl -X GET "https://datas.lan/api/v1/projects/assigned?sales_rep_id=10" \
     --cookie "PHPSESSID=your_session_id"
   ```

### Step 6: Clean Up (Optional)

Once verified working, you can remove the diagnostic scripts:
```bash
rm check-assignments.php
rm fix-sales-tracking-sync.php
```

---

## Technical Details

### Database Schema Reference

**projects table:**
- `id` - Project ID
- `assigned_to` - User ID of the assigned sales rep
- `assigned_at` - Timestamp of assignment

**sales_tracking table:**
- `id` - Tracking record ID
- `project_id` - Foreign key to projects.id
- `sales_rep_id` - User ID of the sales rep (should match projects.assigned_to)
- `tracking_status` - Status of tracking

**Constraint:**
```sql
UNIQUE KEY `unique_project_sales_rep` (`project_id`, `sales_rep_id`)
```

### User IDs
- Dennis Espinar: `id = 6`
- Melody Nool: `id = 10`

---

## Troubleshooting

### Issue: Still seeing cross-user projects after fix

**Check 1: Verify API is updated**
```bash
grep -n "st.sales_rep_id = p.assigned_to" api/projects/assigned.php
```
Should show the line with the new JOIN condition.

**Check 2: Clear browser cache**
```
Ctrl + F5 (hard refresh)
or
Ctrl + Shift + Delete → Clear cache
```

**Check 3: Check database directly**
```sql
-- For a specific project that's appearing incorrectly:
SELECT 
    p.id,
    p.project_name,
    p.assigned_to,
    st.sales_rep_id
FROM projects p
LEFT JOIN sales_tracking st ON p.id = st.project_id
WHERE p.id = <project_id>;
```

**Check 4: Restart PHP-FPM (if using)**
```bash
sudo systemctl restart php-fpm
```

### Issue: Fix script shows errors

**Error: "Cannot delete or update a parent row"**
- This means there are foreign key constraints preventing deletion
- Solution: The script should handle this, but if not, check the constraint and temporarily disable if needed:
```sql
SET FOREIGN_KEY_CHECKS=0;
-- run deletion
SET FOREIGN_KEY_CHECKS=1;
```

**Error: "Duplicate entry for key 'unique_project_sales_rep'"**
- This means there are duplicate records in sales_tracking
- Solution: Manually identify and remove duplicates:
```sql
SELECT project_id, sales_rep_id, COUNT(*) as cnt
FROM sales_tracking
GROUP BY project_id, sales_rep_id
HAVING cnt > 1;
```

---

## Rollback Plan

If issues occur, you can rollback:

### Code Rollback
```bash
git revert HEAD
git push
# Then on server:
git pull
```

### Data Rollback
If you have a backup before running the fix script:
```bash
mysql -u user -p database_name < backup_before_fix.sql
```

---

## Prevention for Future

### When Assigning Projects

Ensure both tables are updated:
```php
// Update projects table
UPDATE projects SET assigned_to = :sales_rep_id WHERE id = :project_id;

// Update or create sales_tracking record
INSERT INTO sales_tracking (project_id, sales_rep_id, ...)
VALUES (:project_id, :sales_rep_id, ...)
ON DUPLICATE KEY UPDATE sales_rep_id = :sales_rep_id;
```

### When Querying Assigned Projects

Always join with matching condition:
```sql
LEFT JOIN sales_tracking st 
  ON p.id = st.project_id 
  AND st.sales_rep_id = p.assigned_to
```

---

## Contact

If issues persist after following this guide:
1. Check the server logs: `/var/log/php_errors.log` or `/var/log/apache2/error.log`
2. Enable debug mode in `config.php`
3. Contact the development team with:
   - Steps taken
   - Error messages
   - Output from check-assignments.php
   - Browser console errors (F12)

---

**Last Updated:** 2026-06-29  
**Version:** 1.0
