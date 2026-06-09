# DATAS Production Deployment Guide

## Pre-Deployment Checklist

### 1. Database Setup
- [ ] Create MySQL database `datas_db` on production server
- [ ] Create database user with appropriate permissions
- [ ] Import the database schema: `mysql -u [user] -p [database] < database/schema.sql`
- [ ] Verify all tables are created successfully

### 2. Configuration Updates
- [ ] Update `config.php` with production database credentials
- [ ] Set `CORS_ORIGIN` to your production domain
- [ ] Ensure `DEBUG_MODE` is set to `false`
- [ ] Update `APP_ENV` to `'production'`

### 3. File Permissions
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Create and secure the uploads directory
- [ ] Ensure web server can write to session directories

### 4. Security Configuration
- [ ] Enable HTTPS/SSL certificate
- [ ] Configure security headers
- [ ] Update password requirements if needed
- [ ] Test rate limiting functionality

## Common Issues & Solutions

### Issue: 500 Internal Server Error on `/api/auth/login.php`

**Causes:**
1. Database connection failure
2. Missing PHP extensions
3. File permission issues
4. Configuration errors

**Solutions:**
1. Check database credentials in `config.php`
2. Verify PHP extensions: `mysqli`, `pdo_mysql`, `json`
3. Check web server error logs
4. Test database connection manually

### Issue: CORS Errors

**Solution:** Update `CORS_ORIGIN` in `config.php` to match your domain.

### Issue: Session Issues

**Solutions:**
1. Ensure session directory is writable
2. Check session configuration in `php.ini`
3. Verify session timeout settings

## Testing Steps

1. **Database Connection Test:**
   ```php
   // Create test-db-connection.php
   <?php
   require_once 'config.php';
   require_once 'api/db.php';
   
   try {
       $db = getDB();
       echo "Database connection successful!";
   } catch (Exception $e) {
       echo "Database connection failed: " . $e->getMessage();
   }
   ?>
   ```

2. **API Endpoint Test:**
   - Test GET `/api/available-months.php`
   - Test POST `/api/auth/login.php` with valid credentials

3. **Frontend Test:**
   - Load the main application
   - Test login functionality
   - Verify all static assets load correctly

## Production Environment Requirements

- PHP 8.0+ with extensions: `mysqli`, `pdo_mysql`, `json`, `mbstring`, `openssl`
- MySQL 5.7+ or MariaDB 10.2+
- Apache 2.4+ or Nginx with PHP-FPM
- HTTPS enabled
- Adequate disk space for uploads and logs

## Security Recommendations

1. Use strong database passwords (20+ characters)
2. Enable fail2ban or similar intrusion prevention
3. Regular security updates for OS and software
4. Set up automated backups
5. Monitor error logs regularly
6. Use a Web Application Firewall (WAF)

## Troubleshooting Commands

### Check Web Server Logs
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

### Check PHP Errors
```bash
tail -f /var/log/php_errors.log
```

### Test Database Connection
```bash
mysql -u [username] -p -h [host] [database]
```

### Check File Permissions
```bash
find /path/to/datas -type f -exec chmod 644 {} \;
find /path/to/datas -type d -exec chmod 755 {} \;
```

## Contact Support
If issues persist, check the browser console and server error logs for specific error messages.