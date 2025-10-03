<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();


if (!hasPermission('borrowed-tools/create')) {
    echo '<div class="alert alert-danger">You do not have permission to create a borrowed tool request.</div>';
    return;
}
?>

<!-- Custom styles for searchable dropdown -->
<style>
.cursor-pointer {
    cursor: pointer;
}

.dropdown-menu {
    display: block;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 0.375rem;
}

.dropdown-item:hover,
.dropdown-item.active {
    background-color: #f8f9fa;
    border-left: 3px solid #0d6efd;
}

.dropdown-item.active {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.asset-search-dropdown {
    background-color: white;
    border: 1px solid #ced4da;
}

.asset-search-dropdown:focus-within {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>

<!-- MVA Workflow Information -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="alert alert-success">
            <h6><i class="bi bi-lightning-charge me-1"></i><strong>Basic Tools Workflow</strong> (≤₱50,000)</h6>
            <p class="mb-2"><span class="badge bg-primary">Streamlined Process</span> - One-click borrowing</p>
            <small>Warehouseman: Create → Auto-Verify → Auto-Approve → Borrowed</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert alert-warning">
            <h6><i class="bi bi-shield-check me-1"></i><strong>Critical Tools Workflow</strong> (>₱50,000)</h6>
            <p class="mb-2"><span class="badge bg-warning text-dark">Full MVA Process</span></p>
            <small>
                <span class="badge bg-primary">Maker</span> (Warehouseman) →
                <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
                <span class="badge bg-success">Authorizer</span> (Asset Director/Finance Director)
            </small>
        </div>
    </div>
</div>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Borrow Tool Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Tool Borrowing Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=borrowed-tools/create" class="needs-validation" novalidate x-data="borrowToolForm()">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Asset Selection -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           class="form-control <?= isset($errors) && in_array('Asset is required', $errors) ? 'is-invalid' : '' ?>" 
                                           id="asset_search" 
                                           placeholder="Search for assets..." 
                                           autocomplete="off"
                                           x-model="searchText"
                                           @input="filterAssets()"
                                           @focus="showDropdown = true"
                                           @keydown.arrow-down.prevent="selectNext()"
                                           @keydown.arrow-up.prevent="selectPrevious()"
                                           @keydown.enter.prevent="selectCurrent()"
                                           @keydown.escape="showDropdown = false">
                                    <button type="button" 
                                            class="btn btn-outline-primary" 
                                            id="qr-scan-btn"
                                            @click="toggleQRScanner()"
                                            :disabled="scannerActive">
                                        <i class="bi bi-qr-code-scan"></i>
                                        <span x-text="scannerActive ? 'Scanning...' : 'Scan QR'"></span>
                                    </button>
                                    <!-- Debug buttons -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info ms-1" 
                                            @click="console.log('🧪 Debug: AlpineJS is working', $data)">
                                        <i class="bi bi-bug"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-warning ms-1" 
                                            @click="testDirectCamera()"
                                            title="Test camera directly">
                                        <i class="bi bi-camera"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success ms-1" 
                                            @click="testQRAPI()"
                                            title="Test QR API">
                                        <i class="bi bi-api"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger ms-1" 
                                            @click="forceStartCamera()"
                                            title="Force start camera (bypass modal)">
                                        <i class="bi bi-lightning"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary ms-1" 
                                            @click="debugModalState()"
                                            title="Debug modal state">
                                        <i class="bi bi-window"></i>
                                    </button>
                                </div>
                                
                                <!-- Hidden field for actual form submission -->
                                <input type="hidden" 
                                       id="asset_id" 
                                       name="asset_id" 
                                       x-model="formData.asset_id" 
                                       required>
                                
                                <!-- Dropdown list -->
                                <div class="dropdown-menu w-100 shadow-lg" 
                                     style="max-height: 300px; overflow-y: auto;"
                                     x-show="showDropdown && filteredAssets.length > 0"
                                     x-transition
                                     @click.away="showDropdown = false">
                                    <template x-for="(asset, index) in filteredAssets" :key="asset.id">
                                        <div class="dropdown-item cursor-pointer d-flex justify-content-between align-items-start p-3"
                                             :class="{ 'active': index === selectedIndex }"
                                             @click="selectAsset(asset)"
                                             @mouseenter="selectedIndex = index">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-primary" x-text="asset.ref"></div>
                                                <div class="text-dark" x-text="asset.name"></div>
                                                <div class="small text-muted">
                                                    <span x-text="asset.category_name"></span>
                                                    <span x-show="asset.project_name"> • <span x-text="asset.project_name"></span></span>
                                                    <span x-show="asset.model"> • Model: <span x-text="asset.model"></span></span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle"></i> Available
                                                </small>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- No results message -->
                                    <div x-show="filteredAssets.length === 0 && searchText.length > 0" 
                                         class="dropdown-item-text text-muted text-center p-3">
                                        <i class="bi bi-search"></i> No assets found matching "<span x-text="searchText"></span>"
                                    </div>
                                </div>
                                
                                <div class="invalid-feedback">Please select an asset to borrow.</div>
                            </div>
                            
                            <!-- Selected asset info -->
                            <div x-show="selectedAsset" class="mt-2 p-2 bg-light rounded">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted">Selected:</div>
                                        <div class="fw-bold">
                                            <span x-text="selectedAsset?.ref"></span> - <span x-text="selectedAsset?.name"></span>
                                        </div>
                                        <div class="small text-muted">
                                            <span x-text="selectedAsset?.category_name"></span>
                                            <span x-show="selectedAsset?.project_name"> • <span x-text="selectedAsset?.project_name"></span></span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div x-show="selectedAsset?.acquisition_cost <= 50000">
                                            <span class="badge bg-success">
                                                <i class="bi bi-lightning-charge"></i> Basic Tool
                                            </span>
                                            <div class="small text-muted mt-1">Streamlined Process</div>
                                        </div>
                                        <div x-show="selectedAsset?.acquisition_cost > 50000">
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-shield-check"></i> Critical Tool
                                            </span>
                                            <div class="small text-muted mt-1">Full MVA Required</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> Only non-consumable assets from your current project are shown
                            </div>
                        </div>
                    </div>

                    <!-- Borrower Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="borrower_name" class="form-label">Borrower Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors) && in_array('Borrower name is required', $errors) ? 'is-invalid' : '' ?>" 
                                   id="borrower_name" 
                                   name="borrower_name" 
                                   value="<?= htmlspecialchars($formData['borrower_name'] ?? '') ?>" 
                                   required
                                   x-model="formData.borrower_name">
                            <div class="invalid-feedback">Please provide the borrower's name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="borrower_contact" class="form-label">Contact Information</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="borrower_contact" 
                                   name="borrower_contact" 
                                   value="<?= htmlspecialchars($formData['borrower_contact'] ?? '') ?>"
                                   placeholder="Phone number or email"
                                   x-model="formData.borrower_contact">
                            <div class="form-text">Optional but recommended</div>
                        </div>
                    </div>

                    <!-- Return Date -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="expected_return" class="form-label">Expected Return Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control <?= isset($errors) && in_array('Expected return date is required', $errors) ? 'is-invalid' : '' ?>" 
                                   id="expected_return" 
                                   name="expected_return" 
                                   value="<?= htmlspecialchars($formData['expected_return'] ?? '') ?>" 
                                   required
                                   x-model="formData.expected_return">
                            <div class="form-text">When do you expect to return this tool?</div>
                            <div class="invalid-feedback">Please provide the expected return date.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Borrowing Period</label>
                            <div class="form-control-plaintext" id="borrowingPeriod">
                                <span class="text-muted">Select return date to see period</span>
                            </div>
                        </div>
                    </div>

                    <!-- Purpose -->
                    <div class="mb-4">
                        <label for="purpose" class="form-label">Purpose/Reason for Borrowing</label>
                        <textarea class="form-control" 
                                  id="purpose" 
                                  name="purpose" 
                                  rows="3" 
                                  placeholder="Describe why you need this tool..."
                                  x-model="formData.purpose"><?= htmlspecialchars($formData['purpose'] ?? '') ?></textarea>
                        <div class="form-text">Provide details about the intended use</div>
                    </div>

                    <!-- Tool Condition -->
                    <div class="mb-4">
                        <label for="condition_out" class="form-label">Tool Condition (Before Borrowing)</label>
                        <textarea class="form-control" 
                                  id="condition_out" 
                                  name="condition_out" 
                                  rows="2" 
                                  placeholder="Describe the current condition of the tool..."
                                  x-model="formData.condition_out"><?= htmlspecialchars($formData['condition_out'] ?? '') ?></textarea>
                        <div class="form-text">Document any existing damage or issues</div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <div class="d-flex align-items-center">
                            <!-- Dynamic button text based on tool type -->
                            <button type="submit" class="btn btn-primary" 
                                    x-show="!selectedAsset || selectedAsset?.acquisition_cost <= 50000">
                                <i class="bi bi-lightning-charge me-1"></i>Process Tool (Streamlined)
                            </button>
                            <button type="submit" class="btn btn-warning" 
                                    x-show="selectedAsset?.acquisition_cost > 50000">
                                <i class="bi bi-shield-check me-1"></i>Create Request (MVA Required)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- QR Scanner Modal -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrScannerModalLabel">
                        <i class="bi bi-qr-code-scan me-2"></i>Scan Asset QR Code
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <!-- Scanner Status -->
                    <div class="alert" :class="scannerStatus.type" x-show="scannerStatus.message">
                        <i class="bi" :class="scannerStatus.icon"></i>
                        <span x-text="scannerStatus.message"></span>
                    </div>
                    
                    <!-- Camera Permission Request -->
                    <div class="mb-3" x-show="!scannerActive && scannerStatus.type !== 'alert-success'">
                        <div class="text-center">
                            <button type="button" 
                                    class="btn btn-primary btn-lg"
                                    @click="requestCameraPermission()"
                                    :disabled="scannerStatus.type === 'alert-info'">
                                <i class="bi bi-camera me-2"></i>
                                <span x-text="scannerStatus.type === 'alert-info' ? 'Starting Camera...' : 'Allow Camera Access'"></span>
                            </button>
                            <p class="text-muted mt-2 small">
                                <i class="bi bi-info-circle me-1"></i>
                                Please allow camera access to scan QR codes
                            </p>
                        </div>
                        
                        <!-- HTTPS Warning -->
                        <div class="alert alert-warning small" x-show="location.protocol === 'http:' && !location.hostname.includes('localhost')">
                            <i class="bi bi-shield-exclamation me-2"></i>
                            <strong>Camera Unavailable:</strong> QR scanning requires HTTPS connection. 
                            <br>Try accessing via: <code x-text="location.href.replace('http://', 'https://')"></code>
                        </div>
                    </div>
                    
                    <!-- Camera Selection -->
                    <div class="mb-3" x-show="availableCameras.length > 1 && scannerActive">
                        <label class="form-label small">Camera:</label>
                        <div class="btn-group" role="group">
                            <template x-for="(camera, index) in availableCameras" :key="camera.deviceId">
                                <button type="button" 
                                        class="btn btn-sm"
                                        :class="selectedCameraId === camera.deviceId ? 'btn-primary' : 'btn-outline-secondary'"
                                        @click="selectedCameraId = camera.deviceId; switchCamera()"
                                        x-text="camera.label || `Camera ${index + 1}`">
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Camera Preview -->
                    <div class="position-relative d-inline-block mb-3">
                        <video id="qr-video" width="400" height="300" class="border rounded" style="display: none;"></video>
                        <canvas id="qr-canvas" width="400" height="300" class="border rounded" style="display: none;"></canvas>
                        
                        <!-- Scanner Overlay (when camera not active) -->
                        <div id="qr-overlay" class="position-absolute top-50 start-50 translate-middle">
                            <div class="text-center">
                                <i class="bi bi-qr-code display-1 text-muted mb-3"></i>
                                <h5>Position QR code in view</h5>
                                <p class="text-muted">The camera will automatically detect the QR code</p>
                            </div>
                        </div>
                        
                        <!-- Scanning Indicator (when camera is active) -->
                        <div id="qr-scanning-indicator" 
                             class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                             style="display: none !important; background: rgba(0,0,0,0.1); pointer-events: none;"
                             x-show="scannerActive">
                            <div class="bg-white rounded p-2 shadow" style="background: rgba(255,255,255,0.9);">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <small class="text-primary fw-bold">Scanning for QR codes...</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- QR Detection Frame -->
                        <div id="qr-detection-frame" 
                             class="position-absolute border border-primary rounded"
                             style="display: none; width: 200px; height: 200px; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;">
                            <div class="position-absolute" style="top: -1px; left: -1px; width: 20px; height: 20px; border-top: 3px solid #0d6efd; border-left: 3px solid #0d6efd;"></div>
                            <div class="position-absolute" style="top: -1px; right: -1px; width: 20px; height: 20px; border-top: 3px solid #0d6efd; border-right: 3px solid #0d6efd;"></div>
                            <div class="position-absolute" style="bottom: -1px; left: -1px; width: 20px; height: 20px; border-bottom: 3px solid #0d6efd; border-left: 3px solid #0d6efd;"></div>
                            <div class="position-absolute" style="bottom: -1px; right: -1px; width: 20px; height: 20px; border-bottom: 3px solid #0d6efd; border-right: 3px solid #0d6efd;"></div>
                        </div>
                    </div>
                    
                    <!-- Camera Permission Help -->
                    <div class="alert alert-info" x-show="scannerStatus.type === 'alert-danger' && scannerStatus.message.includes('permission')">
                        <h6><i class="bi bi-lightbulb me-2"></i>Camera Permission Help</h6>
                        <small>
                            <strong>If camera access was denied:</strong><br>
                            1. Look for a camera icon <i class="bi bi-camera-video"></i> in your browser's address bar<br>
                            2. Click it and select "Allow"<br>
                            3. Refresh the page if needed<br><br>
                            <strong>Chrome:</strong> Look for camera icon near the address bar<br>
                            <strong>Firefox:</strong> Check the left side of the address bar<br>
                            <strong>Safari:</strong> Look in Safari menu → Settings for This Website
                        </small>
                    </div>
                    
                    <!-- Manual Input Alternative -->
                    <div class="border-top pt-3">
                        <h6 class="mb-3">Or Enter Asset Reference Manually</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" 
                                       class="form-control" 
                                       id="manual-asset-ref" 
                                       placeholder="Enter asset reference (e.g., CL2025012345)"
                                       x-model="manualAssetRef">
                            </div>
                            <div class="col-md-4">
                                <button type="button" 
                                        class="btn btn-outline-primary w-100" 
                                        @click="searchAssetByRef()"
                                        :disabled="!manualAssetRef">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                            </div>
                        </div>
                        
                        <!-- Debug Info -->
                        <div class="mt-2 small text-muted">
                            <div>Camera Status: <span x-text="scannerActive ? 'Active' : 'Inactive'"></span></div>
                            <div>Protocol: <span x-text="location.protocol"></span></div>
                            <div>Hostname: <span x-text="location.hostname"></span></div>
                            <div class="mt-2">
                                <strong>Note:</strong> This scanner requires SecureLink™ QR codes generated from the asset tag management system. 
                                Plain asset references (like "CL20250106") will not work - you need the base64-encoded QR data from printed asset tags.
                            </div>
                            <div id="debug-info"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar with Guidelines -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Borrowing Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Borrowable Items</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-1"></i> Tools & Equipment</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> Non-consumable assets</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> Project-specific items only</li>
                    </ul>
                    <div class="alert alert-warning py-2 px-3 mb-0">
                        <small><i class="bi bi-exclamation-triangle me-1"></i> 
                        <strong>Note:</strong> Consumable items (nails, cement, etc.) cannot be borrowed as they are meant for consumption.</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Required Information</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-1"></i> Asset to borrow</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> Borrower name</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> Expected return date</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Before Borrowing</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1"></i> Verify tool condition</li>
                        <li><i class="bi bi-check text-success me-1"></i> Set realistic return date</li>
                        <li><i class="bi bi-check text-success me-1"></i> Provide accurate contact info</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Your Responsibilities</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Return on time</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Report damage immediately</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Use tool properly and safely</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Keep tool in good condition</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> Document the tool's condition before borrowing to avoid disputes when returning.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=assets" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box-seam me-1"></i>View All Assets
                    </a>
                    <a href="?route=borrowed-tools" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-clock-history me-1"></i>View Borrowed Tools
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Detection Library -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<script>
function borrowToolForm() {
    return {
        formData: {
            asset_id: '<?= htmlspecialchars($formData['asset_id'] ?? '') ?>',
            borrower_name: '<?= htmlspecialchars($formData['borrower_name'] ?? '') ?>',
            borrower_contact: '<?= htmlspecialchars($formData['borrower_contact'] ?? '') ?>',
            expected_return: '<?= htmlspecialchars($formData['expected_return'] ?? '') ?>',
            purpose: '<?= htmlspecialchars($formData['purpose'] ?? '') ?>',
            condition_out: '<?= htmlspecialchars($formData['condition_out'] ?? '') ?>'
        },
        
        // Search functionality
        searchText: '',
        showDropdown: false,
        selectedIndex: -1,
        selectedAsset: null,
        availableAssets: <?= json_encode($availableAssets ?? []) ?>,
        filteredAssets: [],
        
        // QR Scanner functionality
        scannerModal: null,
        scannerActive: false,
        scannerStream: null,
        scannerVideo: null,
        scannerCanvas: null,
        scannerContext: null,
        manualAssetRef: '',
        availableCameras: [],
        selectedCameraId: null,
        scanFrameCount: 0,
        lastScanTime: 0,
        scannerStatus: {
            message: '',
            type: '',
            icon: ''
        },
        
        init() {
            // Initialize filtered assets with all available assets
            this.filteredAssets = this.availableAssets;
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            const returnDateInput = document.getElementById('expected_return');
            returnDateInput.min = today;
            
            // Set default return date to 7 days from now if not set
            if (!this.formData.expected_return) {
                const nextWeek = new Date();
                nextWeek.setDate(nextWeek.getDate() + 7);
                this.formData.expected_return = nextWeek.toISOString().split('T')[0];
                this.calculateBorrowingPeriod();
            }
            
            // Calculate borrowing period when date changes
            this.$watch('formData.expected_return', () => {
                this.calculateBorrowingPeriod();
            });
            
            // Pre-select asset if form data has asset_id
            if (this.formData.asset_id) {
                const asset = this.availableAssets.find(a => a.id == this.formData.asset_id);
                if (asset) {
                    this.selectAsset(asset);
                }
            }
            
            // Initialize Bootstrap modal
            try {
                console.log('🔧 Initializing Bootstrap modal...');
                console.log('🔧 Bootstrap available:', typeof bootstrap !== 'undefined');
                console.log('🔧 Modal element exists:', !!document.getElementById('qrScannerModal'));
                
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalElement = document.getElementById('qrScannerModal');
                    if (modalElement) {
                        this.scannerModal = new bootstrap.Modal(modalElement, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        console.log('✅ Bootstrap modal created successfully');
                    } else {
                        console.error('❌ Modal element qrScannerModal not found');
                    }
                    
                    // Add event listeners for modal events
                    document.getElementById('qrScannerModal').addEventListener('shown.bs.modal', () => {
                        this.initializeScanner();
                    });
                    
                    document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', () => {
                        this.cleanupScanner();
                    });
                    
                    // Prevent modal from interfering with header dropdowns
                    document.getElementById('qrScannerModal').addEventListener('show.bs.modal', (e) => {
                        // Close any open dropdowns in header
                        const dropdowns = document.querySelectorAll('.dropdown-toggle');
                        dropdowns.forEach(dropdown => {
                            const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown);
                            if (dropdownInstance) {
                                dropdownInstance.hide();
                            }
                        });
                        e.stopPropagation();
                    });
                    
                    console.log('Bootstrap modal initialized successfully');
                } else {
                    console.warn('Bootstrap not available, QR scanner will use fallback mode');
                }
            } catch (error) {
                console.error('Error initializing Bootstrap modal:', error);
            }
        },
        
        filterAssets() {
            const search = this.searchText.toLowerCase();
            this.filteredAssets = this.availableAssets.filter(asset => {
                return asset.ref.toLowerCase().includes(search) ||
                       asset.name.toLowerCase().includes(search) ||
                       (asset.category_name && asset.category_name.toLowerCase().includes(search)) ||
                       (asset.model && asset.model.toLowerCase().includes(search)) ||
                       (asset.serial_number && asset.serial_number.toLowerCase().includes(search));
            });
            this.selectedIndex = -1;
            this.showDropdown = true;
        },
        
        selectAsset(asset) {
            this.selectedAsset = asset;
            this.formData.asset_id = asset.id;
            this.searchText = `${asset.ref} - ${asset.name}`;
            this.showDropdown = false;
            this.selectedIndex = -1;
        },
        
        selectNext() {
            if (this.filteredAssets.length === 0) return;
            this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredAssets.length - 1);
            this.showDropdown = true;
        },
        
        selectPrevious() {
            if (this.filteredAssets.length === 0) return;
            this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
            this.showDropdown = true;
        },
        
        selectCurrent() {
            if (this.selectedIndex >= 0 && this.selectedIndex < this.filteredAssets.length) {
                this.selectAsset(this.filteredAssets[this.selectedIndex]);
            }
        },
        
        calculateBorrowingPeriod() {
            const periodDisplay = document.getElementById('borrowingPeriod');
            
            if (this.formData.expected_return) {
                const today = new Date();
                const returnDate = new Date(this.formData.expected_return);
                const diffTime = returnDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 0) {
                    periodDisplay.innerHTML = `<span class="text-success"><i class="bi bi-calendar-check me-1"></i>${diffDays} day(s)</span>`;
                } else if (diffDays === 0) {
                    periodDisplay.innerHTML = `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Same day return</span>`;
                } else {
                    periodDisplay.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Past date selected</span>`;
                }
            } else {
                periodDisplay.innerHTML = `<span class="text-muted">Select return date to see period</span>`;
            }
        },
        
        // QR Scanner Methods
        toggleQRScanner() {
            console.log('🚀 QR Scanner button clicked');
            console.log('🔧 Modal instance available:', !!this.scannerModal);
            console.log('🔧 Bootstrap available:', typeof bootstrap !== 'undefined');
            console.log('🔧 Scanner active:', this.scannerActive);
            
            // If scanner is already active, just show modal
            if (this.scannerActive && this.scannerModal) {
                console.log('📱 Scanner already active, showing modal');
                this.scannerModal.show();
                return;
            }
            
            if (this.scannerModal) {
                console.log('✅ Opening modal via Bootstrap');
                this.scannerModal.show();
            } else {
                console.log('⚠️ Bootstrap modal not available, trying fallback');
                // Fallback: show modal manually
                const modal = document.getElementById('qrScannerModal');
                if (modal) {
                    console.log('✅ Showing modal manually');
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    // Add backdrop manually
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.id = 'manual-backdrop';
                    document.body.appendChild(backdrop);
                    this.initializeScanner();
                } else {
                    console.error('❌ Modal element not found!');
                }
            }
        },
        
        initializeScanner() {
            console.log('🔧 Initializing QR scanner');
            this.manualAssetRef = '';
            this.setScannerStatus('Ready to scan QR codes', 'alert-info', 'bi-qr-code-scan');
            
            // Initialize scanner elements
            this.scannerVideo = document.getElementById('qr-video');
            this.scannerCanvas = document.getElementById('qr-canvas');
            
            console.log('🔧 Scanner elements found:', {
                video: !!this.scannerVideo,
                canvas: !!this.scannerCanvas,
                videoId: this.scannerVideo?.id,
                canvasId: this.scannerCanvas?.id
            });
            
            if (!this.scannerVideo || !this.scannerCanvas) {
                console.error('❌ Scanner video or canvas elements not found');
                this.setScannerStatus('Scanner elements not found', 'alert-danger', 'bi-exclamation-triangle');
                return;
            }
            
            this.scannerContext = this.scannerCanvas.getContext('2d');
            console.log('🔧 Canvas context created:', !!this.scannerContext);
            
            // Don't automatically start camera - wait for user permission
            console.log('✅ Scanner initialized, waiting for user to request camera access');
        },
        
        async requestCameraPermission() {
            console.log('🎥 User requested camera access');
            console.log('📱 Available cameras before request:', this.availableCameras);
            console.log('🔧 Scanner elements check:', {
                video: !!this.scannerVideo,
                canvas: !!this.scannerCanvas,
                context: !!this.scannerContext
            });
            this.setScannerStatus('Requesting camera access...', 'alert-info', 'bi-info-circle');
            
            // Check if camera is supported
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.error('Camera not supported');
                this.setScannerStatus('Camera not supported in this browser', 'alert-danger', 'bi-exclamation-triangle');
                return;
            }
            
            // Check HTTPS requirement (more detailed)
            const isSecure = location.protocol === 'https:' || 
                           location.hostname === 'localhost' || 
                           location.hostname === '127.0.0.1' ||
                           location.hostname.endsWith('.local');
                           
            if (!isSecure) {
                const currentUrl = location.href;
                const httpsUrl = currentUrl.replace('http://', 'https://');
                this.setScannerStatus(`Camera requires HTTPS. Try: ${httpsUrl}`, 'alert-danger', 'bi-shield-exclamation');
                return;
            }
            
            try {
                // Get available cameras first
                await this.getAvailableCameras();
                
                // Start camera
                await this.startCamera();
                
            } catch (error) {
                console.error('Camera permission error:', error);
                this.handleCameraPermissionError(error);
            }
        },
        
        handleCameraPermissionError(error) {
            let errorMessage = 'Camera access failed';
            let suggestions = [];
            
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Camera permission denied';
                suggestions = [
                    'Click the camera icon in your browser\'s address bar',
                    'Select "Allow" for camera access',
                    'Refresh the page and try again'
                ];
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No camera found';
                suggestions = [
                    'Make sure your device has a camera',
                    'Check if another app is using the camera',
                    'Try refreshing the page'
                ];
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'Camera is busy';
                suggestions = [
                    'Close other apps that might be using the camera',
                    'Restart your browser',
                    'Try a different camera if available'
                ];
            } else if (error.name === 'OverconstrainedError') {
                errorMessage = 'Camera constraints not supported';
                suggestions = [
                    'Your camera doesn\'t support the required settings',
                    'Try using the manual input option below'
                ];
            }
            
            // Show error with helpful suggestions
            this.setScannerStatus(errorMessage, 'alert-danger', 'bi-exclamation-triangle');
            
            // Log detailed error for debugging
            console.error('Detailed camera error:', {
                name: error.name,
                message: error.message,
                suggestions: suggestions
            });
        },
        
        cleanupScanner() {
            this.stopCamera();
            this.scannerActive = false;
            this.manualAssetRef = '';
            this.availableCameras = [];
            this.selectedCameraId = null;
            this.setScannerStatus('', '', '');
        },
        
        async getAvailableCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.availableCameras = devices.filter(device => device.kind === 'videoinput');
                console.log('Available cameras:', this.availableCameras);
                
                // Default to back camera if available
                const backCamera = this.availableCameras.find(camera => 
                    camera.label.toLowerCase().includes('back') || 
                    camera.label.toLowerCase().includes('rear') ||
                    camera.label.toLowerCase().includes('environment')
                );
                
                this.selectedCameraId = backCamera ? backCamera.deviceId : 
                    (this.availableCameras.length > 0 ? this.availableCameras[0].deviceId : null);
                    
            } catch (error) {
                console.error('Error getting available cameras:', error);
            }
        },
        
        switchCamera() {
            if (this.availableCameras.length > 1) {
                // Restart camera with new selection
                this.stopCamera();
                setTimeout(() => {
                    this.startCamera();
                }, 100);
            }
        },
        
        async startCamera() {
            console.log('Starting camera with selected device...');
            
            try {
                const constraints = {
                    video: { 
                        width: { ideal: 400 },
                        height: { ideal: 300 }
                    }
                };
                
                // Use selected camera if available
                if (this.selectedCameraId) {
                    constraints.video.deviceId = { exact: this.selectedCameraId };
                } else {
                    // Default to back camera for QR scanning
                    constraints.video.facingMode = 'environment';
                }
                
                console.log('Camera constraints:', constraints);
                this.scannerStream = await navigator.mediaDevices.getUserMedia(constraints);
                
                console.log('Camera access granted');
                this.scannerVideo.srcObject = this.scannerStream;
                this.scannerVideo.style.display = 'block';
                
                // Hide static overlay and show scanning indicators
                const overlay = document.getElementById('qr-overlay');
                const detectionFrame = document.getElementById('qr-detection-frame');
                
                if (overlay) {
                    overlay.style.display = 'none';
                }
                if (detectionFrame) {
                    detectionFrame.style.display = 'block';
                }
                
                await this.scannerVideo.play();
                
                // Wait a moment for video to fully initialize
                setTimeout(() => {
                    console.log('Video dimensions:', this.scannerVideo.videoWidth, 'x', this.scannerVideo.videoHeight);
                    console.log('Canvas element:', this.scannerCanvas);
                    console.log('jsQR available:', typeof jsQR !== 'undefined');
                    
                    // Reset scanning counters
                    this.scanFrameCount = 0;
                    this.lastScanTime = 0;
                    
                    this.scannerActive = true;
                    this.setScannerStatus('Scanner active - Position QR code in view', 'alert-success', 'bi-check-circle');
                    
                    // Start scanning for QR codes
                    this.scanForQR();
                }, 500);
                
            } catch (error) {
                console.error('Camera access error:', error);
                let errorMessage = 'Camera access failed';
                
                if (error.name === 'NotAllowedError') {
                    errorMessage = 'Camera permission denied. Please allow camera access and try again.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage = 'No camera found on this device.';
                } else if (error.name === 'NotReadableError') {
                    errorMessage = 'Camera is being used by another application.';
                } else if (error.name === 'OverconstrainedError') {
                    errorMessage = 'Camera constraints not supported.';
                }
                
                this.setScannerStatus(errorMessage, 'alert-danger', 'bi-exclamation-triangle');
            }
        },
        
        stopCamera() {
            if (this.scannerStream) {
                this.scannerStream.getTracks().forEach(track => track.stop());
                this.scannerStream = null;
            }
            
            if (this.scannerVideo) {
                this.scannerVideo.style.display = 'none';
                this.scannerVideo.srcObject = null;
            }
            
            // Show static overlay and hide scanning indicators
            const overlay = document.getElementById('qr-overlay');
            const detectionFrame = document.getElementById('qr-detection-frame');
            
            if (overlay) {
                overlay.style.display = 'block';
            }
            if (detectionFrame) {
                detectionFrame.style.display = 'none';
            }
        },
        
        scanForQR() {
            if (!this.scannerActive) {
                console.log('❌ Scanner not active, stopping scan loop');
                return;
            }
            
            // Check if jsQR is available
            if (typeof jsQR === 'undefined') {
                console.error('❌ jsQR library not loaded');
                this.setScannerStatus('QR detection library not loaded', 'alert-danger', 'bi-exclamation-triangle');
                return;
            }
            
            // Log every 120 frames to avoid spam
            if (this.scanFrameCount % 120 === 0 && this.scanFrameCount > 0) {
                console.log('🔍 QR Scanner running, frame:', this.scanFrameCount);
            }
            
            // Check if video is ready
            if (this.scannerVideo.readyState === this.scannerVideo.HAVE_ENOUGH_DATA) {
                this.scanFrameCount++;
                const currentTime = Date.now();
                
                // Update status every 60 frames (roughly every 2 seconds at 30fps)
                if (this.scanFrameCount % 60 === 0) {
                    const fps = this.lastScanTime > 0 ? Math.round(60000 / (currentTime - this.lastScanTime)) : 0;
                    console.log(`Scanning frame ${this.scanFrameCount}, FPS: ${fps}`);
                    this.setScannerStatus(`Scanning... (${this.scanFrameCount} frames processed)`, 'alert-success', 'bi-camera');
                    this.lastScanTime = currentTime;
                }
                
                // Set canvas size to match video (only if different)
                if (this.scannerCanvas.height !== this.scannerVideo.videoHeight || 
                    this.scannerCanvas.width !== this.scannerVideo.videoWidth) {
                    this.scannerCanvas.height = this.scannerVideo.videoHeight;
                    this.scannerCanvas.width = this.scannerVideo.videoWidth;
                    console.log('Canvas resized to:', this.scannerCanvas.width, 'x', this.scannerCanvas.height);
                }
                
                // Draw current video frame to canvas
                this.scannerContext.drawImage(this.scannerVideo, 0, 0, this.scannerCanvas.width, this.scannerCanvas.height);
                
                // Get image data for QR detection
                const imageData = this.scannerContext.getImageData(0, 0, this.scannerCanvas.width, this.scannerCanvas.height);
                
                try {
                    // Attempt QR code detection
                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: "dontInvert"
                    });
                    
                    if (code) {
                        console.log('🎯 QR Code detected!', code.data);
                        console.log('QR Code location:', code.location);
                        
                        // Stop scanning while processing
                        this.scannerActive = false;
                        this.handleQRDetected(code.data);
                        return;
                    }
                } catch (error) {
                    console.error('QR detection error:', error);
                    this.setScannerStatus('QR detection error: ' + error.message, 'alert-warning', 'bi-exclamation-triangle');
                }
            } else {
                // Log video readiness occasionally
                if (this.scanFrameCount % 30 === 0) {
                    console.log('Video not ready yet, readyState:', this.scannerVideo.readyState, 'waiting...');
                }
            }
            
            // Continue scanning
            requestAnimationFrame(() => this.scanForQR());
        },
        
        async handleQRDetected(qrData) {
            this.setScannerStatus('QR Code detected! Validating...', 'alert-info', 'bi-search');
            console.log('QR Data detected:', qrData);
            
            try {
                // Validate QR code with backend
                const url = '?route=api/borrowed-tools/validate-qr&data=' + encodeURIComponent(qrData);
                console.log('Making API request to:', url);
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                });
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const result = await response.json();
                console.log('API Response:', result);
                
                if (result.valid && result.asset) {
                    console.log('Valid QR code, looking for asset:', result.asset.ref);
                    console.log('Available assets:', this.availableAssets);
                    
                    // Find the asset in available assets
                    const asset = this.availableAssets.find(a => a.ref === result.asset.ref);
                    
                    if (asset) {
                        console.log('Asset found in available assets:', asset);
                        this.selectAsset(asset);
                        this.setScannerStatus('Asset found and selected!', 'alert-success', 'bi-check-circle');
                        
                        setTimeout(() => {
                            this.scannerModal.hide();
                        }, 1500);
                    } else {
                        console.log('Asset not found in available assets list');
                        this.setScannerStatus('Asset not available for borrowing', 'alert-warning', 'bi-exclamation-triangle');
                    }
                } else {
                    console.log('Invalid QR result:', result);
                    let errorMessage = result.message || 'Invalid QR code or asset not found';
                    
                    // Provide helpful error messages based on the type of failure
                    if (result.message && result.message.includes('Invalid QR code format')) {
                        errorMessage = 'Invalid QR format. Please scan a SecureLink™ QR code from an asset tag.';
                    } else if (result.message && result.message.includes('Asset not found')) {
                        errorMessage = 'QR code is valid but asset not found in system.';
                    }
                    
                    this.setScannerStatus(errorMessage, 'alert-danger', 'bi-x-circle');
                    
                    // Restart scanning after 3 seconds to allow for another attempt
                    setTimeout(() => {
                        if (this.scannerVideo && this.scannerVideo.srcObject) {
                            this.scannerActive = true;
                            this.setScannerStatus('Ready to scan - Try another QR code', 'alert-info', 'bi-qr-code-scan');
                        }
                    }, 3000);
                }
            } catch (error) {
                console.error('QR validation error:', error);
                this.setScannerStatus('Error validating QR code', 'alert-danger', 'bi-exclamation-triangle');
                
                // Restart scanning after 3 seconds
                setTimeout(() => {
                    if (this.scannerVideo && this.scannerVideo.srcObject) {
                        this.scannerActive = true;
                        this.setScannerStatus('Ready to scan - Try again', 'alert-info', 'bi-qr-code-scan');
                    }
                }, 3000);
            }
        },
        
        async searchAssetByRef() {
            if (!this.manualAssetRef.trim()) return;
            
            this.setScannerStatus('Searching for asset...', 'alert-info', 'bi-search');
            
            // Find asset by reference in available assets
            const asset = this.availableAssets.find(a => 
                a.ref.toLowerCase() === this.manualAssetRef.toLowerCase()
            );
            
            if (asset) {
                this.selectAsset(asset);
                this.setScannerStatus('Asset found and selected!', 'alert-success', 'bi-check-circle');
                
                setTimeout(() => {
                    this.scannerModal.hide();
                }, 1500);
            } else {
                this.setScannerStatus('Asset not found or not available for borrowing', 'alert-warning', 'bi-exclamation-triangle');
            }
        },
        
        setScannerStatus(message, type, icon) {
            this.scannerStatus = { message, type, icon };
        },
        
        // Debug function to test camera directly
        async testDirectCamera() {
            console.log('🧪 Testing camera directly...');
            console.log('🔍 navigator:', !!navigator);
            console.log('🔍 navigator.mediaDevices:', !!navigator.mediaDevices);
            console.log('🔍 getUserMedia:', !!navigator.mediaDevices?.getUserMedia);
            console.log('🔍 Location protocol:', location.protocol);
            console.log('🔍 Location hostname:', location.hostname);
            
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Camera API not available. This requires HTTPS or localhost.');
                }
                
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } 
                });
                console.log('✅ Camera access successful!', stream);
                
                // Stop the stream immediately
                stream.getTracks().forEach(track => track.stop());
                console.log('✅ Camera test completed');
                
                alert('Camera test successful! Check console for details.');
            } catch (error) {
                console.error('❌ Camera test failed:', error);
                alert('Camera test failed: ' + error.message);
            }
        },
        
        // Debug function to test QR API
        async testQRAPI() {
            console.log('🧪 Testing QR API...');
            try {
                const testData = 'test-qr-data';
                const url = '?route=api/borrowed-tools/validate-qr&data=' + encodeURIComponent(testData);
                console.log('🔗 Testing URL:', url);
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                });
                
                console.log('📡 Response status:', response.status);
                const result = await response.json();
                console.log('📦 Response data:', result);
                
                alert('QR API test completed! Check console for details.');
            } catch (error) {
                console.error('❌ QR API test failed:', error);
                alert('QR API test failed: ' + error.message);
            }
        },
        
        // Force start camera directly (bypass modal flow)
        async forceStartCamera() {
            console.log('⚡ Force starting camera...');
            try {
                // Initialize scanner elements if not done
                if (!this.scannerVideo || !this.scannerCanvas) {
                    this.scannerVideo = document.getElementById('qr-video');
                    this.scannerCanvas = document.getElementById('qr-canvas');
                    this.scannerContext = this.scannerCanvas.getContext('2d');
                }
                
                console.log('⚡ Elements ready, requesting camera...');
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } 
                });
                
                console.log('⚡ Camera stream obtained:', stream);
                this.scannerVideo.srcObject = stream;
                this.scannerVideo.style.display = 'block';
                
                await this.scannerVideo.play();
                this.scannerActive = true;
                this.scannerStream = stream;
                
                // Start scanning
                this.scanFrameCount = 0;
                this.lastScanTime = 0;
                this.scanForQR();
                
                console.log('⚡ Camera started successfully, scanning active');
                alert('Camera force-started! QR scanning is now active.');
                
            } catch (error) {
                console.error('⚡ Force camera start failed:', error);
                alert('Force camera start failed: ' + error.message);
            }
        },
        
        // Debug modal state
        debugModalState() {
            const modal = document.getElementById('qrScannerModal');
            const backdrop = document.querySelector('.modal-backdrop');
            
            console.log('🪟 Modal Debug Info:', {
                modalExists: !!modal,
                modalVisible: modal?.style.display,
                modalClasses: modal?.className,
                backdropExists: !!backdrop,
                modalInstance: !!this.scannerModal,
                scannerActive: this.scannerActive,
                videoElement: !!this.scannerVideo,
                canvasElement: !!this.scannerCanvas
            });
            
            // Log computed styles
            if (modal) {
                const styles = window.getComputedStyle(modal);
                console.log('🪟 Modal computed styles:', {
                    display: styles.display,
                    visibility: styles.visibility,
                    zIndex: styles.zIndex
                });
            }
            
            alert('Modal debug info logged to console!');
        }
    }
}

// Auto-suggest condition based on asset selection
document.addEventListener('DOMContentLoaded', function() {
    const assetSelect = document.getElementById('asset_id');
    const conditionField = document.getElementById('condition_out');
    
    assetSelect.addEventListener('change', function() {
        if (this.value && !conditionField.value) {
            const suggestions = [
                'Good working condition',
                'Minor wear, fully functional',
                'Excellent condition',
                'Fair condition, some wear visible'
            ];
            const suggestion = suggestions[Math.floor(Math.random() * suggestions.length)];
            conditionField.placeholder = suggestion;
        }
    });
});

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Borrow Tool - ConstructLink™';
$pageHeader = 'Borrow Tool';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Borrow Tool', 'url' => '?route=borrowed-tools/create']
];

// Page actions for header
$pageActions = '
    <div class="btn-group" role="group">
        <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Borrowed Tools
        </a>
        <a href="?route=borrowed-tools/create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>New Borrowing
        </a>
    </div>
';


// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
