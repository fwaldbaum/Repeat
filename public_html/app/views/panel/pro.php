<?php
$tier = plan_tier($user);
$active = $tier !== 'free';
$zero = rtrim((string) config('zero_url', 'https://zerosolutions.cl'), '/');
$save = (int) round((1 - PRO_PRICE / PRO_LIST_PRICE) * 100);
?>
<div class="page-head">
  <div><h1>Repeat Pro</h1><p>Tu marca en primer plano y herramientas para que tus clientes vuelvan más seguido.</p></div>
</div>

<section class="glass pro-hero">
  <div class="pro-hero-text">
    <?php if ($active): ?>
      <span class="badge badge-pro"><?= icon('crown', 13) ?> <?= $tier === 'max' ? 'Incluido en tu ZERO Plus' : 'Repeat Pro activo' ?></span>
      <h2>Tienes todas las funciones Pro</h2>
      <p><?= $user['plan_expires_at'] ? 'Tu plan está activo hasta el ' . e(local_time($user['plan_expires_at'], 'j M Y')) . '.' : 'Tu plan está activo.' ?> Lo renuevas desde tu cuenta ZERO.</p>
      <div class="cta-row" style="margin-top:20px">
        <a class="btn btn-primary" href="<?= e(u('/panel/tarjeta')) ?>"><?= icon('palette', 18) ?> Configurar funciones Pro</a>
        <a class="btn btn-glass" href="<?= e($zero . '/cuenta/plan') ?>"><?= icon('external-link', 16) ?> Ver mi plan en ZERO</a>
      </div>
    <?php else: ?>
      <span class="badge badge-offer">Oferta de lanzamiento · <?= $save ?>% menos</span>
      <h2>Lleva tu tarjeta al siguiente nivel</h2>
      <div class="price-line">
        <span class="was"><?= e(clp(PRO_LIST_PRICE)) ?></span>
        <span class="now"><?= e(clp(PRO_PRICE)) ?></span><span class="per">/ mes</span>
      </div>
      <p>IVA incluido. Se paga con Flow desde tu cuenta ZERO, sin contratos. También viene incluido en <b>ZERO Plus</b>.</p>
      <div class="cta-row" style="margin-top:20px">
        <a class="btn btn-primary btn-lg" href="<?= e(pro_upgrade_url()) ?>"><?= icon('crown', 18) ?> Activar Repeat Pro</a>
        <a class="btn btn-glass btn-lg" href="<?= e($zero . '/precios') ?>">Ver todos los planes</a>
      </div>
      <p class="hint" style="margin-top:12px">Después de pagar, vuelve a abrir Repeat desde tu cuenta ZERO y las funciones Pro quedan activas.</p>
    <?php endif; ?>
  </div>
  <div class="pro-hero-card">
    <?php partial('loyalty_card', ['business' => ['stamp_icon' => 'star'] + array_diff_key($business, ['owner_id' => 1]), 'stamps' => max(2, (int) floor($business['stamps_required'] * .7)), 'preview_pro' => true]); ?>
    <p class="hint" style="text-align:center">Tu tarjeta con Pro: tu ícono y sin el logo de Repeat</p>
  </div>
</section>

<div class="features pro-features">
  <?php foreach (PRO_FEATURES as [$ic, $title, $text]): ?>
    <div class="glass feature"><div class="ic"><?= icon($ic, 20) ?></div><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div>
  <?php endforeach; ?>
</div>
