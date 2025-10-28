<?php
/**
 * Progress Bar Component
 *
 * Displays a labeled progress bar with automatic percentage calculation and color theming.
 * Follows WCAG 2.1 AA accessibility standards with full ARIA attributes.
 *
 * @param string $label Progress bar label
 * @param int|float $current Current value
 * @param int|float $total Total/max value
 * @param array $config Optional configuration:
 *   - 'color' (string): Progress bar color (success, primary, warning, danger, info, secondary)
 *   - 'thresholds' (array): Auto-color thresholds [90 => 'danger', 75 => 'warning', 0 => 'success']
 *   - 'showPercentage' (bool): Show percentage inside bar (default: false)
 *   - 'showCount' (bool): Show count above bar (default: true)
 *   - 'height' (string): Height class (progress-sm, progress-md, progress-lg; default: progress-md)
 *   - 'striped' (bool): Use striped progress bar (default: false)
 *   - 'animated' (bool): Animate striped progress bar (default: false)
 *
 * @example
 * // Simple usage
 * $label = 'Available Assets';
 * $current = 45;
 * $total = 100;
 * include APP_ROOT . '/views/dashboard/components/progress_bar.php';
 *
 * // Advanced usage with auto-coloring
 * $label = 'Budget Utilization';
 * $current = 85000;
 * $total = 100000;
 * $config = [
 *     'thresholds' => [90 => 'danger', 75 => 'warning', 0 => 'success'],
 *     'showPercentage' => true,
 *     'height' => 'progress-lg'
 * ];
 * include APP_ROOT . '/views/dashboard/components/progress_bar.php';
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.0
 * @since 2025-10-28
 */

// Validate required parameters
if (!isset($label) || !isset($current) || !isset($total)) {
    error_log('[Dashboard Component] progress_bar.php: $label, $current, and $total parameters are required');
    return;
}

// Set defaults
$config = $config ?? [];
$showCount = $config['showCount'] ?? true;
$showPercentage = $config['showPercentage'] ?? false;
$height = $config['height'] ?? 'progress-md';
$striped = $config['striped'] ?? false;
$animated = $config['animated'] ?? false;

// Ensure total is never zero
$total = max($total, 1);
$current = max($current, 0);

// Calculate percentage
$percentage = round(($current / $total) * 100, 1);
$percentage = min($percentage, 100); // Cap at 100%

// Determine color (manual override or threshold-based)
if (isset($config['color'])) {
    $color = $config['color'];
} elseif (isset($config['thresholds'])) {
    // Auto-color based on thresholds
    $thresholds = $config['thresholds'];
    krsort($thresholds); // Sort descending
    $color = 'primary';
    foreach ($thresholds as $threshold => $thresholdColor) {
        if ($percentage >= $threshold) {
            $color = $thresholdColor;
            break;
        }
    }
} else {
    // Default color
    $color = 'success';
}

// Validate color
$validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
if (!in_array($color, $validColors)) {
    $color = 'success';
}

// Validate height
$validHeights = ['progress-sm', 'progress-md', 'progress-lg'];
if (!in_array($height, $validHeights)) {
    $height = 'progress-md';
}

// Build progress bar classes
$progressBarClasses = ['progress-bar', 'bg-' . $color];
if ($striped) {
    $progressBarClasses[] = 'progress-bar-striped';
}
if ($animated && $striped) {
    $progressBarClasses[] = 'progress-bar-animated';
}

// Determine text color for count based on progress bar color
$countColorMap = [
    'success' => 'text-success',
    'danger' => 'text-danger',
    'warning' => 'text-warning',
    'info' => 'text-info',
    'primary' => 'text-primary',
    'secondary' => 'text-secondary'
];
$countColor = $countColorMap[$color] ?? 'text-dark';

// Format numbers
$formattedCurrent = is_numeric($current) ? number_format($current) : htmlspecialchars($current);
$formattedTotal = is_numeric($total) ? number_format($total) : htmlspecialchars($total);

// Generate unique ID for ARIA labeling
$uniqueId = 'progress-' . md5($label . $current . $total);
?>

<div class="mb-2">
    <?php if ($showCount): ?>
    <div class="d-flex justify-content-between mb-1">
        <span id="<?= $uniqueId ?>-label"><?= htmlspecialchars($label) ?></span>
        <span class="<?= $countColor ?>">
            <?= $formattedCurrent ?>
            <?php if ($total > 1): ?>
            <span class="text-muted">/ <?= $formattedTotal ?></span>
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <div class="progress <?= htmlspecialchars($height) ?>"
         role="progressbar"
         aria-labelledby="<?= $uniqueId ?>-label"
         aria-valuenow="<?= $percentage ?>"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-valuetext="<?= $percentage ?>% - <?= htmlspecialchars($label) ?>: <?= $formattedCurrent ?> of <?= $formattedTotal ?>">

        <div class="<?= implode(' ', $progressBarClasses) ?>"
             style="width: <?= $percentage ?>%">
            <?php if ($showPercentage): ?>
            <span class="px-2"><?= $percentage ?>%</span>
            <?php else: ?>
            <span class="visually-hidden"><?= $percentage ?>% complete</span>
            <?php endif; ?>
        </div>
    </div>
</div>
