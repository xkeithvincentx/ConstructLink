<?php
/**
 * ============================================================================
 * TRANSFER FILTERS PARTIAL
 * ============================================================================
 *
 * Displays filter form for transfers with responsive design (mobile offcanvas + desktop card)
 *
 * ARCHITECTURE:
 * - Filter Flow: User input ($_GET) → Validation functions → $validatedFilters array
 * - Controller reads $_GET → Model applies filters → Results returned
 * - Defense-in-depth validation: Client-side Alpine.js + Server-side PHP
 *
 * FILTER TYPES:
 * 1. Status Filter: Workflow status (role-based visibility)
 *    - Pending Verification: Project Managers can verify transfers from their projects
 *    - Pending Approval: Directors (Asset/Finance) can approve verified transfers
 *    - Approved: Warehouseman can prepare for transit
 *    - In Transit: Transfer is currently being transported
 *    - Completed: Transfer successfully received at destination
 *    - Canceled: Transfer request was canceled
 *
 * 2. Type Filter: Temporary vs Permanent
 *    - Temporary: Assets will be returned after use
 *    - Permanent: Assets permanently reassigned to new project
 *
 * 3. Project Filters: From Project, To Project
 *    - From Project: Source project where asset is currently located
 *    - To Project: Destination project where asset will be transferred
 *
 * 4. Date Range: Transfer date range (transfer_date field)
 *    - Date From: Start of date range for transfer_date
 *    - Date To: End of date range for transfer_date
 *    - Validation: Ensures date_from <= date_to
 *
 * 5. Search: Asset name, reference, or transfer reason
 *    - Searches across: asset names, transfer references, transfer reasons
 *    - Uses debouncing (500ms) to prevent excessive form submissions
 *
 * QUICK FILTERS (Role-Based Buttons):
 * - "My Verifications" (Project Managers): Filters to Pending Verification status
 *   - Shows transfers from projects managed by the current user
 *   - One-click access to pending verification queue
 *
 * - "My Approvals" (Asset/Finance Directors): Filters to Pending Approval status
 *   - Shows transfers awaiting director-level approval
 *   - One-click access to pending approval queue
 *
 * - "In Transit" (All Users): Filters to In Transit status
 *   - Shows transfers currently being transported
 *   - Useful for tracking active transfers
 *
 * ALPINE.JS INTEGRATION:
 * - x-data="transferFilters()": Main component initialization
 * - @change="autoSubmit": Auto-submit form when dropdowns change
 * - x-model="searchQuery": Two-way binding for search input
 * - @input.debounce.500ms="autoSubmit": Debounced search submission
 * - @click="quickFilter(status)": Quick filter button handlers
 * - @change="validateDateRange": Date range validation with inline errors
 * - x-ref="dateFrom", x-ref="dateTo": DOM references for date validation
 *
 * SECURITY:
 * - All inputs validated server-side with dedicated validation functions
 * - XSS prevention: htmlspecialchars(..., ENT_QUOTES, 'UTF-8') on all outputs
 * - SQL injection prevention: Parameterized queries in TransferModel
 * - CSRF protection: Handled at controller level (GET requests don't need CSRF)
 * - Input sanitization: strip_tags, length limits, type validation
 *
 * ACCESSIBILITY (WCAG 2.1 AA):
 * - aria-label on all interactive elements
 * - aria-describedby for help text
 * - role="alert" and aria-live="polite" for error messages
 * - Keyboard navigation: Tab, Enter, Arrow keys supported
 * - Screen reader friendly: Proper labels and semantic HTML
 * - Focus management: Maintained during dynamic updates
 *
 * RESPONSIVE DESIGN:
 * - Mobile: Offcanvas panel (full-screen overlay)
 * - Desktop: Card with inline filters
 * - Same validation logic applies to both forms
 * - Active filter count badge on mobile button
 *
 * PERFORMANCE:
 * - Auto-submit on dropdown change (no manual filter button needed)
 * - Debounced search (500ms) prevents excessive requests
 * - Client-side date validation reduces server round-trips
 * - Efficient DOM manipulation using Alpine.js reactivity
 *
 * ============================================================================
 */

// Ensure required variables exist (defensive check)
$projects = $projects ?? [];

