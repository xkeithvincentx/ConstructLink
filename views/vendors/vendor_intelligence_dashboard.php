<?php
/**
 * ConstructLink™ Vendor Intelligence Dashboard
 * Advanced analytics and insights for vendor management
 */

// Include main layout header
include APP_ROOT . '/views/layouts/main.php';
?>

<div class="container-fluid mt-4">
    <!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

                <div class="btn-group" role="group">
                    <a href="?route=vendors" class="btn btn-outline-secondary">
                        <i class="fas fa-list mr-1"></i> Vendor List
                    </a>
                    <a href="?route=vendors/vendorComparison" class="btn btn-outline-primary">
                        <i class="fas fa-balance-scale mr-1"></i> Compare Vendors
                    </a>
                    <a href="?route=vendors/riskAssessment" class="btn btn-outline-warning">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Risk Assessment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Overview -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Vendors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($vendorStats['total_vendors'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Preferred Vendors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($vendorStats['preferred_vendors'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
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
                                Active Vendors (30 days)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($vendorStats['active_vendors_30d'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                Average Rating
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($vendorStats['average_rating'] ?? 0, 2) ?>/5.0
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star-half-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Vendors and Risk Summary -->
    <div class="row mb-4">
        <!-- Top Performing Vendors -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top Performing Vendors</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" onclick="refreshTopVendors()">
                                <i class="fas fa-sync mr-2"></i> Refresh Data
                            </a>
                            <a class="dropdown-item" href="?route=vendors/vendorComparison">
                                <i class="fas fa-balance-scale mr-2"></i> Compare All
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($topVendors)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Vendor</th>
                                        <th>Performance Score</th>
                                        <th>Grade</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topVendors as $vendor): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($vendor['is_preferred'] ?? false): ?>
                                                        <i class="fas fa-star text-warning mr-2" title="Preferred Vendor"></i>
                                                    <?php endif; ?>
                                                    <strong><?= htmlspecialchars($vendor['name']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 mr-2" style="height: 8px;">
                                                        <div class="progress-bar <?= getScoreColor($vendor['performance_score']) ?>" 
                                                             style="width: <?= $vendor['performance_score'] ?>%"></div>
                                                    </div>
                                                    <small class="text-muted"><?= number_format($vendor['performance_score'], 1) ?>%</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= getGradeBadgeColor($vendor['performance_grade']) ?>">
                                                    <?= htmlspecialchars($vendor['performance_grade']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= htmlspecialchars($vendor['contact_person'] ?? 'N/A') ?></small>
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
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">No vendor performance data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Risk Summary -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Risk Level Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="riskPieChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Critical
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> High
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Medium
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Low
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-secondary"></i> Minimal
                        </span>
                    </div>
                    <div class="mt-3">
                        <a href="?route=vendors/riskAssessment" class="btn btn-outline-warning btn-sm btn-block">
                            <i class="fas fa-exclamation-triangle mr-1"></i> View Risk Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Procurement Insights and Delivery Trends -->
    <div class="row mb-4">
        <!-- Procurement Insights -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Procurement Insights</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-primary">
                                    <?= number_format($procurementInsights['total_orders'] ?? 0) ?>
                                </div>
                                <div class="text-xs text-muted">Total Orders</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-success">
                                    <?= number_format($procurementInsights['on_time_rate'] ?? 0, 1) ?>%
                                </div>
                                <div class="text-xs text-muted">On-Time Rate</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-info">
                                    <?= number_format($procurementInsights['active_vendors'] ?? 0) ?>
                                </div>
                                <div class="text-xs text-muted">Active Vendors</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-warning">
                                    <?= number_format($procurementInsights['completion_rate'] ?? 0, 1) ?>%
                                </div>
                                <div class="text-xs text-muted">Completion Rate</div>
                            </div>
                        </div>
                    </div>
                    <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                        <hr>
                        <div class="text-center">
                            <div class="h5 font-weight-bold text-dark">
                                ₱<?= number_format($procurementInsights['total_value'] ?? 0, 2) ?>
                            </div>
                            <div class="text-xs text-muted">Total Procurement Value (12 months)</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Delivery Performance Trends -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Delivery Performance Trends</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="deliveryTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="?route=vendors/vendorComparison" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-balance-scale mb-2"></i><br>
                                Compare Vendors
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="?route=vendors/riskAssessment" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-exclamation-triangle mb-2"></i><br>
                                Risk Assessment
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="?route=vendors" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-list mb-2"></i><br>
                                Vendor Directory
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <button class="btn btn-outline-info btn-block" onclick="getVendorRecommendations()">
                                <i class="fas fa-lightbulb mb-2"></i><br>
                                Get Recommendations
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vendor Recommendations Modal -->
<div class="modal fade" id="recommendationsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vendor Recommendations</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="recommendationsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions for styling
function getScoreColor($score) {
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
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initializeRiskPieChart();
    initializeDeliveryTrendsChart();
});

// Risk Distribution Pie Chart
function initializeRiskPieChart() {
    const ctx = document.getElementById('riskPieChart').getContext('2d');
    const riskData = <?= json_encode($riskSummary) ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low', 'Minimal'],
            datasets: [{
                data: [
                    riskData.critical || 0,
                    riskData.high || 0,
                    riskData.medium || 0,
                    riskData.low || 0,
                    riskData.minimal || 0
                ],
                backgroundColor: [
                    '#e74a3b',
                    '#f39c12',
                    '#3498db',
                    '#2ecc71',
                    '#95a5a6'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: false
            },
            cutoutPercentage: 80,
        },
    });
}

// Delivery Trends Line Chart
function initializeDeliveryTrendsChart() {
    const ctx = document.getElementById('deliveryTrendsChart').getContext('2d');
    const trendsData = <?= json_encode($deliveryTrends) ?>;
    
    const labels = trendsData.map(item => item.month);
    const onTimeRates = trendsData.map(item => parseFloat(item.on_time_rate));
    const completionRates = trendsData.map(item => parseFloat(item.completion_rate));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'On-Time Delivery Rate',
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: onTimeRates,
            }, {
                label: 'Completion Rate',
                lineTension: 0.3,
                backgroundColor: "rgba(28, 200, 138, 0.05)",
                borderColor: "rgba(28, 200, 138, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(28, 200, 138, 1)",
                pointBorderColor: "rgba(28, 200, 138, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(28, 200, 138, 1)",
                pointHoverBorderColor: "rgba(28, 200, 138, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: completionRates,
            }]
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
                    time: {
                        unit: 'month'
                    },
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
                display: true
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
}

// Get vendor recommendations
function getVendorRecommendations() {
    $('#recommendationsModal').modal('show');
    $('#recommendationsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading recommendations...</div>');
    
    fetch('?route=vendors/getVendorRecommendations')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecommendations(data.recommendations);
            } else {
                $('#recommendationsContent').html('<div class="alert alert-danger">Failed to load recommendations</div>');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            $('#recommendationsContent').html('<div class="alert alert-danger">Error loading recommendations</div>');
        });
}

// Display vendor recommendations
function displayRecommendations(recommendations) {
    let content = '<div class="row">';
    
    if (recommendations.length === 0) {
        content = '<div class="text-center"><p class="text-muted">No recommendations available at this time.</p></div>';
    } else {
        recommendations.forEach((vendor, index) => {
            const scoreColor = getScoreColorClass(vendor.recommendation_score);
            content += `
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">${vendor.name}</h6>
                                <span class="badge badge-${scoreColor}">${vendor.recommendation_score.toFixed(0)}%</span>
                            </div>
                            <p class="card-text small text-muted mb-2">
                                Contact: ${vendor.contact_person || 'N/A'}<br>
                                Orders: ${vendor.total_orders} | On-time: ${vendor.on_time_rate.toFixed(1)}%
                            </p>
                            <div class="btn-group btn-group-sm">
                                <a href="?route=vendors/view&id=${vendor.id}" class="btn btn-outline-primary btn-sm">View</a>
                                <a href="?route=vendors/performanceAnalysis&id=${vendor.id}" class="btn btn-outline-info btn-sm">Analyze</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    content += '</div>';
    $('#recommendationsContent').html(content);
}

// Helper function for score color classes
function getScoreColorClass(score) {
    if (score >= 85) return 'success';
    if (score >= 70) return 'info';
    if (score >= 60) return 'warning';
    return 'danger';
}

// Refresh top vendors data
function refreshTopVendors() {
    // You can implement AJAX refresh here if needed
    location.reload();
}
</script>

<?php include APP_ROOT . '/views/layouts/main.php'; ?>