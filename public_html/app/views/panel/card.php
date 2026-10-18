<div class="page-head">
  <div><h1>Tu tarjeta</h1><p>El diseño y el premio. Los cambios llegan a las tarjetas de todos tus clientes.</p></div>
  <a class="btn btn-glass btn-sm" href="<?= e(u('/c/' . $business['slug'])) ?>" target="_blank" rel="noopener"><?= icon('external-link', 16) ?> Ver como cliente</a>
</div>
<div class="editor">
  <form class="glass box form" method="post" action="<?= e(u('/panel/tarjeta')) ?>" enctype="multipart/form-data" data-card-form>
    <?= csrf_field() ?>
    <?php partial('card_fields', ['business' => $business]); ?>
    <p class="section-title">Logo</p>
    <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
      <?php if ($business['logo']): ?>
        <img src="<?= e(u($business['logo'])) ?>" alt="Logo actual" style="width:64px;height:64px;border-radius:14px;background:var(--surface-2);object-fit:contain">
        <label class="hint" style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="logo_remove" value="1"> Quitar logo</label>
      <?php endif; ?>
      <input class="input" type="file" name="logo" accept="image/png,image/jpeg,image/webp" style="flex:1;min-width:220px" data-logo-input>
    </div>
    <p class="hint">PNG con fondo transparente se ve mejor. Hasta 2 MB.</p>
    <div class="field">
      <label for="message">Mensaje para tus clientes <span style="font-weight:400;color:var(--subtle)">(opcional)</span></label>
      <textarea class="input" id="message" name="message" maxlength="160" placeholder="Ej: Válido de lunes a viernes. Agenda al +56 9 1234 5678."><?= e($business['message']) ?></textarea>
    </div>
    <div class="save-bar"><button class="btn btn-primary"><?= icon('check', 18) ?> Guardar cambios</button></div>
  </form>
  <div class="preview">
    <?php partial('loyalty_card', ['business' => $business, 'stamps' => max(1, (int) floor($business['stamps_required'] * .6))]); ?>
    <p class="hint">Vista previa en vivo</p>
  </div>
</div>
