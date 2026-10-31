<?php
const BACKUP_TITLE = 'C.Console backup';
const BACKUP_FORMAT = 1;
const BACKUP_MARKER = "\n-- ----\n";
const BACKUP_BATCH = 100;
const BACKUP_KEEP = 10;
const BACKUP_UPLOAD_MAX = 52428800;
const BACKUP_SKIP_DATA = ['user_sessions'];
const BACKUP_REQUIRED_TABLES = ['users', 'products', 'stock', 'orders', 'orderline'];

function backup_dir(): string
{
    return dirname(__DIR__) . '/backups';
}

function backup_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function backup_names(): array
{
    $tables = [];
    $views = [];
    $result = q('SHOW FULL TABLES');
    foreach (mysqli_fetch_all($result, MYSQLI_NUM) as $row) {
        if ($row[1] === 'VIEW') {
            $views[] = $row[0];
        } else {
            $tables[] = $row[0];
        }
    }
    return [$tables, $views];
}

function backup_value(mysqli $conn, $value): string
{
    return $value === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
}

function backup_upload_limit(): int
{
    $toBytes = function (string $value): int {
        $value = trim($value);
        $number = (int) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    };
    $limits = array_filter([BACKUP_UPLOAD_MAX, $toBytes((string) ini_get('upload_max_filesize')), $toBytes((string) ini_get('post_max_size'))], fn($n) => $n > 0);
    return min($limits);
}

function backup_build(string $targetPath): void
{
    global $conn;
    [$tables, $views] = backup_names();
    if (!$tables) {
        throw new RuntimeException('The database has no tables to back up.');
    }
    $bodyPath = tempnam(sys_get_temp_dir(), 'ccb');
    if ($bodyPath === false) {
        throw new RuntimeException('A temporary file could not be created.');
    }
    $body = fopen($bodyPath, 'wb');
    if ($body === false) {
        @unlink($bodyPath);
        throw new RuntimeException('A temporary file could not be opened.');
    }
    $hash = hash_init('sha256');
    $emit = function (string $sql) use ($body, $hash): void {
        $chunk = $sql . ';' . BACKUP_MARKER;
        hash_update($hash, $chunk);
        if (fwrite($body, $chunk) === false) {
            throw new RuntimeException('The backup could not be written to disk.');
        }
    };
    try {
        $emit('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($views as $view) {
            $emit('DROP VIEW IF EXISTS ' . backup_ident($view));
        }
        foreach ($tables as $table) {
            $emit('DROP TABLE IF EXISTS ' . backup_ident($table));
        }
        foreach ($tables as $table) {
            $row = mysqli_fetch_row(q('SHOW CREATE TABLE ' . backup_ident($table)));
            $emit(rtrim((string) $row[1], "; \n"));
        }
        foreach ($tables as $table) {
            if (in_array($table, BACKUP_SKIP_DATA, true)) {
                continue;
            }
            $result = mysqli_query($conn, 'SELECT * FROM ' . backup_ident($table), MYSQLI_USE_RESULT);
            $columns = [];
            foreach (mysqli_fetch_fields($result) as $field) {
                $columns[] = backup_ident($field->name);
            }
            $head = 'INSERT INTO ' . backup_ident($table) . ' (' . implode(', ', $columns) . ") VALUES\n";
            $batch = [];
            while ($data = mysqli_fetch_row($result)) {
                $batch[] = '(' . implode(', ', array_map(fn($v) => backup_value($conn, $v), $data)) . ')';
                if (count($batch) >= BACKUP_BATCH) {
                    $emit($head . implode(",\n", $batch));
                    $batch = [];
                }
            }
            mysqli_free_result($result);
            if ($batch) {
                $emit($head . implode(",\n", $batch));
            }
        }
        foreach ($views as $view) {
            $row = mysqli_fetch_row(q('SHOW CREATE VIEW ' . backup_ident($view)));
            $sql = preg_replace('/^CREATE .*? VIEW (`(?:[^`]|``)+`)( \([^)]*\))? AS /s', 'CREATE VIEW $1$2 AS ', (string) $row[1], 1);
            $emit(rtrim($sql, "; \n"));
        }
        $emit('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Throwable $error) {
        fclose($body);
        @unlink($bodyPath);
        throw $error;
    }
    fclose($body);
    $header = '-- ' . BACKUP_TITLE . "\n-- Format: " . BACKUP_FORMAT . "\n-- Created: " . date('Y-m-d H:i:s') . "\n-- SHA256: " . hash_final($hash) . "\n\n";
    $out = fopen($targetPath, 'wb');
    $in = fopen($bodyPath, 'rb');
    $ok = $out !== false && $in !== false && fwrite($out, $header) !== false && stream_copy_to_stream($in, $out) !== false;
    if ($out) {
        fclose($out);
    }
    if ($in) {
        fclose($in);
    }
    @unlink($bodyPath);
    if (!$ok) {
        @unlink($targetPath);
        throw new RuntimeException('The backup file could not be saved.');
    }
}

function backup_file_name(string $prefix = 'C.Console-backup'): string
{
    return $prefix . '-' . date('Ymd-His') . '.sql';
}

function backup_save_safety(): string
{
    $dir = backup_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('The safety backup folder could not be created.');
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('The safety backup folder is not writable.');
    }
    $path = $dir . '/' . backup_file_name('pre-restore');
    backup_build($path);
    $old = glob($dir . '/pre-restore-*.sql') ?: [];
    sort($old);
    foreach (array_slice($old, 0, max(0, count($old) - BACKUP_KEEP)) as $file) {
        @unlink($file);
    }
    return $path;
}

