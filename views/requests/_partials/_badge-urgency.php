<?php
/**
 * ConstructLink™ Urgency Level Badge Partial
 *
 * Renders an urgency badge with appropriate styling and accessibility features.
 * Eliminates duplicated urgency badge logic across multiple views.
 *
 * @param string $urgency - The urgency level (Normal, Urgent, Critical)
 * @param bool $includeIcon - Whether to include an icon (default: true)
 * @param string $size - Badge size: 'normal' or 'small' (default: 'normal')
 *
 * Usage:
 *   include APP_ROOT . '/views/requests/_partials/_badge-urgency.php';
 */

// Validate required parameter
if (!isset($urgency)) {
    throw new InvalidArgumentException('Urgency parameter is required for urgency badge partial');
}

// Default optional parameters
$includeIcon = $includeIcon ?? true;
$size = $size ?? 'normal';

// Urgency badge configuration (single source of truth)
$urgencyConfig = [
    'Normal' => [
        'class' => 'bg-secondary',
        'icon' => 'bi-dash-circle',
        'description' => 'Normal priority'
    ],
    'Urgent' => [
        'class' => 'bg-warning text-dark',
        'icon' => 'bi-exclamation-circle',
        'description' => 'Urgent - requires prompt attention'
    ],
    'Critical' => [
        'class' => 'bg-danger',
        'icon' => 'bi-exclamation-triangle',
        'description' => 'Critical - immediate action required'
    ]
];

// Get configuration for this urgency level (default to secondary if not found)
$config = $urgencyConfig[$urgency] ?? [
    'class' => 'bg-secondary',
    'icon' => 'bi-question-circle',
    'description' => 'Unknown urgency level'
];

$badgeClass = $config['class'];
$icon = $config['icon'];
$description = $config['description'];

// Apply size modifier
$sizeClass = $size === 'small' ? ' badge-sm' : '';
?>
<span class="badge <?= $badgeClass . $sizeClass ?>"
      role="status"
      aria-label="Urgency level: <?= htmlspecialchars($urgency) ?>"
      title="<?= htmlspecialchars($description) ?>">
    <?php if ($includeIcon): ?>
        <i class="<?= $icon ?> me-1" aria-hidden="true"></i>
    <?php endif; ?>
    <?= htmlspecialchars($urgency) ?>
</span>
