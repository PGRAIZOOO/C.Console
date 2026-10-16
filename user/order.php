<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
$order = db_row('SELECT * FROM orders WHERE order_id = ? AND user_id = ?', [$id, $user['id']]);
if (!$order) {
    flash('error', 'Order not found.');
    redirect('user/myorders.php');
}
$lines = db_all(
    'SELECT p.product_id, p.name, p.game_code, p.game_studio, ol.quantity, ol.unit_price
     FROM orderline ol INNER JOIN products p ON p.product_id = ol.product_id
     WHERE ol.order_id = ? ORDER BY p.name',
    [$id]
);
$reviewed = array_column(db_all('SELECT product_id FROM product_reviews WHERE user_id = ?', [$user['id']]), 'product_id');
$canReviewOrder = $order['status'] === 'Confirmed' && $order['date_needed'] !== null;
$account = current_account();
$subtotal = 0;
foreach ($lines as $line) {
    $subtotal += $line['quantity'] * $line['unit_price'];
}
$discount = max(0, $subtotal - (float) $order['total_amount']);

$title = 'Order #' . $id;
include('../includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>Order #<?= $id ?></h1>
        <p><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?><?= $order['date_needed'] ? ' · needed by ' . e(date('F j, Y', strtotime($order['date_needed']))) : '' ?></p>
    </div>
    <div><?= status_badge($order['status']) ?></div>
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
                                    <div>
                                        <strong><?= e($line['name']) ?></strong><small><?= e($line['game_studio'] ?: $line['game_code']) ?></small>
                                        <?php if ($canReviewOrder): ?>
                                            <?php if (in_array((int) $line['product_id'], array_map('intval', $reviewed), true)): ?>
                                                <small><a href="<?= e(url('review/edit.php?product_id=' . $line['product_id'])) ?>">Edit your review</a></small>
                                            <?php else: ?>
                                                <small><a href="<?= e(url('review/create.php?product_id=' . $line['product_id'])) ?>">Write a review</a></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
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
            <div class="line total"><span>Total</span><span><?= money($order['total_amount']) ?></span></div>
        </div>
        <div class="card details-list">
            <h2 style="color:var(--ink);margin-bottom:8px">Delivery</h2>
            <strong><?= e($account['full_name']) ?></strong>
            <span><?= e($account['email']) ?></span>
            <span><?= e($account['contact_number']) ?></span>
            <span><?= e($account['address']) ?></span>
        </div>
    </aside>
</div>
<p style="margin-top:20px"><a href="<?= e(url('user/myorders.php')) ?>">&larr; All orders</a></p>
<?php include('../includes/footer.php'); ?>
