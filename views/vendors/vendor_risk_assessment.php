<?php
/**
 * ConstructLink™ Vendor Risk Assessment
 * Comprehensive risk analysis and mitigation strategies for vendors
 */

// Include main layout header
include APP_ROOT . '/views/layouts/main.php';
?>

<div class="container-fluid mt-4">
    <!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

                <div class="btn-group" role="group">
                    <a href="?route=vendors/intelligenceDashboard" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                    <?php if ($selectedVendor): ?>
                        <a href="?route=vendors/riskAssessment" class="btn btn-outline-info">
                            <i class="fas fa-list mr-1"></i> All Vendors
                        </a>
                        <a href="?route=vendors/performanceAnalysis&id=<?= $selectedVendor['id'] ?>" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line mr-1"></i> Performance Analysis
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-warning" onclick="exportRiskReport()">
                            <i class="fas fa-download mr-1"></i> Export Risk Report
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($riskAssessments)): ?>
        <!-- No Risk Data -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shield-alt fa-4x text-gray-300 mb-4"></i>
                        <h4 class="text-gray-600">No Risk Assessment Data Available</h4>
                        <p class="text-muted mb-4">
                            Risk assessments require sufficient vendor interaction data. 
                            Please ensure vendors have procurement history to generate meaningful risk analysis.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php if (!$selectedVendor): ?>
            <!-- Risk Overview Summary -->
            <div class="row mb-4">
                <?php 
                $riskCounts = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0, 'Minimal' => 0];
                foreach ($riskAssessments as $assessment) {
                    $riskCounts[$assessment['risk_level']]++;
                }
                ?>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Critical Risk</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $riskCounts['Critical'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-skull-crossbones fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">High Risk</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $riskCounts['High'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Medium Risk</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $riskCounts['Medium'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Low Risk</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $riskCounts['Low'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Minimal Risk</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $riskCounts['Minimal'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Vendors</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($riskAssessments) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Risk Assessment Table/Details -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?= $selectedVendor ? 'Detailed Risk Analysis' : 'Vendor Risk Assessment Overview' ?>
                        </h6>
                        <?php if (!$selectedVendor): ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-danger active" onclick="filterByRisk('all')">All</button>
                                <button class="btn btn-outline-danger" onclick="filterByRisk('Critical')">Critical</button>
                                <button class="btn btn-outline-warning" onclick="filterByRisk('High')">High</button>
                                <button class="btn btn-outline-info" onclick="filterByRisk('Medium')">Medium</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($selectedVendor): ?>
                            <!-- Single Vendor Detailed Analysis -->
                            <?php $assessment = $riskAssessments[0]; ?>
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="risk-gauge mb-3">
                                            <canvas id="riskGauge" width="200" height="200"></canvas>
                                        </div>
                                        <h4 class="font-weight-bold text-<?= getRiskColor($assessment['overall_risk_score']) ?>">
                                            <?= htmlspecialchars($assessment['risk_level']) ?> Risk
                                        </h4>
                                        <p class="text-muted">Overall Score: <?= number_format($assessment['overall_risk_score'], 1) ?>%</p>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h6 class="font-weight-bold mb-3">Risk Factors Breakdown</h6>
                                    <?php foreach ($assessment['risk_factors'] as $factor => $data): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-capitalize font-weight-bold"><?= htmlspecialchars($factor) ?> Risk</span>
                                                <span class="badge badge-<?= getRiskColor($data['score']) ?>">
                                                    <?= htmlspecialchars($data['level']) ?> (<?= number_format($data['score'], 1) ?>%)
                                                </span>
                                            </div>
                                            <div class="progress mb-2" style="height: 8px;">
                                                <div class="progress-bar bg-<?= getRiskColor($data['score']) ?>" 
                                                     style="width: <?= $data['score'] ?>%"></div>
                                            </div>
                                            <?php if (isset($data['factors']) && !empty($data['factors'])): ?>
                                                <div class="row">
                                                    <?php foreach ($data['factors'] as $key => $value): ?>
                                                        <div class="col-sm-6">
                                                            <small class="text-muted text-capitalize">
                                                                <?= str_replace('_', ' ', $key) ?>: 
                                                                <strong><?= is_numeric($value) ? number_format($value, 1) : htmlspecialchars($value) ?></strong>
                                                            </small>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Recommendations -->
                            <?php if (!empty($assessment['recommendations'])): ?>
                                <div class="mt-4">
                                    <h6 class="font-weight-bold mb-3">Risk Mitigation Recommendations</h6>
                                    <div class="row">
                                        <?php foreach ($assessment['recommendations'] as $index => $recommendation): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="alert alert-<?= getRecommendationAlertType($recommendation) ?> mb-2">
                                                    <div class="d-flex align-items-start">
                                                        <i class="fas fa-lightbulb mr-2 mt-1"></i>
                                                        <div>
                                                            <small class="font-weight-bold">Recommendation <?= $index + 1 ?></small>
                                                            <p class="mb-0 text-sm"><?= htmlspecialchars($recommendation) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- All Vendors Risk Overview Table -->
                            <div class="table-responsive">
                                <table class="table table-hover" id="riskAssessmentTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Vendor</th>
                                            <th>Overall Risk</th>
                                            <th>Delivery Risk</th>
                                            <th>Financial Risk</th>
                                            <th>Quality Risk</th>
                                            <th>Dependency Risk</th>
                                            <th>Operational Risk</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($riskAssessments as $assessment): ?>
                                            <tr data-risk-level="<?= htmlspecialchars($assessment['risk_level']) ?>">
                                                <td>
                                                    <strong><?= htmlspecialchars($assessment['vendor_name'] ?? 'Unknown') ?></strong>
                                                    <div class="text-xs text-muted">ID: <?= $assessment['vendor_id'] ?></div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 mr-2" style="height: 8px; width: 60px;">
                                                            <div class="progress-bar bg-<?= getRiskColor($assessment['overall_risk_score']) ?>" 
                                                                 style="width: <?= 100 - $assessment['overall_risk_score'] ?>%"></div>
                                                        </div>
                                                        <span class="badge badge-<?= getRiskColor($assessment['overall_risk_score']) ?>">
                                                            <?= htmlspecialchars($assessment['risk_level']) ?>
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-muted mt-1">
                                                        <?= number_format($assessment['overall_risk_score'], 1) ?>%
                                                    </div>
                                                </td>
                                                <?php foreach (['delivery', 'financial', 'quality', 'dependency', 'operational'] as $factor): ?>
                                                    <td class="text-center">
                                                        <?php if (isset($assessment['risk_factors'][$factor])): ?>
                                                            <span class="badge badge-<?= getRiskColor($assessment['risk_factors'][$factor]['score']) ?>">
                                                                <?= htmlspecialchars($assessment['risk_factors'][$factor]['level']) ?>
                                                            </span>
                                                            <div class="text-xs text-muted">
                                                                <?= number_format($assessment['risk_factors'][$factor]['score'], 1) ?>%
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?route=vendors/riskAssessment&id=<?= $assessment['vendor_id'] ?>" 
                                                           class="btn btn-outline-warning btn-sm" title="Detailed Assessment">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </a>
                                                        <a href="?route=vendors/performanceAnalysis&id=<?= $assessment['vendor_id'] ?>" 
                                                           class="btn btn-outline-primary btn-sm" title="Performance Analysis">
                                                            <i class="fas fa-chart-line"></i>
                                                        </a>
                                                        <a href="?route=vendors/view&id=<?= $assessment['vendor_id'] ?>" 
                                                           class="btn btn-outline-secondary btn-sm" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$selectedVendor): ?>
            <!-- Risk Trends and Insights -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Risk Distribution Analysis</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="riskDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Priority Actions</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $criticalVendors = array_filter($riskAssessments, function($a) { return $a['risk_level'] === 'Critical'; });
                            $highRiskVendors = array_filter($riskAssessments, function($a) { return $a['risk_level'] === 'High'; });
                            ?>
                            
                            <?php if (!empty($criticalVendors)): ?>
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-skull-crossbones mr-2"></i>Critical Risk Alert
                                    </h6>
                                    <p class="mb-2">
                                        <strong><?= count($criticalVendors) ?></strong> vendor(s) require immediate attention.
                                    </p>
                                    <hr>
                                    <ul class="mb-0">
                                        <?php foreach (array_slice($criticalVendors, 0, 3) as $vendor): ?>
                                            <li class="text-sm">
                                                <a href="?route=vendors/riskAssessment&id=<?= $vendor['vendor_id'] ?>" class="text-white">
                                                    <?= htmlspecialchars($vendor['vendor_name'] ?? 'Unknown') ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($highRiskVendors)): ?>
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>High Risk Vendors
                                    </h6>
                                    <p class="mb-2">
                                        <strong><?= count($highRiskVendors) ?></strong> vendor(s) need enhanced monitoring.
                                    </p>
                                    <hr>
                                    <ul class="mb-0">
                                        <?php foreach (array_slice($highRiskVendors, 0, 3) as $vendor): ?>
                                            <li class="text-sm">
                                                <a href="?route=vendors/riskAssessment&id=<?= $vendor['vendor_id'] ?>" class="text-dark">
                                                    <?= htmlspecialchars($vendor['vendor_name'] ?? 'Unknown') ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($criticalVendors) && empty($highRiskVendors)): ?>
                                <div class="alert alert-success">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-shield-alt mr-2"></i>Risk Status: Good
                                    </h6>
                                    <p class="mb-0">
                                        No critical or high-risk vendors detected. Continue regular monitoring practices.
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <h6 class="font-weight-bold">Recommended Actions:</h6>
                                <ul class="text-sm">
                                    <li>Review critical vendors immediately</li>
                                    <li>Establish backup supplier relationships</li>
                                    <li>Implement enhanced monitoring protocols</li>
                                    <li>Consider vendor diversification strategies</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php
