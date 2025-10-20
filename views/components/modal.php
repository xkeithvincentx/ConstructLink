<?php
/**
 * Reusable Modal Component
 * Developed by: Ranoa Digital Solutions
 *
 * Usage Example:
 * <?php
 * ob_start();
 * ?>
 * <p>Modal body content here</p>
 * <?php
 * $modalBody = ob_get_clean();
 *
 * ob_start();
 * ?>
 * <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
 * <button type="submit" class="btn btn-primary">Submit</button>
 * <?php
 * $modalActions = ob_get_clean();
 *
 * // Set variables
 * $id = 'myModal';
 * $title = 'My Modal';
 * $icon = 'check-circle';
 * $headerClass = 'bg-success text-white';
 * $body = $modalBody;
 * $actions = $modalActions;
 * $size = 'lg';
 *
 * // Include modal component
 * include APP_ROOT . '/views/components/modal.php';
 * ?>
 */

// Required parameters
$id = $id ?? 'modal_' . uniqid();
$title = $title ?? 'Modal';
$body = $body ?? '';

// Optional parameters
$icon = $icon ?? null;
$headerClass = $headerClass ?? 'bg-light';
$size = $size ?? ''; // '', 'sm', 'lg', 'xl'
$actions = $actions ?? null;
$dismissible = $dismissible ?? true;
$formAction = $formAction ?? null; // If set, wraps content in form
$formMethod = $formMethod ?? 'POST';

// Computed values
$sizeClass = $size ? "modal-{$size}" : '';
$closeButtonClass = str_contains($headerClass, 'text-white') ? 'btn-close-white' : '';
?>

<div class="modal fade"
     id="<?= htmlspecialchars($id) ?>"
     tabindex="-1"
     aria-labelledby="<?= htmlspecialchars($id) ?>Label"
     aria-hidden="true"
     data-bs-backdrop="<?= $dismissible ? 'true' : 'static' ?>"
     data-bs-keyboard="<?= $dismissible ? 'true' : 'false' ?>">
    <div class="modal-dialog <?= htmlspecialchars($sizeClass) ?>">
        <div class="modal-content">
            <?php if ($formAction): ?>
            <form method="<?= htmlspecialchars($formMethod) ?>" action="<?= htmlspecialchars($formAction) ?>">
            <?php endif; ?>

                <div class="modal-header <?= htmlspecialchars($headerClass) ?>">
                    <h5 class="modal-title" id="<?= htmlspecialchars($id) ?>Label">
                        <?php if ($icon): ?>
                            <i class="bi bi-<?= htmlspecialchars($icon) ?> me-2" aria-hidden="true"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($title) ?>
                    </h5>
                    <?php if ($dismissible): ?>
                        <button type="button"
                                class="btn-close <?= $closeButtonClass ?>"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    <?php endif; ?>
                </div>

                <div class="modal-body">
                    <?= $body ?>
                </div>

                <?php if ($actions): ?>
                    <div class="modal-footer">
                        <?= $actions ?>
                    </div>
                <?php endif; ?>

            <?php if ($formAction): ?>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
