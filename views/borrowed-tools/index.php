<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';

// Group borrowed tools by batch_id for batch display
$groupedTools = [];
$singleTools = [];

foreach ($borrowedTools as $tool) {
    if (!empty($tool['batch_id'])) {
        // This is a batch item - group by batch_id
        if (!isset($groupedTools[$tool['batch_id']])) {
            $groupedTools[$tool['batch_id']] = [];
        }
        $groupedTools[$tool['batch_id']][] = $tool;
    } else {
        // Single item (no batch_id)
        $singleTools[] = $tool;
    }
}

// Merge grouped batches and single items for display
// Only treat as "batch" if there are 2+ items with same batch_id
$displayItems = [];
foreach ($groupedTools as $batchId => $batchItems) {
    if (count($batchItems) > 1) {
        // True batch (multiple items)
        $displayItems[] = [
            'type' => 'batch',
            'batch_id' => $batchId,
            'items' => $batchItems,
            'primary' => $batchItems[0] // Use first item for main display
        ];
    } else {
        // Only 1 item with this batch_id - treat as single item
        $displayItems[] = [
            'type' => 'single',
            'item' => $batchItems[0]
        ];
    }
}
foreach ($singleTools as $tool) {
    $displayItems[] = [
        'type' => 'single',
        'item' => $tool
    ];
}

// Sort by ID descending
usort($displayItems, function($a, $b) {
    $idA = $a['type'] === 'batch' ? $a['primary']['id'] : $a['item']['id'];
    $idB = $b['type'] === 'batch' ? $b['primary']['id'] : $b['item']['id'];
    return $idB - $idA;
});
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="mb-4">
    <!-- Mobile: Full width buttons stacked -->
    <div class="d-lg-none d-grid gap-2">
        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
            <a href="?route=borrowed-tools/create-batch" class="btn btn-primary">
                <i class="bi bi-cart-plus me-1"></i>Borrow Equipment
            </a>
        <?php endif; ?>
        <a href="?route=borrowed-tools/print-blank-form" class="btn btn-outline-primary" target="_blank" title="Print blank forms">
            <i class="bi bi-printer me-1"></i>Print Blank Form
        </a>
    </div>

    <!-- Desktop: Horizontal layout with left/right split -->
    <div class="d-none d-lg-flex justify-content-between align-items-center">
        <div class="btn-toolbar gap-2">
            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
                <a href="?route=borrowed-tools/create-batch" class="btn btn-primary btn-sm">
                    <i class="bi bi-cart-plus me-1"></i>Borrow Equipment
                </a>
            <?php endif; ?>
            <a href="?route=borrowed-tools/print-blank-form" class="btn btn-outline-primary btn-sm" target="_blank" title="Print blank forms">
                <i class="bi bi-printer me-1"></i>Print Blank Form
            </a>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshBorrowedTools()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<?php include APP_ROOT . '/views/borrowed-tools/partials/_statistics_cards.php'; ?>

<!-- MVA Workflow Info Banner -->
<div class="alert alert-info mb-4">
    <strong><i class="bi bi-info-circle me-2"></i>MVA Workflow:</strong>
    <span class="badge bg-primary">Maker</span> (Warehouseman) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-info">Authorizer</span> (Asset/Finance Director) →
    <span class="badge bg-success">Approved</span> →
    <span class="badge bg-primary">Borrowed</span> →
    <span class="badge bg-secondary">Returned</span>
</div>

<!-- Filters -->
<?php include APP_ROOT . '/views/borrowed-tools/partials/_filters.php'; ?>

<!-- Borrowed Tools Table -->
<?php include APP_ROOT . '/views/borrowed-tools/partials/_borrowed_tools_list.php'; ?>

