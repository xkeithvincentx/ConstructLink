<?php
/**
 * BIR Form 2307 View Page
 * Display details of a single form
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
?>

<div class="container-fluid py-4">
    <!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

        <div class="d-flex gap-2">
            <a href="?route=bir2307/print-preview&id=<?= $form['id'] ?>" 
               target="_blank" 
               class="btn btn-primary">
                <i class="bi bi-printer me-1"></i>Print Form
            </a>
            <?php if ($form['status'] === 'Generated'): ?>
            <button class="btn btn-success" 
                    onclick="updateFormStatus(<?= $form['id'] ?>, 'Submitted')">
                <i class="bi bi-check-circle me-1"></i>Mark as Submitted
            </button>
            <?php endif; ?>
            <a href="?route=procurement-orders/view&id=<?= $form['procurement_order_id'] ?>" 
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to PO
            </a>
        </div>
    </div>

    <!-- Form Details Card -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Form Number: <?= htmlspecialchars($form['form_number']) ?></h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted">Form Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Form Number:</strong></td>
                            <td><?= htmlspecialchars($form['form_number']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Period:</strong></td>
                            <td>
                                <?= date('M d, Y', strtotime($form['period_from'])) ?> - 
                                <?= date('M d, Y', strtotime($form['period_to'])) ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Quarter:</strong></td>
                            <td><?= htmlspecialchars($form['quarter']) ?> Quarter <?= htmlspecialchars($form['year']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <?php
                                $statusClass = [
                                    'Draft' => 'secondary',
                                    'Generated' => 'warning',
                                    'Printed' => 'info',
                                    'Submitted' => 'success',
                                    'Cancelled' => 'danger'
                                ][$form['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($form['status']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Generated:</strong></td>
                            <td>
                                <?= $form['generated_at'] ? date('M d, Y h:i A', strtotime($form['generated_at'])) : 'N/A' ?>
                            </td>
                        </tr>
                        <?php if ($form['submitted_at']): ?>
                        <tr>
                            <td><strong>Submitted:</strong></td>
                            <td><?= date('M d, Y h:i A', strtotime($form['submitted_at'])) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-muted">Related Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>PO Number:</strong></td>
                            <td>
                                <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>">
                                    <?= htmlspecialchars($procurementOrder['po_number']) ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>PO Title:</strong></td>
                            <td><?= htmlspecialchars($procurementOrder['title']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Vendor:</strong></td>
                            <td><?= htmlspecialchars($vendor['name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Vendor TIN:</strong></td>
                            <td><?= htmlspecialchars($vendor['tin'] ?: 'Not provided') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Vendor Type:</strong></td>
                            <td><?= htmlspecialchars($vendor['vendor_type'] ?? 'Company') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Income Payments Details -->
            <h6 class="text-muted mb-3">Income Payments and Taxes Withheld</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>ATC Code</th>
                            <th>Month</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Tax Withheld</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($form['income_payments'] as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['description']) ?></td>
                            <td><?= htmlspecialchars($payment['atc_code']) ?></td>
                            <td><?= date('F', mktime(0, 0, 0, $payment['month'], 1)) ?></td>
                            <td class="text-end">₱ <?= number_format($payment['amount'], 2) ?></td>
                            <td class="text-end">₱ <?= number_format($payment['tax_withheld'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3">Total</th>
                            <th class="text-end">₱ <?= number_format($form['total_amount'], 2) ?></th>
                            <th class="text-end">₱ <?= number_format($form['total_tax_withheld'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes -->
            <?php if ($form['notes']): ?>
            <div class="mt-4">
                <h6 class="text-muted">Notes</h6>
                <div class="alert alert-info">
                    <?= nl2br(htmlspecialchars($form['notes'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateFormStatus(formId, status) {
    if (!confirm(`Mark this form as ${status}?`)) {
        return;
    }
    
    fetch('?route=bir2307/update-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `form_id=${formId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error updating status');
        console.error(error);
    });
}
</script>