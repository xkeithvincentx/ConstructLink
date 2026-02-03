<?php
// Debug user session and auth
session_start();
define('APP_ROOT', __DIR__);

echo "=== SESSION DATA ===" . PHP_EOL;
echo "Session ID: " . session_id() . PHP_EOL;
echo "Session Data:" . PHP_EOL;
print_r($_SESSION);
echo PHP_EOL;

// Load Auth class
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Auth.php';

$auth = Auth::getInstance();

echo "=== AUTH CHECK ===" . PHP_EOL;
echo "Is Authenticated: " . ($auth->isAuthenticated() ? 'YES' : 'NO') . PHP_EOL;
echo PHP_EOL;

$user = $auth->getCurrentUser();

echo "=== USER DATA ===" . PHP_EOL;
if ($user) {
    echo "User ID: " . ($user['id'] ?? 'N/A') . PHP_EOL;
    echo "User Name: " . ($user['name'] ?? 'N/A') . PHP_EOL;
    echo "User Role: " . ($user['role_name'] ?? 'N/A') . PHP_EOL;
    echo "User Role ID: " . ($user['role_id'] ?? 'N/A') . PHP_EOL;
    echo PHP_EOL;
    echo "Full User Data:" . PHP_EOL;
    print_r($user);
} else {
    echo "No user data found!" . PHP_EOL;
}

echo PHP_EOL;
echo "=== PERMISSION CHECK ===" . PHP_EOL;
echo "Has 'withdrawals/create' permission: " . ($auth->hasPermission('withdrawals/create') ? 'YES' : 'NO') . PHP_EOL;

// If user exists, check permissions array
if ($user) {
    echo PHP_EOL;
    echo "User Permissions:" . PHP_EOL;
    $permissions = $user['permissions'] ?? [];
    if (is_array($permissions)) {
        foreach ($permissions as $perm) {
            echo "  - " . $perm . PHP_EOL;
        }
    } else {
        echo "  Permissions is not an array: " . gettype($permissions) . PHP_EOL;
    }
}
