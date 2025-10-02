<?php
/**
 * ConstructLink™ Vendor Intelligence Model
 * Advanced analytics and intelligent data processing for vendor management
 * Integrates with procurement, assets, and delivery tracking for comprehensive vendor insights
 */

class VendorIntelligenceModel extends BaseModel {
    protected $table = 'vendors';
    
    /**
     * Get database connection for external use
     * @return PDO
     */
    public function getDatabase() {
        return $this->db;
    }
    
    /**
     * Calculate comprehensive vendor performance score
     * @param int $vendorId
     * @return array Performance metrics and score
     */
    public function calculateVendorPerformanceScore($vendorId) {
        try {
            $metrics = [];
            
            // 1. Delivery Performance (25% weight)
            $deliveryMetrics = $this->calculateDeliveryPerformance($vendorId);
            $metrics['delivery'] = $deliveryMetrics;
            
            // 2. Quality Performance (20% weight)
            $qualityMetrics = $this->calculateQualityPerformance($vendorId);
            $metrics['quality'] = $qualityMetrics;
            
            // 3. Cost Performance (20% weight)
            $costMetrics = $this->calculateCostPerformance($vendorId);
            $metrics['cost'] = $costMetrics;
            
            // 4. Reliability Performance (20% weight)
            $reliabilityMetrics = $this->calculateReliabilityPerformance($vendorId);
            $metrics['reliability'] = $reliabilityMetrics;
            
            // 5. Financial Performance (15% weight)
            $financialMetrics = $this->calculateFinancialPerformance($vendorId);
            $metrics['financial'] = $financialMetrics;
            
            // Calculate weighted overall score
            $overallScore = (
                ($deliveryMetrics['score'] * 0.25) +
                ($qualityMetrics['score'] * 0.20) +
                ($costMetrics['score'] * 0.20) +
                ($reliabilityMetrics['score'] * 0.20) +
                ($financialMetrics['score'] * 0.15)
            );
            
            // Determine performance grade
            $grade = $this->getPerformanceGrade($overallScore);
            
            return [
                'vendor_id' => $vendorId,
                'overall_score' => $overallScore,
                'grade' => $grade,
                'metrics' => $metrics,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Calculate vendor performance score error: " . $e->getMessage());
            return [
                'vendor_id' => $vendorId,
                'overall_score' => 0,
                'grade' => 'N/A',
                'metrics' => [],
                'error' => 'Failed to calculate performance score'
            ];
        }
    }
    
    /**
     * Calculate delivery performance metrics
     */
    private function calculateDeliveryPerformance($vendorId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN delivery_status = 'Delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN actual_delivery_date <= scheduled_delivery_date THEN 1 END) as on_time_deliveries,
                    AVG(CASE 
                        WHEN actual_delivery_date IS NOT NULL AND scheduled_delivery_date IS NOT NULL 
                        THEN DATEDIFF(actual_delivery_date, scheduled_delivery_date) 
                        ELSE 0 
                    END) as avg_delivery_delay_days,
                    COUNT(CASE WHEN delivery_status = 'Partial' THEN 1 END) as partial_deliveries
                FROM procurement_orders 
                WHERE vendor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $data = $stmt->fetch();
            
            $onTimeRate = $data['total_orders'] > 0 ? 
                ($data['on_time_deliveries'] / $data['total_orders']) * 100 : 0;
            
            $completionRate = $data['total_orders'] > 0 ? 
                ($data['delivered_orders'] / $data['total_orders']) * 100 : 0;
            
            $partialDeliveryRate = $data['total_orders'] > 0 ? 
                ($data['partial_deliveries'] / $data['total_orders']) * 100 : 0;
            
            // Calculate score (0-100)
            $score = 0;
            if ($data['total_orders'] > 0) {
                $score = (
                    ($onTimeRate * 0.4) +                    // 40% weight for on-time delivery
                    ($completionRate * 0.3) +                // 30% weight for completion rate
                    (max(0, 100 - $partialDeliveryRate) * 0.2) + // 20% weight for avoiding partial deliveries
                    (max(0, 100 - abs($data['avg_delivery_delay_days']) * 5) * 0.1) // 10% weight for minimal delays
                );
            }
            
            return [
                'score' => min(100, max(0, $score)),
                'on_time_rate' => $onTimeRate,
                'completion_rate' => $completionRate,
                'avg_delay_days' => $data['avg_delivery_delay_days'],
                'partial_delivery_rate' => $partialDeliveryRate,
                'total_orders' => $data['total_orders']
            ];
            
        } catch (Exception $e) {
            error_log("Calculate delivery performance error: " . $e->getMessage());
            return ['score' => 0, 'error' => 'Failed to calculate delivery performance'];
        }
    }
    
