<?php
// Test role checking for withdrawals/create-batch
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Simulate the session
$_SESSION = [
    'user_id' => 1,
    'user_role' => 'Warehouseman',
    'user_role_id' => 6
];

define('APP_ROOT', getcwd());

// Test the route
$roleConfig = require APP_ROOT . '/config/roles.php';

if (!function_exists('getRolesFor')) {
    function getRolesFor($routeKey) {
        global $roleConfig;

        if (isset($roleConfig[$routeKey]) && is_array($roleConfig[$routeKey]) && !isset($roleConfig[$routeKey]['maker'])) {
            return $roleConfig[$routeKey];
        }

        if (isset($roleConfig[$routeKey]) && is_array($roleConfig[$routeKey])) {
            $roles = [];
            foreach (['maker', 'verifier', 'authorizer', 'viewer'] as $mvaRole) {
                if (isset($roleConfig[$routeKey][$mvaRole])) {
                    $roles = array_merge($roles, $roleConfig[$routeKey][$mvaRole]);
                }
            }
            return array_unique($roles);
        }

        return ['System Admin'];
    }
}

$routes = require APP_ROOT . '/routes.php';

// Simulate Router role check
$route = 'withdrawals/create-batch';
$routeConfig = $routes[$route];

echo 'Route Configuration:' . PHP_EOL;
echo '===================' . PHP_EOL;
echo 'Route: ' . $route . PHP_EOL;
echo 'Allowed Roles: ' . json_encode($routeConfig['roles']) . PHP_EOL;
echo 'User Role: Warehouseman' . PHP_EOL;
echo PHP_EOL;

$userRole = 'Warehouseman';
$allowedRoles = $routeConfig['roles'];

echo 'Access Check:' . PHP_EOL;
echo '=============' . PHP_EOL;
echo 'Is Warehouseman in allowed roles? ' . (in_array($userRole, $allowedRoles) ? 'YES' : 'NO') . PHP_EOL;
echo PHP_EOL;

// Check exact match
echo 'Detailed Check:' . PHP_EOL;
echo '===============' . PHP_EOL;
foreach ($allowedRoles as $role) {
    echo '- Role: ' . $role . ' | Match: ' . ($role === $userRole ? 'YES' : 'NO') . ' | Comparison: "' . $role . '" === "' . $userRole . '"' . PHP_EOL;
}
