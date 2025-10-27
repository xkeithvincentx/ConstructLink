<?php
/**
 * View Helper - Reusable UI Component Renderers
 *
 * Provides centralized methods for rendering common UI components
 * to eliminate code duplication and improve maintainability.
 */

class ViewHelper
{
    /**
     * Render status badge with icon and accessibility support
     *
     * @param string $status Status text
     * @param bool $withIcon Include icon (default: true)
     * @param array $customConfig Override default status configuration
     * @return string HTML for status badge
     */
    public static function renderStatusBadge(
        string $status,
        bool $withIcon = true,
        array $customConfig = []
    ): string {
        $defaultConfig = [
            'Pending Verification' => ['class' => 'warning text-dark', 'icon' => 'clock'],
            'Pending Approval' => ['class' => 'info', 'icon' => 'hourglass-split'],
            'Approved' => ['class' => 'success', 'icon' => 'check-circle'],
            'Released' => ['class' => 'primary', 'icon' => 'box-arrow-right'],
            'Borrowed' => ['class' => 'secondary', 'icon' => 'box-arrow-up'],
            'Partially Returned' => ['class' => 'warning', 'icon' => 'arrow-repeat'],
            'Returned' => ['class' => 'success', 'icon' => 'check-square'],
            'Overdue' => ['class' => 'danger', 'icon' => 'exclamation-triangle'],
            'Canceled' => ['class' => 'dark', 'icon' => 'x-circle'],
            'Draft' => ['class' => 'secondary', 'icon' => 'file-earmark']
        ];

        $statusConfig = array_merge($defaultConfig, $customConfig);
        $config = $statusConfig[$status] ?? ['class' => 'secondary', 'icon' => 'question-circle'];

        $icon = $withIcon
            ? "<i class='bi bi-{$config['icon']}' aria-hidden='true'></i> "
            : '';

        return sprintf(
            '<span class="badge bg-%s" role="status">%s%s</span>',
            htmlspecialchars($config['class']),
            $icon,
            htmlspecialchars($status)
        );
    }

    /**
     * Render equipment condition badges (Out/In)
     *
     * @param string|null $conditionOut Condition when borrowed
     * @param string|null $conditionReturned Condition when returned
     * @param bool $inline Display inline (true) or stacked (false)
     * @return string HTML for condition badges
     */
    public static function renderConditionBadges(
        ?string $conditionOut,
        ?string $conditionReturned,
        bool $inline = true
    ): string {
        if (!$conditionOut && !$conditionReturned) {
            return '<span class="text-muted" aria-label="No condition data">—</span>';
        }

        $badges = [];

        if ($conditionOut) {
            $badges[] = self::renderSingleConditionBadge('Out', $conditionOut);
        }

        if ($conditionReturned) {
            $badges[] = self::renderSingleConditionBadge('In', $conditionReturned);
        }

        $separator = $inline ? ' ' : '<br>';
        return implode($separator, $badges);
    }

    /**
     * Render single condition badge
     *
     * @param string $label "Out" or "In"
     * @param string $condition Condition value
     * @return string HTML badge
     */
    private static function renderSingleConditionBadge(string $label, string $condition): string
    {
        $class = self::getConditionClass($condition);
        $icon = self::getConditionIcon($condition);

        return sprintf(
            '<span class="badge %s"><i class="bi bi-%s" aria-hidden="true"></i> %s: %s</span>',
            htmlspecialchars($class),
            htmlspecialchars($icon),
            htmlspecialchars($label),
            htmlspecialchars($condition)
        );
    }

    /**
     * Get Bootstrap class for condition
     */
    private static function getConditionClass(string $condition): string
    {
        return match(strtolower($condition)) {
            'good' => 'bg-success',
            'fair' => 'bg-warning text-dark',
            'poor', 'damaged' => 'bg-danger',
            'lost' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get Bootstrap icon for condition
     */
    private static function getConditionIcon(string $condition): string
    {
        return match(strtolower($condition)) {
            'good' => 'check-circle-fill',
            'fair' => 'exclamation-circle-fill',
            'poor', 'damaged' => 'x-circle-fill',
            'lost' => 'question-circle-fill',
            default => 'circle'
        };
    }

    /**
     * Render action button with icon and accessibility
     *
     * @param string $icon Bootstrap icon name
     * @param string $label Aria label and title
     * @param string $url Button URL or action
     * @param string $variant Bootstrap button variant (primary, success, etc)
     * @param array $attributes Additional HTML attributes
     * @return string HTML button
     */
    public static function renderActionButton(
        string $icon,
        string $label,
        string $url = '#',
        string $variant = 'outline-primary',
        array $attributes = []
    ): string {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($value));
        }

        $href = $url !== '#' ? sprintf('href="%s"', htmlspecialchars($url)) : '';

        return sprintf(
            '<a %s class="btn btn-sm btn-%s" aria-label="%s" title="%s"%s><i class="bi bi-%s" aria-hidden="true"></i></a>',
            $href,
            htmlspecialchars($variant),
            htmlspecialchars($label),
            htmlspecialchars($label),
            $attrs,
            htmlspecialchars($icon)
        );
    }

