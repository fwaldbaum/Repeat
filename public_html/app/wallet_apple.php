<?php
declare(strict_types=1);

/**
 * Apple Wallet store cards (.pkpass). A pass is a zip with pass.json, images, a manifest of SHA-1
 * hashes and a detached PKCS#7 signature made with the Pass Type ID certificate. Saved passes
 * register with the web service below; each stamp sends an empty push through APNs and the iPhone
 * downloads the new pass. https://developer.apple.com/documentation/walletpasses
 *
 * config 'apple_wallet' => [
 *   'pass_type_id' => 'pass.cl.zerosolutions.repeat', 'team_id' => 'ABCDE12345',
 *   'certificate' => APP_DIR . '/keys/pass.pem',   // certificate + private key, PEM
 *   'key_password' => '', 'wwdr' => APP_DIR . '/keys/wwdr.pem',
 * ]
 */

function apple_config(): ?array
{
    $c = config('apple_wallet');
    if (!is_array($c) || empty($c['pass_type_id']) || empty($c['team_id'])) {
        return null;
    }
    foreach (['certificate', 'wwdr'] as $file) {
        if (empty($c[$file]) || !is_file($c[$file])) {
            return null;
        }
    }
    return $c + ['key_password' => ''];
}

function apple_enabled(): bool
{
    return apple_config() !== null && class_exists('ZipArchive');
}

/** Per-pass token Wallet sends back on every web-service call. */
function apple_auth_token(string $serial): string
{
    return substr(hash_hmac('sha256', 'apple-pass:' . $serial, (string) config('app_secret', '')), 0, 40);
}

