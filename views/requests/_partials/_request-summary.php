<?php
/**
 * ConstructLink™ Request Summary Card Partial
 *
 * Displays a summary card with key request information.
 * Reusable component for approve, review, and view pages.
 *
 * @param array $request - The request data array (required)
 * @param bool $showDescription - Whether to show description (default: true)
 * @param bool $showRemarks - Whether to show remarks (default: true)
 * @param int $descriptionLimit - Character limit for description (default: 200, 0 = no limit)
 *
 * Usage:
 *   $request = [...]; // Request data array
 *   include APP_ROOT . '/views/requests/_partials/_request-summary.php';
 */

// Validate required parameter
if (!isset($request) || !is_array($request)) {
    throw new InvalidArgumentException('Request data array is required for request summary partial');
}

// Default optional parameters
$showDescription = $showDescription ?? true;
$showRemarks = $showRemarks ?? true;
$descriptionLimit = $descriptionLimit ?? 200;
?>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-info-circle me-2"></i>Request Summary
        </h6>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-5">Request ID:</dt>
            <dd class="col-sm-7">#<?= $request['id'] ?></dd>

            <dt class="col-sm-5">Type:</dt>
            <dd class="col-sm-7">
                <span class="badge bg-light text-dark">
                    <?= htmlspecialchars($request['request_type']) ?>
                </span>
            </dd>

            <?php if (isset($request['status'])): ?>
            <dt class="col-sm-5">Status:</dt>
            <dd class="col-sm-7">
                <?php
                $status = $request['status'];
                include APP_ROOT . '/views/requests/_partials/_badge-status.php';
                ?>
            </dd>
            <?php endif; ?>

            <dt class="col-sm-5">Urgency:</dt>
            <dd class="col-sm-7">
                <?php
                $urgency = $request['urgency'];
                include APP_ROOT . '/views/requests/_partials/_badge-urgency.php';
                ?>
            </dd>

            <dt class="col-sm-5">Project:</dt>
            <dd class="col-sm-7">
                <div class="fw-medium"><?= htmlspecialchars($request['project_name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($request['project_code']) ?></small>
            </dd>

            <dt class="col-sm-5">Requested By:</dt>
            <dd class="col-sm-7"><?= htmlspecialchars($request['requested_by_name']) ?></dd>

            <?php if (!empty($request['reviewed_by_name'])): ?>
            <dt class="col-sm-5">Reviewed By:</dt>
            <dd class="col-sm-7"><?= htmlspecialchars($request['reviewed_by_name']) ?></dd>
            <?php endif; ?>

            <?php if (!empty($request['approved_by_name'])): ?>
            <dt class="col-sm-5">Approved By:</dt>
            <dd class="col-sm-7"><?= htmlspecialchars($request['approved_by_name']) ?></dd>
            <?php endif; ?>

            <?php if (!empty($request['created_at'])): ?>
            <dt class="col-sm-5">Date Created:</dt>
            <dd class="col-sm-7"><?= date('M j, Y', strtotime($request['created_at'])) ?></dd>
            <?php endif; ?>

            <?php if (!empty($request['estimated_cost'])): ?>
            <dt class="col-sm-5">Est. Cost:</dt>
            <dd class="col-sm-7">₱<?= number_format($request['estimated_cost'], 2) ?></dd>
            <?php endif; ?>

            <?php if (!empty($request['date_needed'])): ?>
            <dt class="col-sm-5">Date Needed:</dt>
            <dd class="col-sm-7">
                <span class="<?= strtotime($request['date_needed']) < time() ? 'text-danger fw-bold' : '' ?>">
                    <?= date('M j, Y', strtotime($request['date_needed'])) ?>
                </span>
                <?php if (strtotime($request['date_needed']) < time()): ?>
                    <small class="text-danger">(Overdue)</small>
                <?php endif; ?>
            </dd>
            <?php endif; ?>
        </dl>

        <?php if ($showDescription && !empty($request['description'])): ?>
        <div class="mt-3">
            <h6>Description:</h6>
            <p class="text-muted small">
                <?php
                $description = $request['description'];
                if ($descriptionLimit > 0 && strlen($description) > $descriptionLimit) {
                    echo nl2br(htmlspecialchars(substr($description, 0, $descriptionLimit)));
                    echo '...';
                } else {
                    echo nl2br(htmlspecialchars($description));
                }
                ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($showRemarks && !empty($request['remarks'])): ?>
        <div class="mt-3">
            <h6>Remarks:</h6>
            <p class="text-muted small"><?= nl2br(htmlspecialchars($request['remarks'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
