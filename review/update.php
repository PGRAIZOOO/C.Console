<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

if (!is_post()) {
    redirect('index.php');
}
csrf_verify();

$productId = (int) ($_POST['product_id'] ?? 0);
if (!get_review($productId, $user['id'])) {
    flash('error', 'You have not reviewed this game yet.');
    redirect('game.php?id=' . $productId);
}

$old = read_review_form();
$errors = validate_review_form($old);
if ($errors) {
    $_SESSION['review_old'] = $old;
    $_SESSION['review_errors'] = $errors;
    redirect('review/edit.php?product_id=' . $productId);
}

$masked = mask_bad_words($old['review']);
q(
    'UPDATE product_reviews SET rating = ?, review = ?, updated_at = ? WHERE product_id = ? AND user_id = ?',
    [(int) $old['rating'], $masked, date('Y-m-d H:i:s'), $productId, $user['id']]
);
flash('success', 'Your review was updated.');
if ($masked !== $old['review']) {
    flash('info', 'Some words in your review were masked.');
}
redirect('game.php?id=' . $productId . '#reviews');
