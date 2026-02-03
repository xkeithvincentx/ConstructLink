<?php
/**
 * ConstructLink™ Router
 * Handles URL routing and request dispatching
 */

class Router {
    private $routes = [];
    private $currentRoute = '';
    
    public function __construct() {
        $this->loadRoutes();
    }
    
    /**
     * Load routes from routes.php
     */
    private function loadRoutes() {
        global $routes;
        $this->routes = $routes ?? [];
    }
    
    /**
     * Handle incoming request
     */
    public function handleRequest() {
        // Get the requested route
        $route = $this->getRoute();
        $this->currentRoute = $route;
        
        // Handle root route - check authentication first
        if ($route === '' || $route === '/') {
            // Check if database exists first
            try {
                $db = Database::getInstance();
                if (!$db->tableExists('users')) {
                    $this->dispatchRoute('install');
                    return;
                }
                
                // Check if user is authenticated
                $auth = Auth::getInstance();
                if ($auth->isAuthenticated()) {
                    $this->dispatchRoute('dashboard');
                } else {
                    $this->dispatchRoute('login');
                }
            } catch (Exception $e) {
                // Database connection failed, show install
                $this->dispatchRoute('install');
            }
            return;
        }
        
        // Route to appropriate controller using routes configuration
        try {
            $this->dispatchRoute($route);
        } catch (Exception $e) {
            error_log("Routing error: " . $e->getMessage());
            $this->show404();
        }
    }
    
    /**
     * Get the current route from URL
     */
    private function getRoute() {
        // Try different methods to get route
        $route = '';
        
        // Method 1: GET parameter
        if (isset($_GET['route'])) {
            $route = $_GET['route'];
        }
        // Method 2: PATH_INFO
        elseif (isset($_SERVER['PATH_INFO'])) {
            $route = trim($_SERVER['PATH_INFO'], '/');
        }
        // Method 3: REQUEST_URI parsing
        elseif (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $uri = parse_url($uri, PHP_URL_PATH);
            $uri = trim($uri, '/');
            if ($uri !== 'index.php' && $uri !== '') {
                $route = $uri;
            }
        }
        
        // Remove query string
        if (($pos = strpos($route, '?')) !== false) {
            $route = substr($route, 0, $pos);
        }
        
        return $route;
    }
    
    /**
     * Dispatch request using routes configuration
     */
    private function dispatchRoute($route) {
        // Get route configuration
        $routeConfig = $this->routes[$route] ?? null;
        
        if (!$routeConfig) {
            $this->show404();
            return;
        }
        
        // Check authentication requirement
        if (isset($routeConfig['auth']) && $routeConfig['auth']) {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                // For AJAX requests, return JSON error instead of redirect
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['valid' => false, 'message' => 'Authentication required']);
                    exit;
                }
                
