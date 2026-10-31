<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Manila');

const BASE_URL = '/C.Console';
const SHOP_NAME = 'C.Console';
const CONTACT_EMAIL = 'cconsole@cconsole.test';
const CONTACT_PHONE = '0917 123 4567';
const CONTACT_ADDRESS = 'Taguig City, Philippines';
const CONTACT_HOURS = 'Mon–Sat, 10:00 AM – 8:00 PM';
const LEGAL_UPDATED = 'October 31, 2026';
const LOW_STOCK_LEVEL = 5;
const STOCK_STATUSES = ['in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock'];
const STOCK_REASONS = ['Received new shipment', 'Restock', 'Inventory count correction', 'Damaged copies removed', 'Customer return', 'Returned to supplier', 'Other adjustment'];
const MOVEMENT_TYPES = ['Stock-in', 'Adjustment', 'Sale', 'Restore'];
const STOCK_HISTORY_PER_PAGE = 25;
const STOCK_STEP_MAX = 1000;
const STOCK_MAX = 1000000;
const AUTH_MARKER_COOKIE = 'cc_auth';
const REVIEW_HINT_FRAGMENT = 'masked with asterisks automatically';
const SESSION_LIFETIME = 28800;
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
const PRODUCT_DESCRIPTION_MAX = 2000;
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

function session_token_hash(string $token): string
{
    return hash('sha256', $token);
}

function issue_session_token(int $userId): void
{
    $now = date('Y-m-d H:i:s');
    q('DELETE FROM user_sessions WHERE expires_at < ?', [$now]);
    $token = bin2hex(random_bytes(32));
    q(
        'INSERT INTO user_sessions (user_id, token_hash, created_at, expires_at) VALUES (?, ?, ?, ?)',
        [$userId, session_token_hash($token), $now, date('Y-m-d H:i:s', time() + SESSION_LIFETIME)]
    );
    $_SESSION['auth_token'] = $token;
}

function session_token_valid(): bool
{
    static $checked = [];
    $token = $_SESSION['auth_token'] ?? '';
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if (!is_string($token) || $token === '' || $userId < 1) {
        return false;
    }
    $key = $userId . ':' . $token;
    if (!isset($checked[$key])) {
        $checked[$key] = (bool) db_row(
            'SELECT 1 FROM user_sessions WHERE token_hash = ? AND user_id = ? AND expires_at > ? LIMIT 1',
            [session_token_hash($token), $userId, date('Y-m-d H:i:s')]
        );
    }
    return $checked[$key];
}

function revoke_session_token(): void
{
    $token = $_SESSION['auth_token'] ?? '';
    if (is_string($token) && $token !== '') {
        q('DELETE FROM user_sessions WHERE token_hash = ?', [session_token_hash($token)]);
    }
}

function auth_cookie_options(int $expires): array
{
    return ['expires' => $expires, 'path' => BASE_URL . '/', 'httponly' => true, 'samesite' => 'Lax'];
}

function set_auth_marker(): void
{
    if (!headers_sent()) {
        setcookie(AUTH_MARKER_COOKIE, '1', auth_cookie_options(time() + SESSION_LIFETIME));
    }
    $_COOKIE[AUTH_MARKER_COOKIE] = '1';
}

function clear_auth_marker(): void
{
    if (!headers_sent()) {
        setcookie(AUTH_MARKER_COOKIE, '', auth_cookie_options(time() - 3600));
    }
    unset($_COOKIE[AUTH_MARKER_COOKIE]);
}

