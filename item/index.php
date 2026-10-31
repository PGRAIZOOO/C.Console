<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$search = trim($_GET['q'] ?? '');
$genre = $_GET['genre'] ?? '';
$visibility = $_GET['visibility'] ?? '';
$stock = $_GET['stock'] ?? '';

$where = ['1 = 1'];
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
if ($visibility === 'active') {
    $where[] = 'p.is_active = 1 AND p.is_archived = 0';
} elseif ($visibility === 'hidden') {
    $where[] = '(p.is_active = 0 OR p.is_active IS NULL) AND p.is_archived = 0';
} elseif ($visibility === 'archived') {
    $where[] = 'p.is_archived = 1';
} else {
    $visibility = '';
    $where[] = 'p.is_archived = 0';
}
if (!array_key_exists($stock, STOCK_STATUSES)) {
    $stock = '';
}
$games = db_all(
    'SELECT t.* FROM (' . products_sql(implode(' AND ', $where)) . ') t WHERE ' . stock_condition($stock) . ' ORDER BY t.name ASC',
    $params
);
$counts = stock_counts(false);
$archivedCount = (int) db_val('SELECT COUNT(*) FROM products WHERE is_archived = 1');

$title = 'Games';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Games</h1>
        <p><?= count($games) ?> <?= $stock !== '' ? e(strtolower(STOCK_STATUSES[$stock])) . ' · ' : '' ?><?= $visibility === 'archived' ? 'archived' : 'in the catalog' ?></p>
    </div>
    <div style="display:flex;gap:10px">
        <a class="btn btn-ghost" href="<?= e(url('item/lowstock.php')) ?>">Low stock report</a>
        <a class="btn btn-ghost" href="<?= e(url('item/stockin.php')) ?>">Stock-in history</a>
        <a class="btn btn-primary" href="<?= e(url('item/create.php')) ?>">Add game</a>
    </div>
</div>

<form class="filters five" method="get">
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
        <label for="visibility">Visibility</label>
        <select id="visibility" name="visibility">
            <option value="">All games</option>
            <option value="active" <?= $visibility === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="hidden" <?= $visibility === 'hidden' ? 'selected' : '' ?>>Hidden</option>
            <option value="archived" <?= $visibility === 'archived' ? 'selected' : '' ?>>Archived (<?= $archivedCount ?>)</option>
        </select>
    </div>
    <div class="field">
        <label for="stock">Stock status</label>
        <select id="stock" name="stock">
            <option value="">All stock levels</option>
            <?php foreach (STOCK_STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $stock === $key ? 'selected' : '' ?>><?= e($label) ?> (<?= (int) $counts[$key] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Apply</button>
</form>

<div class="card">
    <?php if (!$games): ?>
        <div class="empty"><h3>No games found</h3><p>Add your first game to start selling.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Game</th><th>Genre</th><th class="num">Cost</th><th class="num">Price</th><th>Stock</th><th>Stock status</th><th>Visibility</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                        <tr>
                            <td>
                                <div class="cell">
                                    <?= cover($game, 'thumb') ?>
                                    <div><strong><?= e($game['name']) ?></strong><small><?= e($game['game_code']) ?><?= $game['game_studio'] ? ' · ' . e($game['game_studio']) : '' ?></small></div>
                                </div>
                            </td>
                            <td><?= e($game['genre'] ?: '—') ?></td>
                            <td class="num"><?= money($game['unit_cost_price']) ?></td>
                            <td class="num"><?= money($game['unit_selling_price']) ?></td>
                            <td><strong><?= (int) $game['quantity'] ?></strong></td>
                            <td><?= stock_badge($game) ?></td>
                            <td><?= (int) $game['is_archived'] === 1 ? '<span class="badge bad">Archived</span>' : ((int) $game['is_active'] === 1 ? '<span class="badge ok">Active</span>' : '<span class="badge">Hidden</span>') ?></td>
                            <td class="actions-cell">
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('item/edit.php?id=' . $game['product_id'])) ?>">Edit</a>
                                <form method="post" action="<?= e(url('item/archive.php')) ?>" data-confirm="<?= (int) $game['is_archived'] === 1 ? 'Restore' : 'Archive' ?> <?= e($game['name']) ?>?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $game['product_id'] ?>">
                                    <input type="hidden" name="action" value="<?= (int) $game['is_archived'] === 1 ? 'restore' : 'archive' ?>">
                                    <button class="btn btn-ghost btn-sm" type="submit"><?= (int) $game['is_archived'] === 1 ? 'Restore' : 'Archive' ?></button>
                                </form>
                                <form method="post" action="<?= e(url('item/delete.php')) ?>" data-confirm="Delete <?= e($game['name']) ?>? Games with order or stock history are archived instead.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $game['product_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include('../includes/footer.php'); ?>
