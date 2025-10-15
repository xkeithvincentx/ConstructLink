<?php
/**
 * ConstructLink™ - Batch Return View
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Process partial or full returns of borrowed equipment
 * Role: Warehouseman
 * Workflow: Released → Partially Returned → Returned
 */

$pageTitle = "Process Batch Return";
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
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-box-arrow-in-left"></i> Process Equipment Return
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Batch Reference:</strong>
                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($batch['batch_reference']) ?></span>
                            </p>
                            <p class="mb-2"><strong>Borrower:</strong> <?= htmlspecialchars($batch['borrower_name']) ?></p>
                            <p class="mb-2"><strong>Released On:</strong>
                                <?= date('M d, Y', strtotime($batch['release_date'])) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Expected Return:</strong>
                                <span class="badge bg-<?= strtotime($batch['expected_return']) < time() ? 'danger' : 'info' ?>">
                                    <?= date('M d, Y', strtotime($batch['expected_return'])) ?>
                                    <?php if (strtotime($batch['expected_return']) < time()): ?>
                                        <i class="bi bi-exclamation-triangle"></i> OVERDUE
                                    <?php endif; ?>
                                </span>
                            </p>
                            <p class="mb-2"><strong>Total Items:</strong> <?= $batch['total_items'] ?></p>
                            <p class="mb-2"><strong>Total Quantity:</strong> <?= $batch['total_quantity'] ?></p>
                        </div>
                    </div>

                    <?php if (strtotime($batch['expected_return']) < time()): ?>
                        <div class="alert alert-danger mb-0 mt-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Overdue Return:</strong> This batch is
                            <?php
                            $daysOverdue = floor((time() - strtotime($batch['expected_return'])) / 86400);
                            echo $daysOverdue . ' day' . ($daysOverdue != 1 ? 's' : '');
                            ?>
                            overdue. Please verify all equipment condition carefully.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Processing Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Items Return Processing</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=borrowed-tools/batch/return" id="returnForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Return Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Enter the quantity being returned for each item</li>
                                <li>Inspect and select the condition of returned equipment</li>
                                <li>Partial returns are supported - you can return items in multiple batches</li>
                                <li>All items must be returned before batch can be closed</li>
                            </ul>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Equipment Name</th>
                                        <th>Asset Tag</th>
                                        <th class="text-center">Borrowed</th>
                                        <th class="text-center">Already Returned</th>
                                        <th class="text-center">Remaining</th>
                                        <th class="text-center">Returning Now</th>
                                        <th>Condition</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($batch['items'] as $item):
                                        $borrowed = $item['quantity'] ?? 1;
                                        $returned = $item['quantity_returned'] ?? 0;
                                        $remaining = $borrowed - $returned;
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($item['asset_name']) ?></strong>
                                                <?php if ($remaining == 0): ?>
                                                    <br><small class="text-success">
                                                        <i class="bi bi-check-circle-fill"></i> Fully Returned
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($item['asset_tag'] ?? 'N/A') ?></code>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= $borrowed ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?= $returned ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $remaining > 0 ? 'warning' : 'secondary' ?>">
                                                    <?= $remaining ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($remaining > 0): ?>
                                                    <input
                                                        type="number"
                                                        name="quantity_returned_<?= $item['id'] ?>"
                                                        class="form-control form-control-sm text-center return-quantity-input"
                                                        min="0"
                                                        max="<?= $remaining ?>"
                                                        value="<?= $remaining ?>"
                                                        style="width: 80px; display: inline-block;"
                                                        data-item-id="<?= $item['id'] ?>"
                                                    >
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($remaining > 0): ?>
                                                    <select
                                                        name="condition_in_<?= $item['id'] ?>"
                                                        class="form-select form-select-sm condition-select"
                                                        data-item-id="<?= $item['id'] ?>"
                                                        style="width: 150px;"
                                                    >
                                                        <option value="Good">Good</option>
                                                        <option value="Fair">Fair</option>
                                                        <option value="Poor">Poor</option>
                                                        <option value="Damaged">Damaged</option>
                                                        <option value="Lost">Lost/Missing</option>
                                                    </select>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <?= htmlspecialchars($item['condition_in'] ?? 'Good') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3 mt-4">
                            <label for="return_notes" class="form-label">
                                Return Notes <small class="text-muted">(Optional)</small>
                            </label>
                            <textarea
                                class="form-control"
                                id="return_notes"
                                name="return_notes"
                                rows="3"
                                placeholder="Add any notes about the return (e.g., damage descriptions, missing items, borrower comments, etc.)"
                            ></textarea>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Before Processing Return:</strong>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="verifyCheck" required>
                                <label class="form-check-label" for="verifyCheck">
                                    I have physically inspected all returned items and verified quantities and conditions
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?route=borrowed-tools/batch/view&id=<?= $batch['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-box-arrow-in-left"></i> Process Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('returnForm').addEventListener('submit', function(e) {
    // Calculate total items being returned
    let returningCount = 0;
    const quantityInputs = document.querySelectorAll('.return-quantity-input');
    quantityInputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        if (qty > 0) {
            returningCount += qty;
        }
    });

    if (returningCount === 0) {
        e.preventDefault();
        alert('Please enter at least one item to return.');
        return;
    }

    const confirmed = confirm(
        'Are you sure you want to process this return?\n\n' +
        'Total items being returned: ' + returningCount + '\n\n' +
        'Please ensure you have:\n' +
        '- Physically inspected all items\n' +
        '- Verified quantities match handwritten form\n' +
        '- Assessed condition accurately\n' +
        '- Noted any damage or issues'
    );

    if (!confirmed) {
        e.preventDefault();
    }
});

// Auto-disable condition select when quantity is 0
document.querySelectorAll('.return-quantity-input').forEach(input => {
    input.addEventListener('change', function() {
        const itemId = this.dataset.itemId;
        const conditionSelect = document.querySelector(`.condition-select[data-item-id="${itemId}"]`);
        const qty = parseInt(this.value) || 0;

        if (conditionSelect) {
            conditionSelect.disabled = qty === 0;
            if (qty === 0) {
                conditionSelect.classList.add('text-muted');
            } else {
                conditionSelect.classList.remove('text-muted');
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
