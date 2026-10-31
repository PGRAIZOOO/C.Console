<?php
const REPORT_PERIODS = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
const TRANSACTIONS_PER_PAGE = 25;
const REPORT_MAX_DAYS = 1830;

function report_date($value): string
{
    $value = is_string($value) ? trim($value) : '';
    $parsed = DateTime::createFromFormat('Y-m-d', $value);
    return $parsed && $parsed->format('Y-m-d') === $value ? $value : '';
}

function sales_filters(array $input): array
{
    $period = is_string($input['period'] ?? null) && isset(REPORT_PERIODS[$input['period']]) ? $input['period'] : 'daily';
    $today = new DateTime('today');
    $defaultFrom = match ($period) {
        'weekly' => (clone $today)->modify('-83 days'),
        'monthly' => (clone $today)->modify('first day of this month')->modify('-11 months'),
        default => (clone $today)->modify('-29 days'),
    };
    $from = report_date($input['from'] ?? '');
    $to = report_date($input['to'] ?? '');
    if ($to === '') {
        $to = $today->format('Y-m-d');
    }
    if ($from === '') {
        $from = $defaultFrom->format('Y-m-d');
    }
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
    $earliest = (new DateTime($to))->modify('-' . REPORT_MAX_DAYS . ' days')->format('Y-m-d');
    if ($from < $earliest) {
        $from = $earliest;
    }
    return ['period' => $period, 'from' => $from, 'to' => $to];
}

function sales_period_sql(string $period): string
{
    return match ($period) {
        'weekly' => 'DATE_SUB(DATE(o.order_date), INTERVAL WEEKDAY(o.order_date) DAY)',
        'monthly' => 'DATE_SUB(DATE(o.order_date), INTERVAL DAYOFMONTH(o.order_date) - 1 DAY)',
        default => 'DATE(o.order_date)',
    };
}

