# Paano Gumagana ang Bulk Assign Recommendations

## 📋 Simplified Explanation (Tagalog)

---

## Ano ang Ginawa Natin?

Nilagyan natin ng **"Recommendations"** ang **Bulk Assign Projects** feature. Kapag mag-a-assign ka ng maraming projects sa isang Sales Representative (SR), **automatic na ipapakita** ng system kung sino ang **pinaka-tamang SR** base sa **Branch niya** at **Province ng Project**.

---

## Paano Ito Gumagana?

### Simple Flow:

```
1️⃣ Admin clicks "Bulk Assign Projects" button
        ↓
2️⃣ System kunin ang location ng mga projects
   (Example: Mga projects ay nasa Manila, NCR)
        ↓
3️⃣ System hanapin ang mga SR na may branch sa Manila
        ↓
4️⃣ System ipakita ang "RECOMMENDED" na mga SR
   ✨ May gold highlight
   ⭐ May percentage (100%, 95%, 85%)
   💡 May explanation bakit sila recommended
        ↓
5️⃣ Admin piliin ang SR (usually yung pinaka-mataas na match)
        ↓
6️⃣ Admin piliin ang mga projects
        ↓
7️⃣ Done! ✅ Projects assigned to best SR
```

---

## Ano ang Basehan ng Matching?

### Priority Order (Pinaka-importante to least importante):

| Rank | Matching Criteria | Score | Example |
|------|-------------------|-------|---------|
| 1 | **Province Match** | 100% | Project: Laguna → SR Branch: "Laguna Office" |
| 2 | **Region Match** | 85% | Project: Region IV-A → SR Branch: "CALABARZON Branch" |
| 3 | **City Match** | 75% | Project: Cebu City → SR Branch: "Cebu Branch" |

### Special Cases (May special handling):

#### 1️⃣ **NCR/Manila Projects**
- Project location: Manila, Quezon City, Makati, Caloocan
- System hahanapin ang:
  - "Manila Branch" → 100%
  - "NCR Office" → 100%
  - "Metro Manila Branch" → 95%
  - "Makati Branch" → 90%
  - "Quezon Branch" → 90%

#### 2️⃣ **Cebu Projects**
- Project location: Cebu Province
- System hahanapin ang:
  - "Cebu Branch" → 100%
  - "Central Visayas Office" → 85%

#### 3️⃣ **Davao Projects**
- Project location: Davao Province
- System hahanapin ang:
  - "Davao Branch" → 100%
  - "Mindanao Office" → 80%

---

## Ano ang Makikita sa Screen?

### BEFORE (Dati):
```
Sales Representatives
┌──────────────────┐
│ John Santos      │  ← Lahat pareho lang
│ Manila Branch    │
└──────────────────┘

┌──────────────────┐
│ Maria Cruz       │
│ Davao Branch     │
└──────────────────┘

┌──────────────────┐
│ Pedro Reyes      │
│ Cebu Branch      │
└──────────────────┘
```
❌ **Problem:** Hindi mo alam kung sino ang best para sa projects

---

### AFTER (Ngayon):
```
✨ Recommended Sales Representatives
2 sales reps matched based on branch location

┏━━━━━━━━━━━━━━━━━━━━━━━━━━┓  ← GOLD HIGHLIGHT
┃ ⭐ 100% Match             ┃
┃ John Santos              ┃
┃ 📍 Manila Branch          ┃
┃ 📊 3 projects assigned    ┃
┃ 🟢 Online                 ┃
┃ ┌────────────────────────┐┃
┃ │ 💡 Manila branch -     ││
┃ │    perfect match for   ││
┃ │    NCR project         ││
┃ └────────────────────────┘┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━┛

┏━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃ ⭐ 85% Match              ┃
┃ Roberto Chan             ┃
┃ 📍 NCR Sales Office       ┃
┃ 📊 0 projects (Available) ┃
┃ ┌────────────────────────┐┃
┃ │ 💡 Located in NCR area ││
┃ └────────────────────────┘┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━┛

────── Other Sales Representatives ──────

┌──────────────────┐
│ Maria Cruz       │  ← Walang gold, hindi match
│ Davao Branch     │
│ 8 projects       │
└──────────────────┘
```
✅ **Better:** Agad mong makikita kung sino ang best!

