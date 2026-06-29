# Priority vs Non-Priority Projects Fix

## Problem
Non-priority projects were appearing in the Priority Projects page because the system was using the `status` field to filter. When a non-priority project's status was changed to "Priority", it appeared in the Priority Projects view even though it wasn't encoded using the Priority form.

## Root Cause
- No distinction between projects **encoded via Priority form** vs projects with **status="Priority"**
- The `status` field is editable and doesn't reliably indicate the encode type
- Both Priority Projects page and Non-Priority Projects page were filtering based on `status` field only

## Solution
Added a new database column `is_priority_encoded` to permanently mark which form was used to encode each project:
- `is_priority_encoded = 'yes'` → Encoded via Priority form (pages/encode/priority.php)
- `is_priority_encoded = 'no'` → Encoded via Non-Priority form (pages/encode/non-priority.php)

## Changes Made

### 1. Database Migration
**Files:**
- `database/add-is-priority-encoded-column.sql` - SQL migration script
- `migrate-is-priority-encoded.php` - PHP migration script

**What it does:**
- Adds `is_priority_encoded` ENUM('yes', 'no') column to `projects` table
- Adds index for faster filtering
- Migrates existing data: projects with `status='Priority'` → `is_priority_encoded='yes'`

### 2. API Updates
**File:** `api/projects/index.php`

**Changes:**
- **GET endpoint:** Updated filtering logic to use `is_priority_encoded` field instead of `status`
  - `type=priority` now filters by `is_priority_encoded = 'yes'`
  - `type=non-priority` now filters by `is_priority_encoded = 'no'`
  - Falls back to status-based filtering if column doesn't exist yet (backward compatibility)

- **POST endpoint:** Updated project creation to set `is_priority_encoded` field
  - Checks if `encode_type='priority'` is in request body
  - Checks if `status='Priority'`
  - Sets `is_priority_encoded='yes'` for priority encoded projects

### 3. Frontend Updates
**File:** `static/js/encode-priority.js`
- Added `encode_type: 'priority'` to payload in `buildPayload()` method
- This tells the API that this project was encoded via Priority form

**File:** `static/js/projects.js`
- Updated filtering logic to use `is_priority_encoded` field
- Falls back to status-based filtering for backward compatibility

## Migration Instructions

### Step 1: Run Database Migration
Open browser and navigate to:
```
http://datas.lan/migrate-is-priority-encoded.php
```

Or run via command line:
```bash
php migrate-is-priority-encoded.php
```

**Expected output:**
```
===== MIGRATION: Add is_priority_encoded Column =====

→ Adding 'is_priority_encoded' column...
✓ Column added successfully.
→ Adding index on 'is_priority_encoded'...
✓ Index added successfully.

→ Migrating existing priority projects...
✓ Migrated X existing priority projects.

===== MIGRATION COMPLETE =====
```

### Step 2: Verify Changes
1. **Check Priority Projects page** (`/projects?type=priority`)
   - Should only show projects that were encoded via Priority form
   - Projects with status="Priority" but encoded via Non-Priority form should NOT appear

2. **Check Non-Priority Projects page** (`/projects?type=non-priority`)
   - Should show all projects encoded via Non-Priority form
   - Even if their status was later changed to "Priority"

3. **Test Encoding**
   - Encode a new project via **Priority form** → should appear in Priority Projects
   - Encode a new project via **Non-Priority form** → should appear in Non-Priority Projects
   - Change a Non-Priority project's status to "Priority" → should still stay in Non-Priority Projects

## Rollback (if needed)
If you need to revert the changes:

```sql
-- Remove the column
ALTER TABLE projects DROP COLUMN is_priority_encoded;

-- Remove the index
ALTER TABLE projects DROP INDEX idx_is_priority_encoded;
```

Then revert the code changes in:
- `api/projects/index.php`
- `static/js/projects.js`
- `static/js/encode-priority.js`

## Files Modified
1. `database/add-is-priority-encoded-column.sql` (NEW)
2. `migrate-is-priority-encoded.php` (NEW)
3. `api/projects/index.php` (UPDATED)
4. `static/js/encode-priority.js` (UPDATED)
5. `static/js/projects.js` (UPDATED)

## Technical Notes

### Database Schema Change
```sql
ALTER TABLE `projects` 
ADD COLUMN `is_priority_encoded` ENUM('yes', 'no') NOT NULL DEFAULT 'no' 
COMMENT 'Marks if project was encoded via Priority form' 
AFTER `status`;
```

### Backward Compatibility
The code includes fallback logic to work even if the migration hasn't been run yet:
- Checks if `is_priority_encoded` column exists before using it
- Falls back to `status`-based filtering if column doesn't exist

### Future Considerations
- The `status` field can still be edited freely (e.g., "For Bidding", "Awarded", etc.)
- The `is_priority_encoded` field is set ONCE at project creation and shouldn't be modified
- This ensures projects stay in their original encode category regardless of status changes