<!-- Overdue Tools Alert -->
<?php if (!empty($overdueTools)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Overdue Tools Alert
                </h6>
            </div>
            <div class="card-body">
                <p class="text-danger mb-3">
                    <strong><?= count($overdueTools) ?> tool(s)</strong> are overdue for return. Please follow up with borrowers.
                </p>
                <div class="row">
                    <?php foreach (array_slice($overdueTools, 0, 6) as $overdueTool): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <div>
                                    <strong><?= htmlspecialchars($overdueTool['asset_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Borrowed by: <?= htmlspecialchars($overdueTool['borrower_name']) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger">
                                        <?= $overdueTool['days_overdue'] ?> days overdue
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($overdueTools) > 6): ?>
                    <p class="text-muted mt-2">And <?= count($overdueTools) - 6 ?> more overdue tools...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Enhanced Borrowed Tools Index Styles */

/* Sortable columns */
th.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
    transition: background-color 0.2s;
}

th.sortable:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

th.sortable i {
    font-size: 0.75rem;
    margin-left: 0.25rem;
}

/* Fix hidden batch items row spacing */
tr.batch-items-row[style*="display: none"] {
    display: none !important;
    height: 0 !important;
    line-height: 0 !important;
}

tr.batch-items-row[style*="display: none"] td {
    padding: 0 !important;
    border: 0 !important;
}

/* Fix action buttons spacing */
.action-buttons {
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1 !important;
}

.action-buttons .d-flex {
    margin: 0 !important;
}

.workflow-step {
    text-align: center;
    min-width: 100px;
}

.workflow-steps .badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.status-cell .progress {
    width: 60px;
    margin-top: 2px;
}

.purpose-cell {
    max-width: 200px;
}

.mva-workflow .badge-sm {
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
    width: 20px;
    text-align: center;
}

.return-schedule {
    min-width: 120px;
}

.action-buttons .btn-group {
    white-space: nowrap;
}

.table-danger {
    --bs-table-bg: rgba(220, 53, 69, 0.1);
    border-left: 4px solid #dc3545;
}

.table-warning {
    --bs-table-bg: rgba(255, 193, 7, 0.1);
    border-left: 4px solid #ffc107;
}

.text-truncated-hover:hover {
    overflow: visible;
    white-space: normal;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem;
    border-radius: 0.25rem;
    position: relative;
    z-index: 1000;
}

.card-body .workflow-steps {
    justify-content: center;
}

@media (max-width: 768px) {
    .workflow-steps {
        flex-direction: column;
        gap: 1rem !important;
    }
    
    .workflow-steps .bi-arrow-right {
        transform: rotate(90deg);
    }
    
    .col-lg-2 {
        margin-bottom: 1rem;
    }
}

/* Priority indicators */
.priority-high {
    border-left: 4px solid #dc3545;
}

.priority-medium {
    border-left: 4px solid #ffc107;
}

.priority-low {
    border-left: 4px solid #198754;
}

/* Enhanced badge styles */
.badge.position-relative .badge {
    font-size: 0.55rem;
}

/* Smooth transitions */
.card, .btn, .badge {
    transition: all 0.2s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Print styles */
@media print {
    .btn, .dropdown, .alert, .card-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
}
</style>

<script>
// Batch expand/collapse functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle batch action modals - load batch data when modal opens
    document.querySelectorAll('.batch-action-btn').forEach(button => {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-batch-id');
            const modalId = this.getAttribute('data-bs-target').substring(1);

            // Store batch ID in modal for form submission
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.setAttribute('data-batch-id', batchId);

                // Load batch items into modal
                loadBatchItemsIntoModal(batchId, modalId);
            }
        });
    });
});

// Load batch items into modal
function loadBatchItemsIntoModal(batchId, modalId) {
    // Find the batch items from the expandable row
    const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);
    if (!batchItemsRow) return;

    const modal = document.getElementById(modalId);
    const itemsContainer = modal.querySelector('.batch-modal-items');

    if (itemsContainer) {
        // Clone the batch items table
        const batchTable = batchItemsRow.querySelector('table').cloneNode(true);
        itemsContainer.innerHTML = '';
        itemsContainer.appendChild(batchTable);
    }

    // Set batch ID in hidden input if exists
    const batchIdInput = modal.querySelector('input[name="batch_id"]');
    if (batchIdInput) {
        batchIdInput.value = batchId;
    }
}

