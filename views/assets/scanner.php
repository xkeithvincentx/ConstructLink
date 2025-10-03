<?php
/**
 * ConstructLink™ Asset QR Scanner View
 * QR code scanner for asset lookup
 */

$pageTitle = $pageTitle ?? 'Asset Scanner - ConstructLink™';
?>

<?php ob_start(); ?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?route=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="?route=assets">Assets</a></li>
            <li class="breadcrumb-item active" aria-current="page">QR Scanner</li>
        </ol>
    </nav>

    <!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

    <div class="row">
        <!-- Scanner Section -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-camera me-2"></i>QR Code Scanner
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Camera Preview -->
                    <div class="text-center mb-4">
                        <div id="scanner-container" class="position-relative d-inline-block">
                            <video id="scanner-video" width="400" height="300" class="border rounded" style="display: none;"></video>
                            <canvas id="scanner-canvas" width="400" height="300" class="border rounded" style="display: none;"></canvas>
                            
                            <!-- Scanner Overlay -->
                            <div id="scanner-overlay" class="position-absolute top-50 start-50 translate-middle">
                                <div class="text-center">
                                    <i class="bi bi-qr-code display-1 text-muted mb-3"></i>
                                    <h5>Click "Start Scanner" to begin</h5>
                                    <p class="text-muted">Position the QR code within the camera view</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scanner Controls -->
                    <div class="text-center mb-4">
                        <button id="start-scanner" class="btn btn-primary me-2">
                            <i class="bi bi-play-circle me-1"></i>Start Scanner
                        </button>
                        <button id="stop-scanner" class="btn btn-outline-secondary me-2" style="display: none;">
                            <i class="bi bi-stop-circle me-1"></i>Stop Scanner
                        </button>
                        <button id="debug-mode" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-bug me-1"></i>Debug Mode
                        </button>
                    </div>
                    
                    <!-- Debug Information -->
                    <div id="debug-info" class="alert alert-info small" style="display: none;">
                        <div class="mb-2">
                            <strong>Debug Information:</strong>
                        </div>
                        <div id="debug-camera">Camera: Not initialized</div>
                        <div id="debug-scanning">Scanning: No</div>
                        <div id="debug-qr-library">jsQR Library: <span id="debug-jsqr-status">Loading...</span></div>
                        <div id="debug-last-detection">Last Detection: None</div>
                        <div class="mt-2">
                            <input type="text" id="debug-qr-input" class="form-control form-control-sm" placeholder="Paste QR data for manual testing...">
                            <button type="button" id="debug-test-qr" class="btn btn-sm btn-outline-secondary mt-1">Test QR Data</button>
                        </div>
                    </div>

                    <!-- Manual Input Alternative -->
                    <div class="border-top pt-4">
                        <h6 class="mb-3">Manual Asset Lookup</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" 
                                       class="form-control" 
                                       id="manual-search" 
                                       placeholder="Enter asset reference or scan QR code...">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary w-100" onclick="searchAsset()">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="col-lg-4">
            <!-- Scanner Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Scanner Status
                    </h6>
                </div>
                <div class="card-body">
                    <div id="scanner-status">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm text-secondary me-2" role="status" style="display: none;" id="status-loading">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span id="status-text" class="text-muted">Ready to scan</span>
                        </div>
                    </div>
                    
                    <div class="mt-3 small text-muted">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Scans Today:</span>
                            <span id="scan-count">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Last Scan:</span>
                            <span id="last-scan">Never</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Result -->
            <div class="card" id="asset-result" style="display: none;">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-box me-2"></i>Asset Found
                    </h6>
                </div>
                <div class="card-body" id="asset-details">
                    <!-- Asset details will be populated here -->
                </div>
            </div>

            <!-- Recent Scans -->
            <div class="card" id="recent-scans" style="display: none;">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Recent Scans
                    </h6>
                </div>
                <div class="card-body">
                    <div id="recent-scans-list">
                        <!-- Recent scans will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Scanner Help -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-question-circle me-2"></i>How to Use
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="bi bi-1-circle text-primary me-2"></i>
                            Click "Start Scanner" to activate camera
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-2-circle text-primary me-2"></i>
                            Position QR code within camera view
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-3-circle text-primary me-2"></i>
                            Asset details will appear automatically
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-4-circle text-primary me-2"></i>
                            Use manual search if camera unavailable
                        </li>
                    </ul>
                    
                    <div class="alert alert-info small mt-3">
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> Ensure good lighting and hold the QR code steady for best results.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include jsQR library -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<script>
class AssetScanner {
    constructor() {
        this.video = document.getElementById('scanner-video');
        this.canvas = document.getElementById('scanner-canvas');
        this.context = this.canvas.getContext('2d');
        this.scanning = false;
        this.stream = null;
        this.scanCount = 0;
        this.recentScans = [];
        this.lastQRData = null;
        this.lastQRTime = 0;
        this.debugMode = false;
        
        this.initializeEventListeners();
        this.loadScanHistory();
        this.initializeDebugMode();
    }
    
