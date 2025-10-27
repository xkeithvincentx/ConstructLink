<?php
/**
 * Reusable Workflow Progress Component
 * Displays MVA workflow progress visualization
 *
 * Usage:
 * $workflowConfig = [
 *     'currentStage' => 'verification', // 'creation', 'verification', 'approval', 'handover', 'borrowed'
 *     'completedStages' => ['creation'], // Array of completed stage names
 *     'stages' => [
 *         ['name' => 'creation', 'label' => 'Created'],
 *         ['name' => 'verification', 'label' => 'Verified'],
 *         ['name' => 'approval', 'label' => 'Approved'],
 *         ['name' => 'handover', 'label' => 'Handover']
 *     ]
 * ];
 * include APP_ROOT . '/views/components/workflow_progress.php';
 */

// Default configuration
$workflowConfig = $workflowConfig ?? [];
$currentStage = $workflowConfig['currentStage'] ?? 'creation';
$completedStages = $workflowConfig['completedStages'] ?? [];
$stages = $workflowConfig['stages'] ?? [
    ['name' => 'creation', 'label' => 'Created'],
    ['name' => 'verification', 'label' => 'Verified'],
    ['name' => 'approval', 'label' => 'Approval'],
    ['name' => 'handover', 'label' => 'Borrowed']
];

// Calculate progress percentage
$totalStages = count($stages);
$currentStageIndex = 0;
foreach ($stages as $index => $stage) {
    if ($stage['name'] === $currentStage) {
        $currentStageIndex = $index;
        break;
    }
}
$progressPercent = ($totalStages > 0) ? (($currentStageIndex + 1) / $totalStages) * 100 : 0;

// Progress bar color based on stage
$progressColors = [
    'creation' => 'bg-secondary',
    'verification' => 'bg-warning',
    'approval' => 'bg-info',
    'handover' => 'bg-primary',
    'borrowed' => 'bg-success',
    'returned' => 'bg-success',
    'overdue' => 'bg-danger'
];
$progressColor = $progressColors[$currentStage] ?? 'bg-info';

// Stage labels for progress bar
$stageLabels = [
    'creation' => 'Created',
    'verification' => 'Pending Verification',
    'approval' => 'Pending Approval',
    'handover' => 'Ready for Handover',
    'borrowed' => 'Borrowed',
    'returned' => 'Returned',
    'overdue' => 'Overdue'
];
$progressLabel = $stageLabels[$currentStage] ?? 'In Progress';
?>

<div class="workflow-progress">
    <h6 class="fw-bold">MVA Workflow Status</h6>
    <div class="progress">
        <div class="progress-bar <?= htmlspecialchars($progressColor) ?>"
             role="progressbar"
             style="width: <?= round($progressPercent) ?>%"
             aria-valuenow="<?= round($progressPercent) ?>"
             aria-valuemin="0"
             aria-valuemax="100">
            <small><?= htmlspecialchars($progressLabel) ?></small>
        </div>
    </div>
    <div class="row mt-2">
        <?php
        $colWidth = $totalStages > 0 ? floor(12 / $totalStages) : 3;
        foreach ($stages as $stage):
            $isCompleted = in_array($stage['name'], $completedStages);
            $isCurrent = $stage['name'] === $currentStage;

            if ($isCompleted) {
                $iconClass = 'bi-check-circle text-success';
                $textClass = 'text-muted';
            } elseif ($isCurrent) {
                $iconClass = 'bi-hourglass-split text-warning';
                $textClass = 'text-warning';
            } else {
                $iconClass = 'bi-circle text-muted';
                $textClass = 'text-muted';
            }
        ?>
        <div class="col-<?= $colWidth ?> workflow-stage">
            <small class="<?= $textClass ?>"><?= htmlspecialchars($stage['label']) ?></small>
            <i class="bi <?= $iconClass ?>" aria-hidden="true"></i>
        </div>
        <?php endforeach; ?>
    </div>
</div>
