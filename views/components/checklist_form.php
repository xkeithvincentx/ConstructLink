<?php
/**
 * Reusable Checklist Form Component
 * Displays checkbox-based checklist for workflow actions
 *
 * Usage:
 * $checklistConfig = [
 *     'title' => 'Verification Checklist',
 *     'items' => [
 *         ['id' => 'check1', 'label' => 'Item is verified', 'required' => true, 'bold' => false],
 *         ['id' => 'check2', 'label' => 'Critical check', 'required' => true, 'bold' => true]
 *     ]
 * ];
 * include APP_ROOT . '/views/components/checklist_form.php';
 */

// Default configuration
$checklistConfig = $checklistConfig ?? [];
$title = $checklistConfig['title'] ?? 'Checklist';
$items = $checklistConfig['items'] ?? [];
$showTitle = $checklistConfig['showTitle'] ?? true;
?>

<?php if (!empty($items)): ?>
<div class="mb-3">
    <?php if ($showTitle): ?>
    <label class="form-label fw-bold"><?= htmlspecialchars($title) ?></label>
    <?php endif; ?>

    <?php foreach ($items as $item): ?>
    <div class="form-check">
        <input class="form-check-input"
               type="checkbox"
               id="<?= htmlspecialchars($item['id']) ?>"
               <?= !empty($item['required']) ? 'required' : '' ?>
               <?= !empty($item['name']) ? 'name="' . htmlspecialchars($item['name']) . '"' : '' ?>>
        <label class="form-check-label" for="<?= htmlspecialchars($item['id']) ?>">
            <?php if (!empty($item['bold'])): ?>
                <strong><?= htmlspecialchars($item['label']) ?></strong>
            <?php else: ?>
                <?= htmlspecialchars($item['label']) ?>
            <?php endif; ?>
        </label>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
