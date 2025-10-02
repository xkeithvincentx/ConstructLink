<?php
/**
 * ConstructLink™ Transfer Return View
 * Return asset from temporary transfer
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-arrow-return-left me-2"></i>Return Asset from Transfer
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Transfer
            </a>
        </div>
    </div>
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
        <!-- Return Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-arrow-return-left me-2"></i>Return Asset to Original Project
                </h5>
            </div>
            <div class="card-body">
                <!-- Transfer Summary -->
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle me-2"></i>Transfer Summary
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?><br>
                            <strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?><br>
                            <strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Transfer Date:</strong> <?= date('M j, Y', strtotime($transfer['transfer_date'])) ?><br>
                            <?php if (!empty($transfer['expected_return'])): ?>
                                <strong>Expected Return:</strong> <?= date('M j, Y', strtotime($transfer['expected_return'])) ?>
                                <?php 
                                $today = date('Y-m-d');
                                $expectedReturn = $transfer['expected_return'];
                                if ($expectedReturn < $today && $transfer['return_status'] === 'not_returned'): ?>
                                    <span class="badge bg-danger ms-1">
                                        <?= abs((strtotime($today) - strtotime($expectedReturn)) / (60*60*24)) ?> days overdue
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <br>
                            <strong>Return Status:</strong> 
                            <?php 
                            $returnStatusBadges = [
                                'not_returned' => 'bg-secondary',
                                'in_return_transit' => 'bg-warning text-dark',
                                'returned' => 'bg-success'
                            ];
                            $returnStatusLabels = [
                                'not_returned' => 'Not Returned',
                                'in_return_transit' => 'In Return Transit',
                                'returned' => 'Returned'
                            ];
                            $currentReturnStatus = $transfer['return_status'] ?? 'not_returned';
                            ?>
                            <span class="badge <?= $returnStatusBadges[$currentReturnStatus] ?? 'bg-secondary' ?>">
                                <?= $returnStatusLabels[$currentReturnStatus] ?? 'Unknown' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (canReturnAsset($transfer, $user)): ?>
                <form method="POST" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Return Notes -->
                    <div class="mb-4">
                        <label for="return_notes" class="form-label">Return Initiation Notes</label>
                        <textarea class="form-control" id="return_notes" name="return_notes" rows="4"
                                  placeholder="Add any notes about the asset condition or return process..."></textarea>
                        <div class="form-text">Optional: Add notes about the asset condition and reason for return initiation.</div>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_return" required>
                            <label class="form-check-label" for="confirm_return">
                                I confirm that the asset is ready to be returned and will be set to in-transit status.
                            </label>
                            <div class="invalid-feedback">
                                Please confirm the return initiation.
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-truck me-1"></i>Initiate Return Process
                        </button>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to return this asset or it is not in a returnable status.
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

                <p><strong>Initiated By:</strong><br>
                <?= htmlspecialchars($transfer['initiated_by_name']) ?></p>

                <?php if (!empty($transfer['approved_by_name'])): ?>
                <p><strong>Approved By:</strong><br>
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
                <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $transfer['asset_status'])) ?></span></p>
            </div>
        </div>

        <!-- Important Notice -->
        <div class="card">
            <div class="card-header bg-warning">
                <h6 class="card-title mb-0 text-dark">
                    <i class="bi bi-exclamation-triangle me-2"></i>Important Notice
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Enhanced Return Process:</strong></p>
                <ul class="small mb-0">
                    <li>Asset will be set to 'in-transit' status during return</li>
                    <li>Origin project manager must confirm receipt</li>
                    <li>Asset will be available at origin only after receipt confirmation</li>
                    <li>Complete audit trail maintained throughout process</li>
                </ul>
            </div>
        </div>

        <div class="mb-3">
            <h6>Who Can Return (MVA Workflow)</h6>
            <ul class="list-unstyled small">
                <li><i class="bi bi-person-check text-primary me-1"></i> System Admin: Any transfer</li>
                <li><i class="bi bi-person-check text-primary me-1"></i> Asset Director/Project Manager: If allowed by workflow</li>
                <li><i class="bi bi-person-check text-primary me-1"></i> Only allowed in <strong>Completed</strong> status for temporary transfers</li>
            </ul>
        </div>
    </div>
</div>

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
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Initiate Asset Return - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?>
