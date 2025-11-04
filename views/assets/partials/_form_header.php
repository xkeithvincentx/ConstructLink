<?php
/**
 * Form Header Partial
 * Navigation, alerts, and permission checks for asset forms
 *
 * Required Variables:
 * @var string $mode - Form mode: 'legacy' or 'standard'
 * @var array $user - Current user information
 * @var array $roleConfig - Role configuration array
 * @var array|null $messages - Success messages (optional)
 * @var array|null $errors - Error messages (optional)
 * @var array $branding - Branding configuration
 *
 * @package ConstructLink
 * @subpackage Views\Assets\Partials
 * @version 1.0.0
 * @since Phase 2 Refactoring
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Determine route based on mode
$routeKey = $mode === 'legacy' ? 'assets/legacy-create' : 'assets/create';
$permissionDeniedMessage = $mode === 'legacy'
    ? 'You do not have permission to create legacy inventory items.'
    : 'You do not have permission to create a new inventory item.';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=assets" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        <span class="d-none d-sm-inline">Back to Inventory</span>
        <span class="d-sm-none">Back</span>
    </a>
</div>

<?php if ($mode === 'legacy'): ?>
<!-- Info Alert (Legacy Mode Only) -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Legacy Item Entry
    </h6>
    <p class="mb-0">
        Use this form to quickly add existing inventory items that are already on the project site.
        Items will be pending verification by the Site Inventory Clerk before final authorization.
    </p>
</div>
<?php endif; ?>

<!-- Success Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Permission Check -->
<?php
$hasPermission = in_array($user['role_name'], $roleConfig[$routeKey] ?? []);
if (!$hasPermission):
?>
    <div class="alert alert-danger mt-4" role="alert">
        <?= htmlspecialchars($permissionDeniedMessage) ?>
    </div>
<?php
    return; // Stop rendering if no permission
endif;
?>
<!-- User has permission - form content will be included here by the main view file -->
