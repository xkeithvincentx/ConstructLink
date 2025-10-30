<?php
/**
 * ConstructLink™ Response Formatter Utility
 *
 * Standardizes response formats across models and services.
 * Eliminates inconsistent return structures throughout the application.
 *
 * Usage:
 *   return ResponseFormatter::success('Operation completed', ['data' => $result]);
 *   return ResponseFormatter::error('Operation failed', ['field' => 'Invalid value']);
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class ResponseFormatter {
    /**
     * Create a success response
     *
     * @param string $message Success message
     * @param array $data Additional data to include
     * @return array Standardized success response
     */
    public static function success($message, $data = []) {
        return array_merge([
            'success' => true,
            'message' => $message
        ], $data);
    }

    /**
     * Create an error response
     *
     * @param string $message Error message
     * @param array|string $errors Error details (array of errors or single error string)
     * @return array Standardized error response
     */
    public static function error($message, $errors = []) {
        $response = [
            'success' => false,
            'message' => $message
        ];

        // Handle both array and string errors
        if (!empty($errors)) {
            $response['errors'] = is_array($errors) ? $errors : [$errors];
        }

        return $response;
    }

    /**
     * Create a validation error response
     *
     * @param array $errors Array of validation errors
     * @param string $message Optional custom message
     * @return array Standardized validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        return [
            'success' => false,
            'valid' => false,
            'message' => $message,
            'errors' => $errors
        ];
    }

    /**
     * Create a validation success response
     *
     * @param array $data Additional data to include
     * @return array Standardized validation success response
     */
    public static function validationSuccess($data = []) {
        return array_merge([
            'success' => true,
            'valid' => true,
            'errors' => []
        ], $data);
    }

    /**
     * Create a not found response
     *
     * @param string $resource Resource name (e.g., 'Batch', 'Tool', 'User')
     * @return array Standardized not found response
     */
    public static function notFound($resource = 'Resource') {
        return [
            'success' => false,
            'message' => "{$resource} not found"
        ];
    }

    /**
     * Create a permission denied response
     *
     * @param string $message Optional custom message
     * @return array Standardized permission denied response
     */
    public static function permissionDenied($message = 'Permission denied') {
        return [
            'success' => false,
            'message' => $message
        ];
    }

    /**
     * Create an invalid status response
     *
     * @param string $currentStatus Current status
     * @param string $expectedStatus Expected status
     * @return array Standardized invalid status response
     */
    public static function invalidStatus($currentStatus, $expectedStatus) {
        return [
            'success' => false,
            'message' => "Invalid status. Expected '{$expectedStatus}', but current status is '{$currentStatus}'"
        ];
    }
}
