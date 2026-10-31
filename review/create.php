<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

$productId = (int) ($_GET['product_id'] ?? 0);
$game = db_row(products_sql('p.product_id = ? AND p.is_active = 1'), [$productId]);
if (!$game) {
    flash('error', 'That game could not be found.');
    redirect('index.php');
}
if (get_review($productId, $user['id'])) {
    flash('info', 'You already reviewed this game. You can update your review here.');
    redirect('review/edit.php?product_id=' . $productId);
}
if (!can_review($user['id'], $productId)) {
    flash('error', 'Only customers with a confirmed online order for this game can review it.');
    redirect('game.php?id=' . $productId);
}

$old = $_SESSION['review_old'] ?? ['rating' => '', 'title' => '', 'review' => ''];
$errors = $_SESSION['review_errors'] ?? [];
unset($_SESSION['review_old'], $_SESSION['review_errors']);

$title = 'Review ' . $game['name'];
include('../includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>Write a review</h1>
        <p><?= e($game['name']) ?></p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('game.php?id=' . $productId)) ?>">Back to game</a>
</div>

<div class="card" style="max-width:720px">
    <form method="post" action="<?= e(url('review/store.php')) ?>" enctype="multipart/form-data" class="form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= $productId ?>">
        <div class="field">
            <span class="field-label">Rating</span>
            <?= star_input_html((string) $old['rating']) ?>
            <?= field_error($errors, 'rating') ?>
        </div>
        <div class="field">
            <label for="title">Review title</label>
            <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" placeholder="Sum up your review in a few words">
            <?= field_error($errors, 'title') ?>
        </div>
        <div class="field">
            <label for="review">Your review</label>
            <textarea id="review" name="review" placeholder="What did you think of the game?"><?= e($old['review']) ?></textarea>
            <?= field_error($errors, 'review') ?>
        </div>
        <div class="field">
            <label for="images">Photos (optional)</label>
            <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
            <small>Up to <?= REVIEW_IMAGE_MAX ?> photos. JPG, PNG or WEBP, up to 3 MB each.</small>
            <?= field_error($errors, 'images') ?>
        </div>
        <div style="display:flex;gap:10px">
            <button class="btn btn-primary" type="submit">Post review</button>
            <a class="btn btn-ghost" href="<?= e(url('game.php?id=' . $productId)) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php include('../includes/footer.php'); ?>
