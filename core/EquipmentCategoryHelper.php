<?php
/**
 * ConstructLink™ Equipment Category Helper
 * Groups equipment into simplified categories for borrower-friendly forms
 * Developed by: Ranoa Digital Solutions
 */

class EquipmentCategoryHelper {

    /**
     * Get simplified category groupings for borrowed tools form
     * These match common worker understanding regardless of database structure
     */
    public static function getSimplifiedCategories() {
        return [
            'power_tools' => [
                'label' => 'Power Tools',
                'icon' => 'bi-tools',
                'description' => 'Electric and battery-powered tools',
                'db_categories' => ['Power Tools', 'Drilling Tools', 'Cutting Tools', 'Grinding Tools'],
                'common_items' => ['Drill', 'Grinder', 'Circular Saw', 'Jigsaw', 'Sander', 'Impact Driver']
            ],
            'hand_tools' => [
                'label' => 'Hand Tools',
                'icon' => 'bi-hammer',
                'description' => 'Manual hand tools',
                'db_categories' => ['Hand Tools', 'Fastening Tools', 'Measuring Tools'],
                'common_items' => ['Hammer', 'Screwdriver', 'Wrench', 'Pliers', 'Tape Measure', 'Level']
            ],
            'painting' => [
                'label' => 'Painting Equipment',
                'icon' => 'bi-paint-bucket',
                'description' => 'Painting and finishing tools',
                'db_categories' => ['Painting Tools'],
                'common_items' => ['Paint Brush', 'Roller', 'Paint Sprayer', 'Caulking Gun', 'Putty Knife']
            ],
            'heavy_equipment' => [
                'label' => 'Heavy Equipment',
                'icon' => 'bi-truck',
                'description' => 'Large machinery and equipment',
                'db_categories' => ['Heavy Equipment', 'Earthmoving Equipment', 'Construction Machinery'],
                'common_items' => ['Excavator', 'Concrete Mixer', 'Compressor', 'Generator', 'Welding Machine']
            ],
            'safety' => [
                'label' => 'Safety Equipment',
                'icon' => 'bi-shield-check',
                'description' => 'Personal protective equipment',
                'db_categories' => ['Safety Equipment', 'PPE'],
                'common_items' => ['Hard Hat', 'Safety Gloves', 'Safety Harness', 'Safety Boots', 'Safety Glasses']
            ],
            'others' => [
                'label' => 'Others',
                'icon' => 'bi-three-dots',
                'description' => 'Other equipment and tools',
                'db_categories' => [], // Catch-all
                'common_items' => []
            ]
        ];
    }

