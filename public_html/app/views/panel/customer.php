<?php $state = customer_state($business, $customer); ?>
<div class="page-head">
  <div>
    <a class="btn btn-ghost btn-sm" href="<?= e(u('/panel/clientes')) ?>" style="margin-left:-10px"><?= icon('arrow-left', 16) ?> Clientes</a>
    <h1 style="margin-top:8px"><?= e($customer['name']) ?></h1>
    <p><?= e(pretty_phone($customer['phone'])) ?><?= $customer['birthday'] ? ' · cumpleaños ' . e(local_time($customer['birthday'] . ' 12:00:00', 'j M')) : '' ?> · cliente desde <?= e(local_time($customer['created_at'], 'j M Y')) ?></p>
  </div>
  <a class="btn btn-glass btn-sm" href="https://wa.me/<?= e(ltrim($customer['phone'], '+')) ?>" target="_blank" rel="noopener"><?= icon('message', 16) ?> WhatsApp</a>
</div>
<div class="scan-layout" data-scanner data-api="<?= e(u('/panel/api')) ?>" data-required="<?= (int) $business['stamps_required'] ?>" data-preload='<?= e(json_encode($state, JSON_UNESCAPED_UNICODE)) ?>'>
  <div class="grid">
    <div class="stats" style="grid-template-columns:repeat(3,1fr)">
      <div class="glass stat"><div class="k"><?= icon('repeat', 16) ?> Visitas</div><div class="v"><?= (int) $customer['visits'] ?></div><div class="s">última <?= e(ago($customer['last_visit_at'])) ?></div></div>
      <div class="glass stat"><div class="k"><?= icon('stamp', 16) ?> Sellos</div><div class="v"><?= (int) $customer['total_stamps'] ?></div><div class="s">en total</div></div>
      <div class="glass stat"><div class="k"><?= icon('gift', 16) ?> Premios</div><div class="v"><?= (int) $customer['rewards_redeemed'] ?></div><div class="s">canjeados</div></div>
    </div>
    <section class="glass box">
      <div class="box-head"><div><h2>Historial</h2></div></div>
      <?php if (!$events): ?><div class="empty">Sin movimientos todavía.</div><?php else: ?>
        <div class="list">
          <?php foreach ($events as $ev): ?>
            <div class="list-row"><span class="avatar"><?= icon(['stamp' => 'stamp', 'reward' => 'gift', 'redeem' => 'check', 'join' => 'user-plus'][$ev['type']] ?? 'clock', 16) ?></span>
              <div class="grow"><div class="t"><?= e(ucfirst(event_text($ev))) ?></div><div class="m"><?= e(local_time($ev['created_at'], 'j M Y, H:i')) ?></div></div></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
  <aside class="glass result" data-result>
    <div class="empty" data-result-empty hidden></div>
    <div data-result-body>
      <div class="who"><span class="avatar" data-r-initials></span><div><b data-r-name></b><div class="hint" data-r-meta></div></div></div>
      <div style="margin-top:16px" data-r-card></div>
      <div class="reward-ready" data-r-reward hidden style="margin-top:14px"><?= icon('gift', 22) ?><div><b data-r-reward-text></b><br><span style="color:var(--muted);font-size:13.5px"><?= e($business['reward']) ?></span></div></div>
      <div class="actions" style="margin-top:16px">
        <button type="button" class="btn btn-primary stamp-big wide" data-act="stamp"><?= icon('stamp', 22) ?> Sumar 1 sello</button>
        <button type="button" class="btn btn-glass" data-act="stamp2">+2 sellos</button>
        <button type="button" class="btn btn-glass" data-act="redeem"><?= icon('gift', 16) ?> Canjear</button>
        <button type="button" class="btn btn-ghost wide" data-act="undo" hidden><?= icon('undo', 16) ?> Deshacer último sello</button>
      </div>
      <a class="btn btn-ghost btn-sm btn-block" style="margin-top:8px" href="<?= e(u('/t/' . $customer['token'])) ?>" target="_blank" rel="noopener"><?= icon('external-link', 15) ?> Ver su tarjeta</a>
    </div>
  </aside>
</div>
<template id="card-template"><?php partial('loyalty_card', ['business' => $business, 'stamps' => 0]); ?></template>
<script defer src="<?= e(asset('js/scanner.js')) ?>"></script>
