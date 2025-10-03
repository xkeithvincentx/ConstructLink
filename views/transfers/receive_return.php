<?php
/**
 * ConstructLink™ Transfer Receive Return View
 * Receive returned asset at origin project
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        <span class="d-none d-sm-inline">Back to Transfer</span>
    </a>
</div>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Receipt Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-box-arrow-in-down me-2"></i>Confirm Asset Receipt at Origin Project
                </h5>
            </div>
            <div class="card-body">
                <!-- Return Transit Summary -->
                <div class="alert alert-warning mb-4">
                    <h6 class="alert-heading">
                        <i class="bi bi-truck me-2"></i>Return Transit Details
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?><br>
                            <strong>Returning From:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?><br>
                            <strong>Returning To:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Return Initiated:</strong> 
                            <?php if (!empty($transfer['return_initiation_date'])): ?>
                                <?= date('M j, Y g:i A', strtotime($transfer['return_initiation_date'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Not available</span>
                            <?php endif; ?><br>
                            
                            <strong>Days in Transit:</strong>
                            <?php if (!empty($transfer['return_initiation_date'])): ?>
                                <?php 
                                $daysInTransit = floor((time() - strtotime($transfer['return_initiation_date'])) / (60*60*24));
                                $badgeClass = $daysInTransit > 3 ? 'bg-danger' : ($daysInTransit > 1 ? 'bg-warning text-dark' : 'bg-success');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $daysInTransit ?> day<?= $daysInTransit != 1 ? 's' : '' ?></span>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($transfer['return_notes'])): ?>
                        <hr>
                        <strong>Return Notes:</strong><br>
                        <div class="small"><?= nl2br(htmlspecialchars($transfer['return_notes'])) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (canReceiveReturn($transfer, $user)): ?>
                <form method="POST" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Asset Condition Assessment -->
                    <div class="mb-4">
                        <label class="form-label">Asset Condition Assessment <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="asset_condition" id="condition_good" value="good" required>
                                    <label class="form-check-label" for="condition_good">
                                        <i class="bi bi-check-circle text-success me-1"></i>Good Condition
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="asset_condition" id="condition_fair" value="fair" required>
                                    <label class="form-check-label" for="condition_fair">
                                        <i class="bi bi-exclamation-circle text-warning me-1"></i>Fair Condition
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="asset_condition" id="condition_damaged" value="damaged" required>
                                    <label class="form-check-label" for="condition_damaged">
                                        <i class="bi bi-exclamation-triangle text-danger me-1"></i>Damaged
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="invalid-feedback">
                            Please assess the asset condition.
                        </div>
                    </div>

                    <!-- Receipt Notes -->
                    <div class="mb-4">
                        <label for="receipt_notes" class="form-label">Receipt Notes</label>
                        <textarea class="form-control" id="receipt_notes" name="receipt_notes" rows="4"
                                  placeholder="Document asset condition, any issues found, or other relevant information..."></textarea>
                        <div class="form-text">Document the asset condition upon receipt and any observations.</div>
                    </div>

                    <!-- Receipt Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_receipt" required>
                            <label class="form-check-label" for="confirm_receipt">
                                I confirm that I have physically received this asset at the origin project and verified its condition.
                            </label>
                            <div class="invalid-feedback">
                                Please confirm the asset receipt.
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Confirm Receipt & Complete Return
                        </button>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to receive this return or it is not in the correct status.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Transfer Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Transfer Information
                </h6>
            </div>
            <div class="card-body">
                <p><strong>Transfer ID:</strong><br>
                #<?= htmlspecialchars($transfer['id']) ?></p>

                <p><strong>Status:</strong><br>
                <span class="badge bg-success"><?= ucfirst($transfer['status']) ?></span></p>

                <p><strong>Transfer Type:</strong><br>
                <span class="badge bg-info"><?= ucfirst($transfer['transfer_type']) ?></span></p>

                <p><strong>Return Status:</strong><br>
                <span class="badge bg-warning text-dark">In Return Transit</span></p>

                <p><strong>Return Initiated By:</strong><br>
                <?= htmlspecialchars($transfer['return_initiated_by_name'] ?? 'Unknown') ?></p>

                <?php if (!empty($transfer['approved_by_name'])): ?>
                <p><strong>Originally Approved By:</strong><br>
                <?= htmlspecialchars($transfer['approved_by_name']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Asset Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box-seam me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <p><strong>Reference:</strong><br>
                <?= htmlspecialchars($transfer['asset_ref']) ?></p>

                <p><strong>Name:</strong><br>
                <?= htmlspecialchars($transfer['asset_name']) ?></p>

                <p><strong>Category:</strong><br>
                <?= htmlspecialchars($transfer['category_name'] ?? 'Unknown') ?></p>

                <p><strong>Current Status:</strong><br>
                <span class="badge bg-warning text-dark">In Transit</span></p>
            </div>
        </div>

        <!-- Return Process Steps -->
        <div class="card">
            <div class="card-header bg-info">
                <h6 class="card-title mb-0 text-white">
                    <i class="bi bi-list-check me-2"></i>Return Process
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success">
                            <i class="bi bi-check"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Return Initiated</h6>
                            <small class="text-muted">Asset set to in-transit</small>
                        </div>
                    </div>
                    
                    <div class="timeline-item active">
                        <div class="timeline-marker bg-primary">
                            <i class="bi bi-arrow-down"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Receive at Origin</h6>
                            <small class="text-muted">Confirm receipt and condition</small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-secondary">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Return Complete</h6>
                            <small class="text-muted">Asset available at origin</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 17px;
    top: 30px;
    bottom: -20px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.timeline-item.completed .timeline-marker {
    background-color: #198754 !important;
}

.timeline-item.active .timeline-marker {
    background-color: #0d6efd !important;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
}

.timeline-content h6 {
    margin: 0 0 4px 0;
    font-size: 14px;
}

.timeline-content small {
    font-size: 12px;
}
</style>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-fill notes based on condition selection
document.addEventListener('DOMContentLoaded', function() {
    const conditionRadios = document.querySelectorAll('input[name="asset_condition"]');
    const notesTextarea = document.getElementById('receipt_notes');
    
    conditionRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const currentNotes = notesTextarea.value;
            let conditionNote = '';
            
            switch(this.value) {
                case 'good':
                    conditionNote = 'Asset received in good condition with no visible damage or issues.';
                    break;
                case 'fair':
                    conditionNote = 'Asset received in fair condition with minor wear but functional.';
                    break;
                case 'damaged':
                    conditionNote = 'Asset received with damage - requires inspection and possible maintenance.';
                    break;
            }
            
            // Only auto-fill if notes are empty
            if (!currentNotes.trim()) {
                notesTextarea.value = conditionNote;
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Receive Returned Asset - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?>