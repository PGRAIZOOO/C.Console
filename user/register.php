<?php
include('../includes/config.php');
include('../includes/functions.php');

if (current_user()) {
    redirect('index.php');
}

$old = $_SESSION['reg_old'] ?? ['full_name' => '', 'username' => '', 'email' => '', 'contact_number' => ''];
$errors = $_SESSION['reg_errors'] ?? [];
unset($_SESSION['reg_old'], $_SESSION['reg_errors']);

$title = 'Create account';
include('../includes/header.php');
?>
<div class="auth card">
    <h1>Create your account</h1>
    <p class="lead">Order games and track your purchases.</p>
    <form method="post" action="<?= e(url('user/store.php')) ?>" enctype="multipart/form-data" class="form" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required autocomplete="name">
            <?= field_error($errors, 'full_name') ?>
        </div>
        <div class="row">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($old['username']) ?>" required autocomplete="username">
                <?= field_error($errors, 'username') ?>
            </div>
            <div class="field">
                <label for="contact_number">Contact number</label>
                <input type="tel" id="contact_number" name="contact_number" value="<?= e($old['contact_number']) ?>" placeholder="09171234567" autocomplete="tel">
                <?= field_error($errors, 'contact_number') ?>
            </div>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required autocomplete="email">
            <?= field_error($errors, 'email') ?>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <?= field_error($errors, 'password') ?>
        </div>
        <div class="field">
            <label for="confirm">Confirm password</label>
            <input type="password" id="confirm" name="confirm" required autocomplete="new-password">
            <?= field_error($errors, 'confirm') ?>
        </div>
        <div class="field">
            <label for="photo">Profile photo (optional)</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
            <small>JPG, PNG or WEBP, up to 3 MB.</small>
            <?= field_error($errors, 'photo') ?>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create account</button>
    </form>
    <p class="foot">Already registered? <a href="<?= e(url('user/login.php')) ?>">Sign in</a></p>
</div>
<?php include('../includes/footer.php'); ?>
