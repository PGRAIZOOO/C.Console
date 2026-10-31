<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = ['1 = 1'];
$params = [];
if (in_array($status, ORDER_STATUSES, true)) {
    $where[] = 's.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(CAST(s.order_id AS CHAR) = ? OR s.customer_name LIKE ? OR s.username LIKE ? OR s.email LIKE ?)';
    array_push($params, ltrim($search, '#'), '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
}

// Reads the order_summary VIEW (orders + users + orderline joined, totals per order). See C.Console.sql.
$orders = db_all(
    'SELECT s.order_id, s.order_date, s.date_needed, s.status, s.total_amount, s.customer_name AS full_name, s.items
     FROM order_summary s
     WHERE ' . implode(' AND ', $where) . ' ORDER BY s.order_id DESC',
    $params
);

$title = 'Orders';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Orders</h1>
        <p><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('admin/pos.php')) ?>">New sale</a>
</div>

<form class="filters" method="get">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Order number, name or email">
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (ORDER_STATUSES as $item): ?>
                <option value="<?= e($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= e($item) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Apply</button>
</form>

<div class="card">
    <?php if (!$orders): ?>
        <div class="empty"><h3>No orders found</h3><p>Orders will appear here as customers check out.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Order</th><th>Date</th><th>Customer</th><th class="num">Items</th><th class="num">Total</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= (int) $order['order_id'] ?></strong></td>
                            <td><?= e(date('M j, Y g:i A', strtotime($order['order_date']))) ?></td>
                            <td><?= e($order['full_name']) ?></td>
                            <td class="num"><?= (int) $order['items'] ?></td>
                            <td class="num"><?= money($order['total_amount']) ?></td>
                            <td><?= status_badge($order['status']) ?></td>
                            <td class="actions-cell"><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/orderDetails.php?id=' . $order['order_id'])) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include('../includes/footer.php'); ?>
