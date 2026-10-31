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
$review = get_review($productId, $user['id']);
if (!$review) {
    flash('error', 'You have not reviewed this game yet.');
    redirect('game.php?id=' . $productId);
}

$old = $_SESSION['review_old'] ?? ['rating' => (string) $review['rating'], 'title' => (string) $review['title'], 'review' => $review['review']];
$photos = review_images($productId, $user['id']);
$errors = $_SESSION['review_errors'] ?? [];
unset($_SESSION['review_old'], $_SESSION['review_errors']);

$title = 'Edit review';
include('../includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>Edit your review</h1>
        <p><?= e($game['name']) ?></p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('game.php?id=' . $productId)) ?>">Back to game</a>
</div>

<div class="card" style="max-width:720px">
    <form method="post" action="<?= e(url('review/update.php')) ?>" enctype="multipart/form-data" class="form" novalidate>
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
        <?php if ($photos): ?>
            <div class="field">
                <label>Current photos</label>
                <div class="gallery-admin">
                    <?php foreach ($photos as $photo): ?>
                        <div class="gallery-item">
                            <img src="<?= e(review_image_url($photo['file_name'])) ?>" alt="">
                            <label><input type="checkbox" name="remove_images[]" value="<?= (int) $photo['image_id'] ?>"> Remove</label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="images">Add photos (optional)</label>
            <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
            <small>Up to <?= REVIEW_IMAGE_MAX ?> photos per review. JPG, PNG or WEBP, up to 3 MB each.</small>
            <?= field_error($errors, 'images') ?>
        </div>
        <div style="display:flex;gap:10px">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <a class="btn btn-ghost" href="<?= e(url('game.php?id=' . $productId)) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php include('../includes/footer.php'); ?>