function backup_validate(string $path): array
{
    $size = @filesize($path);
    if ($size === false || $size < 1) {
        throw new RuntimeException('The backup file is empty.');
    }
    if ($size > BACKUP_UPLOAD_MAX) {
        throw new RuntimeException('The backup file is too large.');
    }
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException('The backup file could not be read.');
    }
    $pattern = '/\A-- ' . preg_quote(BACKUP_TITLE, '/') . "\n-- Format: " . BACKUP_FORMAT . "\n-- Created: [0-9: -]{19}\n-- SHA256: ([a-f0-9]{64})\n\n/";
    if (!preg_match($pattern, $content, $match)) {
        throw new RuntimeException('This is not a C.Console backup file. Only files downloaded from this page can be restored.');
    }
    $body = substr($content, strlen($match[0]));
    unset($content);
    if (!hash_equals($match[1], hash('sha256', $body))) {
        throw new RuntimeException('The backup file is incomplete or has been modified, so it was not restored.');
    }
    $chunks = explode(BACKUP_MARKER, $body);
    unset($body);
    if (array_pop($chunks) !== '') {
        throw new RuntimeException('The backup file is incomplete.');
    }
    $created = [];
    $views = [];
    $statements = [];
    $targets = [];
    $name = '(`[A-Za-z0-9_]+`)';
    foreach ($chunks as $chunk) {
        if (substr($chunk, -1) !== ';') {
            throw new RuntimeException('The backup file contains an unfinished statement.');
        }
        $sql = substr($chunk, 0, -1);
        if (preg_match('/\ASET FOREIGN_KEY_CHECKS = [01]\z/', $sql)) {
        } elseif (preg_match('/\ADROP (?:TABLE|VIEW) IF EXISTS ' . $name . '\z/', $sql, $m)) {
            $targets[] = trim($m[1], '`');
        } elseif (preg_match('/\ACREATE TABLE ' . $name . ' \(/', $sql, $m)) {
            $created[] = trim($m[1], '`');
        } elseif (preg_match('/\ACREATE VIEW ' . $name . '(?: \([^)]*\))? AS /s', $sql, $m)) {
            $views[] = trim($m[1], '`');
        } elseif (preg_match('/\AINSERT INTO ' . $name . ' \(/', $sql, $m)) {
            $targets[] = trim($m[1], '`');
        } else {
            throw new RuntimeException('The backup file contains a statement that is not allowed, so it was not restored.');
        }
        $statements[] = $sql;
    }
    foreach (BACKUP_REQUIRED_TABLES as $required) {
        if (!in_array($required, $created, true)) {
            throw new RuntimeException('The backup file is missing the "' . $required . '" table.');
        }
    }
    $known = array_merge($created, $views);
    foreach (array_unique($targets) as $target) {
        if (!in_array($target, $known, true)) {
            throw new RuntimeException('The backup file refers to a table that it does not define.');
        }
    }
    if (end($statements) !== 'SET FOREIGN_KEY_CHECKS = 1') {
        throw new RuntimeException('The backup file is incomplete.');
    }
    return $statements;
}

function backup_apply(array $statements): void
{
    global $conn;
    @set_time_limit(0);
    try {
        foreach ($statements as $sql) {
            if (mysqli_query($conn, $sql) === false) {
                throw new RuntimeException(mysqli_error($conn));
            }
        }
    } finally {
        @mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 1');
    }
}

function backup_upload_error(?array $file): ?string
{
    if (!$file || !isset($file['error'])) {
        return 'Choose a backup file to restore.';
    }
    $error = (int) $file['error'];
    if ($error === UPLOAD_ERR_NO_FILE) {
        return 'Choose a backup file to restore.';
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return 'The backup file is larger than the upload limit of ' . round(backup_upload_limit() / 1048576, 1) . ' MB.';
    }
    if ($error !== UPLOAD_ERR_OK) {
        return 'The backup file could not be uploaded. Please try again.';
    }
    if (!is_uploaded_file((string) $file['tmp_name'])) {
        return 'The backup file could not be uploaded. Please try again.';
    }
    if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'sql') {
        return 'Only .sql backup files created by this page can be restored.';
    }
    if ((int) $file['size'] > BACKUP_UPLOAD_MAX) {
        return 'The backup file is too large.';
    }
    return null;
}
