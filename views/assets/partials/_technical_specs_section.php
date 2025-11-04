<?php
/**
 * Technical Specifications Section Partial
 * Quantity, unit, and detailed specifications
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
$specPlaceholder = $mode === 'legacy'
    ? 'Technical specifications, dimensions, capacity, etc...'
    : 'Detailed specifications, features, capabilities...';
$specHelpText = $mode === 'legacy'
    ? 'Optional technical details for legacy assets'
    : 'Technical specifications and features';
?>

<!-- Technical Specifications -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2 mb-3">
            <i class="bi bi-gear me-1" aria-hidden="true"></i>Technical Specifications
        </h6>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="quantity" class="form-label">
                Quantity
                <button type="button" class="btn btn-sm btn-outline-primary ms-2 d-none" id="bulk-entry-toggle">
                    <i class="bi bi-stack" aria-hidden="true"></i> Bulk Entry
                </button>
            </label>
            <input type="number" class="form-control" id="quantity" name="quantity"
                   value="<?= htmlspecialchars($formData['quantity'] ?? '1') ?>"
                   min="1" max="9999">
            <div class="form-text" id="quantity-help">
                <span id="quantity-consumable-text" class="d-none">
                    <i class="bi bi-info-circle me-1 text-success" aria-hidden="true"></i>
                    Quantity for consumable items
                </span>
                <span id="quantity-serialized-text" class="d-none">
                    <i class="bi bi-exclamation-triangle me-1 text-warning" aria-hidden="true"></i>
                    Serial-tracked items: quantity = 1 (each item needs separate entry)
                </span>
                <span id="quantity-bulk-text" class="d-none">
                    <i class="bi bi-stack me-1 text-primary" aria-hidden="true"></i>
                    Bulk entry allowed - identical non-serialized items
                </span>
                <span id="quantity-default-text">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    Item quantity
                </span>
            </div>
        </div>
    </div>

    <!-- Bulk Entry Panel (Hidden by default) -->
    <div class="col-12 d-none" id="bulk-entry-panel">
        <div class="alert alert-info border-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6><i class="bi bi-stack me-2" aria-hidden="true"></i>Bulk Entry Mode</h6>
                    <p class="mb-2">Creating multiple identical items efficiently. Each item will get a unique asset reference.</p>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Items to create:</label>
                            <input type="number" class="form-control" id="bulk-quantity" min="2" max="999" value="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Serial numbering:</label>
                            <select class="form-select" id="bulk-serial-mode">
                                <option value="none">No serial numbers</option>
                                <option value="sequence">Sequential (001, 002, 003...)</option>
                                <option value="custom">Custom prefix</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-none" id="bulk-prefix-container">
                            <label class="form-label">Serial prefix:</label>
                            <input type="text" class="form-control" id="bulk-serial-prefix" placeholder="e.g., HAM-">
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="bulk-entry-close"
                        aria-label="Close bulk entry mode">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="unit" class="form-label">Unit</label>
            <select class="form-select" id="unit" name="unit">
                <option value="pcs" <?= ($formData['unit'] ?? 'pcs') === 'pcs' ? 'selected' : '' ?>>Pieces</option>
                <option value="unit" <?= ($formData['unit'] ?? '') === 'unit' ? 'selected' : '' ?>>Unit</option>
                <option value="set" <?= ($formData['unit'] ?? '') === 'set' ? 'selected' : '' ?>>Set</option>
                <option value="box" <?= ($formData['unit'] ?? '') === 'box' ? 'selected' : '' ?>>Box</option>
                <option value="kg" <?= ($formData['unit'] ?? '') === 'kg' ? 'selected' : '' ?>>Kilogram</option>
                <option value="m" <?= ($formData['unit'] ?? '') === 'm' ? 'selected' : '' ?>>Meter</option>
                <option value="m3" <?= ($formData['unit'] ?? '') === 'm3' ? 'selected' : '' ?>>Cubic Meter</option>
                <option value="sqm" <?= ($formData['unit'] ?? '') === 'sqm' ? 'selected' : '' ?>>Square Meter</option>
                <option value="l" <?= ($formData['unit'] ?? '') === 'l' ? 'selected' : '' ?>>Liter</option>
                <option value="lot" <?= ($formData['unit'] ?? '') === 'lot' ? 'selected' : '' ?>>Lot</option>
            </select>
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label for="specifications" class="form-label">Detailed Specifications</label>
            <textarea class="form-control" id="specifications" name="specifications" rows="3"
                      placeholder="<?= htmlspecialchars($specPlaceholder) ?>"><?= htmlspecialchars($formData['specifications'] ?? '') ?></textarea>
            <div class="form-text"><?= htmlspecialchars($specHelpText) ?></div>
        </div>
    </div>
</div>
