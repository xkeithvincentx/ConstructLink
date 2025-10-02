<?php
/**
 * ConstructLink™ - Mark Tool as Borrowed
 * MVA Workflow: Final Step - Physical Handover
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
                        <i class="bi bi-box-arrow-down"></i> Tool Handover - Mark as Borrowed
                        <?php if ($isCritical): ?>
                            <span class="badge bg-success ms-2">Approved Critical Tool</span>
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
                                    <td><strong>Approved By:</strong></td>
                                    <td><?= htmlspecialchars($borrowedTool['approved_by_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Approval Date:</strong></td>
                                    <td><?= date('M d, Y g:i A', strtotime($borrowedTool['approval_date'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Approval Notes -->
                    <?php if ($borrowedTool['notes']): ?>
                    <div class="alert alert-success mb-4">
                        <h6 class="fw-bold">Approval Notes:</h6>
                        <p class="mb-0"><?= htmlspecialchars($borrowedTool['notes']) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Handover Form -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="fw-bold mb-0">Physical Handover Checklist</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?route=borrowed-tools/borrow&id=<?= $borrowedTool['id'] ?>">
                                <?= CSRFProtection::getTokenField() ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Handover Requirements:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_id_verified" required>
                                        <label class="form-check-label" for="check_id_verified">
                                            Borrower identity verified and matches request
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_tool_condition" required>
                                        <label class="form-check-label" for="check_tool_condition">
                                            Tool condition inspected and documented
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_safety_briefing" required>
                                        <label class="form-check-label" for="check_safety_briefing">
                                            Safety briefing provided for tool operation
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_return_date" required>
                                        <label class="form-check-label" for="check_return_date">
                                            Return date and conditions clearly communicated
                                        </label>
                                    </div>
                                    <?php if ($isCritical): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_special_instructions" required>
                                        <label class="form-check-label" for="check_special_instructions">
                                            <strong>Special handling instructions for critical tool provided</strong>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_emergency_contact" required>
                                        <label class="form-check-label" for="check_emergency_contact">
                                            <strong>Emergency contact information provided</strong>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check_borrower_acknowledged" required>
                                        <label class="form-check-label" for="check_borrower_acknowledged">
                                            Borrower acknowledged receipt and responsibility
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="borrow_notes" class="form-label">Handover Notes</label>
                                    <textarea class="form-control" id="borrow_notes" name="borrow_notes" rows="3" placeholder="Enter condition notes, special instructions, or comments..."></textarea>
                                    <div class="form-text">Document the condition of the tool and any special instructions given to the borrower.</div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Important:</strong> By completing this handover, you confirm that the tool has been physically handed over to the borrower and all required procedures have been followed.
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Details
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-down"></i> Complete Handover
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Workflow Status -->
                    <div class="mt-4">
                        <h6 class="fw-bold">MVA Workflow Status</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 75%">
                                <small>Ready for Handover</small>
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
                                <small class="text-muted">Approved</small><br>
                                <i class="bi bi-check-circle text-success"></i>
                            </div>
                            <div class="col-3 text-center">
                                <small class="text-primary">Handover</small><br>
                                <i class="bi bi-hourglass-split text-primary"></i>
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
$pageTitle = 'Mark as Borrowed - ConstructLink™';
$pageHeader = 'Mark as Borrowed: ' . htmlspecialchars($borrowedTool['asset_name'] ?? '');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Mark as Borrowed', 'url' => '?route=borrowed-tools/borrow&id=' . ($borrowedTool['id'] ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>