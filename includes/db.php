<?php
// ============================================================
// includes/db.php — MySQL database helper functions
// Keeps the old db_read/db_write API so the rest of the app works.
// ============================================================

require_once __DIR__ . '/../config.php';

function db_table_from_file(string $file): string {
    $table = preg_replace('/\.json$/', '', basename($file));
    $allowed = ['users', 'posts', 'comments', 'friends', 'messages', 'notifications', 'likes'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Unknown database table: ' . $table);
    }
    return $table;
}

function db_columns(string $table): array {
    return [
        'users' => ['id','username','email','password','first_name','last_name','bio','profile_picture','role','is_banned','created_at','updated_at'],
        'posts' => ['id','user_id','caption','image','filter','created_at','updated_at'],
        'comments' => ['id','post_id','user_id','content','created_at'],
        'friends' => ['id','requester_id','receiver_id','status','created_at','updated_at'],
        'messages' => ['id','sender_id','receiver_id','content','is_read','created_at'],
        'notifications' => ['id','user_id','actor_id','type','reference_id','reference_type','message','is_read','created_at'],
        'likes' => ['id','user_id','post_id','created_at'],
    ][$table];
}

function db_bool_columns(string $table): array {
    return [
        'users' => ['is_banned'],
        'messages' => ['is_read'],
        'notifications' => ['is_read'],
    ][$table] ?? [];
}

function db_int_columns(string $table): array {
    return [
        'users' => ['id'],
        'posts' => ['id','user_id'],
        'comments' => ['id','post_id','user_id'],
        'friends' => ['id','requester_id','receiver_id'],
        'messages' => ['id','sender_id','receiver_id'],
        'notifications' => ['id','user_id','actor_id','reference_id'],
        'likes' => ['id','user_id','post_id'],
    ][$table] ?? ['id'];
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'zazagram';
    $user = getenv('DB_USER') ?: 'zazagram';
    $pass = getenv('DB_PASS') ?: 'zazagram';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function db_normalize_row(string $table, array $row): array {
    foreach (db_int_columns($table) as $col) {
        if (array_key_exists($col, $row) && $row[$col] !== null) $row[$col] = (int)$row[$col];
    }
    foreach (db_bool_columns($table) as $col) {
        if (array_key_exists($col, $row)) $row[$col] = (bool)$row[$col];
    }
    return $row;
}

/** Read a database table and return its rows as a PHP array. */
function db_read(string $file): array {
    $table = db_table_from_file($file);
    $stmt = db()->query("SELECT * FROM `{$table}` ORDER BY id ASC");
    $rows = $stmt->fetchAll();
    return array_map(fn($row) => db_normalize_row($table, $row), $rows);
}

/**
 * Write a PHP array to a database table.
 * This mirrors the old JSON behavior by syncing the complete collection.
 */
function db_write(string $file, array $data): bool {
    $table = db_table_from_file($file);
    $cols = db_columns($table);
    $pdo = db();

    $pdo->beginTransaction();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec("DELETE FROM `{$table}`");

        if (!empty($data)) {
            $colSql = '`' . implode('`,`', $cols) . '`';
            $placeholders = ':' . implode(',:', $cols);
            $stmt = $pdo->prepare("INSERT INTO `{$table}` ({$colSql}) VALUES ({$placeholders})");

            foreach ($data as $row) {
                foreach ($cols as $col) {
                    if (!array_key_exists($col, $row)) $row[$col] = null;
                }
                foreach (db_bool_columns($table) as $col) {
                    if (array_key_exists($col, $row)) $row[$col] = $row[$col] ? 1 : 0;
                }
                $stmt->execute(array_intersect_key($row, array_flip($cols)));
            }
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Get the next auto-increment ID for a collection. */
function db_next_id(array $records): int {
    if (empty($records)) return 1;
    return max(array_column($records, 'id')) + 1;
}

/** Find a single record by field value. */
function db_find_one(array $records, string $field, $value): ?array {
    foreach ($records as $record) {
        if (isset($record[$field]) && $record[$field] == $value) return $record;
    }
    return null;
}

/** Find all records matching a field value. */
function db_find_all(array $records, string $field, $value): array {
    return array_values(array_filter($records, fn($r) => isset($r[$field]) && $r[$field] == $value));
}

/** Update a record in an array by ID and return updated array. */
function db_update(array $records, int $id, array $changes): array {
    return array_map(fn($r) => $r['id'] === $id ? array_merge($r, $changes) : $r, $records);
}

/** Delete a record from an array by ID. */
function db_delete(array $records, int $id): array {
    return array_values(array_filter($records, fn($r) => $r['id'] !== $id));
}

/** Return a JSON HTTP response and exit. */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/** Current UTC timestamp string. */
function now(): string {
    return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
}
