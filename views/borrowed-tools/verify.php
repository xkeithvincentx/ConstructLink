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
                        <i class="bi bi-search"></i> Verify Borrowed Tool Request
                        <?php if ($isCritical): ?>
                            <span class="badge bg-warning ms-2">Critical Tool</span>
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

                    <!-- Verification Form -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="fw-bold mb-0">Verification Checklist</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?route=borrowed-tools/verify&id=<?= $borrowedTool['id'] ?>" class="checklist-form">
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
                                    <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3" placeholder="Enter any additional notes or conditions..." aria-describedby="verification_notes_help"></textarea>
                                    <div id="verification_notes_help" class="form-text">Optional: Add any conditions or special instructions for the borrowing.</div>
                                </div>

                                <div class="workflow-actions">
                                    <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Details
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i> Verify Tool Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Workflow Status -->
                    <div class="workflow-progress">
                        <h6 class="fw-bold">MVA Workflow Status</h6>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                                <small>Pending Verification</small>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-3 workflow-stage">
                                <small class="text-muted">Created</small>
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                            </div>
                            <div class="col-3 workflow-stage">
                                <small class="text-warning">Verifying</small>
                                <i class="bi bi-hourglass-split text-warning" aria-hidden="true"></i>
                            </div>
                            <div class="col-3 workflow-stage">
                                <small class="text-muted">Approval</small>
                                <i class="bi bi-circle text-muted" aria-hidden="true"></i>
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