<?php
declare(strict_types=1);

/**
 * Businesses sign in with their ZERO account. ZERO (zerosolutions.cl/abrir/repeat) redirects to
 * /auth/zero with a single-use ticket: base64url(json).base64url(HMAC-SHA256), signed with the
 * secret shared with ZERO (config 'zero_sso_secret'), valid for 2 minutes, only for confirmed emails.
 */

function zero_secret(): string
{
    return trim((string) config('zero_sso_secret', ''));
}

function b64url_decode(string $data): string|false
{
    return base64_decode(strtr($data, '-_', '+/'), true);
}

/** Returns [claims, null] or [null, reason]. */
function verify_zero_ticket(mixed $ticket): array
{
    $secret = zero_secret();
    if (strlen($secret) < 32) {
        return [null, 'config'];
    }
    if (!is_string($ticket) || substr_count($ticket, '.') !== 1) {
        return [null, 'invalid'];
    }
    [$payload, $sig] = explode('.', $ticket);
    $expected = hash_hmac('sha256', $payload, $secret, true);
    $given = b64url_decode($sig);
    if ($given === false || !hash_equals($expected, $given)) {
        return [null, 'signature'];
    }
    $claims = json_decode((string) b64url_decode($payload), true);
    if (!is_array($claims)) {
        return [null, 'invalid'];
    }
    $exp = $claims['exp'] ?? 0;
    if (!is_int($exp) || $exp <= time() || $exp - time() > 300) {
        return [null, 'expired'];
    }
    $ok = ($claims['iss'] ?? '') === 'zero' && ($claims['aud'] ?? '') === 'repeat'
        && filter_var($claims['email'] ?? '', FILTER_VALIDATE_EMAIL) && strlen((string) ($claims['nonce'] ?? '')) >= 16;
    if (!$ok) {
        return [null, 'invalid'];
    }
    // Each ticket works once.
    $used = q('INSERT IGNORE INTO rp_sso_nonces (nonce, expires_at) VALUES (?, ?)', [$claims['nonce'], gmdate('Y-m-d H:i:s', $exp)]);
    if ($used->rowCount() !== 1) {
        return [null, 'used'];
    }
    q('DELETE FROM rp_sso_nonces WHERE expires_at < ?', [now_utc()]);
    return [$claims, null];
}

/** Finds or creates the Repeat user for a ZERO account (matched by ZERO id, then email). */
function user_from_zero(array $claims): array
{
    $user = find_or_create_zero_user($claims);
    // The ticket also carries the plan paid at ZERO, so Repeat catches up even if ZERO's push failed.
    if (apply_zero_plan($user, $claims['plan'] ?? null)) {
        $user = q('SELECT * FROM rp_users WHERE id = ?', [$user['id']])->fetch();
    }
    return $user;
}

function find_or_create_zero_user(array $claims): array
{
    $email = mb_strtolower(trim($claims['email']));
    $name = mb_substr(trim((string) ($claims['name'] ?? '')), 0, 120) ?: explode('@', $email)[0];
    $zeroId = isset($claims['sub']) ? (int) $claims['sub'] : null;
    $user = q('SELECT * FROM rp_users WHERE zero_id = ? OR email = ? ORDER BY zero_id = ? DESC LIMIT 1', [$zeroId, $email, $zeroId])->fetch();
    if ($user) {
        q('UPDATE rp_users SET zero_id = COALESCE(zero_id, ?), email = ?, name = ?, last_login_at = ? WHERE id = ?', [$zeroId, $email, $name, now_utc(), $user['id']]);
        return q('SELECT * FROM rp_users WHERE id = ?', [$user['id']])->fetch();
    }
    q('INSERT INTO rp_users (zero_id, email, name, last_login_at) VALUES (?, ?, ?, ?)', [$zeroId, $email, $name, now_utc()]);
    return q('SELECT * FROM rp_users WHERE id = ?', [db()->lastInsertId()])->fetch();
}

function login_user(array $user): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    session_regenerate_id(true);
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    start_session();
    $user = empty($_SESSION['uid']) ? null : (q('SELECT * FROM rp_users WHERE id = ?', [$_SESSION['uid']])->fetch() ?: null);
    return $user;
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        redirect('/entrar');
    }
    return $user;
}

/** The signed-in owner's business; sends them to set one up if they have none yet. */
function require_business(): array
{
    $user = require_user();
    $business = q('SELECT * FROM rp_businesses WHERE owner_id = ? ORDER BY id LIMIT 1', [$user['id']])->fetch();
    if (!$business) {
        redirect('/panel/nuevo');
    }
    return $business;
}

/** Where "Entrar" sends people: ZERO's sign-in for this app. */
function zero_login_url(): string
{
    return rtrim((string) config('zero_url', 'https://zerosolutions.cl'), '/') . '/abrir/repeat';
}
