<?php
$business = ['name' => '', 'category' => 'Barbería', 'reward' => '', 'stamps_required' => 10, 'welcome_stamps' => 1,
    'card_bg' => '#1b1250', 'card_fg' => '#ffffff', 'card_accent' => '#8b5cf6', 'logo' => null, 'message' => ''];
?>
<!doctype html>
<html lang="es">
<head><?php partial('head', ['title' => $title]); ?><meta name="robots" content="noindex"></head>
<body>
<?php partial('scene'); ?>
<main class="center-page">
  <div class="editor" style="width:100%;max-width:1040px">
    <form class="glass panel-card form" style="max-width:none" method="post" action="<?= e(u('/panel/nuevo')) ?>" data-card-form>
      <?= csrf_field() ?>
      <div>
        <?php partial('brand', ['by' => true]); ?>
        <h1 style="margin-top:22px">Hola, <?= e(explode(' ', $user['name'])[0]) ?>. Creemos tu tarjeta.</h1>
        <p class="intro">Puedes cambiar todo después. Así la verán tus clientes en su celular.</p>
      </div>
      <?php partial('flashes'); ?>
      <?php partial('card_fields', ['business' => $business]); ?>
      <button class="btn btn-primary btn-lg btn-block"><?= icon('sparkles', 18) ?> Crear mi tarjeta</button>
    </form>
    <div class="preview">
      <?php partial('loyalty_card', ['business' => ['name' => 'Tu negocio', 'reward' => 'Tu premio'] + $business, 'stamps' => 1]); ?>
      <p class="hint">Vista previa en vivo</p>
    </div>
  </div>
</main>
</body>
</html>
