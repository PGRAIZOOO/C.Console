<?php
// Prints flash messages: $_SESSION['flash'] (via flash()), plus the original 'message' and 'success' keys.

$alerts = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

if (isset($_SESSION['message'])) {
    $alerts[] = ['type' => 'error', 'message' => $_SESSION['message']];
    unset($_SESSION['message']);
}
if (isset($_SESSION['success'])) {
    $alerts[] = ['type' => 'success', 'message' => $_SESSION['success']];
    unset($_SESSION['success']);
}

foreach ($alerts as $alert) {
    echo '<div class="alert ' . htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8') . '</div>';
}
