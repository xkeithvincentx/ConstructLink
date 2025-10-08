<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Tags Batch Print - <?= count($assets) ?> Assets</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: white;
            padding: 20px;
        }
        
        .batch-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .batch-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .batch-info {
            color: #666;
            font-size: 14px;
        }
        
        .tags-grid {
            display: grid;
            gap: 15px;
            justify-content: center;
            align-items: start;
        }
        
        /* Engineering-optimized grid layouts based on tag sizes */
        .grid-micro {
            grid-template-columns: repeat(10, 1fr);
            gap: 8px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .grid-compact {
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .grid-standard {
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .grid-industrial {
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .grid-materials {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .grid-infrastructure {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Legacy grid layouts (backward compatibility) */
        .grid-small {
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .grid-medium {
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .grid-large {
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .grid-consumable {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .grid-mixed {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .qr-tag {
            border: 2px solid #000;
            background: white;
            text-align: center;
            padding: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .securelink-branding {
            font-family: Arial, sans-serif;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 4px;
            text-align: center;
        }
        
        /* SecureLink branding for new tag sizes */
        .tag-micro .securelink-branding {
            font-size: 4px;
            margin-bottom: 1px;
        }
        
        .tag-compact .securelink-branding {
            font-size: 5px;
            margin-bottom: 2px;
        }
        
        .tag-standard .securelink-branding {
            font-size: 6px;
            margin-bottom: 3px;
        }
        
        .tag-industrial .securelink-branding {
            font-size: 8px;
            margin-bottom: 4px;
        }
        
        .tag-materials .securelink-branding {
            font-size: 7px;
            margin-bottom: 3px;
        }
        
        .tag-infrastructure .securelink-branding {
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        /* Legacy branding sizes (backward compatibility) */
        .tag-small .securelink-branding {
            font-size: 5px;
            margin-bottom: 2px;
        }
        
        .tag-medium .securelink-branding {
            font-size: 6px;
            margin-bottom: 3px;
        }
        
        .tag-large .securelink-branding {
            font-size: 8px;
            margin-bottom: 4px;
        }
        
        .tag-consumable .securelink-branding {
            font-size: 7px;
            margin-bottom: 3px;
        }
        
        /* Engineering-Optimized Tag Size Standards */
        .tag-micro {
            width: 72px;   /* 0.75 inches at 96 DPI */
            height: 72px;  /* 0.75 inches at 96 DPI */
        }
        
        .tag-compact {
            width: 96px;   /* 1 inch at 96 DPI */
            height: 120px; /* 1.25 inches at 96 DPI */
        }
        
        .tag-standard {
            width: 144px;  /* 1.5 inches at 96 DPI */
            height: 144px; /* 1.5 inches at 96 DPI - Square format */
        }
        
        .tag-industrial {
            width: 192px;  /* 2 inches at 96 DPI */
            height: 240px; /* 2.5 inches at 96 DPI */
        }
        
        .tag-materials {
            width: 288px;  /* 3 inches at 96 DPI */
            height: 144px; /* 1.5 inches at 96 DPI - Wide format */
        }
        
        .tag-infrastructure {
            width: 288px;  /* 3 inches at 96 DPI */
            height: 384px; /* 4 inches at 96 DPI */
        }
        
        /* Legacy size support (for backward compatibility) */
        .tag-small { /* Maps to compact */
            width: 96px;
            height: 120px;
        }
        
        .tag-medium { /* Maps to standard */
            width: 144px;
            height: 144px;
        }
        
        .tag-large { /* Maps to industrial */
            width: 192px;
            height: 240px;
        }
        
        .tag-consumable { /* Maps to materials */
            width: 288px;
            height: 144px;
        }
        
        .qr-code {
            background: #f8f9fa;
            border: 1px solid #ddd;
            margin: 0 auto 6px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* QR Code sizes optimized for each tag type */
        .tag-micro .qr-code {
            width: 45px;
            height: 45px;
        }
        
        .tag-compact .qr-code {
            width: 60px;
            height: 60px;
        }
        
        .tag-standard .qr-code {
            width: 80px;
            height: 80px;
        }
        
        .tag-industrial .qr-code {
            width: 120px;
            height: 120px;
        }
        
        .tag-materials .qr-code {
            width: 100px;
            height: 100px;
        }
        
        .tag-infrastructure .qr-code {
            width: 150px;
            height: 150px;
        }
        
        /* Legacy QR code sizes (backward compatibility) */
        .tag-small .qr-code {
            width: 60px;
            height: 60px;
        }
        
        .tag-medium .qr-code {
            width: 80px;
            height: 80px;
        }
        
        .tag-large .qr-code {
            width: 120px;
            height: 120px;
        }
        
        .tag-consumable .qr-code {
            width: 100px;
            height: 100px;
        }
        
        .qr-code img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .asset-ref {
            font-weight: bold;
            margin-bottom: 3px;
            word-wrap: break-word;
        }
        
        /* Asset reference font sizes for new tag types */
        .tag-micro .asset-ref {
            font-size: 6px;
        }
        
        .tag-compact .asset-ref {
            font-size: 8px;
        }
        
        .tag-standard .asset-ref {
            font-size: 10px;
        }
        
        .tag-industrial .asset-ref {
            font-size: 12px;
        }
        
        .tag-materials .asset-ref {
            font-size: 11px;
        }
        
        .tag-infrastructure .asset-ref {
            font-size: 14px;
        }
        
        /* Legacy asset reference sizes */
        .tag-small .asset-ref {
            font-size: 8px;
        }
        
        .tag-medium .asset-ref {
            font-size: 10px;
        }
        
        .tag-large .asset-ref {
            font-size: 12px;
        }
        
        .tag-consumable .asset-ref {
            font-size: 11px;
        }
        
        .asset-name {
            margin-bottom: 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.1;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Asset name styles for new tag types */
        .tag-micro .asset-name {
            font-size: 5px;
            -webkit-line-clamp: 1;
            max-height: 6px;
        }
        
        .tag-compact .asset-name {
            font-size: 6px;
            -webkit-line-clamp: 2;
            max-height: 14px;
        }
        
        .tag-standard .asset-name {
            font-size: 8px;
            -webkit-line-clamp: 3;
            max-height: 26px;
        }
        
        .tag-industrial .asset-name {
            font-size: 10px;
            -webkit-line-clamp: 4;
            max-height: 44px;
        }
        
        .tag-materials .asset-name {
            font-size: 9px;
            -webkit-line-clamp: 2;
            max-height: 20px;
        }
        
        .tag-infrastructure .asset-name {
            font-size: 12px;
            -webkit-line-clamp: 6;
            max-height: 72px;
        }
        
        /* Legacy asset name styles */
        .tag-small .asset-name {
            font-size: 6px;
            -webkit-line-clamp: 2;
            max-height: 14px;
        }
        
        .tag-medium .asset-name {
            font-size: 8px;
            -webkit-line-clamp: 3;
            max-height: 26px;
        }
        
        .tag-large .asset-name {
            font-size: 10px;
            -webkit-line-clamp: 4;
            max-height: 44px;
        }
        
        .tag-consumable .asset-name {
            font-size: 9px;
            -webkit-line-clamp: 2;
            max-height: 20px;
        }
        
        .additional-info {
            color: #666;
            margin-top: 2px;
            line-height: 1.0;
        }
        
        /* Additional info styles for new tag types */
        .tag-micro .additional-info {
            font-size: 3px;
        }
        
        .tag-compact .additional-info {
            font-size: 4px;
        }
        
        .tag-standard .additional-info {
            font-size: 5px;
        }
        
        .tag-industrial .additional-info {
            font-size: 7px;
        }
        
        .tag-materials .additional-info {
            font-size: 6px;
        }
        
        .tag-infrastructure .additional-info {
            font-size: 9px;
        }
        
        /* Legacy additional info styles */
        .tag-small .additional-info {
            font-size: 4px;
        }
        
        .tag-medium .additional-info {
            font-size: 5px;
        }
        
        .tag-large .additional-info {
            font-size: 7px;
        }
        
        .tag-consumable .additional-info {
            font-size: 6px;
        }
        
        .serial-number {
            color: #333;
            font-weight: bold;
        }
        
        .location-info {
            color: #0066cc;
        }
        
        .status-indicator {
            display: inline-block;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            margin-right: 2px;
            vertical-align: middle;
        }
        
        .status-available { background-color: #28a745; }
        .status-in-use { background-color: #007bff; }
        .status-maintenance { background-color: #ffc107; }
        .status-retired { background-color: #6c757d; }
        
        .print-instructions {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
            page-break-inside: avoid;
        }
        
        /* Cut lines for printing */
        .qr-tag::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border: 1px dashed #bbb;
            pointer-events: none;
        }
        
        /* Corner cut marks */
        .qr-tag::after {
            content: '';
            position: absolute;
            top: -6px;
            left: -6px;
            width: 8px;
            height: 8px;
            border-left: 1px solid #999;
            border-top: 1px solid #999;
            pointer-events: none;
        }
        
        /* Print styles */
        @media print {
            @page {
                margin: 8mm;
                size: auto;
            }
            
            body {
                padding: 0;
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .batch-header {
                margin-bottom: 15px;
            }
            
            .print-instructions {
                display: none;
            }
            
            .qr-tag {
                box-shadow: none;
                page-break-inside: avoid;
                break-inside: avoid;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .tags-grid {
                gap: 8px;
            }
            
            .securelink-branding,
            .status-indicator,
            .location-info {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .qr-tag::before,
            .qr-tag::after {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="batch-header">
        <div class="batch-title">Asset QR Tags</div>
        <div class="batch-info">
            <?= count($assets) ?> Tags • Generated: <?= date('M j, Y g:i A') ?>
        </div>
    </div>
    
    <?php
    // Determine grid class based on tag sizes
    $tagSizes = array_unique(array_column($assets, 'tag_size'));
    $gridClass = 'grid-mixed'; // Default for mixed sizes
    
    if (count($tagSizes) === 1) {
        $gridClass = 'grid-' . $tagSizes[0];
    }
    ?>
    
    <div class="tags-grid <?= $gridClass ?>">
        <?php foreach ($assets as $asset): ?>
            <div class="qr-tag tag-<?= htmlspecialchars($asset['tag_size']) ?>">
                <!-- SecureLink Branding -->
                <div class="securelink-branding">SecureLink™</div>
                
                <!-- QR Code -->
                <div class="qr-code">
                    <?php
                    // Generate QR code URL using QR Server API with optimized sizes
                    $qrData = $asset['ref'];
                    $qrSizeMap = [
                        'micro' => '45',
                        'compact' => '60', 
                        'standard' => '80',
                        'industrial' => '120',
                        'materials' => '100',
                        'infrastructure' => '150',
                        // Legacy support
                        'small' => '60',
                        'medium' => '80', 
                        'large' => '120',
                        'consumable' => '100'
                    ];
                    $qrSize = $qrSizeMap[$asset['tag_size']] ?? '80';
                    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$qrSize}x{$qrSize}&data=" . urlencode($qrData);
                    ?>
                    <img src="<?= $qrCodeUrl ?>" alt="QR Code for <?= htmlspecialchars($asset['ref']) ?>" 
                         onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#666;font-size:12px;\'>QR</div>';">
                </div>
                
                <!-- Asset Reference -->
                <div class="asset-ref">
                    <?php
                    // ISO standard asset references can be up to 50 characters
                    // Adjust max length per tag size to accommodate longer references
                    $refLengthMap = [
                        'micro' => 12,           // Compact tags need tight limits
                        'compact' => 18,         // Small equipment tags
                        'standard' => 28,        // Standard capital asset tags
                        'industrial' => 35,      // Large equipment tags
                        'materials' => 32,       // Wide format materials tags
                        'infrastructure' => 45,  // Large infrastructure tags
                        // Legacy support
                        'small' => 18,
                        'medium' => 28,
                        'large' => 35,
                        'consumable' => 32
                    ];
                    $maxLength = $refLengthMap[$asset['tag_size']] ?? 28;
                    $displayRef = strlen($asset['ref']) > $maxLength
                        ? substr($asset['ref'], 0, $maxLength - 3) . '...'
                        : $asset['ref'];
                    ?>
                    <?= htmlspecialchars($displayRef) ?>
                </div>
                
                <!-- Asset Name -->
                <div class="asset-name">
                    <?= htmlspecialchars($asset['name']) ?>
                </div>
                
                <!-- Additional Information based on tag size -->
                <div class="additional-info">
                    <?php
                    // Status indicator
                    $statusClass = 'status-' . str_replace('_', '-', $asset['status'] ?? 'available');
                    ?>
                    <span class="status-indicator <?= $statusClass ?>"></span>
                    
                    <?php if ($asset['tag_size'] === 'micro'): ?>
                        <!-- Micro tags: Only essential ID -->
                        <?php if (!empty($asset['sub_location'])): ?>
                            <?php
                            $microLoc = str_replace(['Warehouse', 'Tool Room', 'Storage Area', 'Office', 'Field Storage'], ['WH', 'TR', 'ST', 'OF', 'FS'], $asset['sub_location']);
                            ?>
                            <span class="location-info"><?= htmlspecialchars(substr($microLoc, 0, 6)) ?></span>
                        <?php endif; ?>
                        
                    <?php elseif ($asset['tag_size'] === 'compact' || $asset['tag_size'] === 'small'): ?>
                        <!-- Compact/Small tags: Smart location display -->
                        <?php
                        $locationText = '';
                        if (!empty($asset['sub_location'])) {
                            // Prioritize sub_location with abbreviations
                            $subLoc = str_replace(['Warehouse', 'Tool Room', 'Storage Area', 'Office'], ['WH', 'TR', 'ST', 'OF'], $asset['sub_location']);
                            $locationText = substr($subLoc, 0, 8);
                        } elseif (!empty($asset['location'])) {
                            $locationText = substr($asset['location'], 0, 8);
                        } elseif (!empty($asset['project_name'])) {
                            $locationText = substr($asset['project_name'], 0, 8);
                        }
                        ?>
                        <?php if ($locationText): ?>
                            <span class="location-info"><?= htmlspecialchars($locationText) ?></span>
                        <?php endif; ?>
                        
                    <?php elseif ($asset['tag_size'] === 'standard' || $asset['tag_size'] === 'medium'): ?>
                        <!-- Standard/Medium tags: Project and sub-location -->
                        <?php if (!empty($asset['project_name'])): ?>
                            <?= htmlspecialchars(substr($asset['project_name'], 0, 15)) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['sub_location'])): ?>
                            <span class="location-info">@ <?= htmlspecialchars(substr($asset['sub_location'], 0, 12)) ?></span>
                        <?php elseif (!empty($asset['location'])): ?>
                            <span class="location-info">@ <?= htmlspecialchars(substr($asset['location'], 0, 12)) ?></span>
                        <?php endif; ?>
                        
                    <?php elseif ($asset['tag_size'] === 'industrial' || $asset['tag_size'] === 'large'): ?>
                        <!-- Industrial/Large tags: Full equipment details -->
                        <?php if (!empty($asset['project_name'])): ?>
                            Project: <?= htmlspecialchars(substr($asset['project_name'], 0, 18)) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['category_name'])): ?>
                            <?= htmlspecialchars(substr($asset['category_name'], 0, 18)) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['sub_location'])): ?>
                            <span class="location-info">Location: <?= htmlspecialchars(substr($asset['sub_location'], 0, 15)) ?></span><br>
                        <?php elseif (!empty($asset['location'])): ?>
                            <span class="location-info">Location: <?= htmlspecialchars(substr($asset['location'], 0, 15)) ?></span><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['serial_number'])): ?>
                            <span class="serial-number">S/N: <?= htmlspecialchars(substr($asset['serial_number'], 0, 12)) ?></span>
                        <?php endif; ?>
                        
                    <?php elseif ($asset['tag_size'] === 'materials' || $asset['tag_size'] === 'consumable'): ?>
                        <!-- Materials/Consumable tags: Project and inventory info -->
                        <?php if (!empty($asset['project_name'])): ?>
                            <?= htmlspecialchars(substr($asset['project_name'], 0, 20)) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['sub_location'])): ?>
                            <span class="location-info"><?= htmlspecialchars(substr($asset['sub_location'], 0, 18)) ?></span>
                        <?php elseif (!empty($asset['location'])): ?>
                            <span class="location-info"><?= htmlspecialchars(substr($asset['location'], 0, 18)) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($asset['acquired_date'])): ?>
                            <br>Acquired: <?= date('m/Y', strtotime($asset['acquired_date'])) ?>
                        <?php endif; ?>
                        
                    <?php elseif ($asset['tag_size'] === 'infrastructure'): ?>
                        <!-- Infrastructure tags: Comprehensive details -->
                        <?php if (!empty($asset['project_name'])): ?>
                            Project: <?= htmlspecialchars(substr($asset['project_name'], 0, 25)) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['category_name'])): ?>
                            Type: <?= htmlspecialchars(substr($asset['category_name'], 0, 25)) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['sub_location'])): ?>
                            <span class="location-info">Location: <?= htmlspecialchars(substr($asset['sub_location'], 0, 20)) ?></span><br>
                        <?php elseif (!empty($asset['location'])): ?>
                            <span class="location-info">Location: <?= htmlspecialchars(substr($asset['location'], 0, 20)) ?></span><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['serial_number'])): ?>
                            <span class="serial-number">S/N: <?= htmlspecialchars(substr($asset['serial_number'], 0, 18)) ?></span><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['maker_name'])): ?>
                            Mfr: <?= htmlspecialchars(substr($asset['maker_name'], 0, 20)) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($asset['acquired_date'])): ?>
                            Installed: <?= date('M Y', strtotime($asset['acquired_date'])) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="print-instructions">
        <p><strong>Batch Print Instructions:</strong></p>
        <p>• Use your browser's print function (Ctrl+P or Cmd+P)</p>
        <p>• Select "More settings" and set margins to "Minimum" for better fitting</p>
        <p>• Consider using "Landscape" orientation for better layout</p>
        <p>• For best results, use adhesive label paper</p>
        <p>• Cut along dashed lines after printing</p>
        <p><em>Total tags: <?= count($assets) ?></em></p>
    </div>
    
    <script>
        // Auto-print when page loads (optional - user can disable)
        window.addEventListener('load', function() {
            // Small delay to ensure all QR codes load
            setTimeout(function() {
                if (confirm('Print all <?= count($assets) ?> QR tags now?')) {
                    window.print();
                }
            }, 2000);
        });
    </script>
</body>
</html>