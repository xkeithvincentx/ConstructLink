<?php
/**
 * ConstructLink™ Unit Helper
 *
 * This helper class provides utility functions for unit formatting and pluralization
 * to ensure consistent unit display across the application.
 *
 * Usage:
 *   - UnitHelper::pluralize(5, 'box') => 'boxes'
 *   - UnitHelper::pluralize(1, 'piece') => 'piece'
 *   - UnitHelper::formatQuantityWithUnit(10, 'kg') => '10 kg'
 *
 * Benefits:
 *   - Consistent unit formatting
 *   - Proper pluralization rules
 *   - Reusable across all modules
 *   - Easy to extend with new units
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class UnitHelper {
    /**
     * Properly pluralize units based on quantity
     *
     * @param int|float $quantity The quantity value
     * @param string $unit The unit to pluralize
     * @return string The properly pluralized unit
     */
    public static function pluralize($quantity, $unit) {
        $quantity = (int)$quantity;

        // Units that don't change in plural (mass nouns / measurement units)
        $unchangeable = ['kg', 'lbs', 'liters', 'gallons', 'meters', 'm', 'ft', 'cm', 'mm', 'km', 'ml', 'oz', 'g'];
        if (in_array(strtolower($unit), $unchangeable)) {
            return $unit;
        }

        // Special plural forms
        $specialPlurals = [
            'pc' => 'pcs',
            'piece' => 'pieces',
            'box' => 'boxes',
            'bag' => 'bags',
            'roll' => 'rolls',
            'bottle' => 'bottles',
            'can' => 'cans',
            'pack' => 'packs',
            'set' => 'sets',
            'unit' => 'units',
            'item' => 'items',
            'sheet' => 'sheets',
            'bundle' => 'bundles',
            'container' => 'containers',
            'carton' => 'cartons',
            'pallet' => 'pallets',
            'drum' => 'drums',
            'barrel' => 'barrels',
            'sack' => 'sacks',
            'tube' => 'tubes',
            'pair' => 'pairs'
        ];

        $lowerUnit = strtolower($unit);

        // Return singular or plural based on quantity
        if ($quantity == 1) {
            // For quantity of 1, use singular
            // Find the singular form from the plural mapping
            $singular = array_search($lowerUnit, array_map('strtolower', $specialPlurals));
            return $singular !== false ? $singular : $unit;
        } else {
            // For quantity > 1, use plural
            return $specialPlurals[$lowerUnit] ?? $unit;
        }
    }

    /**
     * Format quantity with properly pluralized unit
     *
     * @param int|float $quantity The quantity value
     * @param string $unit The unit
     * @param bool $includeSpace Include space between quantity and unit (default: true)
     * @return string Formatted string like "5 boxes" or "1 piece"
     */
    public static function formatQuantityWithUnit($quantity, $unit, $includeSpace = true) {
        $formattedQuantity = number_format($quantity, 0);
        $pluralizedUnit = self::pluralize($quantity, $unit);
        $separator = $includeSpace ? ' ' : '';
        return $formattedQuantity . $separator . $pluralizedUnit;
    }

    /**
     * Get singular form of a unit
     *
     * @param string $unit The potentially plural unit
     * @return string The singular form
     */
    public static function getSingularForm($unit) {
        return self::pluralize(1, $unit);
    }

    /**
     * Get plural form of a unit
     *
     * @param string $unit The potentially singular unit
     * @return string The plural form
     */
    public static function getPluralForm($unit) {
        return self::pluralize(2, $unit);
    }

    /**
     * Check if unit is a measurement unit (unchangeable)
     *
     * @param string $unit The unit to check
     * @return bool True if unit is a measurement unit
     */
    public static function isMeasurementUnit($unit) {
        $unchangeable = ['kg', 'lbs', 'liters', 'gallons', 'meters', 'm', 'ft', 'cm', 'mm', 'km', 'ml', 'oz', 'g'];
        return in_array(strtolower($unit), $unchangeable);
    }

    /**
     * Validate if a unit is recognized by the system
     *
     * @param string $unit The unit to validate
     * @return bool True if unit is valid
     */
    public static function isValidUnit($unit) {
        $unchangeable = ['kg', 'lbs', 'liters', 'gallons', 'meters', 'm', 'ft', 'cm', 'mm', 'km', 'ml', 'oz', 'g'];
        $specialPlurals = [
            'pc', 'pcs', 'piece', 'pieces', 'box', 'boxes', 'bag', 'bags', 'roll', 'rolls',
            'bottle', 'bottles', 'can', 'cans', 'pack', 'packs', 'set', 'sets', 'unit', 'units',
            'item', 'items', 'sheet', 'sheets', 'bundle', 'bundles', 'container', 'containers',
            'carton', 'cartons', 'pallet', 'pallets', 'drum', 'drums', 'barrel', 'barrels',
            'sack', 'sacks', 'tube', 'tubes', 'pair', 'pairs'
        ];

        $lowerUnit = strtolower($unit);
        return in_array($lowerUnit, $unchangeable) || in_array($lowerUnit, $specialPlurals);
    }
}
