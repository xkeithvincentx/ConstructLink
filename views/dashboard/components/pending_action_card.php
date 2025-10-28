<?php
/**
 * Pending Action Card Component
 *
 * Displays a single pending action item with count badge and conditional action button.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param array $item Required array with keys:
 *   - 'label' (string): Action item label/title
 *   - 'count' (int): Number of pending items
 *   - 'route' (string): URL route for action button
 *   - 'icon' (string): Bootstrap icon class (e.g., 'bi-box-seam')
 *   - 'color' (string): Bootstrap color context (primary, warning, danger, info, success, secondary)
 * @param string $actionText Optional button text (default: 'Review Now')
 * @param string $columnClass Optional column class (default: 'col-12 col-md-6')
 *
 * @example
 * $item = [
 *     'label' => 'High Value Requests',
 *     'count' => 5,
 *     'route' => 'requests?status=Reviewed&high_value=1',
 *     'icon' => 'bi-file-earmark-text',
 *     'color' => 'primary'
 * ];
 * include APP_ROOT . '/views/dashboard/components/pending_action_card.php';
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.0
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
$color = $item['color'] ?? 'primary';
$actionText = $actionText ?? 'Review Now';
$columnClass = $columnClass ?? 'col-12 col-md-6';

// Validate color context
$validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
if (!in_array($color, $validColors)) {
    $color = 'primary';
}

// Generate unique ID for ARIA labeling
$uniqueId = 'pending-action-' . md5($label . $route);
?>

<div class="<?= htmlspecialchars($columnClass) ?> mb-3">
    <div class="pending-action-item pending-action-item-<?= htmlspecialchars($color) ?>"
         role="group"
         aria-labelledby="<?= $uniqueId ?>-label">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <i class="<?= htmlspecialchars($icon) ?> text-<?= htmlspecialchars($color) ?> me-2 fs-5" aria-hidden="true"></i>
                <span class="fw-semibold" id="<?= $uniqueId ?>-label">
                    <?= htmlspecialchars($label) ?>
                </span>
            </div>
            <span class="badge bg-<?= htmlspecialchars($color) ?> rounded-pill"
                  role="status"
                  aria-label="<?= $count ?> pending <?= htmlspecialchars(strtolower($label)) ?>">
                <?= number_format($count) ?>
            </span>
        </div>

        <?php if ($count > 0): ?>
        <a href="?route=<?= urlencode($route) ?>"
           class="btn btn-sm btn-<?= htmlspecialchars($color) ?> mt-1"
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
