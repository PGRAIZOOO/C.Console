<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

const BASE_URL = '/C.Console';
const SHOP_NAME = 'C.Console';
const LOW_STOCK_LEVEL = 5;
const GENRES = ['Action', 'Adventure', 'Survival', 'Casual', 'Violence'];
const ORDER_STATUSES = ['Pending', 'Confirmed', 'Voided', 'Cancelled'];
const ORDER_FLOW = [
    'Pending' => ['Confirmed', 'Cancelled'],
    'Confirmed' => ['Voided'],
    'Voided' => [],
    'Cancelled' => [],
];
const LIVE_STATUSES = "('Pending', 'Confirmed')";
const DISCOUNT_TYPES = ['None', 'Percentage', 'Fixed'];
const IMAGE_TYPES = ['jpg', 'png', 'webp'];
const QTY_SQL = '(SELECT COALESCE(SUM(s.quantity), 0) FROM stock s WHERE s.product_id = p.product_id)';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => (string) ($_SESSION['username'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'name' => (string) ($_SESSION['full_name'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? 'Customer'),
    ];
}

function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'Admin';
}

function start_session_for(array $row, array $cart): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['user_id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['cart_products'] = $cart;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please sign in to continue.');
        redirect('user/login.php');
    }
    $row = db_row('SELECT username, email, full_name, role, is_active FROM users WHERE user_id = ?', [$user['id']]);
    if (!$row || (int) $row['is_active'] !== 1) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'Your account is not active.');
        redirect('user/login.php');
    }
    $_SESSION['username'] = $row['username'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['role'] = $row['role'];
    return current_user();
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'Admin') {
        flash('error', 'You do not have permission to access that page.');
        redirect('index.php');
    }
    return $user;
}

function current_account(): ?array
{
    $user = current_user();
    if (!$user) {
        return null;
    }
    return db_row('SELECT * FROM users WHERE user_id = ?', [$user['id']]);
}

function password_matches(string $plain, string $stored): bool
{
    if (preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $stored)) {
        return password_verify($plain, $stored);
    }
    return $stored !== '' && hash_equals($stored, $plain);
}

function refresh_password_hash(int $userId, string $plain, string $stored): void
{
    $hashed = preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $stored);
    if (!$hashed || password_needs_rehash($stored, PASSWORD_BCRYPT)) {
        q('UPDATE users SET password = ? WHERE user_id = ?', [password_hash($plain, PASSWORD_BCRYPT), $userId]);
    }
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 100;
}

function email_taken(string $email, int $exceptUserId = 0): bool
{
    return (bool) db_row('SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1', [$email, $exceptUserId]);
}

function username_taken(string $username): bool
{
    return (bool) db_row('SELECT 1 FROM users WHERE username = ? LIMIT 1', [$username]);
}

function safe_back(string $fallback): string
{
    $back = (string) ($_POST['back'] ?? '');
    $prefix = BASE_URL . '/';
    if ($back !== '' && strpos($back, $prefix) === 0 && strpos($back, '//') === false) {
        return substr($back, strlen(BASE_URL));
    }
    return $fallback;
}

function products_sql(string $where = '1 = 1'): string
{
    return 'SELECT p.*, ' . QTY_SQL . ' AS quantity FROM products p WHERE ' . $where;
}

function cart_count(): int
{
    $count = 0;
    foreach ($_SESSION['cart_products'] ?? [] as $line) {
        $count += (int) ($line['qty'] ?? 0);
    }
    return $count;
}

function cart_lines(): array
{
    $cart = $_SESSION['cart_products'] ?? [];
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all(products_sql("p.is_active = 1 AND p.product_id IN ($marks)") . ' ORDER BY p.name', $ids);
    $lines = [];
    foreach ($rows as $row) {
        $qty = min((int) ($cart[$row['product_id']]['qty'] ?? 0), (int) $row['quantity']);
        if ($qty < 1) {
            continue;
        }
        $row['qty'] = $qty;
        $row['subtotal'] = $qty * (float) $row['unit_selling_price'];
        $lines[] = $row;
    }
    return $lines;
}

function cart_subtotal(array $lines): float
{
    return array_sum(array_column($lines, 'subtotal'));
}

