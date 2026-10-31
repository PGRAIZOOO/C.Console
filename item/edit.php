<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$game = db_row(products_sql('p.product_id = ?'), [$id]);
if (!$game) {
    flash('error', 'Game not found.');
    redirect('item/index.php');
}

$old = $game;
$errors = [];
if (isset($_SESSION['item_old']) && (int) ($_SESSION['item_old_id'] ?? 0) === $id) {
    $old = array_merge($old, $_SESSION['item_old']);
    $errors = $_SESSION['item_errors'] ?? [];
}
unset($_SESSION['item_old'], $_SESSION['item_errors'], $_SESSION['item_old_id']);

$movements = db_all(
    'SELECT movement_code, quantity_change, reason, movement_date FROM stock_movements WHERE product_id = ? ORDER BY movement_id DESC LIMIT 5',
    [$id]
);
$hasImage = product_image($game['game_code']) !== null;
$gallery = product_gallery($id);
$hasPrimary = (bool) array_filter($gallery, fn($photo) => (int) $photo['is_primary'] === 1);

$title = 'Edit game';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Edit game</h1>
        <p><?= e($game['name']) ?></p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('item/index.php')) ?>">Back to games</a>
</div>

<?php if ((int) $game['is_archived'] === 1): ?>
    <div class="alert info">This game is archived. It stays hidden from the shop and keeps its order and stock history. Restore it from the games list to make changes visible to customers.</div>
<?php endif; ?>

