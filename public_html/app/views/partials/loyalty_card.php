<?php
/**
 * The stamp card as customers see it. $business (colors, name, logo, reward), $stamps filled,
 * optional $customer name. Live editors update it through the data-* hooks.
 */
$required = (int) $business['stamps_required'];
$stamps = min($required, (int) ($stamps ?? 0));
$cols = $required <= 6 ? $required : ($required <= 10 ? (int) ceil($required / 2) : (int) ceil($required / 3));
$check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
$initial = mb_strtoupper(mb_substr($business['name'], 0, 1));
?>
<div class="lcard" data-card style="--bg:<?= e($business['card_bg']) ?>;--fg:<?= e($business['card_fg']) ?>;--accent:<?= e($business['card_accent']) ?>;--cols:<?= $cols ?>">
  <div class="lcard-top">
    <div class="lcard-logo" data-card-logo><?php if ($business['logo']): ?><img src="<?= e(u($business['logo'])) ?>" alt=""><?php else: ?><?= e($initial) ?><?php endif; ?></div>
    <div style="min-width:0">
      <div class="lcard-kicker">Tarjeta de sellos</div>
      <div class="lcard-name" data-card-name><?= e($business['name']) ?></div>
    </div>
  </div>
  <div class="lcard-stamps" data-card-stamps data-filled="<?= $stamps ?>">
    <?php for ($i = 0; $i < $required; $i++): ?>
      <span class="stamp<?= $i < $stamps ? ' on' : '' ?>"><?= $i < $stamps ? $check : '' ?></span>
    <?php endfor; ?>
  </div>
  <div class="lcard-foot">
    <div style="min-width:0">
      <span class="muted"><span data-card-required><?= $required ?></span> sellos = </span><b data-card-reward><?= e($business['reward']) ?></b>
    </div>
    <?php if (!empty($customer)): ?><span class="muted"><?= e($customer['name']) ?></span><?php else: ?><img class="lcard-mark" src="<?= e(u('/assets/img/repeat-mark-white.svg')) ?>" alt=""><?php endif; ?>
  </div>
</div>
