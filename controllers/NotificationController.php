<?php
/**
 * ConstructLink™ Notification Controller
 * Handles notification views and management
 */

class NotificationController {
    private $auth;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Display notifications page
     */
    public function index() {
        if (!$this->auth->isAuthenticated()) {
            header('Location: ?route=login');
            exit;
        }
        
        $user = $this->auth->getCurrentUser();
        $userRole = $user['role_name'];
        
        // Set page data
        $pageTitle = 'Notifications - ConstructLink™';
        $pageHeader = 'Notifications';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Notifications', 'url' => '?route=notifications']
        ];
        
        // Page actions
        $pageActions = '<button class="btn btn-outline-secondary" onclick="markAllAsRead()">
                          <i class="bi bi-check-all me-1"></i>Mark All as Read
                        </button>';
        
        // Load the view
        $content = $this->renderNotificationsView($user, $userRole);
        
        include APP_ROOT . '/views/layouts/main.php';
    }
    
    /**
     * Render the notifications view
     */
    private function renderNotificationsView($user, $userRole) {
        ob_start();
        ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-bell me-2"></i>Your Notifications
                        </h5>
                    </div>
                    <div class="card-body" x-data="notificationsPage" x-init="loadNotifications()">
                        <!-- Loading State -->
                        <div x-show="loading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading notifications...</p>
                        </div>
                        
                        <!-- No Notifications -->
                        <div x-show="!loading && notifications.length === 0" class="text-center py-5">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">No Notifications</h5>
                            <p class="text-muted">You're all caught up! No new notifications at this time.</p>
                        </div>
                        
                        <!-- Notifications List -->
                        <div x-show="!loading && notifications.length > 0">
                            <template x-for="notification in notifications" :key="notification.id">
                                <div class="notification-item border-bottom py-3" 
                                     :class="notification.unread ? 'bg-light' : ''">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="notification-icon rounded-circle d-flex align-items-center justify-content-center"
                                                 :class="getNotificationIconClass(notification.type)"
                                                 style="width: 50px; height: 50px;">
                                                <i :class="notification.icon"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1" x-text="notification.title"></h6>
                                                    <p class="mb-1 text-muted" x-text="notification.message"></p>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <span x-text="notification.time"></span>
                                                    </small>
                                                </div>
                                                <div class="notification-actions">
                                                    <a :href="notification.url" 
                                                       class="btn btn-sm btn-outline-primary me-2">
                                                        <i class="bi bi-eye me-1"></i>View
                                                    </a>
                                                    <button x-show="notification.unread"
                                                            @click="markAsRead(notification.id)"
                                                            class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Load More Button -->
                        <div x-show="!loading && notifications.length > 0 && hasMore" class="text-center mt-4">
                            <button @click="loadMore()" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-down-circle me-1"></i>Load More
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function markAllAsRead() {
            // Implementation for marking all notifications as read
            if (confirm('Mark all notifications as read?')) {
                // This would require a separate API endpoint
                location.reload();
            }
        }
        </script>
        
        <style>
        .notification-item {
            transition: background-color 0.2s ease;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa !important;
        }
        
        .notification-icon {
            min-width: 50px;
        }
        
        .notification-actions {
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        
        .notification-item:hover .notification-actions {
            opacity: 1;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
?>