<div class="page-head">
  <div><h1>Sumar sellos</h1><p>Escanea el código de la tarjeta del cliente, o búscalo por su nombre o teléfono.</p></div>
</div>
<div class="scan-layout" data-scanner data-api="<?= e(u('/panel/api')) ?>" data-required="<?= (int) $business['stamps_required'] ?>">
  <div class="grid">
    <div class="camera">
      <video playsinline muted hidden></video>
      <div class="frame" hidden></div>
      <div class="laser" hidden></div>
      <div class="placeholder" data-cam-placeholder>
        <?= icon('camera', 40) ?>
        <p>Usa la cámara de tu celular para leer el código QR de la tarjeta.</p>
        <button type="button" class="btn btn-primary" data-cam-start><?= icon('scan', 18) ?> Abrir cámara</button>
        <p class="hint" data-cam-error hidden></p>
      </div>
    </div>
    <div class="glass box">
      <div class="toolbar" style="margin:0">
        <div class="search">
          <?= icon('search', 18) ?>
          <input class="input" type="search" placeholder="Buscar por nombre o teléfono" autocomplete="off" data-search aria-label="Buscar cliente">
        </div>
      </div>
      <div class="list" data-results style="margin-top:6px"></div>
    </div>
  </div>

  <aside class="glass result" data-result>
    <div class="empty" data-result-empty><?= icon('scan', 28) ?>Escanea o busca un cliente para sumarle un sello.</div>
    <div data-result-body hidden>
      <div class="who"><span class="avatar" data-r-initials></span><div><b data-r-name></b><div class="hint" data-r-meta></div></div></div>
      <div style="margin-top:16px" data-r-card></div>
      <div class="reward-ready" data-r-reward hidden style="margin-top:14px"><?= icon('gift', 22) ?><div><b data-r-reward-text></b><br><span style="color:var(--muted);font-size:13.5px"><?= e($business['reward']) ?></span></div></div>
      <div class="actions" style="margin-top:16px">
        <button type="button" class="btn btn-primary stamp-big wide" data-act="stamp"><?= icon('stamp', 22) ?> Sumar 1 sello</button>
        <button type="button" class="btn btn-glass" data-act="stamp2">+2 sellos</button>
        <button type="button" class="btn btn-glass" data-act="redeem"><?= icon('gift', 16) ?> Canjear</button>
        <button type="button" class="btn btn-ghost wide" data-act="undo" hidden><?= icon('undo', 16) ?> Deshacer último sello</button>
      </div>
    </div>
  </aside>
</div>
<template id="card-template"><?php partial('loyalty_card', ['business' => $business, 'stamps' => 0]); ?></template>
<script defer src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script defer src="<?= e(asset('js/scanner.js')) ?>"></script>
