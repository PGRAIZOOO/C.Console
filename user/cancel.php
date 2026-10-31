<?php
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/mailer.php');
$user = require_login();
block_admin_purchase('admin/orders.php');

if (!is_post()) {
    redirect('user/myorders.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$order = db_row('SELECT order_id, status, date_needed FROM orders WHERE order_id = ? AND user_id = ?', [$id, $user['id']]);
if (!$order) {
    flash('error', 'Order not found.');
    redirect('user/myorders.php');
}
if (!can_cancel_order($order)) {
    flash('error', 'Order #' . $id . ' can no longer be cancelled because it is already ' . strtolower($order['status']) . '.');
    redirect('user/order.php?id=' . $id);
}

try {
    change_order_status($id, 'Cancelled');
} catch (RuntimeException $error) {
    flash('error', $error->getMessage());
    redirect('user/order.php?id=' . $id);
}

$message = 'Order #' . $id . ' was cancelled.';
try {
    if (send_order_status_email($id)) {
        $message .= ' A confirmation email was sent to you.';
    }
    flash('success', $message);
} catch (Throwable $error) {
    error_log('Cancellation email failed: ' . $error->getMessage());
    flash('success', $message);
    flash('info', 'The cancellation email could not be sent.');
}
redirect('user/order.php?id=' . $id);