// Helper functions for styling
function getRiskColor($score) {
    if ($score >= 75) return 'danger';
    if ($score >= 50) return 'warning';
    if ($score >= 30) return 'info';
    return 'success';
}

function getRecommendationAlertType($recommendation) {
    if (strpos(strtolower($recommendation), 'critical') !== false || strpos(strtolower($recommendation), 'immediate') !== false) {
        return 'danger';
    } elseif (strpos(strtolower($recommendation), 'high') !== false || strpos(strtolower($recommendation), 'enhanced') !== false) {
        return 'warning';
    } elseif (strpos(strtolower($recommendation), 'consider') !== false || strpos(strtolower($recommendation), 'review') !== false) {
        return 'info';
    } else {
        return 'secondary';
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($selectedVendor && !empty($riskAssessments)): ?>
        // Initialize risk gauge for single vendor
        initializeRiskGauge(<?= $riskAssessments[0]['overall_risk_score'] ?>);
    <?php elseif (!$selectedVendor && !empty($riskAssessments)): ?>
        // Initialize risk distribution chart for overview
        initializeRiskDistributionChart();
    <?php endif; ?>
});

<?php if ($selectedVendor): ?>
function initializeRiskGauge(riskScore) {
    const ctx = document.getElementById('riskGauge').getContext('2d');
    
    // Convert risk score to safety score (inverse)
    const safetyScore = 100 - riskScore;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [safetyScore, riskScore],
                backgroundColor: [
                    safetyScore >= 50 ? '#2ecc71' : safetyScore >= 25 ? '#f39c12' : '#e74a3b',
                    '#ecf0f1'
                ],
                borderWidth: 0
            }]
        },
        options: {
            circumference: Math.PI,
            rotation: Math.PI,
            cutoutPercentage: 70,
            tooltips: { enabled: false },
            legend: { display: false },
            animation: {
                animateRotate: true,
                duration: 2000
            }
        }
    });
}
<?php endif; ?>

