<?php
include('./includes/config.php');
include('./includes/functions.php');

if (!is_post()) {
    redirect('view_cart.php');
}
csrf_verify();

if (is_admin()) {
    unset($_SESSION['cart_products']);
    flash('error', 'Administrator accounts cannot place customer orders.');
    redirect('admin/index.php');
}

$action = isset($_POST['remove']) ? 'remove' : ($_POST['action'] ?? '');
$back = safe_back('/view_cart.php');

if ($action === 'add') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty = max(1, (int) ($_POST['qty'] ?? 1));
    $game = db_row(products_sql('p.product_id = ? AND p.is_active = 1 AND p.is_archived = 0'), [$productId]);
    if (!$game) {
        flash('error', 'That game is no longer available.');
    } else {
        $have = (int) ($_SESSION['cart_products'][$productId]['qty'] ?? 0);
        $want = min($have + $qty, (int) $game['quantity']);
        if ($want < 1) {
            flash('error', $game['name'] . ' is out of stock.');
        } else {
            $_SESSION['cart_products'][$productId] = [
                'product_id' => $productId,
                'name' => $game['name'],
                'price' => $game['unit_selling_price'],
                'qty' => $want,
            ];
            flash('success', $game['name'] . ' added to your cart.');
            if ($have + $qty > (int) $game['quantity']) {
                flash('info', 'Only ' . (int) $game['quantity'] . ' in stock, quantity adjusted.');
            }
        }
    }
    header('Location: ' . BASE_URL . $back);
    exit;
}

if ($action === 'update') {
    foreach ((array) ($_POST['qty'] ?? []) as $productId => $qty) {
        $productId = (int) $productId;
        $qty = (int) $qty;
        if ($qty < 1) {
            unset($_SESSION['cart_products'][$productId]);
        } elseif (isset($_SESSION['cart_products'][$productId])) {
            $available = stock_on_hand($productId);
            if ($qty > $available) {
                $qty = $available;
                flash('info', 'Only ' . $available . ' in stock for ' . $_SESSION['cart_products'][$productId]['name'] . ', quantity adjusted.');
            }
            if ($qty < 1) {
                unset($_SESSION['cart_products'][$productId]);
            } else {
                $_SESSION['cart_products'][$productId]['qty'] = $qty;
            }
        }
    }
    flash('success', 'Cart updated.');
}

if ($action === 'remove') {
    unset($_SESSION['cart_products'][(int) ($_POST['remove'] ?? 0)]);
    flash('success', 'Item removed.');
}

if ($action === 'clear') {
    unset($_SESSION['cart_products']);
    flash('success', 'Cart cleared.');
}

redirect('view_cart.php');