function cart_store(array $lines): void
{
    $_SESSION['cart_products'] = [];
    foreach ($lines as $line) {
        $id = (int) $line['product_id'];
        $_SESSION['cart_products'][$id] = [
            'product_id' => $id,
            'name' => $line['name'],
            'price' => $line['unit_selling_price'],
            'qty' => (int) $line['qty'],
        ];
    }
}

function next_movement_code(): string
{
    $last = (int) db_val("SELECT COALESCE(MAX(CAST(SUBSTR(movement_code, 4) AS UNSIGNED)), 0) FROM stock_movements WHERE movement_code LIKE 'MOV%'");
    return 'MOV' . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
}

function log_movement(int $productId, int $change): void
{
    for ($try = 0; $try < 5; $try++) {
        try {
            q(
                'INSERT INTO stock_movements (product_id, movement_code, quantity_change, movement_date) VALUES (?, ?, ?, ?)',
                [$productId, next_movement_code(), $change, date('Y-m-d H:i:s')]
            );
            return;
        } catch (mysqli_sql_exception $error) {
            if ((int) $error->getCode() !== 1062) {
                throw $error;
            }
        }
    }
    throw new RuntimeException('The stock movement could not be recorded.');
}

function stock_on_hand(int $productId): int
{
    return (int) db_val('SELECT COALESCE(SUM(quantity), 0) FROM stock WHERE product_id = ?', [$productId]);
}

function adjust_stock(int $productId, int $delta): bool
{
    if ($delta === 0) {
        return true;
    }
    $row = db_row('SELECT stock_id FROM stock WHERE product_id = ? ORDER BY stock_id LIMIT 1', [$productId]);
    if (!$row) {
        if ($delta < 0) {
            return false;
        }
        q('INSERT INTO stock (product_id, quantity) VALUES (?, ?)', [$productId, $delta]);
    } elseif ($delta < 0) {
        $take = abs($delta);
        q("UPDATE stock SET quantity = quantity - $take WHERE stock_id = ? AND quantity >= $take", [(int) $row['stock_id']]);
        if (db_affected() !== 1) {
            return false;
        }
    } else {
        q("UPDATE stock SET quantity = quantity + $delta WHERE stock_id = ?", [(int) $row['stock_id']]);
    }
    log_movement($productId, $delta);
    return true;
}

function set_stock(int $productId, int $quantity): void
{
    $delta = $quantity - stock_on_hand($productId);
    if ($delta !== 0 && !adjust_stock($productId, $delta)) {
        throw new RuntimeException('The stock level could not be updated.');
    }
}

function order_discount(float $subtotal, string $type, float $value): float
{
    if ($type === 'Percentage') {
        $amount = $subtotal * $value / 100;
    } elseif ($type === 'Fixed') {
        $amount = $value;
    } else {
        $amount = 0;
    }
    return round(min(max($amount, 0), $subtotal), 2);
}

function create_order(int $userId, string $status, ?string $dateNeeded, string $discountType, float $discountValue, float $cashTendered, array $lines): int
{
    if (!$lines) {
        throw new RuntimeException('No items were selected.');
    }
    if (!in_array($discountType, DISCOUNT_TYPES, true)) {
        $discountType = 'None';
    }
    if ($discountType === 'None') {
        $discountValue = 0.0;
    }
    db_begin();
    try {
        $priced = [];
        $subtotal = 0.0;
        foreach ($lines as $productId => $qty) {
            $productId = (int) $productId;
            $qty = (int) $qty;
            $product = db_row('SELECT product_id, name, unit_selling_price FROM products WHERE product_id = ? AND is_active = 1', [$productId]);
            if (!$product || $qty < 1) {
                throw new RuntimeException('A selected game is no longer available.');
            }
            $priced[] = [$product, $qty];
            $subtotal += $qty * (float) $product['unit_selling_price'];
        }
        $subtotal = round($subtotal, 2);
        $discount = order_discount($subtotal, $discountType, $discountValue);
        $total = round($subtotal - $discount, 2);
        if ($cashTendered > 0 && $cashTendered < $total) {
            throw new RuntimeException('The cash tendered is less than the total of ' . money($total) . '.');
        }
        $change = $cashTendered > 0 ? round($cashTendered - $total, 2) : 0.0;
        q(
            'INSERT INTO orders (user_id, order_date, date_needed, status, discount_type, discount_value, cash_tendered, total_amount, change_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, date('Y-m-d H:i:s'), $dateNeeded, $status, $discountType, $discountValue, $cashTendered, $total, $change]
        );
        $orderId = db_insert_id();
        foreach ($priced as [$product, $qty]) {
            if (!adjust_stock((int) $product['product_id'], -$qty)) {
                throw new RuntimeException('Not enough stock for ' . $product['name'] . '.');
            }
            q(
                'INSERT INTO orderline (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)',
                [$orderId, (int) $product['product_id'], $qty, $product['unit_selling_price']]
            );
        }
        db_commit();
        return $orderId;
    } catch (Throwable $error) {
        db_rollback();
        throw $error;
    }
}

