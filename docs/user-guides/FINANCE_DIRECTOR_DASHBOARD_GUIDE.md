# Finance Director Dashboard - Quick Reference Guide

**Version:** 3.0 - Executive Redesign
**Last Updated:** 2025-10-30
**Intended Audience:** Finance Directors, Financial Controllers

---

## What's New in Version 3.0

✨ **Granular Equipment Visibility**
- See specific equipment types (Drills, Saws, Grinders) not just categories
- View availability by project site
- Instant transfer vs. purchase decision support

✨ **Bird's Eye View Design**
- All critical information visible without clicking
- Urgency-based color coding (Critical/Warning/OK)
- One-click actions from dashboard

✨ **Mobile-Optimized**
- Full functionality on tablets and phones
- Touch-friendly interface
- Responsive tables and cards

---

## Dashboard Overview

### Top Stats Bar
Quick metrics displayed in 4 cards:

| Stat | What It Shows | When to Act |
|------|---------------|-------------|
| **Total Assets** | All equipment in system | Informational only |
| **Available** | Ready to deploy | Check if requests pending |
| **In Use** | Currently deployed | Monitor utilization rates |
| **Pending Approvals** | Awaiting your approval | ⚠️ Action required when >0 |

---

## Understanding Inventory Cards

### Equipment Categories

Each category card shows:
```
┌─────────────────────────────────────┐
│ Power Tools         [Equipment] [⚠️ LOW STOCK] │
├─────────────────────────────────────┤
│ Available  In Use  Maint.   Total   │
│    5        10      2       17      │
│                                     │
│ [Show Equipment Types (3)]          │
└─────────────────────────────────────┘
```

#### Urgency Indicators

| Badge | Color | Meaning | Action |
|-------|-------|---------|--------|
| **OUT** | 🔴 Red | Zero available, all in use | Purchase immediately |
| **LOW** | 🟡 Yellow | ≤2 available | Consider purchasing |
| **OK** | 🟢 Green | Adequate stock | Monitor only |

### Equipment Type Breakdown

Click **"Show Equipment Types"** to expand and see:
```
┌─────────────────────────────────────┐
│ 🔧 Drills            [⚠️ LOW STOCK]   │
│ Available: 2  In Use: 8  Total: 10  │
│ Availability: 20% ▓░░░░░░░░░        │
│                                     │
│ ▶ Project Site Distribution (4)     │
│   • Site A: 0/3 drills             │
│   • Site B: 1/2 drills             │
│   • Site C: 1/3 drills             │
│   • Site D: 0/2 drills             │
│                                     │
│ [View All] [Initiate Procurement]   │
└─────────────────────────────────────┘
```

---

## Key Decision: Transfer vs. Purchase

### When to TRANSFER
✅ Another project has surplus inventory
✅ Short-term need (project ending soon)
✅ Cost-effective solution

**Example:**
```
Drills - Low Stock Alert
  Site A: 0/5 drills (all in use)
  Site B: 3/5 drills (2 available) ← Transfer from here

Decision: Transfer 2 drills from Site B → Site A
Action: Click [Transfer] button
```

### When to PURCHASE
✅ All projects at capacity (no surplus)
✅ Long-term need (multiple projects requesting)
✅ Equipment type consistently low

**Example:**
```
Saws - Out of Stock
  Site A: 0/3 saws (all in use)
  Site B: 0/2 saws (all in use)
  Site C: 0/5 saws (all in use)

Decision: No transfer opportunities, purchase new saws
Action: Click [Purchase] button
```

---

## Pending Approvals Workflow

### High-Value Requests (>₱50,000)

1. **Review Count**
   - Badge shows number pending (e.g., "High Value Requests (5)")

2. **Click to Filter**
   - Opens request list filtered to high-value items only

3. **Approve or Reject**
   - Review financial justification
   - Check budget availability
   - Approve/reject with notes

### Transfer Approvals

**Why You See This:**
Cross-project asset transfers require Finance Director approval to ensure:
- Proper asset tracking
- Budget allocation
- Project cost accounting

**Quick Approval:**
- Click "Transfer Approvals (3)"
- Review source/destination projects
- Verify asset value
- Approve if financially sound

---

## Budget Utilization Monitoring

### Reading the Progress Bars

| Color | Percentage | Status |
|-------|------------|--------|
| 🟢 Green | 0-70% | Healthy |
| 🟡 Yellow | 71-90% | Monitor closely |
| 🔴 Red | 91-100% | At risk, investigate |

**Example:**
```
Project Budget Utilization
┌─────────────────────────────────────┐
│ High-Rise Construction              │
│ Budget: ₱5,000,000                  │
│ Utilized: ₱4,750,000                │
│ ▓▓▓▓▓▓▓▓▓░ 95% (⚠️ Over budget risk)│
└─────────────────────────────────────┘
```

**Action:** Review procurement requests for this project

---

## Mobile Usage Tips

### On Tablet/Phone

✅ **Swipe Tables Horizontally**
- Inventory table scrolls left/right on mobile
- Critical columns always visible

✅ **Tap to Expand**
- Category cards collapse on mobile to save space
- Tap header to expand details

✅ **Export to Excel**
- Use "Export" button above inventory table
- Opens in Excel/Google Sheets on device

---

## Common Tasks

### 1. Find Specific Equipment Across All Projects