function clear_auth_state(): void
{
    unset($_SESSION['user_id'], $_SESSION['auth_token'], $_SESSION['username'], $_SESSION['email'], $_SESSION['full_name'], $_SESSION['role']);
    clear_auth_marker();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    if (!session_token_valid()) {
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
    $user = current_user();
    return $user !== null && $user['role'] === 'Admin';
}

function enforce_session(): void
{
    static $done = false;
    if ($done || PHP_SAPI === 'cli') {
        return;
    }
    $done = true;
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $onLogout = str_ends_with($script, '/user/logout.php');
    $onLogin = str_ends_with($script, '/user/login.php');
    $hadSession = isset($_SESSION['user_id']);
    $hadMarker = isset($_COOKIE[AUTH_MARKER_COOKIE]);

    if ($hadSession && session_token_valid()) {
        $row = db_row('SELECT username, email, full_name, role, is_active FROM users WHERE user_id = ?', [(int) $_SESSION['user_id']]);
        if ($row && (int) $row['is_active'] === 1) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];
            if (!$hadMarker) {
                set_auth_marker();
            }
            return;
        }
        revoke_session_token();
        clear_auth_state();
        if ($onLogout) {
            return;
        }
        session_regenerate_id(true);
        flash('error', 'Your account is not active.');
        if (!$onLogin) {
            redirect('user/login.php');
        }
        return;
    }

    if (!$hadSession && !$hadMarker) {
        return;
    }
    clear_auth_state();
    if ($onLogout) {
        return;
    }
    session_regenerate_id(true);
    flash('error', 'Your session has expired. Please sign in again.');
    if (!$onLogin) {
        redirect('user/login.php');
    }
}