                // Store intended URL for redirect after login
                // Fix: Sanitize and ensure proper query string format
                $intendedUrl = $_SERVER['REQUEST_URI'] ?? '';
                // Ensure URL starts with ? or / for proper redirects
                if (!empty($intendedUrl) && $intendedUrl !== '/' && !str_starts_with($intendedUrl, '?')) {
                    $intendedUrl = '?' . ltrim($intendedUrl, '/');
                }
                $_SESSION['intended_url'] = $intendedUrl;
                header('Location: ?route=login');
                exit;
            }
            
            // Check role-based access - IMPROVED LOGIC
            if (isset($routeConfig['roles']) && !empty($routeConfig['roles'])) {
                $user = $auth->getCurrentUser();
                
                if (!$user) {
                    error_log("Router: User data not found during role check for route: $route");
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        http_response_code(403);
                        echo json_encode(['valid' => false, 'message' => 'Access denied']);
                        exit;
                    }
                    $this->show403();
                    return;
                }
                
                $userRole = $user['role_name'] ?? '';
                
                // System Admin always has access
                if ($userRole === 'System Admin') {
                    // Allow access - System Admin bypasses all role checks
                } elseif (!in_array($userRole, $routeConfig['roles'])) {
                    error_log("Router: Access denied for user role '$userRole' on route '$route'. Required roles: " . implode(', ', $routeConfig['roles']));
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        http_response_code(403);
                        echo json_encode(['valid' => false, 'message' => 'Access denied']);
                        exit;
                    }
                    $this->show403();
                    return;
                }
            }
        }
        
        // Get controller and action
        $controllerName = $routeConfig['controller'];
        $action = $routeConfig['action'];
        
        // Load and instantiate controller
        $controllerFile = APP_ROOT . '/controllers/' . $controllerName . '.php';
        
        if (!file_exists($controllerFile)) {
            error_log("Router: Controller file not found: $controllerFile");
            $this->show404();
            return;
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            error_log("Router: Controller class not found: $controllerName");
            $this->show404();
            return;
        }
        
        $controllerInstance = new $controllerName();
        
        // Check if method exists
        if (!method_exists($controllerInstance, $action)) {
            // Try default methods
            if (method_exists($controllerInstance, 'index')) {
                $action = 'index';
            } else {
                error_log("Router: Method '$action' not found in controller '$controllerName'");
                $this->show404();
                return;
            }
        }
        
        // Call controller method
        try {
            $controllerInstance->$action();
        } catch (Exception $e) {
            error_log("Router: Error executing $controllerName::$action - " . $e->getMessage());
            $this->show404();
        }
    }
    
    /**
     * Legacy dispatch method for backward compatibility
     */
    private function dispatch($controller, $action, $id = null) {
        // Map routes to controllers (complete mapping)
        $controllerMap = [
            'install' => 'InstallController',
            'login' => 'AuthController',
            'logout' => 'AuthController',
            'dashboard' => 'DashboardController',
            'assets' => 'AssetController',
            'withdrawals' => 'WithdrawalController',
            'transfers' => 'TransferController',
            'maintenance' => 'MaintenanceController',
            'incidents' => 'IncidentController',
            'users' => 'UserController',
            'reports' => 'ReportController',
            'api' => 'ApiController',
            'projects' => 'ProjectController',
            'categories' => 'CategoryController',
            'vendors' => 'VendorController',
            'makers' => 'MakerController',
            'clients' => 'ClientController'
        ];
        
        // Get controller class
        if (isset($controllerMap[$controller])) {
            $controllerClass = $controllerMap[$controller];
        } else {
            $this->show404();
            return;
        }
        
        // Set method based on controller and action
        if ($controller === 'login') {
            $method = 'login';
        } elseif ($controller === 'logout') {
            $method = 'logout';
        } elseif ($controller === 'install') {
            $method = 'index';
        } elseif ($controller === 'dashboard') {
            $method = 'index';
        } else {
            $method = $action;
        }
        
        // Load controller file
        $controllerFile = APP_ROOT . '/controllers/' . $controllerClass . '.php';
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
        } else {
            $this->show404();
            return;
        }
        
        // Check if class exists after loading
        if (!class_exists($controllerClass)) {
            $this->show404();
            return;
        }
        
        // Instantiate controller
        $controllerInstance = new $controllerClass();
        
        // Check if method exists
        if (!method_exists($controllerInstance, $method)) {
            // Try default methods
            if (method_exists($controllerInstance, 'index')) {
                $method = 'index';
            } else {
                $this->show404();
                return;
            }
        }
        
        // Set request parameters
        if ($id) {
            $_GET['id'] = $id;
        }
        
        // Call controller method
        $controllerInstance->$method();
    }
    
    /**
     * Show 404 error page
     */
    private function show404() {
        http_response_code(404);
        if (file_exists(APP_ROOT . '/views/errors/404.php')) {
            include APP_ROOT . '/views/errors/404.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }
    
    /**
     * Show 403 forbidden page
     */
    private function show403() {
        http_response_code(403);
        if (file_exists(APP_ROOT . '/views/errors/403.php')) {
            include APP_ROOT . '/views/errors/403.php';
        } else {
            echo '<h1>403 - Access Forbidden</h1>';
        }
    }
    
    /**
     * Get current route
     */
    public function getCurrentRoute() {
        return $this->currentRoute;
    }
    
    /**
     * Generate URL
     */
    public static function url($path) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        return $baseUrl . '/?' . http_build_query(['route' => $path]);
    }
    
    /**
     * Check if current route matches pattern
     */
    public function isCurrentRoute($pattern) {
        return fnmatch($pattern, $this->currentRoute);
    }
}
?>