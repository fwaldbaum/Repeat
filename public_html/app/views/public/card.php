<?php
$required = (int) $business['stamps_required'];
$state = customer_state($business, $customer);
$left = $required - $state['stamps'];
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

    <div class="reward-ready" data-reward <?= $state['rewards'] ? '' : 'hidden' ?>>
      <?= icon('gift', 24) ?>
      <div><b data-reward-text><?= $state['rewards'] === 1 ? 'Tienes 1 premio listo' : "Tienes {$state['rewards']} premios listos" ?></b><br><span style="color:var(--muted);font-size:14px">Muestra tu código en caja para canjear: <?= e($business['reward']) ?>.</span></div>
    </div>

    <div class="glass card-qr">
      <div class="qr-box" data-qr="<?= e($customer['token']) ?>" role="img" aria-label="Código de tu tarjeta"></div>
      <p>Muestra este código en <?= e($business['name']) ?> cada vez que vengas.</p>
    </div>

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
    <a class="powered" href="<?= e(u('/')) ?>"><img src="<?= e(u('/assets/img/repeat-mark.svg')) ?>" alt="">Funciona con Repeat</a>
  </div>
</main>
<canvas class="celebrate" aria-hidden="true"></canvas>
</body>
</html>
