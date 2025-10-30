# Finance Director Inventory Overview Feature

## Overview

The Finance Director Inventory Overview provides comprehensive visibility into inventory across all project sites, enabling efficient purchase vs. transfer decisions for material/tool procurement requests.

## Feature Location

**Dashboard:** Finance Director Dashboard (role-specific)
**Route:** `?route=dashboard` (when logged in as Finance Director)

## Business Context

The Finance Director in ConstructLink is an owner in a small company who also handles material/tool procurement requests. They need clear visibility into:

1. What inventory categories exist across all sites
2. Which sites have available inventory
3. Whether to purchase new items or transfer from another site
4. Which categories need immediate attention (low stock/out of stock)

## Architecture

### Database Layer

**File:** `/models/DashboardModel.php`

**Method:** `getInventoryOverviewByCategory()`

**Query Structure:**
```sql
SELECT
    c.id as category_id,
    c.name as category_name,
    c.is_consumable,
    COUNT(DISTINCT a.id) as total_count,
    SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_count,
    SUM(CASE WHEN a.status = 'available' AND c.is_consumable = 1
        THEN a.available_quantity ELSE 0 END) as available_quantity_consumables,
    SUM(CASE WHEN a.status IN ('in_use', 'borrowed') THEN 1 ELSE 0 END) as in_use_count,
    SUM(CASE WHEN a.status IN ('under_maintenance', 'damaged')
        THEN 1 ELSE 0 END) as maintenance_count,
    SUM(CASE WHEN a.acquisition_cost IS NOT NULL
        THEN a.acquisition_cost ELSE 0 END) as total_value,
    COALESCE(c.low_stock_threshold, 5) as low_stock_threshold
FROM categories c
LEFT JOIN assets a ON c.id = a.category_id
WHERE a.status NOT IN ('retired', 'disposed', 'lost') OR a.id IS NULL
GROUP BY c.id, c.name, c.is_consumable, c.low_stock_threshold
HAVING total_count > 0 OR c.id IN (
    SELECT DISTINCT category_id
    FROM requests
    WHERE status IN ('Pending', 'Submitted', 'Reviewed')
)
ORDER BY
    CASE
        WHEN available_count = 0 THEN 1
        WHEN c.is_consumable = 1 AND available_quantity_consumables <=
            COALESCE(c.low_stock_threshold, 5) THEN 2
        WHEN available_count <= 3 THEN 3
        ELSE 4
    END,
    c.name ASC
```

**Priority Ordering Logic:**
1. Out of stock categories (available_count = 0)
2. Low stock consumables (below threshold)
3. Limited availability equipment (≤3 available)
4. Adequate stock (normal)

**Project Breakdown Query:**
For each category, retrieves project-level distribution:
```sql
SELECT
    p.id as project_id,
    p.name as project_name,
    COUNT(DISTINCT a.id) as asset_count,
    SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_count,
    SUM(CASE WHEN a.status = 'available' AND c.is_consumable = 1
        THEN a.available_quantity ELSE 0 END) as available_quantity
FROM projects p
INNER JOIN assets a ON p.id = a.project_id
INNER JOIN categories c ON a.category_id = c.id
WHERE a.category_id = ?
    AND a.status NOT IN ('retired', 'disposed', 'lost')
    AND p.is_active = 1
GROUP BY p.id, p.name
HAVING asset_count > 0
ORDER BY available_count DESC, p.name ASC
```

### Controller Layer

**File:** `/controllers/DashboardController.php`

**Integration:**
The `getFinanceStats()` method in `DashboardModel` automatically calls `getInventoryOverviewByCategory()` and includes the data in the finance role-specific data structure:

```php
$inventoryOverview = $this->getInventoryOverviewByCategory();

return [
    'finance' => array_merge($assetStats, $pendingStats, [
        'inventory_overview' => $inventoryOverview
    ]),
    'budget_utilization' => $budgetStats
];
```

### View Layer

**File:** `/views/dashboard/role_specific/finance_director.php`

**Component Structure:**

```php
<?php if (!empty($financeData['inventory_overview'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-neutral">
            <!-- Inventory Overview Cards -->
        </div>
    </div>
</div>
<?php endif; ?>
```

## UI Components

### Card Layout

Each category is displayed as a card with:

1. **Header Section**
   - Category name
   - Equipment/Consumable badge
   - Urgency indicator badge (Critical/Warning/Adequate)

2. **Metrics Grid (2x2)**
   - Available count
   - In Use count
   - Under Maintenance count
   - Total Assets count

