<?php
/**
 * Stat Cards Component - Neutral Design System V2.0
 *
 * Displays a grid of statistical metrics with icons in a responsive card layout.
 * Follows "Calm Data, Loud Exceptions" philosophy - neutral by default, color only for critical items.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param array $stats Required array of stat items, each with keys:
 *   - 'icon' (string): Bootstrap icon class (e.g., 'bi-box')
 *   - 'count' (int|float): Numeric value to display
 *   - 'label' (string): Descriptive label for the stat
 *   - 'critical' (bool): Optional - if true, displays in red for urgent attention (default: false)
 * @param string $title Optional card title (default: 'Quick Stats')
 * @param string $titleIcon Optional icon for card header (default: null - removed for cleaner design)
 * @param int $columns Optional number of columns (2, 3, or 4; default: 3)
 * @param bool $useCard Optional - wrap stats in a card (default: true)
 *
 * @example Basic usage (neutral design)
 * ```php
 * $stats = [
 *     ['icon' => 'bi-box', 'count' => 150, 'label' => 'Total Assets'],
 *     ['icon' => 'bi-check-circle', 'count' => 42, 'label' => 'Available'],
 *     ['icon' => 'bi-exclamation-triangle', 'count' => 3, 'label' => 'Overdue', 'critical' => true]
 * ];
 * $columns = 3;
 * include APP_ROOT . '/views/dashboard/components/stat_cards.php';
 * ```
 *
 * @example Without card wrapper (for custom layouts)
 * ```php
 * $stats = [...];
 * $useCard = false;
 * include APP_ROOT . '/views/dashboard/components/stat_cards.php';
 * ```
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.1 - Neutral Design
 * @since 2025-10-28
 */

// Validate required parameter
if (!isset($stats) || !is_array($stats) || empty($stats)) {
    error_log('[Dashboard Component] stat_cards.php: $stats parameter is required and must be a non-empty array');
    return;
}

// Set defaults
$title = $title ?? 'Quick Stats';
$titleIcon = $titleIcon ?? null; // Null by default - cleaner design
$columns = $columns ?? 3; // Changed default from 2 to 3 for better layout
$useCard = $useCard ?? true;

// Validate columns
if (!in_array($columns, [2, 3, 4])) {
    $columns = 3;
}

// Calculate column class based on number of columns
$columnClasses = [
    2 => 'col-6',
    3 => 'col-12 col-sm-6 col-md-4',
    4 => 'col-12 col-sm-6 col-md-3'
];
$columnClass = $columnClasses[$columns];

// Generate unique ID for ARIA labeling
$uniqueId = 'stat-cards-' . md5($title . time());
?>

<?php if ($useCard): ?>
<div class="card card-neutral">
    <?php if ($title): ?>
    <div class="card-header">
        <h5 class="mb-0" id="<?= $uniqueId ?>-title">
            <?php if ($titleIcon): ?>
                <i class="<?= htmlspecialchars($titleIcon) ?> me-2" aria-hidden="true"></i>
            <?php endif; ?>
            <?= htmlspecialchars($title) ?>
        </h5>
    </div>
    <?php endif; ?>
    <div class="card-body">
<?php else: ?>
    <div class="stat-cards-wrapper">
<?php endif; ?>

        <div class="row g-3" role="group" aria-labelledby="<?= $uniqueId ?>-title">
            <?php foreach ($stats as $index => $stat): ?>
                <?php
                // Extract and validate stat data
                $icon = $stat['icon'] ?? 'bi-question-circle';
                $count = $stat['count'] ?? 0;
                $label = $stat['label'] ?? 'Metric';
                $critical = $stat['critical'] ?? false; // NEW: critical flag for neutral design

                // Format count (handle large numbers)
                if (is_numeric($count)) {
                    $formattedCount = number_format($count);
                } else {
                    $formattedCount = htmlspecialchars($count);
                }

                // Generate unique ID for this stat
                $statId = $uniqueId . '-stat-' . $index;

                // Determine CSS class based on critical flag
                $cardClass = $critical ? 'card-stat critical' : 'card-stat';
                ?>

                <div class="<?= $columnClass ?>">
                    <div class="<?= $cardClass ?>"
                         role="figure"
                         aria-labelledby="<?= $statId ?>-label"
                         aria-describedby="<?= $statId ?>-value">
                        <div class="stat-icon">
                            <i class="<?= htmlspecialchars($icon) ?>" aria-hidden="true"></i>
                        </div>
                        <h2 class="stat-number"
                            id="<?= $statId ?>-value"
                            aria-live="polite"
                            aria-atomic="true">
                            <?= $formattedCount ?>
                        </h2>
                        <p class="stat-label" id="<?= $statId ?>-label">
                            <?= htmlspecialchars($label) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

<?php if ($useCard): ?>
    </div>
</div>
<?php else: ?>
    </div>
<?php endif; ?>