---

## Mga Dagdag na Features

### 1. **Workload Balancing** (Para balanced ang trabaho)

Kapag may dalawang SR na both 100% match, pipiliin ng system ang:
- SR na **walang projects** (Available) → +5 points
- SR na **may konting projects** (1-4) → +2 points

**Example:**
```
John Santos:
- 100% match (Manila Branch)
- 5 projects assigned
- Final score: 100

Roberto Chan:
- 100% match (NCR Office)
- 0 projects assigned
- Final score: 105 (100 + 5 bonus)
- Shows "(Available)" label

Result: Roberto Chan appears FIRST ⭐
```

---

### 2. **Online Status** (Sino ang active ngayon)

```
🟢 Online - Active within last 5 minutes
(No badge) - Offline or inactive
```

Kapag online ang SR, may pulsing green dot indicator.

---

### 3. **Match Reason** (Explanation bakit sila recommended)

Hindi ka na mag-guess! Sabihin ng system kung bakit match:

| Match Reason | Meaning |
|--------------|---------|
| "Branch matches project province" | Exact province match |
| "Manila branch - perfect match for NCR project" | Special NCR case |
| "Cebu branch - perfect match" | Special Cebu case |
| "Located in NCR area" | Branch is within region |
| "Central Visayas branch" | Regional match |

---

## Real Example Scenarios

### Scenario 1: NCR Projects

**Situation:**
- May 10 unassigned projects
- Lahat nasa Manila, Quezon City (NCR)

**What System Does:**
1. Detect na lahat ng projects ay NCR
2. Find all SRs na may "Manila", "NCR", "Metro Manila" sa branch
3. Show them as recommended

**Result:**
```
✨ Recommended (4 SRs)
- John Santos (Manila Branch) - 100%
- Ana Garcia (NCR Office) - 100%
- Pedro Luz (Metro Manila Branch) - 95%
- Sofia Torres (Makati Branch) - 90%

Other Sales Representatives (3 SRs)
- Maria Cruz (Davao Branch) - No match
- ... (other non-NCR branches)
```

---

### Scenario 2: Cebu Projects

**Situation:**
- May 5 projects sa Cebu Province

**What System Shows:**
```
✨ Recommended
┏━━━━━━━━━━━━━━━━━━━━━━━━┓
┃ ⭐ 100% Match           ┃
┃ Roberto Chan           ┃
┃ Cebu Branch            ┃
┃ 0 projects (Available) ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━┛

┏━━━━━━━━━━━━━━━━━━━━━━━━┓
┃ ⭐ 85% Match            ┃
┃ Maria Santos           ┃
┃ Central Visayas Office ┃
┃ 2 projects assigned    ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━┛
```

---

### Scenario 3: Mixed Provinces

**Situation:**
- May 10 projects:
  - 6 projects → Laguna
  - 3 projects → Cavite
  - 1 project → Batangas

**What System Does:**
1. Count: Laguna is most common (6 projects)
2. Recommend SRs with "Laguna" in branch

**Note:** Admin can still manually select Cavite or Batangas SRs if needed.

---

## Paano Gamitin

### Step-by-Step Guide:

#### Step 1: Open Projects Management
```
Pages → Project Management → Unassigned Tab
```

#### Step 2: Click "Bulk Assign Projects"
```
┌─────────────────────────────┐
│ 👥 Bulk Assign Projects     │  ← Click this
└─────────────────────────────┘
```

#### Step 3: View Recommendations
```
Modal opens:
- See loading message
- Wait for recommendations to load
- See highlighted recommended SRs
```

#### Step 4: Select Best SR
```
Click on any SR card:
- Recommended SR (gold) = usually best
- Other SR = if you have specific reason
```

#### Step 5: Select Projects
```
- Checkboxes appear on all projects
- Check projects you want to assign
- Click "Assign to [SR Name]" button
```

#### Step 6: Confirm
```
✅ Success message appears
Projects assigned!
```

---

## Tips for Best Results

