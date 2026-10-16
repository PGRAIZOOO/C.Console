<?php
include('../includes/config.php');
include('../includes/functions.php');
require_admin();

$old = $_SESSION['item_old'] ?? [
    'name' => '', 'game_code' => next_game_code(), 'game_studio' => '', 'genre' => '',
    'unit_cost_price' => '', 'unit_selling_price' => '', 'min_stock_threshold' => LOW_STOCK_LEVEL,
    'quantity' => 0, 'is_active' => 1,
];
$errors = $_SESSION['item_errors'] ?? [];
unset($_SESSION['item_old'], $_SESSION['item_errors']);

$title = 'Add game';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>Add game</h1>
        <p>Add a new title to the catalog.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('item/index.php')) ?>">Back to games</a>
</div>

<div class="card" style="max-width:820px">
    <form method="post" action="<?= e(url('item/store.php')) ?>" enctype="multipart/form-data" class="form" novalidate>
        <?= csrf_field() ?>
        <div class="row">
            <div class="field">
                <label for="name">Title</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
                <?= field_error($errors, 'name') ?>
            </div>
            <div class="field">
                <label for="game_code">Game code</label>
                <input type="text" id="game_code" name="game_code" value="<?= e($old['game_code']) ?>" required>
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
                        <option value="<?= e($item) ?>" <?= $old['genre'] === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'genre') ?>
            </div>
            <div class="field">
                <label for="product_type">Product type</label>
                <input type="text" id="product_type" value="PlayStation 5 Game" disabled>
            </div>
        </div>
        <div class="row three">
            <div class="field">
                <label for="unit_cost_price">Cost price (₱)</label>
                <input type="number" id="unit_cost_price" name="unit_cost_price" step="0.01" min="0" value="<?= e($old['unit_cost_price']) ?>" required>
                <?= field_error($errors, 'unit_cost_price') ?>
            </div>
            <div class="field">
                <label for="unit_selling_price">Selling price (₱)</label>
                <input type="number" id="unit_selling_price" name="unit_selling_price" step="0.01" min="0" value="<?= e($old['unit_selling_price']) ?>" required>
                <?= field_error($errors, 'unit_selling_price') ?>
            </div>
            <div class="field">
                <label for="quantity">Stock on hand</label>
                <input type="number" id="quantity" name="quantity" min="0" value="<?= e($old['quantity']) ?>" required>
                <?= field_error($errors, 'quantity') ?>
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label for="min_stock_threshold">Low stock alert at</label>
                <input type="number" id="min_stock_threshold" name="min_stock_threshold" min="0" value="<?= e($old['min_stock_threshold']) ?>" required>
                <?= field_error($errors, 'min_stock_threshold') ?>
            </div>
            <div class="field">
                <label for="image">Cover image</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG or WEBP, up to 3 MB.</small>
                <?= field_error($errors, 'image') ?>
            </div>
        </div>
        <div class="field">
            <label for="images">Gallery photos</label>
            <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
            <small>Select up to <?= GALLERY_MAX ?> photos. JPG, PNG or WEBP, up to 3 MB each.</small>
            <?= field_error($errors, 'images') ?>
        </div>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" <?= !empty($old['is_active']) ? 'checked' : '' ?>>
            <span>Show this game in the shop</span>
        </label>
        <div style="display:flex;gap:10px">
            <button class="btn btn-primary" type="submit">Add game</button>
            <a class="btn btn-ghost" href="<?= e(url('item/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php include('../includes/footer.php'); ?>
