<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

if (!is_post()) {
    redirect('item/index.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$game = db_row('SELECT product_id, name, is_archived FROM products WHERE product_id = ?', [$id]);
if (!$game) {
    flash('error', 'Game not found.');
    redirect('item/index.php');
}

if ($action === 'archive') {
    if (archive_product($id)) {
        flash('success', $game['name'] . ' was archived. It is hidden from the shop and keeps all order and stock history.');
    } else {
        flash('info', $game['name'] . ' is already archived.');
    }
} elseif ($action === 'restore') {
    if (restore_product($id)) {
        flash('success', $game['name'] . ' was restored. It stays hidden from the shop until you tick "Show this game in the shop" on its edit page.');
    } else {
        flash('info', $game['name'] . ' is not archived.');
    }
} else {
    flash('error', 'Choose a valid action.');
}
redirect('item/index.php' . ($action === 'restore' ? '?visibility=archived' : ''));
