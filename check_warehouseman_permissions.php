<?php
/**
 * Temporary script to check Warehouseman permissions
 */

define('APP_ROOT', __DIR__);

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Autoloader.php';
require_once APP_ROOT . '/core/helpers.php';

$autoloader = new Autoloader();
$autoloader->register();

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare('SELECT id, name, permissions FROM roles WHERE name = ?');
$stmt->execute(['Warehouseman']);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if ($role) {
    echo "Role: " . $role['name'] . PHP_EOL;
    echo "ID: " . $role['id'] . PHP_EOL;
    echo "Permissions JSON: " . $role['permissions'] . PHP_EOL . PHP_EOL;

    $perms = json_decode($role['permissions'], true);

    if (is_array($perms)) {
        echo "Total permissions: " . count($perms) . PHP_EOL . PHP_EOL;

        echo "Withdrawal-related permissions:" . PHP_EOL;
        foreach ($perms as $perm) {
            if (stripos($perm, 'withdrawal') !== false) {
                echo "  - " . $perm . PHP_EOL;
            }
        }

        echo PHP_EOL . "Has 'withdrawals/create'? " . (in_array('withdrawals/create', $perms) ? 'YES' : 'NO') . PHP_EOL;

        echo PHP_EOL . "All permissions:" . PHP_EOL;
        foreach ($perms as $perm) {
            echo "  - " . $perm . PHP_EOL;
        }
    } else {
        echo "(No permissions or invalid JSON)" . PHP_EOL;
    }
} else {
    echo "Warehouseman role not found" . PHP_EOL;
}
