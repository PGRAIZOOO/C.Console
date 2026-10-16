<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

if (!is_post()) {
    redirect('index.php');
}
csrf_verify();

$productId = (int) ($_POST['product_id'] ?? 0);
if (get_review($productId, $user['id'])) {
    q('DELETE FROM product_reviews WHERE product_id = ? AND user_id = ?', [$productId, $user['id']]);
    flash('success', 'Your review was deleted.');
}
redirect('game.php?id=' . $productId . '#reviews');
