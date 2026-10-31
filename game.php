<?php
include('./includes/config.php');
include('./includes/functions.php');

$id = (int) ($_GET['id'] ?? 0);
$game = db_row(products_sql('p.product_id = ? AND p.is_active = 1'), [$id]);
if (!$game) {
    flash('error', 'That game could not be found.');
    redirect('shop.php');
}

$viewer = current_user();
$summary = review_summary($id);
$reviews = product_reviews($id);
$reviewImages = product_review_images($id);
$myReview = $viewer ? get_review($id, $viewer['id']) : null;
$canReview = $viewer && !$myReview && can_review($viewer['id'], $id);

$title = $game['name'];
include('./includes/header.php');
?>
<p style="margin-bottom:16px"><a href="<?= e(url('shop.php')) ?>">&larr; Back to shop</a></p>
<div class="detail">
    <?= gallery_html($game) ?>
    <div class="info">
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <span class="badge info"><?= e($game['product_type']) ?></span>
            <?php if ($game['genre']): ?>
                <span class="badge"><?= e($game['genre']) ?></span>
            <?php endif; ?>
        </div>
        <h1><?= e($game['name']) ?></h1>
        <?php if ($game['game_studio']): ?>
            <p style="color:var(--muted)"><?= e($game['game_studio']) ?> · <?= e($game['game_code']) ?></p>
        <?php endif; ?>
        <div class="price"><?= money($game['unit_selling_price']) ?></div>
        <div><?= stock_badge($game) ?></div>
        <?php if (trim((string) $game['description']) !== ''): ?>
            <div class="description">
                <h2>About this game</h2>
                <p><?= nl2br(e($game['description'])) ?></p>
            </div>
        <?php endif; ?>
        <?php if (is_admin()): ?>
            <p class="review-note">Administrator accounts cannot place customer orders.</p>
        <?php elseif ((int) $game['quantity'] > 0): ?>
            <form method="post" action="<?= e(url('cart_update.php')) ?>" class="row" style="grid-template-columns:110px 1fr;align-items:end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $game['product_id'] ?>">
                <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                <div class="field">
                    <label for="qty">Quantity</label>
                    <input type="number" id="qty" name="qty" value="1" min="1" max="<?= (int) $game['quantity'] ?>">
                </div>
                <button class="btn btn-primary" type="submit">Add to cart</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<section class="card reviews" id="reviews">
    <div class="card-head">
        <h2>Customer reviews</h2>
        <?php if ($summary['total'] > 0): ?>
            <div class="rating-summary">
                <?= stars_html((int) round($summary['average'])) ?>
                <strong><?= number_format($summary['average'], 1) ?></strong>
                <span>(<?= $summary['total'] ?> review<?= $summary['total'] === 1 ? '' : 's' ?>)</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($canReview): ?>
        <p><a class="btn btn-primary btn-sm" href="<?= e(url('review/create.php?product_id=' . $id)) ?>">Write a review</a></p>
    <?php elseif (!$viewer): ?>
        <p class="review-note"><a href="<?= e(url('user/login.php')) ?>">Sign in</a> to review a game you ordered online.</p>
    <?php elseif (!$myReview): ?>
        <p class="review-note">Only customers with a confirmed online order for this game can review it.</p>
    <?php endif; ?>

    <?php if (!$reviews): ?>
        <div class="empty">No reviews yet.</div>
    <?php endif; ?>
    <?php foreach ($reviews as $review): ?>
        <?php $mine = $viewer && (int) $review['user_id'] === $viewer['id']; ?>
        <article class="review">
            <?= user_avatar($review, 'md') ?>
            <div class="review-body">
                <div class="review-head">
                    <strong><?= e($review['full_name']) ?></strong>
                    <?php if ($mine): ?><span class="badge info">Your review</span><?php endif; ?>
                    <?= stars_html((int) $review['rating']) ?>
                    <small><?= e(date('M j, Y', strtotime($review['created_at']))) ?><?= $review['updated_at'] !== $review['created_at'] ? ' · edited' : '' ?></small>
                </div>
                <?php if ($review['title'] !== ''): ?>
                    <h3 class="review-title"><?= e($review['title']) ?></h3>
                <?php endif; ?>
                <p class="review-text"><?= nl2br(e($review['review'])) ?></p>
                <?= review_gallery_html($reviewImages[(int) $review['user_id']] ?? [], (string) $review['full_name']) ?>
                <?php if ($mine): ?>
                    <div class="review-actions">
                        <a class="btn btn-ghost btn-sm" href="<?= e(url('review/edit.php?product_id=' . $id)) ?>">Edit</a>
                        <form method="post" action="<?= e(url('review/delete.php')) ?>" data-confirm="Delete your review?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $id ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                        </form>
                    </div>
                <?php elseif (is_admin()): ?>
                    <div class="review-actions">
                        <form method="post" action="<?= e(url('review/delete.php')) ?>" data-confirm="Remove this review as an administrator?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $id ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $review['user_id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Remove review</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php include('./includes/footer.php'); ?>
