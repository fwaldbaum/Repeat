<?php
declare(strict_types=1);

/**
 * Stamp cards. A customer belongs to one business; every function takes the business and only
 * touches its rows. A full card turns into an available reward and the count starts over.
 */

const CATEGORIES = ['Barbería', 'Peluquería', 'Cafetería', 'Restaurante', 'Manicure y estética', 'Lavado de autos', 'Veterinaria', 'Panadería', 'Gimnasio', 'Otro'];

function business_by_slug(string $slug): ?array
{
    return q('SELECT * FROM rp_businesses WHERE slug = ?', [$slug])->fetch() ?: null;
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $text), '-'));
    return substr($text ?: 'negocio', 0, 40);
}

function unique_slug(string $name): string
{
    $base = slugify($name);
    $slug = $base;
    for ($i = 2; business_by_slug($slug); $i++) {
        $slug = "{$base}-{$i}";
    }
    return $slug;
}

function hex_color(mixed $value, string $fallback): string
{
    $value = strtolower(trim((string) $value));
    return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $fallback;
}

/** Validates the card settings form; returns [fields, error]. */
function card_settings_from(array $in): array
{
    $name = trim(preg_replace('/\s+/u', ' ', (string) ($in['name'] ?? '')));
    $reward = trim(preg_replace('/\s+/u', ' ', (string) ($in['reward'] ?? '')));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        return [null, 'Escribe el nombre de tu negocio.'];
    }
    if (mb_strlen($reward) < 2 || mb_strlen($reward) > 80) {
        return [null, 'Escribe el premio, por ejemplo "Corte gratis".'];
    }
    $category = in_array($in['category'] ?? '', CATEGORIES, true) ? $in['category'] : 'Otro';
    return [[
        'name' => $name,
        'category' => $category,
        'reward' => $reward,
        'stamps_required' => min(20, max(3, (int) ($in['stamps_required'] ?? 10))),
        'welcome_stamps' => min(5, max(0, (int) ($in['welcome_stamps'] ?? 0))),
        'card_bg' => hex_color($in['card_bg'] ?? '', '#1e1b4b'),
        'card_fg' => hex_color($in['card_fg'] ?? '', '#ffffff'),
        'card_accent' => hex_color($in['card_accent'] ?? '', '#8b5cf6'),
        'message' => mb_substr(trim((string) ($in['message'] ?? '')), 0, 160),
    ], null];
}

/** Stores an uploaded logo (PNG, JPG or WebP up to 2 MB), resized to a 512px square PNG with GD. */
function store_logo(array $file, int $businessId): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('No pudimos recibir el logo. Inténtalo de nuevo.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('El logo pesa más de 2 MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    $src = match ($info[2] ?? 0) {
        IMAGETYPE_PNG => @imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
        default => false,
    };
    if (!$src) {
        throw new RuntimeException('El logo debe ser una imagen PNG, JPG o WebP.');
    }
    // Fit inside a transparent square so wallets and the card always get the same shape.
    $size = 512;
    [$w, $h] = [imagesx($src), imagesy($src)];
    $scale = min($size / $w, $size / $h);
    $dst = imagecreatetruecolor($size, $size);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    $nw = (int) round($w * $scale);
    $nh = (int) round($h * $scale);
    imagecopyresampled($dst, $src, intdiv($size - $nw, 2), intdiv($size - $nh, 2), 0, 0, $nw, $nh, $w, $h);
    $dir = PUBLIC_DIR . '/assets/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = "logo-{$businessId}-" . bin2hex(random_bytes(4)) . '.png';
    imagepng($dst, "{$dir}/{$name}", 8);
    return '/assets/uploads/' . $name;
}

function delete_logo(?string $path): void
{
    if ($path && preg_match('#^/assets/uploads/logo-\d+-[0-9a-f]{8}\.png$#', $path)) {
        @unlink(PUBLIC_DIR . $path);
    }
}

// ---- Customers -------------------------------------------------------------------------

/** Chilean mobile numbers to +569XXXXXXXX; other inputs keep their digits. */
function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 9 && $digits[0] === '9') {
        return '+56' . $digits;
    }
    if (strlen($digits) === 8) {
        return '+569' . $digits;
    }
    if (str_starts_with($digits, '56') && strlen($digits) === 11) {
        return '+' . $digits;
    }
    return $digits === '' ? '' : '+' . $digits;
}

function pretty_phone(string $phone): string
{
    return preg_match('/^\+569(\d{4})(\d{4})$/', $phone, $m) ? "+56 9 {$m[1]} {$m[2]}" : $phone;
}

