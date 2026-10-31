<?php
include('./includes/config.php');
include('./includes/functions.php');

$search = trim($_GET['q'] ?? '');
$genre = $_GET['genre'] ?? '';
$sort = $_GET['sort'] ?? 'new';
$stock = $_GET['stock'] ?? '';
if (!array_key_exists($stock, STOCK_STATUSES)) {
    $stock = '';
}
$counts = stock_counts();
$minPrice = trim($_GET['min'] ?? '');
$maxPrice = trim($_GET['max'] ?? '');
$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ['p.is_active = 1'];
$params = [];
if ($search !== '') {
    // title, studio, code, genre text and price (e.g. "2899", "₱2,899" or "action")
    $like = '%' . $search . '%';
    $priceText = '%' . str_replace(['₱', ',', ' '], '', $search) . '%';
    $where[] = '(p.name LIKE ? OR p.game_studio LIKE ? OR p.game_code LIKE ? OR p.genre LIKE ? OR CAST(p.unit_selling_price AS CHAR) LIKE ?)';
    array_push($params, $like, $like, $like, $like, $priceText);
}
if (is_numeric($minPrice) && (float) $minPrice >= 0) {
    $where[] = 'p.unit_selling_price >= ?';
    $params[] = (float) $minPrice;
} else {
    $minPrice = '';
}
if (is_numeric($maxPrice) && (float) $maxPrice >= 0) {
    $where[] = 'p.unit_selling_price <= ?';
    $params[] = (float) $maxPrice;
} else {
    $maxPrice = '';
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
$whereSql = implode(' AND ', $where);
$total = (int) db_val('SELECT COUNT(*) FROM (' . products_sql($whereSql) . ') t WHERE ' . stock_condition($stock), $params);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$games = db_all(
    'SELECT t.* FROM (' . products_sql($whereSql) . ') t WHERE ' . stock_condition($stock) . ' ORDER BY ' . str_replace('p.', 't.', $orderBy) . ', t.product_id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
    $params
);

// keeps the search / filters when moving between pages
$pageUrl = function (int $n) use ($search, $genre, $sort, $minPrice, $maxPrice, $stock): string {
    $query = array_filter(['q' => $search, 'genre' => $genre, 'stock' => $stock, 'sort' => $sort === 'new' ? '' : $sort, 'min' => $minPrice, 'max' => $maxPrice], fn($v) => $v !== '');
    if ($n > 1) {
        $query['page'] = $n;
    }
    return url('shop.php') . ($query ? '?' . http_build_query($query) : '');
};

$title = 'Shop';
include('./includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>PS5 Games</h1>
        <p>
            <?= $total ?> title<?= $total === 1 ? '' : 's' ?> found<?= $search !== '' ? ' for “' . e($search) . '”' : '' ?>
            <?php if ($total > 0): ?> · showing <?= ($page - 1) * $perPage + 1 ?>–<?= ($page - 1) * $perPage + count($games) ?><?php endif; ?>
        </p>
    </div>
</div>

<form class="filters shop-filters" method="get">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Title, studio, genre, code or price">
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
        <label for="stock">Availability</label>
        <select id="stock" name="stock">
            <option value="">All stock levels</option>
            <?php foreach (STOCK_STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $stock === $key ? 'selected' : '' ?>><?= e($label) ?> (<?= (int) $counts[$key] ?>)</option>
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
    <div class="field">
        <label for="min">Min price (₱)</label>
        <input type="number" id="min" name="min" min="0" step="1" value="<?= e($minPrice) ?>" placeholder="0">
    </div>
    <div class="field">
        <label for="max">Max price (₱)</label>
        <input type="number" id="max" name="max" min="0" step="1" value="<?= e($maxPrice) ?>" placeholder="Any">
    </div>
    <button class="btn btn-primary" type="submit">Search</button>
    <?php if ($search !== '' || $genre !== '' || $stock !== '' || $minPrice !== '' || $maxPrice !== '' || $sort !== 'new'): ?>
        <a class="btn btn-ghost" href="<?= e(url('shop.php')) ?>">Clear</a>
    <?php endif; ?>
</form>

<?php if (!$games): ?>
    <div class="card empty">
        <h3>No games found</h3>
        <p>Try a different keyword, genre or price range.</p>
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
                        <?php if (is_admin()): ?>
                            <button class="btn btn-ghost btn-block" type="button" disabled>Admin view only</button>
                        <?php elseif ((int) $game['quantity'] > 0): ?>
                            <button class="btn btn-primary btn-block" type="submit">Add to cart</button>
                        <?php else: ?>
                            <button class="btn btn-ghost btn-block" type="button" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Pages">
            <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($pageUrl($page - 1)) ?>">&larr; Prev</a><?php endif; ?>
            <?php for ($n = 1; $n <= $pages; $n++): ?>
                <?php if ($n === 1 || $n === $pages || abs($n - $page) <= 1): ?>
                    <a class="btn btn-sm <?= $n === $page ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e($pageUrl($n)) ?>" <?= $n === $page ? 'aria-current="page"' : '' ?>><?= $n ?></a>
                <?php elseif (abs($n - $page) === 2): ?>
                    <span class="gap">…</span>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= e($pageUrl($page + 1)) ?>">Next &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php include('./includes/footer.php'); ?>
