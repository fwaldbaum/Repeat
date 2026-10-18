<?php
declare(strict_types=1);

// Local development with `php -S`: let the built-in server return static files.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require __DIR__ . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if (base_path() !== '' && str_starts_with($path, base_path())) {
    $path = substr($path, strlen(base_path()));
}
$path = rtrim($path, '/') ?: '/';

try {
    route($method, $path);
} catch (PDOException $err) {
    error_log('[repeat] database: ' . $err->getMessage());
    http_response_code(503);
    render('public/error', ['title' => 'Servicio no disponible · Repeat', 'code' => 503, 'message' => 'No pudimos conectar con la base de datos. Inténtalo de nuevo en unos minutos.']);
}

function route(string $method, string $path): void
{
    // Apple Wallet calls these from its own servers, with its own token instead of a session.
    if (str_starts_with($path, '/wallet/')) {
        apple_web_service($method, substr($path, strlen('/wallet')));
    }
    // Everything else that writes needs the session's CSRF token (forms and the panel's fetch calls).
    if ($method === 'POST' && !csrf_ok()) {
        http_response_code(419);
        str_starts_with($path, '/panel/api/')
            ? json_out(['error' => 'La página expiró. Recárgala e inténtalo de nuevo.'], 419)
            : render('public/error', ['title' => 'La página expiró · Repeat', 'code' => 419, 'message' => 'Por seguridad, vuelve atrás, recarga la página e inténtalo de nuevo.']);
        return;
    }

    switch ("{$method} {$path}") {
        case 'GET /':
            render('public/home', ['title' => 'Repeat · Tarjetas de sellos digitales para tu negocio', 'layout' => 'none']);
            return;
        case 'GET /entrar':
            if (current_user()) {
                redirect('/panel');
            }
            redirect(zero_login_url());
        case 'GET /auth/zero':
            [$claims, $reason] = verify_zero_ticket($_GET['ticket'] ?? null);
            if (!$claims) {
                render('public/error', [
                    'title' => 'No pudimos entrar · Repeat', 'code' => 401,
                    'message' => match ($reason) {
                        'config' => 'El inicio de sesión con ZERO no está configurado en Repeat todavía.',
                        'expired', 'used' => 'El acceso expiró o ya se usó. Vuelve a abrir Repeat desde tu cuenta ZERO.',
                        default => 'El acceso desde tu cuenta ZERO no es válido. Vuelve a abrir Repeat desde zerosolutions.cl.',
                    },
                    'action' => ['label' => 'Volver a intentar', 'href' => zero_login_url()],
                ]);
                return;
            }
            login_user(user_from_zero($claims));
            redirect('/panel');
        case 'POST /salir':
            logout_user();
            redirect('/');

        // ---- Owner panel ---------------------------------------------------------
        case 'GET /panel/nuevo':
            $user = require_user();
            if (q('SELECT 1 FROM rp_businesses WHERE owner_id = ?', [$user['id']])->fetchColumn()) {
                redirect('/panel');
            }
            render('panel/onboarding', ['title' => 'Crea tu tarjeta · Repeat', 'layout' => 'none', 'user' => $user]);
            return;
        case 'POST /panel/nuevo':
            create_business_action();
            return;
        case 'GET /panel':
            $business = require_business();
            render('panel/dashboard', [
                'title' => 'Inicio · Repeat', 'layout' => 'panel', 'section' => 'inicio', 'business' => $business,
                'stats' => dashboard_stats($business), 'days' => stamps_by_day($business), 'activity' => recent_activity($business),
                'top' => q('SELECT * FROM rp_customers WHERE business_id = ? AND visits > 0 ORDER BY total_stamps DESC, last_visit_at DESC LIMIT 5', [$business['id']])->fetchAll(),
            ]);
            return;
        case 'GET /panel/escanear':
            $business = require_business();
            render('panel/scan', ['title' => 'Sumar sellos · Repeat', 'layout' => 'panel', 'section' => 'escanear', 'business' => $business]);
            return;
        case 'GET /panel/clientes':
            clients_page();
            return;
        case 'GET /panel/tarjeta':
            $business = require_business();
            render('panel/card', ['title' => 'Tu tarjeta · Repeat', 'layout' => 'panel', 'section' => 'tarjeta', 'business' => $business]);
            return;
        case 'POST /panel/tarjeta':
            save_card_action();
            return;
        case 'GET /panel/compartir':
            $business = require_business();
            render('panel/share', ['title' => 'Compartir · Repeat', 'layout' => 'panel', 'section' => 'compartir', 'business' => $business]);
            return;
        case 'GET /panel/afiche':
            $business = require_business();
            render('panel/poster', ['title' => 'Afiche · ' . $business['name'], 'layout' => 'none', 'business' => $business]);
            return;

        // ---- Panel API (scanner and customer actions, JSON) ---------------------------
        case 'GET /panel/api/buscar':
            $business = require_business();
            json_out(['results' => search_customers($business, (string) ($_GET['q'] ?? ''))]);
        case 'POST /panel/api/sello':
        case 'POST /panel/api/canjear':
            customer_action($path === '/panel/api/sello' ? 'stamp' : 'redeem');
            return;
        case 'POST /panel/api/deshacer':
            $business = require_business();
            $c = undo_stamp($business, (int) ($_POST['event'] ?? 0));
            $c ? json_out(['customer' => customer_state($business, $c)]) : json_out(['error' => 'Ese sello ya no se puede deshacer.'], 422);
    }

    if ($method === 'GET' && preg_match('#^/panel/api/cliente/([\w-]{8,32})$#', $path, $m)) {
        $business = require_business();
        $c = customer_from_scan($business, $m[1]);
        $c ? json_out(['customer' => customer_state($business, $c)]) : json_out(['error' => 'Esta tarjeta no es de tu negocio.'], 404);
    }
    if ($method === 'GET' && preg_match('#^/panel/clientes/(\d+)$#', $path, $m)) {
        $business = require_business();
        $c = customer_in($business, (int) $m[1]) ?? not_found();
        render('panel/customer', [
            'title' => $c['name'] . ' · Repeat', 'layout' => 'panel', 'section' => 'clientes', 'business' => $business, 'customer' => $c,
            'events' => q('SELECT * FROM rp_events WHERE customer_id = ? AND undone_at IS NULL ORDER BY id DESC LIMIT 30', [$c['id']])->fetchAll(),
        ]);
        return;
    }

    // ---- Customers (public) -------------------------------------------------------------
    if (preg_match('#^/c/([a-z0-9-]{2,60})$#', $path, $m)) {
        $business = business_by_slug($m[1]) ?? not_found();
        $method === 'POST' ? join_action($business) : render('public/join', ['title' => $business['name'] . ' · Tarjeta de sellos', 'layout' => 'none', 'business' => $business]);
        return;
    }
    if ($method === 'GET' && preg_match('#^/t/([\w-]{8,32})(/estado|/google|/apple)?$#', $path, $m)) {
        $customer = customer_by_token($m[1]) ?? not_found();
        $business = q('SELECT * FROM rp_businesses WHERE id = ?', [$customer['business_id']])->fetch();
        switch ($m[2] ?? '') {
            case '/estado':
                json_out(customer_state($business, $customer));
            case '/google':
                redirect(gw_save_url($business, $customer) ?? "/t/{$customer['token']}");
            case '/apple':
                apple_enabled() ? apple_send_pass($business, $customer) : redirect("/t/{$customer['token']}");
        }
        render('public/card', ['title' => 'Tu tarjeta de ' . $business['name'], 'layout' => 'none', 'business' => $business, 'customer' => $customer]);
        return;
    }

    not_found();
}

