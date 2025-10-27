<?php
/**
 * ConstructLink™ - Cancel Borrowed Tool Request
 * MVA Workflow: Cancellation at any stage before borrowed
 * REFACTORED: Using reusable components for DRY principles
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Load helpers
require_once APP_ROOT . '/helpers/ButtonHelper.php';

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
                    <?php
                    // Display errors using reusable component
                    if (!empty($errors)) {
                        $alertConfig = [
                            'type' => 'danger',
                            'messages' => $errors
                        ];
                        include APP_ROOT . '/views/components/alert_message.php';
                    }

                    // Display warning message
                    $alertConfig = [
                        'type' => 'warning',
                        'title' => 'Warning:',
                        'message' => 'You are about to cancel this borrowed tool request. This action cannot be undone.',
                        'dismissible' => false
                    ];
                    include APP_ROOT . '/views/components/alert_message.php';
                    ?>

                    <!-- Tool Details -->
                    <?= ViewHelper::renderToolDetailsTable($borrowedTool) ?>

                    <!-- Workflow Status -->
                    <?php if (!empty($borrowedTool['verified_by_name'])): ?>
                    <div class="workflow-notes">
                        <h6 class="fw-bold">Workflow Progress:</h6>
                        <p><strong>Verified by:</strong> <?= htmlspecialchars($borrowedTool['verified_by_name']) ?>
                           on <?= date('M d, Y g:i A', strtotime($borrowedTool['verification_date'])) ?></p>
                        <?php if (!empty($borrowedTool['approved_by_name'])): ?>
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
                            <form method="POST" action="?route=borrowed-tools/cancel&id=<?= $borrowedTool['id'] ?>" class="checklist-form">
                                <?= CSRFProtection::getTokenField() ?>

                                <?php
                                // Build checklist items
                                $checklistItems = [
                                    ['id' => 'confirm_cancellation', 'label' => 'I confirm that I want to cancel this borrowed tool request', 'required' => true],
                                    ['id' => 'understand_implications', 'label' => 'I understand this action cannot be undone and will require a new request', 'required' => true]
                                ];

                                $checklistConfig = [
                                    'title' => 'Cancellation Confirmation:',
                                    'items' => $checklistItems
                                ];
                                include APP_ROOT . '/views/components/checklist_form.php';
                                ?>

                                <div class="mb-3">
                                    <label for="cancellation_reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" required placeholder="Please provide a reason for cancelling this request..." aria-describedby="cancellation_reason_help"></textarea>
                                    <div id="cancellation_reason_help" class="form-text">Please provide a clear reason for cancelling this request for record keeping purposes.</div>
                                </div>

                                <?php
                                // Display important notice
                                $alertConfig = [
                                    'type' => 'danger',
                                    'title' => 'Important:',
                                    'messages' => [
                                        'Permanently cancel the borrowing request',
                                        'Make the asset available for other requests',
                                        'Require a new request to be submitted if needed later',
                                        'Create an audit record of the cancellation'
                                    ],
                                    'dismissible' => false
                                ];
                                include APP_ROOT . '/views/components/alert_message.php';
                                ?>

                                <?php
                                echo ButtonHelper::renderWorkflowActions(
                                    ['url' => "?route=borrowed-tools/view&id={$borrowedTool['id']}"],
                                    [
                                        'text' => 'Cancel Request',
                                        'type' => 'submit',
                                        'style' => 'danger',
                                        'icon' => 'x-circle'
                                    ]
                                );
                                ?>
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

// Load module-specific assets
AssetHelper::loadModuleCSS('borrowed-tools-mva-workflows');

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
