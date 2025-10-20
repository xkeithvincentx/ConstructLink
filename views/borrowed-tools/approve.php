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
                        <i class="bi bi-person-check"></i> Approve Borrowed Tool Request
                        <?php if ($isCritical): ?>
                            <span class="badge bg-danger ms-2">Critical Tool - Authorization Required</span>
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

                    <!-- Verification Notes -->
                    <?php if (!empty($borrowedTool['notes'])): ?>
                    <div class="workflow-notes">
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
                            <form method="POST" action="?route=borrowed-tools/approve&id=<?= $borrowedTool['id'] ?>" class="checklist-form">
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
                                    <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" placeholder="Enter approval conditions, special instructions, or comments..." aria-describedby="approval_notes_help"></textarea>
                                    <div id="approval_notes_help" class="form-text">Optional: Add any conditions or special instructions for the approved borrowing.</div>
                                </div>

                                <div class="workflow-actions">
                                    <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Details
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-person-check" aria-hidden="true"></i> Approve Tool Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Workflow Status -->
                    <div class="workflow-progress">
                        <h6 class="fw-bold">MVA Workflow Status</h6>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                                <small>Pending Approval</small>
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
                                <small class="text-warning">Approving</small>
                                <i class="bi bi-hourglass-split text-warning" aria-hidden="true"></i>
                            </div>
                            <div class="col-3 workflow-stage">
                                <small class="text-muted">Borrowed</small>
                                <i class="bi bi-circle text-muted" aria-hidden="true"></i>
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