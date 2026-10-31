<?php
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/reports.php');
require_admin();

$filters = transaction_filters($_GET);
[$whereSql, $params] = transaction_where($filters);
$live = LIVE_STATUSES;

if (($_GET['export'] ?? '') === 'csv') {
    $all = db_all(transaction_select() . ' WHERE ' . $whereSql . ' ORDER BY o.order_date DESC, o.order_id DESC', $params);
    $out = [];
    foreach ($all as $row) {
        $out[] = [
            $row['order_id'],
            date('Y-m-d H:i:s', strtotime($row['order_date'])),
            $row['full_name'],
            $row['email'],
            transaction_channel($row),
            (string) $row['games'],
            (int) $row['items'],
            number_format((float) $row['subtotal'], 2, '.', ''),
            number_format(max(0, (float) $row['subtotal'] - (float) $row['total_amount']), 2, '.', ''),
            number_format((float) $row['total_amount'], 2, '.', ''),
            number_format((float) $row['cash_tendered'], 2, '.', ''),
            number_format((float) $row['change_amount'], 2, '.', ''),
            $row['status'],
        ];
    }
    csv_download(
        'transaction-history-' . date('Ymd-His') . '.csv',
        ['Order', 'Date', 'Customer', 'Email', 'Channel', 'Games', 'Items', 'Subtotal', 'Discount', 'Total', 'Cash tendered', 'Change', 'Status'],
        $out
    );
}

$stats = db_row(
    "SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN o.status IN $live THEN o.total_amount ELSE 0 END), 0) AS counted,
            COALESCE(SUM(CASE WHEN o.status IN $live THEN 1 ELSE 0 END), 0) AS counted_orders
     FROM orders o INNER JOIN users u ON u.user_id = o.user_id
     WHERE " . $whereSql,
    $params
);
$total = (int) $stats['total'];
$pages = max(1, (int) ceil($total / TRANSACTIONS_PER_PAGE));
$page = min($filters['page'], $pages);
$rows = db_all(
    transaction_select() . ' WHERE ' . $whereSql . ' ORDER BY o.order_date DESC, o.order_id DESC LIMIT ' . TRANSACTIONS_PER_PAGE . ' OFFSET ' . (($page - 1) * TRANSACTIONS_PER_PAGE),
    $params
);
$customers = db_all('SELECT DISTINCT u.user_id, u.full_name, u.email FROM users u INNER JOIN orders o ON o.user_id = u.user_id ORDER BY u.full_name, u.user_id');

$query = array_filter(
    ['q' => $filters['q'], 'status' => $filters['status'], 'from' => $filters['from'], 'to' => $filters['to'], 'customer' => $filters['customer'] ?: ''],
    fn($value) => $value !== ''
);
$pageUrl = fn(int $n): string => url('admin/transactions.php?' . http_build_query($query + ['page' => $n]));
$exportUrl = url('admin/transactions.php?' . http_build_query($query + ['export' => 'csv']));
$filtered = $query !== [];

$title = 'Transaction history';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Transaction history</h1>
        <p><?= number_format($total) ?> transaction<?= $total === 1 ? '' : 's' ?> · <?= money($stats['counted']) ?> in sales across <?= (int) $stats['counted_orders'] ?> pending or confirmed order<?= (int) $stats['counted_orders'] === 1 ? '' : 's' ?></p>
    </div>
    <div style="display:flex;gap:10px" class="no-print">
        <a class="btn btn-ghost" href="<?= e($exportUrl) ?>">Export CSV</a>
        <button class="btn btn-ghost" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-ghost" href="<?= e(url('admin/sales.php')) ?>">Sales summary</a>
    </div>
</div>

<form class="filters six no-print" method="get">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Order number, customer, email or game">
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
        <label for="customer">Customer</label>
        <select id="customer" name="customer">
            <option value="0">All customers</option>
            <?php foreach ($customers as $customer): ?>
                <option value="<?= (int) $customer['user_id'] ?>" <?= $filters['customer'] === (int) $customer['user_id'] ? 'selected' : '' ?>><?= e($customer['full_name']) ?> (<?= e($customer['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (ORDER_STATUSES as $item): ?>
                <option value="<?= e($item) ?>" <?= $filters['status'] === $item ? 'selected' : '' ?>><?= e($item) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary" type="submit">Apply</button>
        <?php if ($filtered): ?>
            <a class="btn btn-ghost" href="<?= e(url('admin/transactions.php')) ?>">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <?php if (!$rows): ?>
        <div class="empty"><h3>No transactions found</h3><p><?= $filtered ? 'Try different filters.' : 'Transactions will appear here as orders are placed.' ?></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Order</th><th>Date</th><th>Customer</th><th>Channel</th><th>Games</th><th class="num">Items</th><th class="num">Discount</th><th class="num">Total</th><th>Payment</th><th>Status</th><th class="no-print"></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $discount = max(0, (float) $row['subtotal'] - (float) $row['total_amount']); ?>
                        <tr>
                            <td><strong>#<?= (int) $row['order_id'] ?></strong></td>
                            <td><?= e(date('M j, Y g:i A', strtotime($row['order_date']))) ?></td>
                            <td><?= e($row['full_name']) ?><small style="display:block"><?= e($row['email']) ?></small></td>
                            <td><?= e(transaction_channel($row)) ?></td>
                            <td><?= (string) $row['games'] !== '' ? e($row['games']) : '—' ?></td>
                            <td class="num"><?= (int) $row['items'] ?></td>
                            <td class="num"><?= $discount > 0 ? money($discount) : '—' ?></td>
                            <td class="num"><?= money($row['total_amount']) ?></td>
                            <td><?= (float) $row['cash_tendered'] > 0 ? 'Cash ' . e(money($row['cash_tendered'])) . '<small style="display:block">Change ' . e(money($row['change_amount'])) . '</small>' : '—' ?></td>
                            <td><?= status_badge($row['status']) ?></td>
                            <td class="actions-cell no-print"><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/orderDetails.php?id=' . $row['order_id'])) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="pager no-print" aria-label="Pages">
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
