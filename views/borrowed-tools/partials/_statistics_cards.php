<?php
/**
 * Statistics Cards Partial
 * Displays role-appropriate borrowed tools statistics dashboard cards
 *
 * Warehouseman/Operational roles: Day-to-day actionable metrics
 * Management/Oversight roles: Overall system health metrics
 *
 * REFACTORED: Reduced duplication through helper function, improved accessibility
 */

$userRole = $user['role_name'] ?? 'Guest';
$isOperationalRole = in_array($userRole, ['Warehouseman', 'Site Inventory Clerk']);
$isManagementRole = in_array($userRole, ['Project Manager', 'Asset Director', 'Finance Director']);

/**
 * Render a statistics card
 */
function renderStatCard(array $config): string {
    $defaults = [
        'title' => '',
        'value' => 0,
        'icon' => 'question-circle',
        'iconColor' => 'secondary',
        'borderColor' => 'neutral',
        'subtitle' => '',
        'subtitleIcon' => 'info-circle',
        'ariaLabel' => ''
    ];

    $card = array_merge($defaults, $config);

    $ariaLabel = $card['ariaLabel'] ?: "{$card['title']}: {$card['value']}";

    return sprintf(
        '<div class="col-lg-3 col-md-6">
            <div class="card h-100" style="border-left: 4px solid var(--%s-color);" role="region" aria-label="%s">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-light p-2 me-3">
                            <i class="bi bi-%s text-%s fs-5" aria-hidden="true"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 small">%s</h6>
                            <h3 class="mb-0">%s</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 small">
                        <i class="bi bi-%s me-1" aria-hidden="true"></i>%s
                    </p>
                </div>
            </div>
        </div>',
        htmlspecialchars($card['borderColor']),
        htmlspecialchars($ariaLabel),
        htmlspecialchars($card['icon']),
        htmlspecialchars($card['iconColor']),
        htmlspecialchars($card['title']),
        number_format($card['value']),
        htmlspecialchars($card['subtitleIcon']),
        htmlspecialchars($card['subtitle'])
    );
}
?>

