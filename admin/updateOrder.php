<?php
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/mailer.php');
require_admin();

if (!is_post()) {
    redirect('admin/orders.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
try {
    change_order_status($id, $status);
} catch (RuntimeException $error) {
    flash('error', $error->getMessage());
    redirect('admin/orderDetails.php?id=' . $id);
}

$message = 'Order #' . $id . ' marked as ' . $status . '.';
try {
    if (send_order_status_email($id)) {
        $message .= ' A receipt was emailed to the customer.';
    }
    flash('success', $message);
} catch (Throwable $error) {
    flash('success', $message);
    flash('error', 'The status was saved, but the email was not sent: ' . $error->getMessage());
}
redirect('admin/orderDetails.php?id=' . $id);
