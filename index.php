<?php
include('./includes/config.php');
include('./includes/functions.php');

$totalGames = (int) db_val('SELECT COUNT(*) FROM products WHERE is_active = 1');
$counts = stock_counts();
$availableGames = $counts['in'] + $counts['low'];

$genreCounts = [];
foreach (db_all('SELECT genre, COUNT(*) AS total FROM products WHERE is_active = 1 AND genre IS NOT NULL GROUP BY genre') as $row) {
    $genreCounts[$row['genre']] = (int) $row['total'];
}

$newArrivals = db_all(
    'SELECT t.* FROM (' . products_sql('p.is_active = 1') . ') t WHERE t.quantity > 0 ORDER BY t.product_id DESC LIMIT 4'
);
$popular = db_all(
    'SELECT t.* FROM (' . products_sql('p.is_active = 1') . ') t
     INNER JOIN (
         SELECT ol.product_id, SUM(ol.quantity) AS sold
         FROM orderline ol INNER JOIN orders o ON o.order_id = ol.order_id
         WHERE o.status IN ' . LIVE_STATUSES . '
         GROUP BY ol.product_id
     ) s ON s.product_id = t.product_id
     WHERE t.quantity > 0
     ORDER BY s.sold DESC, t.name ASC
     LIMIT 4'
);

$genreBlurbs = [
    'Action' => 'Fast combat, big bosses and blockbuster set pieces.',
    'Adventure' => 'Stories, exploration and worlds worth getting lost in.',
    'Survival' => 'Scarce resources, tense nights and hard choices.',
    'Casual' => 'Easy to pick up, great for sharing with friends.',
    'Violence' => 'Mature, high-intensity games for grown-up players.',
];

$steps = [
    ['Browse', 'Search the catalogue by title, studio, genre or price and filter by availability.'],
    ['Add to cart', 'Pick your games and quantities. Stock labels show what is available.'],
    ['Place your order', 'Sign in, confirm your delivery details and choose the date you need it.'],
    ['Track it', 'Follow the status from Pending to Confirmed in My Orders.'],
];

$features = [
    ['Focused PS5 catalogue', 'Only PlayStation 5 games, each with a description, studio, genre and gallery.', 'M6 8h12a3 3 0 0 1 3 3v3a3 3 0 0 1-5.2 2L14 14h-4l-1.8 2A3 3 0 0 1 3 14v-3a3 3 0 0 1 3-3zM8 10.5v3M6.5 12h3'],
    ['Honest stock levels', 'In Stock, Low Stock and Out of Stock labels so there are no surprises.', 'M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10'],
    ['Order tracking', 'Every order, total and status in one place, with receipts by email when enabled.', 'M9 4h6l1 2h3v14H5V6h3zM9 12h6M9 16h4'],
    ['Reviews from real buyers', 'Only customers with a confirmed order can review a game, photos included.', 'M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z'],
];

function home_product(array $game): string
{
    ob_start();
    ?>
    <article class="product">
        <a href="<?= e(url('game.php?id=' . $game['product_id'])) ?>"><?= cover($game) ?></a>
        <div class="body">
            <div class="meta"><span><?= e($game['genre'] ?: 'PS5') ?></span></div>
            <h3><a href="<?= e(url('game.php?id=' . $game['product_id'])) ?>"><?= e($game['name']) ?></a></h3>
            <div class="price"><?= money($game['unit_selling_price']) ?></div>
            <form method="post" action="<?= e(url('cart_update.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $game['product_id'] ?>">
                <input type="hidden" name="qty" value="1">
                <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                <?php if (is_admin()): ?>
                    <button class="btn btn-ghost btn-block" type="button" disabled>Admin view only</button>
                <?php else: ?>
                    <button class="btn btn-primary btn-block" type="submit">Add to cart</button>
                <?php endif; ?>
            </form>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

$title = 'Home';
include('./includes/header.php');
?>
<section class="page-hero">
    <span class="eyebrow">PlayStation 5 games only</span>
    <h1>Your next PS5 game is one click away</h1>
    <p>Welcome to <?= e(SHOP_NAME) ?>, an online game store built around a focused PlayStation 5 catalogue, clear peso prices and honest stock levels. Browse, order and track everything in one place.</p>
    <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('shop.php')) ?>">Shop all games</a>
        <?php if (current_user()): ?>
            <a class="btn btn-ghost" href="<?= e(url(is_admin() ? 'admin/index.php' : 'user/myorders.php')) ?>"><?= is_admin() ? 'Open admin dashboard' : 'View my orders' ?></a>
        <?php else: ?>
            <a class="btn btn-ghost" href="<?= e(url('user/register.php')) ?>">Create a free account</a>
        <?php endif; ?>
    </div>
    <div class="hero-points">
        <span>Clear ₱ pricing</span>
        <span>Live stock labels</span>
        <span>Order tracking</span>
    </div>
</section>

<div class="stat-strip">
    <div><strong><?= $totalGames ?></strong><span>PS5 titles listed</span></div>
    <div><strong><?= $availableGames ?></strong><span>Available to order now</span></div>
    <div><strong><?= count($genreCounts) ?></strong><span>Genres to explore</span></div>
    <div><strong>PS5</strong><span>One platform, done well</span></div>
</div>

<div class="section-head">
    <h2 class="section-title">Shop by genre</h2>
    <a href="<?= e(url('shop.php')) ?>">View all games &rarr;</a>
</div>
<div class="genre-grid">
    <?php foreach (GENRES as $genre): ?>
        <a class="genre-tile" href="<?= e(url('shop.php?genre=' . rawurlencode($genre))) ?>">
            <strong><?= e($genre) ?></strong>
            <span><?= e($genreBlurbs[$genre] ?? '') ?></span>
            <small><?= (int) ($genreCounts[$genre] ?? 0) ?> title<?= ($genreCounts[$genre] ?? 0) === 1 ? '' : 's' ?></small>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($newArrivals): ?>
    <div class="section-head">
        <h2 class="section-title">New arrivals</h2>
        <a href="<?= e(url('shop.php?sort=new')) ?>">See the newest &rarr;</a>
    </div>
    <div class="grid">
        <?php foreach ($newArrivals as $game): ?>
            <?= home_product($game) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($popular): ?>
    <div class="section-head">
        <h2 class="section-title">Popular with our customers</h2>
        <a href="<?= e(url('shop.php')) ?>">Browse the catalogue &rarr;</a>
    </div>
    <div class="grid">
        <?php foreach ($popular as $game): ?>
            <?= home_product($game) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2 class="section-title">How ordering works</h2>
<div class="steps">
    <?php foreach ($steps as $i => [$name, $text]): ?>
        <div class="step">
            <span class="num"><?= $i + 1 ?></span>
            <h3><?= e($name) ?></h3>
            <p><?= e($text) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="section-title">Why shop at <?= e(SHOP_NAME) ?></h2>
<div class="value-grid">
    <?php foreach ($features as [$name, $text, $icon]): ?>
        <article class="value">
            <span class="icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($icon) ?>"/></svg></span>
            <h3><?= e($name) ?></h3>
            <p><?= e($text) ?></p>
        </article>
    <?php endforeach; ?>
</div>

<section class="cta-band">
    <div>
        <h2>New here? We are happy to help</h2>
        <p>Read how ordering works in our FAQ or learn more about the store.</p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('faq.php')) ?>">Read the FAQ</a>
        <a class="btn btn-ghost" href="<?= e(url('about.php')) ?>">About <?= e(SHOP_NAME) ?></a>
    </div>
</section>
<?php include('./includes/footer.php'); ?>
