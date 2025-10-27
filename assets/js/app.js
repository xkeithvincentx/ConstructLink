/**
 * ConstructLink™ Application JavaScript
 * Optimized for performance and user experience
 */

// Initialize ConstructLink application
const ConstructLink = {
    // Configuration
    config: {
        refreshInterval: 30000, // 30 seconds
        apiTimeout: 5000, // 5 seconds
        cacheTimeout: 300000 // 5 minutes
    },
    
    // Cache for API responses
    cache: new Map(),
    
    // Initialize application
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.startPeriodicRefresh();
        console.log("ConstructLink™ initialized successfully");
    },
    
    // Setup event listeners
    setupEventListeners: function() {
        // Handle form submissions
        document.addEventListener("submit", this.handleFormSubmit.bind(this));
        
        // Handle navigation clicks
        document.addEventListener("click", this.handleNavigation.bind(this));
        
        // Handle page visibility changes
        document.addEventListener("visibilitychange", this.handleVisibilityChange.bind(this));
    },
    
    // Initialize components
    initializeComponents: function() {
        // Initialize tooltips
        this.initTooltips();
        
        // Initialize modals
        this.initModals();
        
        // Initialize search functionality
        this.initSearch();
        
        // Initialize auto-refresh
        this.initAutoRefresh();
    },
    
    // Initialize tooltips
    initTooltips: function() {
        if (typeof bootstrap !== "undefined") {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },
    
    // Initialize modals
    initModals: function() {
        // Auto-focus first input in modals
        document.querySelectorAll(".modal").forEach(modal => {
            modal.addEventListener("shown.bs.modal", function() {
                const firstInput = modal.querySelector("input, select, textarea");
                if (firstInput) {
                    firstInput.focus();
                }
            });
        });
    },
    
    // Initialize search functionality
    initSearch: function() {
        const searchInputs = document.querySelectorAll("input[type=\"search\"], .search-input");
        
        searchInputs.forEach(input => {
            let timeout;
            input.addEventListener("input", function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    ConstructLink.performSearch(this.value, this.dataset.target);
                }, 300);
            });
        });
    },
    
    // Initialize auto-refresh
    initAutoRefresh: function() {
        // Only refresh if user is active and page is visible
        setInterval(() => {
            if (document.visibilityState === "visible" && this.isUserActive()) {
                this.refreshCurrentPage();
            }
        }, this.config.refreshInterval);
    },
    
    // Start periodic refresh (compatibility method)
    startPeriodicRefresh: function() {
        this.initAutoRefresh();
    },
    
    // Handle form submissions
    handleFormSubmit: function(event) {
        const form = event.target;
        
        if (form.classList.contains("ajax-form")) {
            event.preventDefault();
            this.submitFormAjax(form);
        }
        
        // Add loading state to submit buttons
        const submitBtn = form.querySelector("button[type=\"submit\"], input[type=\"submit\"]");
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = "Processing...";
            
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }, 3000);
        }
    },
    
    // Handle navigation clicks
    handleNavigation: function(event) {
        const link = event.target.closest("a");

        if (link && link.classList.contains("nav-link")) {
            // Don't show loading state for:
            // - Dropdown toggles (notification, user menu)
            // - PDF generation or file download links
            // - Links with # (modal triggers, dropdowns)
            const href = link.getAttribute("href");
            const isDropdownToggle = link.hasAttribute("data-bs-toggle");
            const isHashLink = href === "#" || (href && href.startsWith("#"));
            const isFileDownload = href && (href.includes("generatePO") || href.includes("download") || href.includes("export"));

            if (isDropdownToggle || isHashLink || isFileDownload) {
                return; // Let the browser handle these normally without loading overlay
            }

            // Add loading state only for actual page navigation
            this.showLoadingState();
        }
    },
    
    // Handle visibility changes
    handleVisibilityChange: function() {
        if (document.visibilityState === "visible") {
            // Refresh data when user returns to tab
            setTimeout(() => {
                this.refreshCurrentPage();
            }, 1000);
        }
    },
    
    // Show loading state
    showLoadingState: function() {
        // Remove existing loader if present
        const existingLoader = document.querySelector(".loading-overlay");
        if (existingLoader) {
            existingLoader.remove();
        }

        const loader = document.createElement("div");
        loader.className = "loading-overlay";
        loader.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        document.body.appendChild(loader);

        // Remove loader when page starts loading or after 3 seconds max
        const removeLoader = () => {
            if (loader.parentNode) {
                loader.remove();
            }
        };

        // Remove on page navigation start
        window.addEventListener("beforeunload", removeLoader, { once: true });

        // Fallback timeout (reduced from 5s to 3s)
        setTimeout(removeLoader, 3000);
    },
    
    // Check if user is active
    isUserActive: function() {
        const lastActivity = localStorage.getItem("lastActivity");
        const now = Date.now();
        
        if (!lastActivity || (now - parseInt(lastActivity)) > 300000) { // 5 minutes
            return false;
        }
        
        return true;
    },
    
    // Refresh current page data
    refreshCurrentPage: function() {
        const currentRoute = new URLSearchParams(window.location.search).get("route") || "dashboard";
        
        // Only refresh dashboard stats for now
        if (currentRoute === "dashboard" || currentRoute === "") {
            this.refreshDashboardStats();
        }
    },
    
    // Refresh dashboard statistics
    refreshDashboardStats: function() {
        const cacheKey = "dashboard_stats";
        const cached = this.cache.get(cacheKey);
        
        if (cached && (Date.now() - cached.timestamp) < this.config.cacheTimeout) {
            return; // Use cached data
        }
        
        fetch("?route=api/dashboard/stats", {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => response.json())
        .then(data => {
            this.updateDashboardStats(data);
            this.cache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
        })
        .catch(error => {
            console.warn("Failed to refresh dashboard stats:", error);
        });
    },
    
    // Update dashboard statistics
    updateDashboardStats: function(stats) {
        // Update stat cards if they exist
        Object.keys(stats).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = stats[key];
            }
        });
    },
    
    // Perform search
    performSearch: function(query, target) {
        if (!query || query.length < 2) return;
        
        const cacheKey = `search_${target}_${query}`;
        const cached = this.cache.get(cacheKey);
        
        if (cached && (Date.now() - cached.timestamp) < this.config.cacheTimeout) {
            this.displaySearchResults(cached.data, target);
            return;
        }
        
        fetch(`?route=api/${target}/search&q=${encodeURIComponent(query)}`, {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => response.json())
        .then(data => {
            this.displaySearchResults(data, target);
            this.cache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
        })
        .catch(error => {
            console.warn("Search failed:", error);
        });
    },
    
    // Display search results
    displaySearchResults: function(results, target) {
        const resultsContainer = document.querySelector(`#${target}-search-results`);
        if (!resultsContainer) return;
        
        if (results.length === 0) {
            resultsContainer.innerHTML = "<p class=\"text-muted\">No results found</p>";
            return;
        }
        
        let html = "<ul class=\"list-group\">";
        results.forEach(item => {
            html += `<li class=\"list-group-item\">${item.name || item.title}</li>`;
        });
        html += "</ul>";
        
        resultsContainer.innerHTML = html;
    },
    
    // Submit form via AJAX
    submitFormAjax: function(form) {
        const formData = new FormData(form);
        const action = form.action || window.location.href;
        
        fetch(action, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification("Success", data.message, "success");
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                this.showNotification("Error", data.message, "error");
            }
        })
        .catch(error => {
            this.showNotification("Error", "An error occurred", "error");
            console.error("Form submission error:", error);
        });
    },
    
    // Show notification
    showNotification: function(title, message, type = "info") {
        // Create notification element
        const notification = document.createElement("div");
        notification.className = `alert alert-${type === "error" ? "danger" : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
        notification.innerHTML = `
            <strong>${title}:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
};

// Track user activity
document.addEventListener("click", () => {
    localStorage.setItem("lastActivity", Date.now().toString());
});

document.addEventListener("keypress", () => {
    localStorage.setItem("lastActivity", Date.now().toString());
});

// Initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        ConstructLink.init();
    });
} else {
    ConstructLink.init();
}

// Add CSS for loading overlay
const style = document.createElement("style");
style.textContent = `
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
`;
document.head.appendChild(style);