// Mark tool as overdue
function markOverdue(borrowId) {
    if (confirm('Mark this tool as overdue? This will update the status and may trigger notifications.')) {
        fetch('?route=borrowed-tools/markOverdue', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ borrow_id: borrowId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to mark as overdue: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking tool as overdue');
        });
    }
}

// Refresh borrowed tools
function refreshBorrowedTools() {
    window.location.reload();
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=borrowed-tools/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=borrowed-tools"]');
    const filterInputs = filterForm.querySelectorAll('select, input[name="date_from"], input[name="date_to"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Search with debounce
    let searchTimeout;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
});

// Quick filter function
function quickFilter(status) {
    // Navigate to borrowed-tools with status filter
    if (status === 'overdue') {
        window.location.href = '?route=borrowed-tools&priority=overdue';
    } else {
        window.location.href = '?route=borrowed-tools&status=' + encodeURIComponent(status);
    }
}

// Sortable table columns
document.addEventListener('DOMContentLoaded', function() {
    const sortableHeaders = document.querySelectorAll('th.sortable');

    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.style.userSelect = 'none';

        header.addEventListener('click', function() {
            const sortColumn = this.getAttribute('data-sort');
            const currentSort = '<?= $currentSort ?? '' ?>';
            const currentOrder = '<?= $currentOrder ?? 'desc' ?>';

            // Determine new sort order
            let newOrder = 'asc';
            if (sortColumn === currentSort) {
                // Toggle order if clicking same column
                newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            }

            // Build URL with current filters and new sort
            const params = new URLSearchParams(window.location.search);
            params.set('sort', sortColumn);
            params.set('order', newOrder);

            // Navigate with new sort
            window.location.href = '?' + params.toString();
        });
    });
});

function filterByPriority(priority) {
    // Handle both desktop and mobile priority selects
    const desktopPriority = document.getElementById('priority');
    const mobilePriority = document.getElementById('priority-mobile');

    if (desktopPriority) {
        desktopPriority.value = priority;
        desktopPriority.closest('form').submit();
    } else if (mobilePriority) {
        mobilePriority.value = priority;
        mobilePriority.closest('form').submit();
    }
}

// Send overdue reminder function
function sendOverdueReminder(borrowId) {
    if (confirm('Send overdue reminder to borrower?')) {
        fetch('?route=borrowed-tools/sendReminder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ borrow_id: borrowId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully!');
            } else {
                alert('Failed to send reminder: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending reminder');
        });
    }
}

// Enhanced table interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add tooltips to truncated text
    const truncatedElements = document.querySelectorAll('.text-truncate');
    truncatedElements.forEach(element => {
        if (element.scrollWidth > element.clientWidth) {
            element.classList.add('text-truncated-hover');
        }
    });
    
    // Highlight overdue rows
    const overdueRows = document.querySelectorAll('.table-danger');
    overdueRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 0 10px rgba(220, 53, 69, 0.3)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });
    });
    
    // Auto-focus on search when using Ctrl+F
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
});

// Auto-refresh for overdue tools with visual indicator
if (document.querySelector('.table-danger') || document.querySelector('.bg-danger')) {
    let refreshTimer = 300; // 5 minutes for overdue items
    
    // Show refresh countdown
    const createRefreshIndicator = () => {
        const indicator = document.createElement('div');
        indicator.id = 'refresh-indicator';
        indicator.className = 'position-fixed bottom-0 end-0 m-3 alert alert-info alert-dismissible';
        indicator.innerHTML = `
            <small>
                <i class="bi bi-arrow-clockwise me-1"></i>
                Auto-refresh in <span id="refresh-countdown">${refreshTimer}</span>s
                <button type="button" class="btn-close btn-close-sm" onclick="clearAutoRefresh()"></button>
            </small>
        `;
        document.body.appendChild(indicator);
        
        const countdown = setInterval(() => {
            refreshTimer--;
            const countdownEl = document.getElementById('refresh-countdown');
            if (countdownEl) countdownEl.textContent = refreshTimer;
            
            if (refreshTimer <= 0) {
                clearInterval(countdown);
                location.reload();
            }
        }, 1000);
        
        window.autoRefreshInterval = countdown;
    };
    
    // Start auto-refresh after 30 seconds
    setTimeout(createRefreshIndicator, 30000);
}

