<?php
/**
 * ConstructLink™ - Approve Borrowed Tool Request
 * MVA Workflow: Authorizer Step
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Start output buffering to capture content
ob_start();

$isCritical = false;
$criticalThreshold = config('business_rules.critical_tool_threshold', 50000);
if (isset($borrowedTool['acquisition_cost']) && $borrowedTool['acquisition_cost'] > $criticalThreshold) {
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
                        <i class="bi bi-person-check"></i> Approve Borrowed Tool Request
                        <?php if ($isCritical): ?>
                            <span class="badge bg-danger ms-2">Critical Tool - Authorization Required</span>
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
                                    <td><strong>Verified By:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['verified_by_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Verification Date:</strong></td>
                                    <td><?= date('M d, Y g:i A', strtotime($borrowedTool['verification_date'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Verification Notes -->
                    <?php if ($borrowedTool['notes']): ?>
                    <div class="alert alert-info mb-4">
                        <h6 class="fw-bold">Verification Notes:</h6>
                        <p class="mb-0"><?= htmlspecialchars($borrowedTool['notes']) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Approval Form -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="fw-bold mb-0">Authorization Checklist</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?route=borrowed-tools/approve&id=<?= $borrowedTool['id'] ?>">
                                <?= CSRFProtection::getTokenField() ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Authorization Requirements:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_authority" required>
                                        <label class="form-check-label" for="check_authority">
                                            I have the authority to approve this tool borrowing request
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_verification" required>
                                        <label class="form-check-label" for="check_verification">
                                            Verification has been completed and requirements met
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_risk_assessment" required>
                                        <label class="form-check-label" for="check_risk_assessment">
                                            Risk assessment completed and acceptable
                                        </label>
                                    </div>
                                    <?php if ($isCritical): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_high_value" required>
                                        <label class="form-check-label" for="check_high_value">
                                            <strong>High-value/critical asset borrowing justified and approved</strong>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_insurance" required>
                                        <label class="form-check-label" for="check_insurance">
                                            <strong>Insurance and liability considerations reviewed</strong>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_policy_compliance" required>
                                        <label class="form-check-label" for="check_policy_compliance">
                                            Borrowing complies with company policies and procedures
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="approval_notes" class="form-label">Approval Notes</label>
                                    <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" placeholder="Enter approval conditions, special instructions, or comments..."></textarea>
                                    <div class="form-text">Optional: Add any conditions or special instructions for the approved borrowing.</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Details
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-person-check"></i> Approve Tool Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Workflow Status -->
                    <div class="mt-4">
                        <h6 class="fw-bold">MVA Workflow Status</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 50%">
                                <small>Pending Approval</small>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-3 text-center">
                                <small class="text-muted">Created</small><br>
                                <i class="bi bi-check-circle text-success"></i>
                            </div>
                            <div class="col-3 text-center">
                                <small class="text-muted">Verified</small><br>
                                <i class="bi bi-check-circle text-success"></i>
                            </div>
                            <div class="col-3 text-center">
                                <small class="text-warning">Approving</small><br>
                                <i class="bi bi-hourglass-split text-warning"></i>
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
$pageTitle = 'Approve Borrowed Tool - ConstructLink™';
$pageHeader = 'Approve Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name'] ?? '');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Approve Request', 'url' => '?route=borrowed-tools/approve&id=' . ($borrowedTool['id'] ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>