<!-- Borrowed Tools Detailed Statistics Cards -->
<div class="row g-3">

            <?php if ($isOperationalRole): ?>
                <!-- OPERATIONAL ROLE CARDS (Warehouseman, Site Inventory Clerk) -->
                <!-- Focus: Today's actionable items and current status -->

                <?= renderStatCard([
                    'title' => 'Borrowed Today',
                    'value' => $borrowedToolStats['borrowed_today'] ?? 0,
                    'icon' => 'calendar-check',
                    'iconColor' => 'success',
                    'borderColor' => 'success',
                    'subtitle' => date('M d, Y'),
                    'subtitleIcon' => 'clock',
                    'ariaLabel' => 'Equipment borrowed today: ' . ($borrowedToolStats['borrowed_today'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Returned Today',
                    'value' => $borrowedToolStats['returned_today'] ?? 0,
                    'icon' => 'arrow-return-left',
                    'iconColor' => 'primary',
                    'borderColor' => 'primary',
                    'subtitle' => 'Items checked in',
                    'subtitleIcon' => 'box-arrow-in-down',
                    'ariaLabel' => 'Equipment returned today: ' . ($borrowedToolStats['returned_today'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Currently Out',
                    'value' => $borrowedToolStats['borrowed'] ?? 0,
                    'icon' => 'tools',
                    'iconColor' => 'info',
                    'borderColor' => 'info',
                    'subtitle' => 'Active borrowings',
                    'subtitleIcon' => 'person-badge',
                    'ariaLabel' => 'Equipment currently borrowed: ' . ($borrowedToolStats['borrowed'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Overdue',
                    'value' => $borrowedToolStats['overdue'] ?? 0,
                    'icon' => 'exclamation-triangle',
                    'iconColor' => 'danger',
                    'borderColor' => 'danger',
                    'subtitle' => 'Follow up needed',
                    'subtitleIcon' => 'telephone',
                    'ariaLabel' => 'Overdue equipment requiring follow-up: ' . ($borrowedToolStats['overdue'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Due Today',
                    'value' => $borrowedToolStats['due_today'] ?? 0,
                    'icon' => 'calendar-event',
                    'iconColor' => 'warning',
                    'borderColor' => 'warning',
                    'subtitle' => 'Expected returns',
                    'subtitleIcon' => 'bell',
                    'ariaLabel' => 'Equipment due for return today: ' . ($borrowedToolStats['due_today'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Due This Week',
                    'value' => $borrowedToolStats['due_this_week'] ?? 0,
                    'icon' => 'calendar-week',
                    'iconColor' => 'secondary',
                    'borderColor' => 'neutral',
                    'subtitle' => 'Next 7 days',
                    'subtitleIcon' => 'calendar-range',
                    'ariaLabel' => 'Equipment due this week: ' . ($borrowedToolStats['due_this_week'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Available Now',
                    'value' => $borrowedToolStats['available_equipment'] ?? 0,
                    'icon' => 'box-seam',
                    'iconColor' => 'success',
                    'borderColor' => 'success',
                    'subtitle' => 'Ready to borrow',
                    'subtitleIcon' => 'check-circle',
                    'ariaLabel' => 'Equipment available to borrow: ' . ($borrowedToolStats['available_equipment'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'This Week',
                    'value' => $borrowedToolStats['activity_this_week'] ?? 0,
                    'icon' => 'graph-up',
                    'iconColor' => 'secondary',
                    'borderColor' => 'neutral',
                    'subtitle' => 'Total transactions',
                    'subtitleIcon' => 'activity',
                    'ariaLabel' => 'Total transactions this week: ' . ($borrowedToolStats['activity_this_week'] ?? 0)
                ]) ?>

            <?php else: ?>
                <!-- MANAGEMENT/OVERSIGHT ROLE CARDS -->
                <!-- Focus: System health, approval queues, overall metrics -->

                <?= renderStatCard([
                    'title' => 'Pending Verification',
                    'value' => $borrowedToolStats['pending_verification'] ?? 0,
                    'icon' => 'clock',
                    'iconColor' => 'warning',
                    'borderColor' => 'warning',
                    'subtitle' => 'Project Manager review',
                    'subtitleIcon' => 'person',
                    'ariaLabel' => 'Requests pending verification: ' . ($borrowedToolStats['pending_verification'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Pending Approval',
                    'value' => $borrowedToolStats['pending_approval'] ?? 0,
                    'icon' => 'hourglass-split',
                    'iconColor' => 'info',
                    'borderColor' => 'info',
                    'subtitle' => 'Director approval needed',
                    'subtitleIcon' => 'shield-check',
                    'ariaLabel' => 'Requests pending approval: ' . ($borrowedToolStats['pending_approval'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Currently Out',
                    'value' => $borrowedToolStats['borrowed'] ?? 0,
                    'icon' => 'tools',
                    'iconColor' => 'primary',
                    'borderColor' => 'primary',
                    'subtitle' => 'Active borrowings',
                    'subtitleIcon' => 'person-badge',
                    'ariaLabel' => 'Equipment currently borrowed: ' . ($borrowedToolStats['borrowed'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Overdue',
                    'value' => $borrowedToolStats['overdue'] ?? 0,
                    'icon' => 'exclamation-triangle',
                    'iconColor' => 'danger',
                    'borderColor' => 'danger',
                    'subtitle' => 'Requires immediate action',
                    'subtitleIcon' => 'clock',
                    'ariaLabel' => 'Overdue equipment: ' . ($borrowedToolStats['overdue'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'This Month',
                    'value' => $borrowedToolStats['borrowed_this_month'] ?? 0,
                    'icon' => 'calendar3',
                    'iconColor' => 'success',
                    'borderColor' => 'success',
                    'subtitle' => date('F Y'),
                    'subtitleIcon' => 'graph-up',
                    'ariaLabel' => 'Equipment borrowed this month: ' . ($borrowedToolStats['borrowed_this_month'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Returned This Month',
                    'value' => $borrowedToolStats['returned_this_month'] ?? 0,
                    'icon' => 'arrow-return-left',
                    'iconColor' => 'secondary',
                    'borderColor' => 'neutral',
                    'subtitle' => 'Completed returns',
                    'subtitleIcon' => 'check-circle',
                    'ariaLabel' => 'Equipment returned this month: ' . ($borrowedToolStats['returned_this_month'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Available Equipment',
                    'value' => $borrowedToolStats['available_equipment'] ?? 0,
                    'icon' => 'box-seam',
                    'iconColor' => 'success',
                    'borderColor' => 'success',
                    'subtitle' => 'Non-consumable items',
                    'subtitleIcon' => 'check-circle',
                    'ariaLabel' => 'Available equipment: ' . ($borrowedToolStats['available_equipment'] ?? 0)
                ]) ?>

                <?= renderStatCard([
                    'title' => 'Total Borrowings',
                    'value' => $borrowedToolStats['total_borrowings'] ?? 0,
                    'icon' => 'list-ul',
                    'iconColor' => 'secondary',
                    'borderColor' => 'neutral',
                    'subtitle' => 'All-time records',
                    'subtitleIcon' => 'archive',
                    'ariaLabel' => 'Total borrowings all-time: ' . ($borrowedToolStats['total_borrowings'] ?? 0)
                ]) ?>

            <?php endif; ?>

</div><!-- End row -->
