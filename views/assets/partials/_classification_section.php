<?php
/**
 * Classification Section Partial
 * Category and project selection with smart role-based project access
 *
 * Required Variables:
 * @var string $mode - Form mode: 'legacy' or 'standard'
 * @var array $formData - Form data for pre-filling (optional)
 * @var array $categories - Available categories
 * @var array $projects - Available projects
 * @var string $userRole - Current user's role (for project filtering)
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
$sectionTitle = $mode === 'legacy' ? 'Classification' : 'Classification & Details';
$showCategoryInfo = $mode === 'legacy';
?>

<!-- Classification -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2 mb-3">
            <i class="bi bi-tags me-1" aria-hidden="true"></i><?= htmlspecialchars($sectionTitle) ?>
        </h6>
    </div>

    <!-- Category Selection -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="category_id" class="form-label">
                Category <span class="text-danger">*</span>
                <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="clear-category-btn"
                        aria-label="Clear category selection to see all item types"
                        title="Clear category to see all item types">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Reset
                </button>
            </label>
            <select class="form-select" id="category_id" name="category_id" required data-disciplines="true"
                    x-ref="categorySelect"
                    x-model="categoryId">
                <option value="">Select Category</option>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"
                                <?php if ($mode === 'legacy'): ?>
                                data-asset-type="<?= htmlspecialchars($category['asset_type'] ?? 'capital') ?>"
                                data-generates-assets="<?= $category['generates_assets'] ? '1' : '0' ?>"
                                data-is-consumable="<?= $category['is_consumable'] ? '1' : '0' ?>"
                                data-threshold="<?= htmlspecialchars($category['capitalization_threshold'] ?? '0') ?>"
                                data-business-desc="<?= htmlspecialchars($category['business_description'] ?? '') ?>"
                                <?php endif; ?>
                                data-disciplines="<?= htmlspecialchars($category['discipline_tags'] ?? '') ?>"
                                data-keywords="<?= htmlspecialchars($category['search_keywords'] ?? '') ?>"
                                <?= ($formData['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?php if ($mode === 'legacy'): ?>
                                <?php
                                $assetTypeIcon = '';
                                switch($category['asset_type'] ?? 'capital') {
                                    case 'capital': $assetTypeIcon = '🔧'; break;
                                    case 'inventory': $assetTypeIcon = '📦'; break;
                                    case 'expense': $assetTypeIcon = '💰'; break;
                                }
                                echo $assetTypeIcon . ' ' . htmlspecialchars($category['name']);
                                ?>
                                <?= $category['is_consumable'] ? ' (Consumable)' : '' ?>
                            <?php else: ?>
                                <?= htmlspecialchars($category['name']) ?>
                                <?php if (!empty($category['parent_name'])): ?>
                                    (<?= htmlspecialchars($category['parent_name']) ?>)
                                <?php endif; ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="invalid-feedback">
                Please select a category.
            </div>

            <?php if ($showCategoryInfo): ?>
            <!-- Category Business Information Panel (Legacy Mode Only) -->
            <div id="category-info" class="mt-2 d-none"
                 role="status"
                 aria-live="polite"
                 aria-atomic="true">
                <div class="card border-info">
                    <div class="card-body p-2">
                        <h6 class="card-title text-info mb-1">
                            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Category Business Classification
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small>
                                    <strong>Asset Type:</strong> <span id="category-asset-type"></span><br>
                                    <strong>Generates Assets:</strong> <span id="category-generates-assets"></span>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small>
                                    <strong>Consumable:</strong> <span id="category-is-consumable"></span><br>
                                    <strong>Threshold:</strong> <span id="category-threshold"></span>
                                </small>
                            </div>
                        </div>
                        <div id="category-business-desc" class="mt-2 d-none">
                            <small class="text-muted"></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project Selection -->
    <div class="col-md-6">
        <div class="mb-3">
            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
            <?php if ($mode === 'legacy' && in_array($userRole, ['System Admin', 'Asset Director'])): ?>
                <!-- Full project access for System Admin and Asset Director (Legacy Mode) -->
                <select class="form-select" id="project_id" name="project_id" required>
                    <option value="">Select Project</option>
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                    <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                                <?php if (!empty($project['location'])): ?>
                                    - <?= htmlspecialchars($project['location']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            <?php else: ?>
                <!-- Standard project selection -->
                <select class="form-select" id="project_id" name="project_id" required>
                    <option value="">Select Project</option>
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                    <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                                <?php if (!empty($project['location'])): ?>
                                    - <?= htmlspecialchars($project['location']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            <?php endif; ?>

            <?php if ($mode === 'legacy' && !in_array($userRole, ['System Admin', 'Asset Director'])): ?>
                <!-- Role-specific project assignment message (Legacy Mode) -->
                <div class="form-text">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    <?php if (!empty($user['default_project'])): ?>
                        Assigned to: <?= htmlspecialchars($user['default_project']) ?>
                    <?php else: ?>
                        You have access to specific projects only
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="invalid-feedback">
                Please select a project.
            </div>
        </div>
    </div>
</div>
