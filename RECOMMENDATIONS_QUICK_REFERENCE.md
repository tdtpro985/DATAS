# 🚀 Bulk Assign Recommendations - Quick Reference Card

## 📊 Match Score Guide

| Score | Meaning | Visual |
|-------|---------|--------|
| **100%** | Perfect match - Province exact | ⭐⭐⭐⭐⭐ Gold |
| **95%** | Very strong match - Province partial | ⭐⭐⭐⭐ Gold |
| **85%** | Strong match - Region exact | ⭐⭐⭐ Gold |
| **75%** | Good match - City exact | ⭐⭐ Gray |
| **0%** | No match - Different area | ⭐ Gray |

---

## 🎯 Quick Actions

### To Assign Projects:
```
1. Click "Bulk Assign Projects" button
2. See recommended SRs (gold highlight)
3. Click on best SR (usually highest %)
4. Check projects to assign
5. Click "Assign to [SR]"
```

### To Choose Best SR:
```
Look for:
✅ Highest match % (100% best)
✅ "(Available)" label = no current projects
✅ 🟢 Online status = can start now
✅ Clear match reason
```

---

## 💡 Smart Selection Tips

### Priority Order:
1. **100% match + Available** = PERFECT! ⭐⭐⭐⭐⭐
2. **100% match + Low workload (1-4)** = Excellent ⭐⭐⭐⭐
3. **85-95% match + Available** = Very Good ⭐⭐⭐⭐
4. **85-95% match + Normal workload** = Good ⭐⭐⭐
5. **<75% match** = Consider why needed ⭐⭐

---

## 🗺️ Location Matching Examples

| Project Location | Best SR Branch | Match % |
|------------------|----------------|---------|
| Manila, NCR | "Manila Branch" | 100% |
| Quezon City, NCR | "NCR Office" | 100% |
| Laguna, IV-A | "Laguna Office" | 100% |
| Cebu City, VII | "Cebu Branch" | 100% |
| Davao City, XI | "Davao Branch" | 100% |
| Anywhere, NCR | "Makati Branch" | 90% |
| Anywhere, Visayas | "Central Visayas" | 85% |

---

## 🎨 Visual Indicators

### Colors:
- **🟡 Gold** = Recommended (match ≥85%)
- **🔵 Blue** = Available (match <85%)
- **🟢 Green** = Online (active now)

### Badges:
- **⭐ 100% Match** = Best possible
- **💡 Match reason** = Why recommended
- **📊 X projects** = Current workload
- **🟢 Online** = Active within 5 min

---

## 📋 Common Scenarios

### Scenario 1: All Projects in One Province
**Action:** Use first recommended SR (100% match)

### Scenario 2: Projects in Multiple Provinces
**Action:** Assign in batches by province

### Scenario 3: No Recommendations Shown
**Action:** Check project location data, or assign manually

### Scenario 4: Multiple 100% Matches
**Action:** Choose one with "(Available)" or lowest workload

---

## ⚡ Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Open modal | Click button |
| Search SRs | Type in search box |
| Select SR | Click card |
| Select all projects | Click "Select All" |
| Assign | Click assign button |
| Cancel | ESC or click X |

---

## 🔧 Troubleshooting

| Problem | Solution |
|---------|----------|
| No recommendations | Check if SR branches match project locations |
| Wrong recommendations | Verify project province/region data |
| Can't click SR | Check if modal fully loaded |
| Slow loading | Check network connection |

---

## 📱 Access Path

```
Dashboard → Project Management → Unassigned Tab → Bulk Assign Projects
```

---

## 🎓 Training Checklist

- [ ] Understand match percentages
- [ ] Know how to read match reasons
- [ ] Check SR workload before assigning
- [ ] Prefer available SRs when possible
- [ ] Consider online status
- [ ] Batch assign by province for efficiency

---

## 📞 Support

**Issues?** Contact system admin or check full documentation:
- `BULK_ASSIGN_RECOMMENDATIONS_IMPLEMENTATION.md` (Technical)
- `RECOMMENDATIONS_VISUAL_GUIDE.md` (Visual examples)
- `PAANO_GUMANA_ANG_RECOMMENDATIONS.md` (Tagalog guide)

---

**Print this card and keep it near your workstation! 📄**

---

**Last Updated:** June 5, 2026
**Version:** 1.0
