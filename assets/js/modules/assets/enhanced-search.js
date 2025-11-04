/**
 * Enhanced Asset Search Module
 * Provides real-time search validation, suggestions, and feedback
 *
 * Extracted from inline script for better maintainability
 */

// CSRF Token (will be provided by config in init.js)
let CSRFTokenValue = '';

/**
 * Enhanced Asset Search Class
 * Handles real-time search validation, suggestions, and discipline detection
 */
export class EnhancedAssetSearch {
    constructor(csrfToken = '') {
        this.searchInput = document.getElementById('search');
        this.searchStatus = document.getElementById('search-status');
        this.searchIcon = document.getElementById('search-icon');
        this.searchFeedback = document.getElementById('search-feedback');
        this.suggestions = document.getElementById('search-suggestions');
        this.debounceTimer = null;
        this.csrfToken = csrfToken || CSRFTokenValue;

        this.init();
    }

    init() {
        if (!this.searchInput) return;

        this.searchInput.addEventListener('input', (e) => this.handleSearch(e));
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.target.form.submit();
            }
        });

        // Initialize on page load if there's a search term
        if (this.searchInput.value.trim()) {
            this.validateSearch(this.searchInput.value.trim());
        }
    }

    handleSearch(event) {
        const value = event.target.value.trim();

        if (value.length < 2) {
            this.clearFeedback();
            return;
        }

        this.updateSearchStatus('searching');

        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.validateSearch(value);
        }, 300);
    }

    async validateSearch(query) {
        try {
            const response = await fetch('/api/assets/enhanced-search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ query: query })
            });

            if (!response.ok) {
                throw new Error('Search validation failed');
            }

            const data = await response.json();
            this.handleSearchResult(data, query);

        } catch (error) {
            console.warn('Enhanced search unavailable, falling back to basic search:', error);
            this.updateSearchStatus('basic');
            this.searchFeedback.innerHTML = '<i class="bi bi-info-circle me-1" aria-hidden="true"></i>Basic search mode';
        }
    }

    handleSearchResult(result, originalQuery) {
        if (result.corrected && result.corrected !== originalQuery) {
            this.updateSearchStatus('corrected');
            this.searchFeedback.innerHTML = `
                <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>
                Did you mean: <strong>${this.escapeHtml(result.corrected)}</strong>?
                <button type="button" class="btn btn-sm btn-link p-0 ms-1"
                        onclick="assetSearch.applySuggestion('${this.escapeHtml(result.corrected)}')">
                    Use this
                </button>
            `;
        } else if (result.suggestions && result.suggestions.length > 0) {
            this.updateSuggestions(result.suggestions);
            this.updateSearchStatus('suggestions');
            this.searchFeedback.innerHTML = `<i class="bi bi-lightbulb me-1" aria-hidden="true"></i>Showing ${result.suggestions.length} suggestions`;
        } else {
            this.updateSearchStatus('valid');
            this.searchFeedback.innerHTML = '';
        }

        if (result.disciplines && result.disciplines.length > 0) {
            this.searchFeedback.innerHTML += `
                <div class="mt-1">
                    <small class="text-muted">
                        <i class="bi bi-tags me-1" aria-hidden="true"></i>Disciplines: ${result.disciplines.map(d => this.escapeHtml(d)).join(', ')}
                    </small>
                </div>
            `;
        }
    }

    updateSearchStatus(status) {
        const iconClasses = {
            'searching': 'bi-arrow-clockwise text-primary spin',
            'corrected': 'bi-lightbulb text-warning',
            'suggestions': 'bi-list text-info',
            'valid': 'bi-check-circle text-success',
            'basic': 'bi-search text-muted'
        };

        this.searchIcon.className = `bi ${iconClasses[status] || 'bi-search text-muted'}`;
    }

    updateSuggestions(suggestions) {
        if (!this.suggestions) return;

        this.suggestions.innerHTML = '';
        suggestions.slice(0, 8).forEach(suggestion => {
            const option = document.createElement('option');
            option.value = suggestion;
            this.suggestions.appendChild(option);
        });
    }

    applySuggestion(suggestion) {
        this.searchInput.value = suggestion;
        this.clearFeedback();
        // Auto-submit the form
        this.searchInput.form.submit();
    }

    clearFeedback() {
        this.searchFeedback.innerHTML = '';
        if (this.suggestions) {
            this.suggestions.innerHTML = '';
        }
        this.updateSearchStatus('basic');
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Set CSRF token from global config
export function setCsrfToken(token) {
    CSRFTokenValue = token;
}
