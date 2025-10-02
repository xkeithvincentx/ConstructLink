<?php
// Start output buffering to capture content
ob_start();

// Get user data for role-based UI
$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$auth = Auth::getInstance();
?>

<!-- User Creation Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=users/create">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors) && in_array('Full name is required', $errors) ? 'is-invalid' : '' ?>"
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>"
                                   required>
                            <div class="invalid-feedback">
                                Please provide a full name.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors) && (in_array('Username is required', $errors) || in_array('Username already exists', $errors)) ? 'is-invalid' : '' ?>"
                                   id="username" 
                                   name="username" 
                                   value="<?= htmlspecialchars($formData['username'] ?? '') ?>"
                                   required>
                            <div class="invalid-feedback">
                                Please provide a unique username.
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control <?= isset($errors) && (in_array('Email is required', $errors) || in_array('Email already exists', $errors)) ? 'is-invalid' : '' ?>"
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                                   required>
                            <div class="invalid-feedback">
                                Please provide a valid email address.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" 
                                   class="form-control"
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Role and Department -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors) && in_array('Role is required', $errors) ? 'is-invalid' : '' ?>"
                                    id="role_id" 
                                    name="role_id" 
                                    required>
                                <option value="">Select Role</option>
                                <?php if (isset($roles)): ?>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>"
                                                <?= ($formData['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a role.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" 
                                   class="form-control"
                                   id="department" 
                                   name="department" 
                                   value="<?= htmlspecialchars($formData['department'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Project Assignment -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="current_project_id" class="form-label">Project Assignment</label>
                            <select class="form-select" id="current_project_id" name="current_project_id">
                                <option value="">No Project Assignment</option>
                                <?php if (isset($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"
                                                <?= ($formData['current_project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                            <?php if (!empty($project['location'])): ?>
                                                - <?= htmlspecialchars($project['location']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">Assign user to a specific project for role-based access control</div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control <?= isset($errors) && in_array('Password must be at least 8 characters', $errors) ? 'is-invalid' : '' ?>"
                                   id="password" 
                                   name="password" 
                                   required>
                            <div class="form-text">Minimum 8 characters required</div>
                            <div class="invalid-feedback">
                                Password must be at least 8 characters long.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control"
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   required>
                            <div class="invalid-feedback">
                                Passwords must match.
                            </div>
                        </div>
                    </div>

                    <!-- Account Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       <?= ($formData['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Active Account
                                </label>
                            </div>
                            <div class="form-text">Uncheck to create an inactive account</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=users" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i>Create User
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
                    <i class="bi bi-info-circle me-1"></i>User Creation Guide
                </h6>
            </div>
            <div class="card-body">
                <h6>Required Information:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success me-1"></i> Full Name</li>
                    <li><i class="bi bi-check-circle text-success me-1"></i> Username (unique)</li>
                    <li><i class="bi bi-check-circle text-success me-1"></i> Email Address (unique)</li>
                    <li><i class="bi bi-check-circle text-success me-1"></i> Role Assignment</li>
                    <li><i class="bi bi-check-circle text-success me-1"></i> Password (min 8 chars)</li>
                </ul>

                <h6 class="mt-3">Role Descriptions:</h6>
                <div class="small">
                    <p><strong>System Admin:</strong> Full system access</p>
                    <p><strong>Asset Director:</strong> Asset oversight and management</p>
                    <p><strong>Project Manager:</strong> Project-level asset management</p>
                    <p><strong>Warehouseman:</strong> Warehouse operations</p>
                    <p><strong>Site Inventory Clerk:</strong> Site-level operations</p>
                </div>

                <div class="alert alert-info mt-3">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> Users will receive login credentials via email after account creation.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirm').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long');
        return false;
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create User - ConstructLink™';
$pageHeader = 'Create New User';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Users', 'url' => '?route=users'],
    ['title' => 'Create User', 'url' => '?route=users/create']
];

// Page actions
$pageActions = '<a href="?route=users" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back to Users
</a>';

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
