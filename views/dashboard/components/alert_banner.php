<?php
/**
 * Alert Banner Component - Neutral Design System V2.0
 *
 * Displays contextual alert messages with consistent styling and accessibility.
 * Follows "Calm Data, Loud Exceptions" philosophy with appropriate severity levels.
 * Follows WCAG 2.1 AA accessibility standards with proper ARIA attributes.
 *
 * @param string $type Alert type: 'success', 'info', 'warning', 'danger' (default: 'info')
 * @param string $message Required - The alert message (supports HTML for flexibility)
 * @param string $icon Optional - Custom Bootstrap icon class (auto-selected if not provided)
 * @param bool $dismissible Optional - Show close button (default: false)
 * @param string $containerClass Optional - Additional container classes (default: 'mb-3')
 *
 * @example Basic info alert
 * ```php
 * $type = 'info';
 * $message = 'Your dashboard data has been updated.';
 * include APP_ROOT . '/views/dashboard/components/alert_banner.php';
 * ```
 *
 * @example Warning with custom content
 * ```php
 * $type = 'warning';
 * $message = '<strong>Low Stock Alert:</strong> 5 items are running low on inventory.';
 * $dismissible = true;
 * include APP_ROOT . '/views/dashboard/components/alert_banner.php';
 * ```
 *
 * @example Critical danger alert
 * ```php
 * $type = 'danger';
 * $message = '<strong>Urgent Action Required:</strong> 3 maintenance tasks are overdue.';
 * $icon = 'bi-exclamation-triangle-fill';
 * include APP_ROOT . '/views/dashboard/components/alert_banner.php';
 * ```
 *
 * @example Success confirmation (dismissible)
 * ```php
 * $type = 'success';
 * $message = 'Your settings have been saved successfully.';
 * $dismissible = true;
 * include APP_ROOT . '/views/dashboard/components/alert_banner.php';
 * ```
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.1 - Neutral Design
 * @since 2025-11-02
 */

// Validate required parameter
if (!isset($message) || empty($message)) {
    error_log('[Dashboard Component] alert_banner.php: $message parameter is required');
    return;
}

// Set defaults and sanitize
$type = $type ?? 'info';
$dismissible = $dismissible ?? false;
$containerClass = $containerClass ?? 'mb-3';

// Validate type
$validTypes = ['success', 'info', 'warning', 'danger'];
if (!in_array($type, $validTypes)) {
    error_log('[Dashboard Component] alert_banner.php: Invalid type "' . $type . '", defaulting to "info"');
    $type = 'info';
}

// Auto-select icon based on type if not provided
if (!isset($icon) || empty($icon)) {
    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'info' => 'bi-info-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'danger' => 'bi-exclamation-octagon-fill'
    ];
    $icon = $iconMap[$type];
}

// Determine ARIA role based on type
// Critical alerts (danger, warning) use role="alert" for immediate announcement
// Informational alerts (success, info) use role="status" for polite announcement
$role = in_array($type, ['danger', 'warning']) ? 'alert' : 'status';

// Generate unique ID for ARIA labeling
$uniqueId = 'alert-' . uniqid();
?>

<div class="alert alert-<?= htmlspecialchars($type) ?> <?= $dismissible ? 'alert-dismissible fade show' : '' ?> <?= htmlspecialchars($containerClass) ?>"
     role="<?= htmlspecialchars($role) ?>"
     id="<?= $uniqueId ?>"
     aria-live="<?= $role === 'alert' ? 'assertive' : 'polite' ?>">

    <i class="<?= htmlspecialchars($icon) ?> me-2" aria-hidden="true"></i>
    <?= $message ?> <!-- Allow HTML for flexibility (caller is responsible for sanitization) -->

    <?php if ($dismissible): ?>
        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close alert">
        </button>
    <?php endif; ?>
</div>
