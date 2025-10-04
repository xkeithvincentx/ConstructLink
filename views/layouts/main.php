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
    <meta name="author" content="Ranoa Digital Solutions">
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
                                <span class="text-muted small">© 2024 ConstructLink™ by Ranoa Digital Solutions</span>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="text-muted small">Version <?= APP_VERSION ?> | V CUTAMORA CONSTRUCTION INC.</span>
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
                    <span class="text-muted small">© 2024 ConstructLink™ by Ranoa Digital Solutions | V CUTAMORA CONSTRUCTION INC.</span>
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
    
    <!-- Global JavaScript variables -->
    <script>
        window.ConstructLink = {
            baseUrl: '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>',
            apiUrl: '<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/api',
            csrfToken: '<?= CSRFProtection::generateToken() ?>',
            user: <?= isset($_SESSION['user_id']) ? json_encode([
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['user_role']
            ]) : 'null' ?>
        };
        
        // Global Alpine.js components and utilities
        document.addEventListener('alpine:init', () => {
            // Quick Search Alpine.js Component
            Alpine.data('quickSearch', () => ({
                query: '',
                results: [],
                loading: false,
                
                search() {
                    if (this.query.length < 2) {
                        this.results = [];
                        return;
                    }
                    
                    this.loading = true;
                    
                    fetch(`${window.ConstructLink.baseUrl}/?route=api/assets/search?q=${encodeURIComponent(this.query)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.results = data.results.slice(0, 5); // Limit to 5 results
                            }
                        })
                        .catch(error => console.error('Search error:', error))
                        .finally(() => this.loading = false);
                },
                
                clearSearch() {
                    this.query = '';
                    this.results = [];
                },
                
                getStatusBadgeClass(status) {
                    const classes = {
                        'available': 'bg-success',
                        'in_use': 'bg-primary',
                        'borrowed': 'bg-info',
                        'under_maintenance': 'bg-warning',
                        'retired': 'bg-secondary'
                    };
                    return classes[status] || 'bg-secondary';
                }
            }));
            
            // Notifications Alpine.js Component
            Alpine.data('notifications', () => ({
                notifications: [],
                unreadCount: 0,
                refreshInterval: null,
                isLoading: false,

                init() {
                    // Load notifications immediately but async
                    this.loadNotifications();
                    // Clear any existing interval
                    if (this.refreshInterval) {
                        clearInterval(this.refreshInterval);
                    }
                    // Refresh notifications every 5 minutes
                    this.refreshInterval = setInterval(() => this.loadNotifications(), 300000);
                },

                destroy() {
                    if (this.refreshInterval) {
                        clearInterval(this.refreshInterval);
                        this.refreshInterval = null;
                    }
                },

                loadNotifications() {
                    // Don't reload if already loading
                    if (this.isLoading) return;

                    this.isLoading = true;

                    fetch(`${window.ConstructLink.baseUrl}/?route=api/notifications`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                this.notifications = data.notifications || [];
                                this.unreadCount = data.unread_count || 0;
                            } else {
                                console.error('Failed to load notifications:', data.error);
                                this.notifications = [];
                                this.unreadCount = 0;
                            }
                        })
                        .catch(error => {
                            console.error('Notifications loading error:', error);
                            this.notifications = [];
                            this.unreadCount = 0;
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                }
            }));
            
            // Sidebar Stats Alpine.js Component
            Alpine.data('sidebarStats', () => ({
                stats: {},
                lastUpdated: '',
                refreshInterval: null,
                
                init() {
                    // Delay initial stats load to prevent conflicts on page load
                    setTimeout(() => this.loadStats(), 1500);
                    // Clear any existing interval
                    if (this.refreshInterval) {
                        clearInterval(this.refreshInterval);
                    }
                    // Auto-refresh stats every 5 minutes
                    this.refreshInterval = setInterval(() => this.loadStats(), 300000);
                },
                
                destroy() {
                    if (this.refreshInterval) {
                        clearInterval(this.refreshInterval);
                        this.refreshInterval = null;
                    }
                },
                
                loadStats() {
                    fetch(`${window.ConstructLink.baseUrl}/?route=api/dashboard/stats`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.assets) {
                                this.stats = data.data.assets;
                                this.lastUpdated = new Date().toLocaleTimeString();
                                
                                // Update notification badges
                                this.updateNotificationBadges(data.data);
                            }
                        })
                        .catch(error => console.error('Stats loading error:', error));
                },
                
                updateNotificationBadges(data) {
                    // Update pending withdrawals count
                    const pendingWithdrawalsEl = document.getElementById('pending-withdrawals-count');
                    if (pendingWithdrawalsEl && data.withdrawals && data.withdrawals.pending > 0) {
                        pendingWithdrawalsEl.textContent = data.withdrawals.pending;
                        pendingWithdrawalsEl.style.display = 'inline';
                    }
                    
                    // Update overdue maintenance count
                    const overdueMaintenanceEl = document.getElementById('overdue-maintenance-count');
                    if (overdueMaintenanceEl && data.overdue && data.overdue.maintenance > 0) {
                        overdueMaintenanceEl.textContent = data.overdue.maintenance;
                        overdueMaintenanceEl.style.display = 'inline';
                    }
                    
                    // Update open incidents count
                    const openIncidentsEl = document.getElementById('open-incidents-count');
                    if (openIncidentsEl && data.overdue && data.overdue.incidents > 0) {
                        openIncidentsEl.textContent = data.overdue.incidents;
                        openIncidentsEl.style.display = 'inline';
                    }
                    
                    // Update delivery tracking badges
                    const readyDeliveryEl = document.getElementById('ready-delivery-count');
                    if (readyDeliveryEl && data.delivery && data.delivery.ready_for_delivery > 0) {
                        readyDeliveryEl.textContent = data.delivery.ready_for_delivery;
                        readyDeliveryEl.style.display = 'inline';
                    }
                    
                    const awaitingReceiptEl = document.getElementById('awaiting-receipt-count');
                    if (awaitingReceiptEl && data.delivery && data.delivery.awaiting_receipt > 0) {
                        awaitingReceiptEl.textContent = data.delivery.awaiting_receipt;
                        awaitingReceiptEl.style.display = 'inline';
                    }
                    
                    const deliveryAlertsEl = document.getElementById('delivery-alerts-count');
                    if (deliveryAlertsEl && data.delivery && data.delivery.alerts > 0) {
                        deliveryAlertsEl.textContent = data.delivery.alerts;
                        deliveryAlertsEl.style.display = 'inline';
                    }
                }
            }));
            
            // Notifications Page Alpine.js Component
            Alpine.data('notificationsPage', () => ({
                notifications: [],
                loading: true,
                hasMore: false,
                limit: 20,
                offset: 0,
                
                loadNotifications() {
                    this.loading = true;
                    
                    fetch(`${window.ConstructLink.baseUrl}/?route=api/notifications&limit=${this.limit}&offset=${this.offset}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (this.offset === 0) {
                                    this.notifications = data.notifications || [];
                                } else {
                                    this.notifications = [...this.notifications, ...(data.notifications || [])];
                                }
                                
                                this.hasMore = data.notifications && data.notifications.length === this.limit;
                            } else {
                                console.error('Failed to load notifications:', data.error);
                                this.notifications = [];
                            }
                        })
                        .catch(error => {
                            console.error('Notifications loading error:', error);
                            this.notifications = [];
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },
                
                loadMore() {
                    this.offset += this.limit;
                    this.loadNotifications();
                },
                
                markAsRead(notificationId) {
                    fetch(`${window.ConstructLink.baseUrl}/?route=api/notifications/mark-read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': window.ConstructLink.csrfToken
                        },
                        body: JSON.stringify({
                            notification_id: notificationId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mark notification as read in local state
                            const notification = this.notifications.find(n => n.id === notificationId);
                            if (notification) {
                                notification.unread = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Mark as read error:', error);
                    });
                },
                
                getNotificationIconClass(type) {
                    const classes = {
                        'warning': 'bg-warning text-dark',
                        'danger': 'bg-danger text-white',
                        'info': 'bg-info text-white',
                        'success': 'bg-success text-white'
                    };
                    return classes[type] || 'bg-secondary text-white';
                }
            }));
        });
        
        // Cleanup function for page navigation
        window.addEventListener('beforeunload', () => {
            // Clear all intervals to prevent memory leaks
            for (let i = 1; i < 99999; i++) clearInterval(i);
        });
    </script>
</body>
</html>
