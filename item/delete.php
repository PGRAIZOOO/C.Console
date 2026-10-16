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
if ($game) {
    $used = (int) db_val('SELECT COUNT(*) FROM orderline WHERE product_id = ?', [$id]);
    if ($used > 0) {
        q('UPDATE products SET is_active = 0 WHERE product_id = ?', [$id]);
        flash('info', $game['name'] . ' is part of ' . $used . ' order line' . ($used === 1 ? '' : 's') . ', so it was hidden from the shop instead of deleted.');
    } else {
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
            throw $error;
        }
        remove_image($game['game_code']);
        foreach ($galleryRows as $row) {
            remove_gallery_file($row['file_name']);
        }
        flash('success', $game['name'] . ' was deleted.');
    }
}
redirect('item/index.php');
