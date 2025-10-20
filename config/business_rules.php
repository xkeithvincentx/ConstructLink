<?php
/**
 * ConstructLink Business Rules Configuration
 * Developed by: Ranoa Digital Solutions
 *
 * This configuration file centralizes business logic rules to eliminate hardcoded values
 * throughout the application. Changes to business rules should be made here rather than
 * scattered across multiple files.
 */

return [
    /**
     * Critical Tool Threshold
     *
     * Tools with acquisition cost >= this amount are considered "critical" and require:
     * - Manager verification before release
     * - Director approval before release
     * - Enhanced tracking and oversight
     *
     * @var int Amount in currency (default: 50000)
     */
    'critical_tool_threshold' => 50000,

    /**
     * MVA Workflow Configuration
     *
     * Defines the Manager-Verify-Approval workflow behavior for different tool types.
     * Critical tools (>= threshold) go through full MVA workflow.
     * Basic tools (< threshold) skip MVA and go directly to release.
     */
    'mva_workflow' => [
        // Critical tools workflow
        'critical_requires_verification' => true,
        'critical_requires_approval' => true,

        // Basic tools workflow (non-critical)
        'basic_requires_verification' => false,
        'basic_requires_approval' => false,
    ],

    /**
     * Borrowed Tools Business Rules
     */
    'borrowed_tools' => [
        // Maximum days a tool can be borrowed (0 = unlimited)
        'max_borrow_days' => 90,

        // Days before expected return to send reminder
        'reminder_days_before' => 3,

        // Days after expected return to mark as overdue
        'overdue_grace_days' => 0,

        // Allow partial returns of batch items
        'allow_partial_returns' => true,

        // Require condition notes on return
        'require_condition_notes' => false,
    ],

    /**
     * UI/UX Configuration
     */
    'ui' => [
        // Auto-refresh interval for real-time data (seconds)
        'auto_refresh_interval' => 300, // 5 minutes

        // Number of items per page in listings
        'items_per_page' => 50,

        // Show statistics cards on index page
        'show_statistics' => true,
    ],

    /**
     * Notification Configuration
     */
    'notifications' => [
        // Enable email notifications
        'email_enabled' => false,

        // Enable in-app notifications
        'in_app_enabled' => true,

        // Notify on these events
        'events' => [
            'batch_created' => true,
            'verification_required' => true,
            'approval_required' => true,
            'ready_for_release' => true,
            'tool_released' => true,
            'return_due_soon' => true,
            'tool_overdue' => true,
            'tool_returned' => true,
        ],
    ],
];