3. **Availability Progress Bar**
   - Visual percentage indicator
   - Color-coded (red <20%, yellow <50%, green ≥50%)

4. **Project Site Breakdown**
   - Collapsible section
   - Shows distribution across active projects
   - Available/Total count per project

5. **Action Buttons**
   - "View Assets" (always shown)
   - "Initiate Procurement" (shown for urgent categories)

### Urgency Levels

| Level | Condition | Badge Color | Icon | Actions |
|-------|-----------|-------------|------|---------|
| **Critical** | Available = 0 | Red (`badge-critical`) | `bi-exclamation-triangle-fill` | Highlight + Procurement button |
| **Warning** | Consumable quantity ≤ threshold OR Available ≤ 3 | Yellow (`bg-warning`) | `bi-exclamation-circle-fill` | Highlight + Procurement button |
| **Normal** | Adequate stock | Neutral gray | `bi-info-circle` | Standard view |

## Decision Support

### Purchase vs. Transfer Decision Tree

```
┌─────────────────────────────────────┐
│  Procurement Request Received       │
└───────────┬─────────────────────────┘
            │
            ▼
┌─────────────────────────────────────┐
│  Check Inventory Overview Dashboard │
└───────────┬─────────────────────────┘
            │
            ▼
      ┌─────────┴─────────┐
      │  Available = 0?   │
      └─────────┬─────────┘
            YES │     NO
                │         │
                ▼         ▼
        ┌──────────┐  ┌──────────────────┐
        │ Purchase │  │ Check Projects   │
        └──────────┘  └───────┬──────────┘
                              │
                    ┌─────────┴─────────┐
                    │  Multiple Sites?  │
                    └─────────┬─────────┘
                          YES │     NO
                              │         │
                              ▼         ▼
                      ┌──────────┐  ┌──────────┐
                      │ Transfer │  │ Purchase │
                      └──────────┘  └──────────┘
```

### Visual Indicators

- **Red left border:** Critical - immediate procurement needed
- **Yellow left border:** Warning - stock running low
- **Progress bar colors:**
  - Red: <20% availability
  - Yellow: 20-49% availability
  - Green: ≥50% availability

## Accessibility Features

### WCAG 2.1 AA Compliance

1. **Semantic HTML**
   - Proper heading hierarchy
   - ARIA labels and roles
   - Role="status" for urgency badges

2. **Keyboard Navigation**
   - All interactive elements focusable
   - Collapsible sections keyboard-accessible
   - Tab order follows visual flow

3. **Screen Reader Support**
   - Descriptive aria-labels
   - Icon elements marked aria-hidden
   - Context provided for numeric values

4. **Visual Design**
   - Color is not sole indicator (icons + text)
   - Sufficient color contrast
   - Focus indicators visible

### Example ARIA Usage

```html
<div class="card inventory-overview-card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="mb-1 fw-bold" id="category-5-label">
                Power Tools
            </h6>
            <span class="badge badge-critical rounded-pill" role="status">
                <i class="bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                Out of Stock
            </span>
        </div>

        <div class="progress-bar bg-danger"
             role="progressbar"
             style="width: 15%"
             aria-valuenow="15"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-label="15% available">
        </div>
    </div>
</div>
```

## Responsive Design

### Breakpoints

- **Mobile (<768px):** 1 card per row
- **Tablet (768-1199px):** 2 cards per row
- **Desktop (≥1200px):** 3 cards per row

### Layout Classes

```html
<div class="col-12 col-md-6 col-xl-4">
    <!-- Card content -->
</div>
```

## Performance Considerations

### Query Optimization

1. **Indexed Fields:**
   - `assets.category_id`
   - `assets.status`
   - `assets.project_id`
   - `projects.is_active`

2. **Efficient Aggregation:**
   - Uses CASE statements for conditional counts
   - Single query for category overview
   - Separate optimized query for project breakdown

3. **Caching Strategy:**
   - Dashboard data cached at controller level
   - Refresh on dashboard load
   - Can be extended with Redis/Memcached

### Expected Performance

- **Small database (<1000 assets):** <100ms
- **Medium database (1000-10000 assets):** <500ms
- **Large database (>10000 assets):** <2s

## Empty States

### No Inventory Data
```html
<div class="alert alert-info mb-0" role="status">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    No inventory data available. Assets will appear here once they are added to the system.
</div>
```

### Category Without Projects
```html
<div class="border-top pt-3">
    <small class="text-muted">
        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
        No assets currently assigned to projects
    </small>
</div>
```

## CSS Styling

### Custom Classes

**File:** `/assets/css/app.css`

