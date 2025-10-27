<?php
/**
 * Reusable Alert Message Component
 * Displays Bootstrap alert messages (errors, warnings, info, success)
 *
 * Usage:
 * $alertConfig = [
 *     'type' => 'danger', // danger, warning, info, success
 *     'icon' => 'exclamation-triangle', // Bootstrap icon name
 *     'title' => 'Error', // Optional title
 *     'message' => 'Something went wrong', // Single message string
 *     'messages' => ['Error 1', 'Error 2'], // Or array of messages
 *     'dismissible' => true
 * ];
 * include APP_ROOT . '/views/components/alert_message.php';
 */

// Default configuration
$alertConfig = $alertConfig ?? [];
$type = $alertConfig['type'] ?? 'info';
$icon = $alertConfig['icon'] ?? null;
$title = $alertConfig['title'] ?? null;
$message = $alertConfig['message'] ?? null;
$messages = $alertConfig['messages'] ?? [];
$dismissible = $alertConfig['dismissible'] ?? true;

// Default icons for each type
$defaultIcons = [
    'danger' => 'exclamation-triangle',
    'warning' => 'exclamation-triangle',
    'info' => 'info-circle',
    'success' => 'check-circle'
];

$icon = $icon ?? ($defaultIcons[$type] ?? 'info-circle');

// Convert single message to array
if ($message && empty($messages)) {
    $messages = [$message];
}

// Only render if there are messages
if (empty($messages)) {
    return;
}
?>

<div class="alert alert-<?= htmlspecialchars($type) ?> <?= $dismissible ? 'alert-dismissible fade show' : '' ?>" role="alert">
    <?php if ($icon): ?>
    <i class="bi bi-<?= htmlspecialchars($icon) ?> me-2" aria-hidden="true"></i>
    <?php endif; ?>

    <?php if ($title): ?>
    <strong><?= htmlspecialchars($title) ?></strong>
    <?php endif; ?>

    <?php if (count($messages) === 1): ?>
        <?= htmlspecialchars($messages[0]) ?>
    <?php else: ?>
        <ul class="mb-0 <?= $title ? 'mt-2' : '' ?>">
            <?php foreach ($messages as $msg): ?>
                <li><?= htmlspecialchars($msg) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($dismissible): ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <?php endif; ?>
</div>
