# Migration Instructions for Archive Columns

## Problem
The archiving feature is failing with HTTP 500 error because the `archived_at` and `archived_by` columns don't exist in the production database.

## Solution
Run the migration script on your production MariaDB database.

### Method 1: Using MySQL Command Line via PuTTY

1. Connect to your server via PuTTY
2. Navigate to your project directory (where the SQL file is located)
3. Run one of these commands:

**Option A - Direct file execution:**
```bash
mysql -u your_username -p datas_db < database/add-archive-columns.sql
```

**Option B - Interactive MySQL shell:**
```bash
mysql -u your_username -p
```
Then in the MySQL prompt:
```sql
USE datas_db;
source database/add-archive-columns.sql;
```

### Method 2: Manual SQL Commands

If you prefer to run commands manually, connect to your database and run:

```sql
USE datas_db;

-- Add archived_at column
ALTER TABLE projects ADD COLUMN archived_at DATETIME DEFAULT NULL AFTER encoded_by;

-- Add archived_by column
ALTER TABLE projects ADD COLUMN archived_by INT(10) UNSIGNED DEFAULT NULL AFTER archived_at;

-- Add indexes for better performance
CREATE INDEX idx_archived_at ON projects(archived_at);
CREATE INDEX idx_archived_by ON projects(archived_by);

-- Verify the columns were added
SHOW COLUMNS FROM projects LIKE 'archived%';
```

### Method 3: Using phpMyAdmin or Webmin SQL Interface

If your Webmin has a SQL interface or phpMyAdmin:

1. Log into Webmin
2. Navigate to MySQL/MariaDB Database Server
3. Select the `datas_db` database
4. Go to SQL Query interface
5. Copy and paste the contents of `database/add-archive-columns.sql`
6. Execute

### Verification

After running the migration, verify it worked:

```sql
USE datas_db;
DESCRIBE projects;
```

You should see `archived_at` and `archived_by` columns in the output.

### Test the Archive Feature

Once the migration is complete:
1. Refresh your application
2. Try archiving a project again
3. It should now work without errors!

## Notes
- The migration script is safe to run multiple times (it checks if columns exist before adding them)
- Replace `your_username` with your actual MySQL username
- Make sure you're running this on the production database, not your local one
