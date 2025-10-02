<?php
/**
 * ConstructLink™ Vendor Bank Management View
 * Manage vendor bank accounts and payment details
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-bank me-2"></i>
        Manage Bank Accounts: <?= htmlspecialchars($vendor['name']) ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=vendors/view&id=<?= $vendorId ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Vendor
        </a>
    </div>
</div>

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
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="row">
    <!-- Bank Accounts List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bank me-2"></i>Bank Accounts
                </h6>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBankModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Bank Account
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($vendorBanks)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bank Name</th>
                                    <th>Account Details</th>
                                    <th>Type</th>
                                    <th>Currency</th>
                                    <th>Category</th>
                                    <th>Branch/SWIFT</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendorBanks as $bank): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($bank['bank_name']) ?></div>
                                            <?php if ($bank['bank_category'] === 'Primary'): ?>
                                                <span class="badge bg-primary">Primary</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <code><?= htmlspecialchars($bank['account_number']) ?></code>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($bank['account_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($bank['account_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= htmlspecialchars($bank['currency']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($bank['bank_category']) ?></td>
                                        <td>
                                            <?php if ($bank['branch']): ?>
                                                <div class="small"><?= htmlspecialchars($bank['branch']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($bank['swift_code']): ?>
                                                <small class="text-muted">SWIFT: <?= htmlspecialchars($bank['swift_code']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!$bank['branch'] && !$bank['swift_code']): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editBank(<?= htmlspecialchars(json_encode($bank)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteBank(<?= $bank['id'] ?>, '<?= htmlspecialchars($bank['bank_name']) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bank display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">No Bank Accounts</h5>
                        <p class="text-muted">Add bank accounts for this vendor to manage payments.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBankModal">
                            <i class="bi bi-plus-circle me-1"></i>Add First Bank Account
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vendor Summary -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Vendor Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Name:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($vendor['name']) ?></dd>
                    
                    <dt class="col-sm-5">Contact:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($vendor['contact_person'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-5">Email:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($vendor['email'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-5">Phone:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($vendor['phone'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-5">Bank Accounts:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-primary"><?= count($vendorBanks) ?></span>
                    </dd>
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
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Set one account as Primary for default payments
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Verify account numbers before saving
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Use appropriate currency for international vendors
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Keep bank information confidential
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Bank Account Modal -->
<div class="modal fade" id="addBankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="addBankForm">
                <?= CSRFProtection::getTokenField() ?>
                <input type="hidden" name="action" value="add_bank">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_number" name="account_number" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_name" name="account_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="account_type" class="form-label">Account Type</label>
                                <select class="form-select" id="account_type" name="account_type">
                                    <option value="Checking">Checking</option>
                                    <option value="Savings">Savings</option>
                                    <option value="Corporate">Corporate</option>
                                    <option value="Current">Current</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="PHP">PHP</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="JPY">JPY</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bank_category" class="form-label">Bank Category</label>
                        <select class="form-select" id="bank_category" name="bank_category">
                            <option value="Primary">Primary</option>
                            <option value="Alternate">Alternate</option>
                            <option value="For International">For International</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="swift_code" class="form-label">SWIFT Code</label>
                                <input type="text" class="form-control" id="swift_code" name="swift_code" placeholder="For international transfers">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="branch" class="form-label">Branch</label>
                                <input type="text" class="form-control" id="branch" name="branch" placeholder="Bank branch location">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Bank Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Bank Account Modal -->
<div class="modal fade" id="editBankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editBankForm">
                <?= CSRFProtection::getTokenField() ?>
                <input type="hidden" name="action" value="update_bank">
                <input type="hidden" name="bank_id" id="edit_bank_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_bank_name" name="bank_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_account_number" name="account_number" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_account_name" name="account_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_account_type" class="form-label">Account Type</label>
                                <select class="form-select" id="edit_account_type" name="account_type">
                                    <option value="Checking">Checking</option>
                                    <option value="Savings">Savings</option>
                                    <option value="Corporate">Corporate</option>
                                    <option value="Current">Current</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_currency" class="form-label">Currency</label>
                                <select class="form-select" id="edit_currency" name="currency">
                                    <option value="PHP">PHP</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="JPY">JPY</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_bank_category" class="form-label">Bank Category</label>
                        <select class="form-select" id="edit_bank_category" name="bank_category">
                            <option value="Primary">Primary</option>
                            <option value="Alternate">Alternate</option>
                            <option value="For International">For International</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_swift_code" class="form-label">SWIFT Code</label>
                                <input type="text" class="form-control" id="edit_swift_code" name="swift_code" placeholder="For international transfers">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_branch" class="form-label">Branch</label>
                                <input type="text" class="form-control" id="edit_branch" name="branch" placeholder="Bank branch location">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Bank Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Bank Account Form -->
<form method="POST" id="deleteBankForm" style="display: none;">
    <?= CSRFProtection::getTokenField() ?>
    <input type="hidden" name="action" value="delete_bank">
    <input type="hidden" name="bank_id" id="delete_bank_id">
</form>

<script>
// Edit bank account
function editBank(bank) {
    document.getElementById('edit_bank_id').value = bank.id;
    document.getElementById('edit_bank_name').value = bank.bank_name;
    document.getElementById('edit_account_number').value = bank.account_number;
    document.getElementById('edit_account_name').value = bank.account_name || '';
    document.getElementById('edit_account_type').value = bank.account_type;
    document.getElementById('edit_currency').value = bank.currency;
    document.getElementById('edit_bank_category').value = bank.bank_category;
    document.getElementById('edit_swift_code').value = bank.swift_code || '';
    document.getElementById('edit_branch').value = bank.branch || '';
    
    new bootstrap.Modal(document.getElementById('editBankModal')).show();
}

// Delete bank account
function deleteBank(bankId, bankName) {
    if (confirm(`Are you sure you want to delete the bank account "${bankName}"? This action cannot be undone.`)) {
        document.getElementById('delete_bank_id').value = bankId;
        document.getElementById('deleteBankForm').submit();
    }
}

// Form validation
document.getElementById('addBankForm').addEventListener('submit', function(e) {
    const bankName = document.getElementById('bank_name').value.trim();
    const accountNumber = document.getElementById('account_number').value.trim();
    const accountName = document.getElementById('account_name').value.trim();
    
    if (!bankName || !accountNumber || !accountName) {
        e.preventDefault();
        alert('Please fill in all required fields (Bank Name, Account Number, and Account Name).');
        return false;
    }
});

document.getElementById('editBankForm').addEventListener('submit', function(e) {
    const bankName = document.getElementById('edit_bank_name').value.trim();
    const accountNumber = document.getElementById('edit_account_number').value.trim();
    const accountName = document.getElementById('edit_account_name').value.trim();
    
    if (!bankName || !accountNumber || !accountName) {
        e.preventDefault();
        alert('Please fill in all required fields (Bank Name, Account Number, and Account Name).');
        return false;
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Manage Bank Accounts - ConstructLink™';
$pageHeader = 'Manage Bank Accounts: ' . htmlspecialchars($vendor['name']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Vendors', 'url' => '?route=vendors'],
    ['title' => 'Vendor Details', 'url' => '?route=vendors/view&id=' . $vendorId],
    ['title' => 'Manage Banks', 'url' => '?route=vendors/manageBanks&vendor_id=' . $vendorId]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
