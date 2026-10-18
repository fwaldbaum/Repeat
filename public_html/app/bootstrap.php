<?php
declare(strict_types=1);

define('APP_DIR', __DIR__);
define('PUBLIC_DIR', dirname(__DIR__));

$configFile = APP_DIR . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Falta app/config.php: copia app/config.example.php como app/config.php y completa los datos.');
}
$GLOBALS['rp_config'] = require $configFile;

date_default_timezone_set('America/Santiago');
mb_internal_encoding('UTF-8');

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['rp_config'][$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Path prefix when Repeat lives in a folder (e.g. "/repeat"); empty on its own domain. */
function base_path(): string
{
    return rtrim((string) config('base_path', ''), '/');
}

/** Site-relative link that respects base_path. */
function u(string $path = '/'): string
{
    return base_path() . $path;
}

/** Absolute URL, for QR codes, wallets and links that leave the site. */
function url(string $path = '/'): string
{
    return rtrim((string) config('app_url', ''), '/') . $path;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function redirect(string $path, int $status = 303): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : u($path)), true, $status);
    exit;
}

function json_out(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // Send the answer now; wallet updates registered for shutdown keep running after it.
    if (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
    } elseif (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    exit;
}

function not_found(): never
{
    http_response_code(404);
    render('public/error', ['title' => 'Página no encontrada · Repeat', 'code' => 404, 'message' => 'Esta página no existe o el enlace cambió.']);
    exit;
}

/** Random URL-safe token. */
function token(int $bytes = 12): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

// ---- Database -----------------------------------------------------------------------

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $c = config('db');
    $pdo = new PDO(
        "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
        $c['user'],
        $c['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false],
    );
    $pdo->exec("SET time_zone = '+00:00'");
    migrate($pdo);
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function now_utc(int $offsetSeconds = 0): string
{
    return gmdate('Y-m-d H:i:s', time() + $offsetSeconds);
}

/** UTC DATETIME shown in Chilean time. */
function local_time(?string $utc, string $format = 'j M, H:i'): string
{
    if (!$utc) {
        return '';
    }
    $months = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $dt = new DateTime($utc, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('America/Santiago'));
    return str_replace($dt->format('M'), $months[(int) $dt->format('n')], $dt->format($format));
}

/** "hace 5 min" */
function ago(?string $utc): string
{
    if (!$utc) {
        return 'nunca';
    }
    $s = max(0, time() - strtotime($utc . ' UTC'));
    return match (true) {
        $s < 60 => 'recién',
        $s < 3600 => 'hace ' . intdiv($s, 60) . ' min',
        $s < 86400 => 'hace ' . intdiv($s, 3600) . ' h',
        $s < 86400 * 30 => 'hace ' . intdiv($s, 86400) . ' d',
        default => local_time($utc, 'j M Y'),
    };
}

/**
 * Idempotent schema. Tables are prefixed "rp_" so Repeat can share a database with ZERO.
 * New changes go at the end as a new version; never rewrite earlier ones.
 */
/** ADD COLUMN only when missing (MySQL has no ADD COLUMN IF NOT EXISTS). */
function add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $exists = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $exists->execute([$table, $column]);
    if (!$exists->fetchColumn()) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function migrate(PDO $pdo): void
{
    $version = 2;
    try {
        if ((int) $pdo->query("SELECT v FROM rp_meta WHERE k = 'schema'")->fetchColumn() >= $version) {
            return;
        }
    } catch (PDOException) {
    }
    $t = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_meta (k VARCHAR(40) PRIMARY KEY, v VARCHAR(255) NOT NULL) {$t}");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        zero_id INT UNSIGNED NULL,
        email VARCHAR(190) NOT NULL,
        name VARCHAR(120) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login_at DATETIME NULL,
        UNIQUE KEY rp_users_email (email),
        UNIQUE KEY rp_users_zero (zero_id)
    ) {$t}");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_businesses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        slug VARCHAR(60) NOT NULL,
        name VARCHAR(80) NOT NULL,
        category VARCHAR(40) NOT NULL DEFAULT '',
        stamps_required TINYINT UNSIGNED NOT NULL DEFAULT 10,
        reward VARCHAR(80) NOT NULL,
        welcome_stamps TINYINT UNSIGNED NOT NULL DEFAULT 0,
        card_bg VARCHAR(7) NOT NULL DEFAULT '#1e1b4b',
        card_fg VARCHAR(7) NOT NULL DEFAULT '#ffffff',
        card_accent VARCHAR(7) NOT NULL DEFAULT '#8b5cf6',
        logo VARCHAR(160) NULL,
        message VARCHAR(160) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY rp_businesses_slug (slug),
        KEY rp_businesses_owner (owner_id),
        CONSTRAINT rp_businesses_owner_fk FOREIGN KEY (owner_id) REFERENCES rp_users (id) ON DELETE CASCADE
    ) {$t}");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_customers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        business_id INT UNSIGNED NOT NULL,
        token VARCHAR(32) NOT NULL,
        name VARCHAR(80) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        birthday DATE NULL,
        stamps SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        rewards_available SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        rewards_redeemed SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        total_stamps INT UNSIGNED NOT NULL DEFAULT 0,
        visits INT UNSIGNED NOT NULL DEFAULT 0,
        last_visit_at DATETIME NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY rp_customers_token (token),
        UNIQUE KEY rp_customers_phone (business_id, phone),
        KEY rp_customers_recent (business_id, last_visit_at),
        CONSTRAINT rp_customers_business_fk FOREIGN KEY (business_id) REFERENCES rp_businesses (id) ON DELETE CASCADE
    ) {$t}");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        business_id INT UNSIGNED NOT NULL,
        customer_id INT UNSIGNED NOT NULL,
        type VARCHAR(12) NOT NULL,
        amount SMALLINT NOT NULL DEFAULT 1,
        undone_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        KEY rp_events_business (business_id, created_at),
        KEY rp_events_customer (customer_id, created_at),
        CONSTRAINT rp_events_customer_fk FOREIGN KEY (customer_id) REFERENCES rp_customers (id) ON DELETE CASCADE
    ) {$t}");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_sso_nonces (nonce VARCHAR(64) PRIMARY KEY, expires_at DATETIME NOT NULL) {$t}");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bucket VARCHAR(190) NOT NULL,
        created_at DATETIME NOT NULL,
        KEY rp_attempts_bucket (bucket, created_at)
    ) {$t}");
    // Apple Wallet: devices that saved a pass, so a stamp can push the update to them.
    $pdo->exec("CREATE TABLE IF NOT EXISTS rp_apple_devices (
        device_id VARCHAR(128) NOT NULL,
        serial VARCHAR(32) NOT NULL,
        push_token VARCHAR(128) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (device_id, serial),
        KEY rp_apple_serial (serial)
    ) {$t}");
    // ---- v2: Repeat Pro (sold at ZERO) and its card features ----
    add_column($pdo, 'rp_users', 'plan', "VARCHAR(10) NOT NULL DEFAULT 'free'");
    add_column($pdo, 'rp_users', 'plan_expires_at', 'DATETIME NULL');
    add_column($pdo, 'rp_businesses', 'stamp_icon', "VARCHAR(16) NOT NULL DEFAULT 'check'");
    add_column($pdo, 'rp_businesses', 'birthday_gift', 'TINYINT(1) NOT NULL DEFAULT 0');
    add_column($pdo, 'rp_businesses', 'double_days', "VARCHAR(20) NOT NULL DEFAULT ''");
    add_column($pdo, 'rp_businesses', 'double_from', 'TIME NULL');
    add_column($pdo, 'rp_businesses', 'double_to', 'TIME NULL');
    add_column($pdo, 'rp_businesses', 'referral_stamps', 'TINYINT UNSIGNED NOT NULL DEFAULT 0');
    add_column($pdo, 'rp_customers', 'referred_by', 'INT UNSIGNED NULL');
    add_column($pdo, 'rp_customers', 'birthday_gift_year', 'SMALLINT UNSIGNED NULL');

    $pdo->prepare("INSERT INTO rp_meta (k, v) VALUES ('schema', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)")->execute([(string) $version]);
}

