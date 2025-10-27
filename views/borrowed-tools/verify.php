<?php
/**
 * ConstructLink™ - Verify Borrowed Tool Request
 * MVA Workflow: Verifier Step
 * REFACTORED: Using reusable components for DRY principles
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Load helpers
require_once APP_ROOT . '/helpers/ButtonHelper.php';

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
                    <?php
                    // Display errors using reusable component
                    if (!empty($errors)) {
                        $alertConfig = [
                            'type' => 'danger',
                            'messages' => $errors
                        ];
                        include APP_ROOT . '/views/components/alert_message.php';
                    }
                    ?>

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

                                <?php
                                // Build checklist items
                                $checklistItems = [
                                    ['id' => 'check_asset_available', 'label' => 'Asset is available and in good condition', 'required' => true],
                                    ['id' => 'check_borrower_valid', 'label' => 'Borrower identity and contact information verified', 'required' => true],
                                    ['id' => 'check_purpose_valid', 'label' => 'Purpose of borrowing is legitimate and appropriate', 'required' => true],
                                    ['id' => 'check_return_date', 'label' => 'Expected return date is reasonable', 'required' => true]
                                ];

                                if ($isCritical) {
                                    $checklistItems[] = ['id' => 'check_critical_approval', 'label' => 'Critical tool borrowing requires proper authorization', 'required' => true, 'bold' => true];
                                }

                                $checklistConfig = [
                                    'title' => 'Verification Requirements:',
                                    'items' => $checklistItems
                                ];
                                include APP_ROOT . '/views/components/checklist_form.php';
                                ?>

                                <div class="mb-3">
                                    <label for="verification_notes" class="form-label">Verification Notes</label>
                                    <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3" placeholder="Enter any additional notes or conditions..." aria-describedby="verification_notes_help"></textarea>
                                    <div id="verification_notes_help" class="form-text">Optional: Add any conditions or special instructions for the borrowing.</div>
                                </div>

                                <?php
                                echo ButtonHelper::renderWorkflowActions(
                                    ['url' => "?route=borrowed-tools/view&id={$borrowedTool['id']}"],
                                    [
                                        'text' => 'Verify Tool Request',
                                        'type' => 'submit',
                                        'style' => 'warning',
                                        'icon' => 'check-circle'
                                    ]
                                );
                                ?>
                            </form>
                        </div>
                    </div>

                    <?php
                    // Workflow progress using reusable component
                    $workflowConfig = [
                        'currentStage' => 'verification',
                        'completedStages' => ['creation'],
                        'stages' => [
                            ['name' => 'creation', 'label' => 'Created'],
                            ['name' => 'verification', 'label' => 'Verifying'],
                            ['name' => 'approval', 'label' => 'Approval'],
                            ['name' => 'handover', 'label' => 'Borrowed']
                        ]
                    ];
                    include APP_ROOT . '/views/components/workflow_progress.php';
                    ?>
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
