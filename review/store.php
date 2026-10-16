<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

if (!is_post()) {
    redirect('index.php');
}
csrf_verify();

$productId = (int) ($_POST['product_id'] ?? 0);
$game = db_row(products_sql('p.product_id = ? AND p.is_active = 1'), [$productId]);
if (!$game) {
    flash('error', 'That game could not be found.');
    redirect('index.php');
}
if (get_review($productId, $user['id'])) {
    flash('error', 'You already reviewed this game. Update your review instead.');
    redirect('review/edit.php?product_id=' . $productId);
}
if (!can_review($user['id'], $productId)) {
    flash('error', 'Only customers with a confirmed online order for this game can review it.');
    redirect('game.php?id=' . $productId);
}

$old = read_review_form();
$errors = validate_review_form($old);
if ($errors) {
    $_SESSION['review_old'] = $old;
    $_SESSION['review_errors'] = $errors;
    redirect('review/create.php?product_id=' . $productId);
}

$masked = mask_bad_words($old['review']);
q(
    'INSERT INTO product_reviews (product_id, user_id, rating, review, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
    [$productId, $user['id'], (int) $old['rating'], $masked, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
);
flash('success', 'Thanks! Your review of ' . $game['name'] . ' was posted.');
if ($masked !== $old['review']) {
    flash('info', 'Some words in your review were masked.');
}
redirect('game.php?id=' . $productId . '#reviews');
