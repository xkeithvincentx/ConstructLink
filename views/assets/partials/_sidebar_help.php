<?php
/**
 * Sidebar Help Partial
 * Help cards and information panels (mode-dependent content)
 *
 * Required Variables:
 * @var string $mode - Form mode: 'legacy' or 'standard'
 * @var array $branding - Branding configuration
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

// Mode-specific configurations
$helpTitle = $mode === 'legacy' ? 'Legacy Item Entry Help' : 'Asset Creation Help';
$helpDescription = $mode === 'legacy'
    ? 'Quick guide for adding existing inventory items'
    : 'Follow these steps to create a new asset record';
?>

<!-- Help & Information -->
<div class="card">
    <div class="card-header bg-info text-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-question-circle me-2" aria-hidden="true"></i><?= htmlspecialchars($helpTitle) ?>
        </h6>
    </div>
    <div class="card-body">
        <p class="small text-muted"><?= htmlspecialchars($helpDescription) ?></p>

        <?php if ($mode === 'legacy'): ?>
        <!-- Legacy Mode Help -->
        <div class="mb-3">
            <h6 class="text-primary">Quick Entry Process:</h6>
            <ol class="small">
                <li>Search for equipment type</li>
                <li>Category auto-selects</li>
                <li>Name auto-generates</li>
                <li>Add optional details</li>
                <li>Submit for verification</li>
            </ol>
        </div>

        <div class="alert alert-warning py-2">
            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
            <small><strong>Note:</strong> Legacy items require verification by Site Inventory Clerk before authorization.</small>
        </div>

        <?php else: ?>
        <!-- Standard Mode Help -->
        <div class="mb-3">
            <h6 class="text-primary">Creation Steps:</h6>
            <ol class="small">
                <li>Select category and project</li>
                <li>Choose equipment type</li>
                <li>Enter brand and model</li>
                <li>Add procurement details</li>
                <li>Specify technical specs</li>
                <li>Complete financial info</li>
            </ol>
        </div>

        <div class="alert alert-info py-2">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            <small><strong>Tip:</strong> Link to procurement orders to auto-fill vendor and cost information.</small>
        </div>
        <?php endif; ?>

        <hr>

        <div class="small text-muted">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
            <strong>Required Fields:</strong> Fields marked with <span class="text-danger">*</span> are required.
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-gear me-2" aria-hidden="true"></i>System Prefix
        </h6>
    </div>
    <div class="card-body">
        <p class="small mb-2">Auto-generated references use:</p>
        <div class="alert alert-light mb-0 py-2">
            <code><?= htmlspecialchars($branding['asset_ref_prefix'] ?? 'ASSET') ?>-XXXXX</code>
        </div>
    </div>
</div>
