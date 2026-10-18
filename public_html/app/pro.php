<?php
declare(strict_types=1);

/**
 * Repeat Pro. The plan is bought at ZERO (zerosolutions.cl/precios, paid with Flow) and belongs to the
 * owner's ZERO account; ZERO Plus includes it. ZERO tells Repeat in two ways: a signed POST to
 * /zero/plan right after the payment, and the 'plan' claim in every sign-in ticket.
 * Every Pro feature checks is_pro() when it runs, so an expired plan simply stops them.
 */

const PLAN_RANK = ['free' => 0, 'pro' => 1, 'max' => 2];
/** Shown in Repeat; the price charged lives in ZERO's app/plans.php ('repeat_pro'). */
const PRO_PRICE = 6990;
const PRO_LIST_PRICE = 14990;

function clp(int $amount): string
{
    return '$' . number_format($amount, 0, ',', '.');
}

/** What Pro adds, for the panel and the upgrade page. */
const PRO_FEATURES = [
    ['sparkles', 'Tu marca, sin la de Repeat', 'Tus tarjetas, la página para unirse y el afiche quedan solo con tu logo.'],
    ['stamp', 'Ícono de sello a tu medida', 'Estrellas, corazones, café, tijeras y más, en vez del check.'],
    ['cake', 'Regalo de cumpleaños automático', 'En la semana de su cumpleaños el cliente recibe un premio, sin que hagas nada.'],
    ['zap', 'Horas de sellos dobles', 'Elige días y horario: cada visita en esa franja suma el doble. Llena tus horas tranquilas.'],
    ['user-plus', 'Trae a un amigo', 'Cada cliente tiene su enlace para invitar. Cuando el amigo se une, ambos ganan sellos.'],
    ['download', 'Exportar clientes', 'Descarga tu lista de clientes con sus visitas y sellos en Excel (CSV).'],
];

function owner_of(array $business): array
{
    static $cache = [];
    return $cache[$business['owner_id']] ??= q('SELECT * FROM rp_users WHERE id = ?', [$business['owner_id']])->fetch() ?: [];
}

/** The owner's current tier: 'pro' or 'max' while paid, else 'free'. */
function plan_tier(array $user): string
{
    $tier = $user['plan'] ?? 'free';
    if ($tier === 'free' || !isset(PLAN_RANK[$tier])) {
        return 'free';
    }
    $until = $user['plan_expires_at'] ?? null;
    return $until === null || $until > now_utc() ? $tier : 'free';
}

function is_pro(array $business): bool
{
    return plan_tier(owner_of($business)) !== 'free';
}

function pro_upgrade_url(): string
{
    return rtrim((string) config('zero_url', 'https://zerosolutions.cl'), '/') . '/cuenta/plan?plan=repeat_pro&periodo=mes';
}

/**
 * Stores a plan claim from ZERO ({tier, exp} in Unix seconds). Only moves forward: a higher tier or the
 * same tier for longer, so a stale message can't shorten a plan. Returns true when something changed.
 */
function apply_zero_plan(array $user, mixed $claim): bool
{
    if (!is_array($claim) || empty(PLAN_RANK[$claim['tier'] ?? '']) || !is_int($claim['exp'] ?? null) || $claim['exp'] <= time()) {
        return false;
    }
    $exp = gmdate('Y-m-d H:i:s', $claim['exp']);
    $current = plan_tier($user);
    if ($current !== 'free') {
        $diff = PLAN_RANK[$claim['tier']] - PLAN_RANK[$current];
        if ($diff < 0 || ($diff === 0 && $user['plan_expires_at'] !== null && $exp <= $user['plan_expires_at']) || $user['plan_expires_at'] === null) {
            return false;
        }
    }
    q('UPDATE rp_users SET plan = ?, plan_expires_at = ? WHERE id = ?', [$claim['tier'], $exp, $user['id']]);
    return true;
}

/** POST /zero/plan from ZERO, signed (HMAC-SHA256, hex) over the exact body with the shared secret. */
function zero_plan_webhook(): never
{
    $body = (string) file_get_contents('php://input');
    $sig = (string) ($_SERVER['HTTP_X_ZERO_SIGNATURE'] ?? '');
    $secret = zero_secret();
    if (strlen($secret) < 32 || !hash_equals(hash_hmac('sha256', $body, $secret), $sig)) {
        json_out(['error' => 'Firma no válida'], 401);
    }
    $data = json_decode($body, true);
    if (!is_array($data) || ($data['app'] ?? '') !== 'repeat' || !is_string($data['email'] ?? null)
        || !is_int($data['iat'] ?? null) || abs(time() - $data['iat']) > 300) {
        json_out(['error' => 'Datos inválidos'], 422);
    }
    $user = q('SELECT * FROM rp_users WHERE email = ?', [mb_strtolower(trim($data['email']))])->fetch();
    if (!$user) {
        // The plan arrives in the sign-in ticket the first time they open Repeat.
        json_out(['ok' => true, 'found' => false, 'applied' => false]);
    }
    json_out(['ok' => true, 'found' => true, 'applied' => apply_zero_plan($user, ['tier' => $data['tier'] ?? 'free', 'exp' => $data['exp'] ?? null])]);
}

// ---- Stamp icons ------------------------------------------------------------------------