    /**
     * Calculate quality performance metrics
     */
    private function calculateQualityPerformance($vendorId) {
        try {
            // Quality from procurement orders and assets
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN quality_check_notes IS NOT NULL AND quality_check_notes != '' THEN 1 END) as orders_with_quality_notes,
                    AVG(CASE WHEN quality_check_notes LIKE '%excellent%' OR quality_check_notes LIKE '%good%' THEN 1 ELSE 0 END) as positive_quality_rate
                FROM procurement_orders 
                WHERE vendor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $procurementQuality = $stmt->fetch();
            
            // Quality from asset incidents
            $sql = "
                SELECT 
                    COUNT(DISTINCT a.id) as total_assets,
                    COUNT(DISTINCT i.asset_id) as assets_with_incidents,
                    AVG(CASE WHEN i.type = 'damaged' THEN 1 ELSE 0 END) as damage_incident_rate
                FROM assets a
                LEFT JOIN incidents i ON a.id = i.asset_id AND i.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                WHERE a.vendor_id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $assetQuality = $stmt->fetch();
            
            // Calculate quality score
            $qualityScore = 80; // Base score
            
            if ($procurementQuality['total_orders'] > 0) {
                $qualityScore += ($procurementQuality['positive_quality_rate'] * 20); // Up to 20 points for positive feedback
            }
            
            if ($assetQuality['total_assets'] > 0) {
                $incidentRate = $assetQuality['assets_with_incidents'] / $assetQuality['total_assets'];
                $qualityScore -= ($incidentRate * 50); // Deduct up to 50 points for incidents
            }
            
            return [
                'score' => min(100, max(0, $qualityScore)),
                'positive_quality_rate' => $procurementQuality['positive_quality_rate'] * 100,
                'incident_rate' => $assetQuality['total_assets'] > 0 ? 
                    ($assetQuality['assets_with_incidents'] / $assetQuality['total_assets']) * 100 : 0,
                'total_orders' => $procurementQuality['total_orders'],
                'total_assets' => $assetQuality['total_assets']
            ];
            
        } catch (Exception $e) {
            error_log("Calculate quality performance error: " . $e->getMessage());
            return ['score' => 0, 'error' => 'Failed to calculate quality performance'];
        }
    }
    
    /**
     * Calculate cost performance metrics
     */
    private function calculateCostPerformance($vendorId) {
        try {
            // Cost analysis compared to market average
            $sql = "
                SELECT 
                    po.vendor_id,
                    AVG(pi.unit_price) as avg_unit_price,
                    COUNT(*) as total_items,
                    c.name as category_name
                FROM procurement_orders po
                JOIN procurement_items pi ON po.id = pi.procurement_order_id
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    AND pi.category_id IS NOT NULL
                GROUP BY po.vendor_id, pi.category_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $marketData = $stmt->fetchAll(PDO::FETCH_GROUP);
            
            // Get vendor specific pricing
            $sql = "
                SELECT 
                    AVG(pi.unit_price) as avg_unit_price,
                    pi.category_id,
                    COUNT(*) as total_items,
                    c.name as category_name
                FROM procurement_orders po
                JOIN procurement_items pi ON po.id = pi.procurement_order_id
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE po.vendor_id = ? AND po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    AND pi.category_id IS NOT NULL
                GROUP BY pi.category_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $vendorData = $stmt->fetchAll();
            
            $costScore = 75; // Base score
            $competitiveItems = 0;
            $totalComparisons = 0;
            
            foreach ($vendorData as $vendorItem) {
                $categoryId = $vendorItem['category_id'] ?? null;
                
                if (!$categoryId) {
                    continue; // Skip items without category
                }
                
                // Calculate market average for this category
                $categoryPrices = [];
                foreach ($marketData as $vendorGroup) {
                    foreach ($vendorGroup as $marketItem) {
                        if (isset($marketItem['category_id']) && $marketItem['category_id'] == $categoryId) {
                            $categoryPrices[] = $marketItem['avg_unit_price'];
                        }
                    }
                }
                
                if (!empty($categoryPrices)) {
                    $marketAvg = array_sum($categoryPrices) / count($categoryPrices);
                    $vendorPrice = $vendorItem['avg_unit_price'];
                    
                    if ($vendorPrice <= $marketAvg * 1.05) { // Within 5% of market average or better
                        $competitiveItems++;
                    }
                    $totalComparisons++;
                }
            }
            
            if ($totalComparisons > 0) {
                $competitiveRate = $competitiveItems / $totalComparisons;
                $costScore = 50 + ($competitiveRate * 50); // 50-100 range based on competitiveness
            }
            
            return [
                'score' => min(100, max(0, $costScore)),
                'competitive_rate' => $totalComparisons > 0 ? ($competitiveItems / $totalComparisons) * 100 : 0,
                'categories_analyzed' => $totalComparisons,
                'total_items' => array_sum(array_column($vendorData, 'total_items'))
            ];
            
        } catch (Exception $e) {
            error_log("Calculate cost performance error: " . $e->getMessage());
            return ['score' => 0, 'error' => 'Failed to calculate cost performance'];
        }
    }
    
    /**
     * Calculate reliability performance metrics
     */
    private function calculateReliabilityPerformance($vendorId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN status = 'Delivered' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN status = 'Canceled' THEN 1 END) as canceled_orders,
                    AVG(DATEDIFF(COALESCE(delivered_at, NOW()), created_at)) as avg_fulfillment_time,
                    STDDEV(DATEDIFF(COALESCE(delivered_at, NOW()), created_at)) as fulfillment_consistency
                FROM procurement_orders 
                WHERE vendor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $data = $stmt->fetch();
            
            $completionRate = $data['total_orders'] > 0 ? 
                ($data['completed_orders'] / $data['total_orders']) * 100 : 0;
            
            $cancellationRate = $data['total_orders'] > 0 ? 
                ($data['canceled_orders'] / $data['total_orders']) * 100 : 0;
            
            // Calculate reliability score
            $score = 0;
            if ($data['total_orders'] > 0) {
                $score = (
                    ($completionRate * 0.5) +                    // 50% weight for completion rate
                    (max(0, 100 - $cancellationRate) * 0.3) +    // 30% weight for low cancellation rate
                    (max(0, 100 - ($data['avg_fulfillment_time'] / 30) * 100) * 0.2) // 20% weight for reasonable fulfillment time
                );
            }
            
            return [
                'score' => min(100, max(0, $score)),
                'completion_rate' => $completionRate,
                'cancellation_rate' => $cancellationRate,
                'avg_fulfillment_days' => $data['avg_fulfillment_time'],
                'fulfillment_consistency' => $data['fulfillment_consistency'],
                'total_orders' => $data['total_orders']
            ];
            
        } catch (Exception $e) {
            error_log("Calculate reliability performance error: " . $e->getMessage());
            return ['score' => 0, 'error' => 'Failed to calculate reliability performance'];
        }
    }
    
    /**
     * Calculate financial performance metrics
     */
    private function calculateFinancialPerformance($vendorId) {
        try {
            // Get vendor payment terms and financial history
            $sql = "
                SELECT 
                    v.payment_terms_id,
                    pt.days as payment_days,
                    pt.term_name,
                    COUNT(po.id) as total_orders,
                    SUM(po.net_total) as total_value,
                    AVG(po.net_total) as avg_order_value
                FROM vendors v
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                LEFT JOIN procurement_orders po ON v.id = po.vendor_id 
                    AND po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                WHERE v.id = ?
                GROUP BY v.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $data = $stmt->fetch();
            
            $score = 70; // Base score
            
            // Bonus for favorable payment terms
            if ($data['payment_days']) {
                if ($data['payment_days'] >= 30) {
                    $score += 15; // Good payment terms
                } elseif ($data['payment_days'] >= 15) {
                    $score += 10; // Reasonable payment terms
                } else {
                    $score += 5; // Short payment terms
                }
            }
            
            // Bonus for transaction volume
            if ($data['total_value'] > 0) {
                if ($data['total_value'] > 500000) {
                    $score += 15; // High value vendor
                } elseif ($data['total_value'] > 100000) {
                    $score += 10; // Medium value vendor
                } else {
                    $score += 5; // Regular vendor
                }
            }
            
            return [
                'score' => min(100, max(0, $score)),
                'payment_terms' => $data['term_name'],
                'payment_days' => $data['payment_days'],
                'total_value' => $data['total_value'],
                'avg_order_value' => $data['avg_order_value'],
                'total_orders' => $data['total_orders']
            ];
            
        } catch (Exception $e) {
            error_log("Calculate financial performance error: " . $e->getMessage());
            return ['score' => 0, 'error' => 'Failed to calculate financial performance'];
        }
    }
    
    /**
     * Get performance grade based on score
     */
    private function getPerformanceGrade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 40) return 'D';
        return 'F';
    }
    
    /**
     * Calculate vendor risk assessment
     */
    public function calculateVendorRiskScore($vendorId) {
        try {
            $riskFactors = [];
            $totalRisk = 0;
            
            // 1. Delivery Risk (30% weight)
            $deliveryRisk = $this->assessDeliveryRisk($vendorId);
            $riskFactors['delivery'] = $deliveryRisk;
            $totalRisk += $deliveryRisk['score'] * 0.30;
            
            // 2. Financial Risk (25% weight)
            $financialRisk = $this->assessFinancialRisk($vendorId);
            $riskFactors['financial'] = $financialRisk;
            $totalRisk += $financialRisk['score'] * 0.25;
            
            // 3. Quality Risk (20% weight)
            $qualityRisk = $this->assessQualityRisk($vendorId);
            $riskFactors['quality'] = $qualityRisk;
            $totalRisk += $qualityRisk['score'] * 0.20;
            
            // 4. Dependency Risk (15% weight)
            $dependencyRisk = $this->assessDependencyRisk($vendorId);
            $riskFactors['dependency'] = $dependencyRisk;
            $totalRisk += $dependencyRisk['score'] * 0.15;
            
            // 5. Operational Risk (10% weight)
            $operationalRisk = $this->assessOperationalRisk($vendorId);
            $riskFactors['operational'] = $operationalRisk;
            $totalRisk += $operationalRisk['score'] * 0.10;
            
            $riskLevel = $this->getRiskLevel($totalRisk);
            
            return [
                'vendor_id' => $vendorId,
                'overall_risk_score' => $totalRisk,
                'risk_level' => $riskLevel,
                'risk_factors' => $riskFactors,
                'recommendations' => $this->generateRiskRecommendations($riskFactors, $totalRisk),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Calculate vendor risk score error: " . $e->getMessage());
            return [
                'vendor_id' => $vendorId,
                'overall_risk_score' => 50,
                'risk_level' => 'Unknown',
                'error' => 'Failed to calculate risk score'
            ];
        }
    }
    
    /**
     * Assess delivery risk
     */
    private function assessDeliveryRisk($vendorId) {
        try {
            $deliveryPerf = $this->calculateDeliveryPerformance($vendorId);
            
            $riskScore = 0;
            
            // High risk if low on-time delivery rate
            if ($deliveryPerf['on_time_rate'] < 70) {
                $riskScore += 30;
            } elseif ($deliveryPerf['on_time_rate'] < 85) {
                $riskScore += 15;
            }
            
            // High risk if frequent partial deliveries
            if ($deliveryPerf['partial_delivery_rate'] > 20) {
                $riskScore += 25;
            } elseif ($deliveryPerf['partial_delivery_rate'] > 10) {
                $riskScore += 10;
            }
            
            // Risk based on average delays
            if ($deliveryPerf['avg_delay_days'] > 7) {
                $riskScore += 20;
            } elseif ($deliveryPerf['avg_delay_days'] > 3) {
                $riskScore += 10;
            }
            
            return [
                'score' => min(100, $riskScore),
                'level' => $this->getRiskLevel($riskScore),
                'factors' => [
                    'on_time_rate' => $deliveryPerf['on_time_rate'],
                    'partial_delivery_rate' => $deliveryPerf['partial_delivery_rate'],
                    'avg_delay_days' => $deliveryPerf['avg_delay_days']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Assess delivery risk error: " . $e->getMessage());
            return ['score' => 50, 'level' => 'Medium', 'error' => 'Failed to assess delivery risk'];
        }
    }
    
    /**
     * Assess financial risk
     */
    private function assessFinancialRisk($vendorId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(net_total) as total_value,
                    AVG(net_total) as avg_order_value,
                    STDDEV(net_total) as value_volatility,
                    v.payment_terms_id,
                    pt.days as payment_days
                FROM procurement_orders po
                JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                WHERE po.vendor_id = ? AND po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY po.vendor_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $data = $stmt->fetch();
            
            $riskScore = 0;
            
            // Risk based on transaction volume
            if ($data && $data['total_value'] > 0) {
                if ($data['total_value'] > 1000000) {
                    $riskScore += 20; // High exposure risk
                } elseif ($data['total_value'] > 500000) {
                    $riskScore += 10; // Medium exposure risk
                }
                
                // Risk based on value volatility
                if ($data['value_volatility'] > $data['avg_order_value'] * 0.5) {
                    $riskScore += 15; // High volatility risk
                }
                
                // Risk based on payment terms
                if ($data['payment_days'] && $data['payment_days'] < 15) {
                    $riskScore += 10; // Cash flow risk
                }
            }
            
            return [
                'score' => min(100, $riskScore),
                'level' => $this->getRiskLevel($riskScore),
                'factors' => [
                    'total_value' => $data['total_value'] ?? 0,
                    'value_volatility' => $data['value_volatility'] ?? 0,
                    'payment_days' => $data['payment_days'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Assess financial risk error: " . $e->getMessage());
            return ['score' => 25, 'level' => 'Low', 'error' => 'Failed to assess financial risk'];
        }
    }
    
    /**
     * Assess quality risk
     */
    private function assessQualityRisk($vendorId) {
        try {
            $qualityPerf = $this->calculateQualityPerformance($vendorId);
            
            $riskScore = 0;
            
            // Risk based on incident rate
            if ($qualityPerf['incident_rate'] > 15) {
                $riskScore += 40;
            } elseif ($qualityPerf['incident_rate'] > 8) {
                $riskScore += 20;
            } elseif ($qualityPerf['incident_rate'] > 3) {
                $riskScore += 10;
            }
            
            // Risk based on quality feedback
            if ($qualityPerf['positive_quality_rate'] < 60) {
                $riskScore += 30;
            } elseif ($qualityPerf['positive_quality_rate'] < 80) {
                $riskScore += 15;
            }
            
            return [
                'score' => min(100, $riskScore),
                'level' => $this->getRiskLevel($riskScore),
                'factors' => [
                    'incident_rate' => $qualityPerf['incident_rate'],
                    'positive_quality_rate' => $qualityPerf['positive_quality_rate']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Assess quality risk error: " . $e->getMessage());
            return ['score' => 25, 'level' => 'Low', 'error' => 'Failed to assess quality risk'];
        }
    }
    
    /**
     * Assess dependency risk
     */
    private function assessDependencyRisk($vendorId) {
        try {
            // Check vendor dependency based on procurement volume and category concentration
            $sql = "
                SELECT 
                    SUM(po.net_total) as vendor_total,
                    (SELECT SUM(net_total) FROM procurement_orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)) as system_total,
                    COUNT(DISTINCT pi.category_id) as categories_served,
                    COUNT(DISTINCT po.project_id) as projects_served
                FROM procurement_orders po
                JOIN procurement_items pi ON po.id = pi.procurement_order_id
                WHERE po.vendor_id = ? AND po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $data = $stmt->fetch();
            
            $riskScore = 0;
            
            if ($data && $data['system_total'] > 0) {
                $dependencyRate = ($data['vendor_total'] / $data['system_total']) * 100;
                
                // High dependency risk
                if ($dependencyRate > 25) {
                    $riskScore += 40;
                } elseif ($dependencyRate > 15) {
                    $riskScore += 25;
                } elseif ($dependencyRate > 10) {
                    $riskScore += 15;
                }
                
                // Risk based on category concentration
                if ($data['categories_served'] <= 2) {
                    $riskScore += 20; // High concentration risk
                } elseif ($data['categories_served'] <= 4) {
                    $riskScore += 10; // Medium concentration risk
                }
                
                // Risk based on project concentration
                if ($data['projects_served'] <= 2) {
                    $riskScore += 15; // High project concentration
                }
            }
            
            return [
                'score' => min(100, $riskScore),
                'level' => $this->getRiskLevel($riskScore),
                'factors' => [
                    'dependency_rate' => $data && $data['system_total'] > 0 ? 
                        ($data['vendor_total'] / $data['system_total']) * 100 : 0,
                    'categories_served' => $data['categories_served'] ?? 0,
                    'projects_served' => $data['projects_served'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Assess dependency risk error: " . $e->getMessage());
            return ['score' => 25, 'level' => 'Low', 'error' => 'Failed to assess dependency risk'];
        }
    }
    
    /**
     * Assess operational risk
     */
    private function assessOperationalRisk($vendorId) {
        try {
            // Check for operational indicators like communication, responsiveness, etc.
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN notes IS NOT NULL AND notes != '' THEN 1 END) as orders_with_notes,
                    AVG(DATEDIFF(updated_at, created_at)) as avg_response_time
                FROM procurement_orders 
                WHERE vendor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $data = $stmt->fetch();
            
            $riskScore = 0;
            
            if ($data && $data['total_orders'] > 0) {
                // Risk based on communication (orders requiring notes)
                $noteRate = ($data['orders_with_notes'] / $data['total_orders']) * 100;
                if ($noteRate > 30) {
                    $riskScore += 20; // High communication issues
                } elseif ($noteRate > 15) {
                    $riskScore += 10; // Some communication issues
                }
                
                // Risk based on response time
                if ($data['avg_response_time'] > 5) {
                    $riskScore += 15; // Slow response
                } elseif ($data['avg_response_time'] > 3) {
                    $riskScore += 8; // Moderate response time
                }
            }
            
            return [
                'score' => min(100, $riskScore),
                'level' => $this->getRiskLevel($riskScore),
                'factors' => [
                    'communication_issues_rate' => $data && $data['total_orders'] > 0 ? 
                        ($data['orders_with_notes'] / $data['total_orders']) * 100 : 0,
                    'avg_response_days' => $data['avg_response_time'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Assess operational risk error: " . $e->getMessage());
            return ['score' => 15, 'level' => 'Low', 'error' => 'Failed to assess operational risk'];
        }
    }
    
    /**
     * Get risk level based on score
     */
    private function getRiskLevel($score) {
        if ($score >= 75) return 'Critical';
        if ($score >= 50) return 'High';
        if ($score >= 30) return 'Medium';
        if ($score >= 15) return 'Low';
        return 'Minimal';
    }
    
    /**
     * Generate risk recommendations
     */
    private function generateRiskRecommendations($riskFactors, $totalRisk) {
        $recommendations = [];
        
        if ($totalRisk >= 75) {
            $recommendations[] = 'CRITICAL: Consider immediate vendor review and potential replacement';
        } elseif ($totalRisk >= 50) {
            $recommendations[] = 'HIGH RISK: Implement additional monitoring and backup vendor identification';
        }
        
        // Specific recommendations based on risk factors
        foreach ($riskFactors as $category => $factor) {
            if ($factor['score'] >= 40) {
                switch ($category) {
                    case 'delivery':
                        $recommendations[] = 'Delivery Risk: Establish stricter delivery SLAs and penalty clauses';
                        break;
                    case 'financial':
                        $recommendations[] = 'Financial Risk: Review credit terms and consider deposit requirements';
                        break;
                    case 'quality':
                        $recommendations[] = 'Quality Risk: Implement enhanced quality control and inspection processes';
                        break;
                    case 'dependency':
                        $recommendations[] = 'Dependency Risk: Diversify vendor portfolio and develop alternative suppliers';
                        break;
                    case 'operational':
                        $recommendations[] = 'Operational Risk: Improve communication protocols and response time requirements';
                        break;
                }
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Low risk vendor - continue current monitoring practices';
        }
        
        return $recommendations;
    }
    
    /**
     * Get vendor performance comparison data
     */
    public function getVendorComparisonData($vendorIds, $timeframe = '12 MONTH') {
        try {
            $placeholders = str_repeat('?,', count($vendorIds) - 1) . '?';
            
            $sql = "
                SELECT 
                    v.id,
                    v.name,
                    v.rating,
                    v.is_preferred,
                    COUNT(po.id) as total_orders,
                    SUM(po.net_total) as total_value,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_deliveries,
                    AVG(CASE 
                        WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date) 
                        ELSE 0 
                    END) as avg_delivery_delay,
                    COUNT(DISTINCT pi.category_id) as categories_served
                FROM vendors v
                LEFT JOIN procurement_orders po ON v.id = po.vendor_id 
                    AND po.created_at >= DATE_SUB(NOW(), INTERVAL {$timeframe})
                LEFT JOIN procurement_items pi ON po.id = pi.procurement_order_id
                WHERE v.id IN ({$placeholders})
                GROUP BY v.id
                ORDER BY total_value DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($vendorIds);
            $data = $stmt->fetchAll();
            
            // Calculate additional metrics for each vendor
            foreach ($data as &$vendor) {
                $vendor['on_time_rate'] = $vendor['total_orders'] > 0 ? 
                    ($vendor['on_time_deliveries'] / $vendor['total_orders']) * 100 : 0;
                
                $vendor['completion_rate'] = $vendor['total_orders'] > 0 ? 
                    ($vendor['delivered_orders'] / $vendor['total_orders']) * 100 : 0;
                
                // Get performance score
                $performance = $this->calculateVendorPerformanceScore($vendor['id']);
                $vendor['performance_score'] = $performance['overall_score'];
                $vendor['performance_grade'] = $performance['grade'];
                
                // Get risk score
                $risk = $this->calculateVendorRiskScore($vendor['id']);
                $vendor['risk_score'] = $risk['overall_risk_score'];
                $vendor['risk_level'] = $risk['risk_level'];
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Get vendor comparison data error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get intelligent vendor recommendations for procurement
     */
    public function getVendorRecommendations($categoryId = null, $projectId = null, $budgetRange = null) {
        try {
            $conditions = ['v.id IS NOT NULL'];
            $params = [];
            
            // Filter by category if specified
            if ($categoryId) {
                $conditions[] = 'EXISTS (SELECT 1 FROM vendor_category_assignments vca WHERE vca.vendor_id = v.id AND vca.category_id = ?)';
                $params[] = $categoryId;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            
            $sql = "
                SELECT 
                    v.id,
                    v.name,
                    v.rating,
                    v.is_preferred,
                    v.contact_person,
                    v.email,
                    v.phone,
                    COUNT(po.id) as total_orders,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_deliveries,
                    AVG(CASE 
                        WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date) 
                        ELSE 0 
                    END) as avg_delivery_delay
                FROM vendors v
                LEFT JOIN procurement_orders po ON v.id = po.vendor_id 
                    AND po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                {$whereClause}
                GROUP BY v.id
                HAVING COUNT(po.id) > 0 OR v.is_preferred = 1
                ORDER BY v.is_preferred DESC, total_orders DESC
                LIMIT 10
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $vendors = $stmt->fetchAll();
            
            // Score and rank vendors
            foreach ($vendors as &$vendor) {
                $recommendationScore = 0;
                
                // Preferred vendor bonus
                if ($vendor['is_preferred']) {
                    $recommendationScore += 25;
                }
                
                // Experience score (based on total orders)
                if ($vendor['total_orders'] > 20) {
                    $recommendationScore += 20;
                } elseif ($vendor['total_orders'] > 10) {
                    $recommendationScore += 15;
                } elseif ($vendor['total_orders'] > 5) {
                    $recommendationScore += 10;
                }
                
                // Performance score
                if ($vendor['total_orders'] > 0) {
                    $onTimeRate = ($vendor['on_time_deliveries'] / $vendor['total_orders']) * 100;
                    $completionRate = ($vendor['delivered_orders'] / $vendor['total_orders']) * 100;
                    
                    $recommendationScore += ($onTimeRate * 0.3);
                    $recommendationScore += ($completionRate * 0.2);
                    
                    // Delivery delay penalty
                    if ($vendor['avg_delivery_delay'] > 0) {
                        $recommendationScore -= min(20, $vendor['avg_delivery_delay'] * 2);
                    }
                }
                
                // Rating bonus
                if ($vendor['rating']) {
                    $recommendationScore += ($vendor['rating'] * 4);
                }
                
                $vendor['recommendation_score'] = max(0, min(100, $recommendationScore));
                $vendor['on_time_rate'] = $vendor['total_orders'] > 0 ? 
                    ($vendor['on_time_deliveries'] / $vendor['total_orders']) * 100 : 0;
                $vendor['completion_rate'] = $vendor['total_orders'] > 0 ? 
                    ($vendor['delivered_orders'] / $vendor['total_orders']) * 100 : 0;
            }
            
            // Sort by recommendation score
            usort($vendors, function($a, $b) {
                return $b['recommendation_score'] <=> $a['recommendation_score'];
            });
            
            return $vendors;
            
        } catch (Exception $e) {
            error_log("Get vendor recommendations error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor trend analysis
     */
    public function getVendorTrendAnalysis($vendorId, $months = 12) {
        try {
            $sql = "
                SELECT 
                    DATE_FORMAT(po.created_at, '%Y-%m') as month,
                    COUNT(po.id) as order_count,
                    SUM(po.net_total) as total_value,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as delivered_count,
                    COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_count,
                    AVG(CASE 
                        WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date) 
                        ELSE 0 
                    END) as avg_delay
                FROM procurement_orders po
                WHERE po.vendor_id = ? 
                    AND po.created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(po.created_at, '%Y-%m')
                ORDER BY month ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId, $months]);
            $trends = $stmt->fetchAll();
            
            // Calculate trend metrics
            foreach ($trends as &$trend) {
                $trend['on_time_rate'] = $trend['order_count'] > 0 ? 
                    ($trend['on_time_count'] / $trend['order_count']) * 100 : 0;
                $trend['completion_rate'] = $trend['order_count'] > 0 ? 
                    ($trend['delivered_count'] / $trend['order_count']) * 100 : 0;
            }
            
            return $trends;
            
        } catch (Exception $e) {
            error_log("Get vendor trend analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top performing vendors based on performance scores
     * @param int $limit Number of vendors to return
     * @return array
     */
    public function getTopPerformingVendors($limit = 10) {
        try {
            $sql = "
                SELECT 
                    v.id,
                    v.name,
                    v.contact_person,
                    v.email,
                    v.phone,
                    v.is_preferred,
                    v.rating,
                    COUNT(po.id) as total_orders,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as completed_orders,
                    (COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) * 100.0 / NULLIF(COUNT(po.id), 0)) as on_time_rate
                FROM vendors v
                LEFT JOIN procurement_orders po ON v.id = po.vendor_id
                WHERE 1=1
                GROUP BY v.id
                HAVING COUNT(po.id) > 0
                ORDER BY 
                    (COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) * 0.4 + 
                     (COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) * 100.0 / NULLIF(COUNT(po.id), 0)) * 0.3 + 
                     (v.rating * 20) * 0.3) DESC,
                    COUNT(po.id) DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            $vendors = $stmt->fetchAll();
            
            // Calculate performance scores and grades for each vendor
            foreach ($vendors as &$vendor) {
                $vendor['performance_score'] = $this->calculateVendorPerformanceScore($vendor['id']);
                $vendor['performance_grade'] = $this->getPerformanceGrade($vendor['performance_score']);
            }
            
            return $vendors;
            
        } catch (Exception $e) {
            error_log("Get top performing vendors error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get risk level distribution summary
     * @return array
     */
    public function getRiskSummary() {
        try {
            $sql = "
                SELECT 
                    v.id,
                    v.name,
                    COUNT(po.id) as total_orders,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as completed_orders,
                    (COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) * 100.0 / NULLIF(COUNT(po.id), 0)) as on_time_rate,
                    AVG(po.net_total) as avg_order_value,
                    v.rating
                FROM vendors v
                LEFT JOIN procurement_orders po ON v.id = po.vendor_id
                WHERE 1=1
                GROUP BY v.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $vendors = $stmt->fetchAll();
            
            $riskSummary = [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'minimal' => 0
            ];
            
            foreach ($vendors as $vendor) {
                $riskScore = $this->calculateVendorRiskScore($vendor['id']);
                $riskLevel = $this->getRiskLevel($riskScore);
                
                switch ($riskLevel) {
                    case 'Critical':
                        $riskSummary['critical']++;
                        break;
                    case 'High':
                        $riskSummary['high']++;
                        break;
                    case 'Medium':
                        $riskSummary['medium']++;
                        break;
                    case 'Low':
                        $riskSummary['low']++;
                        break;
                    case 'Minimal':
                        $riskSummary['minimal']++;
                        break;
                }
            }
            
            return $riskSummary;
            
        } catch (Exception $e) {
            error_log("Get risk summary error: " . $e->getMessage());
            return [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'minimal' => 0
            ];
        }
    }
    
}
?>