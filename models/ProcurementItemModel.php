<?php
/**
 * ConstructLink™ Procurement Item Model
 * Handles individual items within procurement orders
 */

class ProcurementItemModel extends BaseModel {
    protected $table = 'procurement_items';
    protected $fillable = [
        'procurement_order_id', 'item_name', 'description', 'specifications',
        'model', 'brand', 'category_id', 'purchase_type', 'atc_code_id',
        'ewt_rate', 'ewt_amount', 'quantity', 'unit', 'unit_price',
        'delivery_status', 'quantity_received', 'quality_notes', 'item_notes'
    ];

    /**
     * Get items by procurement order ID
     */
    public function getItemsByOrderId($procurementOrderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name,
                       atc.code as atc_code, atc.description as atc_description,
                       atc.rate as atc_rate
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN atc_codes atc ON pi.atc_code_id = atc.id
                WHERE pi.procurement_order_id = ?
                ORDER BY pi.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items by order ID error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update item delivery status
     */
    public function updateDeliveryStatus($itemId, $status, $quantityReceived = null, $qualityNotes = null) {
        try {
            $updateData = ['delivery_status' => $status];
            
            if ($quantityReceived !== null) {
                $updateData['quantity_received'] = $quantityReceived;
            }
            
            if ($qualityNotes !== null) {
                $updateData['quality_notes'] = $qualityNotes;
            }

            return $this->update($itemId, $updateData);

        } catch (Exception $e) {
            error_log("Update item delivery status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get items summary for procurement order
     */
    public function getItemsSummary($procurementOrderId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_items,
                    SUM(quantity) as total_quantity,
                    SUM(quantity * unit_price) as total_value,
                    SUM(CASE WHEN delivery_status = 'Complete' THEN 1 ELSE 0 END) as completed_items,
                    SUM(CASE WHEN delivery_status = 'Partial' THEN 1 ELSE 0 END) as partial_items,
                    SUM(CASE WHEN delivery_status = 'Pending' THEN 1 ELSE 0 END) as pending_items,
                    SUM(quantity_received) as total_received
                FROM procurement_items 
                WHERE procurement_order_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            $result = $stmt->fetch();

            return $result ?: [
                'total_items' => 0,
                'total_quantity' => 0,
                'total_value' => 0,
                'completed_items' => 0,
                'partial_items' => 0,
                'pending_items' => 0,
                'total_received' => 0
            ];

        } catch (Exception $e) {
            error_log("Get items summary error: " . $e->getMessage());
            return [
                'total_items' => 0,
                'total_quantity' => 0,
                'total_value' => 0,
                'completed_items' => 0,
                'partial_items' => 0,
                'pending_items' => 0,
                'total_received' => 0
            ];
        }
    }

    /**
     * Bulk update items for an order
     */
    public function bulkUpdateItems($procurementOrderId, $items) {
        try {
            $this->beginTransaction();

            foreach ($items as $item) {
                if (isset($item['id'])) {
                    // Update existing item
                    $result = $this->update($item['id'], $item);
                    if (!$result) {
                        $this->rollback();
                        return ['success' => false, 'message' => 'Failed to update item'];
                    }
                } else {
                    // Create new item
                    $item['procurement_order_id'] = $procurementOrderId;
                    $result = $this->create($item);
                    if (!$result) {
                        $this->rollback();
                        return ['success' => false, 'message' => 'Failed to create item'];
                    }
                }
            }

            $this->commit();
            return ['success' => true, 'message' => 'Items updated successfully'];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Bulk update items error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update items'];
        }
    }

    /**
     * Delete items not in the provided list (for updates)
     */
    public function deleteItemsNotInList($procurementOrderId, $keepItemIds = []) {
        try {
            if (empty($keepItemIds)) {
                // Delete all items for this order
                $sql = "DELETE FROM procurement_items WHERE procurement_order_id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$procurementOrderId]);
            } else {
                // Delete items not in the keep list
                $placeholders = str_repeat('?,', count($keepItemIds) - 1) . '?';
                $sql = "DELETE FROM procurement_items WHERE procurement_order_id = ? AND id NOT IN ({$placeholders})";
                $params = array_merge([$procurementOrderId], $keepItemIds);
                $stmt = $this->db->prepare($sql);
                return $stmt->execute($params);
            }

        } catch (Exception $e) {
            error_log("Delete items not in list error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get items ready for asset generation
     */
    public function getItemsForAssetGeneration($procurementOrderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name,
                       po.project_id, po.vendor_id, po.status as order_status,
                       (COALESCE(pi.quantity_received, pi.quantity) - COALESCE(
                           (SELECT COUNT(*) FROM inventory_items a WHERE a.procurement_item_id = pi.id), 0
                       )) as available_for_generation,
                       pi.discrepancy_notes,
                       pi.discrepancy_type,
                       pi.discrepancy_resolved_at,
                       CASE 
                           WHEN pi.discrepancy_notes IS NOT NULL AND pi.discrepancy_resolved_at IS NULL THEN 'Has Unresolved Discrepancy'
                           WHEN pi.quantity_received < pi.quantity AND pi.discrepancy_resolved_at IS NULL THEN 'Quantity Shortfall'
                           ELSE 'Available'
                       END as availability_status
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.procurement_order_id = ?
                    AND (po.status = 'Received' OR po.delivery_status = 'Delivered')
                    AND pi.quantity_received > 0
                    AND (
                        pi.quantity_received >= pi.quantity 
                        OR pi.discrepancy_resolved_at IS NOT NULL
                    )
                HAVING available_for_generation > 0
                ORDER BY pi.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items for asset generation error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get items that can be converted to assets (for display purposes)
     */
    public function getItemsForAssetDisplay($procurementOrderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name,
                       po.project_id, po.vendor_id, po.status as order_status,
                       COALESCE(pi.quantity_received, pi.quantity) as total_quantity,
                       COALESCE(
                           (SELECT COUNT(*) FROM inventory_items a WHERE a.procurement_item_id = pi.id), 0
                       ) as assets_generated,
                       (COALESCE(pi.quantity_received, pi.quantity) - COALESCE(
                           (SELECT COUNT(*) FROM inventory_items a WHERE a.procurement_item_id = pi.id), 0
                       )) as available_for_generation
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.procurement_order_id = ?
                    AND (po.status = 'Received' OR po.delivery_status = 'Delivered')
                    AND COALESCE(pi.quantity_received, pi.quantity) > 0
                ORDER BY pi.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items for asset display error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get item statistics by category
     */
    public function getItemStatsByCategory($dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];

            if ($dateFrom) {
                $conditions[] = "DATE(pi.created_at) >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo) {
                $conditions[] = "DATE(pi.created_at) <= ?";
                $params[] = $dateTo;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT 
                    c.name as category_name,
                    COUNT(pi.id) as item_count,
                    SUM(pi.quantity) as total_quantity,
                    SUM(pi.quantity * pi.unit_price) as total_value,
                    AVG(pi.unit_price) as avg_unit_price
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                {$whereClause}
                GROUP BY pi.category_id, c.name
                ORDER BY total_value DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get item stats by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get items with delivery discrepancies
     */
    public function getItemsWithDiscrepancies($procurementOrderId = null) {
        try {
            $conditions = ["pi.quantity_received < pi.quantity"];
            $params = [];

            if ($procurementOrderId) {
                $conditions[] = "pi.procurement_order_id = ?";
                $params[] = $procurementOrderId;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT pi.*, po.po_number, po.title as order_title,
                       c.name as category_name
                FROM procurement_items pi
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                LEFT JOIN categories c ON pi.category_id = c.id
                {$whereClause}
                ORDER BY pi.procurement_order_id, pi.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items with discrepancies error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update item quality notes
     */
    public function updateQualityNotes($itemId, $qualityNotes) {
        try {
            return $this->update($itemId, ['quality_notes' => $qualityNotes]);
        } catch (Exception $e) {
            error_log("Update quality notes error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get items by delivery status
     */
    public function getItemsByDeliveryStatus($procurementOrderId, $status) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE pi.procurement_order_id = ? AND pi.delivery_status = ?
                ORDER BY pi.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId, $status]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items by delivery status error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get items with unresolved discrepancies for a procurement order
     */
    public function getItemsWithUnresolvedDiscrepancies($procurementOrderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name,
                       po.po_number, po.title as order_title,
                       u.full_name as resolved_by_name
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                LEFT JOIN users u ON pi.discrepancy_resolved_by = u.id
                WHERE pi.procurement_order_id = ?
                    AND (
                        (pi.discrepancy_notes IS NOT NULL AND pi.discrepancy_notes != '')
                        OR pi.quantity_received < pi.quantity
                    )
                    AND pi.discrepancy_resolved_at IS NULL
                ORDER BY pi.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items with unresolved discrepancies error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Resolve item-level discrepancy
     */
    public function resolveItemDiscrepancy($itemId, $userId, $resolutionNotes = null) {
        try {
            $this->beginTransaction();

            $updateData = [
                'discrepancy_resolved_at' => date('Y-m-d H:i:s'),
                'discrepancy_resolved_by' => $userId
            ];

            if ($resolutionNotes) {
                $currentNotes = $this->getById($itemId)['discrepancy_notes'] ?? '';
                $updateData['discrepancy_notes'] = $currentNotes . "\n\nResolution: " . $resolutionNotes;
            }

            $result = $this->update($itemId, $updateData);

            if ($result) {
                $stmt = $this->db->prepare("
                    INSERT INTO delivery_tracking (procurement_order_id, procurement_item_id, status, notes, updated_by, created_at, updated_at)
                    SELECT po.id, ?, 'Resolved', ?, ?, NOW(), NOW()
                    FROM procurement_items pi
                    JOIN procurement_orders po ON pi.procurement_order_id = po.id
                    WHERE pi.id = ?
                ");
                $trackingResult = $stmt->execute([$itemId, $resolutionNotes ?: 'Item discrepancy resolved', $userId, $itemId]);

                if ($trackingResult) {
                    $this->commit();
                    return ['success' => true, 'message' => 'Item discrepancy resolved successfully'];
                } else {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to create delivery tracking record'];
                }
            } else {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update item discrepancy status'];
            }

        } catch (Exception $e) {
            $this->rollback();
            error_log("Resolve item discrepancy error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to resolve item discrepancy'];
        }
    }

    /**
     * Get items eligible for asset generation (no unresolved discrepancies)
     */
    public function getItemsEligibleForAssetGeneration($procurementOrderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name,
                       po.project_id, po.vendor_id, po.status as order_status,
                       (COALESCE(pi.quantity_received, pi.quantity) - COALESCE(
                           (SELECT COUNT(*) FROM inventory_items a WHERE a.procurement_item_id = pi.id), 0
                       )) as available_for_generation,
                       pi.discrepancy_notes,
                       pi.discrepancy_type,
                       pi.discrepancy_resolved_at,
                       CASE 
                           WHEN pi.discrepancy_notes IS NOT NULL AND pi.discrepancy_resolved_at IS NULL THEN 'Blocked - Unresolved Discrepancy'
                           WHEN pi.quantity_received < pi.quantity AND pi.discrepancy_resolved_at IS NULL THEN 'Blocked - Quantity Shortfall'
                           ELSE 'Available'
                       END as availability_status
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.procurement_order_id = ?
                    AND (po.status = 'Received' OR po.delivery_status = 'Delivered')
                    AND pi.quantity_received > 0
                    AND (
                        (pi.discrepancy_notes IS NULL OR pi.discrepancy_resolved_at IS NOT NULL)
                        AND (pi.quantity_received >= pi.quantity OR pi.discrepancy_resolved_at IS NOT NULL)
                    )
                HAVING available_for_generation > 0
                ORDER BY pi.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items eligible for asset generation error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add discrepancy notes to an item
     */
    public function addDiscrepancyNotes($itemId, $discrepancyType, $notes) {
        try {
            $updateData = [
                'discrepancy_notes' => $notes,
                'discrepancy_type' => $discrepancyType
            ];

            return $this->update($itemId, $updateData);

        } catch (Exception $e) {
            error_log("Add discrepancy notes error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get discrepancy summary for a procurement order
     */
    public function getDiscrepancySummary($procurementOrderId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN pi.discrepancy_notes IS NOT NULL AND pi.discrepancy_notes != '' THEN 1 ELSE 0 END) as items_with_discrepancies,
                    SUM(CASE WHEN pi.discrepancy_resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved_discrepancies,
                    SUM(CASE WHEN pi.discrepancy_notes IS NOT NULL AND pi.discrepancy_resolved_at IS NULL THEN 1 ELSE 0 END) as unresolved_discrepancies,
                    SUM(CASE WHEN pi.quantity_received < pi.quantity THEN 1 ELSE 0 END) as quantity_shortfalls
                FROM procurement_items pi
                WHERE pi.procurement_order_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            $result = $stmt->fetch();

            return $result ?: [
                'total_items' => 0,
                'items_with_discrepancies' => 0,
                'resolved_discrepancies' => 0,
                'unresolved_discrepancies' => 0,
                'quantity_shortfalls' => 0
            ];

        } catch (Exception $e) {
            error_log("Get discrepancy summary error: " . $e->getMessage());
            return [
                'total_items' => 0,
                'items_with_discrepancies' => 0,
                'resolved_discrepancies' => 0,
                'unresolved_discrepancies' => 0,
                'quantity_shortfalls' => 0
            ];
        }
    }
}
