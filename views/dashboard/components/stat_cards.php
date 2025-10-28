<?php
/**
 * Stat Cards Component
 *
 * Displays a grid of statistical metrics with icons in a responsive card layout.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param array $stats Required array of stat items, each with keys:
 *   - 'icon' (string): Bootstrap icon class (e.g., 'bi-box')
 *   - 'count' (int|float): Numeric value to display
 *   - 'label' (string): Descriptive label for the stat
 *   - 'color' (string): Bootstrap color context (primary, success, warning, danger, info, secondary)
 * @param string $title Optional card title (default: 'Quick Stats')
 * @param string $titleIcon Optional icon for card header (default: 'bi-speedometer2')
 * @param int $columns Optional number of columns (2, 3, or 4; default: 2)
 *
 * @example
 * $stats = [
 *     ['icon' => 'bi-box', 'count' => 150, 'label' => 'Total Assets', 'color' => 'primary'],
 *     ['icon' => 'bi-building', 'count' => 12, 'label' => 'Active Projects', 'color' => 'success'],
 *     ['icon' => 'bi-tools', 'count' => 8, 'label' => 'Maintenance', 'color' => 'warning'],
 *     ['icon' => 'bi-exclamation-triangle', 'count' => 3, 'label' => 'Incidents', 'color' => 'danger']
 * ];
 * include APP_ROOT . '/views/dashboard/components/stat_cards.php';
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.0
 * @since 2025-10-28
 */

// Validate required parameter
if (!isset($stats) || !is_array($stats) || empty($stats)) {
    error_log('[Dashboard Component] stat_cards.php: $stats parameter is required and must be a non-empty array');
    return;
}

// Set defaults
$title = $title ?? 'Quick Stats';
$titleIcon = $titleIcon ?? 'bi-speedometer2';
$columns = $columns ?? 2;

// Validate columns
if (!in_array($columns, [2, 3, 4])) {
    $columns = 2;
}

// Calculate column class based on number of columns
$columnClasses = [
    2 => 'col-6',
    3 => 'col-12 col-sm-6 col-md-4',
    4 => 'col-12 col-sm-6 col-md-3'
];
$columnClass = $columnClasses[$columns];

// Generate unique ID for ARIA labeling
$uniqueId = 'stat-cards-' . md5($title);
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0" id="<?= $uniqueId ?>-title">
            <i class="<?= htmlspecialchars($titleIcon) ?> me-2" aria-hidden="true"></i><?= htmlspecialchars($title) ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row text-center" role="group" aria-labelledby="<?= $uniqueId ?>-title">
            <?php foreach ($stats as $index => $stat): ?>
                <?php
                // Extract and validate stat data
                $icon = $stat['icon'] ?? 'bi-question-circle';
                $count = $stat['count'] ?? 0;
                $label = $stat['label'] ?? 'Metric';
                $color = $stat['color'] ?? 'primary';

                // Validate color context
                $validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark', 'light'];
                if (!in_array($color, $validColors)) {
                    $color = 'primary';
                }

                // Format count (handle large numbers)
                if (is_numeric($count)) {
                    $formattedCount = number_format($count);
                } else {
                    $formattedCount = htmlspecialchars($count);
                }

                // Generate unique ID for this stat
                $statId = $uniqueId . '-stat-' . $index;
                ?>

                <div class="<?= $columnClass ?> mb-3">
                    <div class="stat-card-item"
                         role="figure"
                         aria-labelledby="<?= $statId ?>-label"
                         aria-describedby="<?= $statId ?>-value">
                        <i class="<?= htmlspecialchars($icon) ?> text-<?= htmlspecialchars($color) ?> fs-3 d-block mb-2"
                           aria-hidden="true"></i>
                        <h6 class="mb-0 fw-bold"
                            id="<?= $statId ?>-value"
                            aria-live="polite"
                            aria-atomic="true">
                            <?= $formattedCount ?>
                        </h6>
                        <small class="text-muted d-block mt-1" id="<?= $statId ?>-label">
                            <?= htmlspecialchars($label) ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
