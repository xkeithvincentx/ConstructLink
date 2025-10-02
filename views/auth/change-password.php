<?php
// Set page variables
$pageTitle = 'Change Password - ConstructLink™';
$pageHeader = 'Change Password';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Change Password', 'url' => '?route=change-password']
];

// Get current user
$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

include APP_ROOT . '/views/layouts/main.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <!-- Flash Messages -->
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Change Password Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-2"></i>
                        Change Password
                    </h5>
                </div>
                
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="bi bi-person-circle display-4 text-primary"></i>
                                </div>
                                <h6 class="mt-2 mb-0"><?= htmlspecialchars($user['full_name']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($user['username']) ?></small>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h6 class="fw-semibold">Security Information</h6>
                            <p class="text-muted mb-2">
                                Keep your account secure by using a strong password. Your password should be unique and not used on other websites.
                            </p>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">Last Login</small>
                                    <strong><?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?></strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Account Status</small>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Role</small>
                                    <strong><?= htmlspecialchars($user['role_name']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password Form -->
                    <form method="POST" action="?route=change-password" novalidate>
                        <?= CSRFProtection::getTokenField() ?>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-medium">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="current_password" 
                                       name="current_password" 
                                       required
                                       autocomplete="current-password"
                                       placeholder="Enter your current password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                    <i class="bi bi-eye" id="toggleCurrentPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-medium">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       required
                                       autocomplete="new-password"
                                       placeholder="Enter new password"
                                       minlength="<?= PASSWORD_MIN_LENGTH ?>">
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                    <i class="bi bi-eye" id="toggleNewPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="password-strength bg-light mt-2" id="passwordStrength" style="height: 4px; border-radius: 2px;"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-medium">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required
                                       autocomplete="new-password"
                                       placeholder="Confirm new password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordMismatch" style="display: none;">
                                Passwords do not match.
                            </div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="mb-4">
                            <small class="text-muted">
                                <strong>Password Requirements:</strong><br>
                                <span class="requirement text-danger" id="lengthReq">
                                    <i class="bi bi-x-circle me-1"></i>
                                    At least <?= PASSWORD_MIN_LENGTH ?> characters
                                </span><br>
                                <span class="requirement text-danger" id="upperReq">
                                    <i class="bi bi-x-circle me-1"></i>
                                    One uppercase letter
                                </span><br>
                                <span class="requirement text-danger" id="lowerReq">
                                    <i class="bi bi-x-circle me-1"></i>
                                    One lowercase letter
                                </span><br>
                                <span class="requirement text-danger" id="numberReq">
                                    <i class="bi bi-x-circle me-1"></i>
                                    One number
                                </span>
                            </small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="changePasswordButton" disabled>
                                <i class="bi bi-shield-check me-2"></i>
                                Change Password
                            </button>
                            <a href="?route=dashboard" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Security Tips -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>
                        Security Tips
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-semibold">Strong Password Guidelines:</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check-circle text-success me-2"></i>Use a mix of letters, numbers, and symbols</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Make it at least <?= PASSWORD_MIN_LENGTH ?> characters long</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Avoid common words or personal information</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold">Account Security:</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-shield-check text-primary me-2"></i>Change passwords regularly</li>
                                <li><i class="bi bi-shield-check text-primary me-2"></i>Don't share your login credentials</li>
                                <li><i class="bi bi-shield-check text-primary me-2"></i>Log out when using shared computers</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPasswordField = document.getElementById('current_password');
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const changePasswordButton = document.getElementById('changePasswordButton');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordMismatch = document.getElementById('passwordMismatch');
    
    // Password requirements elements
    const lengthReq = document.getElementById('lengthReq');
    const upperReq = document.getElementById('upperReq');
    const lowerReq = document.getElementById('lowerReq');
    const numberReq = document.getElementById('numberReq');
    
    // Toggle password visibility functions
    function setupPasswordToggle(fieldId, toggleId, iconId) {
        document.getElementById(toggleId).addEventListener('click', function() {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    }
    
    setupPasswordToggle('current_password', 'toggleCurrentPassword', 'toggleCurrentPasswordIcon');
    setupPasswordToggle('new_password', 'toggleNewPassword', 'toggleNewPasswordIcon');
    setupPasswordToggle('confirm_password', 'toggleConfirmPassword', 'toggleConfirmPasswordIcon');
    
    // Password strength and validation
    function checkPasswordRequirements(password) {
        const requirements = {
            length: password.length >= <?= PASSWORD_MIN_LENGTH ?>,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /\d/.test(password)
        };
        
        // Update requirement indicators
        updateRequirement(lengthReq, requirements.length);
        updateRequirement(upperReq, requirements.upper);
        updateRequirement(lowerReq, requirements.lower);
        updateRequirement(numberReq, requirements.number);
        
        // Calculate strength
        const score = Object.values(requirements).filter(Boolean).length;
        updatePasswordStrength(score);
        
        return Object.values(requirements).every(Boolean);
    }
    
    function updateRequirement(element, met) {
        const icon = element.querySelector('i');
        if (met) {
            element.classList.remove('text-danger');
            element.classList.add('text-success');
            icon.classList.remove('bi-x-circle');
            icon.classList.add('bi-check-circle');
        } else {
            element.classList.remove('text-success');
            element.classList.add('text-danger');
            icon.classList.remove('bi-check-circle');
            icon.classList.add('bi-x-circle');
        }
    }
    
    function updatePasswordStrength(score) {
        const colors = ['#dc3545', '#fd7e14', '#ffc107', '#198754'];
        const widths = ['25%', '50%', '75%', '100%'];
        
        passwordStrength.style.backgroundColor = colors[score - 1] || '#e9ecef';
        passwordStrength.style.width = widths[score - 1] || '0%';
    }
    
    function validateForm() {
        const currentPassword = currentPasswordField.value;
        const newPassword = newPasswordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        const passwordValid = checkPasswordRequirements(newPassword);
        const passwordsMatch = newPassword === confirmPassword && newPassword.length > 0;
        const currentPasswordFilled = currentPassword.length > 0;
        
        // Show/hide password mismatch message
        if (confirmPassword.length > 0 && !passwordsMatch) {
            passwordMismatch.style.display = 'block';
            confirmPasswordField.classList.add('is-invalid');
        } else {
            passwordMismatch.style.display = 'none';
            confirmPasswordField.classList.remove('is-invalid');
        }
        
        // Enable/disable submit button
        changePasswordButton.disabled = !(currentPasswordFilled && passwordValid && passwordsMatch);
    }
    
    // Event listeners
    currentPasswordField.addEventListener('input', validateForm);
    newPasswordField.addEventListener('input', validateForm);
    confirmPasswordField.addEventListener('input', validateForm);
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
