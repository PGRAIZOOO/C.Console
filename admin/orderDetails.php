<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$order = db_row(
    'SELECT o.*, u.full_name, u.username, u.email, u.contact_number, u.address
     FROM orders o INNER JOIN users u ON u.user_id = o.user_id
     WHERE o.order_id = ?',
    [$id]
);
if (!$order) {
    flash('error', 'Order not found.');
    redirect('admin/orders.php');
}
$lines = db_all(
    'SELECT p.product_id, p.name, p.game_code, p.game_studio, p.unit_cost_price, ol.quantity, ol.unit_price
     FROM orderline ol INNER JOIN products p ON p.product_id = ol.product_id
     WHERE ol.order_id = ? ORDER BY p.name',
    [$id]
);

$subtotal = 0;
$cost = 0;
foreach ($lines as $line) {
    $subtotal += $line['quantity'] * $line['unit_price'];
    $cost += $line['quantity'] * $line['unit_cost_price'];
}
$total = (float) $order['total_amount'];
$discount = max(0, $subtotal - $total);
$profit = $total - $cost;
$isWalkin = order_channel($order['date_needed']) === 'walk-in';
$next = ORDER_FLOW[$order['status']] ?? [];

$title = 'Order #' . $id;
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Order #<?= $id ?></h1>
        <p><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?> · <?= $isWalkin ? 'Walk-in' : 'Online' ?><?= $order['date_needed'] ? ' · needed by ' . e(date('F j, Y', strtotime($order['date_needed']))) : '' ?></p>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
        <?= status_badge($order['status']) ?>
        <a class="btn btn-ghost" href="<?= e(url('admin/orders.php')) ?>">Back to orders</a>
    </div>
</div>

<div class="layout">
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Game</th><th class="num">Price</th><th class="num">Qty</th><th class="num">Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                        <tr>
                            <td>
                                <div class="cell">
                                    <?= cover($line, 'thumb') ?>
                                    <div><strong><?= e($line['name']) ?></strong><small><?= e($line['game_code']) ?></small></div>
                                </div>
                            </td>
                            <td class="num"><?= money($line['unit_price']) ?></td>
                            <td class="num"><?= (int) $line['quantity'] ?></td>
                            <td class="num"><?= money($line['unit_price'] * $line['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <aside>
        <div class="card summary">
            <h2 style="margin-bottom:12px">Summary</h2>
            <div class="line"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <?php if ($order['discount_type'] !== 'None'): ?>
                <div class="line"><span>Discount<?= $order['discount_type'] === 'Percentage' ? ' (' . rtrim(rtrim(number_format((float) $order['discount_value'], 2), '0'), '.') . '%)' : '' ?></span><span>-<?= money($discount) ?></span></div>
            <?php endif; ?>
            <div class="line total"><span>Total</span><span><?= money($total) ?></span></div>
            <?php if ((float) $order['cash_tendered'] > 0): ?>
                <div class="line"><span>Cash tendered</span><span><?= money($order['cash_tendered']) ?></span></div>
                <div class="line"><span>Change</span><span><?= money($order['change_amount']) ?></span></div>
            <?php endif; ?>
            <div class="line"><span>Gross profit</span><span><?= money($profit) ?></span></div>
        </div>

        <div class="card details-list">
            <h2 style="color:var(--ink);margin-bottom:8px"><?= $isWalkin ? 'Cashier' : 'Customer' ?></h2>
            <?php if ($isWalkin): ?>
                <strong>Walk-in customer</strong>
                <span>Recorded by <?= e($order['full_name']) ?></span>
            <?php else: ?>
                <strong><?= e($order['full_name']) ?></strong>
                <span><?= e($order['email']) ?></span>
                <span><?= e($order['contact_number']) ?></span>
                <span><?= e($order['address']) ?></span>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="margin-bottom:12px">Update status</h2>
            <?php if (!$next): ?>
                <p class="details-list">This order is <?= e(strtolower($order['status'])) ?> and can no longer be changed.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('admin/updateOrder.php')) ?>" class="form" data-confirm="Update this order?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="field">
                        <label for="status">New status</label>
                        <select id="status" name="status">
                            <?php foreach ($next as $item): ?>
                                <option value="<?= e($item) ?>"><?= e($item) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Cancelling or voiding returns the items to stock.</small>
                    </div>
                    <button class="btn btn-primary" type="submit">Update order</button>
                </form>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php include('../includes/footer.php'); ?>
