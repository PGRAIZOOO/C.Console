<?php
include('./includes/config.php');
include('./includes/functions.php');

if (is_admin()) {
    unset($_SESSION['cart_products']);
    flash('error', 'Administrator accounts cannot place customer orders.');
    redirect('admin/index.php');
}

$lines = cart_lines();
cart_store($lines);
$subtotal = cart_subtotal($lines);

$title = 'Cart';
include('./includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>Your cart</h1>
        <p><?= cart_count() ?> item<?= cart_count() === 1 ? '' : 's' ?></p>
    </div>
</div>

<?php if (!$lines): ?>
    <div class="card empty">
        <h3>Your cart is empty</h3>
        <p style="margin-bottom:16px">Find something to play.</p>
        <a class="btn btn-primary" href="<?= e(url('shop.php')) ?>">Browse games</a>
    </div>
<?php else: ?>
    <div class="layout">
        <div class="card">
            <form method="post" action="<?= e(url('cart_update.php')) ?>" id="cart-form">
                <?= csrf_field() ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Game</th><th class="num">Price</th><th>Qty</th><th class="num">Subtotal</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $line): ?>
                                <tr>
                                    <td>
                                        <div class="cell">
                                            <?= cover($line, 'thumb') ?>
                                            <div>
                                                <strong><?= e($line['name']) ?></strong>
                                                <small><?= e($line['game_studio'] ?: $line['game_code']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num"><?= money($line['unit_selling_price']) ?></td>
                                    <td><input class="qty" type="number" name="qty[<?= (int) $line['product_id'] ?>]" value="<?= (int) $line['qty'] ?>" min="0" max="<?= (int) $line['quantity'] ?>"></td>
                                    <td class="num"><?= money($line['subtotal']) ?></td>
                                    <td class="actions-cell">
                                        <button class="btn btn-danger btn-sm" type="submit" name="remove" value="<?= (int) $line['product_id'] ?>" formnovalidate>Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="display:flex;gap:10px;margin-top:16px">
                    <button class="btn btn-ghost" type="submit" name="action" value="update">Update cart</button>
                    <button class="btn btn-ghost" type="submit" name="action" value="clear" formnovalidate>Clear cart</button>
                </div>
            </form>
        </div>
        <aside class="card summary">
            <h2 style="margin-bottom:12px">Order summary</h2>
            <div class="line"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <div class="line total"><span>Total</span><span><?= money($subtotal) ?></span></div>
            <a class="btn btn-primary btn-block" href="<?= e(url('checkout.php')) ?>">Proceed to checkout</a>
            <a class="btn btn-ghost btn-block" style="margin-top:10px" href="<?= e(url('shop.php')) ?>">Continue shopping</a>
        </aside>
    </div>
<?php endif; ?>
<?php include('./includes/footer.php'); ?>
