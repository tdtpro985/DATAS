# Reports Page - Archived & Illegitimate Projects Filter Fix

## Problem
The Reports/Dashboard page was including:
- ✗ **Archived projects** (`archived_at IS NOT NULL`)
- ✗ **Illegitimate projects** (`is_actual_project = 'no'`)

This inflated KPIs, charts, and statistics with projects that shouldn't be counted.

---

## Files Fixed

### 1. **api/kpi.php** - Main KPI Summary
**What it does**: Returns total projects, contractors, and pipeline value  
**Fix**: Added filter to exclude archived and illegitimate projects

```php
// Added this line:
$where .= " AND (archived_at IS NULL OR archived_at = '') AND (is_actual_project IS NULL OR is_actual_project != 'no')";
```

### 2. **api/contractors/ranking.php** - Top Contractors List
**What it does**: Returns contractors ranked by total project value  
**Fix**: Added same exclusion filter

### 3. **api/charts/funnel.php** - Sales Funnel Stages
**What it does**: Returns sales funnel data (Prospects → Contacted → SQL → Quoted → Win)  
**Fix**: Added filter with table alias `p.` since it joins with sales_tracking

```php
$where .= " AND (p.archived_at IS NULL OR p.archived_at = '') AND (p.is_actual_project IS NULL OR p.is_actual_project != 'no')";
```

### 4. **api/charts/regional-stats.php** - Regional Project Statistics
**What it does**: Returns project counts and values per region  
**Fix**: Added exclusion filter

### 5. **api/charts/pie.php** - Materials Breakdown (Pie Chart)
**What it does**: Returns Sheet Pile and DRBS totals for pie chart  
**Fix**: Added exclusion filter for both UNION queries

### 6. **api/live-slideshow.php** - Rotating Contractor Display
**What it does**: Returns random project for live slideshow  
**Fix**: Added exclusion filter

---

## Filter Logic

The filter excludes projects where:

```sql
(archived_at IS NULL OR archived_at = '') 
AND 
(is_actual_project IS NULL OR is_actual_project != 'no')
```

**Translation**:
- `archived_at IS NULL OR archived_at = ''` → Project is **not archived**
- `is_actual_project IS NULL OR is_actual_project != 'no'` → Project is **legitimate** (not marked as fake/illegitimate)

---

## Impact

### Before Fix:
- KPIs included all projects (active + archived + illegitimate)
- Charts showed inflated numbers
- Contractor rankings included archived project values
- Regional stats were inaccurate

### After Fix:
- ✅ Only **active, legitimate projects** are counted
- ✅ Archived projects excluded from all reports
- ✅ Illegitimate projects (fake/spam) excluded
- ✅ Accurate KPIs and statistics
- ✅ Correct contractor rankings
- ✅ Reliable regional data

---

## Testing Checklist

### Test Case 1: Archive a Project
1. Archive any project from Project Management
2. Refresh Reports page
3. **Expected**: Total project count decreased by 1
4. **Expected**: Project no longer appears in charts/rankings

### Test Case 2: Mark Project as Illegitimate
1. Open any project modal
2. Set "Is this legitimate?" → **No**
3. Refresh Reports page
4. **Expected**: Total project count decreased by 1
5. **Expected**: Project excluded from all calculations

### Test Case 3: Restore Archived Project
1. Go to Project Management → Archived tab
2. Restore an archived project
3. Refresh Reports page
4. **Expected**: Project reappears in all reports

### Test Case 4: Mark Illegitimate as Legitimate
1. Go to Illegitimate Projects page
2. Mark a project as "Legitimate"
3. Refresh Reports page
4. **Expected**: Project now included in reports

---

## API Endpoints Modified

| Endpoint | Description | Table Alias |
|----------|-------------|-------------|
| `/api/v1/kpi` | Main KPIs | (none) |
| `/api/v1/contractors/ranking` | Top contractors | (none) |
| `/api/v1/charts/funnel` | Sales funnel | `p.` |
| `/api/v1/charts/regional-stats` | Regional breakdown | (none) |
| `/api/v1/charts/pie` | Materials pie chart | (none) |
| `/api/v1/live-slideshow` | Rotating contractor | (none) |

---

## Database Fields Used

- `archived_at` (TIMESTAMP) - Set when project is archived, NULL for active
- `is_actual_project` (ENUM: 'yes', 'no', NULL) - 'no' means illegitimate/fake

---

## Deployment Steps

1. **Backup** production database (just in case)
2. **Deploy** all 6 modified PHP files:
   - `api/kpi.php`
   - `api/contractors/ranking.php`
   - `api/charts/funnel.php`
   - `api/charts/regional-stats.php`
   - `api/charts/pie.php`
   - `api/live-slideshow.php`

3. **Clear browser cache** (Ctrl+Shift+R)
4. **Test** all dashboard sections
5. **Verify** KPIs are accurate

---

## Notes

- No database migration needed - only PHP API changes
- No frontend JavaScript changes needed
- Reports page automatically gets clean data from APIs
- Compatible with existing filtering (month/year/region)

---

**Date**: June 10, 2026  
**Issue**: Reports showing archived and illegitimate projects  
**Status**: ✅ FIXED
