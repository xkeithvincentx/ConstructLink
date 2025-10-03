<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();


?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Status Badge -->
<div class="row mb-4">
    <div class="col-12">
        <?php
        $status = $borrowedTool['status'] ?? 'unknown';
        $statusClasses = [
            'Pending Verification' => 'bg-warning',
            'Pending Approval' => 'bg-info',
            'Approved' => 'bg-success',
            'Borrowed' => 'bg-primary',
            'Returned' => 'bg-success',
            'Overdue' => 'bg-danger',
            'Canceled' => 'bg-secondary'
        ];
        $statusClass = $statusClasses[$status] ?? 'bg-secondary';
        ?>
        <span class="badge <?= $statusClass ?> fs-6">
            <?= ucfirst($status) ?>
        </span>
        
        <!-- Tool Type Indicator -->
        <?php if ($isCriticalTool): ?>
            <span class="badge bg-warning text-dark fs-6 ms-2">
                <i class="bi bi-shield-check"></i> Critical Tool (>₱50,000)
            </span>
        <?php else: ?>
            <span class="badge bg-success fs-6 ms-2">
                <i class="bi bi-lightning-charge"></i> Basic Tool (≤₱50,000)
            </span>
        <?php endif; ?>
        
        <?php if ($status === 'Borrowed' && strtotime($borrowedTool['expected_return']) < time()): ?>
            <span class="badge bg-danger fs-6 ms-2">
                <?= abs(floor((time() - strtotime($borrowedTool['expected_return'])) / 86400)) ?> days overdue
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- MVA Workflow Progress -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">MVA Workflow Progress</h6>
            </div>
            <div class="card-body">
                <?php
                $workflowSteps = [
                    'Pending Verification' => ['label' => 'Verification', 'progress' => 25],
                    'Pending Approval' => ['label' => 'Approval', 'progress' => 50],
                    'Approved' => ['label' => 'Approved', 'progress' => 75],
                    'Borrowed' => ['label' => 'Borrowed', 'progress' => 100],
                    'Returned' => ['label' => 'Returned', 'progress' => 100],
                    'Overdue' => ['label' => 'Overdue', 'progress' => 100],
                    'Canceled' => ['label' => 'Canceled', 'progress' => 0]
                ];
                
                $currentStep = $workflowSteps[$status] ?? ['label' => 'Unknown', 'progress' => 0];
                ?>
                
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar 
                        <?= $status === 'Canceled' ? 'bg-secondary' : ($status === 'Overdue' ? 'bg-danger' : 'bg-info') ?>" 
                        role="progressbar" 
                        style="width: <?= $currentStep['progress'] ?>%"
                        aria-valuenow="<?= $currentStep['progress'] ?>" 
                        aria-valuemin="0" 
                        aria-valuemax="100">
                        <small><?= $currentStep['label'] ?></small>
                    </div>
                </div>
                
                <div class="row text-center">
                    <div class="col-2">
                        <small class="text-muted">Created</small><br>
                        <i class="bi bi-check-circle text-success"></i>
                    </div>
                    <div class="col-2">
                        <small class="<?= in_array($status, ['Pending Verification']) ? 'text-warning' : ($currentStep['progress'] >= 25 ? 'text-success' : 'text-muted') ?>">
                            Verification
                        </small><br>
                        <i class="bi bi-<?= in_array($status, ['Pending Verification']) ? 'hourglass-split text-warning' : ($currentStep['progress'] >= 25 ? 'check-circle text-success' : 'circle text-muted') ?>"></i>
                    </div>
                    <div class="col-2">
                        <small class="<?= in_array($status, ['Pending Approval']) ? 'text-info' : ($currentStep['progress'] >= 50 ? 'text-success' : 'text-muted') ?>">
                            Approval
                        </small><br>
                        <i class="bi bi-<?= in_array($status, ['Pending Approval']) ? 'hourglass-split text-info' : ($currentStep['progress'] >= 50 ? 'check-circle text-success' : 'circle text-muted') ?>"></i>
                    </div>
                    <div class="col-2">
                        <small class="<?= in_array($status, ['Approved']) ? 'text-success' : ($currentStep['progress'] >= 75 ? 'text-success' : 'text-muted') ?>">
                            Approved
                        </small><br>
                        <i class="bi bi-<?= in_array($status, ['Approved']) ? 'hourglass-split text-success' : ($currentStep['progress'] >= 75 ? 'check-circle text-success' : 'circle text-muted') ?>"></i>
                    </div>
                    <div class="col-2">
                        <small class="<?= in_array($status, ['Borrowed']) ? 'text-primary' : ($currentStep['progress'] >= 100 && $status !== 'Canceled' ? 'text-success' : 'text-muted') ?>">
                            Borrowed
                        </small><br>
                        <i class="bi bi-<?= in_array($status, ['Borrowed']) ? 'hourglass-split text-primary' : ($currentStep['progress'] >= 100 && $status !== 'Canceled' ? 'check-circle text-success' : 'circle text-muted') ?>"></i>
                    </div>
                    <div class="col-2">
                        <small class="<?= in_array($status, ['Returned']) ? 'text-success' : ($status === 'Overdue' ? 'text-danger' : 'text-muted') ?>">
                            <?= $status === 'Overdue' ? 'Overdue' : 'Returned' ?>
                        </small><br>
                        <i class="bi bi-<?= in_array($status, ['Returned']) ? 'check-circle text-success' : ($status === 'Overdue' ? 'exclamation-triangle text-danger' : 'circle text-muted') ?>"></i>
                    </div>
                </div>
                
                <?php if ($status !== 'Canceled'): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <small class="text-muted">
                            <strong>MVA Roles:</strong> 
                            Maker (Warehouseman) → Verifier (Project Manager for critical tools) → Authorizer (Asset/Finance Director for critical tools)
                        </small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Borrowed Tool Information -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Borrowing Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Borrow ID:</dt>
                            <dd class="col-sm-7">
                                <strong>#<?= $borrowedTool['id'] ?></strong>
                            </dd>
                            
                            <dt class="col-sm-5">Asset:</dt>
                            <dd class="col-sm-7">
                                <div>
                                    <strong><?= htmlspecialchars($borrowedTool['asset_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($borrowedTool['asset_ref']) ?></small>
                                </div>
                            </dd>
                            
                            <dt class="col-sm-5">Category:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($borrowedTool['category_name'] ?? 'N/A') ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($borrowedTool['project_name'] ?? 'N/A') ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Borrower:</dt>
                            <dd class="col-sm-7">
                                <strong><?= htmlspecialchars($borrowedTool['borrower_name']) ?></strong>
                                <?php if (!empty($borrowedTool['borrower_contact'])): ?>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i>
                                        <?= htmlspecialchars($borrowedTool['borrower_contact']) ?>
                                    </small>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Issued By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($borrowedTool['issued_by_name'] ?? 'N/A') ?></dd>
                            
                            <?php if ($borrowedTool['verified_by_name']): ?>
                            <dt class="col-sm-5">Verified By:</dt>
                            <dd class="col-sm-7">
                                <?= htmlspecialchars($borrowedTool['verified_by_name']) ?>
                                <br><small class="text-muted"><?= formatDateTime($borrowedTool['verification_date']) ?></small>
                            </dd>
                            <?php endif; ?>
                            
                            <?php if ($borrowedTool['approved_by_name']): ?>
                            <dt class="col-sm-5">Approved By:</dt>
                            <dd class="col-sm-7">
                                <?= htmlspecialchars($borrowedTool['approved_by_name']) ?>
                                <br><small class="text-muted"><?= formatDateTime($borrowedTool['approval_date']) ?></small>
                            </dd>
                            <?php endif; ?>
                            
                            <?php if ($borrowedTool['borrowed_by_name']): ?>
                            <dt class="col-sm-5">Borrowed By:</dt>
                            <dd class="col-sm-7">
                                <?= htmlspecialchars($borrowedTool['borrowed_by_name']) ?>
                                <br><small class="text-muted"><?= formatDateTime($borrowedTool['borrowed_date']) ?></small>
                            </dd>
                            <?php endif; ?>
                            
                            <?php if ($borrowedTool['returned_by_name']): ?>
                            <dt class="col-sm-5">Returned By:</dt>
                            <dd class="col-sm-7">
                                <?= htmlspecialchars($borrowedTool['returned_by_name']) ?>
                                <br><small class="text-muted"><?= formatDateTime($borrowedTool['return_date']) ?></small>
                            </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Borrowed Date:</dt>
                            <dd class="col-sm-7">
                                <?= formatDateTime($borrowedTool['created_at']) ?>
                            </dd>
                            
                            <dt class="col-sm-5">Expected Return:</dt>
                            <dd class="col-sm-7">
                                <?php 
                                $expectedReturn = $borrowedTool['expected_return'];
                                $isOverdue = $status === 'Borrowed' && strtotime($expectedReturn) < time();
                                ?>
                                <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                    <?= formatDate($expectedReturn) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Actual Return:</dt>
                            <dd class="col-sm-7">
                                <?php if ($borrowedTool['actual_return']): ?>
                                    <?= formatDate($borrowedTool['actual_return']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Not returned yet</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge <?= $statusClass ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Duration:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $startDate = new DateTime($borrowedTool['created_at']);
                                $endDate = $borrowedTool['actual_return'] ? new DateTime($borrowedTool['actual_return']) : new DateTime();
                                $duration = $startDate->diff($endDate);
                                ?>
                                <?= $duration->days ?> day(s)
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <?php if (!empty($borrowedTool['purpose'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <dt>Purpose:</dt>
                        <dd class="mt-2">
                            <div class="p-3 bg-light rounded">
                                <?= nl2br(htmlspecialchars($borrowedTool['purpose'])) ?>
                            </div>
                        </dd>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tool Condition -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Tool Condition
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Condition When Borrowed</h6>
                        <?php if (!empty($borrowedTool['condition_out'])): ?>
                            <div class="p-3 bg-light rounded">
                                <?= nl2br(htmlspecialchars($borrowedTool['condition_out'])) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No condition notes recorded</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Condition When Returned</h6>
                        <?php if (!empty($borrowedTool['condition_in'])): ?>
                            <div class="p-3 bg-light rounded">
                                <?= nl2br(htmlspecialchars($borrowedTool['condition_in'])) ?>
                            </div>
                        <?php elseif ($status === 'returned'): ?>
                            <p class="text-muted">No return condition notes recorded</p>
                        <?php else: ?>
                            <p class="text-muted">Tool not returned yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Quick Stats -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Quick Info
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="stat-item">
                            <?php
                            $startDate = new DateTime($borrowedTool['created_at']);
                            $endDate = $borrowedTool['actual_return'] ? new DateTime($borrowedTool['actual_return']) : new DateTime();
                            $duration = $startDate->diff($endDate);
                            ?>
                            <div class="stat-value text-primary"><?= $duration->days ?></div>
                            <div class="stat-label">Days Used</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <?php
                            $expectedDate = new DateTime($borrowedTool['expected_return']);
                            $today = new DateTime();
                            $remaining = $today->diff($expectedDate);
                            $daysRemaining = $today > $expectedDate ? -$remaining->days : $remaining->days;
                            ?>
                            <div class="stat-value <?= $daysRemaining < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= abs($daysRemaining) ?>
                            </div>
                            <div class="stat-label">
                                <?= $daysRemaining < 0 ? 'Days Overdue' : 'Days Remaining' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-gear me-2"></i>Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($status === 'Borrowed'): ?>
                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk'])): ?>
                            <a href="?route=borrowed-tools/return&id=<?= $borrowedTool['id'] ?>" class="btn btn-success">
                                <i class="bi bi-arrow-return-left me-1"></i>Return Tool
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman'])): ?>
                            <a href="?route=borrowed-tools/extend&id=<?= $borrowedTool['id'] ?>" class="btn btn-info">
                                <i class="bi bi-calendar-plus me-1"></i>Extend Period
                            </a>
                        <?php endif; ?>
                        
                        <?php if (strtotime($borrowedTool['expected_return']) < time() && $auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman'])): ?>
                            <button type="button" class="btn btn-warning" onclick="markOverdue(<?= $borrowedTool['id'] ?>)">
                                <i class="bi bi-exclamation-triangle me-1"></i>Mark as Overdue
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <a href="?route=assets/view&id=<?= $borrowedTool['asset_id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-box-seam me-1"></i>View Asset Details
                    </a>
                    
                    <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
                        <i class="bi bi-list me-1"></i>All Borrowed Tools
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>

<style>
.stat-item {
    padding: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 5px;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Borrowed Tool Details - ConstructLink™';
$pageHeader = 'Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name'] ?? 'Unknown');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'View Details', 'url' => '?route=borrowed-tools/view&id=' . ($borrowedTool['id'] ?? 0)]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
