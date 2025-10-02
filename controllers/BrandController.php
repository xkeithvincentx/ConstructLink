<?php
/**
 * Brand Controller
 * Manages brand master data
 */

require_once APP_ROOT . '/models/BaseModel.php';

class BrandController {
    private $auth;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
    }
    
    public function index() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        include APP_ROOT . '/views/brands/index.php';
    }
    
    public function create() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        include APP_ROOT . '/views/brands/create.php';
    }
    
    public function edit() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        include APP_ROOT . '/views/brands/edit.php';
    }
    
    public function view() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        include APP_ROOT . '/views/brands/view.php';
    }
}
?>