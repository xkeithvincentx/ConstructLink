<?php
/**
 * ConstructLink™ Autoloader
 * PSR-4 compliant autoloader for the application
 */

class Autoloader {
    private $prefixes = [];
    
    public function __construct() {
        // Register namespace prefixes
        $this->addNamespace('', APP_ROOT . '/');
        $this->addNamespace('Core\\', APP_ROOT . '/core/');
        $this->addNamespace('Models\\', APP_ROOT . '/models/');
        $this->addNamespace('Controllers\\', APP_ROOT . '/controllers/');
        $this->addNamespace('Views\\', APP_ROOT . '/views/');
        $this->addNamespace('Api\\', APP_ROOT . '/api/');
        $this->addNamespace('Middleware\\', APP_ROOT . '/middleware/');
        $this->addNamespace('Repositories\\', APP_ROOT . '/repositories/');
        $this->addNamespace('Utils\\', APP_ROOT . '/utils/');
    }
    
    /**
     * Register the autoloader
     */
    public function register() {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * Add a namespace prefix
     */
    public function addNamespace($prefix, $baseDir, $prepend = false) {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        
        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }
        
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            array_push($this->prefixes[$prefix], $baseDir);
        }
    }
    
    /**
     * Load a class file
     */
    public function loadClass($class) {
        $prefix = $class;
        
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }
            
            $prefix = rtrim($prefix, '\\');
        }
        
        // Try to load without namespace
        return $this->loadMappedFile('', $class);
    }
    
    /**
     * Load the mapped file for a namespace prefix and relative class
     */
    protected function loadMappedFile($prefix, $relativeClass) {
        if (!isset($this->prefixes[$prefix])) {
            return false;
        }
        
        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            
            if ($this->requireFile($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
    /**
     * Require a file if it exists
     */
    protected function requireFile($file) {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}
?>
