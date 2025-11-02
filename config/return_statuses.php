<?php
/**
 * Return Status Configuration
 * Centralized return status definitions for temporary transfers
 *
 * @package ConstructLink
 * @since 1.0.0
 */

return [
    'NOT_RETURNED' => [
        'value' => 'not_returned',
        'label' => 'Not Returned',
        'badge_class' => 'secondary',
        'text_class' => '',
        'icon' => 'bi-clock',
        'description' => 'Asset has not been returned yet',
        'can_initiate_return' => true,
        'can_receive_return' => false
    ],
    'IN_RETURN_TRANSIT' => [
        'value' => 'in_return_transit',
        'label' => 'In Return Transit',
        'badge_class' => 'warning',
        'text_class' => 'text-dark',
        'icon' => 'bi-truck',
        'description' => 'Asset is being returned to origin',
        'can_initiate_return' => false,
        'can_receive_return' => true
    ],
    'RETURNED' => [
        'value' => 'returned',
        'label' => 'Returned',
        'badge_class' => 'success',
        'text_class' => '',
        'icon' => 'bi-check-circle',
        'description' => 'Asset has been returned to origin project',
        'can_initiate_return' => false,
        'can_receive_return' => false
    ]
];
