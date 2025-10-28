<?php
/**
 * Icon Mapper Utility
 *
 * Centralized icon registry for consistent icon usage across the application.
 * Maps actions, statuses, and entities to Bootstrap Icons.
 *
 * @package ConstructLink
 * @subpackage Constants
 * @version 2.0
 * @since 2025-10-28
 */

class IconMapper
{
    /* ==========================================================================
       Action Icons
       ========================================================================== */

    const ACTION_CREATE = 'bi-plus-circle';
    const ACTION_EDIT = 'bi-pencil-square';
    const ACTION_DELETE = 'bi-trash';
    const ACTION_VIEW = 'bi-eye';
    const ACTION_SEARCH = 'bi-search';
    const ACTION_FILTER = 'bi-funnel';
    const ACTION_DOWNLOAD = 'bi-download';
    const ACTION_UPLOAD = 'bi-upload';
    const ACTION_PRINT = 'bi-printer';
    const ACTION_EXPORT = 'bi-file-earmark-arrow-down';
    const ACTION_IMPORT = 'bi-file-earmark-arrow-up';
    const ACTION_REFRESH = 'bi-arrow-clockwise';
    const ACTION_SAVE = 'bi-floppy';
    const ACTION_CANCEL = 'bi-x-circle';
    const ACTION_APPROVE = 'bi-check-circle';
    const ACTION_REJECT = 'bi-x-octagon';
    const ACTION_SUBMIT = 'bi-send';
    const ACTION_VERIFY = 'bi-shield-check';

    /* ==========================================================================
       Module/Entity Icons
       ========================================================================== */

    const MODULE_DASHBOARD = 'bi-speedometer2';
    const MODULE_ASSETS = 'bi-box';
    const MODULE_REQUESTS = 'bi-file-earmark-text';
    const MODULE_PROCUREMENT = 'bi-cart-check';
    const MODULE_TRANSFERS = 'bi-arrow-left-right';
    const MODULE_WITHDRAWALS = 'bi-box-arrow-right';
    const MODULE_BORROWED_TOOLS = 'bi-tools';
    const MODULE_INCIDENTS = 'bi-exclamation-triangle';
    const MODULE_MAINTENANCE = 'bi-wrench';
    const MODULE_PROJECTS = 'bi-building';
    const MODULE_USERS = 'bi-people';
    const MODULE_REPORTS = 'bi-file-earmark-bar-graph';
    const MODULE_SETTINGS = 'bi-gear';
    const MODULE_NOTIFICATIONS = 'bi-bell';
    const MODULE_MESSAGES = 'bi-chat-dots';

    /* ==========================================================================
       Status Icons
       ========================================================================== */

    const STATUS_SUCCESS = 'bi-check-circle-fill';
    const STATUS_WARNING = 'bi-exclamation-triangle-fill';
    const STATUS_ERROR = 'bi-x-circle-fill';
    const STATUS_INFO = 'bi-info-circle-fill';
    const STATUS_PENDING = 'bi-clock';
    const STATUS_IN_PROGRESS = 'bi-hourglass-split';
    const STATUS_COMPLETED = 'bi-check-circle';
    const STATUS_CANCELLED = 'bi-x-circle';
    const STATUS_REJECTED = 'bi-x-octagon';

    /* ==========================================================================
       Asset Status Icons
       ========================================================================== */

    const ASSET_AVAILABLE = 'bi-check-circle';
    const ASSET_IN_USE = 'bi-box-seam';
    const ASSET_MAINTENANCE = 'bi-tools';
    const ASSET_RETIRED = 'bi-archive';
    const ASSET_DISPOSED = 'bi-trash';
    const ASSET_LOST = 'bi-geo-alt';
    const ASSET_DAMAGED = 'bi-hammer';

    /* ==========================================================================
       Financial Icons
       ========================================================================== */

