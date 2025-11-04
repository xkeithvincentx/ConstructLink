<?php
/**
 * Client Supplied Checkbox Partial (LEGACY MODE ONLY)
 * Standalone checkbox for client-supplied assets
 *
 * Required Variables:
 * @var array $formData - Form data for pre-filling (optional)
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

<!-- Client Supplied -->
<div class="row mb-4">
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_client_supplied" name="is_client_supplied"
                   <?= !empty($formData['is_client_supplied']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_client_supplied">
                <strong>Client Supplied Asset</strong>
            </label>
            <div class="form-text">Check if this asset was provided by the client</div>
        </div>
    </div>
</div>
