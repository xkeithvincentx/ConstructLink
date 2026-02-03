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

// Make CSRF token available globally for non-module scripts (enhanced-verification.js)
window.CSRFTokenValue = window.ConstructLinkConfig.csrfToken;
</script>

<!-- jQuery (required for Select2 on filters) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous"></script>

<!-- Select2 CSS for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
      rel="stylesheet"
      integrity="sha256-zaSoHBhwFdle0scfGEFUCwggPN7F+ip9XRglo8IWb4w="
      crossorigin="anonymous" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
      rel="stylesheet"
      integrity="sha256-RuT3RU9KidUWRvNmrrM+n4vPWdPFnKN0Ds2qVXjRCYM="
      crossorigin="anonymous" />

<?php
// Load external CSS module first
AssetHelper::loadModuleCSS('assets/assets');
?>

<!-- CRITICAL: Load custom Select2 CSS LAST to override everything -->
<link href="/assets/css/modules/assets/select2-custom.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"
        integrity="sha256-9yRP/2EFlblE92vzCA10469Ctd0jT48HnmmMw5rJZrA="
        crossorigin="anonymous"></script>

<!-- Filter Select2 Initialization -->
<script src="/assets/js/modules/assets/init/filter-select2.js"></script>

<?php

// Load external JavaScript modules
AssetHelper::loadModuleJS('assets/core-functions', ['type' => 'module']);
AssetHelper::loadModuleJS('assets/enhanced-search', ['type' => 'module']);
AssetHelper::loadModuleJS('assets/ui/pagination', ['type' => 'module']);
AssetHelper::loadModuleJS('assets/init', ['type' => 'module']);
?>

<?php
// Include enhanced verification and authorization modals
include APP_ROOT . '/views/assets/enhanced_verification_modal.php';
include APP_ROOT . '/views/assets/enhanced_authorization_modal.php';
?>

<!-- Enhanced Verification Script (External) -->
<script src="/assets/js/enhanced-verification.js"></script>
