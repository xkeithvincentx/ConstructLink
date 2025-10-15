<?php
/**
 * ConstructLink™ - Batch Verification View
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Project Manager verification of borrowed tool batches
 * Role: Project Manager
 * Workflow: Pending Verification → Pending Approval (or Approved for basic tools)
 */

$pageTitle = "Verify Borrowed Tool Batch";
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
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="bi bi-clipboard-check"></i> Verify Borrowed Tool Batch
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
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Expected Return:</strong>
                                <span class="badge bg-info"><?= date('M d, Y', strtotime($batch['expected_return'])) ?></span>
                            </p>
                            <p class="mb-2"><strong>Total Items:</strong> <?= $batch['total_items'] ?></p>
                            <p class="mb-2"><strong>Total Quantity:</strong> <?= $batch['total_quantity'] ?></p>
                            <?php if ($batch['is_critical_batch']): ?>
                                <p class="mb-0">
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Contains Critical Tools (&gt;₱50,000)
                                    </span>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Items to Verify -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Items to Verify</h5>
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
                                    <th class="text-end">Value</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalValue = 0;
                                foreach ($batch['items'] as $item):
                                    $isCritical = isset($item['acquisition_cost']) && $item['acquisition_cost'] > 50000;
                                    $totalValue += ($item['acquisition_cost'] ?? 0) * ($item['quantity'] ?? 1);
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['asset_name']) ?></strong>
                                            <?php if ($isCritical): ?>
                                                <br><small class="text-danger">
                                                    <i class="bi bi-exclamation-triangle"></i> Critical Tool
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
                                                ₱<?= number_format($item['acquisition_cost'], 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">Available</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total Estimated Value:</th>
                                    <th class="text-center"><?= $batch['total_quantity'] ?> items</th>
                                    <th class="text-end">₱<?= number_format($totalValue, 2) ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Form -->
    <div class="row">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle"></i> Verification Decision
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=borrowed-tools/batch/verify" id="verificationForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Verification Checklist:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Borrower identity and authorization confirmed</li>
                                <li>All requested items are available and in good condition</li>
                                <li>Expected return date is reasonable for the stated purpose</li>
                                <li>Critical tools (&gt;₱50,000) require additional approval from Asset/Finance Director</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label for="verification_notes" class="form-label">
                                Verification Notes <small class="text-muted">(Optional)</small>
                            </label>
                            <textarea
                                class="form-control"
                                id="verification_notes"
                                name="verification_notes"
                                rows="4"
                                placeholder="Add any notes about the verification (e.g., special conditions, observations, etc.)"
                            ></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?route=borrowed-tools/batch/view&id=<?= $batch['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i> Approve Verification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('verificationForm').addEventListener('submit', function(e) {
    const confirmed = confirm(
        'Are you sure you want to verify this batch?\n\n' +
        'This will move the batch to the next approval stage.'
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