// ---- Actions ------------------------------------------------------------------------------

function create_business_action(): void
{
    $user = require_user();
    [$fields, $error] = card_settings_from($_POST);
    if ($error) {
        flash('error', $error);
        redirect('/panel/nuevo');
    }
    q(
        'INSERT INTO rp_businesses (owner_id, slug, name, category, stamps_required, reward, welcome_stamps, card_bg, card_fg, card_accent, message)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$user['id'], unique_slug($fields['name']), $fields['name'], $fields['category'], $fields['stamps_required'], $fields['reward'],
            $fields['welcome_stamps'], $fields['card_bg'], $fields['card_fg'], $fields['card_accent'], $fields['message']],
    );
    flash('success', 'Tu tarjeta está lista. Comparte el código QR para que tus clientes se unan.');
    redirect('/panel/compartir');
}

function save_card_action(): void
{
    $business = require_business();
    [$fields, $error] = card_settings_from($_POST);
    if ($error) {
        flash('error', $error);
        redirect('/panel/tarjeta');
    }
    $logo = $business['logo'];
    if (!empty($_POST['logo_remove'])) {
        delete_logo($logo);
        $logo = null;
    }
    if (($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $new = store_logo($_FILES['logo'], (int) $business['id']);
            delete_logo($logo);
            $logo = $new;
        } catch (RuntimeException $err) {
            flash('error', $err->getMessage());
        }
    }
    q(
        'UPDATE rp_businesses SET name = ?, category = ?, stamps_required = ?, reward = ?, welcome_stamps = ?, card_bg = ?, card_fg = ?,
            card_accent = ?, message = ?, logo = ?, updated_at = ? WHERE id = ?',
        [$fields['name'], $fields['category'], $fields['stamps_required'], $fields['reward'], $fields['welcome_stamps'], $fields['card_bg'],
            $fields['card_fg'], $fields['card_accent'], $fields['message'], $logo, now_utc(), $business['id']],
    );
    flash('success', 'Guardamos tu tarjeta. Las tarjetas de tus clientes ya muestran el cambio.');
    redirect('/panel/tarjeta');
}

