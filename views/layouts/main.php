<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ConstructLink™') ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/app.css" rel="stylesheet">
    
    <!-- Layout Specific CSS -->
    <style>
        /* Footer Styling */
        .main-footer {
            margin-left: 0;
            background-color: #f8f9fa !important;
            border-top: 1px solid #dee2e6;
        }
        
        .guest-footer {
            background-color: #f8f9fa !important;
            border-top: 1px solid #dee2e6;
        }
        
        /* Ensure content has proper spacing */
        .main-content-wrapper {
            min-height: calc(100vh - 200px);
            padding-bottom: 2rem;
        }
        
        /* Guest layout adjustments */
        .container-fluid.h-100 {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container-fluid.h-100 .content {
            flex: 1;
        }
        
        /* Responsive footer adjustments */
        @media (max-width: 767.98px) {
            .main-footer .row {
                text-align: center;
            }
            
            .main-footer .col-md-6:last-child {
                text-align: center !important;
                margin-top: 0.5rem;
            }
        }
    </style>
    
    <!-- Alpine.js for reactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Meta tags -->
    <meta name="description" content="ConstructLink™ - Asset and Inventory Management System for V CUTAMORA CONSTRUCTION INC.">
    <meta name="author" content="<?= SYSTEM_VENDOR ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
</head>
<body class="bg-light">
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
                                <span class="text-muted small">© 2024 ConstructLink™ by <?= SYSTEM_VENDOR ?></span>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="text-muted small">Version <?= APP_VERSION ?> | <?= COMPANY_NAME ?></span>
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
                    <span class="text-muted small">© 2024 ConstructLink™ by <?= SYSTEM_VENDOR ?> | <?= COMPANY_NAME ?></span>
                </div>
            </footer>
        </div>
    <?php endif; ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/app.js"></script>

    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <?= $pageScripts ?>
    <?php endif; ?>

    <!-- Alpine.js Components (Optimized) -->
    <script src="/assets/js/alpine-components.js"></script>
    
    <!-- Global JavaScript Configuration -->
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
</body>
</html>
