<?php
$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>
<!-- ... existing code ... -->
<?php if (in_array($user['role_name'], $roleConfig['procurement-orders/verify'] ?? [])): ?>
    <!-- ... existing verify form ... -->
<?php else: ?>
    <div class="alert alert-danger mt-4">You do not have permission to verify this procurement order.</div>
<?php endif; ?>
<!-- ... existing code ... --> 