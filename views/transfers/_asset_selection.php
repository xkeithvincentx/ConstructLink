<?php
/**
 * Asset Selection Partial (for create.php)
 *
 * Displays searchable asset dropdown with Alpine.js integration
 *
 * Required Parameters:
 * @param array $availableAssets List of available assets
 * @param array $formData Form data array (for pre-filling)
 * @param array $errors Validation errors array
 */

$availableAssets = $availableAssets ?? [];
$formData = $formData ?? [];
$errors = $errors ?? [];
?>

<!-- Asset Selection with Enhanced Search -->
<div class="row mb-4">
    <div class="col-md-12">
        <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>

        <!-- Hidden input for actual asset_id -->
        <input type="hidden" id="asset_id" name="asset_id" x-model="formData.asset_id" required>

        <!-- Validation div for asset selection -->
        <div x-show="!formData.asset_id" class="text-danger small mt-1" style="display: none;">
            Please select an asset to transfer.
        </div>

        <!-- Searchable Dropdown -->
        <div class="position-relative">
            <div class="input-group">
                <span class="input-group-text" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input type="text"
                       class="form-control"
                       placeholder="Search assets by reference, name, category, or location..."
                       x-model="searchText"
                       @input="filterAssets"
                       @focus="showDropdown = true"
                       @keydown.down.prevent="navigateDown"
                       @keydown.up.prevent="navigateUp"
                       @keydown.enter.prevent="selectHighlighted"
                       @keydown.escape="showDropdown = false"
                       aria-label="Search for asset to transfer">
                <button class="btn btn-outline-secondary"
                        type="button"
                        @click="clearSelection"
                        aria-label="Clear asset selection">
                    <i class="bi bi-x-circle" aria-hidden="true"></i> Clear
                </button>
            </div>

            <!-- Dropdown Results -->
            <div class="dropdown-menu w-100"
                 :class="{ 'show': showDropdown && (filteredAssets.length > 0 || searchText.length > 0) }"
                 style="max-height: 300px; overflow-y: auto;"
                 role="listbox"
                 aria-label="Asset search results">
                <template x-if="filteredAssets.length === 0 && searchText.length > 0">
                    <div class="dropdown-item-text text-muted">No assets found matching your search</div>
                </template>
                <template x-for="(asset, index) in filteredAssets" :key="asset.id">
                    <a href="#"
                       class="dropdown-item"
                       :class="{ 'active': index === highlightedIndex }"
                       @click.prevent="selectAsset(asset)"
                       @mouseenter="highlightedIndex = index"
                       role="option"
                       :aria-selected="index === highlightedIndex">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong x-text="asset.ref"></strong> - <span x-text="asset.name"></span><br>
                                <small class="text-muted">
                                    <span x-text="asset.category_name || 'Uncategorized'"></span> |
                                    Location: <span x-text="asset.project_name || 'Unknown'"></span>
                                </small>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </div>

        <div class="form-text">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Type to search available assets for transfer
        </div>
        <div class="invalid-feedback">Please select an asset to transfer.</div>
    </div>
</div>

<!-- Selected Asset Info -->
<div class="row mb-4" x-show="selectedAssetInfo" style="display: none;">
    <div class="col-md-12">
        <div class="alert alert-info" role="alert">
            <h6><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Selected Asset Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <strong>Reference:</strong><br>
                    <span x-text="selectedAssetInfo?.ref"></span>
                </div>
                <div class="col-md-4">
                    <strong>Name:</strong><br>
                    <span x-text="selectedAssetInfo?.name"></span>
                </div>
                <div class="col-md-3">
                    <strong>Category:</strong><br>
                    <span x-text="selectedAssetInfo?.category_name || 'Uncategorized'"></span>
                </div>
                <div class="col-md-2">
                    <strong>Current Location:</strong><br>
                    <span x-text="selectedAssetInfo?.project_name || 'Unknown'"></span>
                </div>
            </div>
        </div>
    </div>
</div>
