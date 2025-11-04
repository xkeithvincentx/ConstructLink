<?php
/**
 * Form Actions Partial
 * Cancel and Submit buttons with loading state
 *
 * Required Variables:
 * @var string $mode - Form mode: 'legacy' or 'standard'
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

// Mode-specific configurations
$submitButtonText = $mode === 'legacy' ? 'Add Legacy Item' : 'Create Asset';
$submitButtonIcon = $mode === 'legacy' ? 'bi-check' : 'bi-plus-lg';
?>

<!-- Submit Buttons -->
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Cancel
            </button>
            <button type="submit" class="btn btn-success" id="submit-btn">
                <span class="btn-content">
                    <i class="<?= $submitButtonIcon ?> me-1" aria-hidden="true"></i><?= htmlspecialchars($submitButtonText) ?>
                </span>
                <span class="spinner-border spinner-border-sm d-none" role="status">
                    <span class="visually-hidden">Submitting...</span>
                </span>
            </button>
        </div>
    </div>
</div>
