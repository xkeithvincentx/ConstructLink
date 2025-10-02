<?php
// Start output buffering to capture content
ob_start();

// Get user data for role-based UI
$currentUser = Auth::getInstance()->getCurrentUser();
$userRole = $currentUser['role_name'] ?? 'Guest';
$auth = Auth::getInstance();
?>

<!-- Edit User Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=users/edit&id=<?= $formData['id'] ?? 0 ?>">
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
                            <label for="current_project_id" class="form-label">Current Project Assignment</label>
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
                            <div class="form-text">Assign user to a specific project for role-based access</div>
                        </div>
                        <div class="col-md-6">
                            <!-- Empty column for spacing -->
                        </div>
                    </div>

                    <!-- Password Change (Optional) -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Change Password (Optional)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" 
                                           class="form-control <?= isset($errors) && in_array('Password must be at least 8 characters', $errors) ? 'is-invalid' : '' ?>"
                                           id="password" 
                                           name="password">
                                    <div class="form-text">Leave blank to keep current password</div>
                                    <div class="invalid-feedback">
                                        Password must be at least 8 characters long.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirm" class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           class="form-control"
                                           id="password_confirm" 
                                           name="password_confirm">
                                    <div class="invalid-feedback">
                                        Passwords must match.
                                    </div>
                                </div>
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
                                       <?= ($formData['is_active'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Active Account
                                </label>
                            </div>
                            <div class="form-text">Uncheck to deactivate this account</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=users/view&id=<?= $formData['id'] ?? 0 ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-1"></i>User Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h6 class="text-muted">User ID</h6>
                            <h4 class="mb-0"><?= $formData['id'] ?? 'N/A' ?></h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Current Role</h6>
                        <h6 class="mb-0">
                            <span class="badge bg-primary"><?= htmlspecialchars($formData['role_name'] ?? 'No Role') ?></span>
                        </h6>
                    </div>
                </div>
                
                <hr>
                
                <div class="small">
                    <p><strong>Created:</strong> <?= isset($formData['created_at']) ? date('M j, Y g:i A', strtotime($formData['created_at'])) : 'N/A' ?></p>
                    <p><strong>Last Login:</strong> <?= isset($formData['last_login']) && $formData['last_login'] ? date('M j, Y g:i A', strtotime($formData['last_login'])) : 'Never' ?></p>
                    <p><strong>Status:</strong> 
                        <?php if ($formData['is_active'] ?? 0): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!empty($formData['locked_until']) && strtotime($formData['locked_until']) > time()): ?>
                        <p><strong>Account:</strong> <span class="badge bg-warning">Locked</span></p>
                    <?php endif; ?>
                    
                    <?php if (($formData['failed_login_attempts'] ?? 0) > 0): ?>
                        <p><strong>Failed Logins:</strong> <span class="text-warning"><?= $formData['failed_login_attempts'] ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-1"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=users/reset-password&id=<?= $formData['id'] ?? 0 ?>" 
                       class="btn btn-outline-warning btn-sm"
                       onclick="return confirm('Are you sure you want to reset this user\'s password?')">
                        <i class="bi bi-key me-1"></i>Reset Password
                    </a>
                    
                    <?php if ($formData['is_active'] ?? 0): ?>
                        <button type="button" 
                                class="btn btn-outline-secondary btn-sm"
                                onclick="toggleUserStatus(<?= $formData['id'] ?? 0 ?>, false)">
                            <i class="bi bi-person-x me-1"></i>Deactivate Account
                        </button>
                    <?php else: ?>
                        <button type="button" 
                                class="btn btn-outline-success btn-sm"
                                onclick="toggleUserStatus(<?= $formData['id'] ?? 0 ?>, true)">
                            <i class="bi bi-person-check me-1"></i>Activate Account
                        </button>
                    <?php endif; ?>
                    
                    <?php if (($formData['id'] ?? 0) != $_SESSION['user_id']): ?>
                        <a href="?route=users/delete&id=<?= $formData['id'] ?? 0 ?>" 
                           class="btn btn-outline-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                            <i class="bi bi-trash me-1"></i>Delete User
                        </a>
                    <?php endif; ?>
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
    
    if (password && password !== confirmPassword) {
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
    
    if (password && password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
    }
    
    if (password && password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long');
        return false;
    }
});

// Toggle user status
function toggleUserStatus(userId, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const message = activate ? 'activate this user' : 'deactivate this user';
    
    if (confirm(`Are you sure you want to ${message}?`)) {
        fetch(`?route=users/toggle-status&id=${userId}&activate=${activate ? 1 : 0}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating user status');
        });
    }
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Edit User - ConstructLink™';
$pageHeader = 'Edit User: ' . htmlspecialchars($formData['full_name'] ?? 'Unknown');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Users', 'url' => '?route=users'],
    ['title' => 'Edit User', 'url' => '?route=users/edit&id=' . ($formData['id'] ?? 0)]
];

// Page actions
$pageActions = '
<a href="?route=users/view&id=' . ($formData['id'] ?? 0) . '" class="btn btn-outline-primary me-2">
    <i class="bi bi-eye me-1"></i>View Profile
</a>
<a href="?route=users" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back to Users
</a>';

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
