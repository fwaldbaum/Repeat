<?php
/**
 * Copy this file to app/config.php (it is ignored by git: it holds passwords and keys).
 */
return [
    // Public address of Repeat, without a trailing slash, e.g. https://repeat.zerosolutions.cl
    'app_url' => 'https://repeat.zerosolutions.cl',
    // Folder when Repeat is not at the root of its domain, e.g. '/repeat'. Empty on its own subdomain.
    'base_path' => '',

    // MySQL. Tables start with "rp_", so it can share the ZERO database.
    'db' => ['host' => 'localhost', 'name' => 'micuenta_zero', 'user' => 'micuenta_zero', 'pass' => 'CAMBIA_ESTA_CLAVE'],

    // Long random string used to sign wallet tokens: php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => '',

    // Sign in with ZERO accounts. The secret must equal 'sso_secret' in ZERO's config.php.
    'zero_url' => 'https://zerosolutions.cl',
    'zero_sso_secret' => '',

    // Optional. Google Wallet: issuer id from pay.google.com/business/console and the JSON key of a
    // Google Cloud service account added as user of that issuer. Upload the key to app/keys/.
    'google_wallet' => [
        'issuer_id' => '',
        'service_account' => __DIR__ . '/keys/google-wallet.json',
    ],

    // Optional. Apple Wallet: Pass Type ID certificate (PEM with its private key) and Apple's WWDR
    // intermediate certificate, both in app/keys/. See README.
    'apple_wallet' => [
        'pass_type_id' => '',
        'team_id' => '',
        'certificate' => __DIR__ . '/keys/pass.pem',
        'key_password' => '',
        'wwdr' => __DIR__ . '/keys/wwdr.pem',
    ],
];
