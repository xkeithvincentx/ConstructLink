<?php
// Start output buffering to capture content
ob_start();
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <!-- Login Card -->
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-building display-4"></i>
                        </div>
                        <h3 class="mb-0">ConstructLink™</h3>
                        <p class="mb-0 opacity-75">Asset & Inventory Management</p>
                    </div>
                    
                    <div class="card-body p-5">
                        <h4 class="text-center mb-4">Sign In to Your Account</h4>
                        
                        <!-- Login Form -->
                        <form method="POST" action="/login" x-data="loginForm()">
                            <?= CSRFProtection::getTokenField() ?>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
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
                                           x-model="username"
                                           placeholder="Enter your username">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input :type="showPassword ? 'text' : 'password'" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required
                                           x-model="password"
                                           placeholder="Enter your password">
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            @click="showPassword = !showPassword">
                                        <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="remember_me" 
                                       name="remember_me"
                                       x-model="rememberMe">
                                <label class="form-check-label" for="remember_me">
                                    Remember me for 30 days
                                </label>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" 
                                        class="btn btn-primary btn-lg"
                                        :disabled="loading"
                                        @click="loading = true">
                                    <span x-show="!loading">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>
                                        Sign In
                                    </span>
                                    <span x-show="loading" style="display: none;">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        Signing In...
                                    </span>
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="/forgot-password" class="text-decoration-none">
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
                <?php if (ENV_TYPE === 'development'): ?>
                <div class="card mt-3 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Demo Credentials</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>System Admin:</strong><br>
                                <code>admin / admin123</code>
                            </div>
                            <div class="col-md-6">
                                <strong>Other Roles:</strong><br>
                                <code>warehouse / password123</code><br>
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

<script>
function loginForm() {
    return {
        username: '',
        password: '',
        rememberMe: false,
        showPassword: false,
        loading: false,
        
        init() {
            // Focus on username field
            this.$nextTick(() => {
                document.getElementById('username').focus();
            });
        }
    }
}
</script>

<style>
.min-vh-100 {
    min-height: 100vh;
}

.card {
    border-radius: 1rem;
}

.card-header {
    border-radius: 1rem 1rem 0 0 !important;
}

.card-footer {
    border-radius: 0 0 1rem 1rem !important;
}

.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}

.form-control {
    border-left: none;
}

.form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

/* Animation for loading state */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.loading {
    animation: pulse 1.5s ease-in-out infinite;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
