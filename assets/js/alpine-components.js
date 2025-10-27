/**
 * ConstructLink™ Alpine.js Components
 * Optimized with singleton pattern, proper interval management, and request caching
 */

/**
 * IntervalManager - Centralized interval timer management
 * Prevents memory leaks and ensures proper cleanup
 */
window.ConstructLink.IntervalManager = {
    intervals: new Map(),

    register(key, intervalId) {
        // Clear existing interval if present
        if (this.intervals.has(key)) {
            clearInterval(this.intervals.get(key));
        }
        this.intervals.set(key, intervalId);
    },

    clear(key) {
        if (this.intervals.has(key)) {
            clearInterval(this.intervals.get(key));
            this.intervals.delete(key);
        }
    },

    clearAll() {
        this.intervals.forEach((intervalId) => clearInterval(intervalId));
        this.intervals.clear();
    }
};

/**
 * RequestCache - Simple request caching with TTL
 * Reduces redundant API calls
 */
window.ConstructLink.RequestCache = {
    cache: new Map(),
    defaultTTL: 300000, // 5 minutes

    get(key) {
        const cached = this.cache.get(key);
        if (!cached) return null;

        // Check if expired
        if (Date.now() - cached.timestamp > cached.ttl) {
            this.cache.delete(key);
            return null;
        }

        return cached.data;
    },

    set(key, data, ttl = null) {
        this.cache.set(key, {
            data: data,
            timestamp: Date.now(),
            ttl: ttl || this.defaultTTL
        });
    },

    clear(key) {
        if (key) {
            this.cache.delete(key);
        } else {
            this.cache.clear();
        }
    }
};

/**
 * RequestDebouncer - Prevents overlapping requests
 * Ensures only one request of each type is active
 */
window.ConstructLink.RequestDebouncer = {
    activeRequests: new Map(),

    async execute(key, requestFn, minDelay = 300) {
        // If request is already active, wait for it
        if (this.activeRequests.has(key)) {
            return this.activeRequests.get(key);
        }

        // Add minimum delay to prevent rapid-fire requests
        const delayPromise = new Promise(resolve => setTimeout(resolve, minDelay));

        // Execute request
        const requestPromise = Promise.all([requestFn(), delayPromise])
            .then(([result]) => result)
            .finally(() => {
                this.activeRequests.delete(key);
            });

        this.activeRequests.set(key, requestPromise);
        return requestPromise;
    }
};

