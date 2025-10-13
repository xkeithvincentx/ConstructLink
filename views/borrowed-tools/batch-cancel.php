<?php
/**
 * ConstructLink™ - Batch Cancellation View
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Cancel a borrowed tool batch before or after release
 * Role: Warehouseman, Project Manager, Asset Director
 * Workflow: Any Status → Canceled
 */

$pageTitle = "Cancel Borrowed Tool Batch";
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
                        <i class="bi bi-x-circle"></i> Cancel Borrowed Tool Batch
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Batch Reference:</strong>
                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($batch['batch_reference']) ?></span>
                            </p>
                            <p class="mb-2"><strong>Borrower:</strong> <?= htmlspecialchars($batch['borrower_name']) ?></p>
                            <p class="mb-2"><strong>Current Status:</strong>
                                <span class="badge bg-warning"><?= htmlspecialchars($batch['status']) ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Created On:</strong>
                                <?= date('M d, Y h:i A', strtotime($batch['created_at'])) ?>
                            </p>
                            <p class="mb-2"><strong>Total Items:</strong> <?= $batch['total_items'] ?></p>
                            <p class="mb-2"><strong>Total Quantity:</strong> <?= $batch['total_quantity'] ?></p>
                        </div>
                    </div>

                    <?php if ($batch['status'] === 'Released' || $batch['status'] === 'Partially Returned'): ?>
                        <div class="alert alert-danger mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Warning:</strong> This batch has already been released to the borrower.
                            Canceling will not automatically return the physical equipment.
                            Please ensure items are recovered before canceling.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Items Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Items in This Batch</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Equipment Name</th>
                                    <th>Asset Tag</th>
                                    <th class="text-center">Quantity</th>
                                    <?php if ($batch['status'] === 'Released' || $batch['status'] === 'Partially Returned'): ?>
                                        <th class="text-center">Returned</th>
                                        <th class="text-center">Still Out</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batch['items'] as $item):
                                    $borrowed = $item['quantity'] ?? 1;
                                    $returned = $item['quantity_returned'] ?? 0;
                                    $stillOut = $borrowed - $returned;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['asset_name']) ?></td>
                                        <td><code><?= htmlspecialchars($item['asset_tag'] ?? 'N/A') ?></code></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $borrowed ?></span>
                                        </td>
                                        <?php if ($batch['status'] === 'Released' || $batch['status'] === 'Partially Returned'): ?>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?= $returned ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $stillOut > 0 ? 'danger' : 'secondary' ?>">
                                                    <?= $stillOut ?>
                                                </span>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancellation Form -->
    <div class="row">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-x-octagon"></i> Cancellation Details
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=borrowed-tools/batch/cancel" id="cancelForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Important:</strong> Canceling this batch will:
                            <ul class="mb-0 mt-2">
                                <li>Mark the batch as "Canceled" in the system</li>
                                <?php if ($batch['status'] === 'Released' || $batch['status'] === 'Partially Returned'): ?>
                                    <li class="text-danger">
                                        <strong>NOT automatically return physical equipment</strong> - you must recover items separately
                                    </li>
                                <?php else: ?>
                                    <li>Release all reserved equipment back to available inventory</li>
                                    <li>Prevent any future actions on this batch</li>
                                <?php endif; ?>
                                <li>Record the cancellation in the audit log</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label for="cancellation_reason" class="form-label">
                                Cancellation Reason <span class="text-danger">*</span>
                            </label>
                            <select class="form-select mb-2" id="reason_preset" onchange="setReasonPreset(this.value)">
                                <option value="">-- Select Common Reason --</option>
                                <option value="Borrower request cancelled">Borrower requested cancellation</option>
                                <option value="Equipment no longer available">Equipment no longer available</option>
                                <option value="Duplicate request">Duplicate request</option>
                                <option value="Project postponed">Project postponed/cancelled</option>
                                <option value="Equipment recovered after release">Equipment recovered (was released)</option>
                                <option value="Approval denied">Approval denied by management</option>
                                <option value="Data entry error">Data entry error</option>
                                <option value="Other">Other (specify below)</option>
                            </select>
                            <textarea
                                class="form-control"
                                id="cancellation_reason"
                                name="cancellation_reason"
                                rows="4"
                                required
                                placeholder="Provide detailed reason for cancellation (required)"
                            ></textarea>
                            <small class="text-muted">
                                This reason will be permanently recorded and visible in batch history.
                            </small>
                        </div>

                        <?php if ($batch['status'] === 'Released' || $batch['status'] === 'Partially Returned'): ?>
                            <div class="alert alert-danger">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmRecovery" required>
                                    <label class="form-check-label" for="confirmRecovery">
                                        <strong>I confirm that all physical equipment has been recovered or accounted for</strong>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?route=borrowed-tools/batch/view&id=<?= $batch['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Go Back
                            </a>
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-x-circle"></i> Cancel Batch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setReasonPreset(value) {
    const textarea = document.getElementById('cancellation_reason');
    if (value && value !== 'Other') {
        textarea.value = value;
    } else if (value === 'Other') {
        textarea.value = '';
        textarea.focus();
    }
}

document.getElementById('cancelForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('cancellation_reason').value.trim();

    if (reason.length < 10) {
        e.preventDefault();
        alert('Please provide a detailed cancellation reason (at least 10 characters).');
        return;
    }

    <?php if ($batch['status'] === 'Released' || $batch['status'] === 'Partially Returned'): ?>
        const message = 'CRITICAL ACTION: Cancel Released Batch\n\n' +
            'Batch Reference: <?= $batch['batch_reference'] ?>\n' +
            'Status: <?= $batch['status'] ?>\n\n' +
            'This batch has been released to the borrower.\n' +
            'Canceling will NOT automatically return the physical equipment.\n\n' +
            'Are you absolutely sure you want to cancel this batch?';
    <?php else: ?>
        const message = 'Are you sure you want to cancel this batch?\n\n' +
            'Batch Reference: <?= $batch['batch_reference'] ?>\n' +
            'Status: <?= $batch['status'] ?>\n\n' +
            'This action cannot be undone.';
    <?php endif; ?>

    const confirmed = confirm(message);

    if (!confirmed) {
        e.preventDefault();
    }
});
</script>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
