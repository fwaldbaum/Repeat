<!doctype html>
<html lang="es">
<head><?php partial('head', ['title' => $title, 'description' => "Únete a la tarjeta de sellos de {$business['name']}: junta {$business['stamps_required']} sellos y gana {$business['reward']}."]); ?><?php partial('tint', ['business' => $business]); ?></head>
<body>
<?php partial('scene'); ?>
<main class="center-page">
  <div class="stack">
    <div class="join-head reveal">
      <span class="pill"><span>Gratis</span> Tarjeta de sellos digital</span>
      <h1><?= e($business['name']) ?></h1>
      <p>Junta <?= (int) $business['stamps_required'] ?> sellos y gana <b style="color:var(--text)"><?= e($business['reward']) ?></b><?= $business['welcome_stamps'] ? '. Te regalamos ' . (int) $business['welcome_stamps'] . ' para empezar.' : '.' ?></p>
    </div>
    <div class="reveal" style="width:100%;display:grid;justify-items:center"><?php partial('loyalty_card', ['business' => $business, 'stamps' => (int) $business['welcome_stamps']]); ?></div>
    <form class="glass panel-card form reveal" method="post" action="<?= e(u('/c/' . $business['slug'])) ?>" novalidate>
      <?= csrf_field() ?>
      <?php if (!empty($error)): ?><div class="alert alert-error" role="alert"><?= icon('alert', 18) ?><span><?= e($error) ?></span></div><?php endif; ?>
      <div class="field">
        <label for="name">Tu nombre</label>
        <input class="input" id="name" name="name" autocomplete="name" required maxlength="80" value="<?= e($old['name'] ?? '') ?>" placeholder="Nombre y apellido">
      </div>
      <div class="field">
        <label for="phone">Tu teléfono</label>
        <input class="input" id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required value="<?= e($old['phone'] ?? '') ?>" placeholder="9 1234 5678">
        <p class="hint">Con tu teléfono te encuentran en caja si no tienes la tarjeta a mano.</p>
      </div>
      <div class="field">
        <label for="birthday">Tu cumpleaños <span style="font-weight:400;color:var(--subtle)">(opcional)</span></label>
        <input class="input" id="birthday" name="birthday" type="date">
      </div>
      <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
      <button class="btn btn-primary btn-lg btn-block"><?= icon('wallet', 18) ?> Obtener mi tarjeta</button>
      <p class="hint" style="text-align:center">Tus datos solo los ve <?= e($business['name']) ?>.</p>
    </form>
    <a class="powered" href="<?= e(u('/')) ?>"><img src="<?= e(u('/assets/img/repeat-mark.svg')) ?>" alt="">Funciona con Repeat</a>
  </div>
</main>
</body>
</html>
