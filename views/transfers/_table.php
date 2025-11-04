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
<div class="transfer-table-wrapper d-none d-md-block">
    <table class="table table-hover transfer-table" id="transfersTable">
        <thead>
            <tr>
                <th scope="col">Reference</th>
                <th scope="col">Asset</th>
                <th scope="col">
                    <span class="d-flex align-items-center gap-1">
                        From
                        <i class="bi bi-arrow-down-up text-muted" style="font-size: 0.7rem;" aria-hidden="true"></i>
                        To
                    </span>
                </th>
                <th scope="col">Type</th>
                <th scope="col">Reason</th>
                <th scope="col">Initiated By</th>
                <th scope="col">Transfer Date</th>
                <th scope="col">Expected Return</th>
                <th scope="col">Return Status</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transfers as $transfer): ?>
                <tr>
                    <td>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>"
                           class="text-decoration-none fw-medium font-monospace"
                           aria-label="View transfer <?= htmlspecialchars($transfer['ref'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> details">
                            <?= htmlspecialchars($transfer['ref'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td>
                        <div>
                            <div class="fw-medium"><?= htmlspecialchars($transfer['asset_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($transfer['asset_ref'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </td>
                    <td>
                        <!-- Vertically Stacked Location Transfer (Space-Optimized) -->
                        <div class="location-transfer">
                            <div class="d-flex align-items-center gap-1">
                                <i class="bi bi-arrow-up-circle text-danger" style="font-size: 0.75rem;" aria-hidden="true" title="From"></i>
                                <span class="location-badge badge bg-light text-dark" title="<?= htmlspecialchars($transfer['from_project_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($transfer['from_project_name'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <i class="bi bi-arrow-down-circle text-success" style="font-size: 0.75rem;" aria-hidden="true" title="To"></i>
                                <span class="location-badge badge bg-light text-dark" title="<?= htmlspecialchars($transfer['to_project_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($transfer['to_project_name'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $transfer['transfer_type'] === 'permanent' ? 'bg-primary' : 'bg-info' ?>">
                            <?= htmlspecialchars(ucfirst($transfer['transfer_type']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <span class="reason-text"
                              title="<?= htmlspecialchars($transfer['reason'], ENT_QUOTES, 'UTF-8') ?>"
                              aria-label="Reason: <?= htmlspecialchars($transfer['reason'], ENT_QUOTES, 'UTF-8') ?>">
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
                        <div class="transfer-actions">
                            <!-- Primary Action: View Details (Always Visible) -->
                            <a href="?route=transfers/view&id=<?= $transfer['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               title="View transfer #<?= $transfer['id'] ?> details"
                               aria-label="View transfer #<?= $transfer['id'] ?> details">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </a>

                            <?php
                            // Use specific permission arrays from roles configuration
                            $verifyRoles = $roleConfig['transfers/verify'] ?? [];
                            $approveRoles = $roleConfig['transfers/approve'] ?? [];
                            $returnRoles = $roleConfig['transfers/returnAsset'] ?? [];
                            $cancelRoles = $roleConfig['transfers/cancel'] ?? [];

                            // Determine workflow action based on status and permissions
                            $workflowAction = null;

                            if (canVerifyTransfer($transfer, $user)):
                                $workflowAction = [
                                    'url' => "?route=transfers/verify&id={$transfer['id']}",
                                    'class' => 'btn-warning',
                                    'icon' => 'search',
                                    'label' => "Verify transfer #{$transfer['id']}"
                                ];
                            elseif (in_array($userRole, $approveRoles) && $transfer['status'] === 'Pending Approval'):
                                $workflowAction = [
                                    'url' => "?route=transfers/approve&id={$transfer['id']}",
                                    'class' => 'btn-success',
                                    'icon' => 'check-circle',
                                    'label' => "Approve transfer #{$transfer['id']}"
                                ];
                            elseif (canDispatchTransfer($transfer, $user)):
                                $workflowAction = [
                                    'url' => "?route=transfers/dispatch&id={$transfer['id']}",
                                    'class' => 'btn-info',
                                    'icon' => 'send',
                                    'label' => "Dispatch transfer #{$transfer['id']}"
                                ];
                            elseif (canReceiveTransfer($transfer, $user)):
                                $workflowAction = [
                                    'url' => "?route=transfers/receive&id={$transfer['id']}",
                                    'class' => 'btn-success',
                                    'icon' => 'check-circle',
                                    'label' => "Complete transfer #{$transfer['id']}"
                                ];
                            elseif (in_array($userRole, $returnRoles) &&
                                    $transfer['transfer_type'] === 'temporary' &&
                                    $transfer['status'] === 'Completed' &&
                                    ($transfer['return_status'] ?? 'not_returned') === 'not_returned'):
                                $workflowAction = [
                                    'url' => "?route=transfers/returnAsset&id={$transfer['id']}",
                                    'class' => 'btn-secondary',
                                    'icon' => 'arrow-return-left',
                                    'label' => "Initiate return for transfer #{$transfer['id']}"
                                ];
                            elseif (canReceiveReturn($transfer, $user)):
                                $workflowAction = [
                                    'url' => "?route=transfers/receive-return&id={$transfer['id']}",
                                    'class' => 'btn-warning',
                                    'icon' => 'box-arrow-in-down',
                                    'label' => "Receive return for transfer #{$transfer['id']}"
                                ];
                            endif;
                            ?>

                            <!-- Workflow Action (If Applicable) -->
                            <?php if ($workflowAction): ?>
                                <a href="<?= $workflowAction['url'] ?>"
                                   class="btn btn-sm <?= $workflowAction['class'] ?>"
                                   title="<?= htmlspecialchars($workflowAction['label']) ?>"
                                   aria-label="<?= htmlspecialchars($workflowAction['label']) ?>">
                                    <i class="bi bi-<?= $workflowAction['icon'] ?>" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>

                            <!-- Secondary Actions Dropdown (Cancel, etc.) -->
                            <?php
                            $hasSecondaryActions = in_array($userRole, $cancelRoles) &&
                                                   in_array($transfer['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'In Transit']);
                            ?>
                            <?php if ($hasSecondaryActions): ?>
                                <div class="btn-group btn-group-sm">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            aria-label="More actions for transfer #<?= $transfer['id'] ?>">
                                        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item text-danger"
                                               href="?route=transfers/cancel&id=<?= $transfer['id'] ?>">
                                                <i class="bi bi-x-circle me-2" aria-hidden="true"></i>Cancel Transfer
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