/**
 * ============================================================================
 * INPUT VALIDATION HELPERS
 * ============================================================================
 *
 * Defense-in-depth validation for all filter parameters.
 * Prevents SQL injection, XSS, and invalid parameter attacks.
 *
 * Validation Strategy:
 * 1. Whitelist approach: Only allow known-good values
 * 2. Type validation: Ensure correct data types (int, string, date)
 * 3. Format validation: Verify date formats, string lengths
 * 4. Sanitization: Strip tags, trim whitespace, escape output
 *
 * Transfer Status Workflow:
 * Pending Verification → Pending Approval → Approved → In Transit → Completed
 *                                      ↓
 *                                  Canceled (any stage)
 *
 * ============================================================================
 */

/**
 * Validate transfer status parameter against allowed values
 *
 * Status workflow validation ensures only valid transfer statuses are accepted.
 * Invalid statuses are rejected and returned as empty string.
 *
 * @param string $status The status value to validate
 * @return string Validated status or empty string if invalid
 *
 * @example
 * validateTransferStatus('Pending Verification') → 'Pending Verification'
 * validateTransferStatus('Invalid Status') → ''
 * validateTransferStatus('') → ''
 */
function validateTransferStatus(string $status): string {
    $allowedStatuses = [
        'Pending Verification',  // Initial state - PM verification needed
        'Pending Approval',      // Verified - Director approval needed
        'Approved',              // Approved - Ready for transit
        'In Transit',            // Currently being transported
        'Completed',             // Successfully received at destination
        'Canceled'               // Canceled at any stage
    ];

    return in_array($status, $allowedStatuses, true) ? $status : '';
}

/**
 * Validate transfer type parameter
 *
 * Transfer types determine asset assignment permanence:
 * - temporary: Asset will be returned after use
 * - permanent: Asset permanently reassigned to new project
 *
 * @param string $type The transfer type to validate
 * @return string Validated type or empty string if invalid
 *
 * @example
 * validateTransferTypeFilter('temporary') → 'temporary'
 * validateTransferTypeFilter('permanent') → 'permanent'
 * validateTransferTypeFilter('invalid') → ''
 */
function validateTransferTypeFilter(string $type): string {
    $allowedTypes = ['temporary', 'permanent'];
    return in_array($type, $allowedTypes, true) ? $type : '';
}

/**
 * Validate date parameter format (Y-m-d)
 *
 * Validates that date string matches YYYY-MM-DD format and represents a valid date.
 * Uses strict validation to prevent invalid dates like 2024-02-30.
 *
 * @param string $date The date value to validate
 * @return string Validated date or empty string if invalid
 *
 * @example
 * validateTransferDate('2024-01-15') → '2024-01-15'
 * validateTransferDate('2024-02-30') → '' (invalid date)
 * validateTransferDate('invalid') → ''
 * validateTransferDate('') → ''
 */
function validateTransferDate(string $date): string {
    if (empty($date)) {
        return '';
    }

    // Strict date validation using DateTime
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);

    // Verify format matches exactly and date is valid
    return ($dateObj && $dateObj->format('Y-m-d') === $date) ? $date : '';
}

/**
 * Render status options based on user role
 *
 * Status options are filtered based on user permissions:
 * - System Admin: All statuses visible
 * - Project Manager: Can see Pending Verification for their projects
 * - Asset Director/Finance Director: Can see Pending Approval
 * - Warehouseman: Can see Approved items ready for transit
 * - All users: Can see In Transit, Completed, Canceled
 *
 * @param Auth $auth Authentication instance for role checking
 * @param string $currentStatus Currently selected status from $_GET
 * @return string HTML options string for <select> dropdown
 *
 * @example
 * renderTransferStatusOptions($auth, 'Pending Verification')
 * // Returns: <option value="">All Statuses</option>
 * //          <option value="Pending Verification" selected>Pending Verification</option>
 * //          ...
 */
