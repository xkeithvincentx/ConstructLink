<?php
/**
 * ConstructLink™ Delivery Status Badge Partial
 *
 * Renders a delivery status badge with appropriate styling and accessibility features.
 * Eliminates duplicated delivery status badge logic across multiple views.
 *
 * @param string $deliveryStatus - The delivery status
 * @param bool $includeIcon - Whether to include an icon (default: true)
 * @param string $size - Badge size: 'normal' or 'small' (default: 'small')
 *
 * Usage:
 *   include APP_ROOT . '/views/requests/_partials/_badge-delivery.php';
 */

// Validate required parameter
if (!isset($deliveryStatus)) {
    throw new InvalidArgumentException('Delivery status parameter is required for delivery badge partial');
}

// Default optional parameters
$includeIcon = $includeIcon ?? true;
$size = $size ?? 'small';

// Delivery status badge configuration (single source of truth)
$deliveryConfig = [
    'Completed' => [
        'class' => 'bg-success',
        'icon' => 'bi-check-circle-fill',
        'description' => 'Delivery completed'
    ],
    'In Progress' => [
        'class' => 'bg-primary',
        'icon' => 'bi-truck',
        'description' => 'Delivery in progress'
    ],
    'Scheduled' => [
        'class' => 'bg-info',
        'icon' => 'bi-calendar-check',
        'description' => 'Delivery scheduled'
    ],
    'Ready for Delivery' => [
        'class' => 'bg-warning text-dark',
        'icon' => 'bi-box-seam',
        'description' => 'Ready for delivery'
    ],
    'Processing' => [
        'class' => 'bg-secondary',
        'icon' => 'bi-hourglass-split',
        'description' => 'Processing delivery'
    ],
    'Awaiting Procurement' => [
        'class' => 'bg-light text-dark',
        'icon' => 'bi-cart',
        'description' => 'Awaiting procurement'
    ],
    'Not Started' => [
        'class' => 'bg-light text-muted',
        'icon' => 'bi-dash-circle',
        'description' => 'Delivery not started'
    ],
    'Pending' => [
        'class' => 'bg-secondary',
        'icon' => 'bi-clock',
        'description' => 'Pending'
    ],
    'In Transit' => [
        'class' => 'bg-warning text-dark',
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
        'description' => 'Received and confirmed'
    ],
    'Partial' => [
        'class' => 'bg-warning text-dark',
        'icon' => 'bi-box-seam',
        'description' => 'Partially delivered'
    ]
];

// Get configuration for this delivery status (default to secondary if not found)
$config = $deliveryConfig[$deliveryStatus] ?? [
    'class' => 'bg-secondary',
    'icon' => 'bi-question-circle',
    'description' => 'Unknown delivery status'
];

$badgeClass = $config['class'];
$icon = $config['icon'];
$description = $config['description'];

// Apply size modifier
$sizeClass = $size === 'small' ? ' badge-sm' : '';
?>
<span class="badge <?= $badgeClass . $sizeClass ?>"
      role="status"
      aria-label="Delivery status: <?= htmlspecialchars($deliveryStatus) ?>"
      title="<?= htmlspecialchars($description) ?>">
    <?php if ($includeIcon): ?>
        <i class="<?= $icon ?> me-1" aria-hidden="true"></i>
    <?php endif; ?>
    <?= htmlspecialchars($deliveryStatus) ?>
</span>
