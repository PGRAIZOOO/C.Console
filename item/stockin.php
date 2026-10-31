<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$filters = stock_in_filters($_GET);
[$whereSql, $params] = stock_in_where($filters);
$from = 'FROM stock_movements m INNER JOIN products p ON p.product_id = m.product_id LEFT JOIN users u ON u.user_id = m.created_by WHERE ' . $whereSql;

$total = (int) db_val('SELECT COUNT(*) ' . $from, $params);
$units = (int) db_val('SELECT COALESCE(SUM(m.quantity_change), 0) ' . $from, $params);
$pages = max(1, (int) ceil($total / STOCK_HISTORY_PER_PAGE));
$page = min($filters['page'], $pages);
$rows = db_all(
    'SELECT m.movement_id, m.movement_code, m.movement_date, m.quantity_change, m.reason, p.product_id, p.name, p.game_code, u.full_name AS admin_name '
    . $from . ' ORDER BY m.movement_date DESC, m.movement_id DESC LIMIT ' . STOCK_HISTORY_PER_PAGE . ' OFFSET ' . (($page - 1) * STOCK_HISTORY_PER_PAGE),
    $params
);

$reasons = array_merge(['Initial stock'], STOCK_REASONS);
$admins = db_all("SELECT user_id, full_name FROM users WHERE role = 'Admin' ORDER BY full_name");
$product = $filters['product'] > 0 ? db_row('SELECT name FROM products WHERE product_id = ?', [$filters['product']]) : null;

$query = array_filter(
    ['q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to'], 'reason' => $filters['reason'], 'admin' => $filters['admin'] ?: '', 'product' => $filters['product'] ?: ''],
    fn($value) => $value !== ''
);
$pageUrl = fn(int $n): string => url('item/stockin.php?' . http_build_query($query + ['page' => $n]));
$filtered = $query !== [];

$title = 'Stock-in history';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Stock-in history</h1>
        <p><?= number_format($total) ?> stock-in record<?= $total === 1 ? '' : 's' ?> · <?= number_format($units) ?> unit<?= $units === 1 ? '' : 's' ?> added<?= $product ? ' · ' . e($product['name']) : '' ?></p>
    </div>
    <div style="display:flex;gap:10px">
        <a class="btn btn-ghost" href="<?= e(url('item/lowstock.php')) ?>">Low stock report</a>
        <a class="btn btn-ghost" href="<?= e(url('item/index.php')) ?>">Back to games</a>
    </div>
</div>

<form class="filters six" method="get">
    <?php if ($filters['product'] > 0): ?>
        <input type="hidden" name="product" value="<?= (int) $filters['product'] ?>">
    <?php endif; ?>
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Title, studio, code or movement">
    </div>
    <div class="field">
        <label for="from">From</label>
        <input type="date" id="from" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
        <label for="to">To</label>
        <input type="date" id="to" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <div class="field">
        <label for="reason">Reason</label>
        <select id="reason" name="reason">
            <option value="">All reasons</option>
            <?php foreach ($reasons as $reason): ?>
                <option value="<?= e($reason) ?>" <?= $filters['reason'] === $reason ? 'selected' : '' ?>><?= e($reason) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="admin">Administrator</label>
        <select id="admin" name="admin">
            <option value="0">All administrators</option>
            <?php foreach ($admins as $admin): ?>
                <option value="<?= (int) $admin['user_id'] ?>" <?= $filters['admin'] === (int) $admin['user_id'] ? 'selected' : '' ?>><?= e($admin['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary" type="submit">Apply</button>
        <?php if ($filtered): ?>
            <a class="btn btn-ghost" href="<?= e(url('item/stockin.php')) ?>">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <?php if (!$rows): ?>
        <div class="empty"><h3>No stock-in records found</h3><p><?= $filtered ? 'Try different filters.' : 'Stock added to a game will appear here.' ?></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Date</th><th>Game</th><th class="num">Quantity added</th><th>Reason</th><th>Administrator</th><th>Movement</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e(date('M j, Y g:i A', strtotime($row['movement_date']))) ?></td>
                            <td><a href="<?= e(url('item/edit.php?id=' . $row['product_id'])) ?>"><?= e($row['name']) ?></a><small style="display:block"><?= e($row['game_code']) ?></small></td>
                            <td class="num">+<?= (int) $row['quantity_change'] ?></td>
                            <td><?= (string) $row['reason'] !== '' ? e($row['reason']) : '—' ?></td>
                            <td><?= (string) $row['admin_name'] !== '' ? e($row['admin_name']) : '—' ?></td>
                            <td><?= e($row['movement_code']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
        <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($pageUrl($page - 1)) ?>">&larr; Prev</a><?php endif; ?>
        <?php for ($n = 1; $n <= $pages; $n++): ?>
            <?php if ($n === 1 || $n === $pages || abs($n - $page) <= 1): ?>
                <a class="btn btn-sm <?= $n === $page ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e($pageUrl($n)) ?>" <?= $n === $page ? 'aria-current="page"' : '' ?>><?= $n ?></a>
            <?php elseif (abs($n - $page) === 2): ?>
                <span class="gap">…</span>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= e($pageUrl($page + 1)) ?>">Next &rarr;</a><?php endif; ?>
    </nav>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
