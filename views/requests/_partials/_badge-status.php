<?php
/**
 * ConstructLink™ Request Status Badge Partial
 *
 * Renders a status badge with appropriate styling based on request status.
 * Eliminates duplicated status badge logic across multiple views.
 *
 * @param string $status - The request status
 * @param bool $includeIcon - Whether to include an icon (default: false)
 * @param string $size - Badge size: 'normal' or 'small' (default: 'normal')
 *
 * Usage:
 *   include APP_ROOT . '/views/requests/_partials/_badge-status.php';
 */

// Validate required parameter
if (!isset($status)) {
    throw new InvalidArgumentException('Status parameter is required for status badge partial');
}

// Default optional parameters
$includeIcon = $includeIcon ?? false;
$size = $size ?? 'normal';

// Status badge configuration (single source of truth)
$statusConfig = [
    'Draft' => [
        'class' => 'bg-secondary',
        'icon' => 'bi-pencil-square'
    ],
    'Submitted' => [
        'class' => 'bg-warning text-dark',
        'icon' => 'bi-send'
    ],
    'Reviewed' => [
        'class' => 'bg-info',
        'icon' => 'bi-eye'
    ],
    'Forwarded' => [
        'class' => 'bg-primary',
        'icon' => 'bi-arrow-right-circle'
    ],
    'Approved' => [
        'class' => 'bg-success',
        'icon' => 'bi-check-circle'
    ],
    'Declined' => [
        'class' => 'bg-danger',
        'icon' => 'bi-x-circle'
    ],
    'Procured' => [
        'class' => 'bg-dark',
        'icon' => 'bi-cart-check'
    ],
    'Fulfilled' => [
        'class' => 'bg-success',
        'icon' => 'bi-check-all'
    ]
];

// Get configuration for this status (default to secondary if not found)
$config = $statusConfig[$status] ?? ['class' => 'bg-secondary', 'icon' => 'bi-question-circle'];
$badgeClass = $config['class'];
$icon = $config['icon'];

// Apply size modifier
$sizeClass = $size === 'small' ? ' badge-sm' : '';
?>
<span class="badge <?= $badgeClass . $sizeClass ?>"
      role="status"
      aria-label="Request status: <?= htmlspecialchars($status) ?>">
    <?php if ($includeIcon): ?>
        <i class="<?= $icon ?> me-1" aria-hidden="true"></i>
    <?php endif; ?>
    <?= htmlspecialchars($status) ?>
</span>
