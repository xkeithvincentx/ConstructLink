<?php
/**
 * Request Workflow Timeline Partial
 *
 * Displays MVA workflow progress timeline showing completed and pending steps.
 * Dynamically adjusts based on maker's role (different approval chains).
 *
 * Required variables:
 * - $workflowChain: Array of workflow steps with completion status
 * - $request: Request data array
 *
 * @version 1.0.0
 */
?>

<!-- MVA Workflow Timeline -->
<div class="card mt-3">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-diagram-3 me-2"></i>Approval Workflow Progress
        </h6>
    </div>
    <div class="card-body">
        <?php if (!empty($workflowChain)): ?>
            <div class="workflow-timeline">
                <?php foreach ($workflowChain as $index => $step): ?>
                    <div class="timeline-step <?= $step['completed'] ? 'completed' : 'pending' ?> <?= $index === count($workflowChain) - 1 ? 'last' : '' ?>">
                        <div class="timeline-marker">
                            <?php if ($step['completed']): ?>
                                <i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-circle text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-step-title">
                                <strong><?= htmlspecialchars($step['step']) ?></strong>
                                <?php if ($step['completed']): ?>
                                    <span class="badge bg-success ms-2">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2">Pending</span>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-step-role text-muted">
                                <small>
                                    <i class="bi bi-person me-1"></i>
                                    Role: <?= htmlspecialchars($step['role']) ?>
                                </small>
                            </div>
                            <?php if ($step['user']): ?>
                                <div class="timeline-step-user">
                                    <small>
                                        <i class="bi bi-check2 me-1"></i>
                                        By: <strong><?= htmlspecialchars($step['user']) ?></strong>
                                    </small>
                                </div>
                            <?php endif; ?>
                            <?php if ($step['timestamp']): ?>
                                <div class="timeline-step-timestamp text-muted">
                                    <small>
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('M j, Y g:i A', strtotime($step['timestamp'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Workflow Progress Bar -->
            <div class="mt-3">
                <?php
                $completedSteps = count(array_filter($workflowChain, function($step) {
                    return $step['completed'];
                }));
                $totalSteps = count($workflowChain);
                $progressPercent = ($totalSteps > 0) ? round(($completedSteps / $totalSteps) * 100) : 0;
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Overall Progress</small>
                    <small class="text-muted"><?= $completedSteps ?> of <?= $totalSteps ?> steps completed</small>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar <?= $progressPercent === 100 ? 'bg-success' : 'bg-primary' ?>"
                         role="progressbar"
                         style="width: <?= $progressPercent ?>%"
                         aria-valuenow="<?= $progressPercent ?>"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
            </div>

            <!-- Workflow Info -->
            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-2"></i>
                <small>
                    <strong>Workflow Type:</strong>
                    <?php
                    $chainLength = $totalSteps;
                    if ($chainLength >= 6) {
                        echo "Full MVA (Warehouseman initiated)";
                    } elseif ($chainLength >= 5) {
                        echo "Standard MVA (Site Inventory Clerk initiated)";
                    } else {
                        echo "Expedited (Project Manager or higher initiated)";
                    }
                    ?>
                </small>
            </div>

        <?php else: ?>
            <p class="text-muted text-center py-3">No workflow information available.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.workflow-timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-step {
    position: relative;
    padding-bottom: 25px;
}

.timeline-step:not(.last)::after {
    content: '';
    position: absolute;
    left: -22px;
    top: 25px;
    width: 2px;
    height: calc(100% - 10px);
    background-color: #dee2e6;
}

.timeline-step.completed:not(.last)::after {
    background-color: #198754;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    font-size: 20px;
}

.timeline-content {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 12px 15px;
}

.timeline-step.completed .timeline-content {
    background-color: #d1e7dd;
    border-left: 3px solid #198754;
}

.timeline-step.pending .timeline-content {
    border-left: 3px solid #6c757d;
}

.timeline-step-title {
    font-size: 15px;
    margin-bottom: 5px;
}

.timeline-step-role,
.timeline-step-user,
.timeline-step-timestamp {
    font-size: 13px;
    margin-top: 3px;
}
</style>
