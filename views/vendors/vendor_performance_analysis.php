<?php
/**
 * ConstructLink™ Vendor Performance Analysis
 * Detailed performance metrics and insights for individual vendors
 */

// Include main layout header
include APP_ROOT . '/views/layouts/main.php';
?>

<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-chart-line mr-2 text-primary"></i>
                        <?= htmlspecialchars($pageHeader) ?>
                    </h1>
                    <p class="text-muted mb-0">Comprehensive performance metrics and trend analysis</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="?route=vendors/intelligenceDashboard" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                    <a href="?route=vendors/view&id=<?= $vendor['id'] ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye mr-1"></i> View Vendor Details
                    </a>
                    <a href="?route=vendors/riskAssessment&id=<?= $vendor['id'] ?>" class="btn btn-outline-warning">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Risk Assessment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Overall Score
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($performanceData['overall_score'], 1) ?>%
                            </div>
                            <div class="text-xs text-muted">
                                Grade: <span class="badge badge-<?= getGradeBadgeColor($performanceData['grade']) ?>">
                                    <?= htmlspecialchars($performanceData['grade']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-trophy fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Delivery Performance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($performanceData['metrics']['delivery']['score'] ?? 0, 1) ?>%
                            </div>
                            <div class="text-xs text-muted">
                                On-time: <?= number_format($performanceData['metrics']['delivery']['on_time_rate'] ?? 0, 1) ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shipping-fast fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Quality Score
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($performanceData['metrics']['quality']['score'] ?? 0, 1) ?>%
                            </div>
                            <div class="text-xs text-muted">
                                Incident Rate: <?= number_format($performanceData['metrics']['quality']['incident_rate'] ?? 0, 2) ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-gem fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Risk Level
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= htmlspecialchars($riskData['risk_level']) ?>
                            </div>
                            <div class="text-xs text-muted">
                                Score: <?= number_format($riskData['overall_risk_score'], 1) ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Breakdown -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Metrics Breakdown</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($performanceData['metrics'] as $category => $metric): ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-capitalize mb-0"><?= htmlspecialchars($category) ?> Performance</h6>
                                <span class="badge badge-<?= getScoreColor($metric['score']) ?> badge-lg">
                                    <?= number_format($metric['score'], 1) ?>%
                                </span>
                            </div>
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar bg-<?= getScoreColor($metric['score']) ?>" 
                                     style="width: <?= $metric['score'] ?>%"></div>
                            </div>
                            <div class="row">
                                <?php if ($category === 'delivery'): ?>
                                    <div class="col-sm-3">
                                        <small class="text-muted">On-Time Rate</small><br>
                                        <strong><?= number_format($metric['on_time_rate'], 1) ?>%</strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Completion Rate</small><br>
                                        <strong><?= number_format($metric['completion_rate'], 1) ?>%</strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Avg Delay</small><br>
                                        <strong><?= number_format($metric['avg_delay_days'], 1) ?> days</strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Total Orders</small><br>
                                        <strong><?= number_format($metric['total_orders']) ?></strong>
                                    </div>
                                <?php elseif ($category === 'quality'): ?>
                                    <div class="col-sm-4">
                                        <small class="text-muted">Positive Rate</small><br>
                                        <strong><?= number_format($metric['positive_quality_rate'], 1) ?>%</strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <small class="text-muted">Incident Rate</small><br>
                                        <strong><?= number_format($metric['incident_rate'], 2) ?>%</strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <small class="text-muted">Total Assets</small><br>
                                        <strong><?= number_format($metric['total_assets']) ?></strong>
                                    </div>
                                <?php elseif ($category === 'cost'): ?>
                                    <div class="col-sm-4">
                                        <small class="text-muted">Competitive Rate</small><br>
                                        <strong><?= number_format($metric['competitive_rate'], 1) ?>%</strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <small class="text-muted">Categories</small><br>
                                        <strong><?= number_format($metric['categories_analyzed']) ?></strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <small class="text-muted">Items Analyzed</small><br>
                                        <strong><?= number_format($metric['total_items']) ?></strong>
                                    </div>
                                <?php elseif ($category === 'reliability'): ?>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Completion Rate</small><br>
                                        <strong><?= number_format($metric['completion_rate'], 1) ?>%</strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Cancellation Rate</small><br>
                                        <strong><?= number_format($metric['cancellation_rate'], 1) ?>%</strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Fulfillment Time</small><br>
                                        <strong><?= number_format($metric['avg_fulfillment_days'], 1) ?> days</strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Total Orders</small><br>
                                        <strong><?= number_format($metric['total_orders']) ?></strong>
                                    </div>
                                <?php elseif ($category === 'financial'): ?>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Payment Terms</small><br>
                                        <strong><?= htmlspecialchars($metric['payment_terms'] ?? 'N/A') ?></strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <small class="text-muted">Payment Days</small><br>
                                        <strong><?= number_format($metric['payment_days'] ?? 0) ?></strong>
                                    </div>
                                    <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                        <div class="col-sm-3">
                                            <small class="text-muted">Total Value</small><br>
                                            <strong>₱<?= number_format($metric['total_value'] ?? 0, 2) ?></strong>
                                        </div>
                                        <div class="col-sm-3">
                                            <small class="text-muted">Avg Order Value</small><br>
                                            <strong>₱<?= number_format($metric['avg_order_value'] ?? 0, 2) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recommendations</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($recommendations)): ?>
                        <?php foreach ($recommendations as $rec): ?>
                            <div class="alert alert-<?= getRecommendationAlertType($rec['type']) ?> mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($rec['category']) ?></strong>
                                        <?php if ($rec['priority'] === 'critical'): ?>
                                            <span class="badge badge-danger ml-2">Critical</span>
                                        <?php elseif ($rec['priority'] === 'high'): ?>
                                            <span class="badge badge-warning ml-2">High</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="mb-0 mt-2"><?= htmlspecialchars($rec['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-lightbulb fa-2x mb-2"></i>
                            <p>No specific recommendations at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Trends -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Trends (12 Months)</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary active" onclick="showTrendChart('orders')">Orders</button>
                        <button class="btn btn-outline-primary" onclick="showTrendChart('value')">Value</button>
                        <button class="btn btn-outline-primary" onclick="showTrendChart('performance')">Performance</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="performanceTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Factors Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Risk Factors Analysis</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($riskData['risk_factors'] as $factor => $data): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 border-left-<?= getRiskColor($data['score']) ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-capitalize mb-0"><?= htmlspecialchars($factor) ?> Risk</h6>
                                            <span class="badge badge-<?= getRiskColor($data['score']) ?>">
                                                <?= htmlspecialchars($data['level']) ?>
                                            </span>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar bg-<?= getRiskColor($data['score']) ?>" 
                                                 style="width: <?= $data['score'] ?>%"></div>
                                        </div>
                                        <div class="text-xs text-muted">
                                            Score: <?= number_format($data['score'], 1) ?>%
                                        </div>
                                        <?php if (isset($data['factors'])): ?>
                                            <hr class="my-2">
                                            <div class="text-xs">
                                                <?php foreach ($data['factors'] as $key => $value): ?>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-capitalize"><?= str_replace('_', ' ', $key) ?>:</span>
                                                        <strong><?= is_numeric($value) ? number_format($value, 1) : htmlspecialchars($value) ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions for styling
function getScoreColor($score) {
    if ($score >= 85) return 'success';
    if ($score >= 70) return 'info';
    if ($score >= 60) return 'warning';
    return 'danger';
}

function getGradeBadgeColor($grade) {
    if (in_array($grade, ['A+', 'A', 'A-'])) return 'success';
    if (in_array($grade, ['B+', 'B', 'B-'])) return 'info';
    if (in_array($grade, ['C+', 'C', 'C-'])) return 'warning';
    return 'danger';
}

function getRiskColor($score) {
    if ($score >= 75) return 'danger';
    if ($score >= 50) return 'warning';
    if ($score >= 30) return 'info';
    return 'success';
}

function getRecommendationAlertType($type) {
    switch ($type) {
        case 'danger': return 'danger';
        case 'warning': return 'warning';
        case 'info': return 'info';
        case 'success': return 'success';
        default: return 'secondary';
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializePerformanceTrendsChart();
});

let currentChart = null;
let trendData = <?= json_encode($trendData) ?>;

function initializePerformanceTrendsChart() {
    showTrendChart('orders');
}

function showTrendChart(type) {
    // Update active button
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    const ctx = document.getElementById('performanceTrendsChart').getContext('2d');
    
    // Destroy existing chart
    if (currentChart) {
        currentChart.destroy();
    }
    
    let datasets = [];
    let yAxisCallback = function(value) { return value; };
    
    switch(type) {
        case 'orders':
            datasets = [{
                label: 'Total Orders',
                data: trendData.map(item => parseInt(item.order_count)),
                borderColor: 'rgba(78, 115, 223, 1)',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true
            }];
            break;
            
        case 'value':
            datasets = [{
                label: 'Order Value (₱)',
                data: trendData.map(item => parseFloat(item.total_value || 0)),
                borderColor: 'rgba(28, 200, 138, 1)',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                fill: true
            }];
            yAxisCallback = function(value) { 
                return '₱' + value.toLocaleString(); 
            };
            break;
            
        case 'performance':
            datasets = [{
                label: 'On-Time Rate (%)',
                data: trendData.map(item => parseFloat(item.on_time_rate)),
                borderColor: 'rgba(54, 185, 204, 1)',
                backgroundColor: 'rgba(54, 185, 204, 0.1)',
                fill: true
            }, {
                label: 'Completion Rate (%)',
                data: trendData.map(item => parseFloat(item.completion_rate)),
                borderColor: 'rgba(246, 194, 62, 1)',
                backgroundColor: 'rgba(246, 194, 62, 0.1)',
                fill: true
            }];
            yAxisCallback = function(value) { 
                return value + '%'; 
            };
            break;
    }
    
    currentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.month),
            datasets: datasets
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        callback: yAxisCallback
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: datasets.length > 1
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
            }
        }
    });
}
</script>

<?php include APP_ROOT . '/views/layouts/main.php'; ?>