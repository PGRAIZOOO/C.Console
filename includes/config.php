<?php
$db_file = __DIR__ . '/db.env';
$db_env = is_file($db_file) ? (parse_ini_file($db_file) ?: []) : [];
$db_host = $db_env['DB_HOST'] ?? 'localhost';
$db_username = $db_env['DB_USERNAME'] ?? 'root';
$db_passwd = $db_env['DB_PASSWORD'] ?? '';
$db_name = $db_env['DB_NAME'] ?? 'C.Console';
$db_port = (int) ($db_env['DB_PORT'] ?? 3306);

$conn = mysqli_connect($db_host, $db_username, $db_passwd, null, $db_port) or die("Could not connect!\n");
mysqli_select_db($conn, $db_name) or die("Could not select the database $db_name!\n" . mysqli_error($conn));
mysqli_set_charset($conn, 'utf8mb4');

function q(string $sql, array $params = [])
{
    global $conn;
    return mysqli_execute_query($conn, $sql, $params ?: null);
}

function db_all(string $sql, array $params = []): array
{
    $result = q($sql, $params);
    return $result instanceof mysqli_result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function db_row(string $sql, array $params = []): ?array
{
    $result = q($sql, $params);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    return $row ?: null;
}

function db_val(string $sql, array $params = [])
{
    $result = q($sql, $params);
    $row = $result instanceof mysqli_result ? mysqli_fetch_row($result) : null;
    return $row ? $row[0] : null;
}

function db_begin(): void
{
    global $conn;
    mysqli_begin_transaction($conn);
}

function db_commit(): void
{
    global $conn;
    mysqli_commit($conn);
}

function db_rollback(): void
{
    global $conn;
    mysqli_rollback($conn);
}

function db_insert_id(): int
{
    global $conn;
    return (int) mysqli_insert_id($conn);
}

function db_affected(): int
{
    global $conn;
    return (int) mysqli_affected_rows($conn);
}
