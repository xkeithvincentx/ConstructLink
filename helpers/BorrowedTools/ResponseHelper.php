<?php
/**
 * ConstructLink™ Borrowed Tools Response Helper
 * Handles JSON and HTML response formatting
 * Created during Phase 2.3 refactoring - extracted from BorrowedToolController
 */

class BorrowedToolsResponseHelper {
    /**
     * Send JSON or HTML error response based on request type
     *
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param string|null $redirectRoute Route to redirect to (for HTML responses)
     * @return void Terminates execution
     */
    public static function sendError($message, $code = 400, $redirectRoute = null) {
        // Detect if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Check Accept header for JSON preference
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) &&
                       strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

        if ($isAjax || $acceptsJson) {
            // Send JSON response
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'code' => $code
            ]);
            exit;
        }

        // Send HTML response
        if ($redirectRoute) {
            $_SESSION['error_message'] = $message;
            header("Location: ?route={$redirectRoute}");
            exit;
        }

        // Render error page
        http_response_code($code);
        $error = $message;
        $errorCode = $code;

        switch ($code) {
            case 403:
                include APP_ROOT . '/views/errors/403.php';
                break;
            case 404:
                include APP_ROOT . '/views/errors/404.php';
                break;
            default:
                include APP_ROOT . '/views/errors/500.php';
                break;
        }
        exit;
    }

    /**
     * Send JSON or HTML success response
     *
     * @param string $message Success message
     * @param array $data Additional data to include
     * @param string|null $redirectRoute Route to redirect to (for HTML responses)
     * @param int $code HTTP status code (default: 200)
     * @return void Terminates execution
     */
    public static function sendSuccess($message, $data = [], $redirectRoute = null, $code = 200) {
        // Detect if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Check Accept header for JSON preference
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) &&
                       strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

        if ($isAjax || $acceptsJson) {
            // Send JSON response
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode(array_merge([
                'success' => true,
                'message' => $message
            ], $data));
            exit;
        }

        // Send HTML response with redirect
        if ($redirectRoute) {
            $_SESSION['success_message'] = $message;
            header("Location: ?route={$redirectRoute}");
            exit;
        }

        // If no redirect, just output success message
        http_response_code($code);
        echo $message;
        exit;
    }

    /**
     * Redirect with success message
     *
     * @param string $message Success message
     * @param string $route Route to redirect to
     * @return void Terminates execution
     */
    public static function redirectWithSuccess($message, $route) {
        $_SESSION['success_message'] = $message;
        header("Location: ?route={$route}");
        exit;
    }

    /**
     * Redirect with error message
     *
     * @param string $message Error message
     * @param string $route Route to redirect to
     * @return void Terminates execution
     */
    public static function redirectWithError($message, $route) {
        $_SESSION['error_message'] = $message;
        header("Location: ?route={$route}");
        exit;
    }

    /**
     * Render error page with proper HTTP code
     *
     * @param int $code HTTP status code
     * @param string|null $message Optional error message
     * @return void Terminates execution
     */
    public static function renderError($code = 500, $message = null) {
        http_response_code($code);
        $error = $message;
        $errorCode = $code;

        switch ($code) {
            case 403:
                include APP_ROOT . '/views/errors/403.php';
                break;
            case 404:
                include APP_ROOT . '/views/errors/404.php';
                break;
            default:
                include APP_ROOT . '/views/errors/500.php';
                break;
        }
        exit;
    }

    /**
     * Check if current request is AJAX
     *
     * @return bool True if AJAX request
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if client accepts JSON
     *
     * @return bool True if client accepts JSON
     */
    public static function acceptsJson() {
        return isset($_SERVER['HTTP_ACCEPT']) &&
               strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }
}
