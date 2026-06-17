# Philippine Timezone Implementation Guide

## Overview
All timestamps in the DATAS system now use **Philippine Time (UTC+8 / Asia/Manila)**.

## What Was Changed

### 1. PHP Configuration
**File: `config.php`**
- Added `date_default_timezone_set('Asia/Manila');` at the top
- Defined constants:
  - `APP_TIMEZONE = 'Asia/Manila'`
  - `DB_TIMEZONE = '+08:00'`

### 2. Database Connection
**File: `api/db.php`**
- MySQL connection now sets timezone to `+08:00` on every connection
- Updated `PDO::MYSQL_ATTR_INIT_COMMAND` to include:
  ```php
  "SET sql_mode='', time_zone='+08:00'"
  ```

### 3. JavaScript DateTime Formatter
**New File: `static/js/date-formatter-ph.js`**
- Created comprehensive Philippine DateTime utility
- Available globally as `window.PhilippineDateTime`

#### Available Methods:
```javascript
// Format full date and time
PhilippineDateTime.formatDateTime(date)
// Output: "6/17/2026, 3:12:33 PM"

// Format short date and time
PhilippineDateTime.formatDateTimeShort(date)
// Output: "6/17/2026, 3:12 PM"

// Format date only
PhilippineDateTime.formatDate(date)
// Output: "June 17, 2026"

// Format short date
PhilippineDateTime.formatDateShort(date)
// Output: "Jun 17, 2026"

// Format numeric date
PhilippineDateTime.formatDateNumeric(date)
// Output: "6/17/2026"

// Format time with seconds
PhilippineDateTime.formatTime(date)
// Output: "3:12:33 PM"

// Format time without seconds
PhilippineDateTime.formatTimeShort(date)
// Output: "3:12 PM"

// Format relative time
PhilippineDateTime.formatRelative(date)
// Output: "2 hours ago", "in 3 days"

// For Activity Logs
PhilippineDateTime.formatActivityLog(date)
// Output: "6/17/2026, 3:12:33 AM"

// For table displays
PhilippineDateTime.formatTableDate(date)
// Output: "Today, 3:12 PM" or "6/17/2026, 3:12 PM"

// Get current Philippine time
PhilippineDateTime.now()
PhilippineDateTime.currentTime()      // "3:12:33 PM"
PhilippineDateTime.currentTimeShort() // "3:12 PM"
```

### 4. Activity Logs
**File: `static/js/activity-logs.js`**
- Updated `renderLogs()` to use Philippine DateTime
- Timestamps now display in Philippine timezone

**File: `pages/activity-logs.php`**
- Added Philippine DateTime script

### 5. Reports Dashboard
**File: `pages/reports.php`**
- Updated clock display to use Philippine timezone
- Added Philippine DateTime script
- Sync time display uses Philippine timezone

## How to Use in Your Code

### PHP Backend
PHP will automatically use Philippine timezone for all date/time operations:
```php
// This will return Philippine time
$now = date('Y-m-d H:i:s');

// Or using DateTime
$dt = new DateTime();
echo $dt->format('Y-m-d H:i:s'); // Philippine time
```

### JavaScript Frontend
**Option 1: Use Philippine DateTime utility (Recommended)**
```javascript
// Include the script first
<script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>

// Then use it
const formattedDate = PhilippineDateTime.formatDateTime(myDate);
```

**Option 2: Use native Intl API**
```javascript
const date = new Date(timestamp);
const formatted = date.toLocaleString('en-PH', { 
    timeZone: 'Asia/Manila',
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
});
```

## Pages Updated
✅ Activity Logs (`pages/activity-logs.php`)
✅ Reports Dashboard (`pages/reports.php`)
✅ Database Connection (`api/db.php`)
✅ Main Config (`config.php`)

## Pages That Need Manual Update
The following pages may still need the Philippine DateTime script added:

1. **Admin Dashboard** (`pages/admin.php`)
   - Add script: `<script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>`
   - Update any date displays

2. **Projects Pages** (`pages/projects.php`, `pages/my-projects.php`)
   - Add script and update date columns

3. **Sales Representatives** (`pages/sales-reps.php`)
   - Add script and update date columns

4. **All other pages with timestamps**
   - Look for: `new Date().toLocaleString()`, `toLocaleDateString()`, etc.
   - Replace with Philippine DateTime methods

## How to Add to Any Page

1. **Add the script before closing `</body>` tag:**
```html
<script src="<?= $base ?>/static/js/date-formatter-ph.js?v=1"></script>
```

2. **Update date formatting in JavaScript:**
```javascript
// Before
const timestamp = new Date(log.created_at).toLocaleString();

// After
const timestamp = PhilippineDateTime.formatDateTime(log.created_at);
```

## Testing
1. Clear browser cache
2. Check Activity Logs page - timestamps should show Philippine time
3. Check Reports Dashboard - clock should show Philippine time
4. Compare with actual Philippine time: https://time.is/Manila

## Notes
- All timestamps stored in database are still in UTC (recommended)
- PHP and JavaScript convert to Philippine time for display
- This ensures consistency across all timezones
- Users in different timezones will see Philippine time (UTC+8)

## Troubleshooting

### Problem: Dates still showing wrong timezone
**Solution:** 
1. Clear browser cache
2. Hard refresh (Ctrl+Shift+R)
3. Check if Philippine DateTime script is loaded:
   ```javascript
   console.log(window.PhilippineDateTime); // Should not be undefined
   ```

### Problem: Server time is wrong
**Solution:**
1. Restart Apache/PHP-FPM
2. Check PHP timezone: `<?php echo date_default_timezone_get(); ?>`
3. Should return: `Asia/Manila`

### Problem: Database timestamps are wrong
**Solution:**
1. Restart MySQL connection
2. Check MySQL timezone: `SELECT @@session.time_zone;`
3. Should return: `+08:00`
