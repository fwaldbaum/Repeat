<?php
/** Pro settings inside the card editor. Visible to everyone; editable only with Pro. */
$pro = is_pro($business);
$days = double_days($business);
$dis = $pro ? '' : ' disabled';
?>
<div class="pro-block<?= $pro ? '' : ' locked' ?>">
  <div class="pro-head">
    <p class="section-title" style="margin:0"><?= icon('crown', 15) ?> Funciones Pro</p>
    <?php if (!$pro): ?><a class="btn btn-primary btn-sm" href="<?= e(u('/panel/pro')) ?>"><?= icon('lock', 15) ?> Activar Repeat Pro</a><?php else: ?><span class="badge badge-pro"><?= icon('crown', 12) ?> Pro activo</span><?php endif; ?>
  </div>

  <div class="field">
    <span class="label">Ícono de los sellos</span>
    <div class="icon-picks">
      <?php foreach (STAMP_ICONS as $key => [$label]): ?>
        <label class="icon-pick" title="<?= e($label) ?>">
          <input type="radio" name="stamp_icon" value="<?= e($key) ?>" data-stamp-option data-svg="<?= e(stamp_svg($key)) ?>" <?= $key === stamp_icon_of($business) ? 'checked' : '' ?><?= $dis ?>>
          <span><?= stamp_svg($key) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <label class="toggle-row">
    <input type="checkbox" name="birthday_gift" value="1" <?= $business['birthday_gift'] ? 'checked' : '' ?><?= $dis ?>>
    <span><b>Regalo de cumpleaños</b><small>En la semana de su cumpleaños, cada cliente recibe un premio listo para canjear. Solo clientes que dejaron su fecha.</small></span>
  </label>

  <div class="field">
    <span class="label">Horas de sellos dobles</span>
    <div class="day-chips">
      <?php foreach (WEEKDAYS as $n => $label): ?>
        <label class="chip"><input type="checkbox" name="double_days[]" value="<?= $n ?>" <?= in_array($n, $days, true) ? 'checked' : '' ?><?= $dis ?>><span><?= e($label) ?></span></label>
      <?php endforeach; ?>
    </div>
    <div class="row-2" style="margin-top:8px">
      <input class="input" type="time" name="double_from" value="<?= e(substr((string) $business['double_from'], 0, 5)) ?>" aria-label="Desde"<?= $dis ?>>
      <input class="input" type="time" name="double_to" value="<?= e(substr((string) $business['double_to'], 0, 5)) ?>" aria-label="Hasta"<?= $dis ?>>
    </div>
    <p class="hint">Cada sello en esos días y horario vale doble. Ideal para llenar las horas tranquilas.</p>
  </div>

  <div class="field">
    <label for="referral_stamps">Trae a un amigo</label>
    <select class="input" id="referral_stamps" name="referral_stamps"<?= $dis ?>>
      <?php foreach ([0 => 'Desactivado', 1 => '1 sello para cada uno', 2 => '2 sellos para cada uno', 3 => '3 sellos para cada uno'] as $n => $label): ?>
        <option value="<?= $n ?>" <?= (int) $business['referral_stamps'] === $n ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <p class="hint">Cada cliente ve su enlace para invitar. Cuando un amigo se une con ese enlace, ambos ganan sellos.</p>
  </div>
  <?php if (!$pro): ?>
    <p class="hint" style="display:flex;gap:8px;align-items:center"><?= icon('sparkles', 15) ?> Con Pro también desaparece el logo de Repeat de tus tarjetas, y puedes exportar tus clientes.</p>
  <?php endif; ?>
</div>
