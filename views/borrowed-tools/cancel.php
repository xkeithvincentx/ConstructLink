<?php
/**
 * ConstructLink™ - Cancel Borrowed Tool Request
 * MVA Workflow: Cancellation at any stage before borrowed
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Start output buffering to capture content
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-x-circle text-danger"></i> Cancel Borrowed Tool Request
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> You are about to cancel this borrowed tool request. This action cannot be undone.
                    </div>

                    <!-- Tool Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Tool Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Asset Reference:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['asset_ref']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Asset Name:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['asset_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Category:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['category_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Project:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['project_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Current Status:</strong></td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'Pending Verification' => 'bg-warning',
                                            'Pending Approval' => 'bg-info',
                                            'Approved' => 'bg-success'
                                        ];
                                        $statusClass = $statusClasses[$borrowedTool['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($borrowedTool['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Borrowing Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Borrower:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['borrower_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['borrower_contact']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Expected Return:</strong></td>
                                    <td><?= date('M d, Y', strtotime($borrowedTool['expected_return'])) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Purpose:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['purpose']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Issued By:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['issued_by_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Request Date:</strong></td>
                                    <td><?= date('M d, Y g:i A', strtotime($borrowedTool['created_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Workflow Status -->
                    <?php if ($borrowedTool['verified_by_name']): ?>
                    <div class="alert alert-info mb-4">
                        <h6 class="fw-bold">Workflow Progress:</h6>
                        <p><strong>Verified by:</strong> <?= htmlspecialchars($borrowedTool['verified_by_name']) ?> 
                           on <?= date('M d, Y g:i A', strtotime($borrowedTool['verification_date'])) ?></p>
                        <?php if ($borrowedTool['approved_by_name']): ?>
                        <p><strong>Approved by:</strong> <?= htmlspecialchars($borrowedTool['approved_by_name']) ?> 
                           on <?= date('M d, Y g:i A', strtotime($borrowedTool['approval_date'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Cancellation Form -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="fw-bold mb-0">Cancellation Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?route=borrowed-tools/cancel&id=<?= $borrowedTool['id'] ?>">
                                <?= CSRFProtection::getTokenField() ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Cancellation Confirmation:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirm_cancellation" required>
                                        <label class="form-check-label" for="confirm_cancellation">
                                            I confirm that I want to cancel this borrowed tool request
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="understand_implications" required>
                                        <label class="form-check-label" for="understand_implications">
                                            I understand this action cannot be undone and will require a new request
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="cancellation_reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" required placeholder="Please provide a reason for cancelling this request..."></textarea>
                                    <div class="form-text">Please provide a clear reason for cancelling this request for record keeping purposes.</div>
                                </div>

                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Important:</strong> Cancelling this request will:
                                    <ul class="mb-0 mt-2">
                                        <li>Permanently cancel the borrowing request</li>
                                        <li>Make the asset available for other requests</li>
                                        <li>Require a new request to be submitted if needed later</li>
                                        <li>Create an audit record of the cancellation</li>
                                    </ul>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Details
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-x-circle"></i> Cancel Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Cancel Borrowed Tool - ConstructLink™';
$pageHeader = 'Cancel Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name'] ?? '');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Cancel Request', 'url' => '?route=borrowed-tools/cancel&id=' . ($borrowedTool['id'] ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>