<?php
/**
 * Transfer Table Partial (Desktop View)
 *
 * Displays transfers in a table format for desktop screens
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

<!-- Desktop Table View (hidden on small screens) -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover" id="transfersTable">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Asset</th>
                <th scope="col">From → To</th>
                <th scope="col">Type</th>
                <th scope="col">Reason</th>
                <th scope="col">Initiated By</th>
                <th scope="col">Transfer Date</th>
                <th scope="col">Expected Return</th>
                <th scope="col">Return Status</th>
                <th scope="col">Status</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transfers as $transfer): ?>
                <tr>
                    <td>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>"
                           class="text-decoration-none"
                           aria-label="View transfer #<?= $transfer['id'] ?> details">
                            #<?= $transfer['id'] ?>
                        </a>
                    </td>
                    <td>
                        <div>
                            <div class="fw-medium"><?= htmlspecialchars($transfer['asset_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($transfer['asset_ref'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($transfer['from_project_name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <i class="bi bi-arrow-right mx-2 text-muted" aria-hidden="true"></i>
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($transfer['to_project_name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $transfer['transfer_type'] === 'permanent' ? 'bg-primary' : 'bg-info' ?>">
                            <?= htmlspecialchars(ucfirst($transfer['transfer_type']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width: 200px;"
                              title="<?= htmlspecialchars($transfer['reason'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($transfer['reason'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <div>
                            <div class="fw-medium"><?= htmlspecialchars($transfer['initiated_by_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted"><?= date('M j, Y', strtotime($transfer['created_at'])) ?></small>
                        </div>
                    </td>
                    <td>
                        <?php if ($transfer['transfer_date']): ?>
                            <small><?= date('M j, Y', strtotime($transfer['transfer_date'])) ?></small>
                        <?php else: ?>
                            <small class="text-muted">Not set</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($transfer['expected_return'] && $transfer['transfer_type'] === 'temporary'): ?>
                            <small class="<?= strtotime($transfer['expected_return']) < time() && $transfer['status'] === 'Completed' ? 'text-danger fw-bold' : '' ?>">
                                <?= date('M j, Y', strtotime($transfer['expected_return'])) ?>
                                <?php if (strtotime($transfer['expected_return']) < time() && $transfer['status'] === 'Completed'): ?>
                                    <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue" aria-label="Overdue return"></i>
                                <?php endif; ?>
                            </small>
                        <?php else: ?>
                            <small class="text-muted">N/A</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($transfer['transfer_type'] === 'temporary' && $transfer['status'] === 'Completed'): ?>
                            <?= ReturnStatusHelper::renderStatusBadge($transfer['return_status'] ?? 'not_returned') ?>

                            <?php if (($transfer['return_status'] ?? 'not_returned') === 'in_return_transit' && !empty($transfer['return_initiation_date'])): ?>
                                <?php
                                $daysInTransit = floor((time() - strtotime($transfer['return_initiation_date'])) / (60*60*24));
                                $transitBadgeClass = $daysInTransit > 3 ? 'bg-danger' : ($daysInTransit > 1 ? 'bg-warning text-dark' : 'bg-info');
                                ?>
                                <br><span class="badge <?= $transitBadgeClass ?> mt-1" style="font-size: 0.7em;">
                                    <?= $daysInTransit ?> day<?= $daysInTransit != 1 ? 's' : '' ?> in transit
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <small class="text-muted">N/A</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= TransferHelper::renderStatusBadge($transfer['status']) ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Transfer actions">
                            <a href="?route=transfers/view&id=<?= $transfer['id'] ?>"
                               class="btn btn-outline-primary"
                               aria-label="View transfer #<?= $transfer['id'] ?> details">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </a>

                            <!-- MVA Workflow Actions -->
                            <?php
                            // Use specific permission arrays from roles configuration
                            $verifyRoles = $roleConfig['transfers/verify'] ?? [];
                            $approveRoles = $roleConfig['transfers/approve'] ?? [];
                            $returnRoles = $roleConfig['transfers/returnAsset'] ?? [];
                            $cancelRoles = $roleConfig['transfers/cancel'] ?? [];
                            ?>

                            <?php if (canVerifyTransfer($transfer, $user)): ?>
                                <a href="?route=transfers/verify&id=<?= $transfer['id'] ?>"
                                   class="btn btn-outline-warning"
                                   aria-label="Verify transfer #<?= $transfer['id'] ?>">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($userRole, $approveRoles) && $transfer['status'] === 'Pending Approval'): ?>
                                <a href="?route=transfers/approve&id=<?= $transfer['id'] ?>"
                                   class="btn btn-outline-success"
                                   aria-label="Approve transfer #<?= $transfer['id'] ?>">
                                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (canDispatchTransfer($transfer, $user)): ?>
                                <a href="?route=transfers/dispatch&id=<?= $transfer['id'] ?>"
                                   class="btn btn-outline-primary"
                                   aria-label="Dispatch transfer #<?= $transfer['id'] ?>">
                                    <i class="bi bi-send" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (canReceiveTransfer($transfer, $user)): ?>
                                <a href="?route=transfers/receive&id=<?= $transfer['id'] ?>"
                                   class="btn btn-outline-success"
                                   aria-label="Complete transfer #<?= $transfer['id'] ?>">
                                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($userRole, $returnRoles) &&
                                      $transfer['transfer_type'] === 'temporary' &&
                                      $transfer['status'] === 'Completed' &&
                                      ($transfer['return_status'] ?? 'not_returned') === 'not_returned'): ?>
                                <a href="?route=transfers/returnAsset&id=<?= $transfer['id'] ?>"
                                   class="btn btn-outline-secondary"
                                   aria-label="Initiate return for transfer #<?= $transfer['id'] ?>">
                                    <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (canReceiveReturn($transfer, $user)): ?>
                                <a href="?route=transfers/receive-return&id=<?= $transfer['id'] ?>"
                                   class="btn btn-outline-warning"
                                   aria-label="Receive return for transfer #<?= $transfer['id'] ?>">
                                    <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($userRole, $cancelRoles) && in_array($transfer['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'In Transit'])): ?>
                                <a href="?route=transfers/cancel&id=<?= $transfer['id'] ?>"
                                   class="btn btn-outline-danger"
                                   aria-label="Cancel transfer #<?= $transfer['id'] ?>">
                                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
