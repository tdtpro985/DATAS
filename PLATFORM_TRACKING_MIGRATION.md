# Platform Tracking Migration Guide

## Overview
This migration adds sales tracking functionality for Platform Leads, similar to the existing Project sales tracking.

## Files Updated
1. **API Router** (`api/router.php`)
   - Added route: `platforms/tracking` (GET/POST)

2. **Database Schema Files**
   - `database/schema.sql` - Added platform_tracking table
   - `database/datas_db.sql` - Added platform_tracking table

3. **Migration Files Created**
   - `database/create-platform-tracking-table.sql` - SQL for creating table
   - `migrate-platform-tracking.php` - PHP migration script

4. **Frontend**
   - `pages/platforms.php` - Fixed API call to use correct endpoint

## Migration Steps (Production)

### Option 1: Via Webmin/SSH
```bash
# 1. Upload files to server
# 2. SSH into server
cd /path/to/DATAS

# 3. Run migration
php migrate-platform-tracking.php
```

### Option 2: Via MySQL Command Line
```bash
# 1. SSH into server
# 2. Run SQL directly
mysql -u datas_user -p datas_db < database/create-platform-tracking-table.sql
```

### Option 3: Via phpMyAdmin
1. Login to phpMyAdmin
2. Select `datas_db` database
3. Go to SQL tab
4. Copy and paste contents of `database/create-platform-tracking-table.sql`
5. Execute

## Table Structure

```sql
CREATE TABLE `platform_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform_id` int(11) NOT NULL,
  `contacted` tinyint(1) DEFAULT NULL,
  `quoted` tinyint(1) DEFAULT NULL,
  `sales_qualified` tinyint(1) DEFAULT NULL,
  `to_win` tinyint(1) DEFAULT NULL,
  `wa_amount` decimal(18,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `sales_rep_id` int(11) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_id` (`platform_id`),
  KEY `sales_rep_id` (`sales_rep_id`),
  CONSTRAINT `fk_platform_tracking_platform` 
    FOREIGN KEY (`platform_id`) 
    REFERENCES `platform_leads` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_platform_tracking_sales_rep` 
    FOREIGN KEY (`sales_rep_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Verification

After migration, verify:

```sql
-- Check if table exists
SHOW TABLES LIKE 'platform_tracking';

-- Check table structure
DESCRIBE platform_tracking;

-- Check foreign keys
SHOW CREATE TABLE platform_tracking;
```

## Rollback (if needed)

```sql
DROP TABLE IF EXISTS `platform_tracking`;
```

## Testing

1. Login to the system
2. Navigate to **Platform Leads**
3. Click on any platform lead
4. Scroll to **Sales Tracking** section
5. Fill out the form and click **Save Sales Tracking**
6. Verify data is saved successfully

## Notes
- The table uses the same structure as project sales tracking
- One tracking record per platform (enforced by UNIQUE KEY on platform_id)
- Foreign keys ensure data integrity
- CASCADE delete removes tracking when platform is deleted
- SET NULL keeps tracking when sales rep is deleted

## Date
June 19, 2026
