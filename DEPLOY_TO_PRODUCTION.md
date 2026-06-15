# 🚀 Deployment Guide for Production Server

## Problem: Reports Dashboard walang data after deploy

### Root Cause:
**PHP OPcache** sa production server ay nag-cache pa ng old bytecode kahit updated na yung files. Kaya kahit nag-upload ka ng bagong code, yung server ay gumagamit pa rin ng luma.

---

## 📋 Deployment Checklist

### Step 1: Upload Files via Webmin/Putty
Upload ang lahat ng modified files:
```bash
# Via SCP/SFTP
api/kpi.php
api/projects/*.php
api/charts/*.php
api/contractors/ranking.php
api/helpers.php
api/live-slideshow.php
pages/reports.php
config.php
clear-cache.php
```

### Step 2: Clear Server Cache
**Option A: Via Browser (Easiest)**
1. Login sa admin account
2. Open: `https://yourdomain.com/clear-cache.php`
3. Check kung "✅ PHP OPcache Cleared successfully"

**Option B: Via SSH/Putty**
```bash
# SSH into server
ssh user@yourserver.com

# Restart PHP-FPM (CentOS/RHEL)
sudo systemctl restart php-fpm

# OR Restart Apache if using mod_php
sudo systemctl restart httpd

# OR Restart Apache (Ubuntu/Debian)
sudo systemctl restart apache2
```

**Option C: Via Webmin**
1. Go to: System → Bootup and Shutdown
2. Find "php-fpm" or "httpd" service
3. Click "Restart"

### Step 3: Verify Cache Clear
1. Open: `https://yourdomain.com/api/v1/kpi`
2. Should return JSON with data
3. If still returns empty, cache didn't clear properly

### Step 4: User Browser Cache
Tell users to **hard refresh**:
- **Windows:** Ctrl + F5
- **Mac:** Cmd + Shift + R
- **Chrome:** Ctrl + Shift + Delete → Clear browsing data

---

## 🔍 Verification Steps

### Test 1: API Endpoints
Open these URLs directly in browser (while logged in):

```
https://yourdomain.com/api/v1/kpi
→ Should return: {"data":{"projects_encoded":28,...}}

https://yourdomain.com/api/v1/available-months
→ Should return: {"months":[...],"total_months":12}

https://yourdomain.com/api/v1/charts/regional-stats
→ Should return: {"regions":[...],"values":[...]}
```

### Test 2: Reports Dashboard
1. Go to: `https://yourdomain.com/reports`
2. Open Browser Console (F12)
3. Check for logs starting with `[API]`
4. Should see successful API calls with data

### Test 3: Check Console Logs
Expected output:
```
Dashboard initialized successfully
[API] Fetching: .../api/v1/kpi?period=monthly
[API] Response status: 200
[API] Data received: {data: {projects_encoded: 28, ...}}
```

---

## 🛠️ If Still Not Working

### Problem 1: Cache didn't clear
**Solution:**
```bash
# Via SSH
sudo systemctl restart php-fpm
sudo systemctl restart httpd

# OR manually clear OPcache directory
sudo rm -rf /var/lib/php/opcache/*
```

### Problem 2: File permissions
**Solution:**
```bash
# Make sure web server can read files
cd /path/to/datas
sudo chown -R apache:apache .
sudo chmod -R 755 .
```

### Problem 3: Old code still running
**Solution:**
```bash
# Check if files are actually updated
ls -la api/kpi.php
# Should show recent timestamp

# Check file contents
head -20 api/kpi.php
# Should NOT have "is_actual_project" in WHERE clause
```

### Problem 4: SELinux blocking (CentOS/RHEL)
**Solution:**
```bash
# Temporarily disable SELinux to test
sudo setenforce 0

# If that fixes it, set proper context
sudo restorecon -Rv /path/to/datas
```

---

## 📊 Quick Health Check

Run this on the server to verify everything:

```bash
# Check PHP version and OPcache
php -v
php -i | grep opcache

# Check if files were updated
stat /path/to/datas/api/kpi.php

# Check Apache/PHP-FPM status
sudo systemctl status php-fpm
sudo systemctl status httpd

# Check recent Apache errors
sudo tail -50 /var/log/httpd/error_log
```

---

## 🎯 Production Best Practices

### 1. Always Clear Cache After Deploy
```bash
# Add to deployment script
sudo systemctl restart php-fpm
```

### 2. Update APP_VERSION in config.php
```php
define('APP_VERSION', '20260611110426'); // Change timestamp
```

### 3. Monitor PHP Error Logs
```bash
sudo tail -f /var/log/php-fpm/error.log
sudo tail -f /var/log/httpd/error_log
```

### 4. Test in Browser Incognito Mode
Avoids browser cache issues during testing

---

## 📞 Emergency Rollback

If new code causes issues:

```bash
# Restore from backup
cd /path/to/datas
sudo cp -r ../datas.backup/* .

# Restart services
sudo systemctl restart php-fpm httpd

# Clear cache
curl https://yourdomain.com/clear-cache.php
```

---

## ✅ Success Indicators

Dashboard is working when:
- ✅ API endpoints return JSON with data
- ✅ Browser console shows `[API] Data received` logs
- ✅ KPI cards show numbers (not "0")
- ✅ Charts render with data
- ✅ No "No Data Available" messages

---

## 🔧 Server Configuration Check

Ensure these are enabled in php.ini:

```ini
opcache.enable=1
opcache.revalidate_freq=0  ; For production, check files every request
opcache.validate_timestamps=1  ; Allow checking for changes
```

To check current settings:
```bash
php -i | grep opcache
```

---

## 📝 Deployment Log Template

Keep track of deploys:

```
Date: 2026-06-11 11:04
Files Updated: api/kpi.php, api/helpers.php, pages/reports.php
Cache Cleared: ✅ Yes (via clear-cache.php)
Verified: ✅ API returns data
Browser Refresh: ✅ Users notified
Issues: None
```

---

## 🚨 Common Pitfalls

1. ❌ Forgot to clear OPcache → Old code runs
2. ❌ Forgot to hard refresh browser → Sees old JavaScript
3. ❌ Wrong file permissions → Server can't read files          
4. ❌ Database password wrong in config.production.php
5. ❌ SELinux blocking file access

---

**Remember:** After EVERY deploy, always run `clear-cache.php` or restart PHP-FPM!