function renderTransferStatusOptions(Auth $auth, string $currentStatus = ''): string {
    $options = ['<option value="">All Statuses</option>'];

    // Status configuration with role-based visibility
    // Empty roles array [] means visible to all users
    $statusOptions = [
        [
            'value' => 'Pending Verification',
            'label' => 'Pending Verification',
            'roles' => ['System Admin', 'Project Manager']
        ],
        [
            'value' => 'Pending Approval',
            'label' => 'Pending Approval',
            'roles' => ['System Admin', 'Asset Director', 'Finance Director']
        ],
        [
            'value' => 'Approved',
            'label' => 'Approved',
            'roles' => ['System Admin', 'Warehouseman', 'Project Manager']
        ],
        [
            'value' => 'In Transit',
            'label' => 'In Transit',
            'roles' => [] // Visible to all users
        ],
        [
            'value' => 'Completed',
            'label' => 'Completed',
            'roles' => [] // Visible to all users
        ],
        [
            'value' => 'Canceled',
            'label' => 'Canceled',
            'roles' => [] // Visible to all users
        ],
    ];

    foreach ($statusOptions as $option) {
        // Show option if no role restriction OR user has required role
        if (empty($option['roles']) || $auth->hasRole($option['roles'])) {
            $selected = $currentStatus === $option['value'] ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8'),
                $selected,
                htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8')
            );
        }
    }

    return implode("\n", $options);
}

/**
 * Render transfer type options
 *
 * Type options determine asset assignment permanence:
 * - All Types: No filter applied
 * - Temporary: Assets that will be returned
 * - Permanent: Assets permanently reassigned
 *
 * @param string $currentType Currently selected type from $_GET
 * @return string HTML options string for <select> dropdown
 *
 * @example
 * renderTransferTypeOptions('temporary')
 * // Returns: <option value="">All Types</option>
 * //          <option value="temporary" selected>Temporary</option>
 * //          <option value="permanent">Permanent</option>
 */
function renderTransferTypeOptions(string $currentType = ''): string {
    $types = [
        '' => 'All Types',
        'temporary' => 'Temporary',
        'permanent' => 'Permanent'
    ];

    $options = [];
    foreach ($types as $value => $label) {
        $selected = $currentType === $value ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            $selected,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }

    return implode("\n", $options);
}

/**
 * Render project options for transfer filters
 *
 * Projects are rendered with ID and name for easy identification.
 * Projects must be active to appear in filter dropdowns.
 *
 * @param array $projects Array of project data from database
 * @param string|int $currentProject Currently selected project ID from $_GET
 * @return string HTML options string for <select> dropdown
 *
 * @example
 * renderTransferProjectOptions($projects, '5')
 * // Returns: <option value="">All Projects</option>
 * //          <option value="3">Project Alpha</option>
 * //          <option value="5" selected>Project Beta</option>
 */
function renderTransferProjectOptions(array $projects, $currentProject = ''): string {
    $options = ['<option value="">All Projects</option>'];

    foreach ($projects as $project) {
        // Use strict comparison with type casting to prevent type juggling
        $selected = (string)$currentProject === (string)$project['id'] ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8'),
            $selected,
            htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8')
        );
    }

    return implode("\n", $options);
}

/**
 * Render quick action filter buttons based on user role
 *
 * Quick filters provide one-click access to common filter combinations:
 * - My Verifications: Sets status=Pending Verification (Project Managers)
 * - My Approvals: Sets status=Pending Approval (Directors)
 * - In Transit: Sets status=In Transit (All users)
 *
 * Buttons use Alpine.js @click directive to call quickFilter() method.
 * Icons use Bootstrap Icons (bi-*) with aria-hidden="true".
 * aria-label provides accessible button description.
 *
 * @param Auth $auth Authentication instance for role checking
 * @return string HTML button elements string
 *
 * @example
 * renderTransferQuickActions($auth)
 * // For Project Manager:
 * // Returns: <button type="button" class="btn btn-outline-warning btn-sm"
 * //                  @click="quickFilter('Pending Verification')">
 * //              <i class="bi bi-clock me-1"></i>My Verifications
 * //          </button>
 * //          <button ... @click="quickFilter('In Transit')">In Transit</button>
 */
