<?php
/**
 * Pending Action Card Component - Neutral Design System V2.0
 *
 * Displays a single pending action item with count badge and conditional action button.
 * Follows "Calm Data, Loud Exceptions" philosophy - neutral by default, red for critical items.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param array $item Required array with keys:
 *   - 'label' (string): Action item label/title
 *   - 'count' (int): Number of pending items
 *   - 'route' (string): URL route for action button
 *   - 'icon' (string): Bootstrap icon class (e.g., 'bi-box-seam')
 *   - 'critical' (bool): Optional - if true, displays in red for urgent attention (default: false)
 * @param string $actionText Optional button text (default: 'Review Now')
 * @param string $columnClass Optional column class (default: 'col-12 col-md-6')
 *
 * @example Basic usage (neutral)
 * ```php
 * $item = [
 *     'label' => 'Scheduled Deliveries',
 *     'count' => 5,
 *     'route' => 'procurement-orders?status=scheduled',
 *     'icon' => 'bi-truck',
 *     'critical' => false
 * ];
 * include APP_ROOT . '/views/dashboard/components/pending_action_card.php';
 * ```
 *
 * @example Critical item (overdue)
 * ```php
 * $item = [
 *     'label' => 'Overdue Returns',
 *     'count' => 3,
 *     'route' => 'borrowed-tools?status=overdue',
 *     'icon' => 'bi-exclamation-triangle',
 *     'critical' => true
 * ];
 * ```
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.1 - Neutral Design
 * @since 2025-10-28
 */

// Validate required parameter
if (!isset($item) || !is_array($item)) {
    error_log('[Dashboard Component] pending_action_card.php: $item parameter is required and must be an array');
    return;
}

// Set defaults and sanitize
$label = $item['label'] ?? 'Action Item';
$count = (int)($item['count'] ?? 0);
$route = $item['route'] ?? '#';
$icon = $item['icon'] ?? 'bi-exclamation-circle';
$critical = $item['critical'] ?? false;
$actionText = $actionText ?? 'Review Now';
$columnClass = $columnClass ?? 'col-12 col-md-6';

// Determine CSS classes based on critical flag
$itemClass = $critical ? 'action-item-critical' : 'action-item';
$badgeClass = $critical ? 'badge-critical' : 'badge-neutral';
$btnClass = $critical ? 'btn-danger' : 'btn-outline-secondary';

// Generate unique ID for ARIA labeling
$uniqueId = 'pending-action-' . md5($label . $route);
?>

<div class="<?= htmlspecialchars($columnClass) ?> mb-4 mb-md-3 d-flex">
    <div class="action-item <?= $itemClass ?> flex-fill"
         role="group"
         aria-labelledby="<?= $uniqueId ?>-label">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <i class="<?= htmlspecialchars($icon) ?> me-2 fs-5" aria-hidden="true"></i>
                <span class="fw-semibold" id="<?= $uniqueId ?>-label">
                    <?= htmlspecialchars($label) ?>
                </span>
            </div>
            <span class="badge <?= $badgeClass ?> rounded-pill"
                  role="status"
                  aria-label="<?= $count ?> pending <?= htmlspecialchars(strtolower($label)) ?>">
                <?= number_format($count) ?>
            </span>
        </div>

        <?php if ($count > 0): ?>
        <a href="?route=<?= urlencode($route) ?>"
           class="btn btn-sm <?= $btnClass ?> mt-1"
           aria-label="<?= htmlspecialchars($actionText) ?> - <?= $count ?> <?= htmlspecialchars(strtolower($label)) ?>">
            <i class="bi bi-eye me-1" aria-hidden="true"></i><?= htmlspecialchars($actionText) ?>
        </a>
        <?php elseif ($count === 0): ?>
        <small class="text-muted d-block mt-1" role="status">
            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>No pending items
        </small>
        <?php endif; ?>
    </div>
</div>
