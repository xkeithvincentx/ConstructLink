<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

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
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- QR Tag Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-qr-code text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">QR Generated</h6>
                        <h3 class="mb-0" id="qrGeneratedCount"><?= $tagStats['qr_generated'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-qr-code me-1"></i>Codes created
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets/tag-management&status=needs_printing" class="text-decoration-none small">
                    <i class="bi bi-filter me-1"></i>View Generated
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-printer text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Need Printing</h6>
                        <h3 class="mb-0" id="needsPrintingCount"><?= $tagStats['needs_printing'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-printer me-1"></i>Ready to print
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets/tag-management&status=needs_printing" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View Pending
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-hand-index text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Need Application</h6>
                        <h3 class="mb-0" id="needsApplicationCount"><?= $tagStats['needs_application'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-hand-index me-1"></i>Apply to items
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets/tag-management&status=needs_application" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View Printed
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid <?= ($tagStats['needs_verification'] ?? 0) > 0 ? 'var(--danger-color)' : 'var(--neutral-color)' ?>;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-shield-check <?= ($tagStats['needs_verification'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Need Verification</h6>
                        <h3 class="mb-0" id="needsVerificationCount"><?= $tagStats['needs_verification'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-shield-check me-1"></i>Await verification
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <?php if (($tagStats['needs_verification'] ?? 0) > 0): ?>
                    <a href="?route=assets/tag-management&status=needs_verification" class="text-decoration-none small">
                        <i class="bi bi-eye me-1"></i>Review Tags
                    </a>
                <?php else: ?>
                    <small class="text-muted">All verified</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Second Row of Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Fully Tagged</h6>
                        <h3 class="mb-0" id="fullyTaggedCount"><?= $tagStats['fully_tagged'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>Complete workflow
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <a href="?route=assets/tag-management&status=fully_tagged" class="text-decoration-none small">
                    <i class="bi bi-eye me-1"></i>View Completed
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-graph-up text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Progress</h6>
                        <h3 class="mb-0">
                            <?php
                            $totalAssets = $tagStats['qr_generated'] ?? 0;
                            $completed = $tagStats['fully_tagged'] ?? 0;
                            $percentage = $totalAssets > 0 ? round(($completed / $totalAssets) * 100, 1) : 0;
                            echo $percentage . '%';
                            ?>
                        </h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-up me-1"></i><?= $tagStats['fully_tagged'] ?? 0 ?> of <?= $tagStats['qr_generated'] ?? 0 ?> tagged
                </p>
            </div>
            <div class="card-footer bg-light border-top">
                <small class="text-muted">Tagging completion rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Project Assignment Warning -->
<?php if (in_array($user['role_name'] ?? '', ['Project Manager', 'Site Inventory Clerk', 'Warehouseman']) && empty($user['current_project_id'])): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>No Project Assigned:</strong> You are not currently assigned to a project. Please contact your administrator to assign you to a project to access item tag management.
</div>
<?php endif; ?>

<!-- Debug info (remove in production) -->
<?php if (isset($_GET['debug'])): ?>
<div class="alert alert-info">
    <strong>Debug Info:</strong><br>
    Status Filter: <?= htmlspecialchars($_GET['status'] ?? 'none') ?><br>
    Project ID Filter: <?= htmlspecialchars($_GET['project_id'] ?? 'none') ?><br>
    Search Filter: <?= htmlspecialchars($_GET['search'] ?? 'none') ?><br>
    Items Count: <?= count($assets ?? []) ?>
</div>
<?php endif; ?>

<!-- Filter and Actions Bar -->
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="" class="d-flex gap-2">
            <input type="hidden" name="route" value="assets/tag-management">
            <select name="status" class="form-select" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Tag Status</option>
                <option value="needs_qr" <?= ($_GET['status'] ?? '') === 'needs_qr' ? 'selected' : '' ?>>Needs QR Generation</option>
                <option value="needs_printing" <?= ($_GET['status'] ?? '') === 'needs_printing' ? 'selected' : '' ?>>Needs Printing</option>
                <option value="needs_application" <?= ($_GET['status'] ?? '') === 'needs_application' ? 'selected' : '' ?>>Needs Application</option>
                <option value="needs_verification" <?= ($_GET['status'] ?? '') === 'needs_verification' ? 'selected' : '' ?>>Needs Verification</option>
                <option value="fully_tagged" <?= ($_GET['status'] ?? '') === 'fully_tagged' ? 'selected' : '' ?>>Fully Tagged</option>
            </select>
            
            <?php if (!in_array($user['role_name'] ?? '', ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])): ?>
                <select name="project_id" class="form-select" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Projects</option>
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            <?php endif; ?>
            
            <input type="text" name="search" class="form-control" placeholder="Search items..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width: 200px;" 
                   onkeypress="if(event.key==='Enter'){this.form.submit();}">
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            
            <?php if (!empty($_GET['status']) || !empty($_GET['project_id']) || !empty($_GET['search'])): ?>
                <a href="?route=assets/tag-management" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Clear Filters
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="col-md-4 text-end">
        <div class="btn-group" role="group">
            <?php 
            $userRole = $user['role_name'] ?? '';
            $canPrint = in_array($userRole, ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']);
            $canApply = in_array($userRole, ['System Admin', 'Warehouseman', 'Site Inventory Clerk']); 
            $canVerify = in_array($userRole, ['System Admin', 'Site Inventory Clerk']);
            ?>
            
            <?php if ($canPrint): ?>
            <button type="button" class="btn btn-success" onclick="bulkPrintTags()" id="bulkPrintBtn" disabled>
                <i class="bi bi-printer me-1"></i>Print Selected Tags
            </button>
            <?php endif; ?>
            
            <?php if ($canApply): ?>
            <button type="button" class="btn btn-outline-info" onclick="markAsApplied()" id="markAppliedBtn" disabled>
                <i class="bi bi-check me-1"></i>Mark as Applied
            </button>
            <?php endif; ?>
            
            <?php if ($canVerify): ?>
            <button type="button" class="btn btn-outline-success" onclick="markAsVerified()" id="markVerifiedBtn" disabled>
                <i class="bi bi-check-circle me-1"></i>Mark as Verified
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Items Table -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-list me-2"></i>Items QR Tag Status
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="assetsTable">
                <thead>
                    <tr>
                        <th width="30">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Project</th>
                        <th>QR Status</th>
                        <th>Tag Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assets)): ?>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="asset-checkbox" value="<?= $asset['id'] ?>" 
                                           onchange="updateBulkButtons()">
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($asset['ref']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($asset['name']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($asset['qr_code'])): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-qr-code me-1"></i>Generated
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i>Missing
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Show only the current status in the workflow
                                    // Verified > Applied > Printed > Needs Print
                                    if (!empty($asset['qr_tag_verified'])):
                                    ?>
                                        <span class="badge bg-success" title="Verified: <?= $asset['qr_tag_verified'] ?>">
                                            <i class="bi bi-check-circle me-1"></i>Verified
                                        </span>
                                    <?php elseif (!empty($asset['qr_tag_applied'])): ?>
                                        <span class="badge bg-primary" title="Applied: <?= $asset['qr_tag_applied'] ?>">
                                            <i class="bi bi-hand-index me-1"></i>Applied
                                        </span>
                                        <small class="text-muted d-block mt-1">Needs Verification</small>
                                    <?php elseif (!empty($asset['qr_tag_printed'])): ?>
                                        <span class="badge bg-info" title="Printed: <?= $asset['qr_tag_printed'] ?>">
                                            <i class="bi bi-printer me-1"></i>Printed
                                        </span>
                                        <small class="text-muted d-block mt-1">Needs Application</small>
                                    <?php elseif (!empty($asset['qr_code'])): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-qr-code me-1"></i>Generated
                                        </span>
                                        <small class="text-muted d-block mt-1">Needs Printing</small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-dash-circle me-1"></i>Not Generated
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (empty($asset['qr_code'])): ?>
                                            <button type="button" class="btn btn-outline-primary"
                                                    onclick="generateQR(<?= $asset['id'] ?>)"
                                                    title="Generate QR Code">
                                                <i class="bi bi-qr-code"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (!empty($asset['qr_code'])): ?>
                                            <button type="button" class="btn btn-outline-success"
                                                    onclick="printSingleTag(<?= $asset['id'] ?>)"
                                                    title="Print Tag">
                                                <i class="bi bi-printer"></i>
                                            </button>

                                            <button type="button" class="btn btn-outline-info"
                                                    onclick="previewTag(<?= $asset['id'] ?>)"
                                                    title="Preview Tag">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (!empty($asset['qr_tag_printed']) && empty($asset['qr_tag_applied']) && $canApply): ?>
                                            <button type="button" class="btn btn-outline-warning"
                                                    onclick="markSingleAsApplied(<?= $asset['id'] ?>)"
                                                    title="Mark as Applied">
                                                <i class="bi bi-hand-index"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (!empty($asset['qr_tag_applied']) && empty($asset['qr_tag_verified']) && $canVerify): ?>
                                            <button type="button" class="btn btn-outline-primary"
                                                    onclick="markSingleAsVerified(<?= $asset['id'] ?>)"
                                                    title="Mark as Verified">
                                                <i class="bi bi-shield-check"></i>
                                            </button>
                                        <?php endif; ?>

                                        <a href="?route=assets/view&id=<?= $asset['id'] ?>"
                                           class="btn btn-outline-secondary" title="View Item">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No items found matching the current filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
            <nav aria-label="Items pagination" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                            <?php
                            // Build query string preserving all filters except page and route
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            unset($queryParams['route']);
                            $queryParams['page'] = $i;
                            $queryString = http_build_query($queryParams);
                            ?>
                            <a class="page-link" href="?route=assets/tag-management&<?= $queryString ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Tag Preview Modal -->
<div class="modal fade" id="tagPreviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Tag Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="tagPreviewContent">
                <!-- Tag preview will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printFromPreview()">Print This Tag</button>
            </div>
        </div>
    </div>
</div>

<script>
// CSRF Token for AJAX requests
const CSRFTokenValue = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?= CSRFProtection::generateToken() ?>';

// Select all functionality
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.asset-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkButtons();
}

// Update bulk action buttons
function updateBulkButtons() {
    const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
    const bulkPrintBtn = document.getElementById('bulkPrintBtn');
    const markAppliedBtn = document.getElementById('markAppliedBtn');
    const markVerifiedBtn = document.getElementById('markVerifiedBtn');
    
    if (bulkPrintBtn) bulkPrintBtn.disabled = checkedBoxes.length === 0;
    if (markAppliedBtn) markAppliedBtn.disabled = checkedBoxes.length === 0;
    if (markVerifiedBtn) markVerifiedBtn.disabled = checkedBoxes.length === 0;
}

// Generate QR code for item
function generateQR(assetId) {
    fetch(`?route=api/assets/generate-qr`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: `asset_id=${assetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to generate QR code: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating QR code');
    });
}

// Print single tag
function printSingleTag(assetId) {
    const printWindow = window.open(`?route=assets/print-tag&id=${assetId}`, '_blank');

    // Detect when print is complete and mark as printed
    if (printWindow) {
        // Poll for window load, then set up print detection
        const pollInterval = setInterval(() => {
            try {
                if (printWindow.document && printWindow.document.readyState === 'complete') {
                    clearInterval(pollInterval);

                    // Set up afterprint event on the print window
                    printWindow.onafterprint = function() {
                        markTagAsPrinted(assetId);
                    };

                    // Fallback: also detect when window closes (user either printed or cancelled)
                    const checkClosed = setInterval(() => {
                        if (printWindow.closed) {
                            clearInterval(checkClosed);
                            // Give a short delay to see if onafterprint fired
                            setTimeout(() => {
                                // Only mark if window was open for more than 2 seconds (user likely printed)
                                if (Date.now() - startTime > 2000) {
                                    markTagAsPrinted(assetId);
                                }
                            }, 500);
                        }
                    }, 500);

                    const startTime = Date.now();
                }
            } catch (e) {
                // Cross-origin or access denied - ignore
            }
        }, 100);
    }
}

// Preview tag
function previewTag(assetId) {
    fetch(`?route=assets/tag-preview&id=${assetId}`)
    .then(response => response.text())
    .then(html => {
        document.getElementById('tagPreviewContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('tagPreviewModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load tag preview');
    });
}

// Print from preview
function printFromPreview() {
    const assetId = document.querySelector('.asset-checkbox:checked')?.value;
    if (assetId) {
        printSingleTag(assetId);
    }
}

// Bulk print tags
function bulkPrintTags() {
    const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
    const assetIds = Array.from(checkedBoxes).map(cb => cb.value);

    if (assetIds.length === 0) {
        alert('Please select items to print tags for');
        return;
    }

    const params = new URLSearchParams();
    assetIds.forEach(id => params.append('ids[]', id));

    const printWindow = window.open(`?route=assets/print-tags&${params.toString()}`, '_blank');

    // Detect when print is complete and mark all as printed
    if (printWindow) {
        // Poll for window load, then set up print detection
        const pollInterval = setInterval(() => {
            try {
                if (printWindow.document && printWindow.document.readyState === 'complete') {
                    clearInterval(pollInterval);

                    // Set up afterprint event on the print window
                    printWindow.onafterprint = function() {
                        markBulkTagsAsPrinted(assetIds);
                    };

                    // Fallback: also detect when window closes (user either printed or cancelled)
                    const checkClosed = setInterval(() => {
                        if (printWindow.closed) {
                            clearInterval(checkClosed);
                            // Give a short delay to see if onafterprint fired
                            setTimeout(() => {
                                // Only mark if window was open for more than 2 seconds (user likely printed)
                                if (Date.now() - startTime > 2000) {
                                    markBulkTagsAsPrinted(assetIds);
                                }
                            }, 500);
                        }
                    }, 500);

                    const startTime = Date.now();
                }
            } catch (e) {
                // Cross-origin or access denied - ignore
            }
        }, 100);
    }
}

// Mark single tag as printed
function markTagAsPrinted(assetId) {
    fetch(`?route=api/assets/mark-tags-printed`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: `asset_ids=${assetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload to show updated status
            location.reload();
        } else {
            console.error('Failed to update tag status:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Mark bulk tags as printed
function markBulkTagsAsPrinted(assetIds) {
    fetch(`?route=api/assets/mark-tags-printed`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: `asset_ids=${assetIds.join(',')}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload to show updated status
            location.reload();
        } else {
            console.error('Failed to update tag status:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Mark single tag as applied
function markSingleAsApplied(assetId) {
    if (!confirm('Mark this QR tag as applied to the item?')) {
        return;
    }

    fetch(`?route=api/assets/mark-tags-applied`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: `asset_ids=${assetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to mark tag as applied: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating tag status');
    });
}

// Mark as applied (bulk)
function markAsApplied() {
    const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
    const assetIds = Array.from(checkedBoxes).map(cb => cb.value);

    if (assetIds.length === 0) {
        alert('Please select items to mark as applied');
        return;
    }

    if (!confirm(`Mark ${assetIds.length} item(s) as having QR tags applied?`)) {
        return;
    }

    fetch(`?route=api/assets/mark-tags-applied`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: `asset_ids=${assetIds.join(',')}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update tag status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating tag status');
    });
}

// Mark single tag as verified
function markSingleAsVerified(assetId) {
    if (!confirm('Mark this QR tag as verified?')) {
        return;
    }

    fetch(`?route=api/assets/verify-tags`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: `asset_ids=${assetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to verify tag: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while verifying tag');
    });
}

// Mark as verified (bulk)
function markAsVerified() {
    const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
    const assetIds = Array.from(checkedBoxes).map(cb => cb.value);

    if (assetIds.length === 0) {
        alert('Please select items to mark as verified');
        return;
    }

    if (!confirm(`Mark ${assetIds.length} item(s) as having QR tags verified?`)) {
        return;
    }

    fetch(`?route=api/assets/verify-tags`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: `asset_ids=${assetIds.join(',')}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to verify tags: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while verifying tags');
    });
}

// Auto-refresh stats every 30 seconds
setInterval(() => {
    fetch(`?route=api/assets/tag-stats`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('qrGeneratedCount').textContent = data.stats.qr_generated;
            document.getElementById('needsPrintingCount').textContent = data.stats.needs_printing;
            document.getElementById('needsApplicationCount').textContent = data.stats.needs_application;
            document.getElementById('needsVerificationCount').textContent = data.stats.needs_verification;
            document.getElementById('fullyTaggedCount').textContent = data.stats.fully_tagged;
        }
    })
    .catch(error => console.error('Stats refresh error:', error));
}, 30000);
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'QR Tag Management - ConstructLink™';
$pageHeader = 'QR Tag Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Inventory', 'url' => '?route=assets'],
    ['title' => 'QR Tag Management', 'url' => '?route=assets/tag-management']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>