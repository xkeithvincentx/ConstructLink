<?php
/**
 * Transfer Status Configuration
 * Centralized transfer status definitions with metadata
 *
 * @package ConstructLink
 * @since 1.0.0
 */

return [
    'PENDING_VERIFICATION' => [
        'value' => 'Pending Verification',
        'label' => 'Pending Verification',
        'badge_class' => 'warning',
        'text_class' => 'text-dark',
        'icon' => 'bi-clock-history',
        'description' => 'Awaiting project manager verification',
        'can_verify' => true,
        'can_approve' => false,
        'can_dispatch' => false,
        'can_receive' => false,
        'can_complete' => false,
        'can_cancel' => true
    ],
    'PENDING_APPROVAL' => [
        'value' => 'Pending Approval',
        'label' => 'Pending Approval',
        'badge_class' => 'info',
        'text_class' => '',
        'icon' => 'bi-hourglass-split',
        'description' => 'Awaiting authorizer approval',
        'can_verify' => false,
        'can_approve' => true,
        'can_dispatch' => false,
        'can_receive' => false,
        'can_complete' => false,
        'can_cancel' => true
    ],
    'APPROVED' => [
        'value' => 'Approved',
        'label' => 'Approved',
        'badge_class' => 'success',
        'text_class' => '',
        'icon' => 'bi-check-circle',
        'description' => 'Approved and ready for dispatch',
        'can_verify' => false,
        'can_approve' => false,
        'can_dispatch' => true,
        'can_receive' => false,
        'can_complete' => false,
        'can_cancel' => true
    ],
    'IN_TRANSIT' => [
        'value' => 'In Transit',
        'label' => 'In Transit',
        'badge_class' => 'primary',
        'text_class' => '',
        'icon' => 'bi-truck',
        'description' => 'Asset is being transferred',
        'can_verify' => false,
        'can_approve' => false,
        'can_dispatch' => false,
        'can_receive' => true,
        'can_complete' => false,
        'can_cancel' => true
    ],
    'RECEIVED' => [
        'value' => 'Received',
        'label' => 'Received',
        'badge_class' => 'secondary',
        'text_class' => '',
        'icon' => 'bi-box-arrow-in-down',
        'description' => 'Received at destination, pending completion',
        'can_verify' => false,
        'can_approve' => false,
        'can_dispatch' => false,
        'can_receive' => false,
        'can_complete' => true,
        'can_cancel' => false
    ],
    'COMPLETED' => [
        'value' => 'Completed',
        'label' => 'Completed',
        'badge_class' => 'dark',
        'text_class' => '',
        'icon' => 'bi-check-circle-fill',
        'description' => 'Transfer completed successfully',
        'can_verify' => false,
        'can_approve' => false,
        'can_dispatch' => false,
        'can_receive' => false,
        'can_complete' => false,
        'can_cancel' => false
    ],
    'CANCELED' => [
        'value' => 'Canceled',
        'label' => 'Canceled',
        'badge_class' => 'danger',
        'text_class' => '',
        'icon' => 'bi-x-circle',
        'description' => 'Transfer request canceled',
        'can_verify' => false,
        'can_approve' => false,
        'can_dispatch' => false,
        'can_receive' => false,
        'can_complete' => false,
        'can_cancel' => false
    ]
];
