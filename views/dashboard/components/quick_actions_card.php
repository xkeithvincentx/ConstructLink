<?php
/**
 * Quick Actions Card Component
 *
 * Displays a card with a grid of action buttons for common operations.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param string $title Card title
 * @param string $titleIcon Bootstrap icon class for card header
 * @param array $actions Required array of action items, each with keys:
 *   - 'label' (string): Button label text
 *   - 'route' (string): URL route for the action
 *   - 'icon' (string): Bootstrap icon class (e.g., 'bi-plus-circle')
 *   - 'color' (string): Bootstrap button color (primary, secondary, success, danger, warning, info, outline-*)
 *   - 'external' (bool): Optional, set true for external links (adds target="_blank" rel="noopener")
 * @param string $accentColor Optional card accent color (default: none)
 *
 * @example
 * $title = 'Financial Operations';
 * $titleIcon = 'bi-lightning-fill';
 * $actions = [
 *     ['label' => 'Financial Reports', 'route' => 'reports/financial', 'icon' => 'bi-file-earmark-bar-graph', 'color' => 'primary'],
 *     ['label' => 'View High Value Assets', 'route' => 'assets?high_value=1', 'icon' => 'bi-eye', 'color' => 'outline-warning']
 * ];
 * include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.0
 * @since 2025-10-28
 */

// Validate required parameters
if (!isset($title) || !isset($actions) || !is_array($actions) || empty($actions)) {
    error_log('[Dashboard Component] quick_actions_card.php: $title and $actions parameters are required');
    return;
}

// Set defaults
$titleIcon = $titleIcon ?? null;
$accentColor = $accentColor ?? null;

// Validate accent color
$validAccentColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
if ($accentColor && !in_array($accentColor, $validAccentColors)) {
    $accentColor = null;
}

// Generate unique ID for ARIA labeling
$uniqueId = 'quick-actions-' . md5($title);
?>

<div class="card mb-4<?= $accentColor ? ' card-accent-' . htmlspecialchars($accentColor) : '' ?>">
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
                $color = $action['color'] ?? 'primary';
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