function clients_page(): void
{
    $business = require_business();
    $search = trim((string) ($_GET['q'] ?? ''));
    $order = ['recientes' => 'COALESCE(last_visit_at, created_at) DESC', 'sellos' => 'total_stamps DESC', 'nuevos' => 'created_at DESC', 'premios' => 'rewards_available DESC, stamps DESC'];
    $sort = array_key_exists($_GET['orden'] ?? '', $order) ? $_GET['orden'] : 'recientes';
    $params = [$business['id']];
    $where = 'business_id = ?';
    if ($search !== '') {
        $where .= ' AND (name LIKE ? OR phone LIKE ?)';
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
        $digits = preg_replace('/\D/', '', $search);
        array_push($params, $like, $digits !== '' ? "%{$digits}%" : $like);
    }
    render('panel/customers', [
        'title' => 'Clientes · Repeat', 'layout' => 'panel', 'section' => 'clientes', 'business' => $business, 'search' => $search, 'sort' => $sort,
        'customers' => q("SELECT * FROM rp_customers WHERE {$where} ORDER BY {$order[$sort]} LIMIT 300", $params)->fetchAll(),
        'total' => (int) q('SELECT COUNT(*) FROM rp_customers WHERE business_id = ?', [$business['id']])->fetchColumn(),
    ]);
}

function search_customers(array $business, string $term): array
{
    $term = trim($term);
    if (mb_strlen($term) < 2) {
        return [];
    }
    $digits = preg_replace('/\D/', '', $term);
    $rows = q(
        'SELECT * FROM rp_customers WHERE business_id = ? AND (name LIKE ? OR (? <> \'\' AND phone LIKE ?)) ORDER BY last_visit_at DESC LIMIT 8',
        [$business['id'], '%' . $term . '%', $digits, '%' . $digits . '%'],
    )->fetchAll();
    return array_map(fn ($c) => customer_state($business, $c), $rows);
}

/** A scanned QR holds the card token (or the card's full URL); either way it must be from this business. */
function customer_from_scan(array $business, string $scanned): ?array
{
    if (preg_match('#/t/([\w-]{8,32})#', $scanned, $m)) {
        $scanned = $m[1];
    }
    $c = customer_by_token($scanned);
    return $c && (int) $c['business_id'] === (int) $business['id'] ? $c : null;
}

function customer_action(string $action): void
{
    $business = require_business();
    $c = customer_from_scan($business, (string) ($_POST['token'] ?? ''));
    if (!$c) {
        json_out(['error' => 'Esta tarjeta no es de tu negocio.'], 404);
    }
    if ($action === 'redeem') {
        $updated = redeem_reward($business, $c);
        $updated ? json_out(['customer' => customer_state($business, $updated)]) : json_out(['error' => 'Este cliente no tiene premios para canjear.'], 422);
    }
    if (too_many('stamp:' . $c['id'], 6, 60)) {
        json_out(['error' => 'Demasiados sellos seguidos para este cliente. Espera un minuto.'], 429);
    }
    [$updated, $eventId, $earned] = add_stamp($business, $c, (int) ($_POST['count'] ?? 1));
    json_out(['customer' => customer_state($business, $updated), 'event' => $eventId, 'earned' => $earned]);
}

function join_action(array $business): void
{
    $name = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? '')));
    $phone = normalize_phone((string) ($_POST['phone'] ?? ''));
    $birthday = (string) ($_POST['birthday'] ?? '');
    $error = match (true) {
        !empty($_POST['website']) => 'spam',
        mb_strlen($name) < 2 || mb_strlen($name) > 80 => 'Escribe tu nombre.',
        strlen($phone) < 9 || strlen($phone) > 16 => 'Escribe un teléfono válido, por ejemplo 9 1234 5678.',
        too_many('join:' . client_ip(), 12, 3600) => 'Demasiados registros desde esta conexión. Inténtalo más tarde.',
        default => null,
    };
    if ($error === 'spam') {
        redirect("/c/{$business['slug']}");
    }
    if ($error) {
        http_response_code(422);
        render('public/join', ['title' => $business['name'] . ' · Tarjeta de sellos', 'layout' => 'none', 'business' => $business, 'error' => $error, 'old' => ['name' => $name, 'phone' => (string) ($_POST['phone'] ?? '')]]);
        return;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $birthday);
    [$customer, $isNew] = join_customer($business, $name, $phone, $date ? $date->format('Y-m-d') : null);
    redirect("/t/{$customer['token']}" . ($isNew ? '?nueva=1' : '?existente=1'));
}
