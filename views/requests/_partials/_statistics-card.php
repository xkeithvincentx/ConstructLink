<?php
/**
 * ConstructLink™ Statistics Card Component
 *
 * Reusable statistics card component for dashboards and index pages.
 * Eliminates duplicated card HTML across views.
 *
 * @param string $title - Card title (required)
 * @param int|string $value - Main statistic value (required)
 * @param string $icon - Bootstrap icon class without 'bi-' prefix (required)
 * @param string $color - Color scheme: info, warning, success, danger, primary, secondary (required)
 * @param string $description - Description text below the value (optional)
 * @param string $actionUrl - URL for action button (optional)
 * @param string $actionLabel - Label for action button (optional)
 * @param int $actionBadge - Badge number for action button (optional)
 *
 * Usage:
 *   $title = 'Pending Requests';
 *   $value = 42;
 *   $icon = 'clock-history';
 *   $color = 'info';
 *   $description = 'Awaiting review';
 *   include APP_ROOT . '/views/requests/_partials/_statistics-card.php';
 */

// Validate required parameters
if (!isset($title) || !isset($value) || !isset($icon) || !isset($color)) {
    throw new InvalidArgumentException('Title, value, icon, and color are required for statistics card partial');
}

// Default optional parameters
$description = $description ?? '';
$actionUrl = $actionUrl ?? '';
$actionLabel = $actionLabel ?? '';
$actionBadge = $actionBadge ?? 0;

// Map color to CSS class
$colorClass = htmlspecialchars($color);
$iconColor = $colorClass;
?>

<div class="col-lg-3 col-md-6">
    <div class="card h-100 stat-card stat-<?= $colorClass ?>">
        <div class="card-body">
            <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle bg-light p-2 me-3" aria-hidden="true">
                    <i class="bi bi-<?= htmlspecialchars($icon) ?> text-<?= $iconColor ?> fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1 small"><?= htmlspecialchars($title) ?></h6>
                    <h3 class="mb-0" aria-label="<?= htmlspecialchars($title) ?>: <?= htmlspecialchars($value) ?>">
                        <?= htmlspecialchars($value) ?>
                    </h3>
                </div>
            </div>

            <?php if ($description): ?>
            <p class="text-muted mb-0 small">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i><?= htmlspecialchars($description) ?>
            </p>
            <?php endif; ?>

            <?php if ($actionUrl && $actionLabel): ?>
            <a href="<?= htmlspecialchars($actionUrl) ?>"
               class="btn btn-sm btn-outline-<?= $iconColor ?> w-100 mt-2"
               aria-label="<?= htmlspecialchars($actionLabel) ?>">
                <i class="bi bi-eye me-1" aria-hidden="true"></i><?= htmlspecialchars($actionLabel) ?>
                <?php if ($actionBadge > 0): ?>
                    <span class="badge bg-<?= $iconColor ?> ms-1"><?= $actionBadge ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
