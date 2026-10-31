<?php
$area = 'store';
$title = $title ?? SHOP_NAME;
$user = current_user();
$script = preg_replace('#^' . preg_quote(BASE_URL, '#') . '#', '', $_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(SHOP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= e(url('images/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('includes/style/style.css')) ?>?v=<?= (int) @filemtime(__DIR__ . '/style/style.css') ?>">
    <script src="<?= e(url('includes/style/app.js')) ?>?v=<?= (int) @filemtime(__DIR__ . '/style/app.js') ?>" defer></script>
</head>
<body class="store">
<header class="topbar">
    <div class="wrap bar">
        <a class="brand" href="<?= e(url('index.php')) ?>"><img class="logo" src="<?= e(url('images/logo.png')) ?>" alt=""> <?= e(SHOP_NAME) ?></a>
        <nav class="links">
            <a href="<?= e(url('index.php')) ?>" class="<?= $script === '/index.php' ? 'active' : '' ?>">Home</a>
            <a href="<?= e(url('shop.php')) ?>" class="<?= in_array($script, ['/shop.php', '/game.php'], true) ? 'active' : '' ?>">Shop</a>
            <?php if ($user): ?>
                <?php if (!is_admin()): ?>
                    <a href="<?= e(url('user/myorders.php')) ?>" class="<?= in_array($script, ['/user/myorders.php', '/user/order.php'], true) ? 'active' : '' ?>">My Orders</a>
                <?php endif; ?>
                <a href="<?= e(url('user/profile.php')) ?>" class="<?= $script === '/user/profile.php' ? 'active' : '' ?>">Profile</a>
                <?php if (is_admin()): ?>
                    <a href="<?= e(url('admin/index.php')) ?>">Admin</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= e(url('about.php')) ?>" class="<?= $script === '/about.php' ? 'active' : '' ?>">About</a>
            <a href="<?= e(url('contact.php')) ?>" class="<?= $script === '/contact.php' ? 'active' : '' ?>">Contact</a>
        </nav>
        <div class="actions">
            <?php if (!is_admin()): ?>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('view_cart.php')) ?>">Cart <span class="count"><?= cart_count() ?></span></a>
            <?php endif; ?>
            <?php if ($user): ?>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('user/logout.php')) ?>">Sign out</a>
                <?= profile_menu() ?>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('user/login.php')) ?>">Sign in</a>
                <a class="btn btn-primary btn-sm" href="<?= e(url('user/register.php')) ?>">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="wrap page">
<?php include __DIR__ . '/alert.php'; ?>
