<?php
$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="?route=dashboard">
            <i class="bi bi-building me-2"></i>
            <span class="fw-bold">ConstructLink™</span>
        </a>
        
        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Search Bar -->
            <div class="mx-auto" style="max-width: 400px;">
                <div class="input-group" x-data="quickSearch">
                    <input type="text" 
                           class="form-control" 
                           placeholder="Quick search assets..." 
                           x-model="query"
                           x-on:input.debounce.300ms="search()"
                           x-on:keydown.escape="clearSearch()">
                    <button class="btn btn-outline-light" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                    
                    <!-- Search Results Dropdown -->
                    <div class="position-absolute top-100 start-0 w-100 bg-white border rounded-bottom shadow-lg z-3" 
                         x-show="results.length > 0" 
                         x-transition
                         style="display: none;">
                        <template x-for="result in results" :key="result.id">
                            <a :href="result.url" 
                               class="d-block p-2 text-decoration-none text-dark border-bottom hover-bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong x-text="result.ref"></strong> - <span x-text="result.name"></span>
                                        <br>
                                        <small class="text-muted">
                                            <span x-text="result.category"></span> | <span x-text="result.project"></span>
                                        </small>
                                    </div>
                                    <span class="badge" 
                                          :class="getStatusBadgeClass(result.status)" 
                                          x-text="result.status_label"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- Right side navigation -->
            <ul class="navbar-nav ms-auto">
                <!-- Notifications -->
                <li class="nav-item dropdown" x-data="notifications">
                    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                              x-show="unreadCount > 0" 
                              x-text="unreadCount"
                              style="display: none;"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>

                        <!-- Loading skeleton -->
                        <template x-if="isLoading && notifications.length === 0">
                            <li class="dropdown-item">
                                <div class="d-flex">
                                    <div class="placeholder-glow w-100">
                                        <span class="placeholder col-12"></span>
                                        <span class="placeholder col-8"></span>
                                    </div>
                                </div>
                            </li>
                        </template>

                        <!-- Notifications list -->
                        <template x-for="notification in notifications" :key="notification.id">
                            <li>
                                <a class="dropdown-item" :href="notification.url">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i :class="notification.icon" class="text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <div class="fw-semibold" x-text="notification.title"></div>
                                            <div class="small text-muted" x-text="notification.message"></div>
                                            <div class="small text-muted" x-text="notification.time"></div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        </template>

                        <!-- Empty state -->
                        <template x-if="!isLoading && notifications.length === 0">
                            <li class="dropdown-item text-muted text-center">
                                No new notifications
                            </li>
                        </template>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="?route=notifications">View All Notifications</a></li>
                    </ul>
                </li>
                
                <!-- QR Scanner -->
                <?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="?route=scanner" title="QR Scanner">
                        <i class="bi bi-qr-code-scan"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center me-2" 
                             style="width: 32px; height: 32px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($user['full_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?= htmlspecialchars($userRole) ?></h6></li>
                        <li><a class="dropdown-item" href="?route=users/profile">
                            <i class="bi bi-person me-2"></i>Profile
                        </a></li>
                        <li><a class="dropdown-item" href="?route=auth/change-password">
                            <i class="bi bi-key me-2"></i>Change Password
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($userRole === 'System Admin'): ?>
                        <li><a class="dropdown-item" href="?route=admin">
                            <i class="bi bi-gear me-2"></i>System Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="?route=logout">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Add top padding to body to account for fixed navbar -->
<style>
body { padding-top: 76px; }
.hover-bg-light:hover { background-color: #f8f9fa !important; }
</style>

