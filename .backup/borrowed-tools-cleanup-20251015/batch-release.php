<?php
/**
 * ConstructLink™ - Batch Release View
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Warehouseman confirmation of physical equipment handover
 * Role: Warehouseman
 * Workflow: Approved → Released
 */

$pageTitle = "Release Borrowed Tool Batch";
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
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-box-arrow-right"></i> Release Equipment to Borrower
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
                        </div>
                    </div>

                    <?php if ($batch['is_critical_batch']): ?>
                        <div class="alert alert-danger mb-0 mt-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Critical Batch:</strong> This batch contains high-value equipment (&gt;₱50,000).
                            Ensure borrower signs handwritten form and verify ID before physical release.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Release Checklist -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Items to Release</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Equipment Name</th>
                                    <th>Asset Tag</th>
                                    <th>Storage Location</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-center">Condition</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batch['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['asset_name']) ?></strong>
                                            <?php if (isset($item['acquisition_cost']) && $item['acquisition_cost'] > 50000): ?>
                                                <br><small class="text-danger">
                                                    <i class="bi bi-exclamation-triangle"></i> High Value - ₱<?= number_format($item['acquisition_cost'], 2) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code class="fs-6"><?= htmlspecialchars($item['asset_tag'] ?? 'N/A') ?></code>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i>
                                                <?= htmlspecialchars($item['current_location'] ?? 'Warehouse') ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary fs-6"><?= $item['quantity'] ?? 1 ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">
                                                <?= htmlspecialchars($item['condition_out'] ?? 'Good') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Release Confirmation Form -->
    <div class="row">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle"></i> Confirm Physical Release
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=borrowed-tools/batch/release" id="releaseForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Release Checklist - Verify Before Handing Over:</strong>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="check1" required>
                                <label class="form-check-label" for="check1">
                                    Borrower identity verified (ID checked if critical batch)
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="check2" required>
                                <label class="form-check-label" for="check2">
                                    All items inspected and in good working condition
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="check3" required>
                                <label class="form-check-label" for="check3">
                                    Quantities verified and match the batch list
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="check4" required>
                                <label class="form-check-label" for="check4">
                                    Handwritten form printed, signed by borrower, and filed
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="check5" required>
                                <label class="form-check-label" for="check5">
                                    Expected return date communicated to borrower
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="release_notes" class="form-label">
                                Release Notes <small class="text-muted">(Optional)</small>
                            </label>
                            <textarea
                                class="form-control"
                                id="release_notes"
                                name="release_notes"
                                rows="3"
                                placeholder="Add any observations during handover (e.g., borrower concerns, special instructions given, etc.)"
                            ></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <p class="mb-2"><small class="text-muted">Release Date & Time</small></p>
                                        <h5 class="mb-0"><?= date('F d, Y - h:i A') ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <p class="mb-2"><small class="text-muted">Expected Return</small></p>
                                        <h5 class="mb-0 text-info">
                                            <?= date('F d, Y', strtotime($batch['expected_return'])) ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?route=borrowed-tools/batch/view&id=<?= $batch['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <div>
                                <a href="?route=borrowed-tools/batch/print&id=<?= $batch['id'] ?>"
                                   target="_blank"
                                   class="btn btn-outline-primary me-2">
                                    <i class="bi bi-printer"></i> Print Handwritten Form
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-box-arrow-right"></i> Confirm Release
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('releaseForm').addEventListener('submit', function(e) {
    const confirmed = confirm(
        'Are you sure you want to confirm the physical release?\n\n' +
        'This action confirms that:\n' +
        '- Equipment has been handed over to the borrower\n' +
        '- Borrower has signed the handwritten form\n' +
        '- All checklist items have been verified\n\n' +
        'The batch status will change to "Released".'
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
