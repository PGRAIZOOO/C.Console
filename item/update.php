<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

if (!is_post()) {
    redirect('item/index.php');
}
if (upload_too_large()) {
    flash('error', 'The upload was too large. Try fewer or smaller photos.');
    redirect('item/index.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$game = db_row('SELECT * FROM products WHERE product_id = ?', [$id]);
if (!$game) {
    flash('error', 'Game not found.');
    redirect('item/index.php');
}

$old = read_product_form();
$errors = validate_product_form($old, $id);

$removeIds = array_map('intval', array_filter((array) ($_POST['remove_images'] ?? []), 'is_scalar'));

$imageExt = null;
$galleryFiles = [];
if (!$errors) {
    try {
        $imageExt = validate_image($_FILES['image'] ?? []);
    } catch (RuntimeException $error) {
        $errors['image'] = $error->getMessage();
    }
    try {
        $galleryFiles = validate_gallery($_FILES['images'] ?? [], gallery_slots_left($id, $removeIds));
    } catch (RuntimeException $error) {
        $errors['images'] = $error->getMessage();
    }
}

if ($errors) {
    $_SESSION['item_old'] = $old;
    $_SESSION['item_old_id'] = $id;
    $_SESSION['item_errors'] = $errors;
    redirect('item/edit.php?id=' . $id);
}

db_begin();
try {
    q(
        'UPDATE products SET game_code = ?, name = ?, game_studio = ?, genre = ?, unit_cost_price = ?, unit_selling_price = ?, min_stock_threshold = ?, is_active = ? WHERE product_id = ?',
        [
            $old['game_code'],
            $old['name'],
            $old['game_studio'] !== '' ? $old['game_studio'] : null,
            $old['genre'] !== '' ? $old['genre'] : null,
            round((float) $old['unit_cost_price'], 2),
            round((float) $old['unit_selling_price'], 2),
            (int) $old['min_stock_threshold'],
            $old['is_active'],
            $id,
        ]
    );
    set_stock($id, (int) $old['quantity']);
    db_commit();
} catch (Throwable $error) {
    db_rollback();
    throw $error;
}

if ($old['game_code'] !== $game['game_code']) {
    rename_image($game['game_code'], $old['game_code']);
}
if ($imageExt !== null) {
    try {
        store_image($_FILES['image'], $imageExt, $old['game_code']);
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
    }
}

if ($removeIds) {
    remove_gallery_images($id, $removeIds);
}
if ($galleryFiles) {
    try {
        store_gallery($id, $galleryFiles);
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
    }
}

flash('success', $old['name'] . ' was updated.');
redirect('item/index.php');