function customer_by_token(string $token): ?array
{
    return q('SELECT * FROM rp_customers WHERE token = ?', [$token])->fetch() ?: null;
}

function customer_in(array $business, int $id): ?array
{
    return q('SELECT * FROM rp_customers WHERE id = ? AND business_id = ?', [$id, $business['id']])->fetch() ?: null;
}

/** Joins a customer (or returns their existing card for the same phone). Returns [customer, isNew]. */
function join_customer(array $business, string $name, string $phone, ?string $birthday): array
{
    $existing = q('SELECT * FROM rp_customers WHERE business_id = ? AND phone = ?', [$business['id'], $phone])->fetch();
    if ($existing) {
        return [$existing, false];
    }
    $welcome = (int) $business['welcome_stamps'];
    q(
        'INSERT INTO rp_customers (business_id, token, name, phone, birthday, stamps, total_stamps) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$business['id'], token(12), $name, $phone, $birthday, $welcome, $welcome],
    );
    $customer = q('SELECT * FROM rp_customers WHERE id = ?', [db()->lastInsertId()])->fetch();
    log_event($business, $customer, 'join', $welcome);
    return [$customer, true];
}

function log_event(array $business, array $customer, string $type, int $amount = 1): int
{
    q('INSERT INTO rp_events (business_id, customer_id, type, amount, created_at) VALUES (?, ?, ?, ?, ?)', [$business['id'], $customer['id'], $type, $amount, now_utc()]);
    return (int) db()->lastInsertId();
}

/**
 * Adds stamps; each full card becomes an available reward. Returns the updated customer, the
 * event id (for undo) and how many rewards this stamp completed.
 */
function add_stamp(array $business, array $customer, int $count = 1): array
{
    $count = min(5, max(1, $count));
    $required = (int) $business['stamps_required'];
    $pdo = db();
    $pdo->beginTransaction();
    $c = q('SELECT * FROM rp_customers WHERE id = ? AND business_id = ? FOR UPDATE', [$customer['id'], $business['id']])->fetch();
    $stamps = (int) $c['stamps'] + $count;
    $earned = intdiv($stamps, $required);
    q(
        'UPDATE rp_customers SET stamps = ?, rewards_available = rewards_available + ?, total_stamps = total_stamps + ?,
            visits = visits + 1, last_visit_at = ?, updated_at = ? WHERE id = ?',
        [$stamps % $required, $earned, $count, now_utc(), now_utc(), $c['id']],
    );
    $eventId = log_event($business, $c, 'stamp', $count);
    if ($earned) {
        log_event($business, $c, 'reward', $earned);
    }
    $pdo->commit();
    $updated = customer_in($business, (int) $c['id']);
    wallet_sync($business, $updated);
    return [$updated, $eventId, $earned];
}

function redeem_reward(array $business, array $customer): ?array
{
    $done = q(
        'UPDATE rp_customers SET rewards_available = rewards_available - 1, rewards_redeemed = rewards_redeemed + 1, updated_at = ?
          WHERE id = ? AND business_id = ? AND rewards_available > 0',
        [now_utc(), $customer['id'], $business['id']],
    );
    if ($done->rowCount() !== 1) {
        return null;
    }
    log_event($business, $customer, 'redeem');
    $updated = customer_in($business, (int) $customer['id']);
    wallet_sync($business, $updated);
    return $updated;
}

/** Reverts a stamp made in the last 10 minutes (a mis-scan). */
function undo_stamp(array $business, int $eventId): ?array
{
    $event = q(
        "SELECT * FROM rp_events WHERE id = ? AND business_id = ? AND type = 'stamp' AND undone_at IS NULL AND created_at > ?",
        [$eventId, $business['id'], now_utc(-600)],
    )->fetch();
    if (!$event) {
        return null;
    }
    $pdo = db();
    $pdo->beginTransaction();
    $c = q('SELECT * FROM rp_customers WHERE id = ? FOR UPDATE', [$event['customer_id']])->fetch();
    $required = (int) $business['stamps_required'];
    // Walk the stamps back, taking back a reward if this stamp completed one that is still unused.
    $stamps = (int) $c['stamps'] - (int) $event['amount'];
    $rewards = (int) $c['rewards_available'];
    while ($stamps < 0 && $rewards > 0) {
        $stamps += $required;
        $rewards--;
    }
    q(
        'UPDATE rp_customers SET stamps = ?, rewards_available = ?, total_stamps = GREATEST(0, total_stamps - ?), visits = GREATEST(0, visits - 1), updated_at = ? WHERE id = ?',
        [max(0, $stamps), $rewards, $event['amount'], now_utc(), $c['id']],
    );
    q('UPDATE rp_events SET undone_at = ? WHERE id = ?', [now_utc(), $eventId]);
    // A reward this stamp completed goes back too, so the history doesn't show it.
    $taken = (int) $c['rewards_available'] - $rewards;
    if ($taken > 0) {
        q(
            "UPDATE rp_events SET undone_at = ? WHERE customer_id = ? AND type = 'reward' AND undone_at IS NULL AND id > ? ORDER BY id LIMIT {$taken}",
            [now_utc(), $c['id'], $eventId],
        );
    }
    $pdo->commit();
    $updated = customer_in($business, (int) $c['id']);
    wallet_sync($business, $updated);
    return $updated;
}

