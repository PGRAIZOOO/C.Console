<?php
include('../includes/config.php');
include('../includes/functions.php');

if (current_user()) {
    redirect(is_admin() ? 'admin/index.php' : 'index.php');
}

$login = '';
$errors = [];
if (is_post()) {
    csrf_verify();
    $login = post_text('login');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $errors = validate_login_form($login, $password);
    if (!$errors) {
        $row = db_row(
            'SELECT user_id, username, email, password, role, full_name, is_active FROM users WHERE (username = ? OR email = ?) LIMIT 1',
            [$login, $login]
        );
        if ($row && password_matches($password, (string) $row['password'])) {
            if ((int) $row['is_active'] !== 1) {
                flash('error', 'Your account has been deactivated. Please contact the shop.');
            } else {
                refresh_password_hash((int) $row['user_id'], $password, (string) $row['password']);
                start_session_for($row, $_SESSION['cart_products'] ?? []);
                redirect($row['role'] === 'Admin' ? 'admin/index.php' : 'index.php');
            }
        } else {
            flash('error', 'Incorrect username, email or password.');
        }
    }
}

$title = 'Sign in';
include('../includes/header.php');
?>
<div class="auth card">
    <h1>Welcome back</h1>
    <p class="lead">Sign in to manage your orders.</p>
    <form method="post" class="form" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label for="login">Username or email</label>
            <input type="text" id="login" name="login" value="<?= e($login) ?>" autofocus autocomplete="username">
            <?= field_error($errors, 'login') ?>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password">
            <?= field_error($errors, 'password') ?>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
    </form>
    <p class="foot">New here? <a href="<?= e(url('user/register.php')) ?>">Create an account</a></p>
</div>
<?php include('../includes/footer.php'); ?>
