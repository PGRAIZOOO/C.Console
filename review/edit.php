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

$old = $_SESSION['review_old'] ?? ['rating' => (string) $review['rating'], 'review' => $review['review']];
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
    <form method="post" action="<?= e(url('review/update.php')) ?>" class="form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= $productId ?>">
        <div class="field">
            <span class="field-label">Rating</span>
            <?= star_input_html((string) $old['rating']) ?>
            <?= field_error($errors, 'rating') ?>
        </div>
        <div class="field">
            <label for="review">Your review</label>
            <textarea id="review" name="review" placeholder="What did you think of the game?"><?= e($old['review']) ?></textarea>
            <small>Offensive English words are masked with asterisks automatically.</small>
            <?= field_error($errors, 'review') ?>
        </div>
        <div style="display:flex;gap:10px">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <a class="btn btn-ghost" href="<?= e(url('game.php?id=' . $productId)) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php include('../includes/footer.php'); ?>
