# Reports Dashboard Debugging Guide

## Issue: No data showing on Reports Dashboard

### Changes Made:

1. **Removed `is_actual_project` column dependency** (column doesn't exist in database)
   - Fixed in: kpi.php, projects/index.php, charts/*.php, contractors/ranking.php, etc.
   - Changed from: `AND (is_actual_project IS NULL OR is_actual_project != 'no')`
   - Changed to: Only checking `archived_at IS NULL`

2. **Fixed date filter NULL handling** (helpers.php)
   - Date filters now properly handle NULL publication_date values
   - Added `IS NOT NULL` checks before date comparisons

3. **Changed default month filter** (reports.php)
   - Changed from: Auto-select most recent month
   - Changed to: Default to "All Months"
   - This ensures all projects show by default regardless of publication_date

4. **Added comprehensive API logging** (reports.php)
   - All API calls now log: URL, status, response data, errors
   - Check browser console to see what's happening

### Debugging Steps:

#### Step 1: Check Browser Console
1. Open Reports Dashboard: http://localhost/datas/reports
2. Open Browser DevTools (F12) → Console tab
3. Look for these log messages:
   ```
   [API] Fetching: http://localhost/datas/api/v1/kpi?period=monthly
   [API] Response status for ...: 200
   [API] Data received from ...: {data: {...}}
   ```
4. If you see errors or fallback data being used, that's the issue!

#### Step 2: Test API Endpoints Directly
Open these URLs in your browser (while logged in):

1. **KPI Endpoint:**
   http://localhost/datas/api/v1/kpi
   - Should return: `{"data": {"projects_encoded": X, "contractors_identified": Y, ...}}`

2. **Available Months:**
   http://localhost/datas/api/v1/available-months
   - Should return: `{"months": [...], "total_months": X}`

3. **Regional Stats:**
   http://localhost/datas/api/v1/charts/regional-stats
   - Should return: `{"regions": [...], "values": [...]}`

If any of these return `{"detail": "Not authenticated"}`, your session expired.
If they return empty arrays, there's no data in the database matching the filters.

#### Step 3: Run Database Test Script
Open in browser: http://localhost/datas/test-api.php

This will show:
- Total projects in database
- Non-archived projects count
- Available months
- Sample KPI data

#### Step 4: Check Database Directly
Run this SQL query in phpMyAdmin:

```sql
-- Check total projects
SELECT COUNT(*) as total FROM projects;

-- Check non-archived projects
SELECT COUNT(*) as total FROM projects WHERE archived_at IS NULL;

-- Check projects with publication dates
SELECT COUNT(*) as total FROM projects 
WHERE archived_at IS NULL 
AND publication_date IS NOT NULL;

-- Sample project data
SELECT id, contractor_name, project_name, status, publication_date, archived_at 
FROM projects 
LIMIT 10;
```

### Common Issues and Solutions:

#### Issue 1: "Not authenticated" errors
**Solution:** Session expired. Log in again at http://localhost/datas/login

#### Issue 2: Empty arrays returned but database has data
**Problem:** Date filters or archived_at filters excluding all data
**Solution:** 
- Check if `archived_at` column has values for all projects
- Check if `publication_date` is NULL for all projects
- In Reports Dashboard, select "All Months" from dropdown

#### Issue 3: Database connection error
**Problem:** Wrong credentials in config.php
**Solution:** 
- Open `c:\xampp\htdocs\DATAS\config.php`
- Change `DB_PASS` to empty string: `define('DB_PASS', '');` (default XAMPP)
- Or set correct password

#### Issue 4: API returns 404
**Problem:** .htaccess rewrite rules not working
**Solution:**
- Ensure Apache mod_rewrite is enabled
- Check XAMPP httpd.conf: `AllowOverride All`

### Expected Console Output (Success):

```
Dashboard initialized successfully
[API] Fetching: http://localhost/datas/api/v1/available-months
[API] Response status for .../available-months: 200
[API] Data received from .../available-months: {months: Array(12), total_months: 12}
[API] Fetching: http://localhost/datas/api/v1/kpi?period=monthly
[API] Response status for .../kpi: 200
[API] Data received from .../kpi: {data: {projects_encoded: 28, contractors_identified: 15, ...}}
[API] Fetching: http://localhost/datas/api/v1/contractors/ranking?period=monthly
[API] Response status for .../contractors/ranking: 200
[API] Data received from .../contractors/ranking: {contractors: Array(15)}
...
```

### If Still Not Working:

1. **Hard refresh:** Ctrl+F5 (clear cache)
2. **Check PHP error log:** `c:\xampp\php\logs\php_error_log`
3. **Check Apache error log:** `c:\xampp\apache\logs\error.log`
4. **Enable DEBUG_MODE:** In config.php, set `define('DEBUG_MODE', true);`
5. **Check Network tab:** See if API calls are even being made

### Quick Fix Checklist:
- [ ] Logged in as valid user
- [ ] Database credentials correct in config.php
- [ ] Database has non-archived projects
- [ ] Hard refresh done (Ctrl+F5)
- [ ] Console shows no errors
- [ ] API endpoints return data when accessed directly
- [ ] "All Months" selected in month dropdown

### Contact Info:
If none of these work, screenshot:
1. Browser console (all logs)
2. Network tab showing API responses
3. test-api.php output
4. Database project count query result
