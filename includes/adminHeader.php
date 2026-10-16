<?php
$area = 'admin';
$title = $title ?? SHOP_NAME;
$user = current_user();
$script = preg_replace('#^' . preg_quote(BASE_URL, '#') . '#', '', $_SERVER['SCRIPT_NAME']);
$adminLinks = [
    'admin/index.php' => 'Dashboard',
    'item/index.php' => 'Games',
    'admin/orders.php' => 'Orders',
    'admin/pos.php' => 'New Sale',
    'admin/customers.php' => 'Users',
];
$activeGroup = [
    '/admin/index.php' => 'admin/index.php',
    '/item/index.php' => 'item/index.php',
    '/item/create.php' => 'item/index.php',
    '/item/edit.php' => 'item/index.php',
    '/admin/orders.php' => 'admin/orders.php',
    '/admin/orderDetails.php' => 'admin/orders.php',
    '/admin/pos.php' => 'admin/pos.php',
    '/admin/customers.php' => 'admin/customers.php',
][$script] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(SHOP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= e(url('images/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('includes/style/style.css')) ?>">
    <script src="<?= e(url('includes/style/app.js')) ?>" defer></script>
</head>
<body class="admin">
<aside class="sidebar">
    <a class="brand" href="<?= e(url('admin/index.php')) ?>"><img class="logo" src="<?= e(url('images/logo.png')) ?>" alt=""> <?= e(SHOP_NAME) ?></a>
    <nav>
        <?php foreach ($adminLinks as $path => $label): ?>
            <a href="<?= e(url($path)) ?>" class="<?= $activeGroup === $path ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <a href="<?= e(url('index.php')) ?>">View store</a>
        <a href="<?= e(url('user/logout.php')) ?>">Sign out</a>
        <?= profile_menu(true, 'up') ?>
    </div>
</aside>
<main class="content">
<?php include __DIR__ . '/alert.php'; ?>
