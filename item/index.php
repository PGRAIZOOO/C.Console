<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$search = trim($_GET['q'] ?? '');
$genre = $_GET['genre'] ?? '';
$visibility = $_GET['visibility'] ?? '';

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
    $where[] = 'p.is_active = 1';
} elseif ($visibility === 'hidden') {
    $where[] = '(p.is_active = 0 OR p.is_active IS NULL)';
} else {
    $visibility = '';
}
$games = db_all(products_sql(implode(' AND ', $where)) . ' ORDER BY p.name ASC', $params);

$title = 'Games';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Games</h1>
        <p><?= count($games) ?> in the catalog</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('item/create.php')) ?>">Add game</a>
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
        <label for="visibility">Visibility</label>
        <select id="visibility" name="visibility">
            <option value="">All games</option>
            <option value="active" <?= $visibility === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="hidden" <?= $visibility === 'hidden' ? 'selected' : '' ?>>Hidden</option>
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
                    <tr><th>Game</th><th>Genre</th><th class="num">Cost</th><th class="num">Price</th><th>Stock</th><th>Status</th><th></th></tr>
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
                            <td><?= stock_badge($game) ?></td>
                            <td><?= (int) $game['is_active'] === 1 ? '<span class="badge ok">Active</span>' : '<span class="badge">Hidden</span>' ?></td>
                            <td class="actions-cell">
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('item/edit.php?id=' . $game['product_id'])) ?>">Edit</a>
                                <form method="post" action="<?= e(url('item/delete.php')) ?>" data-confirm="Delete <?= e($game['name']) ?>?">
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