function clearAutoRefresh() {
    if (window.autoRefreshInterval) {
        clearInterval(window.autoRefreshInterval);
        const indicator = document.getElementById('refresh-indicator');
        if (indicator) indicator.remove();
    }
}
</script>

<!-- Batch Verification Modal -->
<div class="modal fade" id="batchVerifyModal" tabindex="-1" aria-labelledby="batchVerifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="batchVerifyModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Verify Batch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="index.php?route=borrowed-tools/batch/verify">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                    <input type="hidden" name="batch_id" value="">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Review all items in this batch and confirm they match the physical equipment on-site.
                    </div>

                    <!-- Batch Items Table -->
                    <div class="batch-modal-items mb-3">
                        <!-- Items will be loaded here via JavaScript -->
                    </div>

                    <!-- Verification Notes -->
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Verification Notes</label>
                        <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3" placeholder="Optional notes about the verification"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Verify Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Batch Authorization Modal -->
<div class="modal fade" id="batchAuthorizeModal" tabindex="-1" aria-labelledby="batchAuthorizeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="batchAuthorizeModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Authorize Batch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="index.php?route=borrowed-tools/batch/approve">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                    <input type="hidden" name="batch_id" value="">

                    <div class="alert alert-success">
                        <i class="bi bi-info-circle me-2"></i>
                        Review all items and authorize this batch for release.
                    </div>

                    <!-- Batch Items Table -->
                    <div class="batch-modal-items mb-3">
                        <!-- Items will be loaded here via JavaScript -->
                    </div>

                    <!-- Authorization Notes -->
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Authorization Notes</label>
                        <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" placeholder="Optional notes about the authorization"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-shield-check me-1"></i>Authorize Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Batch Release Modal -->
<div class="modal fade" id="batchReleaseModal" tabindex="-1" aria-labelledby="batchReleaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="batchReleaseModalLabel">
                    <i class="bi bi-box-arrow-up me-2"></i>Release Batch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="index.php?route=borrowed-tools/batch/release">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                    <input type="hidden" name="batch_id" value="">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Confirm that all items in this batch are being released to the borrower.
                    </div>

                    <!-- Batch Items Table -->
                    <div class="batch-modal-items mb-3">
                        <!-- Items will be loaded here via JavaScript -->
                    </div>

                    <!-- Release Notes -->
                    <div class="mb-3">
                        <label for="release_notes" class="form-label">Release Notes</label>
                        <textarea class="form-control" id="release_notes" name="release_notes" rows="3" placeholder="Optional notes about the release"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-box-arrow-up me-1"></i>Release Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Batch Return Modal -->
<div class="modal fade" id="batchReturnModal" tabindex="-1" aria-labelledby="batchReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="batchReturnModalLabel">
                    <i class="bi bi-box-arrow-down me-2"></i>Return Equipment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="batchReturnForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="" id="returnCsrfToken">
                    <input type="hidden" name="batch_id" value="" id="returnBatchId">

                    <div class="alert alert-success">
                        <i class="bi bi-info-circle me-2"></i>
                        Enter the quantity returned for each item. Check the condition of each item.
                    </div>

                    <!-- Batch Items Table with Qty In -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="batchReturnTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 23%">Equipment</th>
                                    <th style="width: 12%">Reference</th>
                                    <th style="width: 7%" class="text-center">Borrowed</th>
                                    <th style="width: 7%" class="text-center">Returned</th>
                                    <th style="width: 7%" class="text-center">Remaining</th>
                                    <th style="width: 9%" class="text-center">Return Now</th>
                                    <th style="width: 12%">Condition</th>
                                    <th style="width: 12%">Notes</th>
                                    <th style="width: 6%" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="batchReturnItems">
                                <!-- Items will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Return Notes -->
                    <div class="mb-3">
                        <label for="return_notes" class="form-label">Overall Return Notes</label>
                        <textarea class="form-control" id="return_notes" name="return_notes" rows="3" placeholder="Optional notes about the return"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="processReturnBtn">
                        <i class="bi bi-box-arrow-down me-1"></i>Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Batch Extend Modal -->