function change_order_status(int $orderId, string $newStatus): void
{
    $order = db_row('SELECT status FROM orders WHERE order_id = ?', [$orderId]);
    if (!$order) {
        throw new RuntimeException('Order not found.');
    }
    if (!in_array($newStatus, ORDER_FLOW[$order['status']] ?? [], true)) {
        throw new RuntimeException('That status change is not allowed.');
    }
    db_begin();
    try {
        q('UPDATE orders SET status = ? WHERE order_id = ?', [$newStatus, $orderId]);
        if ($newStatus === 'Cancelled' || $newStatus === 'Voided') {
            foreach (db_all('SELECT product_id, quantity FROM orderline WHERE order_id = ?', [$orderId]) as $line) {
                adjust_stock((int) $line['product_id'], (int) $line['quantity']);
            }
        }
        db_commit();
    } catch (Throwable $error) {
        db_rollback();
        throw $error;
    }
}

function image_dir(): string
{
    return __DIR__ . '/../item/images';
}

function safe_code(?string $code): string
{
    return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $code);
}

function product_image(?string $code): ?string
{
    $code = safe_code($code);
    if ($code === '') {
        return null;
    }
    foreach (IMAGE_TYPES as $ext) {
        if (is_file(image_dir() . "/$code.$ext")) {
            return "images/$code.$ext";
        }
    }
    return null;
}

function validate_image(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image could not be uploaded.');
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('The image must be 3 MB or smaller.');
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    return $types[$mime];
}

function remove_image(?string $code): void
{
    $code = safe_code($code);
    if ($code === '') {
        return;
    }
    foreach (IMAGE_TYPES as $ext) {
        $full = image_dir() . "/$code.$ext";
        if (is_file($full)) {
            unlink($full);
        }
    }
}

function store_image(array $file, string $ext, string $code): void
{
    $code = safe_code($code);
    if (!is_dir(image_dir())) {
        mkdir(image_dir(), 0755, true);
    }
    remove_image($code);
    if (!move_uploaded_file($file['tmp_name'], image_dir() . "/$code.$ext")) {
        throw new RuntimeException('The image could not be saved.');
    }
}

function rename_image(string $from, string $to): void
{
    $from = safe_code($from);
    $to = safe_code($to);
    if ($from === '' || $to === '' || $from === $to) {
        return;
    }
    foreach (IMAGE_TYPES as $ext) {
        if (is_file(image_dir() . "/$from.$ext")) {
            remove_image($to);
            rename(image_dir() . "/$from.$ext", image_dir() . "/$to.$ext");
        }
    }
}

