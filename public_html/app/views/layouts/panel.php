<?php
$section = $section ?? '';
$user = current_user();
$links = [
    'inicio' => ['/panel', 'dashboard', 'Inicio'],
    'escanear' => ['/panel/escanear', 'scan', 'Sumar sellos'],
    'clientes' => ['/panel/clientes', 'users', 'Clientes'],
    'tarjeta' => ['/panel/tarjeta', 'palette', 'Tarjeta'],
    'compartir' => ['/panel/compartir', 'share', 'Compartir'],
    'pro' => ['/panel/pro', 'crown', 'Pro'],
];
?>
<!doctype html>
<html lang="es">
<head><?php partial('head', ['title' => $title ?? 'Repeat']); ?><meta name="robots" content="noindex"><?= $head ?? '' ?></head>
<body>
<div class="scene-dim-wrap"><?php partial('scene'); ?></div>
<div class="app">
  <aside class="side">
    <?php partial('brand', ['href' => '/panel', 'by' => true]); ?>
    <nav class="nav" aria-label="Panel">
      <?php foreach ($links as $key => [$href, $ic, $label]): ?>
        <a href="<?= e(u($href)) ?>" class="<?= $key === $section ? 'active' : '' ?><?= $key === 'escanear' && $section !== 'escanear' ? ' cta' : '' ?>"><?= icon($ic, 18) ?><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <div><b><?= e($business['name'] ?? '') ?></b><span class="who"><?= e($user['email'] ?? '') ?></span></div>
      <form method="post" action="<?= e(u('/salir')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-block" style="justify-content:flex-start;padding:0 6px"><?= icon('log-out', 16) ?> Salir</button></form>
    </div>
  </aside>
  <main class="main">
    <div class="mobile-top">
      <?php partial('brand', ['href' => '/panel']); ?>
      <form method="post" action="<?= e(u('/salir')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" aria-label="Salir"><?= icon('log-out', 18) ?></button></form>
    </div>
    <?php partial('flashes'); ?>
    <?= $content ?>
  </main>
</div>
<nav class="mobile-nav" aria-label="Panel" style="grid-template-columns:repeat(<?= count($links) ?>,1fr)">
  <?php foreach ($links as $key => [$href, $ic, $label]): ?>
    <a href="<?= e(u($href)) ?>" class="<?= $key === $section ? 'active' : '' ?><?= $key === 'escanear' && $section !== 'escanear' ? ' cta' : '' ?>"><?= icon($ic, 20) ?><span><?= e($key === 'escanear' ? 'Sellar' : $label) ?></span></a>
  <?php endforeach; ?>
</nav>
<meta name="csrf" content="<?= e(csrf_token()) ?>">
</body>
</html>
