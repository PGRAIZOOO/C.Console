<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

if (!is_post()) {
    redirect('item/index.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$game = db_row('SELECT * FROM products WHERE product_id = ?', [$id]);
if (!$game) {
    flash('error', 'Game not found.');
    redirect('item/index.php');
}

if (!product_can_be_deleted($id)) {
    if ((int) $game['is_archived'] !== 1) {
        archive_product($id);
    }
    flash('info', $game['name'] . ' has order or stock history, so it was archived instead of deleted. Its records are kept.');
    redirect('item/index.php');
}

$galleryRows = product_gallery($id);
db_begin();
try {
    q('DELETE FROM product_images WHERE product_id = ?', [$id]);
    q('DELETE FROM stock_movements WHERE product_id = ?', [$id]);
    q('DELETE FROM stock WHERE product_id = ?', [$id]);
    q('DELETE FROM products WHERE product_id = ?', [$id]);
    db_commit();
} catch (Throwable $error) {
    db_rollback();
    archive_product($id);
    flash('info', $game['name'] . ' could not be deleted because other records use it, so it was archived instead.');
    redirect('item/index.php');
}
remove_image($game['game_code']);
foreach ($galleryRows as $row) {
    remove_gallery_file($row['file_name']);
}
flash('success', $game['name'] . ' was deleted.');
redirect('item/index.php');
