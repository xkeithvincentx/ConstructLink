<?php
/**
 * Location & Condition Section Partial
 * Current location and condition notes
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
?>

<!-- Location & Condition -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2 mb-3">
            <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Location & Condition
        </h6>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="location" class="form-label">Current Location</label>
            <input type="text" class="form-control" id="location" name="location"
                   value="<?= htmlspecialchars($formData['location'] ?? '') ?>"
                   placeholder="Warehouse, Tool Room, Site Area, etc.">
            <div class="form-text">Where is this asset currently located?</div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="condition_notes" class="form-label">Condition Notes</label>
            <textarea class="form-control" id="condition_notes" name="condition_notes" rows="3"
                      placeholder="Describe the current condition of the asset..."><?= htmlspecialchars($formData['condition_notes'] ?? '') ?></textarea>
            <div class="form-text">Note any wear, damage, or maintenance needs</div>
        </div>
    </div>
</div>
