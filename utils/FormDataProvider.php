<?php
/**
 * ConstructLink™ Form Data Provider
 *
 * Centralized utility for loading dropdown options and form data.
 * Eliminates code duplication across controllers by providing a single
 * method to load all necessary form options.
 *
 * DESIGN PATTERN: Service Locator / Data Provider
 * - Aggregates data from multiple models/repositories
 * - Provides consistent interface for form data
 * - Reduces controller bloat
 *
 * USAGE:
 * ```php
 * // In controller
 * $formProvider = new FormDataProvider();
 * $formOptions = $formProvider->getAssetFormOptions();
 * extract($formOptions);
 * // Now you have: $categories, $projects, $makers, $vendors, $clients, $brands
 * ```
 *
 * @package ConstructLink
 * @version 1.0.0
 */

// Model Dependencies
require_once APP_ROOT . '/models/CategoryModel.php';
require_once APP_ROOT . '/models/ProjectModel.php';
require_once APP_ROOT . '/models/VendorModel.php';

// Repository Dependencies
require_once APP_ROOT . '/repositories/BrandRepository.php';

// Optional Models (loaded conditionally in code)
if (file_exists(APP_ROOT . '/models/MakerModel.php')) {
    require_once APP_ROOT . '/models/MakerModel.php';
}
if (file_exists(APP_ROOT . '/models/ClientModel.php')) {
    require_once APP_ROOT . '/models/ClientModel.php';
}

class FormDataProvider
{
    /**
     * Get all form options needed for asset creation/editing
     *
     * Loads dropdown data from multiple sources:
     * - Categories (active categories from CategoryModel)
     * - Projects (active projects from ProjectModel)
     * - Makers (active makers from MakerModel)
     * - Vendors (all vendors from VendorModel)
     * - Clients (all clients from ClientModel)
     * - Brands (active brands from BrandRepository)
     *
     * @return array Associative array with keys: categories, projects, makers, vendors, clients, brands
     *
     * @example
     * ```php
     * $formProvider = new FormDataProvider();
     * $formOptions = $formProvider->getAssetFormOptions();
     *
     * // Access data
     * foreach ($formOptions['brands'] as $brand) {
     *     echo $brand['official_name'];
     * }
     *
     * // Or use extract for direct variable access
     * extract($formOptions);
     * // Now: $categories, $projects, $makers, $vendors, $clients, $brands
     * ```
     */
    public function getAssetFormOptions(): array
    {
        $options = [
            'categories' => $this->getCategories(),
            'projects' => $this->getProjects(),
            'makers' => $this->getMakers(),
            'vendors' => $this->getVendors(),
            'clients' => $this->getClients(),
            'brands' => $this->getBrands(),
        ];

        return $options;
    }

    /**
     * Get active categories
     *
     * @return array Array of category records
     */
    private function getCategories(): array
    {
        try {
            $categoryModel = new CategoryModel();
            return $categoryModel->getActiveCategories();
        } catch (Exception $e) {
            error_log("FormDataProvider::getCategories() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active projects
     *
     * @return array Array of project records
     */
    private function getProjects(): array
    {
        try {
            $projectModel = new ProjectModel();
            return $projectModel->getActiveProjects();
        } catch (Exception $e) {
            error_log("FormDataProvider::getProjects() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active makers
     *
     * @return array Array of maker records
     */
    private function getMakers(): array
    {
        try {
            // Check if MakerModel exists
            if (!class_exists('MakerModel')) {
                return [];
            }

            $makerModel = new MakerModel();

            // Try modern method first, fallback to legacy
            if (method_exists($makerModel, 'getActiveMakers')) {
                return $makerModel->getActiveMakers();
            } elseif (method_exists($makerModel, 'findAll')) {
                return $makerModel->findAll(['is_active' => 1], 'name ASC');
            }

            return [];
        } catch (Exception $e) {
            error_log("FormDataProvider::getMakers() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all vendors
     *
     * @return array Array of vendor records
     */
    private function getVendors(): array
    {
        try {
            $vendorModel = new VendorModel();

            if (method_exists($vendorModel, 'findAll')) {
                return $vendorModel->findAll([], 'name ASC');
            }

            return [];
        } catch (Exception $e) {
            error_log("FormDataProvider::getVendors() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all clients
     *
     * @return array Array of client records
     */
    private function getClients(): array
    {
        try {
            // Check if ClientModel exists
            if (!class_exists('ClientModel')) {
                return [];
            }

            $clientModel = new ClientModel();

            if (method_exists($clientModel, 'findAll')) {
                return $clientModel->findAll([], 'name ASC');
            }

            return [];
        } catch (Exception $e) {
            error_log("FormDataProvider::getClients() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active brands from inventory_brands table
     *
     * @return array Array of brand records with id, official_name, quality_tier
     */
    private function getBrands(): array
    {
        try {
            $brandRepository = new BrandRepository();
            return $brandRepository->getActiveBrands();
        } catch (Exception $e) {
            error_log("FormDataProvider::getBrands() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get filter options for asset listing page
     *
     * Loads dropdown data specifically for filtering:
     * - Categories, Projects, Vendors, Brands
     *
     * @return array Associative array with filter options
     */
    public function getAssetFilterOptions(): array
    {
        return [
            'categories' => $this->getCategories(),
            'projects' => $this->getProjects(),
            'vendors' => $this->getVendors(),
            'brands' => $this->getBrands(),
        ];
    }

    /**
     * Get options for specific form element
     *
     * @param string $element Element name (categories, projects, makers, vendors, clients, brands)
     * @return array Array of records for the specified element
     */
    public function getFormElement(string $element): array
    {
        $methodMap = [
            'categories' => 'getCategories',
            'projects' => 'getProjects',
            'makers' => 'getMakers',
            'vendors' => 'getVendors',
            'clients' => 'getClients',
            'brands' => 'getBrands',
        ];

        if (!isset($methodMap[$element])) {
            error_log("FormDataProvider::getFormElement() - Unknown element: {$element}");
            return [];
        }

        $method = $methodMap[$element];
        return $this->$method();
    }

    /**
     * Get dropdown options as key-value pairs
     *
     * Converts array of records to simple id => name mapping for dropdowns
     *
     * @param array $records Array of records with 'id' and 'name' keys
     * @param string $idField Field to use as key (default: 'id')
     * @param string $nameField Field to use as value (default: 'name')
     * @return array Associative array [id => name]
     */
    public static function toDropdownOptions(array $records, string $idField = 'id', string $nameField = 'name'): array
    {
        $options = [];

        foreach ($records as $record) {
            if (isset($record[$idField]) && isset($record[$nameField])) {
                $options[$record[$idField]] = $record[$nameField];
            }
        }

        return $options;
    }

    /**
     * Get brand dropdown options as key-value pairs
     *
     * Special handling for brands which use 'official_name' instead of 'name'
     *
     * @return array Associative array [id => official_name]
     */
    public function getBrandDropdownOptions(): array
    {
        $brands = $this->getBrands();
        return self::toDropdownOptions($brands, 'id', 'official_name');
    }
}
