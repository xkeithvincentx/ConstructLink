<?php
/**
 * Card Container Component - Neutral Design System V2.0
 *
 * Reusable card wrapper with consistent header, body, and optional footer.
 * Eliminates repeated card structure patterns across dashboards.
 * Follows WCAG 2.1 AA accessibility standards with proper heading hierarchy.
 *
 * @param string $title Optional - Card header title
 * @param string $icon Optional - Bootstrap icon class for header (e.g., 'bi-box-seam')
 * @param string $content Required - Card body content (use output buffering to capture HTML)
 * @param string $footer Optional - Card footer content
 * @param string $cardClass Optional - Additional card classes (default: 'card-neutral')
 * @param string $headerClass Optional - Additional header classes (default: '')
 * @param string $bodyClass Optional - Additional body classes (default: '')
 * @param string $uniqueId Optional - Custom ID for ARIA labeling (auto-generated if not provided)
 * @param string $headingLevel Optional - Heading level (h1-h6) (default: 'h5')
 *
 * @example Basic card with title and icon
 * ```php
 * <?php ob_start(); ?>
 * <p>This is the card body content.</p>
 * <ul>
 *     <li>Item 1</li>
 *     <li>Item 2</li>
 * </ul>
 * <?php
 * $content = ob_get_clean();
 * $title = 'System Status';
 * $icon = 'bi-shield-check';
 * include APP_ROOT . '/views/dashboard/components/card_container.php';
 * ?>
 * ```
 *
 * @example Card without header
 * ```php
 * <?php ob_start(); ?>
 * <div class="text-center">
 *     <h6>Custom Content</h6>
 *     <p>This card has no standard header.</p>
 * </div>
 * <?php
 * $content = ob_get_clean();
 * include APP_ROOT . '/views/dashboard/components/card_container.php';
 * ?>
 * ```
 *
 * @example Card with footer
 * ```php
 * <?php ob_start(); ?>
 * <p>Card body content here.</p>
 * <?php
 * $content = ob_get_clean();
 * $title = 'Pending Actions';
 * $icon = 'bi-list-check';
 * $footer = '<a href="?route=actions" class="btn btn-sm btn-outline-secondary">View All</a>';
 * include APP_ROOT . '/views/dashboard/components/card_container.php';
 * ?>
 * ```
 *
 * @example Full-height card for grid layouts
 * ```php
 * <?php ob_start(); ?>
 * <p>This card will stretch to match column height.</p>
 * <?php
 * $content = ob_get_clean();
 * $title = 'Equipment Summary';
 * $icon = 'bi-boxes';
 * $cardClass = 'card-neutral h-100';
 * include APP_ROOT . '/views/dashboard/components/card_container.php';
 * ?>
 * ```
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.1 - Neutral Design
 * @since 2025-11-02
 */

// Validate required parameter
if (!isset($content)) {
    error_log('[Dashboard Component] card_container.php: $content parameter is required');
    return;
}

// Set defaults and sanitize
$title = $title ?? null;
$icon = $icon ?? null;
$footer = $footer ?? null;
$cardClass = $cardClass ?? 'card-neutral';
$headerClass = $headerClass ?? '';
$bodyClass = $bodyClass ?? '';
$uniqueId = $uniqueId ?? 'card-' . uniqid();
$headingLevel = $headingLevel ?? 'h5';

// Validate heading level
$validHeadings = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
if (!in_array($headingLevel, $validHeadings)) {
    error_log('[Dashboard Component] card_container.php: Invalid heading level "' . $headingLevel . '", defaulting to "h5"');
    $headingLevel = 'h5';
}
?>

<div class="card <?= htmlspecialchars($cardClass) ?>" id="<?= htmlspecialchars($uniqueId) ?>">

    <?php if ($title): ?>
    <div class="card-header <?= htmlspecialchars($headerClass) ?>">
        <<?= $headingLevel ?> class="mb-0" id="<?= htmlspecialchars($uniqueId) ?>-title">
            <?php if ($icon): ?>
                <i class="<?= htmlspecialchars($icon) ?> me-2" aria-hidden="true"></i>
            <?php endif; ?>
            <?= htmlspecialchars($title) ?>
        </<?= $headingLevel ?>>
    </div>
    <?php endif; ?>

    <div class="card-body <?= htmlspecialchars($bodyClass) ?>"
         <?php if ($title): ?>aria-labelledby="<?= htmlspecialchars($uniqueId) ?>-title"<?php endif; ?>>
        <?= $content ?> <!-- Allow HTML (caller is responsible for sanitization) -->
    </div>

    <?php if ($footer): ?>
    <div class="card-footer">
        <?= $footer ?> <!-- Allow HTML (caller is responsible for sanitization) -->
    </div>
    <?php endif; ?>

</div>