function renderTransferQuickActions(Auth $auth): string {
    $buttons = [];

    // My Verifications - Project Managers
    if ($auth->hasRole(['System Admin', 'Project Manager'])) {
        $buttons[] = '<button type="button" class="btn btn-outline-warning btn-sm" @click="quickFilter(\'Pending Verification\')" aria-label="Filter pending verifications"><i class="bi bi-clock me-1" aria-hidden="true"></i>My Verifications</button>';
    }

    // My Approvals - Directors
    if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])) {
        $buttons[] = '<button type="button" class="btn btn-outline-info btn-sm" @click="quickFilter(\'Pending Approval\')" aria-label="Filter pending approvals"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>My Approvals</button>';
    }

    // In Transit - All users
    $buttons[] = '<button type="button" class="btn btn-outline-primary btn-sm" @click="quickFilter(\'In Transit\')" aria-label="Filter in-transit transfers"><i class="bi bi-truck me-1" aria-hidden="true"></i>In Transit</button>';

    return implode("\n", $buttons);
}

// ============================================================================
// FILTER PARAMETER VALIDATION
// ============================================================================

// Load Auth instance for role-based visibility
$auth = Auth::getInstance();

// Validate and sanitize all $_GET parameters using helper functions
// Invalid values are rejected and returned as empty strings
$validatedFilters = [
    'status' => validateTransferStatus($_GET['status'] ?? ''),
    'transfer_type' => validateTransferTypeFilter($_GET['transfer_type'] ?? ''),
    'from_project' => filter_var($_GET['from_project'] ?? '', FILTER_VALIDATE_INT) ?: '',
    'to_project' => filter_var($_GET['to_project'] ?? '', FILTER_VALIDATE_INT) ?: '',
    'date_from' => validateTransferDate($_GET['date_from'] ?? ''),
    'date_to' => validateTransferDate($_GET['date_to'] ?? ''),
    'search' => InputValidator::sanitizeString($_GET['search'] ?? '')
];

// Calculate active filter count for mobile badge display
// Used to show number of active filters on mobile filter button
$activeFilters = 0;
foreach ($validatedFilters as $value) {
    if (!empty($value)) {
        $activeFilters++;
    }
}
?>

<!-- ============================================================================
     FILTERS SECTION
     ============================================================================

     Responsive filter implementation:
     - Mobile: Offcanvas panel (d-md-none, offcanvas-bottom)
     - Desktop: Card (d-none d-md-block)

     Both forms share same Alpine.js component for consistent behavior.
     ============================================================================ -->
