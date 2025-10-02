<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-person-badge me-2"></i>
        Create New Client
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=clients" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Clients
        </a>
    </div>
</div>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Success Messages -->
<?php if (!empty($messages)): ?>
    <div class="alert alert-success">
        <?php foreach ($messages as $message): ?>
            <div><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create Client Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Client Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=clients/create">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="row">
                        <!-- Client Name -->
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors) && in_array('Client name is required', $errors) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($formData['name'] ?? '') ?>" 
                                   required>
                            <div class="invalid-feedback">
                                Please provide a client name.
                            </div>
                        </div>

                        <!-- Company Type -->
                        <div class="col-md-6 mb-3">
                            <label for="company_type" class="form-label">Company Type</label>
                            <select class="form-select" id="company_type" name="company_type">
                                <option value="">Select Company Type</option>
                                <option value="Corporation" <?= ($formData['company_type'] ?? '') === 'Corporation' ? 'selected' : '' ?>>Corporation</option>
                                <option value="LLC" <?= ($formData['company_type'] ?? '') === 'LLC' ? 'selected' : '' ?>>LLC</option>
                                <option value="Partnership" <?= ($formData['company_type'] ?? '') === 'Partnership' ? 'selected' : '' ?>>Partnership</option>
                                <option value="Sole Proprietorship" <?= ($formData['company_type'] ?? '') === 'Sole Proprietorship' ? 'selected' : '' ?>>Sole Proprietorship</option>
                                <option value="Government" <?= ($formData['company_type'] ?? '') === 'Government' ? 'selected' : '' ?>>Government</option>
                                <option value="Non-Profit" <?= ($formData['company_type'] ?? '') === 'Non-Profit' ? 'selected' : '' ?>>Non-Profit</option>
                                <option value="Other" <?= ($formData['company_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Contact Person -->
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="contact_person" 
                                   name="contact_person" 
                                   value="<?= htmlspecialchars($formData['contact_person'] ?? '') ?>">
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" 
                               class="form-control <?= isset($errors) && in_array('Invalid email format', $errors) ? 'is-invalid' : '' ?>" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                        <div class="invalid-feedback">
                            Please provide a valid email address.
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" 
                                  id="address" 
                                  name="address" 
                                  rows="3"><?= htmlspecialchars($formData['address'] ?? '') ?></textarea>
                    </div>

                    <!-- Contact Information -->
                    <div class="mb-3">
                        <label for="contact_info" class="form-label">Additional Contact Information</label>
                        <textarea class="form-control" 
                                  id="contact_info" 
                                  name="contact_info" 
                                  rows="3" 
                                  placeholder="Additional contact details, business hours, etc."><?= htmlspecialchars($formData['contact_info'] ?? '') ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=clients" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Create Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Panel -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Required Information:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success me-1"></i> Client Name</li>
                </ul>

                <h6 class="mt-3">Optional Information:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-info-circle text-info me-1"></i> Company Type</li>
                    <li><i class="bi bi-info-circle text-info me-1"></i> Contact Person</li>
                    <li><i class="bi bi-info-circle text-info me-1"></i> Phone Number</li>
                    <li><i class="bi bi-info-circle text-info me-1"></i> Email Address</li>
                    <li><i class="bi bi-info-circle text-info me-1"></i> Physical Address</li>
                    <li><i class="bi bi-info-circle text-info me-1"></i> Additional Contact Info</li>
                </ul>

                <div class="alert alert-info mt-3">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>About Clients:</strong> Clients are organizations that supply assets to your projects. These are typically client-supplied equipment or materials.
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
                    <a href="?route=clients" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list"></i> View All Clients
                    </a>
                    <a href="?route=assets&is_client_supplied=1" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-box"></i> Client-Supplied Assets
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create Client - ConstructLink™';
$pageHeader = 'Create New Client';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Clients', 'url' => '?route=clients'],
    ['title' => 'Create Client', 'url' => '?route=clients/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