<div class="card" style="max-width:820px">
    <form method="post" action="<?= e(url('item/update.php')) ?>" enctype="multipart/form-data" class="form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $game['product_id'] ?>">
        <div class="row">
            <div class="field">
                <label for="name">Title</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>">
                <?= field_error($errors, 'name') ?>
            </div>
            <div class="field">
                <label for="game_code">Game code</label>
                <input type="text" id="game_code" name="game_code" value="<?= e($old['game_code']) ?>">
                <?= field_error($errors, 'game_code') ?>
            </div>
        </div>
        <div class="row three">
            <div class="field">
                <label for="game_studio">Game studio</label>
                <input type="text" id="game_studio" name="game_studio" value="<?= e($old['game_studio']) ?>">
                <?= field_error($errors, 'game_studio') ?>
            </div>
            <div class="field">
                <label for="genre">Genre</label>
                <select id="genre" name="genre">
                    <option value="">No genre</option>
                    <?php foreach (GENRES as $item): ?>
                        <option value="<?= e($item) ?>" <?= (string) $old['genre'] === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'genre') ?>
            </div>
            <div class="field">
                <label for="product_type">Product type</label>
                <input type="text" id="product_type" value="<?= e($game['product_type']) ?>" disabled>
            </div>
        </div>
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($old['description'] ?? '') ?></textarea>
            <small>Shown on the game page. Up to <?= number_format(PRODUCT_DESCRIPTION_MAX) ?> characters.</small>
            <?= field_error($errors, 'description') ?>
        </div>
        <div class="row three">
            <div class="field">
                <label for="unit_cost_price">Cost price (₱)</label>
                <input type="text" inputmode="decimal" id="unit_cost_price" name="unit_cost_price" value="<?= e($old['unit_cost_price']) ?>">
                <?= field_error($errors, 'unit_cost_price') ?>
            </div>
            <div class="field">
                <label for="unit_selling_price">Selling price (₱)</label>
                <input type="text" inputmode="decimal" id="unit_selling_price" name="unit_selling_price" value="<?= e($old['unit_selling_price']) ?>">
                <?= field_error($errors, 'unit_selling_price') ?>
            </div>
            <div class="field">
                <label for="quantity">Stock on hand</label>
                <div class="stepper wide">
                    <input type="text" inputmode="numeric" id="quantity" name="quantity" value="<?= e($old['quantity']) ?>">
                    <?= stepper_buttons(' data-stepper-target="#quantity" data-stepper-step="1"', ' data-stepper-target="#quantity" data-stepper-step="-1"') ?>
                </div>
                <?= field_error($errors, 'quantity') ?>
            </div>
        </div>
        <div class="field">
            <label for="stock_reason">Reason for stock change</label>
            <select id="stock_reason" name="stock_reason">
                <option value="">Choose a reason</option>
                <?php $chosenReason = (string) ($old['stock_reason'] ?? ''); ?>
                <?php foreach (STOCK_REASONS as $reason): ?>
                    <option value="<?= e($reason) ?>" <?= $chosenReason === $reason ? 'selected' : '' ?>><?= e($reason) ?></option>
                <?php endforeach; ?>
            </select>
            <small>Required when you change the stock on hand, including with the arrows. It is saved in the stock movement log.</small>
            <?= field_error($errors, 'stock_reason') ?>
        </div>
        <div class="row">
            <div class="field">
                <label for="min_stock_threshold">Low stock alert at</label>
                <input type="text" inputmode="numeric" id="min_stock_threshold" name="min_stock_threshold" value="<?= e($old['min_stock_threshold'] ?? LOW_STOCK_LEVEL) ?>">
                <?= field_error($errors, 'min_stock_threshold') ?>
            </div>
            <div class="field">
                <label for="image">Cover image</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG or WEBP, up to 3 MB.<?= $hasImage ? ' Leave empty to keep the current image.' : '' ?></small>
                <?= field_error($errors, 'image') ?>
            </div>
        </div>
        <?php if ($gallery): ?>
            <div class="field">
                <label>Current gallery photos</label>
                <div class="gallery-admin">
                    <?php foreach ($gallery as $photo): ?>
                        <div class="gallery-item">
                            <img src="<?= e(gallery_url($photo['file_name'])) ?>" alt="">
                            <label><input type="radio" name="primary_image" value="<?= (int) $photo['image_id'] ?>" <?= (int) $photo['is_primary'] === 1 ? 'checked' : '' ?>> Primary</label>
                            <label><input type="checkbox" name="remove_images[]" value="<?= (int) $photo['image_id'] ?>"> Remove</label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label class="check">
                    <input type="radio" name="primary_image" value="0" <?= $hasPrimary ? '' : 'checked' ?>>
                    <span>Use the cover image as the primary image<?= $hasImage ? '' : ' (the first photo is used when there is no cover)' ?></span>
                </label>
                <small>The primary image is the one shown first and in the shop listing.</small>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="images">Gallery photos</label>
            <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
            <small>Select up to <?= GALLERY_MAX ?> photos. JPG, PNG or WEBP, up to 3 MB each.<?= $gallery ? ' Tick Remove above to delete a photo.' : '' ?></small>
            <?= field_error($errors, 'images') ?>
        </div>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" <?= !empty($old['is_active']) && (int) $game['is_archived'] !== 1 ? 'checked' : '' ?> <?= (int) $game['is_archived'] === 1 ? 'disabled' : '' ?>>
            <span>Show this game in the shop</span>
        </label>
        <div style="display:flex;gap:10px">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <a class="btn btn-ghost" href="<?= e(url('item/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php if ($movements): ?>
    <div class="card" style="max-width:820px;margin-top:24px">
        <div class="card-head"><h2>Recent stock movements</h2><a href="<?= e(url('item/stockin.php?product=' . $id)) ?>">Stock-in history</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Code</th><th>Date</th><th>Reason</th><th class="num">Change</th></tr></thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?= e($movement['movement_code']) ?></td>
                            <td><?= e(date('M j, Y g:i A', strtotime($movement['movement_date']))) ?></td>
                            <td><?= e($movement['reason'] ?? '') !== '' ? e($movement['reason']) : '—' ?></td>
                            <td class="num"><?= (int) $movement['quantity_change'] > 0 ? '+' : '' ?><?= (int) $movement['quantity_change'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