<div class="mb-4">
    <!-- ========================================================================
         MOBILE FILTER BUTTON (STICKY)
         ========================================================================

         Sticky button at top of mobile viewport for easy filter access.
         Shows active filter count badge when filters are applied.
         ======================================================================== -->
    <div class="d-md-none position-sticky top-0 z-3 bg-body py-2 mb-3 filters-mobile-sticky">
        <button class="btn btn-primary w-100"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#filterOffcanvas"
                aria-label="Open filters panel"
                aria-expanded="false"
                aria-controls="filterOffcanvas">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i>
            Filters
            <?php if ($activeFilters > 0): ?>
                <span class="badge bg-warning text-dark ms-1" aria-label="<?= $activeFilters ?> active filters"><?= $activeFilters ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ========================================================================
         DESKTOP FILTERS (CARD)
         ========================================================================

         Always visible on desktop with inline filter controls.
         Uses Alpine.js component for auto-submit and validation.
         ======================================================================== -->
    <div class="card d-none d-md-block" x-data="transferFilters()">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form action="?route=transfers" method="GET" @submit.prevent="handleSubmit" role="search">
                <input type="hidden" name="route" value="transfers">

                <div class="row g-3">
                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm" @change="autoSubmit" aria-label="Filter by transfer status">
                            <?= renderTransferStatusOptions($auth, $validatedFilters['status']) ?>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div class="col-md-2">
                        <label for="transfer_type" class="form-label">Type</label>
                        <select name="transfer_type" id="transfer_type" class="form-select form-select-sm" @change="autoSubmit" aria-label="Filter by transfer type">
                            <?= renderTransferTypeOptions($validatedFilters['transfer_type']) ?>
                        </select>
                    </div>

                    <!-- From Project -->
                    <div class="col-md-2">
                        <label for="from_project" class="form-label">From Project</label>
                        <select name="from_project" id="from_project" class="form-select form-select-sm" @change="autoSubmit" aria-label="Filter by source project">
                            <?= renderTransferProjectOptions($projects, $validatedFilters['from_project']) ?>
                        </select>
                    </div>

                    <!-- To Project -->
                    <div class="col-md-2">
                        <label for="to_project" class="form-label">To Project</label>
                        <select name="to_project" id="to_project" class="form-select form-select-sm" @change="autoSubmit" aria-label="Filter by destination project">
                            <?= renderTransferProjectOptions($projects, $validatedFilters['to_project']) ?>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date"
                               name="date_from"
                               id="date_from"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($validatedFilters['date_from'], ENT_QUOTES, 'UTF-8') ?>"
                               @change="validateDateRange($event.target)"
                               x-ref="dateFrom"
                               aria-label="Filter by start date">
                    </div>

                    <!-- Date To -->
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date"
                               name="date_to"
                               id="date_to"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($validatedFilters['date_to'], ENT_QUOTES, 'UTF-8') ?>"
                               @change="validateDateRange($event.target)"
                               x-ref="dateTo"
                               aria-label="Filter by end date">
                    </div>

                    <!-- Search with Debouncing -->
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text"
                               name="search"
                               id="search"
                               class="form-control form-control-sm"
                               placeholder="Asset, reference, or reason..."
                               x-model="searchQuery"
                               @input.debounce.500ms="autoSubmit"
                               value="<?= htmlspecialchars($validatedFilters['search'], ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="Search transfers by asset, reference, or reason">
                    </div>

                    <!-- Action Buttons + Quick Filters -->
                    <div class="col-md-8 d-flex align-items-end gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm" aria-label="Apply filters">
                            <i class="bi bi-search me-1" aria-hidden="true"></i>Filter
                        </button>
                        <a href="?route=transfers" class="btn btn-outline-secondary btn-sm" aria-label="Clear all filters">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear
                        </a>

                        <!-- Quick Filter Buttons (Desktop Only - Large Screens) -->
                        <div class="vr mx-2 d-none d-lg-block" style="height: 38px;"></div>
                        <div class="d-none d-lg-flex gap-2">
                            <?= renderTransferQuickActions($auth) ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================
         MOBILE FILTERS (OFFCANVAS)
         ========================================================================

         Bottom sheet offcanvas panel for mobile filter experience.
         Shares same Alpine.js component as desktop for consistent behavior.
         ======================================================================== -->
    <div class="offcanvas offcanvas-bottom d-md-none"
         tabindex="-1"
         id="filterOffcanvas"
         aria-labelledby="filterOffcanvasLabel"
         style="height: 85vh;"
         x-data="transferFilters()">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filterOffcanvasLabel">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter Transfers
            </h5>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="offcanvas"
                    aria-label="Close filters panel"></button>
        </div>
        <div class="offcanvas-body">
            <form method="GET" action="?route=transfers" @submit.prevent="handleSubmit" role="search">
                <input type="hidden" name="route" value="transfers">

                <!-- Status Filter -->
                <div class="mb-3">
                    <label for="mobile_status" class="form-label">Status</label>
                    <select name="status" id="mobile_status" class="form-select" @change="autoSubmit" aria-label="Filter by transfer status">
                        <?= renderTransferStatusOptions($auth, $validatedFilters['status']) ?>
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="mb-3">
                    <label for="mobile_transfer_type" class="form-label">Type</label>
                    <select name="transfer_type" id="mobile_transfer_type" class="form-select" @change="autoSubmit" aria-label="Filter by transfer type">
                        <?= renderTransferTypeOptions($validatedFilters['transfer_type']) ?>
                    </select>
                </div>

                <!-- From Project -->
                <div class="mb-3">
                    <label for="mobile_from_project" class="form-label">From Project</label>
                    <select name="from_project" id="mobile_from_project" class="form-select" @change="autoSubmit" aria-label="Filter by source project">
                        <?= renderTransferProjectOptions($projects, $validatedFilters['from_project']) ?>
                    </select>
                </div>

                <!-- To Project -->
                <div class="mb-3">
                    <label for="mobile_to_project" class="form-label">To Project</label>
                    <select name="to_project" id="mobile_to_project" class="form-select" @change="autoSubmit" aria-label="Filter by destination project">
                        <?= renderTransferProjectOptions($projects, $validatedFilters['to_project']) ?>
                    </select>
                </div>

                <!-- Date From -->
                <div class="mb-3">
                    <label for="mobile_date_from" class="form-label">Date From</label>
                    <input type="date"
                           name="date_from"
                           id="mobile_date_from"
                           class="form-control"
                           value="<?= htmlspecialchars($validatedFilters['date_from'], ENT_QUOTES, 'UTF-8') ?>"
                           @change="validateDateRange($event.target)"
                           x-ref="mobileDateFrom"
                           aria-label="Filter by start date"
                           aria-describedby="mobile_date_from_help">
                    <small id="mobile_date_from_help" class="form-text text-muted">Start of date range</small>
                </div>

                <!-- Date To -->
                <div class="mb-3">
                    <label for="mobile_date_to" class="form-label">Date To</label>
                    <input type="date"
                           name="date_to"
                           id="mobile_date_to"
                           class="form-control"
                           value="<?= htmlspecialchars($validatedFilters['date_to'], ENT_QUOTES, 'UTF-8') ?>"
                           @change="validateDateRange($event.target)"
                           x-ref="mobileDateTo"
                           aria-label="Filter by end date"
                           aria-describedby="mobile_date_to_help">
                    <small id="mobile_date_to_help" class="form-text text-muted">End of date range</small>
                </div>

                <!-- Search with Debouncing -->
                <div class="mb-3">
                    <label for="mobile_search" class="form-label">Search</label>
                    <input type="text"
                           name="search"
                           id="mobile_search"
                           class="form-control"
                           placeholder="Asset, reference, or reason..."
                           x-model="searchQuery"
                           @input.debounce.500ms="autoSubmit"
                           value="<?= htmlspecialchars($validatedFilters['search'], ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Search transfers by asset, reference, or reason"
                           aria-describedby="mobile_search_help">
                    <small id="mobile_search_help" class="form-text text-muted">Search by asset, reference, or transfer reason</small>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" aria-label="Apply filters">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                    </button>
                    <a href="?route=transfers" class="btn btn-outline-secondary" aria-label="Clear all filters">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All
                    </a>
                </div>

                <!-- Quick Action Buttons (Mobile) -->
                <hr class="my-3">
                <div class="d-grid gap-2">
                    <?= renderTransferQuickActions($auth) ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Alpine.js Component Registration -->
