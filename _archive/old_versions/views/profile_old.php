<?php
/**
 * ConstructLink™ User Profile View
 * Current user's profile management
 */

// Get user data for role-based UI
$currentUser = Auth::getInstance()->getCurrentUser();
$userRole = $currentUser['role_name'] ?? 'Guest';
$auth = Auth::getInstance();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-person-circle me-2"></i>
            My Profile
        </h1>
        <div class="btn-toolbar">
            <a href="?route=dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h6 class="alert-heading mb-2">Please correct the following errors:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Success Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Form -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person me-1"></i>Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=users/profile">
                        <?= CSRFProtection::getTokenField() ?>
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control"
                                       id="full_name" 
                                       name="full_name" 
                                       value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" 
                                       class="form-control"
                                       id="username" 
                                       value="<?= htmlspecialchars($formData['username'] ?? '') ?>"
                                       readonly>
                                <div class="form-text">Username cannot be changed</div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control"
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                                       required>
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

                        <!-- Read-only Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" 
                                       class="form-control"
                                       id="role" 
                                       value="<?= htmlspecialchars($formData['role_name'] ?? 'No Role') ?>"
                                       readonly>
                                <div class="form-text">Role is managed by administrators</div>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" 
                                       class="form-control"
                                       id="department" 
                                       value="<?= htmlspecialchars($formData['department'] ?? 'N/A') ?>"
                                       readonly>
                                <div class="form-text">Department is managed by administrators</div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="?route=dashboard" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-1"></i>Change Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=users/profile" id="passwordForm">
                        <?= CSRFProtection::getTokenField() ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control"
                                       id="current_password" 
                                       name="current_password">
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control"
                                       id="new_password" 
                                       name="new_password">
                                <div class="form-text">Minimum 8 characters</div>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password_confirm" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control"
                                       id="new_password_confirm" 
                                       name="new_password_confirm">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-1"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profile Sidebar -->
        <div class="col-lg-4">
            <!-- Profile Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-1"></i>Profile Summary
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-circle bg-primary text-white mb-3 mx-auto" style="width: 80px; height: 80px; line-height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($formData['full_name'] ?? 'U', 0, 2)) ?>
                    </div>
                    <h5><?= htmlspecialchars($formData['full_name'] ?? 'Unknown User') ?></h5>
                    <p class="text-muted">@<?= htmlspecialchars($formData['username'] ?? 'unknown') ?></p>
                    <span class="badge bg-primary"><?= htmlspecialchars($formData['role_name'] ?? 'No Role') ?></span>
                </div>
            </div>

            <!-- Account Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-shield-check me-1"></i>Account Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>User ID:</strong> #<?= $formData['id'] ?? 'N/A' ?></p>
                        <p><strong>Member Since:</strong> 
                            <?= isset($formData['created_at']) ? date('M j, Y', strtotime($formData['created_at'])) : 'N/A' ?>
                        </p>
                        <p><strong>Last Login:</strong> 
                            <?php if (isset($formData['last_login']) && $formData['last_login']): ?>
                                <?= date('M j, Y g:i A', strtotime($formData['last_login'])) ?>
                            <?php else: ?>
                                Never
                            <?php endif; ?>
                        </p>
                        <p><strong>Account Status:</strong> 
                            <?php if ($formData['is_active'] ?? 0): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Role Permissions -->
            <?php if (!empty($formData['permissions'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-key me-1"></i>My Permissions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <?php 
                            $permissions = is_array($formData['permissions']) ? $formData['permissions'] : [];
                            foreach ($permissions as $permission): 
                            ?>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <?= htmlspecialchars(str_replace('_', ' ', ucwords($permission, '_'))) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Security Tips -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-shield-exclamation me-1"></i>Security Tips
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Use a strong, unique password
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Keep your contact information updated
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Log out when using shared computers
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Report suspicious activity immediately
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>

<script>
// Password form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_password_confirm').value;
    
    // Check if any password field is filled
    if (currentPassword || newPassword || confirmPassword) {
        // If any field is filled, all must be filled
        if (!currentPassword || !newPassword || !confirmPassword) {
            e.preventDefault();
            alert('Please fill in all password fields to change your password.');
            return false;
        }
        
        // Check password length
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('New password must be at least 8 characters long.');
            return false;
        }
        
        // Check password confirmation
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match.');
            return false;
        }
    }
});

// Real-time password confirmation validation
document.getElementById('new_password_confirm').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});

// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthIndicator = document.getElementById('password-strength');
    
    if (password.length === 0) {
        return;
    }
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    // You can add visual strength indicator here if needed
});
</script>

<?php
// Set page variables
$pageTitle = 'My Profile - ConstructLink™';
$pageHeader = 'My Profile';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'My Profile', 'url' => '?route=users/profile']
];
?>
