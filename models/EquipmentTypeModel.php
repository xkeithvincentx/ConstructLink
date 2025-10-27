<?php
/**
 * ConstructLink™ Equipment Type Model
 * Handles equipment type and subtype queries
 * Created during Phase 2.1 refactoring
 */

class EquipmentTypeModel extends BaseModel {
    protected $table = 'equipment_types';

    /**
     * Get equipment types by category names with their subtypes
     * Moved from BorrowedToolController (Phase 2.1)
     *
     * @param array $categoryNames Array of category names to filter by
     * @return array Equipment types with subtypes grouped and formatted
     */
    public function getEquipmentTypesByCategory($categoryNames = []) {
        try {
            if (empty($categoryNames)) {
                return [];
            }

            // Build IN clause placeholders
            $placeholders = implode(',', array_fill(0, count($categoryNames), '?'));

            $sql = "
                SELECT
                    et.name as type_name,
                    c.name as category_name,
                    GROUP_CONCAT(es.subtype_name ORDER BY es.subtype_name SEPARATOR ', ') as subtypes
                FROM equipment_types et
                INNER JOIN categories c ON et.category_id = c.id
                LEFT JOIN equipment_subtypes es ON et.id = es.equipment_type_id AND es.is_active = 1
                WHERE c.name IN ($placeholders)
                  AND et.is_active = 1
                GROUP BY et.id, et.name, c.name
                ORDER BY et.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($categoryNames);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format results with display names
            $formattedResults = [];
            foreach ($results as $row) {
                if ($row['subtypes']) {
                    $formattedResults[] = [
                        'type_name' => $row['type_name'],
                        'category_name' => $row['category_name'],
                        'subtypes' => $row['subtypes'],
                        'display_name' => $row['type_name'] . ' [' . $row['subtypes'] . ']'
                    ];
                } else {
                    $formattedResults[] = [
                        'type_name' => $row['type_name'],
                        'category_name' => $row['category_name'],
                        'subtypes' => null,
                        'display_name' => $row['type_name']
                    ];
                }
            }

            return $formattedResults;

        } catch (Exception $e) {
            error_log("Get equipment types by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get power tools equipment types
     * Convenience method for getting power tools specifically
     *
     * @return array Power tool types with subtypes
     */
    public function getPowerTools() {
        return $this->getEquipmentTypesByCategory([
            'Power Tools',
            'Drilling Tools',
            'Cutting Tools',
            'Grinding Tools'
        ]);
    }

    /**
     * Get hand tools equipment types
     * Convenience method for getting hand tools specifically
     *
     * @return array Hand tool types with subtypes
     */
    public function getHandTools() {
        return $this->getEquipmentTypesByCategory([
            'Hand Tools',
            'Fastening Tools',
            'Measuring Tools'
        ]);
    }
}
