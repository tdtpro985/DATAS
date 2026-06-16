# Encode Projects Validation Fix

## Issue Reported
Projects were being saved even when **Province** and **City** fields were empty, showing "—" in the project details modal.

## Root Cause Analysis

### 1. **HTML Forms Had `novalidate` Attribute**
Both `encode/non-priority.php` and `encode/priority.php` had `<form id="encodeForm" novalidate>`, which **completely disables browser HTML5 validation**. Even though the input fields had `required` attributes, they were being ignored.

### 2. **JavaScript Validation Was Too Simple**
The `validateStep()` function only checked `if (!field.value)` which could pass for whitespace-only values and didn't provide clear feedback to users about which fields were missing.

### 3. **No Server-Side Validation**
The API endpoint `/api/v1/projects` (in `api/projects/index.php`) didn't validate Province and City fields, so even if frontend validation was bypassed, the server would still accept incomplete data.

---

## Fixes Applied

### ✅ **Client-Side Validation (JavaScript)**

#### **File: `static/js/encode-non-priority.js`**
#### **File: `static/js/encode-priority.js`**

**Enhanced the `validateStep()` function to:**

1. **Check for empty and whitespace-only values** using `.trim()`
2. **Highlight missing fields visually** with red border and shadow
3. **Show specific field names** in error message (e.g., "Please fill in: Province, City")
4. **Log missing fields to console** for debugging
5. **Clear error styling** when users start typing in the field

**Changes:**
```javascript
// Before: Simple check
if (!field || !field.value) {
    return false;
}

// After: Enhanced validation with feedback
if (!field || !field.value || field.value.trim() === '') {
    // Add visual feedback
    if (field) {
        field.style.borderColor = '#ef4444';
        field.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.2)';
    }
    
    // Get field label for error message
    const label = document.querySelector(`label[for="${fieldId}"]`);
    const fieldName = label ? label.textContent.replace(' *', '').trim() : fieldId;
    missingFields.push(fieldName);
}
```

**Added input event listener** to clear error styling when user starts typing:
```javascript
this.form.addEventListener('input', (e) => {
    if (e.target.matches('input, select')) {
        e.target.style.borderColor = '';
        e.target.style.boxShadow = '';
    }
});
```

---

### ✅ **Server-Side Validation (PHP API)**

#### **File: `api/projects/index.php`**

**Added validation for all 4 location fields:**
- Contract Province
- Contract City  
- Project Province
- Project City

**Changes:**
```php
// Validate Province and City fields (both Contract and Project locations)
$contractProvince = trim($body['contract_province'] ?? '');
$contractCity = trim($body['contract_city'] ?? '');
$projectProvince = trim($body['project_province'] ?? '');
$projectCity = trim($body['project_city'] ?? '');

if (empty($contractProvince)) {
    jsonError('Contract Province is required', 422);
}
if (empty($contractCity)) {
    jsonError('Contract City is required', 422);
}
if (empty($projectProvince)) {
    jsonError('Project Province is required', 422);
}
if (empty($projectCity)) {
    jsonError('Project City is required', 422);
}

// SECURITY: Validate location field formats
if (!preg_match('/^[a-zA-Z0-9\s\-,\.]+$/', $contractProvince)) {
    jsonError('Invalid contract province format', 422);
}
if (!preg_match('/^[a-zA-Z0-9\s\-,\.]+$/', $contractCity)) {
    jsonError('Invalid contract city format', 422);
}
if (!preg_match('/^[a-zA-Z0-9\s\-,\.]+$/', $projectProvince)) {
    jsonError('Invalid project province format', 422);
}
if (!preg_match('/^[a-zA-Z0-9\s\-,\.]+$/', $projectCity)) {
    jsonError('Invalid project city format', 422);
}
```

---

## Required Fields Summary

### **Non-Priority Form**

#### **Step 1: Contract Details**
- ✅ Published Date
- ✅ Source
- ✅ Contractor Name
- ✅ Contact Number
- ✅ Country
- ✅ Region
- ✅ **Province** ⭐ (NOW ENFORCED)
- ✅ **City** ⭐ (NOW ENFORCED)

