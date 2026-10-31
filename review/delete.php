<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

if (!is_post()) {
    redirect('index.php');
}
csrf_verify();

$productId = (int) ($_POST['product_id'] ?? 0);
$targetId = $user['id'];
if (is_admin() && isset($_POST['user_id']) && is_scalar($_POST['user_id'])) {
    $targetId = (int) $_POST['user_id'];
}
if (get_review($productId, $targetId)) {
    delete_review($productId, $targetId);
    flash('success', $targetId === $user['id'] ? 'Your review was deleted.' : 'The review was removed.');
}
redirect('game.php?id=' . $productId . '#reviews');
