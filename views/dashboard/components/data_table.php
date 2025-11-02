<?php
/**
 * Data Table Component - Neutral Design System V2.0
 *
 * Reusable table component with consistent styling, empty states, and accessibility.
 * Supports responsive design with optional horizontal scrolling.
 * Follows WCAG 2.1 AA accessibility standards with proper table markup.
 *
 * @param array $columns Required - Array of column definitions
 *   Each column should have:
 *   - 'label' (string): Column header text
 *   - 'key' (string): Data key to display from each row
 *   - 'class' (string): Optional - Additional CSS classes for column
 *   - 'format' (callable): Optional - Custom formatting function
 * @param array $rows Required - Array of data rows (associative arrays)
 * @param string $tableClass Optional - Additional table classes (default: 'table-sm table-bordered')
 * @param bool $responsive Optional - Wrap in responsive div (default: true)
 * @param string $emptyMessage Optional - Message when no data (default: 'No data available')
 * @param bool $striped Optional - Use striped rows (default: false)
 * @param bool $hover Optional - Enable hover effect (default: true)
 * @param string $uniqueId Optional - Custom ID for table (auto-generated if not provided)
 *
 * @example Basic table
 * ```php
 * $columns = [
 *     ['label' => 'Project Name', 'key' => 'project_name', 'class' => 'text-start'],
 *     ['label' => 'Available', 'key' => 'available_count', 'class' => 'text-center'],
 *     ['label' => 'In Use', 'key' => 'in_use_count', 'class' => 'text-center']
 * ];
 * $rows = [
 *     ['project_name' => 'Project Alpha', 'available_count' => 15, 'in_use_count' => 5],
 *     ['project_name' => 'Project Beta', 'available_count' => 8, 'in_use_count' => 12]
 * ];
 * include APP_ROOT . '/views/dashboard/components/data_table.php';
 * ```
 *
 * @example Table with custom formatting
 * ```php
 * $columns = [
 *     ['label' => 'Item', 'key' => 'name'],
 *     [
 *         'label' => 'Price',
 *         'key' => 'price',
 *         'class' => 'text-end',
 *         'format' => function($value) { return '$' . number_format($value, 2); }
 *     ],
 *     [
 *         'label' => 'Status',
 *         'key' => 'status',
 *         'format' => function($value) {
 *             $badge = $value === 'active' ? 'badge-success-neutral' : 'badge-neutral';
 *             return '<span class="badge ' . $badge . '">' . ucfirst($value) . '</span>';
 *         }
 *     ]
 * ];
 * $rows = [
 *     ['name' => 'Item A', 'price' => 99.99, 'status' => 'active'],
 *     ['name' => 'Item B', 'price' => 149.50, 'status' => 'inactive']
 * ];
 * include APP_ROOT . '/views/dashboard/components/data_table.php';
 * ```
 *
 * @example Striped table without responsive wrapper
 * ```php
 * $columns = [
 *     ['label' => 'ID', 'key' => 'id'],
 *     ['label' => 'Description', 'key' => 'description']
 * ];
 * $rows = $dataFromDatabase;
 * $striped = true;
 * $responsive = false;
 * $emptyMessage = 'No records found matching your criteria.';
 * include APP_ROOT . '/views/dashboard/components/data_table.php';
 * ```
 *
 * @package ConstructLink
 * @subpackage Dashboard Components
 * @version 2.1 - Neutral Design
 * @since 2025-11-02
 */

// Validate required parameters
if (!isset($columns) || !is_array($columns) || empty($columns)) {
    error_log('[Dashboard Component] data_table.php: $columns parameter is required and must be a non-empty array');
    return;
}

if (!isset($rows) || !is_array($rows)) {
    error_log('[Dashboard Component] data_table.php: $rows parameter is required and must be an array');
    return;
}

// Set defaults
$tableClass = $tableClass ?? 'table-sm table-bordered';
$responsive = $responsive ?? true;
$emptyMessage = $emptyMessage ?? 'No data available';
$striped = $striped ?? false;
$hover = $hover ?? true;
$uniqueId = $uniqueId ?? 'table-' . uniqid();

// Build table class string
$tableClasses = 'table';
if ($striped) $tableClasses .= ' table-striped';
if ($hover) $tableClasses .= ' table-hover';
if ($tableClass) $tableClasses .= ' ' . $tableClass;
?>

<?php if ($responsive): ?><div class="table-responsive"><?php endif; ?>

<table class="<?= htmlspecialchars($tableClasses) ?>"
       id="<?= htmlspecialchars($uniqueId) ?>"
       aria-label="Data table with <?= count($rows) ?> rows">

    <thead class="table-light">
        <tr>
            <?php foreach ($columns as $column): ?>
                <th scope="col" class="<?= htmlspecialchars($column['class'] ?? '') ?>">
                    <?= htmlspecialchars($column['label']) ?>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>

    <tbody>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= count($columns) ?>" class="text-center text-muted py-4">
                    <i class="bi bi-inbox me-2" aria-hidden="true"></i>
                    <?= htmlspecialchars($emptyMessage) ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $rowIndex => $row): ?>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <td class="<?= htmlspecialchars($column['class'] ?? '') ?>">
                        <?php
                        $value = $row[$column['key']] ?? '-';

                        // Apply custom formatting if provided
                        if (isset($column['format']) && is_callable($column['format'])) {
                            echo $column['format']($value);
                        } else {
                            // Default: escape and display
                            echo htmlspecialchars($value);
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>

</table>

<?php if ($responsive): ?></div><?php endif; ?>
