# Console Errors Fix - Project Details Modal

## 🐛 Errors Fixed

### 1. **showToast is not defined** ✅ FIXED
**Error:**
```
Uncaught ReferenceError: showToast is not defined
    at Object.loadSalesTrackingData (projects.js:1055:17)
    at projects.js:946:26
```

**Root Cause:**
- The `toast.js` file was not included in `pages/projects.php`
- The `toast.css` file was not included in `pages/projects.php`
- Functions in `projects.js` call `showToast()` but it doesn't exist

**Solution:**
Added missing includes to `pages/projects.php`:
```php
<!-- CSS -->
<link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">

<!-- JavaScript -->
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
```

**Files Modified:**
- `pages/projects.php` - Added toast.js and toast.css includes

---

### 2. **500 Internal Server Error - Sales Tracking API** ⚠️ NEEDS INVESTIGATION
**Error:**
```
GET http://datas.lan/api/v1/projects/44/sales-tracking 500 (Internal Server Error)
```

**Root Cause:**
- Backend PHP exception in `api/projects/sales-tracking.php`
- Possible causes:
  - Database connection timeout (MariaDB)
  - Missing sales_tracking table or columns
  - SQL query error
  - Authentication/session issue

**Solution Already Applied:**
Enhanced error logging in `api/projects/sales-tracking.php`:
```php
catch (PDOException $e) {
    error_log('[SALES_TRACKING] Database error: ' . $e->getMessage());
    error_log('[SALES_TRACKING] Stack trace: ' . $e->getTraceAsString());
    jsonError('Database error: Unable to fetch sales tracking data', 500);
}
```

**How to Debug:**
1. Check server logs:
   ```bash
   tail -f c:\xampp\apache\logs\error.log
   # OR
   tail -f logs/php_errors.log
   ```

2. Look for `[SALES_TRACKING]` log entries

3. Run test script:
   ```
   http://localhost/DATAS/check-sales-tracking-api.php?project_id=44
   ```

4. Check if sales_tracking table exists:
   ```
   http://localhost/DATAS/test-sales-tracking.php
   ```

**Possible Fixes:**
- If table doesn't exist: Run `database/schema.sql`
- If connection timeout: Check MariaDB is running
- If session expired: Re-login
- If project doesn't exist: Use valid project ID

---

### 3. **Load sales tracking error** ✅ FIXED (Related to #1)
**Error:**
```
[PROJECTS] Load sales tracking error:
ReferenceError: showToast is not defined
```

**Root Cause:**
- Same as error #1 - toast.js not loaded

**Solution:**
- Fixed by adding toast.js include

---

### 4. **Raw errors in console** ✅ FIXED
**Error:**
```
[PROJECTS] Raw errors:
showToast is not defined
```

**Root Cause:**
- Same as error #1

**Solution:**
- Fixed by adding toast.js include

---

## 📋 Summary of Changes

### Files Modified:

#### 1. `pages/projects.php`
**Added CSS include:**
```php
<link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
```

**Added JavaScript include:**
```php
<script src="<?= $base ?>/static/js/toast.js?v=1"></script>
```

**Location in file:**
- CSS: After `roles.css`, before `admin.css`
- JS: After `utils.js`, before `roles.js`

**Why this order matters:**
- toast.css: Needs to load before page renders to avoid FOUC (Flash of Unstyled Content)
- toast.js: Must load before projects.js (which calls showToast())

#### 2. `api/projects/sales-tracking.php` (Already fixed in previous update)
- Enhanced error logging with PDOException handling
- Added debug mode logging
- Better error messages for frontend

#### 3. `static/js/projects.js` (Already fixed in previous update)
- Enhanced error handling in loadSalesTrackingData()
- Added DOM readiness check with retry
- Improved async loading sequence

---

## 🧪 Testing Steps

### 1. Test Toast System
Open browser console and run:
```javascript
// Test if showToast is now defined
console.log(typeof showToast); // Should output: "function"

// Test toast notifications
showToast('Test message', 'success');
showToast('Warning message', 'warning');
showToast('Error message', 'error');
```

**Expected Result:**
- No "showToast is not defined" errors
- Toast notifications appear on screen

### 2. Test Project Modal
1. Open projects page
2. Open DevTools (F12) → Console tab
3. Click on any project to open modal
4. **Check for errors:**
   - ✅ No "showToast is not defined"
   - ✅ No "Load sales tracking error"
   - ⚠️ May still have 500 error if backend issue

### 3. Test Sales Tracking Load
If 500 error persists:

**A. Check Database:**
```
http://localhost/DATAS/test-sales-tracking.php
```
Expected: Shows sales_tracking table and records

**B. Test API Directly:**
```
http://localhost/DATAS/check-sales-tracking-api.php?project_id=5
```
Expected: Shows API response and database query

**C. Check Console Logs:**
```javascript
// Should see in console:
[PROJECTS] Loading sales tracking data for project: X
[PROJECTS] Fetching from URL: ...
[PROJECTS] Response status: 200 OK  // Or 500 if still broken
```

**D. Check Server Logs:**
```bash
# Look for [SALES_TRACKING] entries
tail -f c:\xampp\apache\logs\error.log
```

---

## 🎯 Expected Behavior After Fix

### Success Case (No Backend Errors):
```
Console Output:
[PROJECTS] Loading sales tracking data for project: 5
[PROJECTS] Fetching from URL: http://datas.lan/api/v1/projects/5/sales-tracking
[PROJECTS] Response status: 200 OK
[PROJECTS] Sales tracking response: {exists: true, data: {...}}
[PROJECTS] Found 4 yes-no buttons in DOM
[PROJECTS] ✓ Restored button: contacted = yes
[PROJECTS] ✓ Restored button: quoted = no
[PROJECTS] ✓ Sales tracking data restored successfully
```

