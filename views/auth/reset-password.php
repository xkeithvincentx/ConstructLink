<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Reset Password - ConstructLink™') ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-card { 
            background: rgba(255, 255, 255, 0.95); 
            border-radius: 1rem; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        .card-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
            border-radius: 1rem 1rem 0 0 !important; 
        }
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover { 
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%); 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        .alert {
            border-radius: 0.5rem;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .password-requirements {
            font-size: 0.875rem;
        }
        .requirement {
            transition: color 0.3s ease;
        }
        .requirement.met {
            color: #198754;
        }
        .requirement.unmet {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    
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

                    <!-- Reset Password Card -->
                    <div class="card reset-card shadow-lg border-0">
                        <div class="card-header text-white text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock display-4"></i>
                            </div>
                            <h3 class="mb-0 fw-bold">Reset Password</h3>
                            <p class="mb-0 opacity-75">Create a new secure password</p>
                        </div>
                        
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h5 class="fw-semibold">Enter your new password</h5>
                                <p class="text-muted">Choose a strong password to secure your account.</p>
                            </div>
                            
                            <!-- Reset Password Form -->
                            <form method="POST" action="?route=reset-password&token=<?= htmlspecialchars($_GET['token'] ?? '') ?>" novalidate>
                                <?= CSRFProtection::getTokenField() ?>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-medium">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               required
                                               autocomplete="new-password"
                                               placeholder="Enter new password"
                                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength bg-light mt-2" id="passwordStrength"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
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
                                    <small class="text-muted password-requirements">
                                        <strong>Password Requirements:</strong><br>
                                        <span class="requirement unmet" id="lengthReq">
                                            <i class="bi bi-x-circle me-1"></i>
                                            At least <?= PASSWORD_MIN_LENGTH ?> characters
                                        </span><br>
                                        <span class="requirement unmet" id="upperReq">
                                            <i class="bi bi-x-circle me-1"></i>
                                            One uppercase letter
                                        </span><br>
                                        <span class="requirement unmet" id="lowerReq">
                                            <i class="bi bi-x-circle me-1"></i>
                                            One lowercase letter
                                        </span><br>
                                        <span class="requirement unmet" id="numberReq">
                                            <i class="bi bi-x-circle me-1"></i>
                                            One number
                                        </span>
                                    </small>
                                </div>
                                
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg" id="resetButton" disabled>
                                        <i class="bi bi-shield-check me-2"></i>
                                        Reset Password
                                    </button>
                                </div>
                                
                                <div class="text-center">
                                    <a href="?route=login" class="text-decoration-none">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Back to Login
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card-footer bg-light text-center py-3">
                            <small class="text-muted">
                                <strong>V CUTAMORA CONSTRUCTION INC.</strong><br>
                                Powered by Ranoa Digital Solutions
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const resetButton = document.getElementById('resetButton');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordMismatch = document.getElementById('passwordMismatch');
        
        // Password requirements elements
        const lengthReq = document.getElementById('lengthReq');
        const upperReq = document.getElementById('upperReq');
        const lowerReq = document.getElementById('lowerReq');
        const numberReq = document.getElementById('numberReq');
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const toggleIcon = document.getElementById('toggleConfirmPasswordIcon');
            
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                confirmPasswordField.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });
        
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
                element.classList.remove('unmet');
                element.classList.add('met');
                icon.classList.remove('bi-x-circle');
                icon.classList.add('bi-check-circle');
            } else {
                element.classList.remove('met');
                element.classList.add('unmet');
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
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            const passwordValid = checkPasswordRequirements(password);
            const passwordsMatch = password === confirmPassword && password.length > 0;
            
            // Show/hide password mismatch message
            if (confirmPassword.length > 0 && !passwordsMatch) {
                passwordMismatch.style.display = 'block';
                confirmPasswordField.classList.add('is-invalid');
            } else {
                passwordMismatch.style.display = 'none';
                confirmPasswordField.classList.remove('is-invalid');
            }
            
            // Enable/disable submit button
            resetButton.disabled = !(passwordValid && passwordsMatch);
        }
        
        // Event listeners
        passwordField.addEventListener('input', validateForm);
        confirmPasswordField.addEventListener('input', validateForm);
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (!password || !confirmPassword || password !== confirmPassword) {
                e.preventDefault();
                
                // Show validation message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Please ensure both password fields are filled and match.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const form = document.querySelector('form');
                form.parentNode.insertBefore(alertDiv, form);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alertDiv);
                    bsAlert.close();
                }, 3000);
            }
        });
    </script>
</body>
</html>
