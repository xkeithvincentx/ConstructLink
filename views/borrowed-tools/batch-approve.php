<?php
/**
 * ConstructLink™ - Batch Approval View
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Asset Director/Finance Director approval of borrowed tool batches
 * Role: Asset Director, Finance Director
 * Workflow: Pending Approval → Approved
 */

$pageTitle = "Approve Borrowed Tool Batch";
$currentPage = 'borrowed-tools';

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="?route=borrowed-tools/batch/view&id=<?= $batch['id'] ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Batch Details
        </a>
    </div>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-check"></i> Approve Critical Borrowed Tool Batch
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Batch Reference:</strong>
                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($batch['batch_reference']) ?></span>
                            </p>
                            <p class="mb-2"><strong>Borrower:</strong> <?= htmlspecialchars($batch['borrower_name']) ?></p>
                            <?php if ($batch['borrower_contact']): ?>
                                <p class="mb-2"><strong>Contact:</strong> <?= htmlspecialchars($batch['borrower_contact']) ?></p>
                            <?php endif; ?>
                            <p class="mb-2"><strong>Expected Return:</strong>
                                <span class="badge bg-info"><?= date('M d, Y', strtotime($batch['expected_return'])) ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Total Items:</strong> <?= $batch['total_items'] ?></p>
                            <p class="mb-2"><strong>Total Quantity:</strong> <?= $batch['total_quantity'] ?></p>
                            <p class="mb-2">
                                <span class="badge bg-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Contains Critical Tools (&gt;₱50,000)
                                </span>
                            </p>
                            <?php if ($batch['verified_by_name']): ?>
                                <p class="mb-0"><strong>Verified By:</strong> <?= htmlspecialchars($batch['verified_by_name']) ?>
                                    <br><small class="text-muted">on <?= date('M d, Y h:i A', strtotime($batch['verification_date'])) ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($batch['purpose']): ?>
                        <hr>
                        <p class="mb-0"><strong>Purpose:</strong><br>
                            <span class="text-muted"><?= nl2br(htmlspecialchars($batch['purpose'])) ?></span>
                        </p>
                    <?php endif; ?>

                    <?php if ($batch['verification_notes']): ?>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <strong>Verifier Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($batch['verification_notes'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Items Review -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Critical Items Requiring Approval</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Equipment Name</th>
                                    <th>Asset Tag</th>
                                    <th>Category</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Value</th>
                                    <th class="text-end">Total Value</th>
                                    <th class="text-center">Risk Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalValue = 0;
                                $criticalCount = 0;
                                foreach ($batch['items'] as $item):
                                    $isCritical = isset($item['acquisition_cost']) && $item['acquisition_cost'] > 50000;
                                    $itemTotal = ($item['acquisition_cost'] ?? 0) * ($item['quantity'] ?? 1);
                                    $totalValue += $itemTotal;
                                    if ($isCritical) $criticalCount++;
                                ?>
                                    <tr class="<?= $isCritical ? 'table-danger' : '' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($item['asset_name']) ?></strong>
                                            <?php if ($isCritical): ?>
                                                <br><small class="text-danger">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> High-Value Equipment
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($item['asset_tag'] ?? 'N/A') ?></code>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($item['equipment_type'] ?? 'N/A') ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $item['quantity'] ?? 1 ?></span>
                                        </td>
                                        <td class="text-end">
                                            <?php if (isset($item['acquisition_cost'])): ?>
                                                <strong>₱<?= number_format($item['acquisition_cost'], 2) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <strong>₱<?= number_format($itemTotal, 2) ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isCritical): ?>
                                                <span class="badge bg-danger">CRITICAL</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">STANDARD</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-danger">
                                <tr>
                                    <th colspan="3" class="text-end">Total Batch Value:</th>
                                    <th class="text-center"><?= $batch['total_quantity'] ?> items</th>
                                    <th colspan="2" class="text-end">
                                        <span class="fs-5">₱<?= number_format($totalValue, 2) ?></span>
                                    </th>
                                    <th class="text-center">
                                        <span class="badge bg-danger"><?= $criticalCount ?> Critical</span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Form -->
    <div class="row">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check"></i> Authorization Decision
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=borrowed-tools/batch/approve" id="approvalForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>High-Value Asset Authorization:</strong>
                            <p class="mb-2 mt-2">
                                This batch contains equipment valued at <strong>₱<?= number_format($totalValue, 2) ?></strong>
                                with <strong><?= $criticalCount ?> critical item(s)</strong> exceeding ₱50,000.
                            </p>
                            <p class="mb-0">
                                Please review the following before authorization:
                            </p>
                            <ul class="mb-0 mt-2">
                                <li>Borrower has legitimate business need for high-value equipment</li>
                                <li>Project manager has verified the request</li>
                                <li>Expected return timeline is appropriate</li>
                                <li>Insurance and liability considerations are addressed</li>
                                <li>Borrower accountability and tracking measures in place</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label for="approval_notes" class="form-label">
                                Authorization Notes <small class="text-muted">(Optional)</small>
                            </label>
                            <textarea
                                class="form-control"
                                id="approval_notes"
                                name="approval_notes"
                                rows="4"
                                placeholder="Add any authorization notes, conditions, or special instructions for release"
                            ></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?route=borrowed-tools/batch/view&id=<?= $batch['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-shield-check"></i> Authorize Release
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    const totalValue = <?= $totalValue ?>;
    const criticalCount = <?= $criticalCount ?>;

    const confirmed = confirm(
        'CRITICAL ASSET AUTHORIZATION\n\n' +
        'Total Value: ₱' + totalValue.toLocaleString('en-PH', {minimumFractionDigits: 2}) + '\n' +
        'Critical Items: ' + criticalCount + '\n\n' +
        'Are you sure you want to authorize the release of these high-value assets?\n\n' +
        'This action approves the warehouseman to physically release the equipment.'
    );

    if (!confirmed) {
        e.preventDefault();
    }
});
</script>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
