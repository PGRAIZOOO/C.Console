<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

if (!is_post()) {
    redirect('index.php');
}
if (upload_too_large()) {
    flash('error', 'The upload was too large. Try fewer or smaller photos.');
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
$removeIds = array_map('intval', array_filter((array) ($_POST['remove_images'] ?? []), 'is_scalar'));
$imageFiles = [];
if (!$errors) {
    try {
        $imageFiles = validate_review_images($_FILES['images'] ?? [], review_image_slots_left($productId, $user['id'], $removeIds));
    } catch (RuntimeException $error) {
        $errors['images'] = $error->getMessage();
    }
}
if ($errors) {
    $_SESSION['review_old'] = $old;
    $_SESSION['review_errors'] = $errors;
    redirect('review/edit.php?product_id=' . $productId);
}

$masked = mask_bad_words($old['review']);
$maskedTitle = mask_bad_words($old['title']);
q(
    'UPDATE product_reviews SET rating = ?, title = ?, review = ?, updated_at = ? WHERE product_id = ? AND user_id = ?',
    [(int) $old['rating'], $maskedTitle, $masked, date('Y-m-d H:i:s'), $productId, $user['id']]
);
if ($removeIds) {
    remove_review_images($productId, $user['id'], $removeIds);
}
if ($imageFiles) {
    try {
        store_review_images($productId, $user['id'], $imageFiles);
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
    }
}
flash('success', 'Your review was updated.');
if ($masked !== $old['review'] || $maskedTitle !== $old['title']) {
    flash('info', 'Some words in your review were masked.');
}
redirect('game.php?id=' . $productId . '#reviews');