    /**
     * Render critical tool badge based on acquisition cost
     *
     * @param float $cost Acquisition cost
     * @param float|null $threshold Custom threshold (defaults to config)
     * @return string HTML badge or empty string
     */
    public static function renderCriticalToolBadge(float $cost, ?float $threshold = null): string
    {
        if ($threshold === null) {
            $threshold = 50000; // Default threshold
            // Try to get from config if available
            if (function_exists('config')) {
                $threshold = config('business_rules.critical_tool_threshold', 50000);
            }
        }

        if ($cost > $threshold) {
            return '<span class="badge bg-warning text-dark"><i class="bi bi-shield-check" aria-hidden="true"></i> Critical Item</span>';
        }

        return '';
    }

    /**
     * Format date with optional time
     *
     * @param string|null $date Date string
     * @param bool $includeTime Include time (default: false)
     * @return string Formatted date
     */
    public static function formatDate(?string $date, bool $includeTime = false): string
    {
        if (!$date) {
            return '<span class="text-muted">—</span>';
        }

        $format = $includeTime ? 'M d, Y H:i' : 'M d, Y';
        return date($format, strtotime($date));
    }

    /**
     * Render overdue badge with days count
     *
     * @param string $expectedReturn Expected return date
     * @param string $currentDate Current date (default: now)
     * @return string HTML badge or empty string
     */
    public static function renderOverdueBadge(string $expectedReturn, string $currentDate = 'now'): string
    {
        $expectedTime = strtotime($expectedReturn);
        $currentTime = strtotime($currentDate);

        if ($currentTime > $expectedTime) {
            $daysOverdue = abs(floor(($currentTime - $expectedTime) / 86400));
            return sprintf(
                '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> %d %s overdue</span>',
                $daysOverdue,
                $daysOverdue === 1 ? 'day' : 'days'
            );
        }

        return '';
    }

    /**
     * Render due soon badge
     *
     * @param string $expectedReturn Expected return date
     * @param int $daysThreshold Days before due date to show warning (default: 3)
     * @return string HTML badge or empty string
     */
    public static function renderDueSoonBadge(string $expectedReturn, int $daysThreshold = 3): string
    {
        $expectedTime = strtotime($expectedReturn);
        $currentTime = time();

        if ($currentTime < $expectedTime && $expectedTime <= strtotime("+{$daysThreshold} days")) {
            $daysUntilDue = ceil(($expectedTime - $currentTime) / 86400);
            return sprintf(
                '<span class="badge bg-warning text-dark"><i class="bi bi-clock" aria-hidden="true"></i> Due in %d %s</span>',
                $daysUntilDue,
                $daysUntilDue === 1 ? 'day' : 'days'
            );
        }

        return '';
    }

    /**
     * Render quantity badge with color based on value
     *
     * @param int $quantity Quantity value
     * @param string $label Label for quantity
     * @param string $color Badge color (default: auto-determined)
     * @return string HTML badge
     */
    public static function renderQuantityBadge(int $quantity, string $label = '', string $color = 'auto'): string
    {
        if ($color === 'auto') {
            $color = $quantity > 0 ? 'primary' : 'secondary';
        }

        $labelText = $label ? htmlspecialchars($label) . ': ' : '';

        return sprintf(
            '<span class="badge bg-%s">%s%d</span>',
            htmlspecialchars($color),
            $labelText,
            $quantity
        );
    }

    /**
     * Render MVA workflow badge
     *
     * @param string $role M (Maker), V (Verifier), or A (Authorizer)
     * @param string $userName User name
     * @param int $maxWidth Maximum width for truncation (default: 80px)
     * @return string HTML badge with user name
     */
    public static function renderMVABadge(string $role, string $userName, int $maxWidth = 80): string
    {
        $badgeColors = [
            'M' => 'bg-light text-dark',
            'V' => 'bg-warning text-dark',
            'A' => 'bg-success text-white'
        ];

        $color = $badgeColors[strtoupper($role)] ?? 'bg-secondary';

        return sprintf(
            '<div class="d-flex align-items-center mb-1"><span class="badge badge-sm %s me-1">%s</span><span class="text-truncate" style="max-width: %dpx;">%s</span></div>',
            htmlspecialchars($color),
            htmlspecialchars(strtoupper($role)),
            $maxWidth,
            htmlspecialchars($userName)
        );
    }

