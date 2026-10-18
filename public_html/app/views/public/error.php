<!doctype html>
<html lang="es">
<head><?php partial('head', ['title' => $title]); ?><meta name="robots" content="noindex"></head>
<body>
<?php partial('scene'); ?>
<main class="center-page">
  <div class="glass panel-card" style="text-align:center">
    <div style="display:flex;justify-content:center;margin-bottom:18px"><?php partial('brand'); ?></div>
    <p class="eyebrow">Error <?= (int) ($code ?? 404) ?></p>
    <h1 style="margin-top:10px"><?= ($code ?? 404) === 404 ? 'No encontramos esta página' : 'Algo no salió bien' ?></h1>
    <p class="intro"><?= e($message ?? '') ?></p>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:22px;flex-wrap:wrap">
      <?php if (!empty($action)): ?><a class="btn btn-primary" href="<?= e($action['href']) ?>"><?= e($action['label']) ?></a><?php endif; ?>
      <a class="btn btn-glass" href="<?= e(u('/')) ?>">Ir al inicio</a>
    </div>
  </div>
</main>
</body>
</html>
