<?php
$demo = ['name' => 'Barbería Norte', 'stamps_required' => 10, 'reward' => 'Corte gratis', 'card_bg' => '#1b1250', 'card_fg' => '#ffffff', 'card_accent' => '#8b5cf6', 'logo' => null];
$demo2 = ['name' => 'Café Aurora', 'stamps_required' => 8, 'reward' => 'Café gratis', 'card_bg' => '#0f2a6b', 'card_fg' => '#ffffff', 'card_accent' => '#38bdf8', 'logo' => null];
?>
<!doctype html>
<html lang="es">
<head><?php partial('head', ['title' => $title]); ?></head>
<body>
<?php partial('scene'); ?>
<header class="topbar">
  <div class="wrap">
    <?php partial('brand', ['by' => true]); ?>
    <nav>
      <a href="#como-funciona" class="btn btn-ghost btn-sm hide-sm">Cómo funciona</a>
      <a href="<?= e(u('/entrar')) ?>" class="btn btn-glass btn-sm">Entrar con ZERO</a>
    </nav>
  </div>
</header>

<main class="wrap">
  <section class="hero">
    <div>
      <span class="pill reveal"><span>Nuevo</span> Una app de ZERO Solutions</span>
      <h1 class="reveal">Que tus clientes <span class="gradient-text">vuelvan</span>, una y otra vez.</h1>
      <p class="lead reveal">Repeat convierte la tarjeta de sellos de cartón en una tarjeta digital en el celular de tus clientes. Sin apps que descargar, sin tarjetas perdidas.</p>
      <div class="cta-row reveal">
        <a href="<?= e(u('/entrar')) ?>" class="btn btn-primary btn-lg">Crear mi tarjeta gratis <?= icon('arrow-right', 18) ?></a>
        <a href="#como-funciona" class="btn btn-glass btn-lg">Ver cómo funciona</a>
      </div>
      <p class="hero-note reveal">Entra con tu cuenta ZERO. Listo en 2 minutos.</p>
    </div>
    <div class="hero-card reveal">
      <div class="behind"><?php partial('loyalty_card', ['business' => $demo2, 'stamps' => 3]); ?></div>
      <?php partial('loyalty_card', ['business' => $demo, 'stamps' => 7]); ?>
      <div class="toast-demo glass"><span class="dot"><?= icon('check', 16) ?></span><span><b>Matías</b> sumó un sello<br><span style="color:var(--subtle)">Le faltan 3 para su corte gratis</span></span></div>
    </div>
  </section>

  <section class="section" id="como-funciona">
    <div class="section-head reveal">
      <span class="eyebrow">Cómo funciona</span>
      <h2>Tres pasos y tus clientes ya están juntando sellos</h2>
      <p>Tú configuras la tarjeta una vez. Tus clientes la guardan en su celular y vuelven por su premio.</p>
    </div>
    <div class="steps">
      <div class="glass step reveal"><div class="num">1</div><h3>Crea tu tarjeta</h3><p>Elige el premio, cuántos sellos se necesitan, tus colores y tu logo.</p></div>
      <div class="glass step reveal"><div class="num">2</div><h3>Pon el QR en tu mesón</h3><p>Tus clientes lo escanean, dejan su nombre y teléfono, y la tarjeta queda en su celular.</p></div>
      <div class="glass step reveal"><div class="num">3</div><h3>Suma sellos con tu celular</h3><p>Escanea el código de su tarjeta y el sello aparece al instante. Al completarla, gana su premio.</p></div>
    </div>
  </section>

  <section class="section">
    <div class="section-head reveal">
      <span class="eyebrow">Todo incluido</span>
      <h2>Simple para ti, mágico para tus clientes</h2>
    </div>
    <div class="features">
      <div class="glass feature reveal"><div class="ic"><?= icon('wallet', 20) ?></div><h3>Apple y Google Wallet</h3><p>La tarjeta vive en la billetera del celular, junto a sus pases y tarjetas.</p></div>
      <div class="glass feature reveal"><div class="ic"><?= icon('scan', 20) ?></div><h3>Escáner en tu celular</h3><p>Sin máquinas ni lectores: usa la cámara del celular, o busca al cliente por su teléfono.</p></div>
      <div class="glass feature reveal"><div class="ic"><?= icon('zap', 20) ?></div><h3>Sellos al instante</h3><p>El cliente ve su sello aparecer en su tarjeta en el mismo momento.</p></div>
      <div class="glass feature reveal"><div class="ic"><?= icon('users', 20) ?></div><h3>Conoce a tus clientes</h3><p>Quiénes vuelven más, cuándo fue su última visita y quién tiene un premio listo.</p></div>
      <div class="glass feature reveal"><div class="ic"><?= icon('palette', 20) ?></div><h3>Con tu marca</h3><p>Tus colores, tu logo y tu premio. Cambia el diseño cuando quieras.</p></div>
      <div class="glass feature reveal"><div class="ic"><?= icon('printer', 20) ?></div><h3>Afiche listo</h3><p>Imprime el afiche con tu QR y ponlo en el mesón. Eso es todo.</p></div>
    </div>
  </section>

  <section class="glass cta-band reveal">
    <h2>Tu próximo cliente fiel está a un sello de distancia</h2>
    <p>Crea tu tarjeta con tu cuenta ZERO y empieza hoy.</p>
    <div class="cta-row"><a href="<?= e(u('/entrar')) ?>" class="btn btn-primary btn-lg">Crear mi tarjeta gratis <?= icon('arrow-right', 18) ?></a></div>
  </section>

  <footer class="site-foot">
    <span>Repeat es una aplicación de ZERO Solutions · Hecho en Chile</span>
    <a href="<?= e((string) config('zero_url', 'https://zerosolutions.cl')) ?>">zerosolutions.cl</a>
  </footer>
</main>
</body>
</html>
