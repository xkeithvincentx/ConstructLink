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

    /**
     * ========================================
     * DASHBOARD-SPECIFIC ALPINE.JS COMPONENTS
     * ========================================
     * Reusable interactive components for role-specific dashboards
     * Following WCAG 2.1 AA accessibility standards
     */

    /**
     * Collapsible Card Component
     * Used for: Equipment type cards, category expansions, detailed views
     * Features: Smooth transitions, keyboard accessible, ARIA support
     */
    Alpine.data('collapsibleCard', (defaultOpen = false) => ({
        open: defaultOpen,

        toggle() {
            this.open = !this.open;
        },

        expand() {
            this.open = true;
        },

        collapse() {
            this.open = false;
        },

        // ARIA label support
        get ariaExpanded() {
            return this.open ? 'true' : 'false';
        },

        // Icon class helper
        get chevronClass() {
            return this.open ? 'bi-chevron-up' : 'bi-chevron-down';
        },

        // Button text helper
        get toggleText() {
            return this.open ? 'Hide' : 'Show';
        }
    }));

    /**
     * Filterable List Component
     * Used for: Pending actions, inventory lists, project lists
     * Features: Real-time search, category filtering, sorting
     */
    Alpine.data('filterableList', (items, options = {}) => ({
        items: items,
        search: '',
        filter: 'all',
        sortBy: options.defaultSort || 'label',
        sortDesc: false,

        get filteredItems() {
            let filtered = this.items;

            // Search filter (case-insensitive, searches all values)
            if (this.search) {
                const searchLower = this.search.toLowerCase();
                filtered = filtered.filter(item =>
                    Object.values(item).some(val =>
                        String(val).toLowerCase().includes(searchLower)
                    )
                );
            }

            // Category filter
            if (this.filter !== 'all') {
                if (this.filter === 'critical') {
                    filtered = filtered.filter(item => item.critical === true);
                } else if (this.filter === 'pending') {
                    filtered = filtered.filter(item => item.count > 0);
                } else if (this.filter === 'empty') {
                    filtered = filtered.filter(item => item.count === 0);
                } else {
                    filtered = filtered.filter(item => item.category === this.filter);
                }
            }

            // Sorting
            filtered.sort((a, b) => {
                const aVal = a[this.sortBy];
                const bVal = b[this.sortBy];

                // Handle numeric vs string sorting
                let comparison = 0;
                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    comparison = aVal - bVal;
                } else {
                    comparison = String(aVal).localeCompare(String(bVal));
                }

                return this.sortDesc ? -comparison : comparison;
            });

            return filtered;
        },

        get categories() {
            return [...new Set(this.items.map(i => i.category || 'uncategorized'))];
        },

        get criticalCount() {
            return this.items.filter(i => i.critical === true).length;
        },

        get pendingCount() {
            return this.items.filter(i => i.count > 0).length;
        },

        setSortBy(field) {
            if (this.sortBy === field) {
                this.sortDesc = !this.sortDesc;
            } else {
                this.sortBy = field;
                this.sortDesc = false;
            }
        },

        clearFilters() {
            this.search = '';
            this.filter = 'all';
        },

        setFilter(filterType) {
            this.filter = filterType;
        }
    }));

    /**
     * Stat Card with Trend Component
     * Used for: Dashboard metrics, KPI cards, performance indicators
     * Features: Trend calculations, visual indicators, accessibility
     */
    Alpine.data('statCard', (current, previous, options = {}) => ({
        current: current,
        previous: previous,
        label: options.label || 'Metric',
        showTrend: options.showTrend !== false,

        get change() {
            return this.current - this.previous;
        },

        get changePercent() {
            if (this.previous === 0) return 0;
            return ((this.change / this.previous) * 100).toFixed(1);
        },

        get trendIcon() {
            if (this.change > 0) return 'bi-arrow-up-circle-fill';
            if (this.change < 0) return 'bi-arrow-down-circle-fill';
            return 'bi-dash-circle';
        },

        get trendClass() {
            if (this.change > 0) return 'text-success';
            if (this.change < 0) return 'text-danger';
            return 'text-muted';
        },

        get trendDirection() {
            if (this.change > 0) return 'increase';
            if (this.change < 0) return 'decrease';
            return 'no change';
        },

        // ARIA live region content
        get ariaLiveContent() {
            if (!this.showTrend || this.change === 0) return `${this.label}: ${this.current}`;
            return `${this.label}: ${this.current}, ${Math.abs(this.change)} ${this.trendDirection} (${Math.abs(this.changePercent)}%)`;
        }
    }));

    /**
     * Toast Notification Manager
     * Used for: Success messages, error alerts, user feedback
     * Features: Auto-dismiss, stacking, accessibility, animations
     */
    Alpine.data('toastManager', () => ({
        toasts: [],
        nextId: 1,

        init() {
            // Listen for global toast events
            window.addEventListener('show-toast', (event) => {
                this.addToast(event.detail);
            });
        },

        addToast(toast) {
            const id = this.nextId++;
            const newToast = {
                id,
                type: toast.type || 'info',
                title: toast.title || 'Notification',
                message: toast.message || '',
                duration: toast.duration || 5000,
                dismissible: toast.dismissible !== false
            };

            this.toasts.push(newToast);

            // Auto-dismiss after duration
            if (newToast.duration > 0) {
                setTimeout(() => this.removeToast(id), newToast.duration);
            }
        },

        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },

        clearAll() {
            this.toasts = [];
        },

        getBadgeClass(type) {
            const classes = {
                'success': 'bg-success',
                'danger': 'bg-danger',
                'warning': 'bg-warning text-dark',
                'info': 'bg-info'
            };
            return classes[type] || 'bg-secondary';
        },

        getIconClass(type) {
            const icons = {
                'success': 'bi-check-circle-fill',
                'danger': 'bi-exclamation-triangle-fill',
                'warning': 'bi-exclamation-circle-fill',
                'info': 'bi-info-circle-fill'
            };
            return icons[type] || 'bi-bell-fill';
        }
    }));

    /**
     * Data Table Component with Sorting, Filtering, Pagination
     * Used for: Inventory tables, project distribution, equipment lists
     * Features: Client-side sorting, search, pagination, responsive
     */
    Alpine.data('dataTable', (columns, rows, options = {}) => ({
        columns: columns,
        rows: rows,
        search: '',
        sortBy: options.defaultSort || (columns.length > 0 ? columns[0].key : 'id'),
        sortDesc: false,
        currentPage: 1,
        perPage: options.perPage || 10,
        enablePagination: options.enablePagination !== false,

        get filteredRows() {
            if (!this.search) return this.rows;

            const searchLower = this.search.toLowerCase();
            return this.rows.filter(row =>
                this.columns.some(col => {
                    const value = row[col.key];
                    return value && String(value).toLowerCase().includes(searchLower);
                })
            );
        },

        get sortedRows() {
            return [...this.filteredRows].sort((a, b) => {
                const aVal = a[this.sortBy];
                const bVal = b[this.sortBy];

                // Handle different data types
                let comparison = 0;
                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    comparison = aVal - bVal;
                } else if (aVal === null || aVal === undefined) {
                    comparison = 1;
                } else if (bVal === null || bVal === undefined) {
                    comparison = -1;
                } else {
                    comparison = String(aVal).localeCompare(String(bVal));
                }

                return this.sortDesc ? -comparison : comparison;
            });
        },

        get paginatedRows() {
            if (!this.enablePagination) return this.sortedRows;

            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.sortedRows.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.sortedRows.length / this.perPage);
        },

        get hasResults() {
            return this.filteredRows.length > 0;
        },

        get resultCount() {
            return this.filteredRows.length;
        },

        setSortBy(key) {
            if (this.sortBy === key) {
                this.sortDesc = !this.sortDesc;
            } else {
                this.sortBy = key;
                this.sortDesc = false;
            }
        },

        getSortIcon(key) {
            if (this.sortBy !== key) return 'bi-arrow-down-up';
            return this.sortDesc ? 'bi-sort-down' : 'bi-sort-up';
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        nextPage() {
            this.goToPage(this.currentPage + 1);
        },

        prevPage() {
            this.goToPage(this.currentPage - 1);
        },

        // ARIA helpers
        get ariaLabel() {
            return `Data table with ${this.resultCount} results, page ${this.currentPage} of ${this.totalPages}`;
        }
    }));

    /**
     * Form Validation Component
     * Used for: Dashboard filters, quick actions, input validation
     * Features: Real-time validation, custom rules, accessibility
     */
    Alpine.data('formValidator', (rules) => ({
        formData: {},
        errors: {},
        touched: {},

        init() {
            // Initialize formData from rules
            Object.keys(rules).forEach(field => {
                this.formData[field] = '';
                this.errors[field] = '';
                this.touched[field] = false;
            });
        },

        validate(field) {
            this.touched[field] = true;
            const value = this.formData[field];
            const fieldRules = rules[field];

            // Required validation
            if (fieldRules.required && !value) {
                this.errors[field] = fieldRules.requiredMessage || 'This field is required';
                return false;
            }

            // Pattern validation
            if (fieldRules.pattern && value && !fieldRules.pattern.test(value)) {
                this.errors[field] = fieldRules.patternMessage || 'Invalid format';
                return false;
            }

            // Min length validation
            if (fieldRules.minLength && value && value.length < fieldRules.minLength) {
                this.errors[field] = `Minimum length is ${fieldRules.minLength} characters`;
                return false;
            }

            // Max length validation
            if (fieldRules.maxLength && value && value.length > fieldRules.maxLength) {
                this.errors[field] = `Maximum length is ${fieldRules.maxLength} characters`;
                return false;
            }

            // Custom validation
            if (fieldRules.custom && !fieldRules.custom(value)) {
                this.errors[field] = fieldRules.customMessage || 'Validation failed';
                return false;
            }

            // Clear error if all validations pass
            this.errors[field] = '';
            return true;
        },

        validateAll() {
            let isValid = true;
            Object.keys(rules).forEach(field => {
                if (!this.validate(field)) {
                    isValid = false;
                }
            });
            return isValid;
        },

        hasError(field) {
            return this.touched[field] && this.errors[field] !== '';
        },

        isValid(field) {
            return this.touched[field] && this.errors[field] === '';
        },

        reset() {
            Object.keys(rules).forEach(field => {
                this.formData[field] = '';
                this.errors[field] = '';
                this.touched[field] = false;
            });
        },

        // Bootstrap validation classes
        getFieldClass(field) {
            if (!this.touched[field]) return '';
            return this.hasError(field) ? 'is-invalid' : 'is-valid';
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
