<?php
include('../includes/config.php');
include('../includes/functions.php');
$user = require_login();

$account = current_account();
$old = $account;
$errors = [];

if (is_post()) {
    if (upload_too_large()) {
        flash('error', 'The upload was too large. Try a smaller photo.');
        redirect('user/profile.php');
    }
    csrf_verify();
    foreach (['full_name', 'email', 'contact_number', 'address'] as $key) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    if ($old['full_name'] === '') {
        $errors['full_name'] = 'Enter your full name.';
    } elseif (mb_strlen($old['full_name']) > 100) {
        $errors['full_name'] = 'Use 100 characters or fewer.';
    }
    if (!valid_email($old['email'])) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (email_taken($old['email'], $user['id'])) {
        $errors['email'] = 'That email is already registered.';
    }
    if ($old['contact_number'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['contact_number'])) {
        $errors['contact_number'] = 'Enter a valid contact number.';
    }
    if (mb_strlen($old['address']) > 500) {
        $errors['address'] = 'Use 500 characters or fewer.';
    }
    $photoExt = null;
    if (!$errors) {
        try {
            $photoExt = validate_image($_FILES['photo'] ?? []);
        } catch (RuntimeException $error) {
            $errors['photo'] = $error->getMessage();
        }
    }
    if (!$errors) {
        try {
            q(
                'UPDATE users SET full_name = ?, email = ?, contact_number = ?, address = ? WHERE user_id = ?',
                [
                    $old['full_name'],
                    $old['email'],
                    $old['contact_number'] !== '' ? $old['contact_number'] : null,
                    $old['address'] !== '' ? $old['address'] : null,
                    $user['id'],
                ]
            );
            $_SESSION['full_name'] = $old['full_name'];
            $_SESSION['email'] = $old['email'];
            if ($photoExt !== null) {
                try {
                    store_user_photo($user['id'], $_FILES['photo'], $photoExt);
                } catch (RuntimeException $error) {
                    flash('error', $error->getMessage());
                }
            }
            flash('success', 'Profile saved.');
            redirect('user/profile.php');
        } catch (mysqli_sql_exception $error) {
            if ((int) $error->getCode() !== 1062) {
                throw $error;
            }
            $errors['email'] = 'That email is already registered.';
        }
    }
}

$title = 'Profile';
include('../includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>My profile</h1>
        <p>@<?= e($account['username']) ?></p>
    </div>
</div>
<div class="card" style="max-width:720px">
    <form method="post" enctype="multipart/form-data" class="form" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label for="photo">Profile photo</label>
            <div class="cell">
                <?= user_avatar($account, 'lg') ?>
                <div style="flex:1">
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                    <small>JPG, PNG or WEBP, up to 3 MB.<?= !empty($account['photo']) ? ' Leave empty to keep your current photo.' : '' ?></small>
                    <?= field_error($errors, 'photo') ?>
                </div>
            </div>
        </div>
        <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
            <?= field_error($errors, 'full_name') ?>
        </div>
        <div class="row">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
                <?= field_error($errors, 'email') ?>
            </div>
            <div class="field">
                <label for="contact_number">Contact number</label>
                <input type="tel" id="contact_number" name="contact_number" value="<?= e($old['contact_number']) ?>" placeholder="09171234567">
                <?= field_error($errors, 'contact_number') ?>
            </div>
        </div>
        <div class="field">
            <label for="address">Delivery address</label>
            <textarea id="address" name="address" rows="3" placeholder="House no., street, barangay, city"><?= e($old['address']) ?></textarea>
            <?= field_error($errors, 'address') ?>
        </div>
        <div><button class="btn btn-primary" type="submit">Save changes</button></div>
    </form>
</div>
<?php include('../includes/footer.php'); ?>
