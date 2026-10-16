<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

$orders = db_all(
    'SELECT o.order_id, o.order_date, o.status, o.total_amount,
            (SELECT COALESCE(SUM(ol.quantity), 0) FROM orderline ol WHERE ol.order_id = o.order_id) AS items
     FROM orders o WHERE o.user_id = ? ORDER BY o.order_id DESC',
    [$user['id']]
);

$title = 'My orders';
include('../includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>My orders</h1>
        <p>Track everything you have purchased.</p>
    </div>
</div>
<?php if (!$orders): ?>
    <div class="card empty">
        <h3>No orders yet</h3>
        <p style="margin-bottom:16px">When you place an order it will show up here.</p>
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Browse games</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Order</th><th>Date</th><th class="num">Items</th><th class="num">Total</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= (int) $order['order_id'] ?></strong></td>
                            <td><?= e(date('M j, Y g:i A', strtotime($order['order_date']))) ?></td>
                            <td class="num"><?= (int) $order['items'] ?></td>
                            <td class="num"><?= money($order['total_amount']) ?></td>
                            <td><?= status_badge($order['status']) ?></td>
                            <td class="actions-cell"><a class="btn btn-ghost btn-sm" href="<?= e(url('user/order.php?id=' . $order['order_id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
