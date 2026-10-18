<?php $initials = fn ($n) => mb_strtoupper(mb_substr(trim($n), 0, 1) . mb_substr(strstr(trim($n), ' ') ?: '', 1, 1)); ?>
<div class="page-head">
  <div><h1><?= e($business['name']) ?></h1><p>Así va tu tarjeta de sellos.</p></div>
  <a class="btn btn-primary" href="<?= e(u('/panel/escanear')) ?>"><?= icon('scan', 18) ?> Sumar un sello</a>
</div>

<div class="stats">
  <div class="glass stat"><div class="k"><?= icon('users', 16) ?> Clientes</div><div class="v"><?= $stats['customers'] ?></div><div class="s"><?= $stats['new_week'] ?> <?= $stats['new_week'] === 1 ? 'nuevo' : 'nuevos' ?> esta semana</div></div>
  <div class="glass stat"><div class="k"><?= icon('stamp', 16) ?> Sellos hoy</div><div class="v"><?= $stats['stamps_today'] ?></div><div class="s"><?= $stats['stamps_week'] ?> en los últimos 7 días</div></div>
  <div class="glass stat"><div class="k"><?= icon('repeat', 16) ?> Volvieron</div><div class="v"><?= $stats['returning'] ?></div><div class="s">clientes con 2 o más visitas</div></div>
  <div class="glass stat"><div class="k"><?= icon('gift', 16) ?> Premios</div><div class="v"><?= $stats['rewards_ready'] ?></div><div class="s">listos para canjear · <?= $stats['redeemed'] ?> canjeados</div></div>
</div>

<?php if (!$stats['customers']): ?>
  <div class="glass box" style="margin-top:16px;display:flex;gap:18px;align-items:center;flex-wrap:wrap">
    <div style="flex:1;min-width:240px">
      <h2 style="font-size:20px">Consigue tus primeros clientes</h2>
      <p style="color:var(--muted);margin-top:6px">Imprime el afiche con tu código QR y ponlo en el mesón. Tus clientes lo escanean y tienen su tarjeta en segundos.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(u('/panel/compartir')) ?>"><?= icon('qr', 18) ?> Ver mi QR y afiche</a>
  </div>
<?php endif; ?>

<div class="cols-2">
  <section class="glass box">
    <div class="box-head"><div><h2>Sellos por día</h2><p>Últimos 14 días</p></div></div>
    <?php partial('stamps_chart', ['days' => $days]); ?>
  </section>
  <section class="glass box">
    <div class="box-head"><div><h2>Actividad reciente</h2></div><a class="btn btn-ghost btn-sm" href="<?= e(u('/panel/clientes')) ?>">Ver clientes</a></div>
    <?php if (!$activity): ?>
      <div class="empty"><?= icon('clock', 26) ?>Aquí verás cada sello y cada premio.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($activity as $ev): ?>
          <div class="list-row">
            <span class="avatar"><?= e($initials($ev['name'])) ?></span>
            <div class="grow"><div class="t"><a href="<?= e(u('/panel/clientes/' . $ev['cid'])) ?>"><?= e($ev['name']) ?></a></div><div class="m"><?= e(event_text($ev)) ?></div></div>
            <span class="m" style="color:var(--subtle);font-size:12.5px"><?= e(ago($ev['created_at'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php if ($top): ?>
<section class="glass box" style="margin-top:16px">
  <div class="box-head"><div><h2>Tus clientes más fieles</h2><p>Por sellos acumulados</p></div></div>
  <div class="list">
    <?php foreach ($top as $c): ?>
      <div class="list-row">
        <span class="avatar"><?= e($initials($c['name'])) ?></span>
        <div class="grow"><div class="t"><a href="<?= e(u('/panel/clientes/' . $c['id'])) ?>"><?= e($c['name']) ?></a></div><div class="m"><?= (int) $c['visits'] ?> visitas · última <?= e(ago($c['last_visit_at'])) ?></div></div>
        <?php if ($c['rewards_available']): ?><span class="badge badge-gift"><?= icon('gift', 13) ?> Premio listo</span><?php endif; ?>
        <span class="badge"><?= (int) $c['total_stamps'] ?> sellos</span>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
