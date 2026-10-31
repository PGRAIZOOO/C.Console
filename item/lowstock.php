<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
$genre = (string) ($_GET['genre'] ?? '');
if (!in_array($genre, GENRES, true)) {
    $genre = '';
}

$items = low_stock_report($search, $genre);
$counts = stock_counts();

$title = 'Low stock report';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Low stock report</h1>
        <p><?= count($items) ?> active game<?= count($items) === 1 ? '' : 's' ?> with stock above zero and at or below the low stock alert level</p>
    </div>
    <div style="display:flex;gap:10px">
        <a class="btn btn-ghost" href="<?= e(url('item/index.php?stock=out')) ?>">Out of stock (<?= (int) $counts['out'] ?>)</a>
        <a class="btn btn-ghost" href="<?= e(url('item/stockin.php')) ?>">Stock-in history</a>
    </div>
</div>

<form class="filters compact" method="get">
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
    <button class="btn btn-primary" type="submit">Apply</button>
</form>

<div class="card">
    <?php if (!$items): ?>
        <div class="empty"><h3>No games are running low</h3><p>Games with zero stock are listed under out of stock.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Game</th><th>Genre</th><th class="num">In stock</th><th class="num">Alert level</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $game): ?>
                        <tr>
                            <td><div><strong><?= e($game['name']) ?></strong><small style="display:block"><?= e($game['game_code']) ?><?= $game['game_studio'] ? ' · ' . e($game['game_studio']) : '' ?></small></div></td>
                            <td><?= e($game['genre'] ?: '—') ?></td>
                            <td class="num"><span class="badge warn"><?= (int) $game['quantity'] ?></span></td>
                            <td class="num"><?= (int) $game['threshold'] ?></td>
                            <td class="actions-cell"><a class="btn btn-ghost btn-sm" href="<?= e(url('item/edit.php?id=' . $game['product_id'])) ?>">Restock</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include('../includes/footer.php'); ?>
