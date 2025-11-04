<?php
/**
 * Basic Information Section Partial
 * Item name, reference, and description fields with intelligent name generation
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
$showReference = $mode === 'standard';
$nameFieldId = $mode === 'legacy' ? 'asset_name' : 'name';
$nameFieldName = 'name';
$nameLabel = 'Item Name';
$namePlaceholder = $mode === 'legacy'
    ? 'Name will be generated automatically or enter custom name'
    : 'Select item type and subtype to auto-generate name...';
// Name field should NOT be readonly to allow form submission with auto-generated value
// Instead, we'll disable manual editing via JavaScript unless user clicks edit button
$nameReadonly = '';
$nameRequired = $mode === 'standard' ? '' : '';
$editButtonId = $mode === 'legacy' ? 'manual-edit-toggle' : 'edit-name-btn';
$useNameButtonId = $mode === 'legacy' ? 'use-generated-name' : 'apply-generated-name';
$hiddenNameFields = $mode === 'legacy'
    ? ['generated_name' => '', 'is_custom_name' => '0']
    : ['generated_name' => '', 'name_components' => '', 'is_manual_name' => '0'];
?>

<!-- Basic Information -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2 mb-3">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Basic Information
        </h6>
    </div>

    <?php if ($showReference): ?>
    <!-- Item Reference (Standard Mode Only) -->
    <div class="col-md-4">
        <div class="mb-3">
            <label for="ref" class="form-label">Item Reference</label>
            <input type="text" class="form-control" id="ref" name="ref"
                   value="<?= htmlspecialchars($formData['ref'] ?? '') ?>"
                   placeholder="Leave blank to auto-generate">
            <div class="form-text">Leave blank to auto-generate with system prefix</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Intelligent Item Name Generation -->
    <div class="<?= $showReference ? 'col-md-8' : 'col-12' ?>">
        <div class="mb-3">
            <label for="<?= $nameFieldId ?>" class="form-label">
                <?= htmlspecialchars($nameLabel) ?><?= $nameRequired ?>
                <?php if ($mode === 'standard'): ?>
                <small class="text-muted ms-2">Auto-generated from equipment selection</small>
                <?php endif; ?>
            </label>

            <?php if ($mode === 'standard'): ?>
            <!-- Standard Mode: Name Preview Before Input -->
            <div class="alert alert-success mt-2 border-0 bg-light d-none" id="name-preview"
                 role="status"
                 aria-live="polite"
                 aria-atomic="true">
                <div class="d-flex align-items-center">
                    <i class="bi bi-magic text-success me-2" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <strong>Generated Name Preview:</strong>
                        <div id="preview-name" class="text-success mt-1 fw-bold"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="<?= $useNameButtonId ?>"
                            aria-label="Use the generated name for this item">
                        Use This Name
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="input-group">
                <input type="text" class="form-control" id="<?= $nameFieldId ?>" name="<?= $nameFieldName ?>"
                       value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                       placeholder="<?= htmlspecialchars($namePlaceholder) ?>"
                       maxlength="200"
                       data-auto-generated="true">
                <button type="button" class="btn btn-outline-secondary" id="<?= $editButtonId ?>"
                        aria-label="Edit item name manually"
                        title="Edit <?= $mode === 'legacy' ? 'item' : '' ?> name manually">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                </button>
            </div>

            <?php if ($mode === 'legacy'): ?>
            <!-- Legacy Mode: Name Preview After Input -->
            <div id="name-preview" class="alert alert-success d-none mb-2 mt-2"
                 role="status"
                 aria-live="polite"
                 aria-atomic="true">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <i class="bi bi-lightbulb me-2" aria-hidden="true"></i>
                        <strong>Generated Name: </strong>
                        <span id="preview-name">-</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" id="<?= $useNameButtonId ?>"
                            aria-label="Use the generated name for this item">
                        <i class="bi bi-check me-1" aria-hidden="true"></i>Use This
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-text">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                <?= $mode === 'legacy'
                    ? 'Item name is automatically generated from your selections above. Click the pencil to edit manually.'
                    : 'Name will be auto-generated from your equipment selection. Click pencil icon to edit manually.' ?>
            </div>

            <?php if ($mode === 'standard'): ?>
            <div class="invalid-feedback">
                Please enter the asset name.
            </div>
            <?php endif; ?>

            <!-- Hidden fields for intelligent naming -->
            <?php foreach ($hiddenNameFields as $fieldName => $fieldValue): ?>
            <input type="hidden" id="<?= $fieldName ?>" name="<?= $fieldName ?>" value="<?= htmlspecialchars($fieldValue) ?>">
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Description Field -->
    <div class="col-12">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"
                      placeholder="<?= $mode === 'legacy' ? 'Brief description of the asset...' : 'Detailed description of the asset...' ?>"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
        </div>
    </div>
</div>