const STAMP_ICONS = [
    'check' => ['Check', '<path d="M20 6 9 17l-5-5"/>'],
    'star' => ['Estrella', '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
    'heart' => ['Corazón', '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>'],
    'coffee' => ['Café', '<path d="M10 2v2"/><path d="M14 2v2"/><path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 1 1 0 8h-1"/><path d="M6 2v2"/>'],
    'scissors' => ['Tijeras', '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>'],
    'sparkles' => ['Brillo', '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>'],
    'gift' => ['Regalo', '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>'],
    'zap' => ['Rayo', '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>'],
];

/** The icon a business's stamps use; anything but the check needs Pro. */
function stamp_icon_of(array $business): string
{
    $icon = $business['stamp_icon'] ?? 'check';
    return isset(STAMP_ICONS[$icon]) && ($icon === 'check' || is_pro($business)) ? $icon : 'check';
}

function stamp_svg(string $icon): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . ($icon === 'check' ? 3 : 2.2)
        . '" stroke-linecap="round" stroke-linejoin="round">' . (STAMP_ICONS[$icon][1] ?? STAMP_ICONS['check'][1]) . '</svg>';
}

// ---- Double-stamp hours -------------------------------------------------------------------

const WEEKDAYS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];

function double_days(array $business): array
{
    return array_values(array_filter(array_map('intval', explode(',', (string) $business['double_days'])), fn ($d) => $d >= 0 && $d <= 6));
}

/** True while the business's double-stamp window is open (Chile time). */
function double_active(array $business, ?DateTime $now = null): bool
{
    if (!is_pro($business) || !$business['double_from'] || !$business['double_to'] || !double_days($business)) {
        return false;
    }
    $now ??= new DateTime('now', new DateTimeZone('America/Santiago'));
    $time = $now->format('H:i:s');
    return in_array((int) $now->format('w'), double_days($business), true)
        && $time >= $business['double_from'] && $time < $business['double_to'];
}

/** "Martes y jueves de 15:00 a 17:00" */
function double_label(array $business): string
{
    $names = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábados', 0 => 'domingos'];
    $days = array_map(fn ($d) => $names[$d], array_values(array_intersect(array_keys(WEEKDAYS), double_days($business))));
    if (!$days || !$business['double_from'] || !$business['double_to']) {
        return '';
    }
    $list = count($days) > 1 ? implode(', ', array_slice($days, 0, -1)) . ' y ' . end($days) : $days[0];
    return ucfirst($list) . ' de ' . substr($business['double_from'], 0, 5) . ' a ' . substr($business['double_to'], 0, 5);
}

// ---- Birthday gift ----------------------------------------------------------------------------

/**
 * During the week that starts on the customer's birthday, a Pro business with the gift on hands out one
 * reward, once a year. Checked whenever the card is opened or scanned.
 */
function maybe_birthday_gift(array $business, array $customer): array
{
    if (!$business['birthday_gift'] || !$customer['birthday'] || !is_pro($business)) {
        return $customer;
    }
    $tz = new DateTimeZone('America/Santiago');
    $today = new DateTime('today', $tz);
    $year = (int) $today->format('Y');
    if ((int) $customer['birthday_gift_year'] === $year) {
        return $customer;
    }
    $md = substr($customer['birthday'], 5);
    $day = DateTime::createFromFormat('!Y-m-d', "{$year}-" . ($md === '02-29' && !checkdate(2, 29, $year) ? '02-28' : $md), $tz);
    if (!$day || $today < $day || $today > (clone $day)->modify('+6 days')) {
        return $customer;
    }
    $done = q(
        'UPDATE rp_customers SET rewards_available = rewards_available + 1, birthday_gift_year = ?, updated_at = ?
          WHERE id = ? AND business_id = ? AND (birthday_gift_year IS NULL OR birthday_gift_year <> ?)',
        [$year, now_utc(), $customer['id'], $business['id'], $year],
    );
    if ($done->rowCount() !== 1) {
        return $customer;
    }
    log_event($business, $customer, 'birthday');
    $updated = customer_in($business, (int) $customer['id']);
    wallet_sync($business, $updated);
    return $updated;
}

function is_birthday_week(array $customer): bool
{
    if (!$customer['birthday']) {
        return false;
    }
    $tz = new DateTimeZone('America/Santiago');
    $today = new DateTime('today', $tz);
    $day = DateTime::createFromFormat('!Y-m-d', $today->format('Y') . substr($customer['birthday'], 4), $tz);
    return $day && $today >= $day && $today <= (clone $day)->modify('+6 days');
}

// ---- Referrals --------------------------------------------------------------------------------

function referral_on(array $business): bool
{
    return (int) $business['referral_stamps'] > 0 && is_pro($business);
}

/** A new customer who came from a friend's link: both get the business's referral stamps. */
function reward_referral(array $business, array $newCustomer, string $refToken): void
{
    if (!referral_on($business)) {
        return;
    }
    $friend = customer_by_token($refToken);
    if (!$friend || (int) $friend['business_id'] !== (int) $business['id'] || (int) $friend['id'] === (int) $newCustomer['id']) {
        return;
    }
    q('UPDATE rp_customers SET referred_by = ? WHERE id = ? AND referred_by IS NULL', [$friend['id'], $newCustomer['id']]);
    $n = (int) $business['referral_stamps'];
    foreach ([$friend, $newCustomer] as $c) {
        [$c] = add_stamp($business, $c, $n, 'referral');
    }
}
