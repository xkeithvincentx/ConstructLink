# Finance Director Dashboard - Quick Reference Guide

## 🎯 Your Dashboard at a Glance

### What You See

When you log in, your dashboard shows **Inventory by Project Site** first:

```
┌─────────────────────────────────────────┐
│ 📍 JCLDS - BMS Package    [🔴 Critical] │
│ 50 total • 30 available                 │
│ [Show Inventory Details ▼]              │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 📍 East Residences        [✅ Adequate] │
│ 35 total • 28 available                 │
│ [Show Inventory Details ▼]              │
└─────────────────────────────────────────┘
```

**Projects are sorted by urgency:**
1. 🔴 **Red border** = Critical shortage (0 items available for some equipment type)
2. 🟡 **Yellow border** = Low stock (≤2 items available)
3. ⚪ **Gray border** = Adequate stock (all equipment types have >2 items)

---

## 🛠️ Common Workflows

### Scenario 1: Project Manager Requests Equipment

**Request:** "JCLDS project needs 5 drills"

**Your Workflow:**

1. **Find JCLDS project card** (it's likely at the top if critical)
2. **Click "Show Inventory Details"** to expand
3. **Look for "Power Tools" category** → Find "Drills"
4. **Check availability:** "Drills: 0 avail, 5 in use 🔴 OUT OF STOCK"

**Decision Time:**

**Option A: Transfer from another project**
1. Scroll to **East Residences** project card
2. Click "Show Inventory Details"
3. Find "Power Tools" → "Drills: 8 avail, 2 in use"
4. **Decision:** Transfer 5 drills from East Residences to JCLDS
5. Click **"Transfer Assets"** button on East Residences card
6. Fill in transfer form (pre-populated with source project)

**Option B: Purchase new equipment**
1. JCLDS card shows 🔴 **Critical Shortage**
2. Click **"View All Assets"** to see detailed inventory
3. Click **"Initiate Procurement"** (if no transfer possible)
4. Create procurement request for 10 drills (5 for JCLDS + 5 buffer stock)

---

### Scenario 2: Reviewing Budget vs. Inventory

**Goal:** Ensure projects with high budgets have adequate inventory

**Your Workflow:**

1. **Scroll to "Project Budget Utilization"** table
2. **Identify projects with low budget usage** (e.g., "20% utilized")
3. **Go back to "Inventory by Project Site"** section
4. **Expand that project** to see what equipment they have
5. **Decision:** If inventory is adequate but budget low, consider transferring surplus to other projects

---

### Scenario 3: Weekly Inventory Review

**Goal:** Proactively identify and resolve shortages

**Your Workflow:**

1. **Check dashboard on Monday morning**
2. **Look for projects with 🔴 red borders** (critical shortages)
3. **For each critical project:**
   - Expand inventory details
   - Note which equipment types are at 0 available
   - Check other projects for surplus inventory
   - Initiate transfers OR procurement

4. **Look for projects with 🟡 yellow borders** (low stock)
   - Expand inventory details
   - Note which equipment types are at ≤2 available
   - Plan procurement for next week (before it becomes critical)

5. **Generate report:**
   - Click "Financial Reports" button
   - Export inventory snapshot for executive meeting

---

## 🎨 Visual Indicators Guide

### Project Card Borders

| Color | Meaning | Action Required |
|-------|---------|-----------------|
| 🔴 **Red (2px)** | Critical shortage (≥1 equipment type has 0 available) | **Urgent:** Transfer or purchase immediately |
| 🟡 **Yellow (2px)** | Low stock (≥1 equipment type has ≤2 available) | **Soon:** Plan procurement this week |
| ⚪ **Gray (1px)** | Adequate stock (all equipment types have >2 available) | **Monitor:** No immediate action needed |

### Status Badges

| Badge | Meaning |
|-------|---------|
| ![Critical](https://img.shields.io/badge/OUT_OF_STOCK-red?style=flat-square) | 0 items available |
| ![Warning](https://img.shields.io/badge/LOW_STOCK-yellow?style=flat-square) | ≤2 items available |
| ![Success](https://img.shields.io/badge/Adequate_Stock-green?style=flat-square) | >2 items available |

### Equipment Type Display

```
Drills: 5 avail, 2 in use, 1 maint
│       │        │         └─ Under maintenance
│       │        └─────────── Currently borrowed/in use
│       └──────────────────── Available for borrowing
└──────────────────────────── Equipment type name
```

---

## 🔍 Reading the Inventory Details

When you expand a project card, you'll see:

```
┌─────────────────────────────────────────────┐
│ 📍 JCLDS - BMS Package                      │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│                                             │
│ Quick Stats:                                │
│ [30 Available] [15 In Use] [5 Maintenance]  │
│                                             │
│ 🏷️ Power Tools (15 items)                  │
│  • Drills: 3 avail, 2 in use                │
│  • Saws: 4 avail, 3 in use                  │
│  • Grinders: 0 avail, 3 use 🔴 CRITICAL    │
│                                             │
│ 🏷️ Hand Tools (20 items)                   │
│  • Hammers: 10 avail, 2 in use              │
│  • Wrenches: 8 avail, 5 in use              │
│                                             │
│ [View All Assets] [Transfer Assets]         │
└─────────────────────────────────────────────┘
```

**What each section means:**

1. **Quick Stats:** High-level summary (available, in use, maintenance)
2. **Categories:** Equipment grouped by type (Power Tools, Hand Tools, etc.)
3. **Equipment Types:** Individual items within each category
4. **Action Buttons:**
   - **View All Assets:** See detailed list with serial numbers, conditions, etc.
   - **Transfer Assets:** Create transfer from this project to another

---

## ⌨️ Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| **Expand first project** | Tab → Enter |
| **Next project** | Tab (multiple times) |
| **Expand project** | Enter or Spacebar |
| **Collapse project** | Enter or Spacebar (when expanded) |
| **Scroll to action buttons** | Tab (inside expanded project) |
| **View All Assets** | Enter (when focused) |
| **Transfer Assets** | Tab → Enter |

**Pro Tip:** Use Tab to navigate, Enter to activate. All interactive elements are keyboard accessible.

---

## 📱 Mobile Usage

### On Tablet/iPad

- **Portrait:** Projects stack vertically, full width
- **Landscape:** Side-by-side stats for quick comparison

### On Phone

- **All elements stack vertically**
- **Touch targets ≥44px** (easy to tap)
- **Badges stack on new lines** for readability
- **Buttons full-width** (easier to tap on small screens)

**Pro Tip:** Pinch to zoom on specific project cards if needed.

---

## 🚨 Troubleshooting

### "I don't see any projects"

**Possible Causes:**
1. No active projects have inventory assigned
2. All assets are retired/disposed/lost
3. You don't have permission to view financial dashboard

**Solution:**
- Contact Asset Director or System Admin
- Check if assets exist in the system (click "View All Assets" in sidebar)

### "A project shows 0 total assets but I know it has inventory"

**Possible Causes:**
1. All assets for that project are retired/disposed/lost
2. Assets not assigned to this project (check `project_id` in assets table)

**Solution:**
- Click "View All Assets" and filter by project
- Verify asset statuses
- Contact Site Inventory Clerk to update asset assignments

### "Inventory numbers don't match what I expect"

**Possible Causes:**
1. Recent borrowing/return not yet reflected (refresh page)
2. Assets transferred to another project
3. Assets under maintenance

**Solution:**
- Refresh page (Ctrl+R or Cmd+R)
- Expand project → check "In Use" and "Maintenance" counts
- Click "View All Assets" to see detailed breakdown

---

## 💡 Pro Tips

### 1. **Start with Critical Shortages**

Red-bordered projects appear first. Handle these **immediately** to prevent project delays.

### 2. **Compare Side-by-Side**

Open two projects in separate browser tabs to compare inventories side-by-side.

### 3. **Weekly Review Routine**

Every Monday morning:
- Review all 🔴 red-bordered projects (critical)
- Plan transfers for the week
- Create procurement requests for 🟡 yellow-bordered projects (low stock)

### 4. **Use Browser Bookmarks**

Bookmark your Finance Director dashboard for quick access:
```
https://constructlink.com/index.php?route=dashboard
```

### 5. **Export for Meetings**

When discussing budget/inventory in meetings:
1. Take screenshot of "Inventory by Project Site" section
2. Or click "Financial Reports" → Export to PDF
3. Share with executives for transparency

### 6. **Favor Transfers Over Purchases**

**Cost Savings:**
- **Transfer:** ₱0 (just logistics)
- **Purchase:** ₱5,000 - ₱50,000+ per item

**Always check other projects for surplus before purchasing!**

---

## 📞 Support

### Need Help?

**Technical Issues:**
- Contact: System Admin
- Email: admin@constructlink.com
- Phone: Ext. 100

**Business Questions:**
- Contact: Asset Director
- Email: assetdirector@constructlink.com
- Phone: Ext. 200

**Urgent Inventory Issues:**
- Contact: Warehouseman (for current project)
- Or: Site Inventory Clerk (for specific project site)

---

## 🔄 Changelog

### Version 3.0 (2025-10-30)
- ✅ **NEW:** Project-centric inventory view
- ✅ **IMPROVED:** Critical shortages appear first (red borders)
- ✅ **IMPROVED:** Collapsible project cards for better navigation
- ✅ **IMPROVED:** Compact equipment type display (one-line format)
- ✅ **ADDED:** Decision support alert message
- ✅ **ADDED:** Transfer Assets button on project cards

### Version 2.0 (Previous)
- Category-first inventory view
- Equipment type cards with project breakdown

---

**Last Updated:** 2025-10-30
**Document Version:** 1.0
**For:** Finance Director Role
