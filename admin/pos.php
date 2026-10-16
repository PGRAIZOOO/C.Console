<?php
include('../includes/config.php');
include('../includes/functions.php');
$admin = require_admin();

$old = [
    'discount_type' => 'None',
    'discount_value' => '',
    'cash_tendered' => '',
];

if (is_post()) {
    csrf_verify();
    $lines = [];
    $productIds = (array) ($_POST['game'] ?? []);
    $quantities = (array) ($_POST['qty'] ?? []);
    foreach ($productIds as $index => $productId) {
        $productId = (int) $productId;
        $qty = (int) ($quantities[$index] ?? 0);
        if ($productId > 0 && $qty > 0) {
            $lines[$productId] = ($lines[$productId] ?? 0) + $qty;
        }
    }
    $discountType = (string) ($_POST['discount_type'] ?? 'None');
    $discountRaw = trim($_POST['discount_value'] ?? '');
    $cashRaw = trim($_POST['cash_tendered'] ?? '');
    $discountValue = is_numeric($discountRaw) ? (float) $discountRaw : 0.0;

    $problem = null;
    if (!$lines) {
        $problem = 'Add at least one game to the sale.';
    } elseif (!in_array($discountType, DISCOUNT_TYPES, true)) {
        $problem = 'Choose a valid discount type.';
    } elseif ($discountType !== 'None' && $discountRaw !== '' && !is_numeric($discountRaw)) {
        $problem = 'Enter a valid discount.';
    } elseif ($discountType === 'Percentage' && ($discountValue < 0 || $discountValue > 100)) {
        $problem = 'A percentage discount must be between 0 and 100.';
    } elseif ($discountValue < 0) {
        $problem = 'The discount cannot be negative.';
    } elseif (!is_numeric($cashRaw) || (float) $cashRaw <= 0) {
        $problem = 'Enter the cash tendered.';
    }

    if ($problem === null) {
        try {
            $orderId = create_order($admin['id'], 'Confirmed', null, $discountType, $discountValue, round((float) $cashRaw, 2), $lines);
            flash('success', 'Sale recorded as order #' . $orderId . '.');
            redirect('admin/orderDetails.php?id=' . $orderId);
        } catch (RuntimeException $error) {
            $problem = $error->getMessage();
        }
    }
    flash('error', $problem);
    $old = ['discount_type' => $discountType, 'discount_value' => $discountRaw, 'cash_tendered' => $cashRaw];
}

$games = db_all(products_sql('p.is_active = 1 AND ' . QTY_SQL . ' > 0') . ' ORDER BY p.name');

$title = 'New sale';
include('../includes/adminHeader.php');
?>
<div class="page-head">
    <div>
        <h1>New sale</h1>
        <p>Record an in-store purchase. Stock is deducted right away.</p>
    </div>
</div>

<?php if (!$games): ?>
    <div class="card empty"><h3>Nothing to sell</h3><p>Every game is out of stock.</p></div>
<?php else: ?>
    <div class="layout">
        <div class="card">
            <form method="post" class="form" id="pos-form" data-pos>
                <?= csrf_field() ?>
                <div>
                    <div class="pos-row" style="margin-bottom:6px">
                        <strong>Game</strong>
                        <strong>Quantity</strong>
                    </div>
                    <div id="pos-rows">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="pos-row">
                                <select name="game[]">
                                    <option value="" data-price="0">Select a game</option>
                                    <?php foreach ($games as $game): ?>
                                        <option value="<?= (int) $game['product_id'] ?>" data-price="<?= e($game['unit_selling_price']) ?>"><?= e($game['name'] . ' · ' . money($game['unit_selling_price']) . ' · ' . $game['quantity'] . ' left') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="qty[]" min="1" value="1">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button class="btn btn-ghost btn-sm" type="button" data-add-row="#pos-rows">Add another game</button>
                </div>
                <div class="row three">
                    <div class="field">
                        <label for="discount_type">Discount</label>
                        <select id="discount_type" name="discount_type">
                            <?php foreach (DISCOUNT_TYPES as $type): ?>
                                <option value="<?= e($type) ?>" <?= $old['discount_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="discount_value">Discount value</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" value="<?= e($old['discount_value']) ?>">
                    </div>
                    <div class="field">
                        <label for="cash_tendered">Cash tendered (₱)</label>
                        <input type="number" id="cash_tendered" name="cash_tendered" step="0.01" min="0" value="<?= e($old['cash_tendered']) ?>" required>
                    </div>
                </div>
                <div><button class="btn btn-primary" type="submit">Complete sale</button></div>
            </form>
        </div>
        <aside class="card summary">
            <h2 style="margin-bottom:12px">Sale summary</h2>
            <div class="line"><span>Subtotal</span><span data-pos-subtotal>₱0.00</span></div>
            <div class="line"><span>Discount</span><span data-pos-discount>₱0.00</span></div>
            <div class="line total"><span>Total</span><span data-pos-total>₱0.00</span></div>
            <div class="line"><span>Change</span><span data-pos-change>₱0.00</span></div>
        </aside>
    </div>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
