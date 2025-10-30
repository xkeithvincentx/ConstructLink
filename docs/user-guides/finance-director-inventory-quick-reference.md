# Finance Director: Inventory Overview Quick Reference Guide

## Overview

The Inventory Overview provides a bird's eye view of all inventory categories across your project sites to help you make informed purchase vs. transfer decisions.

## Accessing the Feature

1. Log in to ConstructLink as Finance Director
2. Navigate to Dashboard
3. Inventory Overview appears at the top of your dashboard

## Understanding the Cards

### Card Components

Each category card shows:

```
┌─────────────────────────────────────────┐
│ POWER TOOLS          🔴 Out of Stock    │ ← Header
├─────────────────────────────────────────┤
│ Available    In Use    Maintenance Total│ ← Metrics
│     0           8           2        10  │
├─────────────────────────────────────────┤
│ Availability: 0%                         │ ← Progress Bar
│ ████████████████████████████ (red)      │
├─────────────────────────────────────────┤
│ ▶ Project Site Distribution (3)         │ ← Collapsible
│   Site A: 0/4 available                 │   Section
│   Site B: 0/3 available                 │
│   Site C: 0/3 available                 │
├─────────────────────────────────────────┤
│ [View Assets] [Initiate Procurement]    │ ← Actions
└─────────────────────────────────────────┘
```

### Urgency Indicators

| Badge | Meaning | Action Needed |
|-------|---------|---------------|
| 🔴 **Out of Stock** | No available inventory | Purchase immediately |
| 🟡 **Low Stock** | Below threshold | Purchase or transfer soon |
| 🟡 **Limited Availability** | Only 1-3 available | Monitor closely |
| ⚪ **Adequate Stock** | Sufficient inventory | No action needed |

## Making Decisions

### Quick Decision Guide

#### Scenario 1: Out of Stock (Red Badge)
**Question:** Do we need to purchase?
**Answer:** YES - No inventory available

**Steps:**
1. Check urgency badge (red)
2. Verify 0% availability
3. Click "Initiate Procurement"

#### Scenario 2: Low Stock (Yellow Badge)
**Question:** Purchase or transfer?
**Answer:** Check project breakdown

**Steps:**
1. Expand "Project Site Distribution"
2. Look for sites with available stock
3. If multiple sites have stock → Consider transfer
4. If single site or no stock → Purchase

#### Scenario 3: Adequate Stock (Gray Badge)
**Question:** Any action needed?
**Answer:** NO - Monitor only

**Steps:**
1. Note current levels
2. Review periodically
3. No immediate action

### Transfer Feasibility Check

**When to transfer:**
- ✅ Multiple sites have inventory
- ✅ One site has excess (>50% available)
- ✅ Requesting site has urgent need
- ✅ Transfer cost < purchase cost

**When to purchase:**
- ❌ All sites at low stock
- ❌ Transfer would deplete source site
- ❌ Transfer cost ≥ purchase cost
- ❌ Urgent need (transfer takes time)

## Reading the Metrics

### Four-Quadrant Display

**Available (Green):**
- Ready to use immediately
- Can be transferred or borrowed
- Optimal for fulfilling requests

**In Use (Blue):**
- Currently deployed
- Will return to available eventually
- Consider timeline for availability

**Under Maintenance (Yellow):**
- Temporarily unavailable
- Will return after repair
- Factor into planning

**Total Assets:**
- Overall inventory count
- Includes all statuses
- Reference for capacity planning

### Progress Bar Colors

| Color | Range | Interpretation |
|-------|-------|----------------|
| 🔴 Red | 0-19% | Critical - immediate action |
| 🟡 Yellow | 20-49% | Warning - plan procurement |
| 🟢 Green | 50-100% | Healthy - adequate stock |

## Project Site Breakdown

### Expanding the Section

1. Click "▶ Project Site Distribution"
2. List expands showing all sites
3. Click again to collapse

### Reading Site Information

```
Site Alpha: 5/10 available
           ↑  ↑
           │  └─ Total assets at this site
           └──── Available for use/transfer
```

**Key insights:**
- Sites with high available/total ratio are good transfer sources
- Sites with 0 available need priority attention
- Compare across sites to identify transfer opportunities

## Taking Action

### View Assets Button

**What it does:**
- Opens full asset list for this category
- Shows detailed information
- Allows filtering and sorting

**When to use:**
- Need specific asset details
- Planning maintenance schedules
- Auditing inventory

### Initiate Procurement Button

**What it does:**
- Starts procurement request
- Pre-fills category information
- Enters workflow system

**When to use:**
- Urgency badge is red or yellow
- Project breakdown shows insufficient stock
- Immediate purchase needed

**Note:** Button only appears for categories needing attention

## Common Workflows

### Workflow 1: Processing a Procurement Request