function sales_summary(array $filters): array
{
    $live = LIVE_STATUSES;
    $rows = db_all(
        'SELECT t.period_start, COUNT(*) AS orders, SUM(t.units) AS units, SUM(t.subtotal) AS gross,
                SUM(t.subtotal - t.total_amount) AS discounts, SUM(t.total_amount) AS net, SUM(t.cost) AS cost
         FROM (
             SELECT ' . sales_period_sql($filters['period']) . " AS period_start, o.order_id, o.total_amount,
                    COALESCE(l.units, 0) AS units, COALESCE(l.subtotal, 0) AS subtotal, COALESCE(l.cost, 0) AS cost
             FROM orders o
             LEFT JOIN (
                 SELECT ol.order_id, SUM(ol.quantity) AS units, SUM(ol.quantity * ol.unit_price) AS subtotal,
                        SUM(ol.quantity * p.unit_cost_price) AS cost
                 FROM orderline ol INNER JOIN products p ON p.product_id = ol.product_id
                 GROUP BY ol.order_id
             ) l ON l.order_id = o.order_id
             WHERE o.status IN $live AND o.order_date >= ? AND o.order_date <= ?
         ) t
         GROUP BY t.period_start
         ORDER BY t.period_start DESC",
        [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59']
    );
    $totals = ['orders' => 0, 'units' => 0, 'gross' => 0.0, 'discounts' => 0.0, 'net' => 0.0, 'cost' => 0.0];
    foreach ($rows as $row) {
        $totals['orders'] += (int) $row['orders'];
        $totals['units'] += (int) $row['units'];
        $totals['gross'] += (float) $row['gross'];
        $totals['discounts'] += (float) $row['discounts'];
        $totals['net'] += (float) $row['net'];
        $totals['cost'] += (float) $row['cost'];
    }
    foreach (['gross', 'discounts', 'net', 'cost'] as $key) {
        $totals[$key] = round($totals[$key], 2);
    }
    $totals['profit'] = round($totals['net'] - $totals['cost'], 2);
    $totals['average'] = $totals['orders'] > 0 ? round($totals['net'] / $totals['orders'], 2) : 0.0;
    $excluded = db_all(
        "SELECT status, COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS amount
         FROM orders
         WHERE status NOT IN $live AND order_date >= ? AND order_date <= ?
         GROUP BY status",
        [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59']
    );
    return [$rows, $totals, $excluded];
}

function sales_period_label(string $period, string $start): string
{
    $time = strtotime($start);
    return match ($period) {
        'weekly' => 'Week of ' . date('M j, Y', $time),
        'monthly' => date('F Y', $time),
        default => date('D, M j, Y', $time),
    };
}

function transaction_filters(array $input): array
{
    $q = is_string($input['q'] ?? null) ? mb_substr(trim($input['q']), 0, 60) : '';
    $status = is_string($input['status'] ?? null) && in_array($input['status'], ORDER_STATUSES, true) ? $input['status'] : '';
    $from = report_date($input['from'] ?? '');
    $to = report_date($input['to'] ?? '');
    if ($from !== '' && $to !== '' && $from > $to) {
        [$from, $to] = [$to, $from];
    }
    return [
        'q' => $q,
        'status' => $status,
        'from' => $from,
        'to' => $to,
        'customer' => is_scalar($input['customer'] ?? null) ? max(0, (int) $input['customer']) : 0,
        'page' => is_scalar($input['page'] ?? null) ? max(1, (int) $input['page']) : 1,
    ];
}

function transaction_where(array $filters): array
{
    $where = ['1 = 1'];
    $params = [];
    if ($filters['q'] !== '') {
        $like = '%' . like_escape(ltrim($filters['q'], '#')) . '%';
        $where[] = '(CAST(o.order_id AS CHAR) = ? OR u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?
            OR EXISTS (SELECT 1 FROM orderline x INNER JOIN products px ON px.product_id = x.product_id
                       WHERE x.order_id = o.order_id AND (px.name LIKE ? OR px.game_code LIKE ?)))';
        array_push($params, ltrim($filters['q'], '#'), $like, $like, $like, $like, $like);
    }
    if ($filters['status'] !== '') {
        $where[] = 'o.status = ?';
        $params[] = $filters['status'];
    }
    if ($filters['customer'] > 0) {
        $where[] = 'o.user_id = ?';
        $params[] = $filters['customer'];
    }
    if ($filters['from'] !== '') {
        $where[] = 'o.order_date >= ?';
        $params[] = $filters['from'] . ' 00:00:00';
    }
    if ($filters['to'] !== '') {
        $where[] = 'o.order_date <= ?';
        $params[] = $filters['to'] . ' 23:59:59';
    }
    return [implode(' AND ', $where), $params];
}

function transaction_select(): string
{
    return "SELECT o.order_id, o.order_date, o.date_needed, o.status, o.discount_type, o.discount_value,
                o.cash_tendered, o.change_amount, o.total_amount, u.user_id, u.full_name, u.email,
                COALESCE(l.items, 0) AS items, COALESCE(l.subtotal, 0) AS subtotal,
                (SELECT GROUP_CONCAT(CONCAT(p.name, ' x', ol.quantity) ORDER BY p.name SEPARATOR ', ')
                 FROM orderline ol INNER JOIN products p ON p.product_id = ol.product_id
                 WHERE ol.order_id = o.order_id) AS games
         FROM orders o
         INNER JOIN users u ON u.user_id = o.user_id
         LEFT JOIN (
             SELECT order_id, SUM(quantity) AS items, SUM(quantity * unit_price) AS subtotal
             FROM orderline GROUP BY order_id
         ) l ON l.order_id = o.order_id";
}

function transaction_channel(array $row): string
{
    return $row['date_needed'] === null ? 'In-store' : 'Online';
}

function csv_cell($value): string
{
    $text = (string) $value;
    if ($text !== '' && strpos("=+-@\t\r", $text[0]) !== false) {
        $text = "'" . $text;
    }
    return '"' . str_replace('"', '""', $text) . '"';
}

function csv_download(string $fileName, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo "\xEF\xBB\xBF" . implode(',', array_map('csv_cell', $header)) . "\r\n";
    foreach ($rows as $row) {
        echo implode(',', array_map('csv_cell', $row)) . "\r\n";
    }
    exit;
}
