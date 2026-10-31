<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

if (!is_post()) {
    redirect('item/create.php');
}
if (upload_too_large()) {
    flash('error', 'The upload was too large. Try fewer or smaller photos.');
    redirect('item/create.php');
}
csrf_verify();

$old = read_product_form();
$errors = validate_product_form($old);

$imageExt = null;
$galleryFiles = [];
if (!$errors) {
    try {
        $imageExt = validate_image($_FILES['image'] ?? []);
    } catch (RuntimeException $error) {
        $errors['image'] = $error->getMessage();
    }
    try {
        $galleryFiles = validate_gallery($_FILES['images'] ?? []);
    } catch (RuntimeException $error) {
        $errors['images'] = $error->getMessage();
    }
}

if ($errors) {
    $_SESSION['item_old'] = $old;
    $_SESSION['item_errors'] = $errors;
    redirect('item/create.php');
}

db_begin();
try {
    q(
        'INSERT INTO products (game_code, name, game_studio, genre, description, unit_cost_price, unit_selling_price, min_stock_threshold, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $old['game_code'],
            $old['name'],
            $old['game_studio'] !== '' ? $old['game_studio'] : null,
            $old['genre'] !== '' ? $old['genre'] : null,
            $old['description'] !== '' ? $old['description'] : null,
            round((float) $old['unit_cost_price'], 2),
            round((float) $old['unit_selling_price'], 2),
            (int) $old['min_stock_threshold'],
            $old['is_active'],
        ]
    );
    $productId = db_insert_id();
    if ((int) $old['quantity'] > 0) {
        adjust_stock($productId, (int) $old['quantity'], 'Initial stock');
    } else {
        q('INSERT INTO stock (product_id, quantity) VALUES (?, 0)', [$productId]);
    }
    db_commit();
} catch (Throwable $error) {
    db_rollback();
    throw $error;
}

if ($imageExt !== null) {
    try {
        store_image($_FILES['image'], $imageExt, $old['game_code']);
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
    }
}

if ($galleryFiles) {
    try {
        store_gallery($productId, $galleryFiles);
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
    }
}

flash('success', $old['name'] . ' was added.');
redirect('item/index.php');