<script>
/**
 * Alpine.js Transfer Filters Component
 * Must be loaded before Alpine.js initializes
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('transferFilters', () => ({
        /**
         * Component state
         */
        searchQuery: '',

        /**
         * Initialize component
         */
        init() {
            const searchInput = this.$el.querySelector('[name="search"]');
            if (searchInput) {
                this.searchQuery = searchInput.value;
            }
        },

        /**
         * Auto-submit form on filter change
         */
        autoSubmit() {
            const form = this.$el.querySelector('form');
            if (form) {
                form.submit();
            }
        },

        /**
         * Handle form submission
         */
        handleSubmit(event) {
            event.target.submit();
        },

        /**
         * Quick filter - applies status filter with one click
         */
        quickFilter(statusValue) {
            const statusField = this.$el.querySelector('[name="status"]');
            if (statusField) {
                statusField.value = statusValue;
                this.autoSubmit();
            }
        },

        /**
         * Validate date range
         */
        validateDateRange(changedInput) {
            const dateFromInput = this.$refs.dateFrom || this.$refs.mobileDateFrom;
            const dateToInput = this.$refs.dateTo || this.$refs.mobileDateTo;

            if (!dateFromInput || !dateToInput) {
                return;
            }

            const dateFrom = dateFromInput.value;
            const dateTo = dateToInput.value;

            this.clearDateError(dateFromInput);
            this.clearDateError(dateToInput);

            if (dateFrom && dateTo && dateFrom > dateTo) {
                if (changedInput === dateFromInput) {
                    this.showDateError(dateFromInput, 'Start date cannot be later than end date');
                    dateFromInput.value = '';
                } else {
                    this.showDateError(dateToInput, 'End date cannot be earlier than start date');
                    dateToInput.value = '';
                }
            } else {
                this.autoSubmit();
            }
        },

        /**
         * Show date error message
         */
        showDateError(input, message) {
            input.classList.add('is-invalid');

            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            errorDiv.setAttribute('role', 'alert');
            errorDiv.setAttribute('aria-live', 'polite');

            input.parentNode.appendChild(errorDiv);
        },

        /**
         * Clear date error message
         */
        clearDateError(input) {
            input.classList.remove('is-invalid');
            const errorDiv = input.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
    }));
});
</script>
