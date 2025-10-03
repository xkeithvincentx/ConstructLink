<?php
/**
 * ConstructLink™ Enhanced Vendor Edit Form
 * Includes categories, payment terms, tax info, and business details
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" action="?route=vendors/edit&id=<?= $vendor['id'] ?>" id="vendorForm">
    <?= CSRFProtection::getTokenField() ?>
    
    <div class="row">
        <!-- Main Form -->
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($vendor['name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vendor_type" class="form-label">Vendor Type</label>
                            <select class="form-select" id="vendor_type" name="vendor_type">
                                <option value="">Select Vendor Type</option>
                                <option value="Company" <?= ($vendor['vendor_type'] ?? '') === 'Company' ? 'selected' : '' ?>>Company</option>
                                <option value="Sole Proprietor" <?= ($vendor['vendor_type'] ?? '') === 'Sole Proprietor' ? 'selected' : '' ?>>Sole Proprietor</option>
                                <option value="Partnership" <?= ($vendor['vendor_type'] ?? '') === 'Partnership' ? 'selected' : '' ?>>Partnership</option>
                                <option value="Cooperative" <?= ($vendor['vendor_type'] ?? '') === 'Cooperative' ? 'selected' : '' ?>>Cooperative</option>
                                <option value="Government" <?= ($vendor['vendor_type'] ?? '') === 'Government' ? 'selected' : '' ?>>Government Entity</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                   value="<?= htmlspecialchars($vendor['contact_person'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tin" class="form-label">TIN (Tax Identification Number)</label>
                            <input type="text" class="form-control" id="tin" name="tin" 
                                   value="<?= htmlspecialchars($vendor['tin'] ?? '') ?>"
                                   placeholder="e.g., 123-456-789-000">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($vendor['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="rdo_code" class="form-label">RDO Code</label>
                            <input type="text" class="form-control" id="rdo_code" name="rdo_code" 
                                   value="<?= htmlspecialchars($vendor['rdo_code'] ?? '') ?>"
                                   placeholder="e.g., 047">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($vendor['address'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="contact_info" class="form-label">Additional Contact Information</label>
                        <textarea class="form-control" id="contact_info" name="contact_info" rows="2" 
                                  placeholder="Additional contact details, business hours, etc."><?= htmlspecialchars($vendor['contact_info'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-tags me-2"></i>Vendor Categories
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Select the categories that best describe this vendor's products or services.</p>
                    <div class="row">
                        <?php 
                        $categories = [
                            'Tools' => 'Hand tools, power tools, equipment',
                            'Heavy Equipment' => 'Construction machinery, vehicles',
                            'Electrical' => 'Electrical supplies, components',
                            'Plumbing' => 'Pipes, fittings, fixtures',
                            'Materials' => 'Construction materials, supplies',
                            'Safety Equipment' => 'PPE, safety gear',
                            'Hardware' => 'Fasteners, hardware items',
                            'Services' => 'Professional services, consulting',
                            'Technology' => 'IT equipment, software',
                            'Office Supplies' => 'Stationery, office equipment'
                        ];
                        
                        $selectedCategories = [];
                        if (!empty($vendor['categories'])) {
                            if (is_string($vendor['categories'])) {
                                $selectedCategories = json_decode($vendor['categories'], true) ?? [];
                            } else if (is_array($vendor['categories'])) {
                                $selectedCategories = $vendor['categories'];
                            }
                        }
                        ?>
                        
                        <?php foreach ($categories as $category => $description): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="category_<?= strtolower(str_replace(' ', '_', $category)) ?>" 
                                           name="categories[]" value="<?= htmlspecialchars($category) ?>"
                                           <?= in_array($category, $selectedCategories) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="category_<?= strtolower(str_replace(' ', '_', $category)) ?>">
                                        <strong><?= htmlspecialchars($category) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($description) ?></small>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Payment Terms -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2"></i>Payment Terms
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Select the payment terms this vendor accepts.</p>
                    <div class="row">
                        <?php 
                        $selectedPaymentTerms = [];
                        if (!empty($vendor['payment_terms'])) {
                            if (is_string($vendor['payment_terms'])) {
                                $selectedPaymentTerms = json_decode($vendor['payment_terms'], true) ?? [];
                            } else if (is_array($vendor['payment_terms'])) {
                                $selectedPaymentTerms = $vendor['payment_terms'];
                            }
                        }
                        ?>
                        
                        <?php if (!empty($paymentTerms)): ?>
                            <?php foreach ($paymentTerms as $term): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="payment_term_<?= $term['id'] ?>" 
                                               name="payment_terms[]" value="<?= $term['id'] ?>"
                                               <?= in_array($term['id'], $selectedPaymentTerms) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="payment_term_<?= $term['id'] ?>">
                                            <strong><?= htmlspecialchars($term['name'] ?? $term['term_name'] ?? 'Unknown') ?></strong>
                                            <?php if (!empty($term['description'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($term['description']) ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    No payment terms available. Please contact the administrator to set up payment terms.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="?route=vendors/view&id=<?= $vendor['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Vendor
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Vendor Summary -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Vendor Summary
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Vendor ID:</dt>
                        <dd class="col-sm-7">#<?= $vendor['id'] ?></dd>
                        
                        <dt class="col-sm-5">Created:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($vendor['created_at'])) ?></dd>
                        
                        <dt class="col-sm-5">Last Updated:</dt>
                        <dd class="col-sm-7">
                            <?= $vendor['updated_at'] ? date('M j, Y', strtotime($vendor['updated_at'])) : 'Never' ?>
                        </dd>
                        
                        <?php if (isset($vendor['assets_count'])): ?>
                            <dt class="col-sm-5">Assets:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-primary"><?= $vendor['assets_count'] ?></span>
                            </dd>
                        <?php endif; ?>
                        
                        <?php if (isset($vendor['banks']) && !empty($vendor['banks'])): ?>
                            <dt class="col-sm-5">Bank Accounts:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-info"><?= count($vendor['banks']) ?></span>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Guidelines -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Required Information:</h6>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check text-success me-1"></i> Vendor Name</li>
                    </ul>

                    <h6 class="mt-3">Recommended Information:</h6>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-info-circle text-info me-1"></i> Vendor Type</li>
                        <li><i class="bi bi-info-circle text-info me-1"></i> TIN (Required for BIR 2307)</li>
                        <li><i class="bi bi-info-circle text-info me-1"></i> RDO Code</li>
                        <li><i class="bi bi-info-circle text-info me-1"></i> Contact Information</li>
                        <li><i class="bi bi-info-circle text-info me-1"></i> Categories</li>
                        <li><i class="bi bi-info-circle text-info me-1"></i> Payment Terms</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Tip:</strong> Complete vendor information helps with procurement automation and vendor intelligence.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Finance Director'])): ?>
                            <a href="?route=vendors/manageBanks&vendor_id=<?= $vendor['id'] ?>" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-bank me-1"></i>Manage Banks
                            </a>
                        <?php endif; ?>
                        
                        <a href="?route=assets&vendor_id=<?= $vendor['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box me-1"></i>View Assets
                        </a>
                        
                        <a href="?route=procurement/create&vendor_id=<?= $vendor['id'] ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-cart me-1"></i>New Procurement
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Form validation
document.getElementById('vendorForm').addEventListener('submit', function(e) {
    const vendorName = document.getElementById('name').value.trim();
    
    if (!vendorName) {
        e.preventDefault();
        alert('Please provide a vendor name.');
        document.getElementById('name').focus();
        return false;
    }
    
    // Validate email if provided
    const email = document.getElementById('email').value.trim();
    if (email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please provide a valid email address.');
            document.getElementById('email').focus();
            return false;
        }
    }
});

// Auto-format TIN
document.getElementById('tin').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 3) {
        value = value.substring(0, 3) + '-' + value.substring(3);
    }
    if (value.length >= 7) {
        value = value.substring(0, 7) + '-' + value.substring(7);
    }
    if (value.length >= 11) {
        value = value.substring(0, 11) + '-' + value.substring(11, 14);
    }
    e.target.value = value;
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Edit Vendor - ConstructLink™';
$pageHeader = 'Edit Vendor: ' . htmlspecialchars($vendor['name']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Vendors', 'url' => '?route=vendors'],
    ['title' => 'Vendor Details', 'url' => '?route=vendors/view&id=' . $vendor['id']],
    ['title' => 'Edit', 'url' => '?route=vendors/edit&id=' . $vendor['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
