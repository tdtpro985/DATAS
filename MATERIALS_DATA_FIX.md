# Materials Data Display Issue - Fixed

## Problem Summary
DRBS (Amount) encoded in **Non-Priority Projects** was not displaying in the Project Details modal.

## Root Cause Analysis

### Database Structure
The database has **2 fields for each material** in Priority projects:
1. **Type field** (text) - stores material name/specification
2. **Amount field** (decimal) - stores monetary value

| Material | Type Field | Amount Field |
|----------|-----------|--------------|
| Sheet Pile | `sheet_pile_type` (varchar) | `sheet_pile_amount` (decimal) |
| DRBS | `drbs` (text) | `drbs_value` (decimal) |
| MS Plate | ❌ No type field | `ms_plate` (decimal) |
| Angle Bars | ❌ No type field | `angle_bars` (decimal) |
| Channel Bars | ❌ No type field | `channel_bars` (decimal) |
| Wide Flange | ❌ No type field | `wide_flange` (decimal) |
| GI/BI | ❌ No type field | `gi_bi` (decimal) |

### Priority vs Non-Priority Differences

**PRIORITY PROJECTS:**
- Have TYPE + AMOUNT for Sheet Pile and DRBS
- Form fields:
  - Sheet Pile Material → saves to `sheet_pile_type`
  - Sheet Pile Value → saves to `sheet_pile_amount`
  - DRBs Material → saves to `drbs`
  - DRBs Value → saves to `drbs_value`

**NON-PRIORITY PROJECTS:**
- Only have AMOUNT fields (no material types)
- All materials are amounts only

### The Bug

In `static/js/encode-non-priority.js` (line 561):
```javascript
// ❌ BEFORE (Wrong)
drbs: getFieldNumber('drbs'),  // Saving to 'drbs' which is TEXT field for material type
```

This was saving the DRBS **amount** to the `drbs` **type** field (text), which is meant for storing material names like "DRBS Type A - Standard Reinforcing Bars".

The modal was correctly reading from `drbs_value` (decimal field), so it showed "—" (no data).

## The Fix

Changed line 561 in `static/js/encode-non-priority.js`:
```javascript
// ✅ AFTER (Correct)
drbs_value: getFieldNumber('drbs'),  // Now saving to 'drbs_value' decimal field
```

## Verification Checklist

### ✅ What's Working Correctly
- ✅ Modal display reads from `drbs_value` field
- ✅ Priority projects save DRBS type to `drbs` and amount to `drbs_value`
- ✅ Non-Priority Sheet Pile amount saves to `sheet_pile_amount`
- ✅ All other materials (MS Plate, Angle Bars, etc.) save correctly

### ✅ What Was Fixed
- ✅ Non-Priority DRBS amount now saves to `drbs_value` instead of `drbs`
- ✅ DRBS amounts will now display in modal for Non-Priority projects

## Testing Instructions

### Test Case 1: New Non-Priority Project with DRBS
1. Go to Encode → Non-Priority Projects
2. Fill all required fields in Step 1 & 2
3. In Step 3 (Materials), enter DRBS amount: `100000`
4. Submit the form
5. Open the project modal in Project Leads
6. **Expected**: DRBS (Amount) shows ₱100,000.00
7. **Previous**: Showed "—"

### Test Case 2: Priority Project (Should Still Work)
1. Go to Encode → Priority Projects  
2. Fill all required fields in Steps 1, 2, 3
3. In Materials:
   - DRBs Material: "DRBS Type A"
   - DRBs Value: `200000`
4. Submit and open modal
5. **Expected**: 
   - DRBs Type: "DRBS Type A"
   - DRBS (Amount): ₱200,000.00

### Test Case 3: Existing Data
- Old non-priority projects with DRBS saved to wrong field won't show (data already in `drbs` text field)
- New entries after this fix will save correctly
- Priority projects unaffected

## Data Migration (Optional)

If you have existing non-priority projects with DRBS amounts saved in the wrong field, run this SQL:

```sql
-- Check for non-priority projects with data in wrong field
SELECT id, project_name, drbs, drbs_value 
FROM projects 
WHERE status != 'Priority' 
AND drbs IS NOT NULL 
AND drbs_value IS NULL;

-- If the drbs field contains numeric values (not text like "DRBS Type A"), migrate:
UPDATE projects 
SET drbs_value = CAST(drbs AS DECIMAL(18,2)),
    drbs = NULL
WHERE status != 'Priority' 
AND drbs IS NOT NULL 
AND drbs REGEXP '^[0-9.]+$'  -- Only numeric values
AND drbs_value IS NULL;
```

## Summary

✅ **Fixed**: Non-Priority DRBS amounts now save to correct `drbs_value` field  
✅ **No Impact**: Priority projects continue working as before  
✅ **Modal Display**: Already reading from correct field, just needed data in right place  
✅ **All Other Materials**: Were already correct (MS Plate, Angle Bars, etc.)

## Files Changed
- `static/js/encode-non-priority.js` - Line 561 (1 line changed)

---
**Date**: June 10, 2026  
**Issue**: DRBS amount not displaying in Non-Priority project modals  
**Status**: ✅ RESOLVED
