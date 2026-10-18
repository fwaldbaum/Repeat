<?php
/**
 * The stamp card as customers see it. $business (colors, name, logo, reward, stamp icon), $stamps filled,
 * optional $customer. On the free plan the card carries Repeat's mark; Pro removes it.
 * Live editors update it through the data-* hooks.
 */
$required = (int) $business['stamps_required'];
$stamps = min($required, (int) ($stamps ?? 0));
$cols = $required <= 6 ? $required : ($required <= 10 ? (int) ceil($required / 2) : (int) ceil($required / 3));
// One row fills the width; with two or three rows the stamps shrink so the reward line always fits.
$stampSize = ['1fr', '42px', '32px'][min(3, (int) ceil($required / $cols)) - 1];
$real = isset($business['owner_id']);
$pro = $real ? is_pro($business) : !empty($preview_pro);
$stampIcon = $real ? stamp_icon_of($business) : ($business['stamp_icon'] ?? 'check');
$svg = stamp_svg($stampIcon);
$initial = mb_strtoupper(mb_substr($business['name'], 0, 1));
?>
<div class="lcard" data-card data-stamp-svg="<?= e($svg) ?>" style="--bg:<?= e($business['card_bg']) ?>;--fg:<?= e($business['card_fg']) ?>;--accent:<?= e($business['card_accent']) ?>;--cols:<?= $cols ?>;--stamp:<?= $stampSize ?>">
  <div class="lcard-top">
    <div class="lcard-logo" data-card-logo><?php if ($business['logo']): ?><img src="<?= e(u($business['logo'])) ?>" alt=""><?php else: ?><?= e($initial) ?><?php endif; ?></div>
    <div style="min-width:0;flex:1">
      <div class="lcard-kicker"><?= !empty($customer) ? e($customer['name']) : 'Tarjeta de sellos' ?></div>
      <div class="lcard-name" data-card-name><?= e($business['name']) ?></div>
    </div>
  </div>
  <div class="lcard-stamps" data-card-stamps data-filled="<?= $stamps ?>">
    <?php for ($i = 0; $i < $required; $i++): ?>
      <span class="stamp<?= $i < $stamps ? ' on' : '' ?>"><?= $i < $stamps ? $svg : '' ?></span>
    <?php endfor; ?>
  </div>
  <div class="lcard-foot">
    <div style="min-width:0">
      <span class="muted"><span data-card-required><?= $required ?></span> sellos = </span><b data-card-reward><?= e($business['reward']) ?></b>
    </div>
    <?php if (!$pro): ?><span class="lcard-brand" data-card-brand><img src="<?= e(u('/assets/img/repeat-mark-white.svg')) ?>" alt="">Repeat</span><?php endif; ?>
  </div>
</div>
