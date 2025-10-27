<?php
/**
 * ConstructLink™ - Mark Tool as Borrowed
 * MVA Workflow: Final Step - Physical Handover
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
                        <i class="bi bi-box-arrow-down"></i> Tool Handover - Mark as Borrowed
                        <?php if ($isCritical): ?>
                            <span class="badge bg-success ms-2">Approved Critical Tool</span>
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

                                <?php
                                // Build checklist items
                                $checklistItems = [
                                    ['id' => 'check_id_verified', 'label' => 'Borrower identity verified and matches request', 'required' => true],
                                    ['id' => 'check_tool_condition', 'label' => 'Tool condition inspected and documented', 'required' => true],
                                    ['id' => 'check_safety_briefing', 'label' => 'Safety briefing provided for tool operation', 'required' => true],
                                    ['id' => 'check_return_date', 'label' => 'Return date and conditions clearly communicated', 'required' => true]
                                ];

                                if ($isCritical) {
                                    $checklistItems[] = ['id' => 'check_special_instructions', 'label' => 'Special handling instructions for critical tool provided', 'required' => true, 'bold' => true];
                                    $checklistItems[] = ['id' => 'check_emergency_contact', 'label' => 'Emergency contact information provided', 'required' => true, 'bold' => true];
                                }

                                $checklistItems[] = ['id' => 'check_borrower_acknowledged', 'label' => 'Borrower acknowledged receipt and responsibility', 'required' => true];

                                $checklistConfig = [
                                    'title' => 'Handover Requirements:',
                                    'items' => $checklistItems
                                ];
                                include APP_ROOT . '/views/components/checklist_form.php';
                                ?>

                                <div class="mb-3">
                                    <label for="borrow_notes" class="form-label">Handover Notes</label>
                                    <textarea class="form-control" id="borrow_notes" name="borrow_notes" rows="3" placeholder="Enter condition notes, special instructions, or comments..." aria-describedby="borrow_notes_help"></textarea>
                                    <div id="borrow_notes_help" class="form-text">Document the condition of the tool and any special instructions given to the borrower.</div>
                                </div>

                                <?php
                                // Alert message component
                                $alertConfig = [
                                    'type' => 'warning',
                                    'message' => 'By completing this handover, you confirm that the tool has been physically handed over to the borrower and all required procedures have been followed.',
                                    'dismissible' => false
                                ];
                                include APP_ROOT . '/views/components/alert_message.php';
                                ?>

                                <?php
                                echo ButtonHelper::renderWorkflowActions(
                                    ['url' => "?route=borrowed-tools/view&id={$borrowedTool['id']}"],
                                    [
                                        'text' => 'Complete Handover',
                                        'type' => 'submit',
                                        'style' => 'primary',
                                        'icon' => 'box-arrow-down'
                                    ]
                                );
                                ?>
                            </form>
                        </div>
                    </div>

                    <?php
                    // Workflow progress using reusable component
                    $workflowConfig = [
                        'currentStage' => 'handover',
                        'completedStages' => ['creation', 'verification', 'approval'],
                        'stages' => [
                            ['name' => 'creation', 'label' => 'Created'],
                            ['name' => 'verification', 'label' => 'Verified'],
                            ['name' => 'approval', 'label' => 'Approved'],
                            ['name' => 'handover', 'label' => 'Handover']
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
