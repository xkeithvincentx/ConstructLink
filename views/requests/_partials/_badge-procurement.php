<?php
/**
 * ConstructLink™ Procurement Status Badge Partial
 *
 * Renders a procurement status badge with appropriate styling and accessibility features.
 * Eliminates duplicated procurement status badge logic across multiple views.
 *
 * @param string $procurementStatus - The procurement status
 * @param bool $includeIcon - Whether to include an icon (default: false)
 * @param string $size - Badge size: 'normal' or 'small' (default: 'small')
 *
 * Usage:
 *   include APP_ROOT . '/views/requests/_partials/_badge-procurement.php';
 */

// Validate required parameter
if (!isset($procurementStatus)) {
    throw new InvalidArgumentException('Procurement status parameter is required for procurement badge partial');
}

// Default optional parameters
$includeIcon = $includeIcon ?? false;
$size = $size ?? 'small';

// Procurement status badge configuration (single source of truth)
$procurementConfig = [
    'Draft' => [
        'class' => 'bg-secondary',
        'icon' => 'bi-pencil',
        'description' => 'PO draft'
    ],
    'Pending' => [
        'class' => 'bg-warning text-dark',
        'icon' => 'bi-clock',
        'description' => 'Pending approval'
    ],
    'Approved' => [
        'class' => 'bg-success',
        'icon' => 'bi-check-circle',
        'description' => 'PO approved'
    ],
    'Rejected' => [
        'class' => 'bg-danger',
        'icon' => 'bi-x-circle',
        'description' => 'PO rejected'
    ],
    'Scheduled for Delivery' => [
        'class' => 'bg-info',
        'icon' => 'bi-calendar-check',
        'description' => 'Scheduled for delivery'
    ],
    'In Transit' => [
        'class' => 'bg-primary',
        'icon' => 'bi-truck',
        'description' => 'In transit'
    ],
    'Delivered' => [
        'class' => 'bg-success',
        'icon' => 'bi-check-circle',
        'description' => 'Delivered'
    ],
    'Received' => [
        'class' => 'bg-dark',
        'icon' => 'bi-check-all',
        'description' => 'Received'
    ]
];

// Get configuration for this procurement status (default to secondary if not found)
$config = $procurementConfig[$procurementStatus] ?? [
    'class' => 'bg-secondary',
    'icon' => 'bi-question-circle',
    'description' => 'Unknown procurement status'
];

$badgeClass = $config['class'];
$icon = $config['icon'];
$description = $config['description'];

// Apply size modifier
$sizeClass = $size === 'small' ? ' badge-sm' : '';
?>
<span class="badge <?= $badgeClass . $sizeClass ?>"
      role="status"
      aria-label="Procurement status: <?= htmlspecialchars($procurementStatus) ?>"
      title="<?= htmlspecialchars($description) ?>">
    <?php if ($includeIcon): ?>
        <i class="<?= $icon ?> me-1" aria-hidden="true"></i>
    <?php endif; ?>
    <?= htmlspecialchars($procurementStatus) ?>
</span>