```
1. Receive procurement request
   ↓
2. Open Inventory Overview
   ↓
3. Find requested category
   ↓
4. Check urgency badge
   ↓
5a. Red/Yellow badge → Check project breakdown
    ↓
    Multiple sites with stock?
    ├─ YES → Initiate transfer
    └─ NO  → Approve procurement

5b. Gray badge → Suggest transfer from available stock
```

### Workflow 2: Regular Inventory Review

```
1. Open Dashboard weekly
   ↓
2. Scan for red badges
   ↓
3. Address critical categories first
   ↓
4. Review yellow badges
   ↓
5. Plan procurement for next period
   ↓
6. Check gray badges for trends
```

### Workflow 3: Budget Planning

```
1. Review Inventory Overview
   ↓
2. Count red badges (immediate needs)
   ↓
3. Count yellow badges (near-term needs)
   ↓
4. Click "View Assets" for cost estimates
   ↓
5. Prepare procurement budget
   ↓
6. Submit for approval
```

## Tips & Best Practices

### Daily Best Practices

✅ **Check Dashboard Daily:**
- Review urgency badges
- Prioritize red badges
- Plan for yellow badges

✅ **Document Decisions:**
- Note transfer vs. purchase rationale
- Track cost savings from transfers
- Build decision history

✅ **Communicate Proactively:**
- Alert project managers of low stock
- Share transfer opportunities
- Request input on priorities

### Cost Optimization

💰 **Maximize Transfer Opportunities:**
- Check project breakdown first
- Balance inventory across sites
- Reduce unnecessary purchases

💰 **Bulk Purchase Planning:**
- Group multiple red badges
- Negotiate volume discounts
- Coordinate delivery schedules

💰 **Monitor Consumption Patterns:**
- Track categories frequently red
- Adjust stock thresholds
- Plan ahead for high-demand items

### Inventory Health Indicators

**Healthy Inventory:**
- Most badges are gray
- Progress bars mostly green
- Balanced distribution across sites

**Attention Needed:**
- Multiple red badges
- Progress bars mostly red/yellow
- Concentration at single site

**Action Required:**
- 5+ red badges
- Critical categories at 0%
- All sites depleted

## Troubleshooting

### Issue: Numbers Don't Match Expectations

**Possible Causes:**
- Recent transfers not yet reflected
- Assets in "damaged" status
- Retired assets still counted

**Solution:**
- Click "View Assets" for details
- Filter by status
- Contact system admin if discrepancy persists

### Issue: Project Breakdown Empty

**Possible Cause:**
- Assets not assigned to projects
- All assets in warehouse/storage

**Solution:**
- Indicates assets not deployed
- Consider assigning to projects
- May be good transfer candidates

### Issue: Urgency Level Seems Wrong

**Possible Cause:**
- Category threshold not set
- Threshold needs adjustment

**Solution:**
- Contact system admin
- Review threshold settings
- Adjust based on actual needs

## Mobile Access

### Mobile Features

The Inventory Overview is fully responsive:

**Phone (Portrait):**
- 1 card per row
- Full functionality
- Swipe to scroll
- Tap to expand sections

**Tablet (Landscape):**
- 2 cards per row
- Optimized spacing
- Touch-friendly buttons

**Tips for Mobile:**
- Landscape mode shows more cards
- Pinch to zoom if needed
- Use "View Assets" for detailed info

## Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Focus next card | Tab |
| Focus previous | Shift + Tab |
| Expand/collapse | Enter or Space |
| Click action button | Enter |

## Accessibility Features

**For Screen Readers:**
- All cards clearly labeled
- Urgency levels announced
- Numeric values spoken
- Action buttons described

**For Low Vision:**
- High contrast mode supported
- Scalable text (zoom 200%)
- Large touch targets
- Clear visual hierarchy

## Getting Help

### Resources

📚 **Full Documentation:**
`/docs/features/finance-director-inventory-overview.md`

👥 **Support Team:**
Contact your ConstructLink administrator

📧 **Feedback:**
Share suggestions for improvements

### FAQ

**Q: How often does the data update?**
A: Real-time on dashboard load, manual refresh available

**Q: Can I export this data?**
A: Currently view-only, export feature planned for Phase 2

**Q: Why don't I see all categories?**
A: Only categories with assets or pending requests are shown

**Q: Can other roles see this?**
A: No, this is exclusive to Finance Director role

**Q: How do I change urgency thresholds?**
A: Contact system administrator to adjust category thresholds

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-10-30 | Initial release |

---

**Need More Help?**
Contact your ConstructLink administrator or refer to the full feature documentation.

**Quick Start:**
1. Check badges (red = urgent)
2. Expand project breakdown
3. Decide: purchase or transfer
4. Click appropriate action button

**Remember:** The system prioritizes urgent categories at the top for you!
