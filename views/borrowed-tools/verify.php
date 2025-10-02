<?php
/**
 * ConstructLink™ - Verify Borrowed Tool Request
 * MVA Workflow: Verifier Step
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Start output buffering to capture content
ob_start();

$isCritical = false;
if (isset($borrowedTool['acquisition_cost']) && $borrowedTool['acquisition_cost'] > 50000) {
    $isCritical = true;
}

$criticalCategories = ['Equipment', 'Machinery', 'Safety', 'Heavy Equipment'];
if (in_array($borrowedTool['category_name'], $criticalCategories)) {
    $isCritical = true;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-search"></i> Verify Borrowed Tool Request
                        <?php if ($isCritical): ?>
                            <span class="badge bg-warning ms-2">Critical Tool</span>
                        <?php endif; ?>
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
                                <?php if ($borrowedTool['acquisition_cost']): ?>
                                <tr>
                                    <td><strong>Asset Value:</strong></td>
                                    <td>₱<?= number_format($borrowedTool['acquisition_cost'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
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

                    <!-- Verification Form -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="fw-bold mb-0">Verification Checklist</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?route=borrowed-tools/verify&id=<?= $borrowedTool['id'] ?>">
                                <?= CSRFProtection::getTokenField() ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Verification Requirements:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_asset_available" required>
                                        <label class="form-check-label" for="check_asset_available">
                                            Asset is available and in good condition
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_borrower_valid" required>
                                        <label class="form-check-label" for="check_borrower_valid">
                                            Borrower identity and contact information verified
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_purpose_valid" required>
                                        <label class="form-check-label" for="check_purpose_valid">
                                            Purpose of borrowing is legitimate and appropriate
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_return_date" required>
                                        <label class="form-check-label" for="check_return_date">
                                            Expected return date is reasonable
                                        </label>
                                    </div>
                                    <?php if ($isCritical): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_critical_approval" required>
                                        <label class="form-check-label" for="check_critical_approval">
                                            <strong>Critical tool borrowing requires proper authorization</strong>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="verification_notes" class="form-label">Verification Notes</label>
                                    <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3" placeholder="Enter any additional notes or conditions..."></textarea>
                                    <div class="form-text">Optional: Add any conditions or special instructions for the borrowing.</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Details
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-check-circle"></i> Verify Tool Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Workflow Status -->
                    <div class="mt-4">
                        <h6 class="fw-bold">MVA Workflow Status</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 25%">
                                <small>Pending Verification</small>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-3 text-center">
                                <small class="text-muted">Created</small><br>
                                <i class="bi bi-check-circle text-success"></i>
                            </div>
                            <div class="col-3 text-center">
                                <small class="text-warning">Verifying</small><br>
                                <i class="bi bi-hourglass-split text-warning"></i>
                            </div>
                            <div class="col-3 text-center">
                                <small class="text-muted">Approval</small><br>
                                <i class="bi bi-circle text-muted"></i>
                            </div>
                            <div class="col-3 text-center">
                                <small class="text-muted">Borrowed</small><br>
                                <i class="bi bi-circle text-muted"></i>
                            </div>
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
$pageTitle = 'Verify Borrowed Tool - ConstructLink™';
$pageHeader = 'Verify Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name'] ?? '');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Verify Request', 'url' => '?route=borrowed-tools/verify&id=' . ($borrowedTool['id'] ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>