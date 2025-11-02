<?php
/**
 * Transfer Details Partial (for view.php)
 *
 * Displays transfer information cards
 *
 * Required Parameters:
 * @param array $transfer Transfer record data
 */

if (!isset($transfer) || !is_array($transfer)) {
    return;
}
?>

<!-- Transfer Information -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Transfer ID:</strong><br>
                <span class="text-muted">#<?= htmlspecialchars($transfer['id'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="col-md-6">
                <strong>Status:</strong><br>
                <?= TransferHelper::renderStatusBadge($transfer['status']) ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Transfer Type:</strong><br>
                <span class="badge <?= $transfer['transfer_type'] === 'permanent' ? 'bg-primary' : 'bg-info' ?>">
                    <?= htmlspecialchars(ucfirst($transfer['transfer_type']), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="col-md-6">
                <strong>Transfer Date:</strong><br>
                <span class="text-muted"><?= date('M j, Y', strtotime($transfer['transfer_date'])) ?></span>
            </div>
        </div>

        <?php if ($transfer['transfer_type'] === 'temporary'): ?>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Expected Return:</strong><br>
                <?php if (!empty($transfer['expected_return'])): ?>
                    <span class="text-muted"><?= date('M j, Y', strtotime($transfer['expected_return'])) ?></span>
                    <?php
                    $today = date('Y-m-d');
                    $expectedReturn = $transfer['expected_return'];
                    $currentReturnStatus = $transfer['return_status'] ?? 'not_returned';

                    // Only show overdue if not yet returned and completed
                    if ($transfer['status'] === 'Completed' && $currentReturnStatus === 'not_returned'):
                        if ($expectedReturn < $today): ?>
                            <br><span class="badge bg-danger mt-1">
                                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                <?= abs((strtotime($today) - strtotime($expectedReturn)) / (60*60*24)) ?> days overdue
                            </span>
                        <?php elseif ($expectedReturn <= date('Y-m-d', strtotime('+7 days'))): ?>
                            <br><span class="badge bg-warning mt-1">
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>Due soon
                            </span>
                        <?php endif;
                    elseif ($currentReturnStatus === 'returned'): ?>
                        <br><span class="badge bg-success mt-1">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Returned on time
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">Not specified</span>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <strong>Return Status:</strong><br>
                <?= ReturnStatusHelper::renderStatusBadge($transfer['return_status'] ?? 'not_returned') ?>

                <?php if (!empty($transfer['actual_return'])): ?>
                    <br><span class="text-muted small mt-1">
                        Returned: <?= date('M j, Y', strtotime($transfer['actual_return'])) ?>
                    </span>
                <?php elseif (($transfer['return_status'] ?? 'not_returned') === 'in_return_transit' && !empty($transfer['return_initiation_date'])): ?>
                    <?php
                    $daysInTransit = floor((time() - strtotime($transfer['return_initiation_date'])) / (60*60*24));
                    $transitBadgeClass = $daysInTransit > 3 ? 'bg-danger' : ($daysInTransit > 1 ? 'bg-warning text-dark' : 'bg-info');
                    ?>
                    <br><span class="badge <?= $transitBadgeClass ?> mt-1 small">
                        <?= $daysInTransit ?> day<?= $daysInTransit != 1 ? 's' : '' ?> in transit
                    </span>
                <?php elseif ($transfer['status'] === 'Completed' && ($transfer['return_status'] ?? 'not_returned') === 'not_returned'): ?>
                    <br><span class="text-warning small mt-1">
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Awaiting return
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-12">
                <strong>Reason for Transfer:</strong><br>
                <p class="text-muted mt-1"><?= htmlspecialchars($transfer['reason'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <?php if (!empty($transfer['notes'])): ?>
        <div class="row mb-3">
            <div class="col-12">
                <strong>Notes:</strong><br>
                <p class="text-muted mt-1"><?= htmlspecialchars($transfer['notes'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Asset Information -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-box-seam me-2" aria-hidden="true"></i>Asset Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Asset Reference:</strong><br>
                <span class="text-muted"><?= htmlspecialchars($transfer['asset_ref'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="col-md-6">
                <strong>Asset Name:</strong><br>
                <span class="text-muted"><?= htmlspecialchars($transfer['asset_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Category:</strong><br>
                <span class="text-muted"><?= htmlspecialchars($transfer['category_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="col-md-6">
                <strong>Current Status:</strong><br>
                <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $transfer['asset_status'])) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Project Information -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-building me-2" aria-hidden="true"></i>Project Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>From Project:</strong><br>
                <span class="text-muted"><?= htmlspecialchars($transfer['from_project_name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($transfer['from_project_location'])): ?>
                    <br><small class="text-muted"><?= htmlspecialchars($transfer['from_project_location'], ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <strong>To Project:</strong><br>
                <span class="text-muted"><?= htmlspecialchars($transfer['to_project_name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($transfer['to_project_location'])): ?>
                    <br><small class="text-muted"><?= htmlspecialchars($transfer['to_project_location'], ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