```css
/* Inventory Overview Cards */
.inventory-overview-card {
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
}

.inventory-overview-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.inventory-overview-card.border-danger {
    border-left-width: 4px;
    border-left-color: var(--alert-critical);
}

.inventory-overview-card.border-warning {
    border-left-width: 4px;
    border-left-color: #f59e0b;
}
```

## Integration Points

### Links to Other Modules

1. **View Assets:** `?route=assets&category_id={id}`
2. **Initiate Procurement:** `?route=requests/create&category_id={id}`
3. **Project Details:** Via collapsible project breakdown

### Data Flow

```
┌─────────────────────┐
│  User Loads         │
│  Dashboard          │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ DashboardController │
│ ::index()           │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ DashboardModel      │
│ ::getDashboardStats │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ getFinanceStats()   │
└──────────┬──────────┘
           │
           ▼
┌────────────────────────────┐
│ getInventoryOverviewBy     │
│ Category()                 │
│  - Main query              │
│  - Project breakdown loop  │
│  - Urgency calculation     │
└──────────┬─────────────────┘
           │
           ▼
┌─────────────────────┐
│ Return to View      │
│ finance_director.php│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Render Cards with   │
│ - Metrics           │
│ - Progress bars     │
│ - Project breakdown │
│ - Action buttons    │
└─────────────────────┘
```

## Testing Checklist

### Functional Testing

- [ ] Dashboard loads without errors
- [ ] All categories with assets are displayed
- [ ] Urgency indicators show correctly
- [ ] Project breakdown expands/collapses
- [ ] Action buttons link to correct routes
- [ ] Empty states display properly

### Data Accuracy

- [ ] Asset counts match database
- [ ] Available counts correct per status
- [ ] Percentage calculations accurate
- [ ] Project distribution adds up
- [ ] Low stock thresholds respected

### UI/UX Testing

- [ ] Responsive on mobile (375px)
- [ ] Responsive on tablet (768px)
- [ ] Responsive on desktop (1920px)
- [ ] Hover effects work
- [ ] Collapse animations smooth
- [ ] Cards maintain equal height

### Accessibility Testing

- [ ] Keyboard navigation works
- [ ] Screen reader announces correctly
- [ ] Focus indicators visible
- [ ] Color contrast passes WCAG AA
- [ ] All images have alt text
- [ ] All icons marked aria-hidden

### Performance Testing

- [ ] Page load <3s
- [ ] Query execution <2s
- [ ] No N+1 query issues
- [ ] Efficient database indexing

## Future Enhancements

### Phase 2 Features

1. **Trend Analysis**
   - Historical inventory levels
   - Consumption rate charts
   - Predictive restocking alerts

2. **Advanced Filtering**
   - Filter by urgency level
   - Filter by category type
   - Filter by project

3. **Export Functionality**
   - CSV export of inventory overview
   - PDF report generation
   - Email scheduled reports

4. **Smart Suggestions**
   - AI-powered transfer recommendations
   - Automatic reorder point calculation
   - Seasonal demand forecasting

5. **Transfer Workflow Integration**
   - One-click transfer initiation
   - Transfer approval workflow
   - Cost comparison (transfer vs. purchase)

## Support & Maintenance

### Common Issues

**Issue:** Inventory counts seem incorrect
**Solution:** Check asset status filters and ensure retired/disposed assets are excluded

**Issue:** Project breakdown doesn't show all projects
**Solution:** Only active projects with assets in the category are shown

**Issue:** Urgency levels not updating
**Solution:** Verify category low_stock_threshold values in database

### Database Maintenance

```sql
-- Verify category thresholds
SELECT id, name, is_consumable, low_stock_threshold
FROM categories
WHERE low_stock_threshold IS NULL;

-- Update default thresholds if needed
UPDATE categories
SET low_stock_threshold = 5
WHERE low_stock_threshold IS NULL AND is_consumable = 1;
```

### Monitoring Queries

```sql
-- Categories needing attention
SELECT
    c.name,
    COUNT(a.id) as total,
    SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available
FROM categories c
LEFT JOIN assets a ON c.id = a.category_id
GROUP BY c.id, c.name
HAVING available = 0 OR available <= 3
ORDER BY available ASC;
```

## Changelog

### Version 1.0 (2025-10-30)
- Initial release
- Bird's eye inventory overview
- Project site breakdown
- Urgency indicators
- Decision support actions
- Full accessibility compliance
- Responsive design
- Performance optimized queries

---

**Documentation Version:** 1.0
**Last Updated:** 2025-10-30
**Author:** ConstructLink Development Team
**Status:** Production Ready
