/**
 * Dashboard Module
 * Handles dashboard refresh, auto-updates, and interactivity
 *
 * @package ConstructLink
 * @subpackage JavaScript/Modules
 * @version 1.0.0
 * @since 2025-10-28
 */

(function() {
    'use strict';

    /**
     * Dashboard Manager
     */
    const Dashboard = {
        /**
         * Auto-refresh interval (5 minutes)
         */
        autoRefreshInterval: 300000,

        /**
         * Auto-refresh timer ID
         */
        autoRefreshTimer: null,

        /**
         * Initialize dashboard functionality
         */
        init() {
            this.initTooltips();
            this.setupAutoRefresh();
            console.log('[Dashboard] Initialized');
        },

        /**
         * Initialize Bootstrap tooltips
         */
        initTooltips() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        },

        /**
         * Setup auto-refresh for dashboard stats
         */
        setupAutoRefresh() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
            }

            this.autoRefreshTimer = setInterval(() => {
                fetch('?route=dashboard/getStats')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.updateStatsCards(data.data);
                        }
                    })
                    .catch(error => {
                        console.error('[Dashboard] Auto-refresh failed:', error);
                    });
            }, this.autoRefreshInterval);

            console.log('[Dashboard] Auto-refresh enabled (every 5 minutes)');
        },

        /**
         * Refresh entire dashboard
         */
        refreshDashboard() {
            const refreshBtn = document.querySelector('[onclick="Dashboard.refreshDashboard()"]');
            if (!refreshBtn) return;

            const originalText = refreshBtn.innerHTML;

            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Refreshing...';
            refreshBtn.disabled = true;

            // Reload page after brief delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        },

        /**
         * Refresh activities section
         */
        refreshActivities() {
            const refreshBtn = document.querySelector('[onclick="Dashboard.refreshActivities()"]');
            if (!refreshBtn) return;

            const originalText = refreshBtn.innerHTML;

            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
            refreshBtn.disabled = true;

            fetch('?route=dashboard/getStats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('[Dashboard] Activities refreshed');
                        // Update DOM with new activities data
                        // Implementation depends on specific dashboard structure
                    }
                })
                .catch(error => {
                    console.error('[Dashboard] Failed to refresh activities:', error);
                })
                .finally(() => {
                    refreshBtn.innerHTML = originalText;
                    refreshBtn.disabled = false;
                });
        },

        /**
         * Update stats cards with new data
         *
         * @param {Object} data Stats data from API
         */
        updateStatsCards(data) {
            if (!data || !data.assets) return;

            // Update total assets
            const totalElement = document.querySelector('.text-primary h3, .card-accent-neutral h3');
            if (totalElement) {
                totalElement.textContent = new Intl.NumberFormat().format(data.assets.total_assets || 0);
            }

            // Update available assets
            const availableElement = document.querySelector('.text-success h3, .card-accent-success h3');
            if (availableElement) {
                availableElement.textContent = new Intl.NumberFormat().format(data.assets.available_assets || 0);
            }

            // Update in-use assets
            const inUseElement = document.querySelector('.text-warning h3, .card-accent-warning h3');
            if (inUseElement) {
                inUseElement.textContent = new Intl.NumberFormat().format(data.assets.in_use_assets || 0);
            }

            console.log('[Dashboard] Stats updated');
        },

        /**
         * Stop auto-refresh (e.g., when user navigates away)
         */
        stopAutoRefresh() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
                this.autoRefreshTimer = null;
                console.log('[Dashboard] Auto-refresh stopped');
            }
        }
    };

    // Expose Dashboard to global scope
    window.Dashboard = Dashboard;

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Dashboard.init());
    } else {
        Dashboard.init();
    }

    // Stop auto-refresh when page is about to unload
    window.addEventListener('beforeunload', () => Dashboard.stopAutoRefresh());

})();
