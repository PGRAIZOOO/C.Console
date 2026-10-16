<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$status = $_GET['status'] ?? '';
$channel = $_GET['channel'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = ['1 = 1'];
$params = [];
if (in_array($status, ORDER_STATUSES, true)) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($channel === 'online') {
    $where[] = 'o.date_needed IS NOT NULL';
} elseif ($channel === 'walk-in') {
    $where[] = 'o.date_needed IS NULL';
}
if ($search !== '') {
    $where[] = '(CAST(o.order_id AS CHAR) = ? OR u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    array_push($params, ltrim($search, '#'), '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
}

$orders = db_all(
    'SELECT o.order_id, o.order_date, o.date_needed, o.status, o.total_amount, u.full_name,
            (SELECT COALESCE(SUM(ol.quantity), 0) FROM orderline ol WHERE ol.order_id = o.order_id) AS items
     FROM orders o INNER JOIN users u ON u.user_id = o.user_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY o.order_id DESC',
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
    <div class="field">
        <label for="channel">Channel</label>
        <select id="channel" name="channel">
            <option value="">All channels</option>
            <option value="online" <?= $channel === 'online' ? 'selected' : '' ?>>Online</option>
            <option value="walk-in" <?= $channel === 'walk-in' ? 'selected' : '' ?>>Walk-in</option>
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
                    <tr><th>Order</th><th>Date</th><th>Customer</th><th>Channel</th><th class="num">Items</th><th class="num">Total</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php $online = order_channel($order['date_needed']) === 'online'; ?>
                        <tr>
                            <td><strong>#<?= (int) $order['order_id'] ?></strong></td>
                            <td><?= e(date('M j, Y g:i A', strtotime($order['order_date']))) ?></td>
                            <td><?= $online ? e($order['full_name']) : 'Walk-in customer' ?></td>
                            <td><?= $online ? '<span class="badge info">Online</span>' : '<span class="badge">Walk-in</span>' ?></td>
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
