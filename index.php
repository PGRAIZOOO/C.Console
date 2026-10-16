<?php
include('./includes/config.php');
include('./includes/functions.php');

$search = trim($_GET['q'] ?? '');
$genre = $_GET['genre'] ?? '';
$sort = $_GET['sort'] ?? 'new';

$where = ['p.is_active = 1'];
$params = [];
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.game_studio LIKE ? OR p.game_code LIKE ?)';
    array_push($params, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
}
if (in_array($genre, GENRES, true)) {
    $where[] = 'p.genre = ?';
    $params[] = $genre;
} else {
    $genre = '';
}
$orders = [
    'new' => 'p.product_id DESC',
    'low' => 'p.unit_selling_price ASC',
    'high' => 'p.unit_selling_price DESC',
    'name' => 'p.name ASC',
];
$orderBy = $orders[$sort] ?? $orders['new'];
$games = db_all(products_sql(implode(' AND ', $where)) . ' ORDER BY ' . $orderBy, $params);

$title = 'Shop';
include('./includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>PS5 Games</h1>
        <p><?= count($games) ?> title<?= count($games) === 1 ? '' : 's' ?> available</p>
    </div>
</div>

<form class="filters" method="get">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Title, studio or code">
    </div>
    <div class="field">
        <label for="genre">Genre</label>
        <select id="genre" name="genre">
            <option value="">All genres</option>
            <?php foreach (GENRES as $item): ?>
                <option value="<?= e($item) ?>" <?= $genre === $item ? 'selected' : '' ?>><?= e($item) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="sort">Sort by</label>
        <select id="sort" name="sort">
            <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Title A–Z</option>
            <option value="low" <?= $sort === 'low' ? 'selected' : '' ?>>Price: low to high</option>
            <option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>Price: high to low</option>
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Apply</button>
</form>

<?php if (!$games): ?>
    <div class="card empty">
        <h3>No games found</h3>
        <p>Try a different search or genre.</p>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($games as $game): ?>
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
                        <?php if ((int) $game['quantity'] > 0): ?>
                            <button class="btn btn-primary btn-block" type="submit">Add to cart</button>
                        <?php else: ?>
                            <button class="btn btn-ghost btn-block" type="button" disabled>Out of stock</button>
                        <?php endif; ?>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php include('./includes/footer.php'); ?>