    /**
     * Check if tool is critical based on acquisition cost
     *
     * @param float $cost Acquisition cost
     * @param float|null $threshold Custom threshold (defaults to config)
     * @return bool True if cost >= threshold
     */
    public static function isCriticalTool(float $cost, ?float $threshold = null): bool
    {
        if ($threshold === null) {
            $threshold = 50000; // Default threshold
            // Try to get from config if available
            if (function_exists('config')) {
                $threshold = config('business_rules.critical_tool_threshold', 50000);
            }
        }

        return $cost >= $threshold;
    }

    /**
     * Render critical tool warning alert
     *
     * @return string HTML alert for critical tools
     */
    public static function renderCriticalToolWarning(): string
    {
        return '<div class="alert alert-warning mb-3" role="alert">' .
               '<i class="bi bi-shield-exclamation" aria-hidden="true"></i> ' .
               '<strong>Critical Tool - Requires Verification &amp; Approval</strong>' .
               '</div>';
    }

    /**
     * Render tool details table for MVA workflows
     *
     * @param array $tool Tool data with all required fields
     * @param bool $showActions Show actions column (default: false)
     * @return string HTML table with tool details
     */
    public static function renderToolDetailsTable(array $tool, bool $showActions = false): string
    {
        $html = '<div class="row mb-4">';

        // Tool Information Column
        $html .= '<div class="col-md-6 mb-3 mb-md-0">';
        $html .= '<h6 class="fw-bold">Tool Information</h6>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-sm">';

        $html .= '<tr>';
        $html .= '<td><strong>Asset Reference:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['asset_ref']) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><strong>Asset Name:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['asset_name']) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><strong>Category:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['category_name']) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><strong>Project:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['project_name']) . '</td>';
        $html .= '</tr>';

        if (isset($tool['acquisition_cost']) && $tool['acquisition_cost']) {
            $html .= '<tr>';
            $html .= '<td><strong>Asset Value:</strong></td>';
            $html .= '<td>₱' . number_format($tool['acquisition_cost'], 2);

            // Add critical tool badge if applicable
            if (self::isCriticalTool($tool['acquisition_cost'])) {
                $html .= ' ' . self::renderCriticalToolBadge($tool['acquisition_cost']);
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '</div>'; // table-responsive
        $html .= '</div>'; // col-md-6

        // Borrowing Details Column
        $html .= '<div class="col-md-6">';
        $html .= '<h6 class="fw-bold">Borrowing Details</h6>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-sm">';

        $html .= '<tr>';
        $html .= '<td><strong>Borrower:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['borrower_name']) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><strong>Contact:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['borrower_contact']) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><strong>Expected Return:</strong></td>';
        $html .= '<td>' . date('M d, Y', strtotime($tool['expected_return'])) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><strong>Purpose:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['purpose']) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><strong>Issued By:</strong></td>';
        $html .= '<td>' . htmlspecialchars($tool['issued_by_name']) . '</td>';
        $html .= '</tr>';

        // Add workflow-specific rows based on what data is available
        if (isset($tool['created_at'])) {
            $html .= '<tr>';
            $html .= '<td><strong>Request Date:</strong></td>';
            $html .= '<td>' . date('M d, Y g:i A', strtotime($tool['created_at'])) . '</td>';
            $html .= '</tr>';
        }

        if (isset($tool['verified_by_name']) && $tool['verified_by_name']) {
            $html .= '<tr>';
            $html .= '<td><strong>Verified By:</strong></td>';
            $html .= '<td>' . htmlspecialchars($tool['verified_by_name']) . '</td>';
            $html .= '</tr>';

            if (isset($tool['verification_date'])) {
                $html .= '<tr>';
                $html .= '<td><strong>Verification Date:</strong></td>';
                $html .= '<td>' . date('M d, Y g:i A', strtotime($tool['verification_date'])) . '</td>';
                $html .= '</tr>';
            }
        }

        if (isset($tool['approved_by_name']) && $tool['approved_by_name']) {
            $html .= '<tr>';
            $html .= '<td><strong>Approved By:</strong></td>';
            $html .= '<td>' . htmlspecialchars($tool['approved_by_name']) . '</td>';
            $html .= '</tr>';

            if (isset($tool['approval_date'])) {
                $html .= '<tr>';
                $html .= '<td><strong>Approval Date:</strong></td>';
                $html .= '<td>' . date('M d, Y g:i A', strtotime($tool['approval_date'])) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';
        $html .= '</div>'; // table-responsive
        $html .= '</div>'; // col-md-6

        $html .= '</div>'; // row

        return $html;
    }
}