function read_product_form(): array
{
    return [
        'name' => trim($_POST['name'] ?? ''),
        'game_code' => strtoupper(trim($_POST['game_code'] ?? '')),
        'game_studio' => trim($_POST['game_studio'] ?? ''),
        'genre' => trim($_POST['genre'] ?? ''),
        'unit_cost_price' => trim($_POST['unit_cost_price'] ?? ''),
        'unit_selling_price' => trim($_POST['unit_selling_price'] ?? ''),
        'min_stock_threshold' => trim($_POST['min_stock_threshold'] ?? ''),
        'quantity' => trim($_POST['quantity'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
}

function validate_product_form(array $old, int $exceptProductId = 0): array
{
    $errors = [];
    if ($old['name'] === '') {
        $errors['name'] = 'Enter the game title.';
    } elseif (mb_strlen($old['name']) > 100) {
        $errors['name'] = 'The title must be 100 characters or fewer.';
    }
    if ($old['game_code'] === '') {
        $errors['game_code'] = 'Enter the game code.';
    } elseif (!preg_match('/^[A-Z0-9_-]{1,50}$/', $old['game_code'])) {
        $errors['game_code'] = 'Use letters, numbers, dashes or underscores only.';
    } elseif (db_row('SELECT 1 FROM products WHERE game_code = ? AND product_id <> ? LIMIT 1', [$old['game_code'], $exceptProductId])) {
        $errors['game_code'] = 'That game code is already used.';
    }
    if (mb_strlen($old['game_studio']) > 100) {
        $errors['game_studio'] = 'The studio must be 100 characters or fewer.';
    }
    if ($old['genre'] !== '' && !in_array($old['genre'], GENRES, true)) {
        $errors['genre'] = 'Choose a genre from the list.';
    }
    if (!is_numeric($old['unit_cost_price']) || (float) $old['unit_cost_price'] < 0 || (float) $old['unit_cost_price'] > 99999999.99) {
        $errors['unit_cost_price'] = 'Enter a valid cost price.';
    }
    if (!is_numeric($old['unit_selling_price']) || (float) $old['unit_selling_price'] <= 0 || (float) $old['unit_selling_price'] > 99999999.99) {
        $errors['unit_selling_price'] = 'Enter a valid selling price.';
    }
    if (filter_var($old['min_stock_threshold'], FILTER_VALIDATE_INT) === false || (int) $old['min_stock_threshold'] < 0) {
        $errors['min_stock_threshold'] = 'Enter a whole number, 0 or more.';
    }
    if (filter_var($old['quantity'], FILTER_VALIDATE_INT) === false || (int) $old['quantity'] < 0) {
        $errors['quantity'] = 'Enter a whole number, 0 or more.';
    }
    return $errors;
}

function next_game_code(): string
{
    $last = (int) db_val("SELECT COALESCE(MAX(CAST(SUBSTR(game_code, 4) AS UNSIGNED)), 0) FROM products WHERE game_code LIKE 'PS5%'");
    return 'PS5' . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
}

function initials(string $title): string
{
    $words = array_values(array_filter(
        preg_split('/[^A-Za-z0-9]+/', $title, -1, PREG_SPLIT_NO_EMPTY),
        fn($word) => ctype_alpha($word[0])
    ));
    if (!$words) {
        return 'G';
    }
    if (count($words) === 1) {
        return strtoupper(substr($words[0], 0, 2));
    }
    return strtoupper($words[0][0] . $words[1][0]);
}

function cover(array $game, string $class = ''): string
{
    $name = (string) ($game['name'] ?? '');
    $path = product_image($game['game_code'] ?? '');
    if ($path) {
        $version = filemtime(image_dir() . '/' . basename($path));
        return '<div class="cover ' . e($class) . '"><img src="' . e(url('item/' . $path) . '?v=' . $version) . '" alt="' . e($name) . '"></div>';
    }
    return '<div class="cover p-ps ' . e($class) . '"><span>' . e(initials($name)) . '</span></div>';
}

function status_badge(string $status): string
{
    $map = ['Pending' => 'warn', 'Confirmed' => 'ok', 'Voided' => 'bad', 'Cancelled' => 'bad'];
    return '<span class="badge ' . ($map[$status] ?? '') . '">' . e($status) . '</span>';
}

function stock_badge(array $game): string
{
    $quantity = (int) $game['quantity'];
    $threshold = (int) ($game['min_stock_threshold'] ?? LOW_STOCK_LEVEL);
    if ($quantity <= 0) {
        return '<span class="badge bad">Out of stock</span>';
    }
    if ($quantity <= $threshold) {
        return '<span class="badge warn">Low: ' . $quantity . '</span>';
    }
    return '<span class="badge ok">' . $quantity . ' in stock</span>';
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="error">' . e($errors[$key]) . '</small>' : '';
}

function order_channel(?string $dateNeeded): string
{
    return $dateNeeded === null ? 'walk-in' : 'online';
}


const GALLERY_MAX = 8;

function gallery_dir(): string
{
    return image_dir() . '/gallery';
}

function product_gallery(int $productId): array
{
    return db_all('SELECT image_id, file_name FROM product_images WHERE product_id = ? ORDER BY image_id ASC', [$productId]);
}

function gallery_url(string $fileName): string
{
    $fileName = basename($fileName);
    $path = gallery_dir() . '/' . $fileName;
    $version = is_file($path) ? filemtime($path) : 0;
    return url('item/images/gallery/' . rawurlencode($fileName)) . '?v=' . $version;
}

function upload_too_large(): bool
{
    return is_post() && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function gallery_uploads(array $files): array
{
    $list = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $list;
    }
    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = [
            'name' => (string) $name,
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
    }
    return $list;
}

function gallery_slots_left(int $productId, array $removeIds = []): int
{
    $count = 0;
    foreach (product_gallery($productId) as $row) {
        if (!in_array((int) $row['image_id'], $removeIds, true)) {
            $count++;
        }
    }
    return max(0, GALLERY_MAX - $count);
}

function validate_gallery(array $files, int $slots = GALLERY_MAX): array
{
    $uploads = gallery_uploads($files);
    if (!$uploads) {
        return [];
    }
    if (count($uploads) > $slots) {
        throw new RuntimeException(
            $slots > 0
                ? 'You can add up to ' . $slots . ' more photo' . ($slots === 1 ? '' : 's') . ' to this game.'
                : 'This game already has the maximum of ' . GALLERY_MAX . ' gallery photos.'
        );
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $valid = [];
    foreach ($uploads as $file) {
        $label = basename($file['name']);
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('"' . $label . '" could not be uploaded.');
        }
        if ($file['size'] > 3 * 1024 * 1024) {
            throw new RuntimeException('"' . $label . '" must be 3 MB or smaller.');
        }
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($types[$mime])) {
            throw new RuntimeException('"' . $label . '" is not a JPG, PNG or WEBP image.');
        }
        $file['ext'] = $types[$mime];
        $valid[] = $file;
    }
    return $valid;
}

function store_gallery(int $productId, array $valid): void
{
    if (!is_dir(gallery_dir())) {
        mkdir(gallery_dir(), 0755, true);
    }
    $failed = 0;
    foreach ($valid as $file) {
        $fileName = $productId . '_' . bin2hex(random_bytes(8)) . '.' . $file['ext'];
        if (!move_uploaded_file($file['tmp_name'], gallery_dir() . '/' . $fileName)) {
            $failed++;
            continue;
        }
        q('INSERT INTO product_images (product_id, file_name) VALUES (?, ?)', [$productId, $fileName]);
    }
    if ($failed > 0) {
        throw new RuntimeException($failed . ' gallery photo' . ($failed === 1 ? '' : 's') . ' could not be saved.');
    }
}

function remove_gallery_file(string $fileName): void
{
    $full = gallery_dir() . '/' . basename($fileName);
    if (is_file($full)) {
        unlink($full);
    }
}

function remove_gallery_images(int $productId, array $imageIds): void
{
    foreach ($imageIds as $imageId) {
        $row = db_row('SELECT file_name FROM product_images WHERE image_id = ? AND product_id = ?', [(int) $imageId, $productId]);
        if (!$row) {
            continue;
        }
        q('DELETE FROM product_images WHERE image_id = ?', [(int) $imageId]);
        remove_gallery_file($row['file_name']);
    }
}

function gallery_html(array $game): string
{
    $gallery = product_gallery((int) ($game['product_id'] ?? 0));
    if (!$gallery) {
        return cover($game, 'large');
    }
    $name = (string) ($game['name'] ?? '');
    $slides = [];
    $cover = product_image($game['game_code'] ?? '');
    if ($cover) {
        $slides[] = url('item/' . $cover) . '?v=' . filemtime(image_dir() . '/' . basename($cover));
    }
    foreach ($gallery as $row) {
        $slides[] = gallery_url($row['file_name']);
    }
    $html = '<div class="gallery">';
    $html .= '<div class="cover large"><img src="' . e($slides[0]) . '" alt="' . e($name) . '" data-gallery-main>';
    if (count($slides) > 1) {
        $chevron = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="%s"/></svg>';
        $html .= '<button type="button" class="gallery-arrow prev" data-gallery-step="-1" aria-label="Previous photo">' . sprintf($chevron, '15 18 9 12 15 6') . '</button>';
        $html .= '<button type="button" class="gallery-arrow next" data-gallery-step="1" aria-label="Next photo">' . sprintf($chevron, '9 18 15 12 9 6') . '</button>';
    }
    $html .= '</div>';
    if (count($slides) > 1) {
        $html .= '<div class="gallery-thumbs">';
        foreach ($slides as $i => $src) {
            $html .= '<button type="button" class="gallery-thumb' . ($i === 0 ? ' active' : '') . '" data-gallery-src="' . e($src) . '">'
                . '<img src="' . e($src) . '" alt="' . e($name) . ' photo ' . ($i + 1) . '"></button>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
}

function user_photo_dir(): string
{
    return __DIR__ . '/../user/images';
}

function user_photo_url(?string $fileName): ?string
{
    $fileName = basename((string) $fileName);
    $path = user_photo_dir() . '/' . $fileName;
    if ($fileName === '' || !is_file($path)) {
        return null;
    }
    return url('user/images/' . rawurlencode($fileName)) . '?v=' . filemtime($path);
}

function store_user_photo(int $userId, array $file, string $ext): void
{
    if (!is_dir(user_photo_dir())) {
        mkdir(user_photo_dir(), 0755, true);
    }
    $old = (string) db_val('SELECT photo FROM users WHERE user_id = ?', [$userId]);
    $fileName = $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], user_photo_dir() . '/' . $fileName)) {
        throw new RuntimeException('The photo could not be saved.');
    }
    q('UPDATE users SET photo = ? WHERE user_id = ?', [$fileName, $userId]);
    $oldPath = user_photo_dir() . '/' . basename($old);
    if ($old !== '' && is_file($oldPath)) {
        unlink($oldPath);
    }
}

function user_avatar(array $account, string $class = ''): string
{
    $name = (string) ($account['full_name'] ?? '');
    $src = user_photo_url($account['photo'] ?? null);
    if ($src) {
        return '<div class="avatar ' . e($class) . '"><img src="' . e($src) . '" alt="' . e($name) . '"></div>';
    }
    return '<div class="avatar ' . e($class) . '"><span>' . e(initials($name)) . '</span></div>';
}

function profile_menu(bool $withName = false, string $direction = 'down'): string
{
    $account = current_account();
    if (!$account) {
        return '';
    }
    $name = (string) $account['full_name'];
    $role = (string) $account['role'];
    $classes = 'profile-menu' . ($direction === 'up' ? ' up' : '') . ($withName ? ' with-name' : '');
    $item = fn(string $path, string $label, string $extra = ''): string => '<a class="item' . $extra . '" href="' . e(url($path)) . '">' . e($label) . '</a>';

    $html = '<div class="' . $classes . '">';
    $html .= '<a class="profile-trigger" href="' . e(url('user/profile.php')) . '" aria-haspopup="true" aria-label="Account menu for ' . e($name) . '">'
        . user_avatar($account) . ($withName ? '<span class="profile-name">' . e($name) . '</span>' : '') . '</a>';
    $html .= '<div class="profile-dropdown"><div class="profile-card">';
    $html .= '<div class="profile-info">' . user_avatar($account, 'md') . '<div>'
        . '<strong>' . e($name) . '</strong>'
        . '<small>' . e($account['email']) . '</small>'
        . '<small>@' . e($account['username']) . '</small>'
        . '<span class="badge' . ($role === 'Admin' ? ' info' : '') . '">' . e($role) . '</span>'
        . '</div></div>';
    $html .= $item('user/profile.php', 'Profile settings') . $item('user/myorders.php', 'My orders');
    if ($role === 'Admin') {
        $html .= $item('admin/index.php', 'Admin dashboard');
    }
    $html .= $item('user/logout.php', 'Sign out', ' danger');
    return $html . '</div></div></div>';
}


// ---------- Product reviews ----------

const REVIEW_MIN_LENGTH = 5;
const REVIEW_MAX_LENGTH = 1000;
// English words that are masked with asterisks in a review.
const BAD_WORDS = [
    'fuck', 'motherfucker', 'shit', 'bullshit', 'shithead', 'bitch', 'bastard', 'asshole', 'dumbass', 'jackass',
    'dick', 'dickhead', 'cock', 'pussy', 'cunt', 'twat', 'prick', 'wanker', 'bollocks', 'arse', 'damn', 'goddamn',
    'crap', 'piss', 'slut', 'whore', 'retard', 'douche', 'douchebag', 'nigger', 'nigga', 'faggot',
];

// One regex for every word: repeated letters (fuuuck) and look-alike characters (sh1t, a$$) are caught,
// common endings (-s, -ed, -ing, -er, -y) are allowed, and whole words only, so "class" or "cockpit" stay clean.
function bad_words_pattern(): string
{
    static $pattern = null;
    if ($pattern !== null) {
        return $pattern;
    }
    $look = ['a' => '[a@4]', 'e' => '[e3]', 'i' => '[i1!]', 'o' => '[o0]', 's' => '[s$5]', 't' => '[t7]'];
    $words = [];
    foreach (BAD_WORDS as $word) {
        $regex = '';
        foreach (str_split($word) as $letter) {
            $regex .= ($look[$letter] ?? preg_quote($letter, '/')) . '+';
        }
        $words[] = $regex;
    }
    $pattern = '/(?<![\p{L}\p{N}])(?:' . implode('|', $words) . ')(?:s|es|ed|er|ers|ing|y)?(?![\p{L}\p{N}])/iu';
    return $pattern;
}

function mask_bad_words(string $text): string
{
    $masked = preg_replace_callback(
        bad_words_pattern(),
        fn(array $match): string => str_repeat('*', mb_strlen($match[0])),
        $text
    );
    return $masked ?? $text;
}

// "Availed" = the customer has a Confirmed online order (one with a date needed) that contains the game.
function can_review(int $userId, int $productId): bool
{
    return (bool) db_row(
        "SELECT 1 FROM orders o INNER JOIN orderline ol ON ol.order_id = o.order_id
         WHERE o.user_id = ? AND ol.product_id = ? AND o.status = 'Confirmed' AND o.date_needed IS NOT NULL LIMIT 1",
        [$userId, $productId]
    );
}

function get_review(int $productId, int $userId): ?array
{
    return db_row('SELECT * FROM product_reviews WHERE product_id = ? AND user_id = ?', [$productId, $userId]);
}

function product_reviews(int $productId): array
{
    return db_all(
        'SELECT r.*, u.full_name, u.photo FROM product_reviews r INNER JOIN users u ON u.user_id = r.user_id
         WHERE r.product_id = ? ORDER BY r.created_at DESC, r.user_id DESC',
        [$productId]
    );
}

function review_summary(int $productId): array
{
    $row = db_row('SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average FROM product_reviews WHERE product_id = ?', [$productId]);
    return ['total' => (int) $row['total'], 'average' => (float) $row['average']];
}

function stars_html(int $rating): string
{
    $rating = max(0, min(5, $rating));
    return '<span class="stars" role="img" aria-label="' . $rating . ' out of 5 stars">'
        . str_repeat('&#9733;', $rating) . '<span class="off">' . str_repeat('&#9733;', 5 - $rating) . '</span></span>';
}

function star_input_html(string $selected): string
{
    $html = '<div class="star-input">';
    for ($star = 5; $star >= 1; $star--) {
        $html .= '<input type="radio" id="star' . $star . '" name="rating" value="' . $star . '"' . ($selected === (string) $star ? ' checked' : '') . '>'
            . '<label for="star' . $star . '" title="' . $star . ' star' . ($star > 1 ? 's' : '') . '">&#9733;</label>';
    }
    return $html . '</div>';
}

function read_review_form(): array
{
    return [
        'rating' => trim((string) ($_POST['rating'] ?? '')),
        'review' => trim(str_replace("\r\n", "\n", (string) ($_POST['review'] ?? ''))),
    ];
}

function validate_review_form(array $old): array
{
    $errors = [];
    if (!preg_match('/^[1-5]$/', $old['rating'])) {
        $errors['rating'] = 'Choose a rating from 1 to 5 stars.';
    }
    $length = mb_strlen($old['review']);
    if ($old['review'] === '') {
        $errors['review'] = 'Write a few words about the game.';
    } elseif ($length < REVIEW_MIN_LENGTH) {
        $errors['review'] = 'Your review must be at least ' . REVIEW_MIN_LENGTH . ' characters.';
    } elseif ($length > REVIEW_MAX_LENGTH) {
        $errors['review'] = 'Your review must be ' . REVIEW_MAX_LENGTH . ' characters or fewer.';
    }
    return $errors;
}
