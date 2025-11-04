<?php
/**
 * Brand & Discipline Section Partial
 * Brand/manufacturer selection, model/serial, and discipline classification
 *
 * Required Variables:
 * @var string $mode - Form mode: 'legacy' or 'standard'
 * @var array $formData - Form data for pre-filling (optional)
 * @var array $brands - Available brands/manufacturers
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
$sectionSubtitle = $mode === 'legacy'
    ? 'Smart classification for legacy assets'
    : 'Smart classification based on category selection';
$brandHelpText = $mode === 'legacy'
    ? 'Select from verified construction brands (updates generated name)'
    : 'Select from verified construction brands';
$modelPlaceholder = $mode === 'legacy' ? 'e.g., DHP484Z' : 'e.g., XR20';
$modelHelpText = $mode === 'legacy' ? '<div class="form-text">Updates generated name</div>' : '';
$primaryDisciplineLabel = $mode === 'legacy' ? 'Primary Discipline' : 'Primary Discipline <span class="text-danger">*</span>';
$primaryDisciplinePlaceholder = $mode === 'legacy' ? 'Select Primary Use (Optional)' : 'Select Primary Use';
$primaryDisciplineHelpText = $mode === 'legacy'
    ? 'Main discipline where this asset was used'
    : 'Main discipline where this asset will be used';
$disciplineAlertText = $mode === 'legacy'
    ? 'This legacy asset type is used across multiple engineering disciplines.'
    : 'This asset type is used across multiple engineering disciplines.';
$disciplineCheckboxHelpText = $mode === 'legacy'
    ? 'Select all applicable disciplines for this legacy asset'
    : 'Select all applicable disciplines for this asset';
?>

<!-- Brand & Engineering Usage Smart Section -->
<div class="row mb-4" id="brand-discipline-section">
    <div class="col-12">
        <h6 class="text-info border-bottom pb-2 mb-3">
            <i class="bi bi-award me-1" aria-hidden="true"></i>Brand & Engineering Usage
            <small class="text-muted ms-2"><?= htmlspecialchars($sectionSubtitle) ?></small>
        </h6>
    </div>

    <!-- Brand Section -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="brand" class="form-label">Brand/Manufacturer</label>
            <select class="form-select" id="brand" name="brand">
                <option value="">Select Brand</option>
                <?php if (!empty($brands)): ?>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= htmlspecialchars($brand['official_name']) ?>"
                                data-brand-id="<?= $brand['id'] ?>"
                                data-quality="<?= $brand['quality_tier'] ?>"
                                <?= ($formData['brand'] ?? '') == $brand['official_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($brand['official_name']) ?>
                            <?php if (!empty($brand['quality_tier'])): ?>
                                - <?= ucfirst($brand['quality_tier']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="form-text"><?= htmlspecialchars($brandHelpText) ?></div>
            <!-- Hidden fields for brand standardization -->
            <input type="hidden" id="standardized_brand" name="standardized_brand">
            <input type="hidden" id="brand_id" name="brand_id">
        </div>
    </div>

    <!-- Model/Serial Section -->
    <div class="col-md-6">
        <div class="row">
            <div class="<?= $mode === 'legacy' ? 'col-6' : 'col-sm-6' ?>">
                <div class="mb-3">
                    <label for="model" class="form-label">Model</label>
                    <input type="text" class="form-control" id="model" name="model"
                           value="<?= htmlspecialchars($formData['model'] ?? '') ?>"
                           placeholder="<?= htmlspecialchars($modelPlaceholder) ?>">
                    <?= $modelHelpText ?>
                </div>
            </div>
            <div class="<?= $mode === 'legacy' ? 'col-6' : 'col-sm-6' ?>">
                <div class="mb-3">
                    <label for="serial_number" class="form-label">Serial #</label>
                    <input type="text" class="form-control" id="serial_number" name="serial_number"
                           value="<?= htmlspecialchars($formData['serial_number'] ?? '') ?>"
                           placeholder="Optional">
                </div>
            </div>
        </div>
    </div>

    <!-- Disciplines Section (Initially Hidden, Shows Based on Category) -->
    <div id="discipline-section" class="row d-none">
        <div class="col-12">
            <div class="alert alert-info py-2 mb-3">
                <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>
                <strong>Multi-Disciplinary Classification:</strong> <?= htmlspecialchars($disciplineAlertText) ?> Select the applicable ones.
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="mb-3">
                <label for="primary_discipline" class="form-label">
                    <?= $primaryDisciplineLabel ?>
                </label>
                <select class="form-select" id="primary_discipline" name="primary_discipline">
                    <option value=""><?= htmlspecialchars($primaryDisciplinePlaceholder) ?></option>
                </select>
                <div class="form-text"><?= htmlspecialchars($primaryDisciplineHelpText) ?></div>
            </div>
        </div>

        <div class="col-lg-8 col-md-12">
            <div class="mb-3">
                <label class="form-label">Also Used In:</label>
                <div id="discipline-checkboxes" class="border rounded p-3 bg-light overflow-auto" style="max-height: 160px;">
                    <!-- Dynamically populated discipline checkboxes -->
                </div>
                <div class="form-text"><?= htmlspecialchars($disciplineCheckboxHelpText) ?></div>
            </div>
        </div>
    </div>
</div>