<div class="modal fade" id="batchExtendModal" tabindex="-1" aria-labelledby="batchExtendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="batchExtendModalLabel">
                    <i class="bi bi-calendar-plus me-2"></i>Extend Batch Return Date
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="batchExtendForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="" id="extendCsrfToken">
                    <input type="hidden" name="batch_id" value="" id="extendBatchId">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Select which items in this batch to extend. You can extend all items or only specific items that are still borrowed.
                    </div>

                    <!-- Batch Items Table with Selection -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="batchExtendTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width: 5%">
                                        <input type="checkbox" class="form-check-input" id="selectAllExtend" title="Select All">
                                    </th>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 30%">Equipment</th>
                                    <th style="width: 15%">Reference</th>
                                    <th style="width: 10%" class="text-center">Borrowed</th>
                                    <th style="width: 10%" class="text-center">Remaining</th>
                                    <th style="width: 15%">Current Return Date</th>
                                    <th style="width: 10%">Status</th>
                                </tr>
                            </thead>
                            <tbody id="batchExtendItems">
                                <!-- Items will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- New Return Date -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_expected_return" class="form-label">New Expected Return Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="new_expected_return" name="new_expected_return" required>
                                <small class="text-muted">All selected items will be extended to this date</small>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-3">
                        <label for="extend_reason" class="form-label">Reason for Extension <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="extend_reason" name="reason" rows="3" placeholder="Provide reason for extending the return date" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info" id="processExtendBtn">
                        <i class="bi bi-calendar-plus me-1"></i>Extend Selected Items
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Incident Report Modal -->
<div class="modal fade" id="quickIncidentModal" tabindex="-1" aria-labelledby="quickIncidentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="quickIncidentModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Report Incident
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickIncidentForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="" id="incidentCsrfToken">
                    <input type="hidden" name="asset_id" value="" id="incidentAssetId">
                    <input type="hidden" name="borrowed_tool_id" value="" id="incidentBorrowedToolId">

                    <div class="alert alert-info">
                        <strong id="incidentEquipmentName"></strong><br>
                        <small>Reference: <code id="incidentAssetRef"></code></small>
                    </div>

                    <div class="mb-3">
                        <label for="incident_type" class="form-label">Incident Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="incident_type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="lost">Lost</option>
                            <option value="damaged">Damaged</option>
                            <option value="stolen">Stolen</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="incident_severity" class="form-label">Severity</label>
                        <select class="form-select" id="incident_severity" name="severity">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="incident_description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="incident_description" name="description" rows="4" required
                                  placeholder="Describe what happened, when it occurred, and any relevant details..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="incident_location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="incident_location" name="location"
                               placeholder="Where did this incident occur?">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="submitIncidentBtn">
                        <i class="bi bi-exclamation-triangle me-1"></i>Report Incident
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store CSRF token once at page load for return modal
const returnBatchCsrfToken = '<?= CSRFProtection::generateToken() ?>';
const extendBatchCsrfToken = '<?= CSRFProtection::generateToken() ?>';
const incidentCsrfToken = '<?= CSRFProtection::generateToken() ?>';

