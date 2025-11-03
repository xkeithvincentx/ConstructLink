<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Load helpers for database-driven branding and asset management
    require_once APP_ROOT . '/helpers/BrandingHelper.php';
    require_once APP_ROOT . '/helpers/AssetHelper.php';

    // Load branding data from database
    $branding = BrandingHelper::loadBranding();
    $pageTitle = BrandingHelper::getPageTitle('Login');
    ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Favicon -->
    <link rel="icon" href="<?= htmlspecialchars($branding['favicon_url']) ?>" type="image/x-icon">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-4LISF5TTJX/fLmGSxO53rV4miRxdg84mZsxmO8Rx5jGtp/LbrixFETvWa5a6sESd" crossorigin="anonymous">

    <!-- External Auth Module CSS -->
    <?= AssetHelper::loadModuleCSS('auth') ?>

    <!-- Dynamic Branding Colors -->
    <?= BrandingHelper::generateCSSVariables() ?>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                    <!-- Flash Messages -->
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger alert-dismissible fade show auth-alert" role="alert">
                                <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
                                <span class="fw-semibold">Error:</span> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="alert alert-success alert-dismissible fade show auth-alert" role="status">
                                <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Login Card -->
                    <div class="card auth-card border-0">
                        <div class="card-header auth-card-header">
                            <div class="icon-container">
                                <?php
                                // Use database logo if exists, otherwise fallback to icon
                                $logoPath = APP_ROOT . $branding['logo_url'];
                                if (!empty($branding['logo_url']) && file_exists($logoPath)):
                                ?>
                                    <img src="<?= htmlspecialchars($branding['logo_url']) ?>"
                                         alt="<?= htmlspecialchars($branding['company_name']) ?> Logo"
                                         class="auth-logo"
                                         width="80"
                                         height="80">
                                <?php else: ?>
                                    <i class="bi bi-building" aria-hidden="true"></i>
                                <?php endif; ?>
                            </div>
                            <h3><?= htmlspecialchars($branding['app_name']) ?></h3>
                            <p>Asset &amp; Inventory Management System</p>
                        </div>

                        <div class="card-body auth-card-body">
                            <h4 id="login-heading">Sign In to Your Account</h4>

                            <!-- Login Form -->
                            <form method="POST"
                                  action="?route=login"
                                  class="auth-form"
                                  novalidate
                                  aria-labelledby="login-heading">
                                <?= CSRFProtection::getTokenField() ?>

                                <!-- Username Field -->
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        Username
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text" aria-hidden="true">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text"
                                               class="form-control"
                                               id="username"
                                               name="username"
                                               required
                                               autofocus
                                               autocomplete="username"
                                               autocapitalize="off"
                                               spellcheck="false"
                                               placeholder="Enter your username"
                                               aria-required="true"
                                               aria-describedby="username-help"
                                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                    </div>
                                    <small id="username-help" class="form-text text-muted visually-hidden">
                                        Enter your ConstructLink username
                                    </small>
                                </div>

                                <!-- Password Field -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        Password
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text" aria-hidden="true">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password"
                                               class="form-control"
                                               id="password"
                                               name="password"
                                               required
                                               autocomplete="current-password"
                                               placeholder="Enter your password"
                                               aria-required="true"
                                               aria-describedby="password-help">
                                        <button class="btn btn-outline-secondary btn-password-toggle"
                                                type="button"
                                                id="togglePassword"
                                                aria-label="Show password"
                                                aria-pressed="false">
                                            <i class="bi bi-eye" id="togglePasswordIcon" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <small id="password-help" class="form-text text-muted visually-hidden">
                                        Enter your ConstructLink password
                                    </small>
                                </div>

                                <!-- Remember Me Checkbox -->
                                <div class="mb-4 form-check">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           id="remember_me"
                                           name="remember_me"
                                           aria-describedby="remember-help">
                                    <label class="form-check-label" for="remember_me">
                                        Remember me for 30 days
                                    </label>
                                    <small id="remember-help" class="form-text text-muted visually-hidden">
                                        Keep you signed in on this device for 30 days
                                    </small>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg btn-submit">
                                        <span class="btn-text">
                                            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                                            Sign In
                                        </span>
                                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>

                                <!-- Forgot Password Link -->
                                <div class="text-center">
                                    <a href="?route=forgot-password" class="auth-link">
                                        <i class="bi bi-question-circle me-1" aria-hidden="true"></i>
                                        Forgot your password?
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Footer -->
                        <div class="card-footer auth-card-footer">
                            <small>
                                <strong><?= htmlspecialchars($branding['company_name']) ?></strong><br>
                                Powered by <?= htmlspecialchars($branding['app_name']) ?>
                            </small>
                        </div>
                    </div>

                    <!-- Demo Credentials (Development Only) -->
                    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
                    <div class="card demo-credentials border-info" role="complementary" aria-label="Demo credentials">
                        <div class="card-header">
                            <h6>
                                <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                                Demo Credentials
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <strong>System Admin:</strong><br>
                                    <code>admin / admin123</code>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Finance Director:</strong><br>
                                    <code>finance_dir / password123</code>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Warehouseman:</strong><br>
                                    <code>warehouse / password123</code>
                                </div>
                                <div class="col-sm-6">
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

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

    <!-- External Auth Module JS -->
    <?= AssetHelper::loadModuleJS('auth') ?>
</body>
</html>
