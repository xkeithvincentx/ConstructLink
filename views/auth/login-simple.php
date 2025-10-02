<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Login - ConstructLink™') ?></title>
    
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
        .login-card { 
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
        .demo-credentials {
            font-size: 0.875rem;
        }
        .demo-credentials code {
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
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

                    <!-- Login Card -->
                    <div class="card login-card shadow-lg border-0">
                        <div class="card-header text-white text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-building display-4"></i>
                            </div>
                            <h3 class="mb-0 fw-bold">ConstructLink™</h3>
                            <p class="mb-0 opacity-75">Asset & Inventory Management System</p>
                        </div>
                        
                        <div class="card-body p-5">
                            <h4 class="text-center mb-4 fw-semibold">Sign In to Your Account</h4>
                            
                            <!-- Login Form -->
                            <form method="POST" action="?route=login" novalidate>
                                <?= CSRFProtection::getTokenField() ?>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label fw-medium">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control" 
                                               id="username" 
                                               name="username" 
                                               required 
                                               autofocus
                                               autocomplete="username"
                                               placeholder="Enter your username"
                                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-medium">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               required
                                               autocomplete="current-password"
                                               placeholder="Enter your password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="remember_me" 
                                           name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        Remember me for 30 days
                                    </label>
                                </div>
                                
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>
                                        Sign In
                                    </button>
                                </div>
                                
                                <div class="text-center">
                                    <a href="?route=forgot-password" class="text-decoration-none">
                                        <i class="bi bi-question-circle me-1"></i>
                                        Forgot your password?
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
                    
                    <!-- Demo Credentials (for development) -->
                    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
                    <div class="card mt-3 border-info demo-credentials">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Demo Credentials</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>System Admin:</strong><br>
                                    <code>admin / admin123</code><br><br>
                                    <strong>Finance Director:</strong><br>
                                    <code>finance_dir / password123</code>
                                </div>
                                <div class="col-md-6">
                                    <strong>Warehouse:</strong><br>
                                    <code>warehouse / password123</code><br><br>
                                    <strong>Project Manager:</strong><br>
                                    <code>project_mgr / password123</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
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
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                
                // Show validation message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Please enter both username and password.
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