// Global Alpine.js components and utilities - SINGLETON PATTERN
document.addEventListener('alpine:init', () => {
    // Component instance tracking to prevent duplicates
    const componentInstances = {
        notifications: null,
        sidebarStats: null
    };

    // Quick Search Alpine.js Component
    Alpine.data('quickSearch', () => ({
        query: '',
        results: [],
        loading: false,
        searchTimeout: null,

        search() {
            if (this.query.length < 2) {
                this.results = [];
                return;
            }

            // Clear existing timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // Debounce search
            this.searchTimeout = setTimeout(() => {
                this.performSearch();
            }, 300);
        },

        async performSearch() {
            this.loading = true;
            const cacheKey = `search_${this.query}`;

            // Check cache first
            const cached = window.ConstructLink.RequestCache.get(cacheKey);
            if (cached) {
                this.results = cached.slice(0, 5);
                this.loading = false;
                return;
            }

            try {
                const response = await fetch(`${window.ConstructLink.baseUrl}/?route=api/assets/search&q=${encodeURIComponent(this.query)}`);
                const data = await response.json();

                if (data.success) {
                    const results = data.results || [];
                    window.ConstructLink.RequestCache.set(cacheKey, results, 60000); // 1 minute cache
                    this.results = results.slice(0, 5);
                }
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                this.loading = false;
            }
        },

        clearSearch() {
            this.query = '';
            this.results = [];
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
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

    // Notifications Alpine.js Component - SINGLETON WITH PROPER CLEANUP
    Alpine.data('notifications', () => ({
        notifications: [],
        unreadCount: 0,
        isLoading: false,
        instanceId: null,

        init() {
            // Singleton pattern - only one instance should load data
            if (componentInstances.notifications) {
                console.log('Notifications component already initialized - using singleton');
                // Reference existing instance data
                this.notifications = componentInstances.notifications.notifications;
                this.unreadCount = componentInstances.notifications.unreadCount;
                return;
            }

            componentInstances.notifications = this;
            this.instanceId = 'notifications_singleton';

            // Load notifications immediately
            this.loadNotifications();

            // Register refresh interval with IntervalManager
            const intervalId = setInterval(() => this.loadNotifications(), 300000); // 5 minutes
            window.ConstructLink.IntervalManager.register(this.instanceId, intervalId);
        },

        destroy() {
            // Clean up only if this is the singleton instance
            if (componentInstances.notifications === this) {
                window.ConstructLink.IntervalManager.clear(this.instanceId);
                componentInstances.notifications = null;
            }
        },

        async loadNotifications() {
            const requestKey = 'notifications_load';

            try {
                // No minimum delay for notifications - load immediately
                await window.ConstructLink.RequestDebouncer.execute(requestKey, async () => {
                    this.isLoading = true;

                    // Check cache
                    const cacheKey = 'notifications_data';
                    const cached = window.ConstructLink.RequestCache.get(cacheKey);
                    if (cached) {
                        this.notifications = cached.notifications || [];
                        this.unreadCount = cached.unread_count || 0;
                        this.isLoading = false;
                        return;
                    }

                    // Create abort controller for timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 5000);

                    try {
                        const response = await fetch(`${window.ConstructLink.baseUrl}/?route=api/notifications&limit=5`, {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' },
                            signal: controller.signal
                        });

                        clearTimeout(timeoutId);

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.notifications = data.notifications || [];
                            this.unreadCount = data.unread_count || 0;

                            // Cache the result
                            window.ConstructLink.RequestCache.set(cacheKey, {
                                notifications: this.notifications,
                                unread_count: this.unreadCount
                            }, 60000); // 1 minute cache
                        } else {
                            console.error('Failed to load notifications:', data.error);
                            this.notifications = [];
                            this.unreadCount = 0;
                        }
                    } catch (error) {
                        clearTimeout(timeoutId);
                        if (error.name === 'AbortError') {
                            console.warn('Notifications request timeout - continuing without notifications');
                        } else {
                            console.error('Notifications loading error:', error);
                        }
                        this.notifications = [];
                        this.unreadCount = 0;
                    } finally {
                        this.isLoading = false;
                    }
                }, 0); // Remove 300ms delay - load immediately
            } catch (error) {
                console.error('Request debouncer error:', error);
            }
        }
    }));

    // Sidebar Stats Alpine.js Component - SINGLETON WITH OPTIMIZED LOADING
    Alpine.data('sidebarStats', () => ({
        stats: {},
        lastUpdated: '',
        isLoading: false,
        instanceId: null,

        init() {
            // Singleton pattern
            if (componentInstances.sidebarStats) {
                console.log('Sidebar stats component already initialized - using singleton');
                this.stats = componentInstances.sidebarStats.stats;
                this.lastUpdated = componentInstances.sidebarStats.lastUpdated;
                return;
            }

            componentInstances.sidebarStats = this;
            this.instanceId = 'sidebar_stats_singleton';

            // Load stats immediately (no delay needed - debouncer handles this)
            this.loadStats();

            // Register refresh interval
            const intervalId = setInterval(() => this.loadStats(), 300000); // 5 minutes
            window.ConstructLink.IntervalManager.register(this.instanceId, intervalId);
        },

        destroy() {
            if (componentInstances.sidebarStats === this) {
                window.ConstructLink.IntervalManager.clear(this.instanceId);
                componentInstances.sidebarStats = null;
            }
        },

        async loadStats() {
            const requestKey = 'sidebar_stats_load';

            try {
                // No minimum delay - load immediately
                await window.ConstructLink.RequestDebouncer.execute(requestKey, async () => {
                    // Check cache
                    const cacheKey = 'sidebar_stats_data';
                    const cached = window.ConstructLink.RequestCache.get(cacheKey);
                    if (cached) {
                        this.stats = cached.stats;
                        this.lastUpdated = cached.lastUpdated;
                        if (cached.fullData) {
                            this.updateNotificationBadges(cached.fullData);
                        }
                        return;
                    }

                    const response = await fetch(`${window.ConstructLink.baseUrl}/?route=api/dashboard/stats`);
                    const data = await response.json();

                    if (data.success && data.data.assets) {
                        this.stats = data.data.assets;
                        this.lastUpdated = new Date().toLocaleTimeString();

                        // Update notification badges
                        this.updateNotificationBadges(data.data);

                        // Cache the result
                        window.ConstructLink.RequestCache.set(cacheKey, {
                            stats: this.stats,
                            lastUpdated: this.lastUpdated,
                            fullData: data.data
                        }, 60000); // 1 minute cache
                    }
                }, 0); // Remove 300ms delay - load immediately
            } catch (error) {
                console.error('Stats loading error:', error);
            }
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
                    // Clear notification cache to force refresh
                    window.ConstructLink.RequestCache.clear('notifications_data');
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

// Proper cleanup function for page navigation
window.addEventListener('beforeunload', () => {
    // Use IntervalManager for proper cleanup
    window.ConstructLink.IntervalManager.clearAll();
});

// Clear cache on page visibility change after being hidden for a while
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Clear cache to force fresh data when user returns
        window.ConstructLink.RequestCache.clear();
    }
});
