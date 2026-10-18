<?php
declare(strict_types=1);

/**
 * Google Wallet loyalty cards (https://developers.google.com/wallet/retail/loyalty-cards).
 * "Agregar a Google Wallet" is a link carrying a JWT signed (RS256) with a Google Cloud service
 * account key; it creates the business's class and the customer's object on first save. Stamps
 * then PATCH the object through the REST API so the card updates on the phone.
 *
 * config 'google_wallet' => ['issuer_id' => '338...', 'service_account' => APP_DIR . '/keys/google.json']
 */

function gw_config(): ?array
{
    $c = config('google_wallet');
    if (!is_array($c) || empty($c['issuer_id']) || empty($c['service_account']) || !is_file($c['service_account'])) {
        return null;
    }
    $key = json_decode((string) file_get_contents($c['service_account']), true);
    if (empty($key['client_email']) || empty($key['private_key'])) {
        return null;
    }
    return ['issuer' => preg_replace('/\D/', '', (string) $c['issuer_id']), 'email' => $key['client_email'], 'key' => $key['private_key']];
}

function gw_enabled(): bool
{
    return gw_config() !== null;
}

function b64url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function gw_jwt(array $claims, string $privateKey): string
{
    $head = b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $body = b64url(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    openssl_sign("{$head}.{$body}", $sig, $privateKey, OPENSSL_ALGO_SHA256);
    return "{$head}.{$body}." . b64url($sig);
}

/** Absolute PNG for wallets: the business logo or Repeat's mark. */
function wallet_logo_url(array $business): string
{
    return url($business['logo'] ?: '/assets/img/repeat-mark-512.png');
}

function gw_class(array $business, string $issuer): array
{
    return [
        'id' => "{$issuer}.repeat_business_{$business['id']}",
        'issuerName' => $business['name'],
        'programName' => mb_substr($business['reward'], 0, 40),
        'programLogo' => ['sourceUri' => ['uri' => wallet_logo_url($business)], 'contentDescription' => ['defaultValue' => ['language' => 'es-419', 'value' => $business['name']]]],
        'hexBackgroundColor' => $business['card_bg'],
        'reviewStatus' => 'UNDER_REVIEW',
        'countryCode' => 'CL',
        'multipleDevicesAndHoldersAllowedStatus' => 'ONE_USER_ALL_DEVICES',
    ];
}

function gw_object(array $business, array $customer, string $issuer): array
{
    $required = (int) $business['stamps_required'];
    $rewards = (int) $customer['rewards_available'];
    return [
        'id' => "{$issuer}.repeat_card_{$customer['token']}",
        'classId' => "{$issuer}.repeat_business_{$business['id']}",
        'state' => 'ACTIVE',
        'accountId' => $customer['token'],
        'accountName' => $customer['name'],
        'loyaltyPoints' => ['label' => 'Sellos', 'balance' => ['string' => "{$customer['stamps']} de {$required}"]],
        'secondaryLoyaltyPoints' => ['label' => 'Premios listos', 'balance' => ['int' => $rewards]],
        'barcode' => ['type' => 'QR_CODE', 'value' => $customer['token'], 'alternateText' => $customer['name']],
        'textModulesData' => array_values(array_filter([
            ['id' => 'reward', 'header' => "Premio al completar {$required} sellos", 'body' => $business['reward']],
            $business['message'] ? ['id' => 'message', 'header' => $business['name'], 'body' => $business['message']] : null,
            // Free plan cards carry Repeat's name; Pro removes it.
            isset($business['owner_id']) && !is_pro($business) ? ['id' => 'repeat', 'header' => 'Tarjeta digital', 'body' => 'Funciona con Repeat by ZERO · repeat.zerosolutions.cl'] : null,
        ])),
        'linksModuleData' => ['uris' => [['uri' => url("/t/{$customer['token']}"), 'description' => 'Ver mi tarjeta', 'id' => 'card']]],
        'hexBackgroundColor' => $business['card_bg'],
    ];
}

/** The "Agregar a Google Wallet" link, or null when Google Wallet isn't configured. */
function gw_save_url(array $business, array $customer): ?string
{
    $gw = gw_config();
    if (!$gw) {
        return null;
    }
    $jwt = gw_jwt([
        'iss' => $gw['email'],
        'aud' => 'google',
        'typ' => 'savetowallet',
        'iat' => time(),
        'origins' => [rtrim((string) config('app_url'), '/')],
        'payload' => [
            'loyaltyClasses' => [gw_class($business, $gw['issuer'])],
            'loyaltyObjects' => [gw_object($business, $customer, $gw['issuer'])],
        ],
    ], $gw['key']);
    return 'https://pay.google.com/gp/v/save/' . $jwt;
}

/** OAuth access token for the Wallet REST API, cached in the session-less file cache for 50 minutes. */
function gw_access_token(array $gw): ?string
{
    $cache = APP_DIR . '/storage/google-token.json';
    $cached = is_file($cache) ? json_decode((string) file_get_contents($cache), true) : null;
    if (($cached['exp'] ?? 0) > time() + 60) {
        return $cached['token'];
    }
    $assertion = gw_jwt([
        'iss' => $gw['email'],
        'scope' => 'https://www.googleapis.com/auth/wallet_object.issuer',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => time(),
        'exp' => time() + 3600,
    ], $gw['key']);
    $res = http_request('POST', 'https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $assertion,
    ]);
    $token = $res['json']['access_token'] ?? null;
    if ($token) {
        @file_put_contents($cache, json_encode(['token' => $token, 'exp' => time() + 3000]), LOCK_EX);
    } else {
        error_log('[repeat] google token: ' . $res['status'] . ' ' . substr($res['body'], 0, 200));
    }
    return $token;
}

/** Pushes the new stamp count to the customer's saved card. A card never saved answers 404; that's fine. */
function gw_update(array $business, array $customer): void
{
    $gw = gw_config();
    if (!$gw || !($token = gw_access_token($gw))) {
        return;
    }
    $object = gw_object($business, $customer, $gw['issuer']);
    $res = http_request('PATCH', 'https://walletobjects.googleapis.com/walletobjects/v1/loyaltyObject/' . rawurlencode($object['id']), $object, $token);
    if (!in_array($res['status'], [200, 404], true)) {
        error_log('[repeat] google wallet update: ' . $res['status'] . ' ' . substr($res['body'], 0, 200));
    }
}

/** Small curl helper: form body for the OAuth call, JSON otherwise. */
function http_request(string $method, string $url, array $data = [], ?string $bearer = null): array
{
    $ch = curl_init($url);
    $isToken = str_contains($url, 'oauth2.googleapis.com');
    $headers = ['Accept: application/json'];
    if ($bearer) {
        $headers[] = "Authorization: Bearer {$bearer}";
    }
    if (!$isToken) {
        $headers[] = 'Content-Type: application/json';
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $isToken ? http_build_query($data) : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = $body === false ? curl_error($ch) : '';
    curl_close($ch);
    return ['status' => $status, 'body' => $body === false ? $error : $body, 'json' => json_decode((string) $body, true)];
}

/**
 * Keeps every wallet copy of the card in step after a stamp, reward or undo. Runs after the response
 * is sent (see json_out), so the person at the counter never waits on Google or Apple.
 */
function wallet_sync(array $business, array $customer): void
{
    if (!gw_enabled() && !apple_enabled()) {
        return;
    }
    register_shutdown_function(function () use ($business, $customer): void {
        try {
            gw_update($business, $customer);
            apple_push($customer['token']);
        } catch (Throwable $err) {
            error_log('[repeat] wallet sync: ' . $err->getMessage());
        }
    });
}