// ---- Session, CSRF, flash, rate limits -----------------------------------------------

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('rp_sid');
    session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'path' => base_path() ?: '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30));
    $dir = APP_DIR . '/storage/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (is_writable($dir)) {
        session_save_path($dir);
    }
    session_start();
}

function csrf_token(): string
{
    start_session();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    start_session();
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

function flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flash(): array
{
    start_session();
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function too_many(string $bucket, int $max, int $windowSeconds): bool
{
    if (random_int(1, 50) === 1) {
        q('DELETE FROM rp_attempts WHERE created_at < ?', [now_utc(-86400)]);
    }
    $count = (int) q('SELECT COUNT(*) FROM rp_attempts WHERE bucket = ? AND created_at > ?', [$bucket, now_utc(-$windowSeconds)])->fetchColumn();
    if ($count >= $max) {
        return true;
    }
    q('INSERT INTO rp_attempts (bucket, created_at) VALUES (?, ?)', [$bucket, now_utc()]);
    return false;
}

// ---- Views -----------------------------------------------------------------------------

/** Renders app/views/<name>.php inside $vars['layout']: "panel" for the business area, else "none" (full pages). */
function render(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_DIR . "/views/{$name}.php";
    $content = ob_get_clean();
    require APP_DIR . '/views/layouts/' . ($vars['layout'] ?? 'none') . '.php';
}

function partial(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require APP_DIR . "/views/partials/{$name}.php";
}

/** Inline Lucide icon (https://lucide.dev, ISC). No emojis anywhere in the UI. */
function icon(string $name, int $size = 20, string $class = ''): string
{
    static $paths = null;
    $paths ??= require APP_DIR . '/icons.php';
    return '<svg class="icon ' . e($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** Cache-busted asset URL. */
function asset(string $path): string
{
    $file = PUBLIC_DIR . '/assets/' . $path;
    return u('/assets/' . $path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

require APP_DIR . '/auth.php';
require APP_DIR . '/cards.php';
require APP_DIR . '/pro.php';
require APP_DIR . '/wallet_google.php';
require APP_DIR . '/wallet_apple.php';