    /**
     * Get equipment grouped by simplified categories from database
     * Filters to only show non-consumable, available assets from user's project
     *
     * @param int|null $projectId Filter by project
     * @return array Equipment grouped by simplified categories
     */
    public static function getGroupedEquipment($projectId = null) {
        try {
            $db = Database::getInstance()->getConnection();

            // Build query
            $sql = "
                SELECT
                    a.id,
                    a.ref,
                    a.name,
                    a.model,
                    a.serial_number,
                    a.acquisition_cost,
                    a.status,
                    c.id as category_id,
                    c.name as category_name,
                    c.iso_code,
                    et.id as equipment_type_id,
                    et.name as equipment_type_name,
                    p.name as project_name,
                    p.id as project_id
                FROM assets a
                INNER JOIN categories c ON a.category_id = c.id
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                INNER JOIN projects p ON a.project_id = p.id
                WHERE a.status = 'available'
                  AND a.workflow_status = 'approved'
                  AND c.is_consumable = 0
                  AND p.is_active = 1
            ";

            $params = [];

            if ($projectId) {
                $sql .= " AND a.project_id = ?";
                $params[] = $projectId;
            }

            // Exclude items currently borrowed or in pending batches
            // But allow items that are fully returned (quantity = quantity_returned) or have status 'Returned'
            $sql .= " AND a.id NOT IN (
                SELECT bt.asset_id
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                WHERE btb.status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released', 'Borrowed', 'Partially Returned')
                  AND bt.status != 'Returned'
                  AND (bt.quantity > bt.quantity_returned OR bt.quantity_returned IS NULL)
            )";

            $sql .= " ORDER BY c.name, et.name, a.name";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $allEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group by simplified categories
            $categories = self::getSimplifiedCategories();
            $grouped = [];

            // Initialize groups
            foreach ($categories as $key => $category) {
                $grouped[$key] = [
                    'label' => $category['label'],
                    'icon' => $category['icon'],
                    'description' => $category['description'],
                    'items' => []
                ];
            }

            // Sort equipment into groups
            foreach ($allEquipment as $equipment) {
                $categoryName = $equipment['category_name'];
                $placed = false;

                // Try to match to a simplified category
                foreach ($categories as $key => $category) {
                    if ($key === 'others') continue; // Skip others for now

                    if (in_array($categoryName, $category['db_categories'])) {
                        $grouped[$key]['items'][] = $equipment;
                        $placed = true;
                        break;
                    }
                }

                // If not placed, put in "Others"
                if (!$placed) {
                    $grouped['others']['items'][] = $equipment;
                }
            }

            // Remove empty categories (except Others)
            foreach ($grouped as $key => $group) {
                if ($key !== 'others' && empty($group['items'])) {
                    unset($grouped[$key]);
                }
            }

            return $grouped;

        } catch (Exception $e) {
            error_log("Get grouped equipment error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get printable form items grouped by category
     * Returns items formatted for the 4-per-page checklist form
     *
     * @param int $projectId User's project ID
     * @return array Items grouped for printable form
     */
    public static function getPrintableFormGroups($projectId = null) {
        $grouped = self::getGroupedEquipment($projectId);
        $formGroups = [];

        foreach ($grouped as $key => $group) {
            if (empty($group['items'])) continue;

            $formGroups[$key] = [
                'label' => $group['label'],
                'items' => []
            ];

            // Get most common items (limit to top 10 per category for form space)
            $items = $group['items'];
            usort($items, function($a, $b) {
                // Sort by name
                return strcmp($a['name'], $b['name']);
            });

            foreach (array_slice($items, 0, 10) as $item) {
                $formGroups[$key]['items'][] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'ref' => $item['ref']
                ];
            }
        }

        return $formGroups;
    }

    /**
     * Map database category to simplified category
     *
     * @param string $categoryName Database category name
     * @return string Simplified category key
     */
    public static function mapToSimplifiedCategory($categoryName) {
        $categories = self::getSimplifiedCategories();

        foreach ($categories as $key => $category) {
            if ($key === 'others') continue;

            if (in_array($categoryName, $category['db_categories'])) {
                return $key;
            }
        }

        return 'others';
    }

    /**
     * Get category color for UI
     *
     * @param string $categoryKey Simplified category key
     * @return string Bootstrap color class
     */
    public static function getCategoryColor($categoryKey) {
        $colors = [
            'power_tools' => 'primary',
            'hand_tools' => 'success',
            'painting' => 'warning',
            'heavy_equipment' => 'danger',
            'safety' => 'info',
            'others' => 'secondary'
        ];

        return $colors[$categoryKey] ?? 'secondary';
    }

    /**
     * Get common borrower names (autocomplete helper)
     * Returns list of borrower names who have borrowed before with active borrow info
     *
     * @param int|null $projectId Filter by project
     * @param int $limit Number of results
     * @return array List of borrower names with borrow counts and active borrows
     */
    public static function getCommonBorrowers($projectId = null, $limit = 20) {
        try {
            $db = Database::getInstance()->getConnection();

            // Standardize borrower names: UPPER(TRIM(borrower_name))
            $sql = "
                SELECT
                    TRIM(btb.borrower_name) as borrower_name,
                    btb.borrower_contact,
                    COUNT(DISTINCT btb.id) as borrow_count,
                    MAX(btb.created_at) as last_borrow_date,
                    COUNT(DISTINCT CASE WHEN btb.status IN ('Released', 'Partially Returned') THEN btb.id END) as active_borrows,
                    SUM(CASE WHEN btb.status IN ('Released', 'Partially Returned') THEN btb.total_items ELSE 0 END) as active_items_count
                FROM borrowed_tool_batches btb
            ";

            $conditions = [];
            $params = [];

            if ($projectId) {
                $sql .= " INNER JOIN borrowed_tools bt ON btb.id = bt.batch_id
                          INNER JOIN assets a ON bt.asset_id = a.id";
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            // Group by standardized name (trimmed, case-insensitive grouping)
            $sql .= " GROUP BY UPPER(TRIM(btb.borrower_name)), btb.borrower_contact
                      ORDER BY active_borrows DESC, borrow_count DESC, last_borrow_date DESC
                      LIMIT ?";

            $params[] = $limit;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get common borrowers error: " . $e->getMessage());
            return [];
        }
    }
}
?>