<?php if (!$selectedVendor): ?>
function initializeRiskDistributionChart() {
    const ctx = document.getElementById('riskDistributionChart').getContext('2d');
    const riskData = <?= json_encode(array_count_values(array_column($riskAssessments, 'risk_level'))) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(riskData),
            datasets: [{
                label: 'Number of Vendors',
                data: Object.values(riskData),
                backgroundColor: [
                    '#e74a3b',  // Critical
                    '#f39c12',  // High
                    '#3498db',  // Medium
                    '#2ecc71',  // Low
                    '#95a5a6'   // Minimal
                ],
                borderColor: [
                    '#c0392b',
                    '#e67e22',
                    '#2980b9',
                    '#27ae60',
                    '#7f8c8d'
                ],
                borderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.yLabel + ' vendor(s)';
                    }
                }
            }
        }
    });
}

function filterByRisk(riskLevel) {
    // Update active button
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    const rows = document.querySelectorAll('#riskAssessmentTable tbody tr');
    
    rows.forEach(row => {
        if (riskLevel === 'all' || row.dataset.riskLevel === riskLevel) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
<?php endif; ?>

function exportRiskReport() {
    window.open('?route=vendors/export&type=risk_assessment', '_blank');
}
</script>

<?php include APP_ROOT . '/views/layouts/main.php'; ?>