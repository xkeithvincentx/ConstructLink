<?php
/**
 * ConstructLink™ - Approve Borrowed Tool Request
 * MVA Workflow: Authorizer Step
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
                        <i class="bi bi-person-check"></i> Approve Borrowed Tool Request
                        <?php if ($isCritical): ?>
                            <span class="badge bg-danger ms-2">Critical Tool - Authorization Required</span>
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

                                <?php
                                // Build checklist items
                                $checklistItems = [
                                    ['id' => 'check_authority', 'label' => 'I have the authority to approve this tool borrowing request', 'required' => true],
                                    ['id' => 'check_verification', 'label' => 'Verification has been completed and requirements met', 'required' => true],
                                    ['id' => 'check_risk_assessment', 'label' => 'Risk assessment completed and acceptable', 'required' => true]
                                ];

                                if ($isCritical) {
                                    $checklistItems[] = ['id' => 'check_high_value', 'label' => 'High-value/critical asset borrowing justified and approved', 'required' => true, 'bold' => true];
                                    $checklistItems[] = ['id' => 'check_insurance', 'label' => 'Insurance and liability considerations reviewed', 'required' => true, 'bold' => true];
                                }

                                $checklistItems[] = ['id' => 'check_policy_compliance', 'label' => 'Borrowing complies with company policies and procedures', 'required' => true];

                                $checklistConfig = [
                                    'title' => 'Authorization Requirements:',
                                    'items' => $checklistItems
                                ];
                                include APP_ROOT . '/views/components/checklist_form.php';
                                ?>

                                <div class="mb-3">
                                    <label for="approval_notes" class="form-label">Approval Notes</label>
                                    <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" placeholder="Enter approval conditions, special instructions, or comments..." aria-describedby="approval_notes_help"></textarea>
                                    <div id="approval_notes_help" class="form-text">Optional: Add any conditions or special instructions for the approved borrowing.</div>
                                </div>

                                <?php
                                echo ButtonHelper::renderWorkflowActions(
                                    ['url' => "?route=borrowed-tools/view&id={$borrowedTool['id']}"],
                                    [
                                        'text' => 'Approve Tool Request',
                                        'type' => 'submit',
                                        'style' => 'success',
                                        'icon' => 'person-check'
                                    ]
                                );
                                ?>
                            </form>
                        </div>
                    </div>

                    <?php
                    // Workflow progress using reusable component
                    $workflowConfig = [
                        'currentStage' => 'approval',
                        'completedStages' => ['creation', 'verification'],
                        'stages' => [
                            ['name' => 'creation', 'label' => 'Created'],
                            ['name' => 'verification', 'label' => 'Verified'],
                            ['name' => 'approval', 'label' => 'Approving'],
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