// Enhanced load function for batch return modal with Qty In inputs
document.getElementById('batchReturnModal').addEventListener('shown.bs.modal', function() {
    const batchId = this.getAttribute('data-batch-id');
    const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);

    if (!batchItemsRow) {
        console.error('Batch items row not found for batch ID:', batchId);
        return;
    }

    // Set batch ID and CSRF token in hidden fields
    const batchIdInput = document.getElementById('returnBatchId');
    if (batchIdInput) {
        batchIdInput.value = batchId;
    }

    const csrfInput = document.getElementById('returnCsrfToken');
    if (csrfInput) {
        csrfInput.value = returnBatchCsrfToken;
    }

    const items = batchItemsRow.querySelectorAll('tbody tr');
    const returnTableBody = document.getElementById('batchReturnItems');

    if (!returnTableBody) {
        console.error('Return table body not found');
        return;
    }

    returnTableBody.innerHTML = '';

    if (items.length === 0) {
        returnTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No items found in batch</td></tr>';
        return;
    }

    items.forEach((item, index) => {
        const cells = item.querySelectorAll('td');
        if (cells.length < 10) {
            console.warn('Invalid row structure, skipping item', index);
            return;
        }

        // Get borrowed_tool ID and asset ID from data attributes
        const borrowedToolId = item.getAttribute('data-item-id') || item.dataset.id || '';
        const assetId = item.getAttribute('data-asset-id') || item.dataset.assetId || '';

        const itemNumber = cells[0].textContent.trim();
        const equipmentCell = cells[1];
        const equipmentName = equipmentCell.querySelector('strong') ? equipmentCell.querySelector('strong').textContent : equipmentCell.textContent;
        const equipmentCategory = equipmentCell.querySelector('small') ? equipmentCell.querySelector('small').textContent : '';
        const reference = cells[2].textContent.trim();
        const borrowed = parseInt(cells[3].textContent.trim()) || 1;
        const returned = parseInt(cells[4].textContent.trim()) || 0;
        const remaining = borrowed - returned;
        const returnedCondition = cells[6].textContent.trim();
        const returnedNotes = cells[9].textContent.trim();

        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-center">${index + 1}</td>
            <td>
                <strong>${equipmentName}</strong>
                ${equipmentCategory ? `<br><small class="text-muted">${equipmentCategory}</small>` : ''}
                ${remaining === 0 ? '<br><small class="text-success"><i class="bi bi-check-circle-fill"></i> Fully Returned</small>' : ''}
            </td>
            <td><code>${reference}</code></td>
            <td class="text-center"><span class="badge bg-primary">${borrowed}</span></td>
            <td class="text-center"><span class="badge bg-success">${returned}</span></td>
            <td class="text-center"><span class="badge bg-${remaining > 0 ? 'warning' : 'secondary'}">${remaining}</span></td>
            <td class="text-center">
                ${remaining > 0 ? `
                <input type="number"
                       class="form-control form-control-sm qty-in-input"
                       name="qty_in[]"
                       min="0"
                       max="${remaining}"
                       value="${remaining}"
                       style="width: 70px; display: inline-block;">
                <input type="hidden" name="item_id[]" value="${borrowedToolId}">
                ` : '<span class="text-muted">-</span>'}
            </td>
            <td>
                ${remaining > 0 ? `
                <select class="form-select form-select-sm condition-select" name="condition[]">
                    <option value="Good" selected>Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                    <option value="Damaged">Damaged</option>
                    <option value="Lost">Lost</option>
                </select>
                ` : `<span class="badge bg-info">${returnedCondition || 'Good'}</span>`}
            </td>
            <td>
                ${remaining > 0 ? `<input type="text" class="form-control form-control-sm" name="item_notes[]" placeholder="Optional">` : `<small class="text-muted">${returnedNotes !== '-' ? returnedNotes : ''}</small>`}
            </td>
            <td class="text-center">
                ${remaining > 0 ? `
                <button type="button" class="btn btn-sm btn-outline-danger report-incident-item-btn"
                        data-item-id="${borrowedToolId}"
                        data-asset-id="${assetId}"
                        data-asset-ref="${reference}"
                        data-asset-name="${equipmentName}"
                        title="Report incident for this item">
                    <i class="bi bi-exclamation-triangle"></i>
                </button>
                ` : '-'}
            </td>
        `;
        returnTableBody.appendChild(row);
    });

    console.log(`Loaded ${items.length} items into return modal for batch ${batchId}`);
});

// Handle batch return form submission via AJAX
document.getElementById('batchReturnForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('processReturnBtn');
    const originalBtnText = submitBtn.innerHTML;

    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

    try {
        const formData = new FormData(this);

        const response = await fetch('index.php?route=borrowed-tools/batch/return', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchReturnModal'));
            modal.hide();

            // Show success message
            alert('Batch returned successfully!');

            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to process return'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    } catch (error) {
        console.error('Batch return error:', error);
        alert('Error: Failed to process return. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});

// Batch Extend Modal Logic
document.getElementById('batchExtendModal').addEventListener('shown.bs.modal', function() {
    const batchId = this.getAttribute('data-batch-id');
    const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);

    if (!batchItemsRow) {
        console.error('Batch items row not found for batch ID:', batchId);
        return;
    }

    // Set batch ID and CSRF token
    document.getElementById('extendBatchId').value = batchId;
    document.getElementById('extendCsrfToken').value = extendBatchCsrfToken;

    // Get batch items data from the hidden table
    const itemsTable = batchItemsRow.querySelector('.batch-items-table tbody');
    const items = Array.from(itemsTable.querySelectorAll('tr[data-item-id]'));

    const extendTableBody = document.getElementById('batchExtendItems');
    extendTableBody.innerHTML = '';

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('new_expected_return').setAttribute('min', today);

    items.forEach((item, index) => {
        const cells = item.querySelectorAll('td');
        if (cells.length < 8) {
            console.warn('Invalid row structure, skipping item', index);
            return;
        }

        const borrowedToolId = item.getAttribute('data-item-id');
        const equipmentName = cells[1].querySelector('strong') ? cells[1].querySelector('strong').textContent : cells[1].textContent;
        const equipmentCategory = cells[1].querySelector('small') ? cells[1].querySelector('small').textContent : '';
        const reference = cells[2].textContent.trim();
        const borrowed = parseInt(cells[3].textContent.trim()) || 1;
        const returned = parseInt(cells[4].textContent.trim()) || 0;
        const remaining = borrowed - returned;
        const statusBadge = cells[8].querySelector('.badge');
        const status = statusBadge ? statusBadge.textContent.trim() : '';

        // Get expected return date from the main table
        const mainTableRow = document.querySelector(`tr[data-batch-id="${batchId}"]`);
        const expectedReturnCell = mainTableRow ? mainTableRow.querySelector('td:nth-child(6)') : null;
        const expectedReturn = expectedReturnCell ? expectedReturnCell.textContent.trim() : 'N/A';

        // Only show items that have remaining quantity (not fully returned)
        if (remaining <= 0) {
            return;
        }

        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="form-check-input item-extend-checkbox"
                       name="item_ids[]" value="${borrowedToolId}"
                       data-remaining="${remaining}"
                       ${remaining > 0 ? 'checked' : 'disabled'}>
            </td>
            <td>${index + 1}</td>
            <td>
                <strong>${equipmentName}</strong>
                ${equipmentCategory ? `<br><small class="text-muted">${equipmentCategory}</small>` : ''}
            </td>
            <td>${reference}</td>
            <td class="text-center"><span class="badge bg-primary">${borrowed}</span></td>
            <td class="text-center"><span class="badge bg-warning">${remaining}</span></td>
            <td>${expectedReturn}</td>
            <td><span class="badge bg-secondary">${status}</span></td>
        `;

        extendTableBody.appendChild(row);
    });

    // Update select all checkbox state
    updateSelectAllExtendCheckbox();
});

