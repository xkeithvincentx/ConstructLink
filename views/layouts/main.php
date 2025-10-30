<?php
// Load branding helper if not already loaded
if (!class_exists('BrandingHelper')) {
    require_once APP_ROOT . '/helpers/BrandingHelper.php';
}
$branding = BrandingHelper::loadBranding();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $branding['app_name']) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/app.css" rel="stylesheet">
    <link href="/assets/css/layout.css" rel="stylesheet">

    <!-- Database-Driven Branding Colors (ONLY inline CSS allowed) -->
    <style>
        /* ==========================================================================
           DATABASE-DRIVEN BRANDING COLORS
           Purpose: Override CSS variables with database values
           Note: This is the ONLY inline CSS allowed - all other styles in external files
           ========================================================================== */
        :root {
            --primary-color: <?= htmlspecialchars($branding['primary_color']) ?>;
            --secondary-color: <?= htmlspecialchars($branding['secondary_color']) ?>;
            --accent-color: <?= htmlspecialchars($branding['accent_color']) ?>;
            --success-color: <?= htmlspecialchars($branding['success_color']) ?>;
            --warning-color: <?= htmlspecialchars($branding['warning_color']) ?>;
            --danger-color: <?= htmlspecialchars($branding['danger_color']) ?>;
            --info-color: <?= htmlspecialchars($branding['info_color']) ?>;
        }

        /* Navbar - Database-driven primary color background */
        .navbar {
            background-color: <?= htmlspecialchars($branding['primary_color']) ?>;
        }
    </style>

    <!-- Meta tags -->
    <meta name="description" content="<?= htmlspecialchars($branding['app_name']) ?> - Asset and Inventory Management System for <?= htmlspecialchars($branding['company_name']) ?>">
    <meta name="author" content="<?= htmlspecialchars($branding['company_name']) ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Navigation -->
        <?php include APP_ROOT . '/views/layouts/navbar.php'; ?>
        
        <!-- Main Content -->
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <?php include APP_ROOT . '/views/layouts/sidebar.php'; ?>
                
                <!-- Main Content Area -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <!-- Content Wrapper -->
                    <div class="main-content-wrapper">
                        <!-- Breadcrumb -->
                        <?php if (isset($breadcrumbs)): ?>
                            <nav aria-label="breadcrumb" class="mt-3">
                                <ol class="breadcrumb">
                                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                                        <?php if ($index === count($breadcrumbs) - 1): ?>
                                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['title']) ?></li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item">
                                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        <?php endif; ?>
                        
                        <!-- Page Header -->
                        <?php if (isset($pageHeader)): ?>
                            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                                <h1 class="h2"><?= htmlspecialchars($pageHeader) ?></h1>
                                <?php if (isset($pageActions)): ?>
                                    <div class="btn-toolbar mb-2 mb-md-0">
                                        <?= $pageActions ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Flash Messages -->
                        <?php include APP_ROOT . '/views/layouts/messages.php'; ?>
                        
                        <!-- Page Content -->
                        <div class="content">
                            <?php if (isset($content)): ?>
                                <?= $content ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Footer within main content area -->
                    <footer class="main-footer mt-5 py-3 border-top bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="text-muted small">© <?= date('Y') ?> <?= htmlspecialchars($branding['company_name']) ?>. All rights reserved.</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="text-muted small">Version <?= APP_VERSION ?> | <?= htmlspecialchars($branding['company_name']) ?></span>
                            </div>
                        </div>
                    </footer>
                </main>
            </div>
        </div>
    <?php else: ?>
        <!-- Guest Layout (Login, etc.) -->
        <div class="container-fluid h-100">
            <!-- Flash Messages -->
            <?php include APP_ROOT . '/views/layouts/messages.php'; ?>
            
            <!-- Page Content -->
            <div class="content">
                <?php if (isset($content)): ?>
                    <?= $content ?>
                <?php endif; ?>
            </div>
            
            <!-- Guest Footer -->
            <footer class="guest-footer mt-auto py-3 border-top bg-light">
                <div class="text-center">
                    <span class="text-muted small">© <?= date('Y') ?> <?= htmlspecialchars($branding['company_name']) ?>. All rights reserved. Powered by <?= htmlspecialchars($branding['app_name']) ?></span>
                </div>
            </footer>
        </div>
    <?php endif; ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/app.js"></script>

    <!-- Global JavaScript Configuration (must load before components) -->
    <script>
        // Initialize ConstructLink global object
        window.ConstructLink = window.ConstructLink || {};

        // Configuration
        window.ConstructLink.baseUrl = '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>';
        window.ConstructLink.apiUrl = '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/api';
        window.ConstructLink.csrfToken = '<?= CSRFProtection::generateToken() ?>';
        window.ConstructLink.user = <?= isset($_SESSION['user_id']) ? json_encode([
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['user_role']
        ]) : 'null' ?>;

        console.log('ConstructLink™ initialized - Performance optimized');
    </script>

    <!-- Defer Alpine.js auto-start -->
    <script>
        document.addEventListener('alpine:init', () => {
            console.log('Alpine.js components registered');
        });
    </script>

    <!-- Alpine.js Components (load first) -->
    <script src="/assets/js/alpine-components.js"></script>

    <!-- Alpine.js (loads last and auto-starts after components registered) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>
