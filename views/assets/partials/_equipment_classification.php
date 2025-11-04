<?php
/**
 * Equipment Classification Partial
 * Intelligent equipment type and subtype selection with auto-naming
 * Supports both legacy (inline) and standard (separate section) modes
 * NOW WITH ALPINE.JS REACTIVE DROPDOWN SYNCHRONIZATION
 *
 * Required Variables:
 * @var string $mode - Form mode: 'legacy' or 'standard'
 * @var array $formData - Form data for pre-filling (optional)
 *
 * @package ConstructLink
 * @subpackage Views\Assets\Partials
 * @version 2.0.0 - Alpine.js Integration
 * @since Phase 2 Refactoring / Phase 4 Alpine.js Integration
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Determine section attributes based on mode
$sectionClass = $mode === 'standard' ? 'row mb-4 d-none' : 'row mb-0';
$sectionId = $mode === 'standard' ? 'equipment-classification-section' : '';
$showHeader = $mode === 'standard';
$clearIcon = $mode === 'standard' ? 'bi-x-circle' : 'bi-arrow-clockwise';
$clearText = $mode === 'standard' ? '' : ' Clear';
$typeLabel = $mode === 'standard' ? 'Item Type' : 'Item Type';
$typePlaceholder = $mode === 'legacy' ? 'Type to search equipment...' : 'Select Item Type';
$typeHelpText = $mode === 'legacy'
    ? '<i class="bi bi-lightbulb text-warning"></i> Start typing equipment name (e.g., "drill", "hammer") - category will be auto-selected'
    : 'What type of equipment is this?';
$subtypeLabel = $mode === 'standard' ? 'Specific Subtype' : 'Item Subtype';
$subtypeHelpText = $mode === 'standard'
    ? 'What specific type/variation is it? (Required only if subtypes are available)'
    : '';
?>

<!-- Equipment Classification Section with Alpine.js -->
<div
    class="<?= $sectionClass ?>"
    <?php if ($sectionId): ?>id="<?= $sectionId ?>"<?php endif; ?>
>
    <?php if ($showHeader): ?>
    <div class="col-12">
        <h6 class="text-success border-bottom pb-2 mb-3">
            <i class="bi bi-cpu me-1" aria-hidden="true"></i>Intelligent Equipment Classification
            <small class="text-muted ms-2">Smart item type selection with auto-naming</small>
        </h6>
    </div>
    <?php endif; ?>

    <!-- Item Type Selection -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="equipment_type_id" class="form-label">
                <?= htmlspecialchars($typeLabel) ?> <span class="text-danger">*</span>
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm ms-2"
                    @click="clearAll()"
                    aria-label="Clear equipment selection and reset to all types"
                    title="Clear equipment selection and reset to all types"
                >
                    <i class="<?= $clearIcon ?>" aria-hidden="true"></i><?= $clearText ?>
                </button>
                <?php if ($mode === 'standard'): ?>
                <i class="bi bi-info-circle ms-1" aria-hidden="true" title="Main equipment category (e.g., Drill, Grinder, Welder)"></i>
                <?php endif; ?>
            </label>

            <select
                class="form-select"
                id="equipment_type_id"
                name="equipment_type_id"
                required
                x-ref="equipmentTypeSelect"
                x-model="equipmentTypeId"
            >
                <option value=""><?= htmlspecialchars($typePlaceholder) ?></option>
                <template x-for="type in filteredEquipmentTypes" :key="type.id">
                    <option
                        :value="type.id"
                        x-text="<?= $mode === 'legacy' ? 'type.name + \' (\' + type.category_name + \')\'' : 'type.name' ?>"
                        :data-category-id="type.category_id"
                        :data-category-name="type.category_name"
                    ></option>
                </template>
            </select>

            <div class="form-text">
                <?= $typeHelpText ?>
                <span x-show="loadingEquipmentTypes" class="text-muted">
                    <span class="spinner-border spinner-border-sm ms-2" role="status"></span>
                    Loading...
                </span>
            </div>

            <div class="invalid-feedback">
                Please select an item type.
            </div>
        </div>
    </div>

    <!-- Subtype Selection -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="subtype_id" class="form-label">
                <?= htmlspecialchars($subtypeLabel) ?>
                <span class="text-danger d-none" id="subtype-required-asterisk" x-show="subtypes.length > 0">*</span>
                <?php if ($mode === 'standard'): ?>
                <i class="bi bi-info-circle ms-1" aria-hidden="true" title="Specific variation (e.g., Electric, Cordless, Angle)"></i>
                <?php endif; ?>
            </label>

            <select
                class="form-select"
                id="subtype_id"
                name="subtype_id"
                x-ref="subtypeSelect"
                x-model="subtypeId"
                :required="subtypes.length > 0"
            >
                <option value=""><?= $mode === 'standard' ? 'Select Specific Subtype' : 'Select Subtype' ?></option>
                <template x-if="loadingSubtypes">
                    <option value="">Loading subtypes...</option>
                </template>
                <template x-if="!loadingSubtypes && subtypes.length === 0 && equipmentTypeId">
                    <option value="">No subtypes available</option>
                </template>
                <template x-for="subtype in subtypes" :key="subtype.id">
                    <option
                        :value="subtype.id"
                        x-text="subtype.subtype_name"
                        :data-material="subtype.material_type || ''"
                        :data-power="subtype.power_source || ''"
                        :data-application="subtype.application_area || ''"
                    ></option>
                </template>
            </select>

            <?php if ($subtypeHelpText): ?>
            <div class="form-text"><?= htmlspecialchars($subtypeHelpText) ?></div>
            <?php endif; ?>

            <div class="invalid-feedback">
                Please select an item subtype.
            </div>
        </div>
    </div>

    <?php if ($mode === 'standard'): ?>
    <!-- Equipment Details Display (Standard Mode Only) -->
    <div class="col-12 d-none" id="equipment-details" x-show="itemTypeData">
        <div class="alert alert-info border-0">
            <div class="row">
                <div class="col-md-6" x-show="itemTypeData?.material_type">
                    <strong><i class="bi bi-gear me-1" aria-hidden="true"></i>Material Type:</strong>
                    <span x-text="itemTypeData?.material_type || 'N/A'">-</span>
                </div>
                <div class="col-md-6" x-show="itemTypeData?.power_source">
                    <strong><i class="bi bi-lightning me-1" aria-hidden="true"></i>Power Source:</strong>
                    <span x-text="itemTypeData?.power_source || 'N/A'">-</span>
                </div>
                <div class="col-12 mt-2" x-show="itemTypeData?.application_area">
                    <strong><i class="bi bi-wrench me-1" aria-hidden="true"></i>Application:</strong>
                    <span x-text="itemTypeData?.application_area || 'N/A'">-</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State Indicator -->
    <div class="col-12" x-show="loadingItemTypeData">
        <div class="alert alert-info">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
            Loading item type data...
        </div>
    </div>

    <!-- Error State -->
    <div class="col-12" x-show="error">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <span x-text="error"></span>
        </div>
    </div>
    <?php endif; ?>
</div>
