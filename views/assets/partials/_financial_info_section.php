<?php
/**
 * Financial Information Section Partial
 * Acquired date, warranty expiry, acquisition cost, and unit cost
 *
 * Required Variables:
 * @var string $mode - Form mode: 'legacy' or 'standard'
 * @var array $formData - Form data for pre-filling (optional)
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
$acquiredLabel = $mode === 'legacy' ? 'Estimated Acquired Date' : 'Acquired Date';
$acquiredHelpText = $mode === 'legacy'
    ? 'Approximate date when asset was acquired (can be estimated)'
    : 'Date when asset was purchased or acquired';
$costLabel = $mode === 'legacy' ? 'Acquisition Cost (Estimated)' : 'Acquisition Cost';
$costPlaceholder = $mode === 'legacy' ? 'Estimated cost' : 'Total acquisition cost';
$costHelpText = $mode === 'legacy'
    ? 'Estimated cost when acquired (optional)'
    : 'Total cost of acquisition';
?>

<!-- Financial Information -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2 mb-3">
            <i class="bi bi-currency-dollar me-1" aria-hidden="true"></i>Financial Information
        </h6>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="acquired_date" class="form-label">
                <?= htmlspecialchars($acquiredLabel) ?> <span class="text-danger">*</span>
            </label>
            <input type="date" class="form-control" id="acquired_date" name="acquired_date"
                   value="<?= htmlspecialchars($formData['acquired_date'] ?? date('Y-m-d')) ?>"
                   max="<?= date('Y-m-d') ?>" required>
            <div class="form-text"><?= htmlspecialchars($acquiredHelpText) ?></div>
            <div class="invalid-feedback">
                Please provide an acquired date.
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
            <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry"
                   value="<?= htmlspecialchars($formData['warranty_expiry'] ?? '') ?>">
            <div class="form-text">Manufacturer warranty expiration date</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="acquisition_cost" class="form-label"><?= htmlspecialchars($costLabel) ?></label>
            <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" id="acquisition_cost" name="acquisition_cost"
                       step="0.01" min="0" value="<?= htmlspecialchars($formData['acquisition_cost'] ?? '') ?>"
                       placeholder="<?= htmlspecialchars($costPlaceholder) ?>">
            </div>
            <div class="form-text"><?= htmlspecialchars($costHelpText) ?></div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="unit_cost" class="form-label">Unit Cost</label>
            <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" id="unit_cost" name="unit_cost"
                       step="0.01" min="0" value="<?= htmlspecialchars($formData['unit_cost'] ?? '') ?>"
                       placeholder="Cost per unit">
            </div>
            <div class="form-text">Individual unit cost if different from total</div>
        </div>
    </div>
</div>
