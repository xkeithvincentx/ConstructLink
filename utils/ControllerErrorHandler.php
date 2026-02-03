<?php
/**
 * ConstructLink™ Controller Error Handler
 *
 * Centralized error handling utility for controllers.
 * Eliminates code duplication in try-catch blocks by providing a single
 * method for exception handling with appropriate responses.
 *
 * FEATURES:
 * - Automatic AJAX vs HTML request detection
 * - Appropriate error responses (JSON for AJAX, 500 page for HTML)
 * - Error logging with context
 * - Proper HTTP status codes
 * - User-friendly error messages
 *
 * USAGE:
 * ```php
 * try {
 *     // Controller logic
 * } catch (Exception $e) {
 *     ControllerErrorHandler::handleException($e, 'load assets');
 * }
 * ```
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class ControllerErrorHandler
{
    /**
     * Handle exception with appropriate response
     *
     * Logs the error, sets HTTP status code, and returns appropriate response
     * based on request type (AJAX/HTML). Exits script after handling.
     *
     * @param Exception $e Exception to handle
     * @param string $context Context description (e.g., 'load assets', 'create user')
     * @param bool|null $isAjax Force AJAX response (null = auto-detect)
     * @param int $statusCode HTTP status code (default: 500)
     * @return void Exits script after handling
     *
     * @example
     * ```php
     * try {
     *     $assets = $this->assetModel->getAll();
     * } catch (Exception $e) {
     *     ControllerErrorHandler::handleException($e, 'load assets');
     *     // Script exits here
     * }
     * ```
     */
    public static function handleException(
        Exception $e,
        string $context,
        ?bool $isAjax = null,
        int $statusCode = 500
    ): void {
        // Log the error with full context
        self::logError($e, $context);

        // Auto-detect AJAX if not specified
        if ($isAjax === null) {
            $isAjax = self::isAjaxRequest();
        }

        // Set HTTP status code
        http_response_code($statusCode);

        // Return appropriate response
        if ($isAjax) {
            self::sendJsonError($e, $context, $statusCode);
        } else {
            self::sendHtmlError($e, $context, $statusCode);
        }

        exit;
    }

    /**
     * Handle error without exiting (for non-fatal errors)
     *
     * Logs the error and returns error data for controller to handle.
     * Does NOT exit script.
     *
     * @param Exception $e Exception to handle
     * @param string $context Context description
     * @return array Error data array with 'success' => false and 'error' message
     *
     * @example
     * ```php
     * try {
     *     $result = $this->someOperation();
     * } catch (Exception $e) {
     *     $error = ControllerErrorHandler::getErrorData($e, 'some operation');
     *     return $this->jsonResponse($error);
     * }
     * ```
     */
    public static function getErrorData(Exception $e, string $context): array
    {
        self::logError($e, $context);

        return [
            'success' => false,
            'error' => self::getUserFriendlyMessage($context),
            'technical_error' => APP_DEBUG ? $e->getMessage() : null,
            'code' => $e->getCode() ?: 500,
        ];
    }

    /**
     * Log error with context
     *
     * @param Exception $e Exception to log
     * @param string $context Context description
     * @return void
     */
    private static function logError(Exception $e, string $context): void
    {
        $message = sprintf(
            "Error during '%s': %s in %s:%d\nStack trace:\n%s",
            $context,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        error_log($message);
    }

    /**
     * Send JSON error response
     *
     * @param Exception $e Exception
     * @param string $context Context description
     * @param int $statusCode HTTP status code
     * @return void
     */
    private static function sendJsonError(Exception $e, string $context, int $statusCode): void
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => self::getUserFriendlyMessage($context),
            'code' => $statusCode,
        ];

        // Include technical details in development mode
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $response['technical_error'] = $e->getMessage();
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
        }

        echo json_encode($response);
    }

    /**
     * Send HTML error page
     *
     * @param Exception $e Exception
     * @param string $context Context description
     * @param int $statusCode HTTP status code
     * @return void
     */
    private static function sendHtmlError(Exception $e, string $context, int $statusCode): void
    {
        // Store error message in session for display
        $_SESSION['error'] = self::getUserFriendlyMessage($context);

        // Try to load appropriate error page
        $errorPage = APP_ROOT . "/views/errors/{$statusCode}.php";

        if (file_exists($errorPage)) {
            // Set variables for error page
            $error = self::getUserFriendlyMessage($context);
            $technicalError = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null;

            include $errorPage;
        } else {
            // Fallback error page
            self::renderFallbackErrorPage($context, $statusCode, $e);
        }
    }

    /**
     * Render fallback error page when error page file doesn't exist
     *
     * @param string $context Context description
     * @param int $statusCode HTTP status code
     * @param Exception $e Exception
     * @return void
     */
    private static function renderFallbackErrorPage(string $context, int $statusCode, Exception $e): void
    {
        $errorMessage = self::getUserFriendlyMessage($context);
        $technicalError = (defined('APP_DEBUG') && APP_DEBUG) ? htmlspecialchars($e->getMessage()) : '';

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - ConstructLink</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            text-align: center;
        }
        .error-icon {
            font-size: 64px;
            color: #d9534f;
            margin-bottom: 20px;
        }
        h1 {
            color: #d9534f;
            margin: 0 0 10px 0;
        }
        .error-code {
            color: #999;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .error-message {
            color: #333;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .technical-error {
            background: #f8f8f8;
            border-left: 4px solid #d9534f;
            padding: 15px;
            text-align: left;
            font-family: monospace;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Something went wrong</h1>
        <div class="error-code">Error ' . $statusCode . '</div>
        <div class="error-message">' . htmlspecialchars($errorMessage) . '</div>
        ' . ($technicalError ? '<div class="technical-error">' . $technicalError . '</div>' : '') . '
        <a href="?route=dashboard" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>';
    }

    /**
     * Get user-friendly error message based on context
     *
     * @param string $context Context description
     * @return string User-friendly error message
     */
    private static function getUserFriendlyMessage(string $context): string
    {
        // Map common contexts to user-friendly messages
        $messageMap = [
            'load assets' => 'Failed to load assets. Please try again.',
            'create asset' => 'Failed to create asset. Please check your input and try again.',
            'update asset' => 'Failed to update asset. Please try again.',
            'delete asset' => 'Failed to delete asset. Please try again.',
            'load asset details' => 'Failed to load asset details. Please try again.',
            'export assets' => 'Failed to export assets. Please try again.',
            'bulk update' => 'Failed to perform bulk update. Please try again.',
            'save data' => 'Failed to save data. Please try again.',
            'load data' => 'Failed to load data. Please try again.',
        ];

        // Return mapped message or generic message
        return $messageMap[$context] ?? "Failed to {$context}. Please try again.";
    }

    /**
     * Detect if current request is AJAX
     *
     * @return bool True if AJAX request
     */
    private static function isAjaxRequest(): bool
    {
        // Check X-Requested-With header
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        // Check Accept header for JSON
        if (!empty($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }

        // Check Content-Type header for JSON
        if (!empty($_SERVER['CONTENT_TYPE']) &&
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Handle validation errors specifically
     *
     * @param array $errors Validation errors array
     * @param bool|null $isAjax Force AJAX response (null = auto-detect)
     * @return void Exits script after handling
     *
     * @example
     * ```php
     * $errors = ['name' => 'Name is required', 'email' => 'Invalid email'];
     * ControllerErrorHandler::handleValidationErrors($errors);
     * ```
     */
    public static function handleValidationErrors(array $errors, ?bool $isAjax = null): void
    {
        if ($isAjax === null) {
            $isAjax = self::isAjaxRequest();
        }

        http_response_code(422); // Unprocessable Entity

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Validation failed',
                'validation_errors' => $errors,
                'code' => 422,
            ]);
        } else {
            $_SESSION['validation_errors'] = $errors;
            $_SESSION['error'] = 'Please correct the errors below.';
            // Return to previous page
            if (!empty($_SERVER['HTTP_REFERER'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header('Location: ?route=dashboard');
            }
        }

        exit;
    }
}