function start_session_for(array $row, array $cart): void
{
    session_regenerate_id(true);
    issue_session_token((int) $row['user_id']);
    $_SESSION['user_id'] = (int) $row['user_id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['cart_products'] = $row['role'] === 'Admin' ? [] : $cart;
    set_auth_marker();
}

function require_login(): array
{
    $expired = isset($_SESSION['user_id']) && !session_token_valid();
    $user = current_user();
    if (!$user) {
        if ($expired) {
            clear_auth_state();
        }
        flash('error', $expired ? 'Your session has expired. Please sign in again.' : 'Please sign in to continue.');
        redirect('user/login.php');
    }
    $row = db_row('SELECT username, email, full_name, role, is_active FROM users WHERE user_id = ?', [$user['id']]);
    if (!$row || (int) $row['is_active'] !== 1) {
        revoke_session_token();
        clear_auth_state();
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

function block_admin_purchase(string $fallback = 'index.php'): void
{
    if (is_admin()) {
        flash('error', 'Administrator accounts cannot place customer orders.');
        redirect($fallback);
    }
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


function parse_search(string $raw): array
{
    $raw = strtolower(trim(preg_replace('/\s+/', ' ', $raw)));
    if ($raw === '') {
        return [[], ''];
    }
    if (strlen($raw) > 60) {
        return [[], 'Search is limited to 60 characters.'];
    }
    if (!preg_match("/^[a-z0-9][a-z0-9 '\\-:.&]*$/", $raw)) {
        return [[], 'Please search using English words only (letters A to Z and numbers).'];
    }
    $words = array_values(array_unique(array_filter(explode(' ', $raw), fn($w) => preg_match('/[a-z0-9]/', $w))));
    return [array_slice($words, 0, 6), ''];
}

function like_escape(string $word): string
{
    return addcslashes($word, '\\%_');
}

function cart_count(): int
{
    if (is_admin()) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart_products'] ?? [] as $line) {
        $count += (int) ($line['qty'] ?? 0);
    }
    return $count;
}

function cart_lines(): array
{
    $cart = $_SESSION['cart_products'] ?? [];
    if (!$cart || is_admin()) {
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

function log_movement(int $productId, int $change, ?string $reason = null, ?string $type = null): void
{
    if ($type === null || !in_array($type, MOVEMENT_TYPES, true)) {
        $type = $change > 0 ? 'Stock-in' : 'Adjustment';
    }
    $admin = current_user();
    $adminId = $admin !== null && $admin['role'] === 'Admin' ? $admin['id'] : null;
    for ($try = 0; $try < 5; $try++) {
        try {
            q(
                'INSERT INTO stock_movements (product_id, movement_code, movement_type, quantity_change, reason, created_by, movement_date) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$productId, next_movement_code(), $type, $change, $reason !== null && $reason !== '' ? $reason : null, $adminId, date('Y-m-d H:i:s')]
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

function adjust_stock(int $productId, int $delta, ?string $reason = null, ?string $type = null): bool
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
    log_movement($productId, $delta, $reason, $type);
    return true;
}

function set_stock(int $productId, int $quantity, ?string $reason = null): void
{
    $delta = $quantity - stock_on_hand($productId);
    if ($delta !== 0 && !adjust_stock($productId, $delta, $reason)) {
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
            $product = db_row('SELECT product_id, name, unit_selling_price FROM products WHERE product_id = ? AND is_active = 1 AND is_archived = 0', [$productId]);
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
            if (!adjust_stock((int) $product['product_id'], -$qty, 'Sold on order #' . $orderId, 'Sale')) {
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

function can_cancel_order(array $order): bool
{
    return ($order['status'] ?? '') === 'Pending' && ($order['date_needed'] ?? null) !== null;
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
        q('UPDATE orders SET status = ? WHERE order_id = ? AND status = ?', [$newStatus, $orderId, $order['status']]);
        if (db_affected() !== 1) {
            throw new RuntimeException('The order status was already changed.');
        }
        if ($newStatus === 'Cancelled' || $newStatus === 'Voided') {
            foreach (db_all('SELECT product_id, quantity FROM orderline WHERE order_id = ?', [$orderId]) as $line) {
                adjust_stock((int) $line['product_id'], (int) $line['quantity'], 'Order #' . $orderId . ' ' . strtolower($newStatus), 'Restore');
            }
        }
        db_commit();
    } catch (Throwable $error) {
        db_rollback();
        throw $error;
    }
}

function archive_product(int $productId): bool
{
    q('UPDATE products SET is_archived = 1, is_active = 0, archived_at = ? WHERE product_id = ? AND is_archived = 0', [date('Y-m-d H:i:s'), $productId]);
    return db_affected() === 1;
}

function restore_product(int $productId): bool
{
    q('UPDATE products SET is_archived = 0, archived_at = NULL WHERE product_id = ? AND is_archived = 1', [$productId]);
    return db_affected() === 1;
}

function product_can_be_deleted(int $productId): bool
{
    $orders = (int) db_val('SELECT COUNT(*) FROM orderline WHERE product_id = ?', [$productId]);
    $history = (int) db_val("SELECT COUNT(*) FROM stock_movements WHERE product_id = ? AND (reason IS NULL OR reason <> 'Initial stock')", [$productId]);
    return $orders === 0 && $history === 0;
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

function post_text(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function read_product_form(): array
{
    return [
        'name' => post_text('name'),
        'game_code' => strtoupper(post_text('game_code')),
        'game_studio' => post_text('game_studio'),
        'genre' => post_text('genre'),
        'description' => post_text('description'),
        'unit_cost_price' => post_text('unit_cost_price'),
        'unit_selling_price' => post_text('unit_selling_price'),
        'min_stock_threshold' => post_text('min_stock_threshold'),
        'quantity' => post_text('quantity'),
        'stock_reason' => post_text('stock_reason'),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
}

function valid_price(string $value, bool $allowZero): bool
{
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
        return false;
    }
    return $allowZero || (float) $value > 0;
}


function valid_count(string $value): bool
{
    return preg_match('/^\d{1,7}$/', $value) === 1 && (int) $value <= 1000000;
}

function validate_product_form(array $old, int $exceptProductId = 0, ?int $currentQuantity = null): array
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
    if (mb_strlen($old['description']) > PRODUCT_DESCRIPTION_MAX) {
        $errors['description'] = 'The description must be ' . number_format(PRODUCT_DESCRIPTION_MAX) . ' characters or fewer.';
    }
    if (!valid_price($old['unit_cost_price'], true)) {
        $errors['unit_cost_price'] = 'Enter a valid cost price, such as 1499 or 1499.50.';
    }
    if (!valid_price($old['unit_selling_price'], false)) {
        $errors['unit_selling_price'] = 'Enter a selling price above 0, such as 1499 or 1499.50.';
    }
    if (!valid_count($old['min_stock_threshold'])) {
        $errors['min_stock_threshold'] = 'Enter a whole number from 0 to 1,000,000.';
    }
    if (!valid_count($old['quantity'])) {
        $errors['quantity'] = 'Enter a whole number from 0 to 1,000,000.';
    } elseif ($currentQuantity !== null && (int) $old['quantity'] !== $currentQuantity) {
        if ($old['stock_reason'] === '') {
            $errors['stock_reason'] = 'Enter the reason for changing the stock on hand.';
        }
    }
    if (isset($old['stock_reason']) && mb_strlen($old['stock_reason']) > 255) {
        $errors['stock_reason'] = 'The reason must be 255 characters or fewer.';
    } elseif (!empty($old['stock_reason']) && !in_array($old['stock_reason'], STOCK_REASONS, true)) {
        $errors['stock_reason'] = 'Choose a reason from the list.';
    }
    return $errors;
}

function validate_login_form(string $login, string $password): array
{
    $errors = [];
    if ($login === '') {
        $errors['login'] = 'Enter your username or email.';
    } elseif (mb_strlen($login) > 100) {
        $errors['login'] = 'That is too long for a username or email.';
    } elseif (str_contains($login, '@')) {
        if (!valid_email($login)) {
            $errors['login'] = 'Enter a valid email address.';
        }
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $login)) {
        $errors['login'] = 'A username has 3 to 50 letters, numbers, dots or underscores.';
    }
    if ($password === '') {
        $errors['password'] = 'Enter your password.';
    } elseif (strlen($password) > 255) {
        $errors['password'] = 'That password is too long.';
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

function gallery_summary(): array
{
    static $summary = null;
    if ($summary === null) {
        $summary = [];
        foreach (db_all('SELECT product_id, file_name, is_primary FROM product_images ORDER BY image_id ASC') as $row) {
            $id = (int) $row['product_id'];
            $summary[$id] ??= ['primary' => null, 'first' => $row['file_name']];
            if ((int) $row['is_primary'] === 1 && $summary[$id]['primary'] === null) {
                $summary[$id]['primary'] = $row['file_name'];
            }
        }
    }
    return $summary;
}

function primary_image_url(array $game): ?string
{
    $summary = gallery_summary()[(int) ($game['product_id'] ?? 0)] ?? null;
    if ($summary && $summary['primary'] !== null) {
        return gallery_url($summary['primary']);
    }
    $path = product_image($game['game_code'] ?? '');
    if ($path) {
        return url('item/' . $path) . '?v=' . filemtime(image_dir() . '/' . basename($path));
    }
    if ($summary) {
        return gallery_url($summary['first']);
    }
    return null;
}

function set_primary_image(int $productId, int $imageId): void
{
    q('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [$productId]);
    if ($imageId > 0) {
        q('UPDATE product_images SET is_primary = 1 WHERE image_id = ? AND product_id = ?', [$imageId, $productId]);
    }
}

function cover(array $game, string $class = ''): string
{
    $name = (string) ($game['name'] ?? '');
    $src = primary_image_url($game);
    if ($src) {
        return '<div class="cover ' . e($class) . '"><img src="' . e($src) . '" alt="' . e($name) . '"></div>';
    }
    return '<div class="cover p-ps ' . e($class) . '"><span>' . e(initials($name)) . '</span></div>';
}

function status_badge(string $status): string
{
    $map = ['Pending' => 'warn', 'Confirmed' => 'ok', 'Voided' => 'bad', 'Cancelled' => 'bad'];
    return '<span class="badge ' . ($map[$status] ?? '') . '">' . e($status) . '</span>';
}

function stock_threshold_sql(string $alias = 't'): string
{
    return 'COALESCE(' . $alias . '.min_stock_threshold, ' . LOW_STOCK_LEVEL . ')';
}

function stock_condition(string $status, string $alias = 't'): string
{
    $threshold = stock_threshold_sql($alias);
    return match ($status) {
        'out' => $alias . '.quantity <= 0',
        'low' => '(' . $alias . '.quantity > 0 AND ' . $alias . '.quantity <= ' . $threshold . ')',
        'in' => $alias . '.quantity > ' . $threshold,
        default => '1 = 1',
    };
}

function stock_status(array $game): string
{
    $quantity = (int) ($game['quantity'] ?? 0);
    $threshold = (int) ($game['min_stock_threshold'] ?? LOW_STOCK_LEVEL);
    if ($quantity <= 0) {
        return 'out';
    }
    return $quantity <= $threshold ? 'low' : 'in';
}

function stock_counts(bool $activeOnly = true): array
{
    $source = products_sql($activeOnly ? 'p.is_active = 1 AND p.is_archived = 0' : 'p.is_archived = 0');
    $row = db_row(
        'SELECT COALESCE(SUM(' . stock_condition('in') . '), 0) AS in_stock,
                COALESCE(SUM(' . stock_condition('low') . '), 0) AS low_stock,
                COALESCE(SUM(' . stock_condition('out') . '), 0) AS out_stock
         FROM (' . $source . ') t'
    );
    return ['in' => (int) $row['in_stock'], 'low' => (int) $row['low_stock'], 'out' => (int) $row['out_stock']];
}

function low_stock_report(string $search = '', string $genre = ''): array
{
    $where = ['1 = 1'];
    $params = [];
    if ($search !== '') {
        $like = '%' . like_escape($search) . '%';
        $where[] = '(name LIKE ? OR game_code LIKE ? OR game_studio LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($genre !== '') {
        $where[] = 'genre = ?';
        $params[] = $genre;
    }
    return db_all(
        'SELECT product_id, game_code, name, game_studio, genre, quantity, threshold
         FROM low_stock_report WHERE ' . implode(' AND ', $where) . ' ORDER BY quantity ASC, name ASC',
        $params
    );
}

function stock_list(string $status, bool $activeOnly = true): array
{
    if ($status === 'low' && $activeOnly) {
        return low_stock_report();
    }
    $source = products_sql($activeOnly ? 'p.is_active = 1 AND p.is_archived = 0' : 'p.is_archived = 0');
    return db_all(
        'SELECT t.product_id, t.name, t.game_code, t.game_studio, t.quantity, ' . stock_threshold_sql() . ' AS threshold
         FROM (' . $source . ') t
         WHERE ' . stock_condition($status) . '
         ORDER BY t.quantity ASC, t.name ASC'
    );
}

function stock_in_filters(array $input): array
{
    $text = fn(string $key, int $max): string => is_string($input[$key] ?? null) ? mb_substr(trim($input[$key]), 0, $max) : '';
    $date = function (string $key) use ($input): string {
        $value = is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        $parsed = DateTime::createFromFormat('Y-m-d', $value);
        return $parsed && $parsed->format('Y-m-d') === $value ? $value : '';
    };
    $from = $date('from');
    $to = $date('to');
    if ($from !== '' && $to !== '' && $from > $to) {
        [$from, $to] = [$to, $from];
    }
    return [
        'q' => $text('q', 60),
        'from' => $from,
        'to' => $to,
        'reason' => $text('reason', 255),
        'admin' => max(0, (int) ($input['admin'] ?? 0)),
        'product' => max(0, (int) ($input['product'] ?? 0)),
        'page' => max(1, (int) ($input['page'] ?? 1)),
    ];
}

function stock_in_where(array $filters): array
{
    $where = ["m.movement_type = 'Stock-in'"];
    $params = [];
    if ($filters['q'] !== '') {
        $like = '%' . like_escape($filters['q']) . '%';
        $where[] = '(p.name LIKE ? OR p.game_code LIKE ? OR p.game_studio LIKE ? OR m.movement_code LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if ($filters['product'] > 0) {
        $where[] = 'm.product_id = ?';
        $params[] = $filters['product'];
    }
    if ($filters['from'] !== '') {
        $where[] = 'm.movement_date >= ?';
        $params[] = $filters['from'] . ' 00:00:00';
    }
    if ($filters['to'] !== '') {
        $where[] = 'm.movement_date <= ?';
        $params[] = $filters['to'] . ' 23:59:59';
    }
    if ($filters['reason'] !== '') {
        $where[] = 'm.reason = ?';
        $params[] = $filters['reason'];
    }
    if ($filters['admin'] > 0) {
        $where[] = 'm.created_by = ?';
        $params[] = $filters['admin'];
    }
    return [implode(' AND ', $where), $params];
}

function stock_badge(array $game): string
{
    $quantity = (int) $game['quantity'];
    return match (stock_status($game)) {
        'out' => '<span class="badge bad">Out of Stock</span>',
        'low' => '<span class="badge warn">Low Stock: ' . $quantity . '</span>',
        default => '<span class="badge ok">In Stock: ' . $quantity . '</span>',
    };
}

function chevron_icon(string $direction): string
{
    $points = $direction === 'up' ? '6 15 12 9 18 15' : '6 9 12 15 18 9';
    return '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="' . $points . '"/></svg>';
}

function stepper_buttons(string $upAttrs, string $downAttrs): string
{
    return '<div class="stepper-arrows">'
        . '<button type="button" class="stepper-btn" aria-label="Increase stock"' . $upAttrs . '>' . chevron_icon('up') . '</button>'
        . '<button type="button" class="stepper-btn" aria-label="Decrease stock"' . $downAttrs . '>' . chevron_icon('down') . '</button>'
        . '</div>';
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="error">' . e($errors[$key]) . '</small>' : '';
}


const GALLERY_MAX = 8;

function gallery_dir(): string
{
    return image_dir() . '/gallery';
}

function product_gallery(int $productId): array
{
    return db_all('SELECT image_id, file_name, is_primary FROM product_images WHERE product_id = ? ORDER BY image_id ASC', [$productId]);
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
        if ((int) $row['is_primary'] === 1) {
            array_unshift($slides, gallery_url($row['file_name']));
        } else {
            $slides[] = gallery_url($row['file_name']);
        }
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
    $html .= $item('user/profile.php', 'Profile settings');
    if ($role !== 'Admin') {
        $html .= $item('user/myorders.php', 'My orders');
    }
    if ($role === 'Admin') {
        $html .= $item('admin/index.php', 'Admin dashboard');
    }
    $html .= $item('user/logout.php', 'Sign out', ' danger');
    return $html . '</div></div></div>';
}




const REVIEW_TITLE_MIN_LENGTH = 3;
const REVIEW_TITLE_MAX_LENGTH = 100;
const REVIEW_IMAGE_MAX = 4;
const REVIEW_MIN_LENGTH = 5;
const REVIEW_MAX_LENGTH = 1000;

const BAD_WORDS = [
    'fuck', 'motherfucker', 'shit', 'bullshit', 'shithead', 'bitch', 'bastard', 'asshole', 'dumbass', 'jackass',
    'dick', 'dickhead', 'cock', 'pussy', 'cunt', 'twat', 'prick', 'wanker', 'bollocks', 'arse', 'damn', 'goddamn',
    'crap', 'piss', 'slut', 'whore', 'retard', 'douche', 'douchebag', 'nigger', 'nigga', 'faggot',
];

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
         WHERE r.product_id = ? AND r.review NOT LIKE ? AND r.title NOT LIKE ? ORDER BY r.created_at DESC, r.user_id DESC',
        [$productId, '%' . REVIEW_HINT_FRAGMENT . '%', '%' . REVIEW_HINT_FRAGMENT . '%']
    );
}

function review_summary(int $productId): array
{
    $row = db_row(
        'SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average FROM product_reviews WHERE product_id = ? AND review NOT LIKE ? AND title NOT LIKE ?',
        [$productId, '%' . REVIEW_HINT_FRAGMENT . '%', '%' . REVIEW_HINT_FRAGMENT . '%']
    );
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

function is_review_hint(string $text): bool
{
    return stripos($text, REVIEW_HINT_FRAGMENT) !== false;
}

function contains_markup(string $text): bool
{
    return preg_match('/<\/?[a-z!?]/i', $text) === 1;
}

function read_review_form(): array
{
    return [
        'rating' => trim((string) ($_POST['rating'] ?? '')),
        'title' => trim((string) ($_POST['title'] ?? '')),
        'review' => trim(str_replace("\r\n", "\n", (string) ($_POST['review'] ?? ''))),
    ];
}

function validate_review_form(array $old): array
{
    $errors = [];
    if (!preg_match('/^[1-5]$/', $old['rating'])) {
        $errors['rating'] = 'Choose a rating from 1 to 5 stars.';
    }
    $titleLength = mb_strlen($old['title']);
    if ($old['title'] === '') {
        $errors['title'] = 'Enter a title for your review.';
    } elseif ($titleLength < REVIEW_TITLE_MIN_LENGTH) {
        $errors['title'] = 'The title must be at least ' . REVIEW_TITLE_MIN_LENGTH . ' characters.';
    } elseif ($titleLength > REVIEW_TITLE_MAX_LENGTH) {
        $errors['title'] = 'The title must be ' . REVIEW_TITLE_MAX_LENGTH . ' characters or fewer.';
    } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]|[\r\n]/', $old['title'])) {
        $errors['title'] = 'The title must be a single line of plain text.';
    } elseif (contains_markup($old['title'])) {
        $errors['title'] = 'HTML or script tags are not allowed in the title.';
    } elseif (is_review_hint($old['title'])) {
        $errors['title'] = 'Please write your own title.';
    }
    $length = mb_strlen($old['review']);
    if ($old['review'] === '') {
        $errors['review'] = 'Write a few words about the game.';
    } elseif ($length < REVIEW_MIN_LENGTH) {
        $errors['review'] = 'Your review must be at least ' . REVIEW_MIN_LENGTH . ' characters.';
    } elseif ($length > REVIEW_MAX_LENGTH) {
        $errors['review'] = 'Your review must be ' . REVIEW_MAX_LENGTH . ' characters or fewer.';
    } elseif (contains_markup($old['review'])) {
        $errors['review'] = 'HTML or script tags are not allowed in a review.';
    } elseif (is_review_hint($old['review'])) {
        $errors['review'] = 'Please write your own review in your own words.';
    }
    return $errors;
}

function review_image_dir(): string
{
    return __DIR__ . '/../review/images';
}

function review_image_url(string $fileName): string
{
    $fileName = basename($fileName);
    $path = review_image_dir() . '/' . $fileName;
    $version = is_file($path) ? filemtime($path) : 0;
    return url('review/images/' . rawurlencode($fileName)) . '?v=' . $version;
}

function review_images(int $productId, int $userId): array
{
    return db_all(
        'SELECT image_id, file_name FROM review_images WHERE product_id = ? AND user_id = ? ORDER BY image_id ASC',
        [$productId, $userId]
    );
}

function product_review_images(int $productId): array
{
    $grouped = [];
    foreach (db_all('SELECT user_id, image_id, file_name FROM review_images WHERE product_id = ? ORDER BY image_id ASC', [$productId]) as $row) {
        $grouped[(int) $row['user_id']][] = $row;
    }
    return $grouped;
}

function review_gallery_html(array $photos, string $author): string
{
    if (!$photos) {
        return '';
    }
    $total = count($photos);
    $multiple = $total > 1;
    $html = '<div class="review-gallery' . ($multiple ? ' multiple' : '') . '" data-review-gallery tabindex="0" aria-label="Photos from ' . e($author) . '\'s review">';
    $html .= '<div class="review-gallery-frame">';
    foreach (array_values($photos) as $i => $photo) {
        $src = review_image_url($photo['file_name']);
        $html .= '<a class="review-slide' . ($i === 0 ? ' active' : '') . '" href="' . e($src) . '" target="_blank" rel="noopener" data-review-slide>'
            . '<img src="' . e($src) . '" alt="Photo ' . ($i + 1) . ' of ' . $total . ' from ' . e($author) . '\'s review"' . ($i > 0 ? ' loading="lazy"' : '') . '></a>';
    }
    if ($multiple) {
        $chevron = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="%s"/></svg>';
        $html .= '<button type="button" class="gallery-arrow prev" data-review-step="-1" aria-label="Previous photo">' . sprintf($chevron, '15 18 9 12 15 6') . '</button>';
        $html .= '<button type="button" class="gallery-arrow next" data-review-step="1" aria-label="Next photo">' . sprintf($chevron, '9 18 15 12 9 6') . '</button>';
        $html .= '<span class="review-counter" aria-live="polite"><span data-review-index>1</span> / ' . $total . '</span>';
    }
    $html .= '</div>';
    if ($multiple) {
        $html .= '<div class="review-dots">';
        for ($i = 0; $i < $total; $i++) {
            $html .= '<button type="button" class="review-dot' . ($i === 0 ? ' active' : '') . '" data-review-dot="' . $i . '" aria-label="Show photo ' . ($i + 1) . ' of ' . $total . '"></button>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
}

function review_image_slots_left(int $productId, int $userId, array $removeIds = []): int
{
    $count = 0;
    foreach (review_images($productId, $userId) as $row) {
        if (!in_array((int) $row['image_id'], $removeIds, true)) {
            $count++;
        }
    }
    return max(0, REVIEW_IMAGE_MAX - $count);
}

function validate_review_images(array $files, int $slots = REVIEW_IMAGE_MAX): array
{
    $uploads = gallery_uploads($files);
    if (!$uploads) {
        return [];
    }
    if (count($uploads) > $slots) {
        throw new RuntimeException(
            $slots > 0
                ? 'You can add up to ' . $slots . ' more photo' . ($slots === 1 ? '' : 's') . ' to this review.'
                : 'This review already has the maximum of ' . REVIEW_IMAGE_MAX . ' photos.'
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

function store_review_images(int $productId, int $userId, array $valid): void
{
    if (!is_dir(review_image_dir())) {
        mkdir(review_image_dir(), 0755, true);
    }
    $failed = 0;
    foreach ($valid as $file) {
        $fileName = $productId . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $file['ext'];
        if (!move_uploaded_file($file['tmp_name'], review_image_dir() . '/' . $fileName)) {
            $failed++;
            continue;
        }
        q('INSERT INTO review_images (product_id, user_id, file_name) VALUES (?, ?, ?)', [$productId, $userId, $fileName]);
    }
    if ($failed > 0) {
        throw new RuntimeException($failed . ' review photo' . ($failed === 1 ? '' : 's') . ' could not be saved.');
    }
}

function remove_review_images(int $productId, int $userId, array $imageIds): void
{
    foreach ($imageIds as $imageId) {
        $row = db_row(
            'SELECT file_name FROM review_images WHERE image_id = ? AND product_id = ? AND user_id = ?',
            [(int) $imageId, $productId, $userId]
        );
        if (!$row) {
            continue;
        }
        q('DELETE FROM review_images WHERE image_id = ?', [(int) $imageId]);
        $full = review_image_dir() . '/' . basename($row['file_name']);
        if (is_file($full)) {
            unlink($full);
        }
    }
}

function delete_review(int $productId, int $userId): void
{
    $ids = array_column(review_images($productId, $userId), 'image_id');
    remove_review_images($productId, $userId, $ids);
    q('DELETE FROM product_reviews WHERE product_id = ? AND user_id = ?', [$productId, $userId]);
}

enforce_session();
