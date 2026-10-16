<?php
include('../includes/config.php');
include('../includes/functions.php');

if (!is_post()) {
    redirect('user/register.php');
}
if (upload_too_large()) {
    flash('error', 'The upload was too large. Try a smaller photo.');
    redirect('user/register.php');
}
csrf_verify();

$old = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'contact_number' => trim($_POST['contact_number'] ?? ''),
];
$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['confirm'] ?? '');
$errors = [];

if ($old['full_name'] === '') {
    $errors['full_name'] = 'Enter your full name.';
} elseif (mb_strlen($old['full_name']) > 100) {
    $errors['full_name'] = 'Use 100 characters or fewer.';
}
if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $old['username'])) {
    $errors['username'] = 'Use 3 to 50 letters, numbers, dots or underscores.';
} elseif (username_taken($old['username'])) {
    $errors['username'] = 'That username is already taken.';
}
if (!valid_email($old['email'])) {
    $errors['email'] = 'Enter a valid email address.';
} elseif (email_taken($old['email'])) {
    $errors['email'] = 'That email is already registered.';
}
if ($old['contact_number'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['contact_number'])) {
    $errors['contact_number'] = 'Enter a valid contact number.';
}
if (strlen($password) < 6) {
    $errors['password'] = 'Use at least 6 characters.';
} elseif ($password !== $confirm) {
    $errors['confirm'] = 'Passwords do not match.';
}

$photoExt = null;
if (!$errors) {
    try {
        $photoExt = validate_image($_FILES['photo'] ?? []);
    } catch (RuntimeException $error) {
        $errors['photo'] = $error->getMessage();
    }
}

if ($errors) {
    $_SESSION['reg_old'] = $old;
    $_SESSION['reg_errors'] = $errors;
    redirect('user/register.php');
}

try {
    q(
        "INSERT INTO users (username, email, password, role, full_name, contact_number, address, is_active) VALUES (?, ?, ?, 'Customer', ?, ?, NULL, 1)",
        [$old['username'], $old['email'], password_hash($password, PASSWORD_BCRYPT), $old['full_name'], $old['contact_number'] !== '' ? $old['contact_number'] : null]
    );
} catch (mysqli_sql_exception $error) {
    if ((int) $error->getCode() !== 1062) {
        throw $error;
    }
    $_SESSION['reg_old'] = $old;
    $_SESSION['reg_errors'] = ['username' => 'That username or email is already registered.'];
    redirect('user/register.php');
}
$userId = db_insert_id();
if ($photoExt !== null) {
    try {
        store_user_photo($userId, $_FILES['photo'], $photoExt);
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
    }
}

start_session_for(
    ['user_id' => $userId, 'username' => $old['username'], 'email' => $old['email'], 'full_name' => $old['full_name'], 'role' => 'Customer'],
    $_SESSION['cart_products'] ?? []
);
flash('success', 'Welcome to ' . SHOP_NAME . ', ' . $old['full_name'] . '!');
redirect('index.php');
