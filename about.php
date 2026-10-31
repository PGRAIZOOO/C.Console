<?php
include('./includes/config.php');
include('./includes/functions.php');

$gameCount = (int) db_val('SELECT COUNT(*) FROM products WHERE is_active = 1');
$studioCount = (int) db_val("SELECT COUNT(DISTINCT game_studio) FROM products WHERE is_active = 1 AND game_studio IS NOT NULL AND game_studio <> ''");

$values = [
    ['Focus', 'One platform, done properly. A tight PlayStation 5 catalogue means every listing gets the attention it deserves.', 'M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z'],
    ['Honesty', 'Clear peso prices, truthful stock labels and order statuses that say exactly where your order stands.', 'M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6zM8.5 12l2.5 2.5 4.5-5'],
    ['Community', 'Reviews come from customers who actually bought the game, with photos, so shoppers can learn from each other.', 'M16 11a3 3 0 1 0-6 0M4 19a6 6 0 0 1 12 0M17 8a3 3 0 1 1 0 6M20 19a5 5 0 0 0-3-4.6'],
    ['Care', 'Questions get real answers, and your personal information is handled only for running your account and orders.', 'M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z'],
];

$title = 'About Us';
include('./includes/header.php');
?>
<section class="page-hero compact">
    <span class="eyebrow">About <?= e(SHOP_NAME) ?></span>
    <h1>A PlayStation 5 game shop made for players</h1>
    <p><?= e(SHOP_NAME) ?> is an online game store based in <?= e(CONTACT_ADDRESS) ?>. We keep the catalogue focused on PS5 titles so finding, ordering and tracking your next game is quick and simple.</p>
    <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('shop.php')) ?>">Browse the shop</a>
        <a class="btn btn-ghost" href="<?= e(url('contact.php')) ?>">Contact us</a>
    </div>
</section>

<div class="stat-strip">
    <div><strong><?= $gameCount ?></strong><span>PS5 titles listed</span></div>
    <div><strong><?= $studioCount ?></strong><span>Game studios represented</span></div>
    <div><strong><?= count(GENRES) ?></strong><span>Genres to explore</span></div>
    <div><strong>₱</strong><span>Clear peso pricing</span></div>
</div>

<div class="split">
    <div class="card prose">
        <h2>Our story</h2>
        <p><?= e(SHOP_NAME) ?> is built around a simple idea: a game store should feel like a shelf curated by people who play. Instead of listing every platform and every accessory, we concentrate on PlayStation 5 games, from sweeping action epics to cosy simulations and tense survival horror.</p>
        <p>That focus shapes the whole website. Each game page carries a proper description, the studio, a game code, a gallery of images and honest stock information, so you know what you are getting before you add it to your cart.</p>
        <p>Behind the storefront is a straightforward ordering process. You choose your games, tell us when you need them, and follow your order from Pending to Confirmed, with email receipts when the store's email service is on.</p>
    </div>
    <div class="card prose">
        <h2>What makes us useful to gamers</h2>
        <ul class="checks">
            <li><strong>Search that fits how you shop.</strong> Look up games by title, studio, genre, code or price, then filter by availability.</li>
            <li><strong>Live stock labels.</strong> See at a glance whether a game is In Stock, running Low or Out of Stock.</li>
            <li><strong>Order tracking.</strong> Check every order, its total and its status from My Orders, and cancel while it is still pending.</li>
            <li><strong>Reviews from real buyers.</strong> Only customers with a confirmed order can review a game, and they can add photos.</li>
            <li><strong>Simple support.</strong> A help centre, a contact form and clear policies when you need them.</li>
        </ul>
    </div>
</div>

<div class="split">
    <div class="card mission">
        <span class="eyebrow">Our mission</span>
        <h2>Make buying PS5 games easy and dependable</h2>
        <p>To give players a clear, friendly place to discover PlayStation 5 games, order them with confidence and get help quickly when they need it.</p>
    </div>
    <div class="card mission">
        <span class="eyebrow">Our vision</span>
        <h2>The go-to shop for focused PS5 collectors</h2>
        <p>To grow into a trusted destination where players choose their next game based on honest information and the experiences of other customers.</p>
    </div>
</div>

<h2 class="section-title">What we stand for</h2>
<div class="value-grid">
    <?php foreach ($values as [$name, $text, $icon]): ?>
        <article class="value">
            <span class="icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($icon) ?>"/></svg></span>
            <h3><?= e($name) ?></h3>
            <p><?= e($text) ?></p>
        </article>
    <?php endforeach; ?>
</div>

<section class="cta-band">
    <div>
        <h2>Ready to find your next game?</h2>
        <p>Explore the full PlayStation 5 catalogue or ask us anything.</p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('shop.php')) ?>">Shop now</a>
        <a class="btn btn-ghost" href="<?= e(url('faq.php')) ?>">Read the FAQ</a>
    </div>
</section>
<?php include('./includes/footer.php'); ?>