**Result:**
- Modal opens smoothly
- Sales tracking buttons show correct state
- Form fields populated with data
- **NO ERRORS** in console ✅

### Failure Case (Backend Error Persists):
```
Console Output:
[PROJECTS] Loading sales tracking data for project: 44
[PROJECTS] Fetching from URL: http://datas.lan/api/v1/projects/44/sales-tracking
[PROJECTS] Response status: 500 Internal Server Error
[PROJECTS] Error response body: {"detail":"Database error: ..."}
```

**Result:**
- Toast notification shows: "Failed to load sales tracking data"
- Modal still opens but sales tracking section is empty
- **NO "showToast is not defined" ERROR** ✅

---

## 🔍 Debugging Guide

### If "showToast is not defined" Still Appears:

1. **Clear browser cache:**
   ```
   Ctrl+Shift+R (Windows)
   Cmd+Shift+R (Mac)
   ```

2. **Check if toast.js loaded:**
   - Open DevTools → Network tab
   - Reload page
   - Search for "toast.js"
   - Should show 200 status

3. **Check console:**
   ```javascript
   console.log(typeof Toast);      // Should be: "object"
   console.log(typeof showToast);  // Should be: "function"
   ```

4. **Check file path:**
   - Open: `http://localhost/DATAS/static/js/toast.js`
   - Should show JavaScript code, not 404

### If 500 Error Persists:

1. **Check table exists:**
   ```sql
   USE datas_db;
   SHOW TABLES LIKE 'sales_tracking';
   ```

2. **Check columns:**
   ```sql
   DESCRIBE sales_tracking;
   ```

3. **Check project exists:**
   ```sql
   SELECT * FROM projects WHERE id = 44;
   ```

4. **Check sales_tracking record:**
   ```sql
   SELECT * FROM sales_tracking WHERE project_id = 44;
   ```

5. **Check server logs:**
   ```bash
   # Windows (XAMPP)
   notepad c:\xampp\apache\logs\error.log
   
   # Linux (Production)
   tail -f /var/log/apache2/error.log
   ```

6. **Enable debug mode:**
   ```php
   // In config.php
   define('DEBUG_MODE', true);
   ```
   Then check logs for detailed error messages

---

## 📦 Files Structure

```
DATAS/
├── pages/
│   └── projects.php ← MODIFIED (added toast includes)
├── static/
│   ├── css/
│   │   └── toast.css ← REQUIRED (already exists)
│   └── js/
│       ├── toast.js ← REQUIRED (already exists)
│       └── projects.js ← MODIFIED (previous update)
├── api/
│   └── projects/
│       └── sales-tracking.php ← MODIFIED (previous update)
├── test-sales-tracking.php ← DEBUG TOOL
├── check-sales-tracking-api.php ← DEBUG TOOL
└── clear-in-progress-tracking.php ← UTILITY TOOL
```

---

## ✅ Verification Checklist

After deploying changes:

- [ ] Clear browser cache (Ctrl+Shift+R)
- [ ] Open projects page
- [ ] Open DevTools Console (F12)
- [ ] Click on a project to open modal
- [ ] **Verify:** No "showToast is not defined" errors
- [ ] **Verify:** Toast notifications work
- [ ] **Verify:** Sales tracking loads (or shows proper error)
- [ ] **Verify:** Console shows proper logging
- [ ] **Test:** Close and reopen modal multiple times
- [ ] **Test:** Open different projects
- [ ] **Test:** Test with different user roles

---

## 🚀 Deployment to Production

### Pre-Deployment:
```bash
# Backup current file
cp pages/projects.php pages/projects.php.backup
```

### Deployment:
```bash
# Upload modified file
# Only this file needs to be updated:
pages/projects.php

# Files that should already exist (no changes needed):
static/js/toast.js
static/css/toast.css
```

### Post-Deployment:
1. Clear browser cache
2. Test on production server
3. Monitor server logs
4. Check for any new errors

### Rollback (if needed):
```bash
# Restore backup
cp pages/projects.php.backup pages/projects.php
```

---

## 📊 Impact Assessment

**Positive:**
- ✅ Eliminates 3 major console errors
- ✅ Improves user experience (proper error notifications)
- ✅ Better debugging with toast messages
- ✅ No breaking changes to existing functionality

**Risk:**
- ⚠️ Minimal - only adding missing dependencies
- ⚠️ If toast.js/css missing, will revert to console errors (same as before)

**Performance:**
- 📈 Negligible impact (2 small files: ~10KB total)
- 📈 Toast system uses efficient CSS animations
- 📈 No additional API calls

---

## 🎓 What We Learned

1. **Missing Dependencies:** Always check if utility functions (like showToast) have their required files loaded

2. **Load Order Matters:** JavaScript files must be loaded in correct order:
   - Utils/helpers first
   - Libraries next
   - App-specific code last

3. **Error Propagation:** One missing file (toast.js) caused multiple errors throughout the app

4. **Better Debugging:** Enhanced error logging helps identify backend issues faster

---

**Last Updated:** June 10, 2026  
**Status:** ✅ Frontend errors FIXED, ⚠️ Backend 500 error needs investigation  
**Files Changed:** 1 file (pages/projects.php)  
**Impact:** Low risk, high benefit
