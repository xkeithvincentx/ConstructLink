<?php
/**
 * JavaScript Partial - REFACTORED
 * Loads external JavaScript modules instead of inline scripts
 *
 * IMPROVEMENTS:
 * - ✅ NO INLINE JAVASCRIPT (external modules via AssetHelper)
 * - ✅ Proper ES6 module loading
 * - ✅ Configuration data transfer only (PHP → JavaScript)
 * - ✅ Browser caching enabled
 * - ✅ Cleaner separation of concerns
 */
?>

<!-- Configuration Data Transfer (PHP → JavaScript) -->
<!-- EXCEPTION: Configuration objects are allowed for data transfer -->
<script>
// Configuration object for JS modules
window.ConstructLinkConfig = {
    csrfToken: '<?= htmlspecialchars(CSRFProtection::generateToken() ?? "", ENT_QUOTES, 'UTF-8') ?>',
    apiEndpoint: '/api/assets',
    userId: <?= (int)($user['id'] ?? 0) ?>,
    userRole: '<?= htmlspecialchars($user['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
};

// Debug: Log config info
console.log('=== ConstructLink Assets Module ===');
console.log('Config loaded:', !!window.ConstructLinkConfig);
console.log('CSRF Token:', window.ConstructLinkConfig.csrfToken ? 'Present' : 'Missing');
console.log('User Role:', window.ConstructLinkConfig.userRole);
</script>

<?php
// Load external CSS module
AssetHelper::loadModuleCSS('assets/assets');

// Load external JavaScript modules
AssetHelper::loadModuleJS('assets/core-functions', ['type' => 'module']);
AssetHelper::loadModuleJS('assets/enhanced-search', ['type' => 'module']);
AssetHelper::loadModuleJS('assets/init', ['type' => 'module']);
?>

<?php
// Include enhanced verification and authorization modals
include APP_ROOT . '/views/assets/enhanced_verification_modal.php';
include APP_ROOT . '/views/assets/enhanced_authorization_modal.php';
?>

<!-- Enhanced Verification Script (External) -->
<script src="/assets/js/enhanced-verification.js"></script>
