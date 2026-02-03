# Request MVA Workflow - View Integration Instructions

## Quick Start

To complete the integration, add the workflow partials to the request view page.

### Location
`/views/requests/view.php`

### Integration Point
Add the partials in the sidebar (right column, `col-lg-4`) after the "Project Information" card.

### Code to Add

Find this section in `/views/requests/view.php`:
```php
<div class="col-lg-4">
    <!-- Project Information -->
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-building me-2"></i>Project Information
            </h6>
        </div>
        <div class="card-body">
            <!-- Project info content -->
        </div>
    </div>

    <!-- ADD THE FOLLOWING TWO LINES HERE -->
    <?php include APP_ROOT . '/views/requests/_partials/_workflow_actions.php'; ?>
    <?php include APP_ROOT . '/views/requests/_partials/_workflow_timeline.php'; ?>

    <!-- Procurement & Delivery Tracking card continues below -->
```

### Complete Integration Example

```php
<div class="col-lg-4">
    <!-- Project Information -->
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-building me-2"></i>Project Information
            </h6>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-5">Project:</dt>
                <dd class="col-sm-7">
                    <div class="fw-medium"><?= htmlspecialchars($request['project_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($request['project_code']) ?></small>
                </dd>
            </dl>

            <div class="mt-3">
                <a href="?route=projects/view&id=<?= $request['project_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-eye me-1"></i>View Project Details
                </a>
            </div>
        </div>
    </div>

    <!-- MVA Workflow Actions Card -->
    <?php include APP_ROOT . '/views/requests/_partials/_workflow_actions.php'; ?>

    <!-- MVA Workflow Timeline Card -->
    <?php include APP_ROOT . '/views/requests/_partials/_workflow_timeline.php'; ?>

    <!-- Procurement & Delivery Tracking -->
    <?php if (isset($procurementOrders) && !empty($procurementOrders)): ?>
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-cart me-2"></i>Procurement & Delivery Status
            </h6>
        </div>
        <!-- ... rest of procurement card ... -->
    </div>
    <?php endif; ?>

    <!-- Quick Actions card continues... -->
</div>
```

## What These Partials Display

### 1. Workflow Actions Card (`_workflow_actions.php`)
Shows context-aware action buttons based on:
- Current request status
- User's role
- User's workflow permissions

**Displays:**
- **Verify button** - If user can verify (Site Inv Clerk/PM)
- **Authorize button** - If user can authorize (PM/FD)
- **Approve button** - If user can approve (FD/AD)
- **Decline button** - Always visible to approvers
- **Resubmit button** - For declined requests (original maker only)
- **Submit button** - For draft requests (maker only)
- **Status info** - Shows who's pending if user has no actions

### 2. Workflow Timeline Card (`_workflow_timeline.php`)
Displays visual progress through workflow:
- **Completed steps** - Green checkmarks with user name and timestamp
- **Pending steps** - Gray circles showing future steps
- **Progress bar** - Shows X of Y steps completed
- **Workflow type** - Identifies Full MVA, Standard MVA, or Expedited

## Required Variables

These variables are already set by the updated `RequestController::view()` method:

```php
// From controller
$request             // Full request data with workflow details
$workflowChain       // Array of workflow steps
$nextApprover        // Next approver info (role, action, status)
$canVerify          // Boolean - can current user verify?
$canAuthorize       // Boolean - can current user authorize?
$canApprove         // Boolean - can current user approve?
$currentUser        // Current user data
$auth               // Auth instance
```

No additional setup needed - just include the partials!

## Visual Result

After integration, the sidebar will show:

```
┌─────────────────────────────┐
│  Project Information        │
│  ✓ Project: ABC Building   │
│  [View Project Details]     │
└─────────────────────────────┘

┌─────────────────────────────┐
│  Approval Actions           │
│                             │
│  [Verify Request]           │
│  ─ or ─                     │
│  [Decline Request]          │
└─────────────────────────────┘

┌─────────────────────────────┐
│  Approval Workflow Progress │
│                             │
│  ✓ Submitted                │
│    By: John Doe             │
│    On: Jan 7, 2025 10:00AM  │
│                             │
│  ○ Verified                 │
│    Role: Site Inv Clerk     │
│                             │
│  ○ Authorized               │
│    Role: Project Manager    │
│                             │
│  ○ Approved                 │
│    Role: Finance Director   │
│                             │
│  [Progress Bar: 25%]        │
│  1 of 4 steps completed     │
└─────────────────────────────┘

┌─────────────────────────────┐
│  Procurement Status         │
│  (existing card)            │
└─────────────────────────────┘
```

## Testing After Integration

1. **Create test request as Warehouseman**
   - Should see "Submit" button in actions card
   - Timeline should show full MVA workflow (6 steps)

2. **Submit request**
   - Site Inventory Clerk should see "Verify" button
   - Timeline should show step 1 completed

3. **Verify as Site Inventory Clerk**
   - Project Manager should see "Authorize" button
   - Timeline should show steps 1-2 completed

4. **Continue workflow**
   - Each approver sees appropriate button
   - Timeline updates after each action

5. **Test decline**
   - Click "Decline Request"
   - Modal appears requiring reason
   - After decline, maker sees "Resubmit" button

## Troubleshooting

### Buttons not showing
- Verify `$canVerify`, `$canAuthorize`, `$canApprove` are set in controller
- Check user's role in database
- Check `config/roles.php` permissions

### Timeline not rendering
- Verify `$workflowChain` is populated
- Check browser console for JavaScript errors
- Verify Bootstrap CSS is loaded

### Modal not working
- Verify Bootstrap 5 JavaScript is loaded
- Check for jQuery conflicts
- Verify modal target ID matches button

## Complete!

That's it! The MVA workflow is now fully integrated into the request view page.

**Next Steps:**
1. Test all three workflow scenarios (Warehouseman, Site Inv Clerk, Project Manager as initiators)
2. Test decline and resubmit flow
3. Verify permissions for all roles
4. Check activity logs are being created
5. Consider adding email notifications (future enhancement)