### ✅ DO:

1. **Check the match percentage**
   - 100% = Perfect match
   - 85-95% = Very good match
   - <85% = Consider manually

2. **Check workload**
   - Prefer SRs with "(Available)" label
   - Balance assignments across team

3. **Check online status**
   - Online SRs (🟢) can start immediately

4. **Read match reason**
   - Understand why they're recommended
   - Helps you make informed decision

### ❌ DON'T:

1. **Don't ignore recommendations**
   - System based on actual data
   - Geographic matching improves success

2. **Don't overload one SR**
   - Check project count
   - Distribute fairly

3. **Don't assign wrong location**
   - Manila SR for Davao project = inefficient
   - Use recommendations as guide

---

## Benefits ng Recommendations

### For Admins:
✅ **Mas mabilis** - Hindi na kailangan mag-check ng bawat branch manually
✅ **Mas accurate** - System based on data, not guessing
✅ **Fair distribution** - Nakikita ang workload ng each SR
✅ **Better decisions** - May clear explanation kung bakit recommended

### For Sales Reps:
✅ **Local projects** - Projects sa kanilang area
✅ **Better success rate** - Familiar sa location
✅ **Balanced workload** - Hindi overloaded
✅ **Faster response** - Can visit client easily

### For Business:
✅ **Better coverage** - Right person in right place
✅ **Faster deals** - Local SRs can act quickly
✅ **Happy clients** - Face-to-face meetings easier
✅ **Data-driven** - System learns patterns

---

## Common Questions (FAQ)

### Q1: Paano kung walang recommended SRs?
**A:** Ibig sabihin walang SR na match ang branch sa project location. Pwede pa rin mag-assign manually from "Other Sales Representatives" section.

### Q2: Pwede bang i-override ang recommendation?
**A:** Oo! Pwede mo pa rin piliin ang kahit sinong SR. Ang recommendations ay guide lang, hindi mandatory.

### Q3: Paano kung mali ang branch ng SR?
**A:** Update ang branch sa Users Management page. Next time, tama na ang recommendations.

### Q4: Bakit may SRs na hindi naka-gold highlight?
**A:** Walang location match. Hindi nila specialty ang area ng projects, pero pwede pa rin i-assign kung gusto.

### Q5: Paano kung sobrang dami ng projects sa iba't ibang province?
**A:** System will use most common province. Better to bulk assign by province/region batches.

### Q6: May bayad ba ang feature na ito?
**A:** Wala! Free included sa system. 😊

---

## Technical Terms (Simple Explanation)

| Technical Term | Tagalog Meaning |
|----------------|-----------------|
| Match Score | Percentage ng compatibility |
| Recommended | Inirerekomenda ng system |
| Branch | Office location ng SR |
| Province | Lalawigan ng project |
| Region | Rehiyon ng project |
| Workload | Bilang ng assigned projects |
| Online Status | Kung active ba ang SR ngayon |
| Algorithm | Formula ng matching |

---

## Color Guide

### Gold (Dilaw/Ginto) - RECOMMENDED
- Ibig sabihin: BEST MATCH
- Use: For top recommendations

### Blue (Asul) - NORMAL
- Ibig sabihin: Available but not best match
- Use: Other SRs

### Green (Berde) - ONLINE
- Ibig sabihin: Active ngayon
- Use: Online status indicator

### Gray (Abo) - OFFLINE
- Ibig sabihin: Hindi active
- Use: Offline status

---

## Summary (Buod)

**Simple lang:**
1. System automatic na hahanap ng **best SR** base sa **location**
2. May **gold highlight** ang recommended
3. May **percentage** kung gaano ka-match
4. May **explanation** kung bakit
5. May **workload info** para fair
6. May **online status** para alam mo kung available

**Result:**
⚡ Mas mabilis mag-assign
🎯 Mas accurate ang choice
👥 Mas balanced ang workload
📈 Mas successful ang projects

---

**Tandaan:** Ang recommendations ay **tumutulong** sa decision, pero **ikaw pa rin** ang final decision maker! 💪

---

**Created:** June 5, 2026
**Status:** ✅ Fully Implemented and Working
