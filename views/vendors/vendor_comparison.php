<?php
/**
 * ConstructLink™ Vendor Comparison Tool
 * Side-by-side vendor performance comparison and analysis
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
                    <button class="btn btn-outline-primary" onclick="exportComparison()">
                        <i class="fas fa-download mr-1"></i> Export Comparison
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Selection -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Select Vendors to Compare</h6>
                </div>
                <div class="card-body">
                    <form method="GET" id="comparisonForm">
                        <input type="hidden" name="route" value="vendors/vendorComparison">
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-control select2" multiple="multiple" name="vendor_ids[]" 
                                        data-placeholder="Select vendors to compare (up to 5)" style="width: 100%;">
                                    <?php foreach ($allVendors as $vendor): ?>
                                        <option value="<?= $vendor['id'] ?>" 
                                                <?= in_array($vendor['id'], $vendorIds ?? []) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vendor['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="btn-group btn-block">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search mr-1"></i> Compare Selected
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                                        <i class="fas fa-times mr-1"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($comparisonData)): ?>
        <!-- Comparison Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Vendor</th>
                                        <th>Overall Score</th>
                                        <th>Grade</th>
                                        <th>Risk Level</th>
                                        <th>Total Orders</th>
                                        <th>On-Time Rate</th>
                                        <th>Completion Rate</th>
                                        <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                            <th>Total Value</th>
                                        <?php endif; ?>
                                        <th>Categories</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comparisonData as $vendor): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($vendor['is_preferred']): ?>
                                                        <i class="fas fa-star text-warning mr-2" title="Preferred Vendor"></i>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars($vendor['name']) ?></strong>
                                                        <?php if ($vendor['rating']): ?>
                                                            <div class="text-xs text-muted">
                                                                Rating: <?= number_format($vendor['rating'], 1) ?>/5.0
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 mr-2" style="height: 8px; width: 60px;">
                                                        <div class="progress-bar <?= getScoreProgressColor($vendor['performance_score']) ?>" 
                                                             style="width: <?= $vendor['performance_score'] ?>%"></div>
                                                    </div>
                                                    <span class="text-sm font-weight-bold">
                                                        <?= number_format($vendor['performance_score'], 1) ?>%
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= getGradeBadgeColor($vendor['performance_grade']) ?>">
                                                    <?= htmlspecialchars($vendor['performance_grade']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= getRiskBadgeColor($vendor['risk_level']) ?>">
                                                    <?= htmlspecialchars($vendor['risk_level']) ?>
                                                </span>
                                                <div class="text-xs text-muted">
                                                    <?= number_format($vendor['risk_score'], 1) ?>%
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= number_format($vendor['total_orders']) ?></strong>
                                                <div class="text-xs text-muted">
                                                    Avg: ₱<?= number_format($vendor['avg_order_value'], 0) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <div class="h6 mb-0 <?= getPercentageColor($vendor['on_time_rate']) ?>">
                                                        <?= number_format($vendor['on_time_rate'], 1) ?>%
                                                    </div>
                                                    <?php if ($vendor['avg_delivery_delay'] > 0): ?>
                                                        <div class="text-xs text-danger">
                                                            +<?= number_format($vendor['avg_delivery_delay'], 1) ?> days
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <div class="h6 mb-0 <?= getPercentageColor($vendor['completion_rate']) ?>">
                                                        <?= number_format($vendor['completion_rate'], 1) ?>%
                                                    </div>
                                                    <div class="text-xs text-muted">
                                                        <?= number_format($vendor['delivered_orders']) ?>/<?= number_format($vendor['total_orders']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                                <td>
                                                    <strong>₱<?= number_format($vendor['total_value'], 2) ?></strong>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= number_format($vendor['categories_served']) ?> categories
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?route=vendors/performanceAnalysis&id=<?= $vendor['id'] ?>" 
                                                       class="btn btn-outline-primary btn-sm" title="Performance Analysis">
                                                        <i class="fas fa-chart-line"></i>
                                                    </a>
                                                    <a href="?route=vendors/view&id=<?= $vendor['id'] ?>" 
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Metrics Comparison -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Detailed Performance Metrics</h6>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" onclick="showMetricChart('performance')">Performance</button>
                            <button class="btn btn-outline-primary" onclick="showMetricChart('delivery')">Delivery</button>
                            <button class="btn btn-outline-primary" onclick="showMetricChart('risk')">Risk</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="chart-container">
                                    <canvas id="comparisonChart"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div id="metricDetails">
                                    <!-- Details will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Strengths and Weaknesses -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Strengths & Weaknesses Analysis</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($comparisonData as $vendor): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header py-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($vendor['name']) ?></h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h6 class="text-success">
                                                        <i class="fas fa-thumbs-up mr-1"></i> Strengths
                                                    </h6>
                                                    <ul class="list-unstyled">
                                                        <?php if ($vendor['performance_score'] >= 80): ?>
                                                            <li><small><i class="fas fa-check text-success mr-1"></i> High Performance</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['on_time_rate'] >= 85): ?>
                                                            <li><small><i class="fas fa-check text-success mr-1"></i> Reliable Delivery</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['completion_rate'] >= 90): ?>
                                                            <li><small><i class="fas fa-check text-success mr-1"></i> High Completion Rate</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['is_preferred']): ?>
                                                            <li><small><i class="fas fa-check text-success mr-1"></i> Preferred Status</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['categories_served'] >= 3): ?>
                                                            <li><small><i class="fas fa-check text-success mr-1"></i> Multi-category Service</small></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                                <div class="col-6">
                                                    <h6 class="text-warning">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i> Areas for Improvement
                                                    </h6>
                                                    <ul class="list-unstyled">
                                                        <?php if ($vendor['performance_score'] < 70): ?>
                                                            <li><small><i class="fas fa-times text-warning mr-1"></i> Low Performance Score</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['on_time_rate'] < 80): ?>
                                                            <li><small><i class="fas fa-times text-warning mr-1"></i> Delivery Delays</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['completion_rate'] < 85): ?>
                                                            <li><small><i class="fas fa-times text-warning mr-1"></i> Incomplete Orders</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['risk_level'] === 'High' || $vendor['risk_level'] === 'Critical'): ?>
                                                            <li><small><i class="fas fa-times text-warning mr-1"></i> High Risk Level</small></li>
                                                        <?php endif; ?>
                                                        <?php if ($vendor['total_orders'] < 5): ?>
                                                            <li><small><i class="fas fa-times text-warning mr-1"></i> Limited Experience</small></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendation Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Comparison Summary & Recommendations</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-left-success h-100">
                                    <div class="card-body">
                                        <h6 class="text-success">
                                            <i class="fas fa-crown mr-1"></i> Top Performer
                                        </h6>
                                        <?php 
                                        $topPerformer = $comparisonData[0]; // Already sorted by performance
                                        ?>
                                        <h5><?= htmlspecialchars($topPerformer['name']) ?></h5>
                                        <p class="text-sm mb-2">
                                            Score: <?= number_format($topPerformer['performance_score'], 1) ?>% 
                                            (<?= htmlspecialchars($topPerformer['performance_grade']) ?>)
                                        </p>
                                        <small class="text-muted">
                                            Best overall performance with consistent delivery and quality metrics.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-left-info h-100">
                                    <div class="card-body">
                                        <h6 class="text-info">
                                            <i class="fas fa-shipping-fast mr-1"></i> Most Reliable
                                        </h6>
                                        <?php 
                                        $mostReliable = $comparisonData[0];
                                        foreach ($comparisonData as $vendor) {
                                            if ($vendor['on_time_rate'] > $mostReliable['on_time_rate']) {
                                                $mostReliable = $vendor;
                                            }
                                        }
                                        ?>
                                        <h5><?= htmlspecialchars($mostReliable['name']) ?></h5>
                                        <p class="text-sm mb-2">
                                            On-time: <?= number_format($mostReliable['on_time_rate'], 1) ?>%
                                        </p>
                                        <small class="text-muted">
                                            Highest on-time delivery rate with minimal delays.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-left-warning h-100">
                                    <div class="card-body">
                                        <h6 class="text-warning">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Needs Attention
                                        </h6>
                                        <?php 
                                        $needsAttention = end($comparisonData); // Lowest performer
                                        ?>
                                        <h5><?= htmlspecialchars($needsAttention['name']) ?></h5>
                                        <p class="text-sm mb-2">
                                            Score: <?= number_format($needsAttention['performance_score'], 1) ?>%
                                        </p>
                                        <small class="text-muted">
                                            Consider performance improvement discussions or alternative options.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- No Comparison Data -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-balance-scale fa-4x text-gray-300 mb-4"></i>
                        <h4 class="text-gray-600">Select Vendors to Compare</h4>
                        <p class="text-muted mb-4">
                            Choose 2 or more vendors from the dropdown above to see a detailed side-by-side comparison 
                            of their performance metrics, delivery rates, and risk assessments.
                        </p>
                        <div class="text-muted">
                            <small>
                                <i class="fas fa-info-circle mr-1"></i>
                                You can compare up to 5 vendors at once for comprehensive analysis.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Helper functions for styling
function getScoreProgressColor($score) {
    if ($score >= 85) return 'bg-success';
    if ($score >= 70) return 'bg-info';
    if ($score >= 60) return 'bg-warning';
    return 'bg-danger';
}

function getGradeBadgeColor($grade) {
    if (in_array($grade, ['A+', 'A', 'A-'])) return 'success';
    if (in_array($grade, ['B+', 'B', 'B-'])) return 'info';
    if (in_array($grade, ['C+', 'C', 'C-'])) return 'warning';
    return 'danger';
}

function getRiskBadgeColor($riskLevel) {
    switch (strtolower($riskLevel)) {
        case 'critical': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        case 'low': return 'success';
        case 'minimal': return 'secondary';
        default: return 'secondary';
    }
}

function getPercentageColor($percentage) {
    if ($percentage >= 85) return 'text-success';
    if ($percentage >= 70) return 'text-info';
    if ($percentage >= 60) return 'text-warning';
    return 'text-danger';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        maximumSelectionLength: 5
    });
    
    <?php if (!empty($comparisonData)): ?>
        // Initialize comparison chart
        showMetricChart('performance');
    <?php endif; ?>
});

let currentChart = null;
const comparisonData = <?= json_encode($comparisonData ?? []) ?>;

function showMetricChart(type) {
    // Update active button
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    
    // Destroy existing chart
    if (currentChart) {
        currentChart.destroy();
    }
    
    let datasets = [];
    let labels = comparisonData.map(vendor => vendor.name);
    
    switch(type) {
        case 'performance':
            datasets = [{
                label: 'Performance Score (%)',
                data: comparisonData.map(vendor => vendor.performance_score),
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2
            }];
            break;
            
        case 'delivery':
            datasets = [{
                label: 'On-Time Rate (%)',
                data: comparisonData.map(vendor => vendor.on_time_rate),
                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 2
            }, {
                label: 'Completion Rate (%)',
                data: comparisonData.map(vendor => vendor.completion_rate),
                backgroundColor: 'rgba(54, 185, 204, 0.8)',
                borderColor: 'rgba(54, 185, 204, 1)',
                borderWidth: 2
            }];
            break;
            
        case 'risk':
            datasets = [{
                label: 'Risk Score (%)',
                data: comparisonData.map(vendor => vendor.risk_score),
                backgroundColor: 'rgba(231, 74, 59, 0.8)',
                borderColor: 'rgba(231, 74, 59, 1)',
                borderWidth: 2
            }];
            break;
    }
    
    currentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
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
                        maxRotation: 45,
                        minRotation: 0
                    }
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        max: 100,
                        maxTicksLimit: 5,
                        padding: 10,
                        callback: function(value, index, values) {
                            return value + '%';
                        }
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
                callbacks: {
                    label: function(tooltipItem, chart) {
                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                        return datasetLabel + ': ' + tooltipItem.yLabel.toFixed(1) + '%';
                    }
                }
            }
        }
    });
    
    // Update metric details
    updateMetricDetails(type);
}

function updateMetricDetails(type) {
    let content = '<h6 class="font-weight-bold mb-3">Metric Details</h6>';
    
    switch(type) {
        case 'performance':
            content += '<p class="text-sm text-muted mb-3">Overall performance score based on delivery, quality, cost, reliability, and financial metrics.</p>';
            break;
        case 'delivery':
            content += '<p class="text-sm text-muted mb-3">Delivery performance metrics including on-time rates and completion percentages.</p>';
            break;
        case 'risk':
            content += '<p class="text-sm text-muted mb-3">Risk assessment scores considering delivery, financial, quality, dependency, and operational factors.</p>';
            break;
    }
    
    content += '<div class="table-responsive"><table class="table table-sm">';
    content += '<thead><tr><th>Vendor</th><th>Value</th><th>Rank</th></tr></thead><tbody>';
    
    // Sort data for ranking
    let sortedData = [...comparisonData];
    if (type === 'risk') {
        sortedData.sort((a, b) => a.risk_score - b.risk_score); // Lower risk is better
    } else if (type === 'performance') {
        sortedData.sort((a, b) => b.performance_score - a.performance_score);
    } else if (type === 'delivery') {
        sortedData.sort((a, b) => b.on_time_rate - a.on_time_rate);
    }
    
    sortedData.forEach((vendor, index) => {
        let value = '';
        switch(type) {
            case 'performance':
                value = vendor.performance_score.toFixed(1) + '%';
                break;
            case 'delivery':
                value = vendor.on_time_rate.toFixed(1) + '%';
                break;
            case 'risk':
                value = vendor.risk_score.toFixed(1) + '%';
                break;
        }
        
        content += `<tr>
            <td class="text-sm">${vendor.name}</td>
            <td class="text-sm font-weight-bold">${value}</td>
            <td><span class="badge badge-${index === 0 ? 'success' : index === 1 ? 'info' : 'secondary'}">#${index + 1}</span></td>
        </tr>`;
    });
    
    content += '</tbody></table></div>';
    
    document.getElementById('metricDetails').innerHTML = content;
}

function clearSelection() {
    $('.select2').val(null).trigger('change');
}

function exportComparison() {
    if (comparisonData.length === 0) {
        alert('Please select vendors to compare before exporting.');
        return;
    }
    
    // Implement export functionality
    window.open('?route=vendors/export&' + $('#comparisonForm').serialize(), '_blank');
}
</script>

<?php include APP_ROOT . '/views/layouts/main.php'; ?>