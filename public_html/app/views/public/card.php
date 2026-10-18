<?php
$required = (int) $business['stamps_required'];
$state = customer_state($business, $customer);
$left = $required - $state['stamps'];
$pro = is_pro($business);
$double = $pro ? double_label($business) : '';
$inviteUrl = url('/c/' . $business['slug'] . '?ref=' . $customer['token']);
$refStamps = (int) $business['referral_stamps'];
?>
<!doctype html>
<html lang="es">
<head><?php partial('head', ['title' => $title]); ?><?php partial('tint', ['business' => $business]); ?>
<meta name="robots" content="noindex">
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
</head>
<body class="card-page">
<?php partial('scene'); ?>
<main class="center-page">
  <div class="stack" data-card-page data-token="<?= e($customer['token']) ?>" data-state-url="<?= e(u("/t/{$customer['token']}/estado")) ?>" data-updated="<?= (int) $state['updated'] ?>">
    <div class="join-head">
      <?php if (isset($_GET['nueva'])): ?>
        <span class="pill"><span>Listo</span> Ya tienes tu tarjeta</span>
      <?php elseif (isset($_GET['existente'])): ?>
        <span class="pill"><span>Hola de nuevo</span> Ya tenías una tarjeta</span>
      <?php endif; ?>
      <h1>Hola, <?= e(explode(' ', $customer['name'])[0]) ?></h1>
      <p data-left><?= $left === $required && !$state['rewards'] ? "Junta {$required} sellos y gana {$business['reward']}." : ($left === 1 ? 'Te falta 1 sello para tu premio.' : "Te faltan {$left} sellos para tu premio.") ?></p>
    </div>

    <?php partial('loyalty_card', ['business' => $business, 'stamps' => $state['stamps'], 'customer' => $customer]); ?>

    <div class="progress-line">
      <div class="bar"><span data-bar style="--w:<?= round($state['stamps'] / $required * 100) ?>%"></span></div>
    </div>

    <?php if ($pro && $business['birthday_gift'] && is_birthday_week($customer)): ?>
      <div class="reward-ready" style="border-color:rgba(236,72,153,.4)"><?= icon('cake', 24) ?><div><b>Feliz cumpleaños, <?= e(explode(' ', $customer['name'])[0]) ?></b><br><span style="color:var(--muted);font-size:14px"><?= e($business['name']) ?> te dejó un premio de regalo en tu tarjeta.</span></div></div>
    <?php endif; ?>

    <div class="reward-ready" data-reward <?= $state['rewards'] ? '' : 'hidden' ?>>
      <?= icon('gift', 24) ?>
      <div><b data-reward-text><?= $state['rewards'] === 1 ? 'Tienes 1 premio listo' : "Tienes {$state['rewards']} premios listos" ?></b><br><span style="color:var(--muted);font-size:14px">Muestra tu código en caja para canjear: <?= e($business['reward']) ?>.</span></div>
    </div>

    <div class="glass card-qr">
      <div class="qr-box" data-qr="<?= e($customer['token']) ?>" role="img" aria-label="Código de tu tarjeta"></div>
      <p>Muestra este código en <?= e($business['name']) ?> cada vez que vengas.</p>
    </div>

    <?php if ($double): ?>
      <div class="alert alert-info" style="width:100%;max-width:420px"><?= icon('zap', 18) ?><span><b>Sellos dobles</b> <?= e(mb_strtolower(mb_substr($double, 0, 1)) . mb_substr($double, 1)) ?>.</span></div>
    <?php endif; ?>

    <?php if (referral_on($business)): ?>
      <div class="glass invite" data-invite data-url="<?= e($inviteUrl) ?>">
        <div><b>Invita a un amigo</b><p>Cuando se una con tu enlace, ganan <?= $refStamps ?> <?= $refStamps === 1 ? 'sello' : 'sellos' ?> cada uno.</p></div>
        <div class="invite-actions">
          <a class="btn btn-primary btn-sm" href="https://wa.me/?text=<?= rawurlencode("Únete a la tarjeta de sellos de {$business['name']} con mi enlace y ganamos sellos los dos: {$inviteUrl}") ?>" target="_blank" rel="noopener"><?= icon('message', 15) ?> WhatsApp</a>
          <button type="button" class="btn btn-glass btn-sm" data-invite-copy><?= icon('copy', 15) ?> Copiar enlace</button>
        </div>
      </div>
    <?php endif; ?>

    <div class="wallet-buttons">
      <?php if (gw_enabled()): ?>
        <a class="wallet-btn" href="<?= e(u("/t/{$customer['token']}/google")) ?>"><?= icon('wallet', 20) ?> Agregar a Google Wallet</a>
      <?php endif; ?>
      <?php if (apple_enabled()): ?>
        <a class="wallet-btn" href="<?= e(u("/t/{$customer['token']}/apple")) ?>"><?= icon('wallet', 20) ?> Agregar a Apple Wallet</a>
      <?php endif; ?>
      <button type="button" class="btn btn-glass btn-block" data-save-link><?= icon('smartphone', 18) ?> Guardar en mi celular</button>
      <p class="hint" style="text-align:center" data-save-hint hidden>Guarda esta página en favoritos o en la pantalla de inicio (menú del navegador, "Agregar a pantalla de inicio"). Es tu tarjeta.</p>
    </div>

    <?php if ($business['message']): ?><p class="hint" style="max-width:420px;text-align:center"><?= e($business['message']) ?></p><?php endif; ?>
    <?php if (!$pro): ?><a class="powered" href="<?= e(u('/')) ?>"><img src="<?= e(u('/assets/img/repeat-mark.svg')) ?>" alt="">Funciona con Repeat</a><?php endif; ?>
  </div>
</main>
<canvas class="celebrate" aria-hidden="true"></canvas>
</body>
</html>