    initializeEventListeners() {
        document.getElementById('start-scanner').addEventListener('click', () => this.startScanner());
        document.getElementById('stop-scanner').addEventListener('click', () => this.stopScanner());
        document.getElementById('debug-mode').addEventListener('click', () => this.toggleDebugMode());
        document.getElementById('debug-test-qr').addEventListener('click', () => this.testQRData());
        document.getElementById('manual-search').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchAsset();
            }
        });
    }
    
    initializeDebugMode() {
        // Check jsQR library status
        const jsqrStatus = document.getElementById('debug-jsqr-status');
        if (typeof jsQR !== 'undefined') {
            jsqrStatus.textContent = 'Available';
            jsqrStatus.className = 'text-success';
        } else {
            jsqrStatus.textContent = 'Not loaded';
            jsqrStatus.className = 'text-danger';
        }
    }
    
    toggleDebugMode() {
        this.debugMode = !this.debugMode;
        const debugInfo = document.getElementById('debug-info');
        const debugButton = document.getElementById('debug-mode');
        
        if (this.debugMode) {
            debugInfo.style.display = 'block';
            debugButton.innerHTML = '<i class="bi bi-bug-fill me-1"></i>Debug On';
            debugButton.classList.remove('btn-outline-info');
            debugButton.classList.add('btn-info');
            this.updateDebugInfo();
        } else {
            debugInfo.style.display = 'none';
            debugButton.innerHTML = '<i class="bi bi-bug me-1"></i>Debug Mode';
            debugButton.classList.remove('btn-info');
            debugButton.classList.add('btn-outline-info');
        }
    }
    
    updateDebugInfo() {
        if (!this.debugMode) return;
        
        document.getElementById('debug-camera').innerHTML = 
            `Camera: ${this.stream ? 'Active' : 'Not initialized'}`;
        document.getElementById('debug-scanning').innerHTML = 
            `Scanning: ${this.scanning ? 'Yes' : 'No'}`;
        document.getElementById('debug-last-detection').innerHTML = 
            `Last Detection: ${this.lastQRData ? new Date(this.lastQRTime).toLocaleTimeString() : 'None'}`;
    }
    
    testQRData() {
        const qrInput = document.getElementById('debug-qr-input');
        const qrData = qrInput.value.trim();
        
        if (!qrData) {
            alert('Please enter QR data to test');
            return;
        }
        
        console.log('Testing QR data manually:', qrData);
        this.processQRCode(qrData);
    }
    
    async startScanner() {
        try {
            this.updateStatus('Requesting camera access...', true);
            
            this.stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 400 },
                    height: { ideal: 300 }
                } 
            });
            
            this.video.srcObject = this.stream;
            this.video.style.display = 'block';
            document.getElementById('scanner-overlay').style.display = 'none';
            document.getElementById('start-scanner').style.display = 'none';
            document.getElementById('stop-scanner').style.display = 'inline-block';
            
            this.video.play();
            this.scanning = true;
            this.updateStatus('Scanner active - Position QR code in view');
            this.updateDebugInfo();
            
            // Start scanning loop
            this.scanLoop();
            
        } catch (error) {
            console.error('Camera access error:', error);
            this.updateStatus('Camera access denied or unavailable');
            alert('Unable to access camera. Please use manual search or check camera permissions.');
        }
    }
    
    stopScanner() {
        this.scanning = false;
        
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        
        this.video.style.display = 'none';
        document.getElementById('scanner-overlay').style.display = 'block';
        document.getElementById('start-scanner').style.display = 'inline-block';
        document.getElementById('stop-scanner').style.display = 'none';
        
        this.updateStatus('Scanner stopped');
        this.updateDebugInfo();
    }
    
    scanLoop() {
        if (!this.scanning) return;
        
        // Check if video is ready
        if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
            // Set canvas size to match video
            this.canvas.height = this.video.videoHeight;
            this.canvas.width = this.video.videoWidth;
            
            // Draw video frame to canvas
            this.context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
            
            // Use jsQR to detect QR codes
            this.detectQRCode();
        }
        
        // Continue scanning
        requestAnimationFrame(() => this.scanLoop());
    }
    
    detectQRCode() {
        // Check if jsQR is available
        if (typeof jsQR === 'undefined') {
            console.error('jsQR library not loaded');
            this.updateStatus('QR detection library not available');
            return;
        }
        
        // Get image data for QR detection
        const imageData = this.context.getImageData(0, 0, this.canvas.width, this.canvas.height);
        
        try {
            // Attempt QR code detection with multiple inversion attempts for better detection
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "attemptBoth" // Try both normal and inverted for better detection
            });
            
            if (code && code.data) {
                console.log('QR Code detected:', code.data);
                
                // Prevent multiple rapid scans of the same code
                if (this.lastQRData !== code.data || Date.now() - this.lastQRTime > 3000) {
                    this.lastQRData = code.data;
                    this.lastQRTime = Date.now();
                    this.updateDebugInfo();
                    
                    // Stop scanning temporarily while processing
                    this.scanning = false;
                    setTimeout(() => {
                        this.scanning = true;
                        this.updateDebugInfo();
                    }, 2000);
                    
                    this.processQRCode(code.data);
                }
            }
        } catch (error) {
            console.error('QR detection error:', error);
        }
    }
    
    async processQRCode(qrData) {
        try {
            this.updateStatus('QR code detected - Validating...', true);
            console.log('Processing QR data:', qrData);
            
            // Call the QR validation API to process SecureLink QR codes
            const response = await fetch('?route=api/validate-qr&data=' + encodeURIComponent(qrData));
            const result = await response.json();
            
            console.log('QR validation result:', result);
            
            if (result.valid && result.data) {
                // SecureLink validation returns data object with asset info
                const assetData = result.data;
                this.updateStatus('Valid QR code - Loading asset...', true);
                this.lookupAsset(assetData.asset_id);
                this.recordScan({
                    asset_id: assetData.asset_id,
                    asset_ref: assetData.asset_ref || 'Unknown',
                    timestamp: new Date().toISOString()
                });
            } else {
                this.updateStatus('Invalid or expired QR code');
                console.log('QR validation failed:', result.message);
                
                // Show more detailed error message
                setTimeout(() => {
                    alert(`QR Code Error: ${result.message || 'Invalid QR code format'}\n\nPlease ensure you're scanning a valid SecureLink™ QR code from an asset tag.`);
                }, 1000);
            }
            
        } catch (error) {
            console.error('QR processing error:', error);
            this.updateStatus('Failed to process QR code');
            setTimeout(() => {
                alert('Failed to process QR code. Please try again or use manual search.');
            }, 1000);
        }
    }
    
    async lookupAsset(assetId) {
        try {
            // Use the asset search API to get detailed asset information
            const response = await fetch(`?route=api/assets/search&q=id:${assetId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('Asset lookup result:', result);
            
            if (result.success && result.assets && result.assets.length > 0) {
                const asset = result.assets[0];
                this.displayAssetResult(asset);
                this.updateStatus('Asset found successfully');
            } else {
                this.updateStatus('Asset not found in database');
                this.showAssetNotFound(assetId);
            }
            
        } catch (error) {
            console.error('Asset lookup error:', error);
            this.updateStatus('Failed to lookup asset');
            
            // Fallback: try to display basic asset info
            this.displayAssetResult({
                id: assetId,
                name: 'Asset Details Unavailable',
                ref: 'Loading...',
                status: 'unknown',
                category_name: 'Unable to load details'
            });
        }
    }
    
    displayAssetResult(asset) {
        const resultCard = document.getElementById('asset-result');
        const detailsDiv = document.getElementById('asset-details');
        
        const statusClass = this.getStatusClass(asset.status);
        
        detailsDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="mb-1">${this.escapeHtml(asset.name)}</h6>
                    <small class="text-muted">${this.escapeHtml(asset.ref)}</small>
                </div>
                <span class="badge ${statusClass}">${this.formatStatus(asset.status)}</span>
            </div>
            
            <div class="small">
                <div class="row mb-2">
                    <div class="col-5 text-muted">Category:</div>
                    <div class="col-7">${this.escapeHtml(asset.category_name || 'N/A')}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Project:</div>
                    <div class="col-7">${this.escapeHtml(asset.project_name || 'N/A')}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-5 text-muted">Location:</div>
                    <div class="col-7">${this.escapeHtml(asset.project_location || 'N/A')}</div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <a href="?route=assets/view&id=${asset.id}" class="btn btn-primary btn-sm">
                    <i class="bi bi-eye me-1"></i>View Details
                </a>
                ${asset.status === 'available' ? `
                    <a href="?route=withdrawals/create&asset_id=${asset.id}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Withdraw Asset
                    </a>
                ` : ''}
            </div>
        `;
        
        resultCard.style.display = 'block';
    }
    
    showAssetNotFound(assetId) {
        const resultCard = document.getElementById('asset-result');
        const detailsDiv = document.getElementById('asset-details');
        
        detailsDiv.innerHTML = `
            <div class="text-center py-3">
                <i class="bi bi-exclamation-triangle text-warning display-4 mb-3"></i>
                <h6>Asset Not Found</h6>
                <p class="text-muted small">Asset ID: ${this.escapeHtml(assetId)}</p>
                <a href="?route=assets" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-search me-1"></i>Browse All Assets
                </a>
            </div>
        `;
        
        resultCard.style.display = 'block';
    }
    
    recordScan(data) {
        this.scanCount++;
        this.recentScans.unshift({
            ...data,
            timestamp: new Date().toLocaleTimeString()
        });
        
        // Keep only last 5 scans
        if (this.recentScans.length > 5) {
            this.recentScans = this.recentScans.slice(0, 5);
        }
        
        this.updateScanStats();
        this.saveScanHistory();
    }
    
    updateScanStats() {
        document.getElementById('scan-count').textContent = this.scanCount;
        document.getElementById('last-scan').textContent = 
            this.recentScans.length > 0 ? this.recentScans[0].timestamp : 'Never';
    }
    
    updateStatus(message, loading = false) {
        document.getElementById('status-text').textContent = message;
        document.getElementById('status-loading').style.display = loading ? 'inline-block' : 'none';
    }
    
    getStatusClass(status) {
        const classes = {
            'available': 'bg-success',
            'in_use': 'bg-primary',
            'borrowed': 'bg-warning',
            'under_maintenance': 'bg-info',
            'retired': 'bg-secondary'
        };
        return classes[status] || 'bg-secondary';
    }
    
    formatStatus(status) {
        return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    loadScanHistory() {
        const saved = localStorage.getItem('assetScanHistory');
        if (saved) {
            const data = JSON.parse(saved);
            this.scanCount = data.scanCount || 0;
            this.recentScans = data.recentScans || [];
            this.updateScanStats();
        }
    }
    
    saveScanHistory() {
        localStorage.setItem('assetScanHistory', JSON.stringify({
            scanCount: this.scanCount,
            recentScans: this.recentScans
        }));
    }
}

// Manual search function
function searchAsset() {
    const searchTerm = document.getElementById('manual-search').value.trim();
    if (!searchTerm) {
        alert('Please enter an asset reference to search');
        return;
    }
    
    // Redirect to asset search
    window.location.href = `?route=assets&search=${encodeURIComponent(searchTerm)}`;
}

// Initialize scanner when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Check if browser supports camera
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        document.getElementById('start-scanner').disabled = true;
        document.getElementById('start-scanner').innerHTML = '<i class="bi bi-camera-video-off me-1"></i>Camera Not Supported';
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-warning';
        alert.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Camera access is not supported in this browser. Please use manual search.';
        document.querySelector('.card-body').prepend(alert);
    } else {
        new AssetScanner();
    }
});
</script>

<?php
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Asset Scanner - ConstructLink™';
$pageHeader = 'Asset QR Scanner';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Assets', 'url' => '?route=assets'],
    ['title' => 'QR Scanner', 'url' => '?route=assets/scanner']
];

include APP_ROOT . '/views/layouts/main.php';
?>
