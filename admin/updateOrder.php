<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

if (!is_post()) {
    redirect('admin/orders.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
try {
    change_order_status($id, $status);
    flash('success', 'Order #' . $id . ' marked as ' . $status . '.');
} catch (RuntimeException $error) {
    flash('error', $error->getMessage());
}
redirect('admin/orderDetails.php?id=' . $id);
