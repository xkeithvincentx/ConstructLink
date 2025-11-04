<?php
/**
 * ConstructLink™ Review Guidelines Card Partial
 *
 * Displays review/approval guidelines for request reviewers and approvers.
 * Reusable component with configurable context.
 *
 * @param string $context - Context: 'review' or 'approve' (default: 'review')
 *
 * Usage:
 *   $context = 'approve'; // or 'review'
 *   include APP_ROOT . '/views/requests/_partials/_review-guidelines.php';
 */

// Default to review context if not set
$context = $context ?? 'review';
?>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-lightbulb me-2"></i><?= $context === 'approve' ? 'Approval' : 'Review' ?> Guidelines
        </h6>
    </div>
    <div class="card-body">
        <?php if ($context === 'approve'): ?>
            <h6>Approve when:</h6>
            <ul class="small">
                <li>Budget is available and justified</li>
                <li>Request aligns with project objectives</li>
                <li>Proper authorization exists</li>
                <li>Timeline is reasonable</li>
            </ul>

            <h6>Decline when:</h6>
            <ul class="small">
                <li>Budget constraints exist</li>
                <li>Request violates policies</li>
                <li>Insufficient justification</li>
                <li>Better alternatives available</li>
            </ul>

            <h6>Request more info when:</h6>
            <ul class="small">
                <li>Details are unclear or missing</li>
                <li>Additional documentation needed</li>
                <li>Clarification required</li>
            </ul>
        <?php else: ?>
            <h6>Forward to Finance Director when:</h6>
            <ul class="small">
                <li>Budget approval is required</li>
                <li>Cost exceeds department limits</li>
                <li>Financial impact assessment needed</li>
            </ul>

            <h6>Forward to Procurement when:</h6>
            <ul class="small">
                <li>Vendor selection is required</li>
                <li>Market research needed</li>
                <li>Procurement process must be followed</li>
            </ul>

            <h6>Decline when:</h6>
            <ul class="small">
                <li>Request doesn't align with project goals</li>
                <li>Budget constraints exist</li>
                <li>Alternative solutions available</li>
                <li>Insufficient justification provided</li>
            </ul>
        <?php endif; ?>
    </div>
</div>