function rgb(string $hex): string
{
    [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
    return "rgb({$r}, {$g}, {$b})";
}

function apple_pass_json(array $business, array $customer, array $c): array
{
    $required = (int) $business['stamps_required'];
    $back = [
        ['key' => 'how', 'label' => 'Cómo funciona', 'value' => "Muestra este código en {$business['name']} cada vez que vengas. Al juntar {$required} sellos ganas: {$business['reward']}."],
        ['key' => 'card', 'label' => 'Tu tarjeta en la web', 'value' => url("/t/{$customer['token']}")],
    ];
    if ($business['message']) {
        array_unshift($back, ['key' => 'message', 'label' => $business['name'], 'value' => $business['message']]);
    }
    if (!is_pro($business)) {
        $back[] = ['key' => 'repeat', 'label' => 'Tarjeta digital', 'value' => 'Funciona con Repeat by ZERO'];
    }
    return [
        'formatVersion' => 1,
        'passTypeIdentifier' => $c['pass_type_id'],
        'teamIdentifier' => $c['team_id'],
        'serialNumber' => $customer['token'],
        'organizationName' => $business['name'],
        'description' => "Tarjeta de sellos de {$business['name']}",
        'logoText' => $business['name'],
        'backgroundColor' => rgb($business['card_bg']),
        'foregroundColor' => rgb($business['card_fg']),
        'labelColor' => rgb($business['card_accent']),
        'webServiceURL' => url('/wallet'),
        'authenticationToken' => apple_auth_token($customer['token']),
        'barcodes' => [['format' => 'PKBarcodeFormatQR', 'message' => $customer['token'], 'messageEncoding' => 'iso-8859-1', 'altText' => $customer['name']]],
        'storeCard' => [
            'primaryFields' => [['key' => 'stamps', 'label' => 'SELLOS', 'value' => "{$customer['stamps']} de {$required}", 'changeMessage' => 'Sumaste un sello: ahora tienes %@']],
            'secondaryFields' => [
                ['key' => 'reward', 'label' => 'PREMIO', 'value' => $business['reward']],
                ['key' => 'ready', 'label' => 'LISTOS', 'value' => (int) $customer['rewards_available'], 'changeMessage' => 'Tienes %@ premio(s) listos para canjear'],
            ],
            'auxiliaryFields' => [['key' => 'name', 'label' => 'CLIENTE', 'value' => $customer['name']]],
            'backFields' => $back,
        ],
    ];
}

/** PNG of the logo resized to fit $w x $h (transparent background). */
function png_fit(string $source, int $w, int $h): string
{
    $src = imagecreatefrompng($source);
    $scale = min($w / imagesx($src), $h / imagesy($src));
    $nw = max(1, (int) round(imagesx($src) * $scale));
    $nh = max(1, (int) round(imagesy($src) * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, imagesx($src), imagesy($src));
    ob_start();
    imagepng($dst);
    return (string) ob_get_clean();
}

/** Builds and signs the .pkpass; returns its bytes. */
function apple_build_pass(array $business, array $customer): string
{
    $c = apple_config() ?? throw new RuntimeException('Apple Wallet no está configurado.');
    $logo = PUBLIC_DIR . ($business['logo'] ?: '/assets/img/repeat-mark-512.png');
    $files = [
        'pass.json' => json_encode(apple_pass_json($business, $customer, $c), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'icon.png' => png_fit($logo, 29, 29),
        'icon@2x.png' => png_fit($logo, 58, 58),
        'icon@3x.png' => png_fit($logo, 87, 87),
        'logo.png' => png_fit($logo, 50, 50),
        'logo@2x.png' => png_fit($logo, 100, 100),
        'logo@3x.png' => png_fit($logo, 150, 150),
    ];
    $manifest = json_encode(array_map('sha1', $files), JSON_UNESCAPED_SLASHES);

    $tmp = tempnam(sys_get_temp_dir(), 'pkp');
    $sigFile = $tmp . '.sig';
    file_put_contents($tmp, $manifest);
    $cert = 'file://' . realpath($c['certificate']);
    $ok = openssl_pkcs7_sign($tmp, $sigFile, $cert, [$cert, (string) $c['key_password']], [], PKCS7_BINARY | PKCS7_DETACHED, realpath($c['wwdr']));
    if (!$ok) {
        @unlink($tmp);
        throw new RuntimeException('No se pudo firmar la tarjeta de Apple: ' . openssl_error_string());
    }
    // openssl writes S/MIME; Wallet wants the raw DER signature.
    $smime = (string) file_get_contents($sigFile);
    preg_match('/filename="?smime\.p7s"?\s+(.+?)\s+------/s', $smime, $m);
    $files['manifest.json'] = $manifest;
    $files['signature'] = base64_decode(preg_replace('/\s+/', '', $m[1] ?? ''));
    @unlink($tmp);
    @unlink($sigFile);

    $zipFile = tempnam(sys_get_temp_dir(), 'pkz');
    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::OVERWRITE);
    foreach ($files as $name => $bytes) {
        $zip->addFromString($name, $bytes);
    }
    $zip->close();
    $bytes = (string) file_get_contents($zipFile);
    @unlink($zipFile);
    return $bytes;
}

function apple_send_pass(array $business, array $customer): never
{
    $bytes = apple_build_pass($business, $customer);
    header('Content-Type: application/vnd.apple.pkpass');
    header('Content-Disposition: attachment; filename="' . slugify($business['name']) . '.pkpass"');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', strtotime($customer['updated_at'] . ' UTC')) . ' GMT');
    header('Content-Length: ' . strlen($bytes));
    echo $bytes;
    exit;
}

/** Wallet's web service: /wallet/v1/... (register devices, list updated passes, serve the latest pass). */
function apple_web_service(string $method, string $path): never
{
    $c = apple_config();
    if (!$c) {
        http_response_code(404);
        exit;
    }
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $authorized = fn (string $serial) => hash_equals('ApplePass ' . apple_auth_token($serial), $auth);

    if ($method === 'POST' && $path === '/v1/log') {
        error_log('[repeat] wallet log: ' . substr((string) file_get_contents('php://input'), 0, 500));
        http_response_code(200);
        exit;
    }
    if (preg_match('#^/v1/devices/([\w.-]+)/registrations/([\w.-]+)/([\w-]+)$#', $path, $m)) {
        [, $device, $type, $serial] = $m;
        if ($type !== $c['pass_type_id'] || !$authorized($serial) || !customer_by_token($serial)) {
            http_response_code(401);
            exit;
        }
        if ($method === 'DELETE') {
            q('DELETE FROM rp_apple_devices WHERE device_id = ? AND serial = ?', [$device, $serial]);
            http_response_code(200);
            exit;
        }
        $push = (string) (json_decode((string) file_get_contents('php://input'), true)['pushToken'] ?? '');
        if ($method !== 'POST' || !preg_match('/^[0-9a-f]{32,128}$/i', $push)) {
            http_response_code(400);
            exit;
        }
        $new = q('INSERT IGNORE INTO rp_apple_devices (device_id, serial, push_token) VALUES (?, ?, ?)', [$device, $serial, $push])->rowCount() === 1;
        if (!$new) {
            q('UPDATE rp_apple_devices SET push_token = ? WHERE device_id = ? AND serial = ?', [$push, $device, $serial]);
        }
        http_response_code($new ? 201 : 200);
        exit;
    }
    if ($method === 'GET' && preg_match('#^/v1/devices/([\w.-]+)/registrations/([\w.-]+)$#', $path, $m)) {
        $since = (int) ($_GET['passesUpdatedSince'] ?? 0);
        $rows = q(
            'SELECT c.token, UNIX_TIMESTAMP(c.updated_at) AS t FROM rp_apple_devices d JOIN rp_customers c ON c.token = d.serial WHERE d.device_id = ?',
            [$m[1]],
        )->fetchAll();
        $changed = array_values(array_filter($rows, fn ($r) => (int) $r['t'] > $since));
        if (!$changed) {
            http_response_code(204);
            exit;
        }
        json_out(['serialNumbers' => array_column($changed, 'token'), 'lastUpdated' => (string) max(array_column($rows, 't'))]);
    }
    if ($method === 'GET' && preg_match('#^/v1/passes/([\w.-]+)/([\w-]+)$#', $path, $m)) {
        $customer = customer_by_token($m[2]);
        if ($m[1] !== $c['pass_type_id'] || !$customer || !$authorized($m[2])) {
            http_response_code(401);
            exit;
        }
        $business = q('SELECT * FROM rp_businesses WHERE id = ?', [$customer['business_id']])->fetch();
        $since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '') ?: 0;
        if ($since && strtotime($customer['updated_at'] . ' UTC') <= $since) {
            http_response_code(304);
            exit;
        }
        apple_send_pass($business, $customer);
    }
    http_response_code(404);
    exit;
}

/** Empty push to every iPhone holding this pass; Wallet then fetches the new version. */
function apple_push(string $serial): void
{
    $c = apple_config();
    if (!$c) {
        return;
    }
    $tokens = q('SELECT push_token FROM rp_apple_devices WHERE serial = ?', [$serial])->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tokens as $pushToken) {
        $ch = curl_init("https://api.push.apple.com/3/device/{$pushToken}");
        curl_setopt_array($ch, [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_HTTPHEADER => ["apns-topic: {$c['pass_type_id']}", 'apns-push-type: background'],
            CURLOPT_SSLCERT => $c['certificate'],
            CURLOPT_SSLKEY => $c['certificate'],
            CURLOPT_KEYPASSWD => (string) $c['key_password'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($status === 410) {
            // The device removed the pass.
            q('DELETE FROM rp_apple_devices WHERE push_token = ?', [$pushToken]);
        } elseif ($status !== 200) {
            error_log("[repeat] apns {$status} for {$serial}");
        }
    }
}
