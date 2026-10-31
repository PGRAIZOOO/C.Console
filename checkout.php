<?php
include('./includes/config.php');
include('./includes/functions.php');
include('./includes/mailer.php');
$user = require_login();
block_admin_purchase('admin/index.php');

$lines = cart_lines();
if (!$lines) {
    flash('error', 'Your cart is empty.');
    redirect('view_cart.php');
}

$account = current_account();
$hasAddress = trim((string) $account['address']) !== '' && trim((string) $account['contact_number']) !== '';
$subtotal = cart_subtotal($lines);
$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+1 year'));
$dateNeeded = date('Y-m-d', strtotime('+3 days'));
$errors = [];

if (is_post()) {
    csrf_verify();
    $dateNeeded = trim($_POST['date_needed'] ?? '');
    $parsed = DateTime::createFromFormat('Y-m-d', $dateNeeded);
    if (!$hasAddress) {
        flash('error', 'Add your contact number and delivery address first.');
        redirect('user/profile.php');
    }
    if (!$parsed || $parsed->format('Y-m-d') !== $dateNeeded || $dateNeeded < $minDate || $dateNeeded > $maxDate) {
        $errors['date_needed'] = 'Choose a date from today up to one year ahead.';
    }
    if (!$errors) {
        $order = [];
        foreach ($lines as $line) {
            $order[(int) $line['product_id']] = (int) $line['qty'];
        }
        try {
            $orderId = create_order($user['id'], 'Pending', $dateNeeded, 'None', 0.0, 0.0, $order);
            unset($_SESSION['cart_products']);
            $message = 'Thank you! Your order #' . $orderId . ' has been placed.';
            try {
                if (send_order_status_email($orderId)) {
                    $message .= ' A confirmation email with your receipt was sent to ' . $account['email'] . '.';
                }
                flash('success', $message);
            } catch (Throwable $error) {
                error_log('Order placed email failed: ' . $error->getMessage());
                flash('success', $message);
                flash('info', 'Your order was saved, but the confirmation email could not be sent.');
            }
            redirect('user/order.php?id=' . $orderId);
        } catch (RuntimeException $error) {
            flash('error', $error->getMessage());
            redirect('view_cart.php');
        }
    }
}

$title = 'Checkout';
include('./includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>Checkout</h1>
        <p>Review your order and confirm.</p>
    </div>
</div>

<div class="layout">
    <div>
        <div class="card">
            <div class="card-head">
                <h2>Delivery details</h2>
                <a href="<?= e(url('user/profile.php')) ?>">Edit</a>
            </div>
            <?php if ($hasAddress): ?>
                <div class="details-list">
                    <strong><?= e($account['full_name']) ?></strong>
                    <span><?= e($account['email']) ?></span>
                    <span><?= e($account['contact_number']) ?></span>
                    <span><?= e($account['address']) ?></span>
                </div>
            <?php else: ?>
                <div class="alert info" style="margin:0">Add your contact number and delivery address to place an order.</div>
                <p style="margin-top:14px"><a class="btn btn-primary" href="<?= e(url('user/profile.php')) ?>">Complete profile</a></p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="margin-bottom:16px">Items</h2>
            <div class="table-wrap">
                <table>
                    <tbody>
                        <?php foreach ($lines as $line): ?>
                            <tr>
                                <td>
                                    <div class="cell">
                                        <?= cover($line, 'thumb') ?>
                                        <div><strong><?= e($line['name']) ?></strong><small><?= e($line['game_studio'] ?: $line['game_code']) ?> · Qty <?= (int) $line['qty'] ?></small></div>
                                    </div>
                                </td>
                                <td class="num"><?= money($line['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="post" class="card summary form" novalidate>
        <?= csrf_field() ?>
        <h2>Order summary</h2>
        <div class="field">
            <label for="date_needed">Date needed</label>
            <input type="date" id="date_needed" name="date_needed" value="<?= e($dateNeeded) ?>" min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" required>
            <?= field_error($errors, 'date_needed') ?>
        </div>
        <div>
            <div class="line"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <div class="line total"><span>Total</span><span><?= money($subtotal) ?></span></div>
        </div>
        <button class="btn btn-primary btn-block" type="submit" <?= $hasAddress ? '' : 'disabled' ?>>Place order</button>
    </form>
</div>
<?php include('./includes/footer.php'); ?>
