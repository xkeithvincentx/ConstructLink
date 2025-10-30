<?php
/**
 * Inventory Management Index View
 *
 * DATABASE MAPPING NOTE:
 * - This view displays "Inventory" to users
 * - Backend uses AssetController and `assets` database table
 * - See controllers/AssetController.php header for full mapping documentation
 */

// Load required helper classes
require_once APP_ROOT . '/helpers/AssetStatus.php';
require_once APP_ROOT . '/helpers/AssetWorkflowStatus.php';
require_once APP_ROOT . '/helpers/UnitHelper.php';

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<?php include __DIR__ . '/partials/_action_buttons.php'; ?>

<?php include __DIR__ . '/partials/_messages.php'; ?>

<?php include __DIR__ . '/partials/_statistics_cards.php'; ?>

<?php include __DIR__ . '/partials/_workflow_cards.php'; ?>

<?php include __DIR__ . '/partials/_alerts.php'; ?>

<?php include __DIR__ . '/partials/_filters.php'; ?>

<?php include __DIR__ . '/partials/_asset_list.php'; ?>

<?php include __DIR__ . '/partials/_javascript.php'; ?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Inventory - ConstructLink™';
$pageHeader = 'Inventory Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Inventory', 'url' => '?route=assets']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
