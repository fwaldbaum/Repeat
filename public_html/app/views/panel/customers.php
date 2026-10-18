<?php $initials = fn ($n) => mb_strtoupper(mb_substr(trim($n), 0, 1) . mb_substr(strstr(trim($n), ' ') ?: '', 1, 1)); ?>
<div class="page-head">
  <div><h1>Clientes</h1><p><?= $total ?> <?= $total === 1 ? 'cliente tiene' : 'clientes tienen' ?> tu tarjeta.</p></div>
</div>
<form class="toolbar" method="get" action="<?= e(u('/panel/clientes')) ?>">
  <div class="search"><?= icon('search', 18) ?><input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre o teléfono" aria-label="Buscar"></div>
  <input type="hidden" name="orden" value="<?= e($sort) ?>">
  <nav class="tabs" aria-label="Ordenar">
    <?php foreach (['recientes' => 'Recientes', 'sellos' => 'Más sellos', 'premios' => 'Con premio', 'nuevos' => 'Nuevos'] as $k => $label): ?>
      <a href="<?= e(u('/panel/clientes?orden=' . $k . ($search !== '' ? '&q=' . rawurlencode($search) : ''))) ?>" class="<?= $k === $sort ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
</form>
<section class="glass box">
  <?php if (!$customers): ?>
    <div class="empty"><?= icon('users', 28) ?><?= $search !== '' ? 'No encontramos clientes con esa búsqueda.' : 'Todavía no tienes clientes. Comparte tu código QR para que se unan.' ?></div>
  <?php else: ?>
    <div class="list">
      <?php foreach ($customers as $c): $req = (int) $business['stamps_required']; ?>
        <a class="list-row" href="<?= e(u('/panel/clientes/' . $c['id'])) ?>">
          <span class="avatar"><?= e($initials($c['name'])) ?></span>
          <div class="grow">
            <div class="t"><?= e($c['name']) ?></div>
            <div class="m"><?= e(pretty_phone($c['phone'])) ?> · <?= (int) $c['visits'] ?> visitas · última <?= e(ago($c['last_visit_at'])) ?></div>
          </div>
          <?php if ($c['rewards_available']): ?><span class="badge badge-gift"><?= icon('gift', 13) ?> <?= (int) $c['rewards_available'] ?></span><?php endif; ?>
          <?php if (strtotime($c['created_at'] . ' UTC') > time() - 7 * 86400): ?><span class="badge badge-new">Nuevo</span><?php endif; ?>
          <span class="mini-stamps" title="<?= (int) $c['stamps'] ?> de <?= $req ?> sellos"><?php for ($i = 0; $i < min($req, 12); $i++): ?><i class="<?= $i < $c['stamps'] ? 'on' : '' ?>"></i><?php endfor; ?></span>
          <?= icon('chevron-right', 16) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
