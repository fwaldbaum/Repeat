<?php
/** Card settings shared by onboarding and the editor; inputs drive the live preview (repeat.js). */
$b = $business;
$presets = [
    ['#1b1250', '#8b5cf6', 'Noche'], ['#0f2a6b', '#38bdf8', 'Océano'], ['#111827', '#f59e0b', 'Ámbar'],
    ['#3b0a2a', '#ec4899', 'Fucsia'], ['#052e2b', '#34d399', 'Menta'], ['#1f1408', '#f97316', 'Cobre'],
    ['#0b0b0f', '#e5e7eb', 'Grafito'], ['#2a0a0a', '#ef4444', 'Rubí'],
];
?>
<div class="field">
  <label for="name">Nombre de tu negocio</label>
  <input class="input" id="name" name="name" required maxlength="80" value="<?= e($b['name']) ?>" placeholder="Barbería Norte" data-live="name">
</div>
<div class="row-2">
  <div class="field">
    <label for="category">Rubro</label>
    <select class="input" id="category" name="category">
      <?php foreach (CATEGORIES as $c): ?><option <?= $c === $b['category'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="reward">Premio al completar la tarjeta</label>
    <input class="input" id="reward" name="reward" required maxlength="80" value="<?= e($b['reward']) ?>" placeholder="Corte gratis" data-live="reward">
  </div>
</div>
<div class="row-2">
  <div class="field">
    <span class="label">Sellos para ganar el premio</span>
    <div class="stepper" data-stepper>
      <button type="button" data-step="-1" aria-label="Menos"><?= icon('minus', 16) ?></button>
      <output data-live-out="stamps_required"><?= (int) $b['stamps_required'] ?></output>
      <button type="button" data-step="1" aria-label="Más"><?= icon('plus', 16) ?></button>
      <input type="hidden" name="stamps_required" value="<?= (int) $b['stamps_required'] ?>" data-live="stamps_required" data-min="3" data-max="20">
    </div>
  </div>
  <div class="field">
    <span class="label">Sellos de regalo al unirse</span>
    <div class="stepper" data-stepper>
      <button type="button" data-step="-1" aria-label="Menos"><?= icon('minus', 16) ?></button>
      <output data-live-out="welcome_stamps"><?= (int) $b['welcome_stamps'] ?></output>
      <button type="button" data-step="1" aria-label="Más"><?= icon('plus', 16) ?></button>
      <input type="hidden" name="welcome_stamps" value="<?= (int) $b['welcome_stamps'] ?>" data-live="welcome_stamps" data-min="0" data-max="5">
    </div>
  </div>
</div>
<div class="field">
  <span class="label">Estilo</span>
  <div class="presets">
    <?php foreach ($presets as [$bg, $accent, $label]): ?>
      <button type="button" class="preset" style="--a:<?= $bg ?>;--b:<?= $accent ?>" data-preset="<?= $bg ?>,<?= $accent ?>" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"></button>
    <?php endforeach; ?>
  </div>
</div>
<div class="swatches">
  <label class="swatch"><input type="color" name="card_bg" value="<?= e($b['card_bg']) ?>" data-live="card_bg">Fondo</label>
  <label class="swatch"><input type="color" name="card_accent" value="<?= e($b['card_accent']) ?>" data-live="card_accent">Sellos</label>
  <label class="swatch"><input type="color" name="card_fg" value="<?= e($b['card_fg']) ?>" data-live="card_fg">Texto</label>
</div>
