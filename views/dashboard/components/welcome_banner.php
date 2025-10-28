<?php
/**
 * Compact Welcome Banner Component
 *
 * Displays a minimal, dismissible welcome message for authenticated users.
 * Part of the Neutral Design System V2.0
 *
 * @package ConstructLink
 * @subpackage Dashboard/Components
 * @version 2.0.0
 * @since 2025-10-28
 *
 * @param array $user User data array with keys:
 *                    - full_name (string): User's full name
 *                    - role_name (string): User's role
 *                    - department (string|null): User's department (optional)
 * @param bool $dismissible Whether the banner can be dismissed (default: true)
 * @param string $variant Style variant: 'default' or 'minimal' (default: 'default')
 *
 * @example
 * ```php
 * $user = Auth::getInstance()->getCurrentUser();
 * include APP_ROOT . '/views/dashboard/components/welcome_banner.php';
 * ```
 *
 * @example Minimal variant
 * ```php
 * $user = getCurrentUser();
 * $variant = 'minimal';
 * $dismissible = false;
 * include APP_ROOT . '/views/dashboard/components/welcome_banner.php';
 * ```
 */

// Validate required parameter
if (!isset($user) || !is_array($user)) {
    error_log('[Dashboard Component] welcome_banner.php: $user parameter is required');
    return;
}

// Default values
$dismissible = $dismissible ?? true;
$variant = $variant ?? 'default';

// Sanitize user data
$userName = htmlspecialchars($user['full_name'] ?? 'User');
$userRole = htmlspecialchars($user['role_name'] ?? '');
$userDepartment = isset($user['department']) ? htmlspecialchars($user['department']) : null;

// Generate unique ID for this banner instance
$bannerId = 'welcome-banner-' . uniqid();
?>

<!-- Compact Welcome Banner -->
<div class="welcome-banner"
     id="<?= $bannerId ?>"
     role="region"
     aria-label="Welcome message">
    <div class="welcome-content">
        <span class="user-name">Welcome back, <?= $userName ?></span>
        <?php if ($variant === 'default' && $userRole): ?>
            <span class="user-role"><?= $userRole ?></span>
        <?php endif; ?>
        <?php if ($variant === 'default' && $userDepartment): ?>
            <span class="user-role">• <?= $userDepartment ?></span>
        <?php endif; ?>
    </div>

    <?php if ($dismissible): ?>
        <button type="button"
                class="btn-dismiss"
                aria-label="Dismiss welcome message"
                onclick="dismissWelcomeBanner('<?= $bannerId ?>')">
            <i class="bi bi-x" aria-hidden="true"></i>
        </button>
    <?php endif; ?>
</div>

<?php if ($dismissible): ?>
<script>
/**
 * Dismiss welcome banner and store preference
 *
 * @param {string} bannerId The ID of the banner element to dismiss
 */
function dismissWelcomeBanner(bannerId) {
    const banner = document.getElementById(bannerId);
    if (!banner) return;

    // Add fade out animation
    banner.style.opacity = '0';
    banner.style.transition = 'opacity 200ms ease';

    // Remove from DOM after animation
    setTimeout(() => {
        banner.remove();
    }, 200);

    // Store dismissal preference in session storage
    // (persists for this session only - will show again on next login)
    sessionStorage.setItem('welcomeBannerDismissed', 'true');
}

// Auto-dismiss after 10 seconds (optional)
// Uncomment if you want auto-dismissal
/*
setTimeout(() => {
    const banner = document.getElementById('<?= $bannerId ?>');
    if (banner && !sessionStorage.getItem('welcomeBannerDismissed')) {
        dismissWelcomeBanner('<?= $bannerId ?>');
    }
}, 10000);
*/
</script>
<?php endif; ?>