/** What the card page and the scanner need about a customer. */
function customer_state(array $business, array $c): array
{
    return [
        'token' => $c['token'],
        'id' => (int) $c['id'],
        'name' => $c['name'],
        'phone' => pretty_phone($c['phone']),
        'stamps' => (int) $c['stamps'],
        'required' => (int) $business['stamps_required'],
        'rewards' => (int) $c['rewards_available'],
        'redeemed' => (int) $c['rewards_redeemed'],
        'visits' => (int) $c['visits'],
        'last_visit' => ago($c['last_visit_at']),
        'updated' => strtotime($c['updated_at'] . ' UTC'),
    ];
}

function dashboard_stats(array $business): array
{
    $id = $business['id'];
    $today = (new DateTime('today', new DateTimeZone('America/Santiago')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $week = gmdate('Y-m-d H:i:s', time() - 7 * 86400);
    $one = fn (string $sql, array $p) => (int) q($sql, $p)->fetchColumn();
    return [
        'customers' => $one('SELECT COUNT(*) FROM rp_customers WHERE business_id = ?', [$id]),
        'new_week' => $one('SELECT COUNT(*) FROM rp_customers WHERE business_id = ? AND created_at > ?', [$id, $week]),
        'stamps_today' => $one("SELECT COALESCE(SUM(amount), 0) FROM rp_events WHERE business_id = ? AND type = 'stamp' AND undone_at IS NULL AND created_at > ?", [$id, $today]),
        'stamps_week' => $one("SELECT COALESCE(SUM(amount), 0) FROM rp_events WHERE business_id = ? AND type = 'stamp' AND undone_at IS NULL AND created_at > ?", [$id, $week]),
        'rewards_ready' => $one('SELECT COALESCE(SUM(rewards_available), 0) FROM rp_customers WHERE business_id = ?', [$id]),
        'redeemed' => $one('SELECT COALESCE(SUM(rewards_redeemed), 0) FROM rp_customers WHERE business_id = ?', [$id]),
        'returning' => $one('SELECT COUNT(*) FROM rp_customers WHERE business_id = ? AND visits >= 2', [$id]),
    ];
}

/** Stamps per day for the last 14 days (Chile time, including daylight saving), oldest first. */
function stamps_by_day(array $business): array
{
    $rows = q(
        "SELECT created_at, amount FROM rp_events WHERE business_id = ? AND type = 'stamp' AND undone_at IS NULL AND created_at > ?",
        [$business['id'], now_utc(-15 * 86400)],
    )->fetchAll();
    $tz = new DateTimeZone('America/Santiago');
    $byDay = [];
    foreach ($rows as $r) {
        $d = (new DateTime($r['created_at'], new DateTimeZone('UTC')))->setTimezone($tz)->format('Y-m-d');
        $byDay[$d] = ($byDay[$d] ?? 0) + (int) $r['amount'];
    }
    $out = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = (new DateTime("-{$i} days", $tz))->format('Y-m-d');
        $out[] = ['date' => $d, 'count' => $byDay[$d] ?? 0];
    }
    return $out;
}

function recent_activity(array $business, int $limit = 8): array
{
    return q(
        "SELECT e.*, c.name, c.id AS cid FROM rp_events e JOIN rp_customers c ON c.id = e.customer_id
          WHERE e.business_id = ? AND e.undone_at IS NULL ORDER BY e.id DESC LIMIT {$limit}",
        [$business['id']],
    )->fetchAll();
}

/** Text for an activity row. */
function event_text(array $ev): string
{
    $n = (int) $ev['amount'];
    return match ($ev['type']) {
        'join' => 'se unió a tu tarjeta' . ($n ? " con {$n} " . ($n === 1 ? 'sello' : 'sellos') . ' de regalo' : ''),
        'stamp' => $n === 1 ? 'sumó un sello' : "sumó {$n} sellos",
        'reward' => 'completó la tarjeta y ganó su premio',
        'redeem' => 'canjeó su premio',
        default => $ev['type'],
    };
}