    const FINANCE_CASH = 'bi-cash-stack';
    const FINANCE_BUDGET = 'bi-wallet2';
    const FINANCE_HIGH_VALUE = 'bi-currency-dollar';
    const FINANCE_INVOICE = 'bi-receipt';
    const FINANCE_PAYMENT = 'bi-credit-card';

    /* ==========================================================================
       Workflow Icons
       ========================================================================== */

    const WORKFLOW_DRAFT = 'bi-file-earmark';
    const WORKFLOW_SUBMITTED = 'bi-send';
    const WORKFLOW_REVIEWED = 'bi-eye-fill';
    const WORKFLOW_APPROVED = 'bi-check-circle-fill';
    const WORKFLOW_IN_TRANSIT = 'bi-truck';
    const WORKFLOW_DELIVERED = 'bi-check2-square';

    /* ==========================================================================
       Quick Action Icons
       ========================================================================== */

    const QUICK_ACTIONS = 'bi-lightning-fill';
    const QUICK_STATS = 'bi-speedometer2';
    const PENDING_ACTIONS = 'bi-hourglass-split';
    const RECENT_ACTIVITY = 'bi-clock-history';

    /* ==========================================================================
       Utility Icons
       ========================================================================== */

    const UTIL_CALENDAR = 'bi-calendar';
    const UTIL_LOCATION = 'bi-geo-alt';
    const UTIL_USER = 'bi-person';
    const UTIL_STAR = 'bi-star-fill';
    const UTIL_LINK = 'bi-link-45deg';
    const UTIL_ATTACHMENT = 'bi-paperclip';
    const UTIL_COMMENT = 'bi-chat-square-text';
    const UTIL_TAG = 'bi-tag';

    /**
     * Get icon for a specific action type
     *
     * @param string $action Action identifier (create, edit, delete, etc.)
     * @return string Bootstrap icon class
     */
    public static function getActionIcon($action)
    {
        $iconMap = [
            'create' => self::ACTION_CREATE,
            'add' => self::ACTION_CREATE,
            'new' => self::ACTION_CREATE,
            'edit' => self::ACTION_EDIT,
            'update' => self::ACTION_EDIT,
            'delete' => self::ACTION_DELETE,
            'remove' => self::ACTION_DELETE,
            'view' => self::ACTION_VIEW,
            'show' => self::ACTION_VIEW,
            'search' => self::ACTION_SEARCH,
            'filter' => self::ACTION_FILTER,
            'download' => self::ACTION_DOWNLOAD,
            'upload' => self::ACTION_UPLOAD,
            'print' => self::ACTION_PRINT,
            'export' => self::ACTION_EXPORT,
            'import' => self::ACTION_IMPORT,
            'refresh' => self::ACTION_REFRESH,
            'reload' => self::ACTION_REFRESH,
            'save' => self::ACTION_SAVE,
            'cancel' => self::ACTION_CANCEL,
            'approve' => self::ACTION_APPROVE,
            'reject' => self::ACTION_REJECT,
            'submit' => self::ACTION_SUBMIT,
            'verify' => self::ACTION_VERIFY
        ];

        return $iconMap[strtolower($action)] ?? 'bi-circle';
    }

    /**
     * Get icon for a specific module/entity
     *
     * @param string $module Module identifier
     * @return string Bootstrap icon class
     */
    public static function getModuleIcon($module)
    {
        $iconMap = [
            'dashboard' => self::MODULE_DASHBOARD,
            'assets' => self::MODULE_ASSETS,
            'asset' => self::MODULE_ASSETS,
            'requests' => self::MODULE_REQUESTS,
            'request' => self::MODULE_REQUESTS,
            'procurement' => self::MODULE_PROCUREMENT,
            'procurement-orders' => self::MODULE_PROCUREMENT,
            'transfers' => self::MODULE_TRANSFERS,
            'transfer' => self::MODULE_TRANSFERS,
            'withdrawals' => self::MODULE_WITHDRAWALS,
            'withdrawal' => self::MODULE_WITHDRAWALS,
            'borrowed-tools' => self::MODULE_BORROWED_TOOLS,
            'borrowed_tools' => self::MODULE_BORROWED_TOOLS,
            'incidents' => self::MODULE_INCIDENTS,
            'incident' => self::MODULE_INCIDENTS,
            'maintenance' => self::MODULE_MAINTENANCE,
            'projects' => self::MODULE_PROJECTS,
            'project' => self::MODULE_PROJECTS,
            'users' => self::MODULE_USERS,
            'user' => self::MODULE_USERS,
            'reports' => self::MODULE_REPORTS,
            'report' => self::MODULE_REPORTS,
            'settings' => self::MODULE_SETTINGS,
            'notifications' => self::MODULE_NOTIFICATIONS,
            'messages' => self::MODULE_MESSAGES
        ];

        return $iconMap[strtolower($module)] ?? 'bi-box';
    }

