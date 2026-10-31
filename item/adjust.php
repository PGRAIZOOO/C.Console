<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

if (!is_post()) {
    redirect('item/index.php');
}
csrf_verify();

$returnTo = ltrim(safe_back('/item/index.php'), '/');
$id = (int) ($_POST['id'] ?? 0);
$direction = (string) ($_POST['direction'] ?? '');
$stepRaw = is_string($_POST['step'] ?? null) ? trim($_POST['step']) : '1';
$reason = post_text('reason');

$game = db_row(products_sql('p.product_id = ?'), [$id]);
if (!$game) {
    flash('error', 'Game not found.');
    redirect($returnTo);
}
if (!in_array($direction, ['up', 'down'], true)) {
    flash('error', 'Choose the up or down arrow to adjust the stock.');
    redirect($returnTo);
}
if (!preg_match('/^\d{1,4}$/', $stepRaw) || (int) $stepRaw < 1 || (int) $stepRaw > STOCK_STEP_MAX) {
    flash('error', 'The amount must be a whole number from 1 to ' . number_format(STOCK_STEP_MAX) . '.');
    redirect($returnTo);
}
if ($reason === '') {
    flash('error', 'Enter the reason for changing the stock of ' . $game['name'] . '.');
    redirect($returnTo);
}
if (mb_strlen($reason) > 255) {
    flash('error', 'The reason must be 255 characters or fewer.');
    redirect($returnTo);
}

$step = (int) $stepRaw;
$delta = $direction === 'up' ? $step : -$step;

db_begin();
try {
    $before = stock_on_hand($id);
    if ($before + $delta < 0) {
        db_rollback();
        flash('error', 'Stock cannot go below 0. ' . $game['name'] . ' only has ' . $before . ' on hand.');
        redirect($returnTo);
    }
    if ($before + $delta > STOCK_MAX) {
        db_rollback();
        flash('error', 'Stock cannot be more than ' . number_format(STOCK_MAX) . '.');
        redirect($returnTo);
    }
    if (!adjust_stock($id, $delta, $reason)) {
        throw new RuntimeException('The stock level could not be updated.');
    }
    db_commit();
} catch (Throwable $error) {
    db_rollback();
    flash('error', $error instanceof RuntimeException ? $error->getMessage() : 'The stock level could not be updated.');
    redirect($returnTo);
}

$_SESSION['last_stock_reason'] = $reason;
$after = stock_on_hand($id);
flash('success', $game['name'] . ' stock changed from ' . $before . ' to ' . $after . ' (' . ($delta > 0 ? '+' : '') . $delta . '). Reason: ' . $reason);
redirect($returnTo);
