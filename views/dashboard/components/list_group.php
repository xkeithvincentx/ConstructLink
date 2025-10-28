<?php
/**
 * List Group Component - Neutral Design System V2.0
 *
 * Displays a flush list group with label/value pairs and optional badges.
 * Follows "Calm Data, Loud Exceptions" philosophy - neutral by default, red for critical items.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param array $items Required array of list items, each with keys:
 *   - 'label' (string): Item label/description
 *   - 'value' (int|string): Item value (displayed as badge or text)
 *   - 'critical' (bool): Optional - if true, displays in red for urgent attention (default: false)
 *   - 'success' (bool): Optional - if true, displays in green for positive confirmation (default: false)
 *   - 'icon' (string): Optional icon class to display before label
 *   - 'route' (string): Optional route to make item clickable
 * @param string $title Optional section title displayed above list
 * @param string $emptyMessage Optional message when list is empty
 *
 * @example Basic usage (neutral)
 * ```php
 * $items = [
 *     ['label' => 'Active Vendors', 'value' => 25],
 *     ['label' => 'Preferred Vendors', 'value' => 12, 'icon' => 'bi-star-fill'],
 *     ['label' => 'Total Orders', 'value' => 150]
 * ];
 * $title = 'Vendor Management';
 * include APP_ROOT . '/views/dashboard/components/list_group.php';
 * ```
 *
 * @example Critical item
 * ```php
 * $items = [
 *     ['label' => 'Overdue Returns', 'value' => 3, 'critical' => true, 'icon' => 'bi-exclamation-triangle']
 * ];
 * ```
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.1 - Neutral Design
 * @since 2025-10-28
 */

// Validate required parameter
if (!isset($items) || !is_array($items)) {
    error_log('[Dashboard Component] list_group.php: $items parameter is required and must be an array');
    return;
}

// Set defaults
$title = $title ?? null;
$emptyMessage = $emptyMessage ?? 'No items to display';

// Filter out items with zero/empty values if configured
$displayItems = array_filter($items, function($item) {
    // Always show items unless explicitly configured to hide
    return true;
});

// Generate unique ID for ARIA labeling
$uniqueId = 'list-group-' . md5($title ?? 'list' . count($items));
?>

<?php if ($title): ?>
<h6 class="text-muted mb-2" id="<?= $uniqueId ?>-title"><?= htmlspecialchars($title) ?></h6>
<?php endif; ?>

<?php if (empty($displayItems)): ?>
<p class="text-muted text-center small mb-0" role="status">
    <i class="bi bi-info-circle me-1" aria-hidden="true"></i><?= htmlspecialchars($emptyMessage) ?>
</p>
<?php else: ?>
<div class="list-group list-group-flush" role="list"<?= $title ? ' aria-labelledby="' . $uniqueId . '-title"' : '' ?>>
    <?php foreach ($displayItems as $index => $item): ?>
        <?php
        // Extract and validate item data
        $label = $item['label'] ?? 'Item';
        $value = $item['value'] ?? 0;
        $critical = $item['critical'] ?? false;
        $success = $item['success'] ?? false;
        $icon = $item['icon'] ?? null;
        $route = $item['route'] ?? null;

        // Determine badge class based on critical/success flags
        if ($critical) {
            $badgeClass = 'badge-critical';
            $iconClass = 'text-danger';
        } elseif ($success) {
            $badgeClass = 'badge-success-neutral';
            $iconClass = 'text-success';
        } else {
            $badgeClass = 'badge-neutral';
            $iconClass = 'text-muted';
        }

        // Format value
        if (is_numeric($value)) {
            $formattedValue = number_format($value);
        } else {
            $formattedValue = htmlspecialchars($value);
        }

        // Determine if item should be clickable
        $isClickable = !empty($route);
        $itemTag = $isClickable ? 'a' : 'div';
        $itemHref = $isClickable ? ' href="?route=' . urlencode($route) . '"' : '';
        $itemClass = $isClickable ? ' list-group-item-action' : '';

        // Generate unique ID for this item
        $itemId = $uniqueId . '-item-' . $index;
        ?>

        <<?= $itemTag ?><?= $itemHref ?>
            class="list-group-item px-0 d-flex justify-content-between align-items-center<?= $itemClass ?>"
            role="listitem"
            <?= $isClickable ? 'aria-label="' . htmlspecialchars($label) . ' - ' . $formattedValue . '"' : '' ?>>

            <span>
                <?php if ($icon): ?>
                <i class="<?= htmlspecialchars($icon) ?> me-2 <?= $iconClass ?>" aria-hidden="true"></i>
                <?php endif; ?>
                <?= htmlspecialchars($label) ?>
            </span>

            <span class="badge <?= $badgeClass ?>" role="status">
                <?= $formattedValue ?>
            </span>
        </<?= $itemTag ?>>
    <?php endforeach; ?>
</div>
<?php endif; ?>