// Select All Extend functionality
document.getElementById('selectAllExtend').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.item-extend-checkbox:not(:disabled)');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Update select all checkbox when individual checkboxes change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-extend-checkbox')) {
        updateSelectAllExtendCheckbox();
    }
});

function updateSelectAllExtendCheckbox() {
    const checkboxes = document.querySelectorAll('.item-extend-checkbox:not(:disabled)');
    const selectAllCheckbox = document.getElementById('selectAllExtend');

    if (checkboxes.length === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        return;
    }

    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;

    if (checkedCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedCount === checkboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

// Handle batch extend form submission
document.getElementById('batchExtendForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('processExtendBtn');
    const originalBtnText = submitBtn.innerHTML;

    // Get selected items
    const selectedItems = Array.from(document.querySelectorAll('.item-extend-checkbox:checked')).map(cb => cb.value);

    if (selectedItems.length === 0) {
        alert('Please select at least one item to extend');
        return;
    }

    const newExpectedReturn = document.getElementById('new_expected_return').value;
    const reason = document.getElementById('extend_reason').value.trim();

    if (!newExpectedReturn) {
        alert('Please enter a new expected return date');
        return;
    }

    if (!reason) {
        alert('Please provide a reason for the extension');
        return;
    }

    // Disable submit button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

    try {
        const formData = new FormData();
        formData.append('_csrf_token', document.getElementById('extendCsrfToken').value);
        formData.append('batch_id', document.getElementById('extendBatchId').value);
        formData.append('new_expected_return', newExpectedReturn);
        formData.append('reason', reason);

        // Add selected item IDs
        selectedItems.forEach(itemId => {
            formData.append('item_ids[]', itemId);
        });

        const response = await fetch('?route=borrowed-tools/batch/extend', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchExtendModal'));
            modal.hide();

            // Show success message
            alert('Batch extended successfully!');

            // Reload page to show updated dates
            window.location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to extend batch'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    } catch (error) {
        console.error('Batch extend error:', error);
        alert('Error: Failed to extend batch. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});

// Handle incident report button click from return modal
document.addEventListener('click', function(e) {
    if (e.target.closest('.report-incident-item-btn')) {
        e.preventDefault();
        const btn = e.target.closest('.report-incident-item-btn');
        const itemId = btn.getAttribute('data-item-id');
        const assetId = btn.getAttribute('data-asset-id');
        const assetRef = btn.getAttribute('data-asset-ref');
        const assetName = btn.getAttribute('data-asset-name');

        // Populate incident modal
        document.getElementById('incidentCsrfToken').value = incidentCsrfToken;
        document.getElementById('incidentAssetId').value = assetId || '';
        document.getElementById('incidentBorrowedToolId').value = itemId;
        document.getElementById('incidentEquipmentName').textContent = assetName;
        document.getElementById('incidentAssetRef').textContent = assetRef;

        // Reset form fields
        document.getElementById('incident_type').value = '';
        document.getElementById('incident_severity').value = 'medium';
        document.getElementById('incident_description').value = '';
        document.getElementById('incident_location').value = '';

        // Show incident modal
        const incidentModal = new bootstrap.Modal(document.getElementById('quickIncidentModal'));
        incidentModal.show();
    }
});

// Handle incident form submission
document.getElementById('quickIncidentForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitIncidentBtn');
    const originalBtnText = submitBtn.innerHTML;

    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating Incident...';

    try {
        const formData = new FormData(this);
        formData.append('reported_by', <?= $_SESSION['user_id'] ?? 0 ?>);
        formData.append('date_reported', new Date().toISOString().split('T')[0]);

        const response = await fetch('?route=incidents/create', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        if (response.redirected) {
            // Success - incident was created and redirected to view page
            const incidentModal = bootstrap.Modal.getInstance(document.getElementById('quickIncidentModal'));
            incidentModal.hide();

            alert('Incident report created successfully!');

            // Optionally open the incident view in new tab
            window.open(response.url, '_blank');
        } else {
            const result = await response.json();
            if (result.success) {
                const incidentModal = bootstrap.Modal.getInstance(document.getElementById('quickIncidentModal'));
                incidentModal.hide();

                alert('Incident report created successfully!');
            } else {
                alert('Error: ' + (result.message || 'Failed to create incident'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    } catch (error) {
        console.error('Incident creation error:', error);
        alert('Error: Failed to create incident. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});

</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Borrowed Tools - ConstructLink™';
$pageHeader = 'Borrowed Tools Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
