<?php
/**
 * Simple Data Quality Calculator
 * Calculates quality scores based on asset data completeness and validity
 * No database tables needed - just analyzes the data directly
 */
class SimpleDataQualityCalculator {
    
    /**
     * Calculate data quality scores for an asset
     */
    public static function calculateQuality($assetData, $userRole = null) {
        $results = [
            'overall_score' => 0,
            'completeness_score' => 0,
            'accuracy_score' => 0,
            'validation_results' => [],
            'errors' => [],
            'warnings' => [],
            'info' => []
        ];
        
        // Define what fields are required vs optional
        $requiredFields = [
            'ref' => 'Reference',
            'name' => 'Asset Name',
            'category_id' => 'Category',
            'equipment_type_id' => 'Equipment Type',
            'project_id' => 'Project',
            'quantity' => 'Quantity'
        ];
        
        $recommendedFields = [
            'brand_id' => 'Brand',
            'location' => 'Location',
            'description' => 'Description',
            'subtype_id' => 'Equipment Subtype',
            'acquisition_cost' => 'Cost'
        ];
        
        $optionalFields = [
            'serial_number' => 'Serial Number',
            'model' => 'Model',
            'discipline_tags' => 'Disciplines',
            'warranty_expiry' => 'Warranty',
            'condition_notes' => 'Condition'
        ];
        
        // Track scoring
        $totalPoints = 0;
        $earnedPoints = 0;
        $completenessTotal = 0;
        $completenessEarned = 0;
        $accuracyTotal = 0;
        $accuracyEarned = 0;
        
        // Check required fields (3 points each)
        foreach ($requiredFields as $field => $label) {
            $totalPoints += 3;
            $completenessTotal += 3;
            
            if (!empty($assetData[$field])) {
                $earnedPoints += 3;
                $completenessEarned += 3;
                $results['validation_results'][] = [
                    'field' => $field,
                    'label' => $label,
                    'status' => 'passed',
                    'message' => "$label is provided"
                ];
            } else {
                $results['errors'][] = "$label is required but missing";
                $results['validation_results'][] = [
                    'field' => $field,
                    'label' => $label,
                    'status' => 'failed',
                    'message' => "$label is required"
                ];
            }
        }
        
        // Check recommended fields (2 points each)
        foreach ($recommendedFields as $field => $label) {
            $totalPoints += 2;
            $completenessTotal += 2;
            
            if (!empty($assetData[$field])) {
                $earnedPoints += 2;
                $completenessEarned += 2;
                $results['validation_results'][] = [
                    'field' => $field,
                    'label' => $label,
                    'status' => 'passed',
                    'message' => "$label is provided"
                ];
            } else {
                $results['warnings'][] = "$label is recommended but missing";
                $results['validation_results'][] = [
                    'field' => $field,
                    'label' => $label,
                    'status' => 'warning',
                    'message' => "$label is recommended"
                ];
            }
        }
        
        // Check optional fields (1 point each)
        foreach ($optionalFields as $field => $label) {
            $totalPoints += 1;
            $completenessTotal += 1;
            
            if (!empty($assetData[$field])) {
                $earnedPoints += 1;
                $completenessEarned += 1;
                $results['validation_results'][] = [
                    'field' => $field,
                    'label' => $label,
                    'status' => 'passed',
                    'message' => "$label is provided"
                ];
            } else {
                $results['info'][] = "$label could be added for better tracking";
            }
        }
        
        // Data accuracy checks (worth 20 points total)
        $accuracyTotal = 20;
        
        // Check if quantity is reasonable (5 points)
        if (isset($assetData['quantity']) && is_numeric($assetData['quantity'])) {
            $qty = intval($assetData['quantity']);
            if ($qty > 0 && $qty < 10000) {
                $accuracyEarned += 5;
            } else {
                $results['warnings'][] = "Quantity seems unusual";
            }
        }
        
        // Check if cost is reasonable if provided (5 points)
        if (!empty($assetData['acquisition_cost']) && is_numeric($assetData['acquisition_cost'])) {
            $cost = floatval($assetData['acquisition_cost']);
            if ($cost > 0 && $cost < 10000000) {
                $accuracyEarned += 5;
            } else {
                $results['warnings'][] = "Cost seems unusual";
            }
        } else {
            // No cost provided, give partial credit
            $accuracyEarned += 3;
        }
        
        // Check if description is meaningful (5 points)
        if (!empty($assetData['description'])) {
            if (strlen($assetData['description']) > 10) {
                $accuracyEarned += 5;
            } else {
                $accuracyEarned += 2;
                $results['info'][] = "Description could be more detailed";
            }
        }
        
        // Check discipline assignment for equipment that needs it (5 points)
        $equipmentName = strtolower($assetData['equipment_type_name'] ?? '');
        $needsDiscipline = false;
        $criticalEquipment = ['generator', 'crane', 'pump', 'compressor', 'welding', 'excavator'];
        
        foreach ($criticalEquipment as $critical) {
            if (strpos($equipmentName, $critical) !== false) {
                $needsDiscipline = true;
                break;
            }
        }
        
        if ($needsDiscipline) {
            if (!empty($assetData['discipline_tags'])) {
                $accuracyEarned += 5;
            } else {
                $results['warnings'][] = "This type of equipment should have discipline assignment";
            }
        } else {
            // Not critical equipment, give full points
            $accuracyEarned += 5;
        }
        
        // Calculate final scores
        $totalPoints += $accuracyTotal;
        $earnedPoints += $accuracyEarned;
        
        $results['overall_score'] = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $results['completeness_score'] = $completenessTotal > 0 ? round(($completenessEarned / $completenessTotal) * 100, 2) : 0;
        $results['accuracy_score'] = $accuracyTotal > 0 ? round(($accuracyEarned / $accuracyTotal) * 100, 2) : 0;
        
        return $results;
    }
}
?>