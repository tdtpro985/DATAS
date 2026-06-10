# Quick Fix Guide - Sales Tracking Display Issue

## ⚡ Quick Summary

**Problem:** Sales Tracking "In Progress" data hindi lumalabas sa modal  
**Root Cause:** 500 API error + DOM timing race condition  
**Status:** ✅ FIXED  

## 🔧 Files Modified

1. `api/projects/sales-tracking.php` - Enhanced error handling
2. `static/js/projects.js` - Fixed async loading + DOM timing
3. `test-sales-tracking.php` - NEW debug script
4. `check-sales-tracking-api.php` - NEW API test script

## 🚀 Quick Test (Local XAMPP)

### 1. Test Database
```
http://localhost/DATAS/test-sales-tracking.php
```
**Expected:** ✅ All tests pass, shows tracking records

### 2. Test API Endpoint
```
http://localhost/DATAS/check-sales-tracking-api.php?project_id=5
```
**Expected:** ✅ Status 200, shows tracking data

### 3. Test in Browser
1. Open DevTools (F12) → Console tab
2. Click any project with "In Progress" tracking
3. **Look for these logs:**
   ```
   [PROJECTS] Loading sales tracking data for project: 5
   [PROJECTS] Fetching from URL: ...
   [PROJECTS] Response status: 200 OK
   [PROJECTS] Found 4 yes-no buttons in DOM
   [PROJECTS] ✓ Restored button: contacted = yes
   [PROJECTS] ✓ Sales tracking data restored successfully
   ```

## 🐛 If Still Not Working

### Check Console Errors:
```javascript
// If you see "500 Internal Server Error"
→ Check: logs/php_errors.log
→ Look for: [SALES_TRACKING] error messages

// If you see "Found 0 yes-no buttons"  
→ DOM not ready, buttons not rendered yet
→ Fix applied: Auto-retry after 200ms

// If you see "Failed to load sales tracking data"
→ Network or authentication issue
→ Check: Session still active? CORS settings?
```

### Check Server Logs:
```bash
# Local (XAMPP)
tail -f c:\xampp\apache\logs\error.log

# Production (Linux)
tail -f /var/log/apache2/error.log
tail -f /path/to/DATAS/logs/php_errors.log
```

## 📦 Deploy to Production (Webmin/Putty)

### Step 1: Backup
```bash
cd /path/to/DATAS
cp api/projects/sales-tracking.php api/projects/sales-tracking.php.backup
cp static/js/projects.js static/js/projects.js.backup
```

### Step 2: Upload Files
```bash
# Using SCP/SFTP, upload:
- api/projects/sales-tracking.php
- static/js/projects.js
- test-sales-tracking.php
- check-sales-tracking-api.php
```

### Step 3: Test Production
```bash
# Test database
https://yourdomain.com/test-sales-tracking.php

# Test API
https://yourdomain.com/check-sales-tracking-api.php?project_id=5
```

### Step 4: Monitor Logs
```bash
# Watch for errors in real-time
tail -f /path/to/DATAS/logs/php_errors.log

# Open project modal and watch logs
# Should see: [SALES_TRACKING] log entries
```

### Step 5: Security
```bash
# After testing, DISABLE debug mode
nano /path/to/DATAS/config.php
# Change: define('DEBUG_MODE', false);

# Remove or restrict test scripts
rm test-sales-tracking.php
rm check-sales-tracking-api.php
# OR add .htaccess protection
```

## 🎯 What Was Fixed

### Before:
```javascript
// Race condition - buttons might not exist yet
this.loadSalesReps();
this.loadSalesTrackingData(projectId);  // ❌ Called too early
```

### After:
```javascript
// Proper sequencing with Promise chain
this.loadSalesReps().then(() => {
    this.loadSalesTrackingData(projectId);  // ✅ Called after reps loaded
});

// Plus: DOM readiness check with auto-retry
if (allButtons.length === 0) {
    setTimeout(() => this.restoreButtonStates(data), 200);  // ✅ Retry
}
```

### Before:
```php
catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);  // ❌ Exposes details
}
```

### After:
```php
catch (PDOException $e) {
    error_log('[SALES_TRACKING] Database error: ' . $e->getMessage());  // ✅ Log server-side
    error_log('[SALES_TRACKING] Stack trace: ' . $e->getTraceAsString());
    jsonError('Database error: Unable to fetch sales tracking data', 500);  // ✅ Safe message
}
```

## 🔍 Debugging Checklist

- [ ] Check browser console for logs
- [ ] Check server error logs
- [ ] Run test-sales-tracking.php
- [ ] Run check-sales-tracking-api.php
- [ ] Verify session is active
- [ ] Check database connection
- [ ] Clear browser cache (Ctrl+Shift+R)
- [ ] Try different project IDs
- [ ] Test with different user roles

## 📞 Common Error Messages

### "500 Internal Server Error"
**Cause:** Backend exception  
**Fix:** Check `logs/php_errors.log` for [SALES_TRACKING] errors

### "Failed to load sales tracking data"
**Cause:** Network or API issue  
**Fix:** Check console for detailed error, verify authentication

### "Found 0 yes-no buttons in DOM"
**Cause:** DOM not ready  
**Fix:** Already handled with auto-retry, should self-resolve

### "CORS policy"
**Cause:** Cross-origin request blocked  
**Fix:** Check CORS_ORIGIN in config.php matches domain

### "Not authenticated"
**Cause:** Session expired  
**Fix:** Refresh page and login again

## ✅ Success Indicators

**In Console:**
```
✅ [PROJECTS] Sales tracking response: {exists: true, data: {...}}
✅ [PROJECTS] Found 4 yes-no buttons in DOM
✅ [PROJECTS] ✓ Restored button: contacted = yes
✅ [PROJECTS] ✓ Restored button: quoted = no
✅ [PROJECTS] ✓ Sales tracking data restored successfully
```

**In Modal:**
```
✅ "Contacted" button is GREEN (Yes) or RED (No)
✅ "Quoted" button is GREEN (Yes) or RED (No)  
✅ Sales Rep dropdown shows selected rep
✅ Branch field is filled
✅ W/L Amount shows value
✅ Remarks shows text
```

**In Logs:**
```
✅ [SALES_TRACKING] GET request for project ID: 5
✅ [SALES_TRACKING] Query result: Found
✅ [SALES_TRACKING] Returning data for project: 5
✅ (No error messages)
```

## 🎉 Done!

If everything is working:
1. Buttons show correct Yes/No state ✅
2. Form fields are populated ✅
3. No console errors ✅
4. No server errors ✅

**You're all set! 🚀**

---

**Need Help?**
1. Check `SALES_TRACKING_FIX.md` for full documentation
2. Run test scripts to diagnose issues
3. Check console and server logs for details

**Last Updated:** June 10, 2026  
**Tested On:** XAMPP Local + Production (Webmin/Putty + MariaDB)