    /**
     * Get icon for a specific status
     *
     * @param string $status Status value
     * @return string Bootstrap icon class
     */
    public static function getStatusIcon($status)
    {
        // First check asset-specific statuses
        $assetIconMap = [
            'Available' => self::ASSET_AVAILABLE,
            'In Use' => self::ASSET_IN_USE,
            'Maintenance' => self::ASSET_MAINTENANCE,
            'Retired' => self::ASSET_RETIRED,
            'Disposed' => self::ASSET_DISPOSED,
            'Lost' => self::ASSET_LOST,
            'Damaged' => self::ASSET_DAMAGED
        ];

        if (isset($assetIconMap[$status])) {
            return $assetIconMap[$status];
        }

        // Then check general workflow statuses
        $generalIconMap = [
            'Draft' => self::WORKFLOW_DRAFT,
            'Submitted' => self::WORKFLOW_SUBMITTED,
            'Reviewed' => self::WORKFLOW_REVIEWED,
            'Approved' => self::WORKFLOW_APPROVED,
            'Pending' => self::STATUS_PENDING,
            'Pending Approval' => self::STATUS_PENDING,
            'Pending Verification' => self::STATUS_PENDING,
            'In Progress' => self::STATUS_IN_PROGRESS,
            'In Transit' => self::WORKFLOW_IN_TRANSIT,
            'Completed' => self::STATUS_COMPLETED,
            'Delivered' => self::WORKFLOW_DELIVERED,
            'Cancelled' => self::STATUS_CANCELLED,
            'Rejected' => self::STATUS_REJECTED
        ];

        return $generalIconMap[$status] ?? 'bi-circle';
    }

    /**
     * Get icon with color combination for a status
     *
     * @param string $status Status value
     * @return array ['icon' => string, 'color' => string]
     */
    public static function getStatusIconWithColor($status)
    {
        // Use WorkflowStatus class for color if available
        if (class_exists('WorkflowStatus')) {
            $color = WorkflowStatus::getStatusColor($status);
        } else {
            // Fallback color logic
            $colorMap = [
                'Draft' => 'secondary',
                'Pending' => 'warning',
                'Approved' => 'success',
                'Completed' => 'success',
                'Rejected' => 'danger',
                'Cancelled' => 'danger'
            ];
            $color = $colorMap[$status] ?? 'primary';
        }

        return [
            'icon' => self::getStatusIcon($status),
            'color' => $color
        ];
    }

    /**
     * Render an icon with proper accessibility attributes
     *
     * @param string $iconClass Bootstrap icon class
     * @param array $options Optional attributes ['color' => 'primary', 'size' => 'fs-3', 'class' => 'me-2']
     * @return string HTML icon element
     */
    public static function renderIcon($iconClass, $options = [])
    {
        $color = isset($options['color']) ? ' text-' . htmlspecialchars($options['color']) : '';
        $size = isset($options['size']) ? ' ' . htmlspecialchars($options['size']) : '';
        $additionalClass = isset($options['class']) ? ' ' . htmlspecialchars($options['class']) : '';

        return '<i class="' . htmlspecialchars($iconClass) . $color . $size . $additionalClass . '" aria-hidden="true"></i>';
    }
}