**Steps:**
1. Scroll to "Inventory by Equipment Type" section
2. Use browser search (Ctrl+F / Cmd+F)
3. Type equipment name (e.g., "Drill")
4. View all results highlighted

**Faster Method:**
- Click category containing equipment
- Expand equipment type list
- See project distribution immediately

### 2. Identify Urgent Procurement Needs

**Steps:**
1. Look for 🔴 red borders on category cards
2. Expand those categories
3. Note equipment types with "OUT" badge
4. Click [Purchase] to initiate procurement

**Shortcut:**
- Red-bordered cards appear first (sorted by urgency)

### 3. Approve Multiple High-Value Requests

**Steps:**
1. Click "Pending Financial Approvals" card
2. Click "High Value Requests (X)"
3. Review list in order of date submitted
4. Approve/reject with bulk actions (if enabled)

### 4. Generate Inventory Report for Meeting

**Steps:**
1. Scroll to Inventory Table (bottom of dashboard)
2. Click "Excel" button
3. Opens spreadsheet with all data
4. Filter/format as needed for presentation

**What's Included:**
- All projects
- All equipment types
- Availability status
- Urgency indicators

---

## Understanding Financial Summary

### Key Metrics

**Total Asset Value**
- Sum of all equipment acquisition costs
- Includes depreciation if configured
- **Use Case:** Portfolio valuation

**Average Asset Value**
- Total value ÷ number of assets
- **Use Case:** Procurement planning (high-value vs. low-value mix)

**High Value Assets**
- Count of items >₱50,000
- **Use Case:** Insurance planning, audit focus

### Quick Actions

**Financial Reports**
- Detailed reports on asset value, depreciation, ROI
- Exportable to PDF/Excel

**View High Value Assets**
- Filtered list of all equipment >₱50,000
- Shows location, condition, assigned project

---

## Troubleshooting

### "No inventory data available"

**Cause:** Assets not linked to equipment types
**Solution:** Contact System Admin to:
1. Create equipment types (Power Tools > Drills)
2. Link existing assets to equipment types
3. Refresh dashboard

### Dashboard not updating after approval

**Cause:** Cache or delayed sync
**Solution:**
1. Click refresh button (top-right)
2. Or reload page (F5 / Cmd+R)
3. Auto-refresh occurs every 5 minutes

### Equipment type not expanding on mobile

**Cause:** JavaScript not loaded
**Solution:**
1. Check internet connection
2. Clear browser cache
3. Contact IT support if persistent

---

## Best Practices

✅ **Daily Review**
- Check "Pending Approvals" count first thing
- Review red/yellow urgency badges
- Approve time-sensitive requests

✅ **Weekly Analysis**
- Export inventory table to Excel
- Identify trends (frequently low equipment types)
- Plan bulk procurement to reduce unit costs

✅ **Monthly Reporting**
- Use Financial Summary metrics
- Compare budget utilization across projects
- Adjust procurement policies if needed

✅ **Quarterly Audit**
- Review high-value asset locations
- Verify transfer records
- Ensure accurate asset tracking

---

## Keyboard Shortcuts (Desktop)

| Shortcut | Action |
|----------|--------|
| `Tab` | Navigate between cards |
| `Enter` | Expand/collapse focused card |
| `Ctrl+F` / `Cmd+F` | Search inventory table |
| `F5` / `Cmd+R` | Refresh dashboard |

---

## Getting Help

**Dashboard Issues:**
- Contact: System Admin
- Email: admin@company.com
- Phone: (555) 123-4567

**Financial Questions:**
- Contact: Accounting Department
- Email: accounting@company.com

**Feature Requests:**
- Submit via: Project Management Portal
- Or: Email IT department with "Dashboard Enhancement" subject

---

## Appendix: Example Scenarios

### Scenario 1: Project Manager Requests Drills

**Situation:**
- Site A needs 5 drills urgently
- Currently 0 drills available at Site A

**Dashboard View:**
```
Drills - Warning (2 available)
  Site A: 0/5 (all in use)
  Site B: 1/3 (2 available)
  Site C: 1/2 (1 available)
```

**Decision Process:**
1. Check other sites: Site B has 2 available
2. Decision: Transfer 2 from Site B → Site A
3. Action: Click [Transfer] → Fill form → Approve
4. Outcome: Site A gets 2 drills, need to purchase 3 more

### Scenario 2: Budget Alert on Project

**Situation:**
- High-Rise Construction at 95% budget utilization
- New procurement request for ₱100,000 equipment

**Dashboard View:**
```
Project Budget Utilization
High-Rise Construction: ₱4,750,000 / ₱5,000,000 (95%)
▓▓▓▓▓▓▓▓▓░ [RED]

Pending Approvals:
High Value Procurement (1) - ₱100,000 Excavator
```

**Decision Process:**
1. Check budget: Only ₱250,000 remaining
2. Review necessity: Equipment critical for project timeline
3. Options:
   - Approve + increase project budget
   - Approve + transfer from completed project
   - Reject + request alternative solution
4. Action: Approve with budget increase

---

**End of Quick Reference Guide**

For technical details, see: [Finance Director Dashboard Audit Report](/docs/ui-ux-audit/FINANCE_DIRECTOR_DASHBOARD_AUDIT_2025-10-30.md)
