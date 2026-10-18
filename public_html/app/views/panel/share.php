<?php $joinUrl = url('/c/' . $business['slug']); ?>
<div class="page-head">
  <div><h1>Compartir</h1><p>Tus clientes se unen escaneando este código o abriendo el enlace.</p></div>
  <a class="btn btn-primary" href="<?= e(u('/panel/afiche')) ?>" target="_blank" rel="noopener"><?= icon('printer', 18) ?> Imprimir afiche</a>
</div>
<div class="share-grid">
  <div class="glass card-qr" style="max-width:none">
    <div class="qr-box" data-qr="<?= e($joinUrl) ?>" role="img" aria-label="Código QR para unirse"></div>
    <p>Escanéalo con la cámara de tu celular para probarlo.</p>
    <button type="button" class="btn btn-glass btn-sm" data-download-qr="qr-<?= e($business['slug']) ?>.png"><?= icon('image', 16) ?> Descargar QR</button>
  </div>
  <div class="grid">
    <section class="glass box">
      <div class="box-head"><div><h2>Enlace para unirse</h2><p>Ponlo en tu Instagram, WhatsApp o Google Maps.</p></div></div>
      <div class="link-box">
        <input class="input" value="<?= e($joinUrl) ?>" readonly data-copy-source aria-label="Enlace para unirse">
        <button type="button" class="btn btn-glass" data-copy><?= icon('copy', 16) ?> Copiar</button>
      </div>
      <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
        <a class="btn btn-ghost btn-sm" href="https://wa.me/?text=<?= rawurlencode("Únete a la tarjeta de sellos de {$business['name']} y gana {$business['reward']}: {$joinUrl}") ?>" target="_blank" rel="noopener"><?= icon('message', 16) ?> Compartir por WhatsApp</a>
        <a class="btn btn-ghost btn-sm" href="<?= e($joinUrl) ?>" target="_blank" rel="noopener"><?= icon('external-link', 16) ?> Abrir</a>
      </div>
    </section>
    <section class="glass box">
      <div class="box-head"><div><h2>Para que funcione mejor</h2></div></div>
      <ul class="checklist" style="list-style:none;padding:0;margin:0">
        <li><?= icon('check', 18) ?><span>Pon el afiche donde el cliente espera: el mesón, la caja o el espejo.</span></li>
        <li><?= icon('check', 18) ?><span>Invítalo en voz alta: "¿Quieres tu tarjeta de sellos? Escanea aquí".</span></li>
        <li><?= icon('check', 18) ?><span>Dale 1 sello de regalo al unirse: la tarjeta empezada se completa más.</span></li>
        <li><?= icon('check', 18) ?><span>Suma el sello antes de cobrar, para que no se olvide.</span></li>
      </ul>
    </section>
    <section class="glass box">
      <div class="box-head"><div><h2>Billeteras del celular</h2><p>Tus clientes guardan la tarjeta junto a sus pases y tarjetas del celular.</p></div></div>
      <div class="grid" style="gap:8px">
        <div class="wallet-status"><span class="dot-status <?= gw_enabled() ? 'on' : '' ?>"></span>Google Wallet · <?= gw_enabled() ? 'activo' : 'próximamente' ?></div>
        <?php if (apple_enabled()): ?>
          <div class="wallet-status"><span class="dot-status on"></span>Apple Wallet · activo</div>
        <?php else: ?>
          <div class="wallet-status"><span class="dot-status"></span><span>Apple Wallet · ¿Lo quieres para tu negocio? Escríbenos a <a class="link-accent" href="mailto:hola@zerosolutions.cl?subject=<?= rawurlencode('Apple Wallet para ' . $business['name']) ?>">hola@zerosolutions.cl</a></span></div>
        <?php endif; ?>
        <p class="hint">Mientras tanto, la tarjeta funciona en el navegador de cualquier celular, con su código QR y sus sellos al instante.</p>
      </div>
    </section>
  </div>
</div>
<?php $head = '<script defer src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>'; ?>
