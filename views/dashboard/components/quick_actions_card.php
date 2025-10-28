<?php
/**
 * Quick Actions Card Component - Neutral Design System V2.0
 *
 * Displays a card with a grid of action buttons for common operations.
 * Follows "Calm Data, Loud Exceptions" philosophy - neutral by default, color for emphasis.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param string $title Card title
 * @param string $titleIcon Bootstrap icon class for card header (default: null - cleaner design)
 * @param array $actions Required array of action items, each with keys:
 *   - 'label' (string): Button label text
 *   - 'route' (string): URL route for the action
 *   - 'icon' (string): Bootstrap icon class (e.g., 'bi-plus-circle')
 *   - 'color' (string): Optional button color (default: 'outline-secondary' for neutral design)
 *                       Use 'primary' for main action, 'danger' for critical, 'success' for positive
 *   - 'external' (bool): Optional, set true for external links (adds target="_blank" rel="noopener")
 *
 * @example Basic usage (neutral)
 * ```php
 * $title = 'Warehouse Operations';
 * $actions = [
 *     ['label' => 'Process Deliveries', 'route' => 'procurement-orders/for-receipt', 'icon' => 'bi-box-arrow-in-down'],
 *     ['label' => 'View Inventory', 'route' => 'assets?status=available', 'icon' => 'bi-list-ul']
 * ];
 * include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
 * ```
 *
 * @example With primary action
 * ```php
 * $actions = [
 *     ['label' => 'New Request', 'route' => 'borrowed-tools/create', 'icon' => 'bi-plus-circle', 'color' => 'primary'],
 *     ['label' => 'View All', 'route' => 'borrowed-tools', 'icon' => 'bi-list-ul']
 * ];
 * ```
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.1 - Neutral Design
 * @since 2025-10-28
 */

// Validate required parameters
if (!isset($title) || !isset($actions) || !is_array($actions) || empty($actions)) {
    error_log('[Dashboard Component] quick_actions_card.php: $title and $actions parameters are required');
    return;
}

// Set defaults
$titleIcon = $titleIcon ?? null;

// Generate unique ID for ARIA labeling
$uniqueId = 'quick-actions-' . md5($title);
?>

<div class="card card-neutral mb-4">
    <div class="card-header">
        <h5 class="mb-0" id="<?= $uniqueId ?>-title">
            <?php if ($titleIcon): ?><i class="<?= htmlspecialchars($titleIcon) ?> me-2" aria-hidden="true"></i><?php endif; ?><?= htmlspecialchars($title) ?>
        </h5>
    </div>
    <div class="card-body">
        <nav class="d-grid gap-2" aria-labelledby="<?= $uniqueId ?>-title">
            <?php foreach ($actions as $index => $action): ?>
                <?php
                // Extract and validate action data
                $label = $action['label'] ?? 'Action';
                $route = $action['route'] ?? '#';
                $icon = $action['icon'] ?? 'bi-arrow-right-circle';
                $color = $action['color'] ?? 'outline-secondary'; // Default to neutral
                $external = $action['external'] ?? false;

                // Build href
                $href = $external ? htmlspecialchars($route) : '?route=' . urlencode($route);

                // Build external link attributes
                $externalAttrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
                $externalIcon = $external ? '<i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>' : '';
                ?>

                <a href="<?= $href ?>"
                   class="btn btn-<?= htmlspecialchars($color) ?> btn-sm"
                   aria-label="<?= htmlspecialchars($label) ?><?= $external ? ' (opens in new window)' : '' ?>"<?= $externalAttrs ?>>
                    <i class="<?= htmlspecialchars($icon) ?> me-1" aria-hidden="true"></i>
                    <?= htmlspecialchars($label) ?>
                    <?= $externalIcon ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
