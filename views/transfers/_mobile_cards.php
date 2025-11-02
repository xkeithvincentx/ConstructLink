<?php
/**
 * Transfer Mobile Cards Partial
 *
 * Displays transfers in card format for mobile screens
 *
 * Required Parameters:
 * @param array $transfers List of transfer records
 * @param array $user Current user data
 * @param array $roleConfig Role configuration array
 */

if (!isset($transfers) || !is_array($transfers)) {
    return;
}

$user = $user ?? [];
$roleConfig = $roleConfig ?? [];
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Mobile Card View (visible on small screens) -->
<div class="d-md-none">
    <?php foreach ($transfers as $transfer): ?>
        <div class="card mb-3 transfer-mobile-card">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>"
                           class="text-decoration-none fw-bold"
                           aria-label="View transfer #<?= $transfer['id'] ?> details">
                            #<?= $transfer['id'] ?>
                        </a>
                        <span class="ms-2 badge <?= $transfer['transfer_type'] === 'permanent' ? 'bg-primary' : 'bg-info' ?>">
                            <?= htmlspecialchars(ucfirst($transfer['transfer_type']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <?= TransferHelper::renderStatusBadge($transfer['status']) ?>
                </div>

                <!-- Asset Info -->
                <div class="mb-2">
                    <div class="fw-medium"><?= htmlspecialchars($transfer['asset_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($transfer['asset_ref'], ENT_QUOTES, 'UTF-8') ?></small>
                </div>

                <!-- From → To -->
                <div class="mb-2">
                    <small class="text-muted d-block mb-1">Transfer Route</small>
                    <div class="d-flex align-items-center flex-wrap gap-1">
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($transfer['from_project_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="bi bi-arrow-right text-muted" aria-hidden="true"></i>
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($transfer['to_project_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <?php
                // Get permission roles
                $verifyRoles = $roleConfig['transfers/verify'] ?? [];
                $approveRoles = $roleConfig['transfers/approve'] ?? [];
                $receiveRoles = $roleConfig['transfers/receive'] ?? [];
                $completeRoles = $roleConfig['transfers/complete'] ?? [];
                $returnRoles = $roleConfig['transfers/returnAsset'] ?? [];
                $cancelRoles = $roleConfig['transfers/cancel'] ?? [];
                ?>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?route=transfers/view&id=<?= $transfer['id'] ?>"
                       class="btn btn-sm btn-primary flex-grow-1"
                       aria-label="View transfer #<?= $transfer['id'] ?> details">
                        <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                    </a>

                    <?php if (canVerifyTransfer($transfer, $user)): ?>
                        <a href="?route=transfers/verify&id=<?= $transfer['id'] ?>"
                           class="btn btn-sm btn-warning flex-grow-1"
                           aria-label="Verify transfer #<?= $transfer['id'] ?>">
                            <i class="bi bi-search me-1" aria-hidden="true"></i>Verify
                        </a>
                    <?php endif; ?>

                    <?php if (in_array($userRole, $approveRoles) && $transfer['status'] === 'Pending Approval'): ?>
                        <a href="?route=transfers/approve&id=<?= $transfer['id'] ?>"
                           class="btn btn-sm btn-success flex-grow-1"
                           aria-label="Approve transfer #<?= $transfer['id'] ?>">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Approve
                        </a>
                    <?php endif; ?>

                    <?php if (canDispatchTransfer($transfer, $user)): ?>
                        <a href="?route=transfers/dispatch&id=<?= $transfer['id'] ?>"
                           class="btn btn-sm btn-primary flex-grow-1"
                           aria-label="Dispatch transfer #<?= $transfer['id'] ?>">
                            <i class="bi bi-send me-1" aria-hidden="true"></i>Dispatch
                        </a>
                    <?php endif; ?>

                    <?php if (canReceiveTransfer($transfer, $user)): ?>
                        <a href="?route=transfers/receive&id=<?= $transfer['id'] ?>"
                           class="btn btn-sm btn-success flex-grow-1"
                           aria-label="Complete transfer #<?= $transfer['id'] ?>">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Receive
                        </a>
                    <?php endif; ?>

                    <?php if (in_array($userRole, $returnRoles) &&
                              $transfer['transfer_type'] === 'temporary' &&
                              $transfer['status'] === 'Completed' &&
                              ($transfer['return_status'] ?? 'not_returned') === 'not_returned'): ?>
                        <a href="?route=transfers/returnAsset&id=<?= $transfer['id'] ?>"
                           class="btn btn-sm btn-secondary flex-grow-1"
                           aria-label="Initiate return for transfer #<?= $transfer['id'] ?>">
                            <i class="bi bi-arrow-return-left me-1" aria-hidden="true"></i>Return
                        </a>
                    <?php endif; ?>

                    <?php if (canReceiveReturn($transfer, $user)): ?>
                        <a href="?route=transfers/receive-return&id=<?= $transfer['id'] ?>"
                           class="btn btn-sm btn-warning flex-grow-1"
                           aria-label="Receive return for transfer #<?= $transfer['id'] ?>">
                            <i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Receive Return
                        </a>
                    <?php endif; ?>

                    <?php if (in_array($userRole, $cancelRoles) && in_array($transfer['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'In Transit'])): ?>
                        <a href="?route=transfers/cancel&id=<?= $transfer['id'] ?>"
                           class="btn btn-sm btn-danger flex-grow-1"
                           aria-label="Cancel transfer #<?= $transfer['id'] ?>">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
