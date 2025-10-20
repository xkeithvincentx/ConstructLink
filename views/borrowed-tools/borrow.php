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

// Check if tool is critical
$isCritical = ViewHelper::isCriticalTool($borrowedTool['acquisition_cost'] ?? 0);

// Also check category-based criticality
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
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($isCritical): ?>
                        <?= ViewHelper::renderCriticalToolWarning() ?>
                    <?php endif; ?>

                    <!-- Tool Details -->
                    <?= ViewHelper::renderToolDetailsTable($borrowedTool) ?>

                    <!-- Approval Notes -->
                    <?php if (!empty($borrowedTool['notes'])): ?>
                    <div class="workflow-notes">
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
                            <form method="POST" action="?route=borrowed-tools/borrow&id=<?= $borrowedTool['id'] ?>" class="checklist-form">
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
                                    <textarea class="form-control" id="borrow_notes" name="borrow_notes" rows="3" placeholder="Enter condition notes, special instructions, or comments..." aria-describedby="borrow_notes_help"></textarea>
                                    <div id="borrow_notes_help" class="form-text">Document the condition of the tool and any special instructions given to the borrower.</div>
                                </div>

                                <div class="alert alert-warning" role="alert">
                                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                    <strong>Important:</strong> By completing this handover, you confirm that the tool has been physically handed over to the borrower and all required procedures have been followed.
                                </div>

                                <div class="workflow-actions">
                                    <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Details
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-down" aria-hidden="true"></i> Complete Handover
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Workflow Status -->
                    <div class="workflow-progress">
                        <h6 class="fw-bold">MVA Workflow Status</h6>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
                                <small>Ready for Handover</small>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-3 workflow-stage">
                                <small class="text-muted">Created</small>
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                            </div>
                            <div class="col-3 workflow-stage">
                                <small class="text-muted">Verified</small>
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                            </div>
                            <div class="col-3 workflow-stage">
                                <small class="text-muted">Approved</small>
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                            </div>
                            <div class="col-3 workflow-stage">
                                <small class="text-primary">Handover</small>
                                <i class="bi bi-hourglass-split text-primary" aria-hidden="true"></i>
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

// Load module-specific assets
AssetHelper::loadModuleCSS('borrowed-tools-mva-workflows');

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