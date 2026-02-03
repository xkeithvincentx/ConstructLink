<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT id, name, permissions FROM roles WHERE name = ?');
$stmt->execute(['Warehouseman']);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if ($role) {
    echo 'Role: ' . $role['name'] . PHP_EOL;
    echo 'Permissions (raw): ' . $role['permissions'] . PHP_EOL;
    echo PHP_EOL;
    echo 'Permissions (decoded):' . PHP_EOL;
    $perms = json_decode($role['permissions'], true);
    if (is_array($perms)) {
        foreach ($perms as $perm) {
            echo '  - ' . $perm . PHP_EOL;
        }
        echo PHP_EOL;
        echo 'Has withdrawals/create: ' . (in_array('withdrawals/create', $perms) ? 'YES' : 'NO') . PHP_EOL;
    } else {
        echo 'Permissions is not an array!' . PHP_EOL;
    }
} else {
    echo 'Role not found!' . PHP_EOL;
}