#### **Step 2: Project Details**
- ✅ Project Name
- ✅ Country
- ✅ Region
- ✅ **Province** ⭐ (NOW ENFORCED)
- ✅ **City** ⭐ (NOW ENFORCED)
- ✅ Project Value
- ✅ Project Status

#### **Step 3: Material Details**
- All fields are optional

---

### **Priority Form**

#### **Step 1: Contractor Details**
- ✅ Published Date
- ✅ Source
- ✅ Contract ID
- ✅ Contractor Name
- ✅ Contact Number
- ✅ Country
- ✅ Region
- ✅ **Province** ⭐ (NOW ENFORCED)
- ✅ **City** ⭐ (NOW ENFORCED)

#### **Step 2: Project Details**
- ✅ Project Name
- ✅ Country
- ✅ Region
- ✅ **Province** ⭐ (NOW ENFORCED)
- ✅ **City** ⭐ (NOW ENFORCED)
- ✅ Project Value
- ✅ Completion Rate

#### **Step 3: Material Details**
- All fields are optional

#### **Step 4: File Upload**
- All fields are optional

---

## Testing Instructions

### **Test 1: Try to skip Province/City fields**
1. Go to Encode → Non-Priority Projects
2. Fill in all required fields in Step 1 **EXCEPT** Province and City
3. Try to click "Next Step"
4. **Expected Result:** 
   - Red border appears on empty Province and City fields
   - Toast error shows: "Please fill in: Province, City"
   - Cannot proceed to Step 2

### **Test 2: Try to submit with whitespace only**
1. Fill Province field with just spaces: "   "
2. Try to proceed
3. **Expected Result:** 
   - Field is treated as empty
   - Validation fails with error message

### **Test 3: Server-side validation**
1. Open browser console
2. Try to submit a project via API with missing Province:
```javascript
fetch('/DATAS/api/v1/projects', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    credentials: 'include',
    body: JSON.stringify({
        contractor_name: 'Test',
        project_name: 'Test',
        project_value: 1000,
        status: 'Prospect',
        source: 'Test',
        contract_region: 'NCR',
        // Missing contract_province and contract_city
        project_region: 'NCR',
        project_province: 'Metro Manila',
        project_city: 'Manila'
    })
})
```
3. **Expected Result:**
   - API returns 422 error
   - Error message: "Contract Province is required"

### **Test 4: Visual feedback works**
1. Leave Province field empty
2. Click "Next Step"
3. **Expected Result:** Province field has red border
4. Start typing in Province field
5. **Expected Result:** Red border disappears immediately

---

## Files Modified

1. ✅ `static/js/encode-non-priority.js` - Enhanced validation + visual feedback
2. ✅ `static/js/encode-priority.js` - Enhanced validation + visual feedback
3. ✅ `api/projects/index.php` - Added server-side validation

---

## Deployment Notes

**No database changes required** - this is purely a validation fix.

### Deployment Steps:
1. Upload the 3 modified files to production server
2. Clear browser cache or use Ctrl+F5 to force reload JS files
3. Test the encode forms immediately after deployment

### Rollback Plan (if needed):
Keep backups of these 3 files before deploying. If issues occur, simply restore the old versions.

---

## Additional Improvements Made

### **Better Error Messages**
- Before: "Please fill all required fields" (generic)
- After: "Please fill in: Province, City" (specific field names)

### **Visual Feedback**
- Empty required fields now highlighted in red
- Error styling clears automatically when user starts typing
- Improves user experience and reduces confusion

### **Security**
- Added regex validation on server-side to prevent injection attacks
- Only allows alphanumeric characters, spaces, hyphens, commas, and periods in location fields

### **Consistency**
- Both Priority and Non-Priority forms now have identical validation behavior
- Server-side validation matches client-side requirements

---

## Prevention for Future

To prevent similar issues in the future:

1. **Always add server-side validation** for any required field
2. **Test with whitespace-only values** (not just empty strings)
3. **Remove `novalidate` attribute** from forms unless there's a specific reason
4. **Provide clear visual feedback** for validation errors
5. **Show specific field names** in error messages

---

## Status: ✅ FIXED

All required fields (including Province and City) are now properly validated on:
- ✅ Client-side (JavaScript with visual feedback)
- ✅ Server-side (PHP API with security checks)
- ✅ Both Non-Priority and Priority encode forms
