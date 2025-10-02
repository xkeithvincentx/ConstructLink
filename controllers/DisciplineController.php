<?php
/**
 * Discipline Controller
 * Manages discipline master data
 */

require_once APP_ROOT . '/models/BaseModel.php';

class DisciplineController {
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
        
        include APP_ROOT . '/views/disciplines/index.php';
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
        
        include APP_ROOT . '/views/disciplines/create.php';
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
        
        include APP_ROOT . '/views/disciplines/edit.php';
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
        
        include APP_ROOT . '/views/disciplines/view.php';
    }
}
?>