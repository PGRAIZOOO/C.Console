<?php
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/reports.php');
require_admin();

$filters = sales_filters($_GET);
[$rows, $totals, $excluded] = sales_summary($filters);

if (($_GET['export'] ?? '') === 'csv') {
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            sales_period_label($filters['period'], $row['period_start']),
            (int) $row['orders'],
            (int) $row['units'],
            number_format((float) $row['gross'], 2, '.', ''),
            number_format((float) $row['discounts'], 2, '.', ''),
            number_format((float) $row['net'], 2, '.', ''),
            number_format((float) $row['cost'], 2, '.', ''),
            number_format((float) $row['net'] - (float) $row['cost'], 2, '.', ''),
        ];
    }
    $out[] = [
        'Total', $totals['orders'], $totals['units'],
        number_format($totals['gross'], 2, '.', ''), number_format($totals['discounts'], 2, '.', ''),
        number_format($totals['net'], 2, '.', ''), number_format($totals['cost'], 2, '.', ''),
        number_format($totals['profit'], 2, '.', ''),
    ];
    csv_download(
        'sales-summary-' . $filters['period'] . '-' . $filters['from'] . '-to-' . $filters['to'] . '.csv',
        ['Period', 'Orders', 'Units sold', 'Gross sales', 'Discounts', 'Net sales', 'Cost of goods', 'Profit'],
        $out
    );
}

$excludedCount = 0;
foreach ($excluded as $item) {
    $excludedCount += (int) $item['orders'];
}
$exportQuery = http_build_query(['period' => $filters['period'], 'from' => $filters['from'], 'to' => $filters['to'], 'export' => 'csv']);

$title = 'Sales summary';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Sales summary</h1>
        <p><?= e(REPORT_PERIODS[$filters['period']]) ?> · <?= e(date('M j, Y', strtotime($filters['from']))) ?> to <?= e(date('M j, Y', strtotime($filters['to']))) ?></p>
    </div>
    <div style="display:flex;gap:10px" class="no-print">
        <a class="btn btn-ghost" href="<?= e(url('admin/sales.php?' . $exportQuery)) ?>">Export CSV</a>
        <button class="btn btn-ghost" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-ghost" href="<?= e(url('admin/transactions.php')) ?>">Transactions</a>
    </div>
</div>

<form class="filters no-print" method="get">
    <div class="field">
        <label for="period">Report</label>
        <select id="period" name="period">
            <?php foreach (REPORT_PERIODS as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filters['period'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="from">From</label>
        <input type="date" id="from" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
        <label for="to">To</label>
        <input type="date" id="to" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <button class="btn btn-primary" type="submit">Apply</button>
</form>

<div class="stats">
    <div class="stat"><span>Net sales</span><strong><?= money($totals['net']) ?></strong></div>
    <div class="stat"><span>Orders</span><strong><?= number_format($totals['orders']) ?></strong></div>
    <div class="stat"><span>Units sold</span><strong><?= number_format($totals['units']) ?></strong></div>
    <div class="stat"><span>Average order</span><strong><?= money($totals['average']) ?></strong></div>
    <div class="stat"><span>Discounts given</span><strong><?= money($totals['discounts']) ?></strong></div>
    <div class="stat"><span>Profit</span><strong><?= money($totals['profit']) ?></strong></div>
</div>

<div class="card">
    <?php if (!$rows): ?>
        <div class="empty"><h3>No sales found</h3><p>There are no pending or confirmed orders in this date range.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th><?= e(REPORT_PERIODS[$filters['period']]) ?></th><th class="num">Orders</th><th class="num">Units</th><th class="num">Gross sales</th><th class="num">Discounts</th><th class="num">Net sales</th><th class="num">Cost</th><th class="num">Profit</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><strong><?= e(sales_period_label($filters['period'], $row['period_start'])) ?></strong></td>
                            <td class="num"><?= (int) $row['orders'] ?></td>
                            <td class="num"><?= (int) $row['units'] ?></td>
                            <td class="num"><?= money($row['gross']) ?></td>
                            <td class="num"><?= money($row['discounts']) ?></td>
                            <td class="num"><?= money($row['net']) ?></td>
                            <td class="num"><?= money($row['cost']) ?></td>
                            <td class="num"><?= money((float) $row['net'] - (float) $row['cost']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="num"><?= number_format($totals['orders']) ?></th>
                        <th class="num"><?= number_format($totals['units']) ?></th>
                        <th class="num"><?= money($totals['gross']) ?></th>
                        <th class="num"><?= money($totals['discounts']) ?></th>
                        <th class="num"><?= money($totals['net']) ?></th>
                        <th class="num"><?= money($totals['cost']) ?></th>
                        <th class="num"><?= money($totals['profit']) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<p style="margin-top:16px;color:var(--muted);font-size:13px">
    Sales count pending and confirmed orders, the same as the dashboard. Cancelled and voided orders are excluded<?php if ($excludedCount > 0): ?>
        (<?= $excludedCount ?> in this range:
        <?php
        $parts = [];
        foreach ($excluded as $item) {
            $parts[] = (int) $item['orders'] . ' ' . strtolower($item['status']) . ' worth ' . money($item['amount']);
        }
        echo e(implode(', ', $parts));
        ?>)<?php endif; ?>.
    Net sales are order totals after discounts; cost uses each game's current cost price.<?= $filters['period'] === 'weekly' ? ' Weeks start on Monday.' : '' ?>
</p>
<?php include('../includes/footer.php'); ?>
