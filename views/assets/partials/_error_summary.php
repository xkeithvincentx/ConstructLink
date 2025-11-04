<?php
/**
 * Error Summary Partial
 * Displays validation errors with jump links for accessibility
 *
 * Required Variables:
 * None - This is a placeholder that gets populated by JavaScript on validation errors
 *
 * @package ConstructLink
 * @subpackage Views\Assets\Partials
 * @version 1.0.0
 * @since Phase 2 Refactoring
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
?>

<!-- Error Summary (Hidden by default, shown on validation errors) -->
<div id="error-summary" class="alert alert-danger d-none mb-4" role="alert" aria-live="assertive" aria-atomic="true">
    <h5 class="alert-heading">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Please fix the following errors:
    </h5>
    <ul id="error-list" class="mb-0"></ul>
</div>
