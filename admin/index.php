<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$today = date('Y-m-d 00:00:00');
$month = date('Y-m-01 00:00:00');
$live = LIVE_STATUSES;

$salesToday = (float) db_val("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN $live AND order_date >= ?", [$today]);
$salesMonth = (float) db_val("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN $live AND order_date >= ?", [$month]);
$costMonth = (float) db_val(
    "SELECT COALESCE(SUM(l.quantity * p.unit_cost_price), 0)
     FROM orderline l
     INNER JOIN orders o ON o.order_id = l.order_id
     INNER JOIN products p ON p.product_id = l.product_id
     WHERE o.status IN $live AND o.order_date >= ?",
    [$month]
);
$profitMonth = $salesMonth - $costMonth;
$pending = (int) db_val("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$stockCounts = stock_counts();
$lowCount = $stockCounts['low'];
$outCount = $stockCounts['out'];
$inventory = (float) db_val('SELECT COALESCE(SUM(t.quantity * t.unit_cost_price), 0) FROM (' . products_sql('p.is_archived = 0') . ') t');

$recent = db_all(
    'SELECT o.order_id, o.order_date, o.status, o.total_amount, o.date_needed, u.full_name
     FROM orders o INNER JOIN users u ON u.user_id = o.user_id
     ORDER BY o.order_id DESC LIMIT 50'
);
$low = stock_list('low');
$out = stock_list('out');
$top = db_all(
    "SELECT p.name, p.game_studio, SUM(l.quantity) AS sold
     FROM orderline l
     INNER JOIN products p ON p.product_id = l.product_id
     INNER JOIN orders o ON o.order_id = l.order_id
     WHERE o.status IN $live
     GROUP BY p.product_id, p.name, p.game_studio
     ORDER BY sold DESC, p.name ASC LIMIT 20"
);

$title = 'Dashboard';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p><?= e(date('l, F j, Y')) ?></p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('admin/pos.php')) ?>">New sale</a>
</div>

<div class="stats seven">
    <div class="stat"><span>Sales today</span><strong><?= money($salesToday) ?></strong></div>
    <div class="stat"><span>Sales this month</span><strong><?= money($salesMonth) ?></strong></div>
    <div class="stat"><span>Profit this month</span><strong><?= money($profitMonth) ?></strong></div>
    <div class="stat"><span>Pending orders</span><strong><?= $pending ?></strong></div>
    <div class="stat <?= $lowCount ? 'warn-stat' : '' ?>"><span>Low stock</span><strong><?= $lowCount ?></strong></div>
    <div class="stat <?= $outCount ? 'alert-stat' : '' ?>"><span>Out of stock</span><strong><?= $outCount ?></strong></div>
    <div class="stat"><span>Inventory value (cost)</span><strong><?= money($inventory) ?></strong></div>
</div>

<div class="two">
    <div class="card">
        <div class="card-head"><h2>Recent orders</h2><a href="<?= e(url('admin/orders.php')) ?>">View all</a></div>
        <?php if (!$recent): ?>
            <div class="empty">No orders yet.</div>
        <?php else: ?>
            <div class="table-wrap scroll-list">
                <table>
                    <thead><tr><th>Order</th><th>Customer</th><th class="num">Total</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $order): ?>
                            <tr>
                                <td><a href="<?= e(url('admin/orderDetails.php?id=' . $order['order_id'])) ?>">#<?= (int) $order['order_id'] ?></a></td>
                                <td><?= e($order['full_name']) ?></td>
                                <td class="num"><?= money($order['total_amount']) ?></td>
                                <td><?= status_badge($order['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head"><h2>Low stock <span class="badge warn"><?= $lowCount ?></span></h2><a href="<?= e(url('item/lowstock.php')) ?>">View all</a></div>
        <?php if (!$low): ?>
            <div class="empty">No games are running low.</div>
        <?php else: ?>
            <div class="table-wrap scroll-list">
                <table>
                    <thead><tr><th>Game</th><th class="num">Left</th><th class="num">Reorder at</th></tr></thead>
                    <tbody>
                        <?php foreach ($low as $game): ?>
                            <tr>
                                <td><a href="<?= e(url('item/edit.php?id=' . $game['product_id'])) ?>"><?= e($game['name']) ?></a><small style="display:block"><?= e($game['game_studio']) ?></small></td>
                                <td class="num"><span class="badge warn"><?= (int) $game['quantity'] ?></span></td>
                                <td class="num"><?= (int) $game['threshold'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="two" style="margin-top:24px">
    <div class="card">
        <div class="card-head"><h2>Out of stock <span class="badge bad"><?= $outCount ?></span></h2><a href="<?= e(url('item/index.php?stock=out')) ?>">View all</a></div>
        <?php if (!$out): ?>
            <div class="empty">No games are out of stock.</div>
        <?php else: ?>
            <div class="table-wrap scroll-list">
                <table>
                    <thead><tr><th>Game</th><th class="num">Left</th><th class="num">Reorder at</th></tr></thead>
                    <tbody>
                        <?php foreach ($out as $game): ?>
                            <tr>
                                <td><a href="<?= e(url('item/edit.php?id=' . $game['product_id'])) ?>"><?= e($game['name']) ?></a><small style="display:block"><?= e($game['game_studio']) ?></small></td>
                                <td class="num"><span class="badge bad">0</span></td>
                                <td class="num"><?= (int) $game['threshold'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
    <div class="card-head"><h2>Best sellers</h2></div>
    <?php if (!$top): ?>
        <div class="empty">Sales will appear here once orders come in.</div>
    <?php else: ?>
        <div class="table-wrap scroll-list">
            <table>
                <thead><tr><th>Game</th><th>Studio</th><th class="num">Units sold</th></tr></thead>
                <tbody>
                    <?php foreach ($top as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['game_studio'] ?: '—') ?></td>
                            <td class="num"><?= (int) $row['sold'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
