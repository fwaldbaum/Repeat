<?php
$demo = ['name' => 'Barbería Norte', 'stamps_required' => 10, 'reward' => 'Corte gratis', 'card_bg' => '#1b1250', 'card_fg' => '#ffffff', 'card_accent' => '#8b5cf6', 'logo' => null];
$demo2 = ['name' => 'Café Aurora', 'stamps_required' => 8, 'reward' => 'Café gratis', 'card_bg' => '#0f2a6b', 'card_fg' => '#ffffff', 'card_accent' => '#38bdf8', 'logo' => null];
$phoneCard = ['stamps_required' => 8] + $demo;
$zero = rtrim((string) config('zero_url', 'https://zerosolutions.cl'), '/');
$save = (int) round((1 - PRO_PRICE / PRO_LIST_PRICE) * 100);
$presets = [['Morado', '#1b1250', '#8b5cf6'], ['Océano', '#0f2a6b', '#38bdf8'], ['Café', '#2b1a12', '#f59e0b'], ['Bosque', '#0b3b2e', '#34d399'], ['Rosa', '#3b0d2c', '#f472b6']];
$businesses = [['scissors', 'Barberías'], ['coffee', 'Cafeterías'], ['sparkles', 'Peluquerías'], ['heart', 'Manicure'], ['gift', 'Pastelerías'], ['star', 'Heladerías'], ['zap', 'Lavados de autos'], ['heart', 'Veterinarias'], ['coffee', 'Panaderías'], ['star', 'Sushi y pizzerías'], ['sparkles', 'Spas'], ['gift', 'Tiendas de barrio']];

// Decorative QR-like pattern: three finder squares and a stable pseudo-random fill. It doesn't encode anything.
$fakeQr = static function (int $seed): string {
    $n = 21;
    $cells = '';
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            $fx = $x >= $n - 7 ? $n - 7 : 0;
            $fy = $y >= $n - 7 ? $n - 7 : 0;
            $inFinder = ($x < 7 || $x >= $n - 7) && ($y < 7 || $y >= $n - 7) && !($x >= $n - 7 && $y >= $n - 7);
            if ($inFinder) {
                $dx = $x - $fx;
                $dy = $y - $fy;
                $on = $dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4);
            } elseif (($x < 8 || $x >= $n - 8) && ($y < 8 || $y >= $n - 8) && !($x >= $n - 8 && $y >= $n - 8)) {
                $on = false;
            } else {
                $on = crc32("{$seed}:{$x}:{$y}") % 100 < 47;
            }
            if ($on) {
                $cells .= "<rect x=\"{$x}\" y=\"{$y}\" width=\"1.02\" height=\"1.02\"/>";
            }
        }
    }
    return "<svg class=\"fake-qr\" viewBox=\"0 0 {$n} {$n}\" shape-rendering=\"crispEdges\" aria-hidden=\"true\">{$cells}</svg>";
};
?>
<!doctype html>
<html lang="es">
<head>
<?php partial('head', ['title' => $title]); ?>
<link rel="stylesheet" href="<?= e(asset('css/landing.css')) ?>">
<script defer src="<?= e(asset('js/landing.js')) ?>"></script>
</head>
<body class="landing">
<?php partial('scene'); ?>
<canvas class="confetti" aria-hidden="true"></canvas>
<header class="topbar">
  <div class="wrap">
    <?php partial('brand', ['by' => true]); ?>
    <nav>
      <a href="#como-funciona" class="btn btn-ghost btn-sm hide-md">Cómo funciona</a>
      <a href="#funciones" class="btn btn-ghost btn-sm hide-md">Funciones</a>
      <a href="#precios" class="btn btn-ghost btn-sm hide-sm">Precios</a>
      <a href="<?= e(u('/entrar')) ?>" class="btn btn-glass btn-sm">Entrar con ZERO</a>
    </nav>
  </div>
</header>

<main>
  <!-- Hero: a card that fills itself, stamp by stamp. -->
  <section class="wrap hero">
    <div>
      <span class="pill reveal"><span>Nuevo</span> Una app de ZERO Solutions</span>
      <h1 class="reveal">Que tus clientes <span class="gradient-text word-loop" data-words="vuelvan|regresen|te prefieran">vuelvan</span>, una y otra vez.</h1>
      <p class="lead reveal">Repeat convierte la tarjeta de sellos de cartón en una tarjeta digital en el celular de tus clientes. Sin apps que descargar, sin tarjetas perdidas.</p>
      <div class="cta-row reveal">
        <a href="<?= e(u('/entrar')) ?>" class="btn btn-primary btn-lg">Crear mi tarjeta gratis <?= icon('arrow-right', 18) ?></a>
        <a href="#pruebalo" class="btn btn-glass btn-lg">Pruébala aquí</a>
      </div>
      <ul class="hero-points reveal">
        <li><?= icon('check', 16) ?> Gratis para empezar</li>
        <li><?= icon('check', 16) ?> Sin lectores ni máquinas</li>
        <li><?= icon('check', 16) ?> Lista en 2 minutos</li>
      </ul>
    </div>
    <div class="hero-card reveal" data-tilt>
      <div class="tilt">
        <div class="behind"><?php partial('loyalty_card', ['business' => $demo2, 'stamps' => 3]); ?></div>
        <div data-live-card data-start="4"><?php partial('loyalty_card', ['business' => $demo, 'stamps' => 4]); ?></div>
      </div>
      <div class="hero-chip chip-a glass"><?= icon('wallet', 15) ?> Google Wallet</div>
      <div class="hero-chip chip-b glass"><?= icon('smartphone', 15) ?> Sin descargar apps</div>
      <div class="toast-demo glass" data-live-toast aria-live="polite">
        <span class="dot" data-toast-icon><?= icon('check', 16) ?></span>
        <span data-toast-text><b>Matías</b> sumó un sello<br><span class="sub">Le faltan 6 para su corte gratis</span></span>
      </div>
      <template data-gift-icon><?= icon('gift', 16) ?></template>
      <template data-check-icon><?= icon('check', 16) ?></template>
    </div>
  </section>

  <!-- Who it's for. -->
  <section class="marquee-wrap reveal" aria-label="Para quién es Repeat">
    <p class="marquee-title">Para todo negocio al que la gente vuelve</p>
    <div class="marquee">
      <?php for ($copy = 0; $copy < 2; $copy++): ?>
        <ul<?= $copy ? ' aria-hidden="true"' : '' ?>>
          <?php foreach ($businesses as [$ic, $label]): ?>
            <li><span><?= stamp_svg($ic) ?></span><?= e($label) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endfor; ?>
    </div>
  </section>

  <!-- How it works: the phone follows the steps as you scroll. -->
  <section class="wrap section" id="como-funciona">
    <div class="section-head reveal">
      <span class="eyebrow">Cómo funciona</span>
      <h2>Tres pasos y tus clientes ya están juntando sellos</h2>
      <p>Tú configuras la tarjeta una vez. Tus clientes la guardan en su celular y vuelven por su premio.</p>
    </div>
    <div class="story" data-story>
      <div class="story-phone">
        <div class="phone">
          <div class="phone-notch"></div>
          <div class="phone-screen">
            <div class="ph-screen on" data-screen>
              <div class="ph-title">Diseña tu tarjeta</div>
              <div data-cycle-card><?php partial('loyalty_card', ['business' => $phoneCard, 'stamps' => 5]); ?></div>
              <div class="ph-field"><span>Premio</span><b>Corte gratis</b></div>
              <div class="ph-field"><span>Sellos para el premio</span><b>8</b></div>
              <div class="ph-swatches">
                <?php foreach ($presets as $i => [, $bg, $accent]): ?>
                  <i class="<?= $i === 0 ? 'on' : '' ?>" style="--c1:<?= $bg ?>;--c2:<?= $accent ?>" data-preset="<?= $bg ?>,<?= $accent ?>"></i>
                <?php endforeach; ?>
              </div>
              <div class="ph-btn">Guardar tarjeta</div>
            </div>
            <div class="ph-screen" data-screen>
              <div class="ph-poster">
                <small>Barbería Norte</small>
                <b>Junta sellos y gana un corte gratis</b>
                <div class="ph-qr"><?= $fakeQr(11) ?><span class="ph-laser"></span></div>
                <small>Escanea con la cámara</small>
              </div>
              <div class="ph-sheet">
                <b>Únete a Barbería Norte</b>
                <div class="ph-input"><span>Nombre</span><i class="type t1">Matías</i></div>
                <div class="ph-input"><span>Teléfono</span><i class="type t2">+56 9 1234 5678</i></div>
                <div class="ph-btn ph-press">Obtener mi tarjeta</div>
              </div>
            </div>
            <div class="ph-screen" data-screen>
              <div class="ph-title">Escanear</div>
              <div class="ph-cam">
                <div class="ph-cam-qr"><?= $fakeQr(23) ?></div>
                <span class="ph-laser"></span>
                <i></i><i></i><i></i><i></i>
              </div>
              <div class="ph-result">
                <div class="ph-cust">
                  <span class="ph-avatar">M</span>
                  <div><b>Matías</b><small class="swap"><span>6 de 8 sellos</span><span>7 de 8 sellos</span></small></div>
                </div>
                <div class="ph-dots">
                  <?php for ($i = 0; $i < 8; $i++): ?><span class="<?= $i < 6 ? 'on' : ($i === 6 ? 'next' : '') ?>"></span><?php endfor; ?>
                </div>
                <div class="ph-btn ph-press">Sumar sello</div>
              </div>
              <div class="ph-toast"><?= icon('check', 14) ?> Sello sumado. Le falta 1.</div>
            </div>
          </div>
        </div>
      </div>
      <ol class="story-steps">
        <li class="story-step on" data-step>
          <span class="num">1</span>
          <div>
            <h3>Diseña tu tarjeta en 2 minutos</h3>
            <p>Elige tus colores, sube tu logo, define el premio y cuántos sellos se necesitan. Ves cómo queda mientras la editas.</p>
          </div>
        </li>
        <li class="story-step" data-step>
          <span class="num">2</span>
          <div>
            <h3>Tus clientes escanean el QR</h3>
            <p>Imprime el afiche con tu código y ponlo en el mesón. Tus clientes dejan su nombre y teléfono, y la tarjeta queda en su celular. Sin descargar ninguna app.</p>
          </div>
        </li>
        <li class="story-step" data-step>
          <span class="num">3</span>
          <div>
            <h3>Suma sellos con tu celular</h3>
            <p>Escanea su tarjeta con la cámara o búscalo por su teléfono. El sello aparece en su celular al instante y, al completar la tarjeta, gana su premio.</p>
          </div>
        </li>
      </ol>
    </div>
  </section>

  <!-- Try it: a playable card. -->
  <section class="wrap section" id="pruebalo">
    <div class="section-head reveal">
      <span class="eyebrow">Pruébala</span>
      <h2>Así se siente tu tarjeta</h2>
      <p>Cambia los colores, elige el ícono y suma sellos hasta ganar el premio.</p>
    </div>
    <div class="demo glass reveal" data-demo>
      <div class="demo-stage">
        <div class="demo-card" data-demo-card><?php partial('loyalty_card', ['business' => $demo2, 'stamps' => 3]); ?></div>
        <p class="demo-status" data-demo-status aria-live="polite">Le faltan 5 sellos para su café gratis.</p>
      </div>
      <div class="demo-controls">
        <div class="ctrl">
          <span class="ctrl-label">Colores</span>
          <div class="demo-swatches">
            <?php foreach ($presets as $i => [$label, $bg, $accent]): ?>
              <button type="button" class="<?= $i === 1 ? 'on' : '' ?>" style="--c1:<?= $bg ?>;--c2:<?= $accent ?>" data-colors="<?= $bg ?>,<?= $accent ?>" aria-label="<?= e($label) ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="ctrl">
          <span class="ctrl-label">Ícono del sello <span class="badge badge-pro"><?= icon('crown', 12) ?> Pro</span></span>
          <div class="icon-picks">
            <?php foreach (['check', 'coffee', 'scissors', 'heart', 'star', 'sparkles'] as $i => $ic): ?>
              <label class="icon-pick" title="<?= e(STAMP_ICONS[$ic][0]) ?>">
                <input type="radio" name="demo_icon" value="<?= $ic ?>" data-svg="<?= e(stamp_svg($ic)) ?>"<?= $i === 0 ? ' checked' : '' ?>>
                <span><?= stamp_svg($ic) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="ctrl toggles">
          <label class="switch-row"><input type="checkbox" data-demo-double><span class="switch"></span><span>Hora de sellos dobles <span class="badge badge-pro"><?= icon('crown', 12) ?> Pro</span></span></label>
          <label class="switch-row"><input type="checkbox" data-demo-brand><span class="switch"></span><span>Quitar el logo de Repeat <span class="badge badge-pro"><?= icon('crown', 12) ?> Pro</span></span></label>
        </div>
        <div class="demo-actions">
          <button type="button" class="btn btn-primary btn-lg" data-demo-stamp><?= icon('stamp', 18) ?> <span>Sumar sello</span></button>
          <button type="button" class="btn btn-glass btn-lg" data-demo-reset><?= icon('undo', 18) ?> Reiniciar</button>
        </div>
      </div>
    </div>
  </section>

  <!-- Features. -->
  <section class="wrap section" id="funciones">
    <div class="section-head reveal">
      <span class="eyebrow">Todo incluido</span>
      <h2>Simple para ti, mágico para tus clientes</h2>
      <p>Todo lo que necesitas para que vuelvan, en el celular que ya tienes.</p>
    </div>
    <div class="bento">
      <article class="glass tile span-2 reveal">
        <div class="tile-text">
          <div class="ic"><?= icon('wallet', 20) ?></div>
          <h3>En la billetera del celular</h3>
          <p>Con un toque, la tarjeta queda en Google Wallet junto a sus pases y tarjetas, y se actualiza sola con cada sello. ¿Apple Wallet? Escríbenos.</p>
        </div>
        <div class="viz-wallet" aria-hidden="true">
          <div class="pass p3"></div>
          <div class="pass p2"></div>
          <div class="pass p1">
            <div class="pass-top"><span class="pass-logo">B</span><b>Barbería Norte</b></div>
            <div class="pass-row"><div><small>Sellos</small><b>7 / 10</b></div><div><small>Premio</small><b>Corte gratis</b></div></div>
            <div class="pass-qr"><?= $fakeQr(5) ?></div>
          </div>
        </div>
      </article>
      <article class="glass tile reveal">
        <div class="ic"><?= icon('scan', 20) ?></div>
        <h3>Escáner en tu celular</h3>
        <p>Sin máquinas ni lectores: usa la cámara o busca al cliente por su teléfono.</p>
        <div class="viz-scan" aria-hidden="true"><?= $fakeQr(31) ?><span class="ph-laser"></span><i></i><i></i><i></i><i></i></div>
      </article>
      <article class="glass tile reveal">
        <div class="ic"><?= icon('zap', 20) ?></div>
        <h3>Sellos al instante</h3>
        <p>El cliente ve aparecer su sello en el mismo momento, y celebra cuando gana su premio.</p>
        <div class="viz-live" aria-hidden="true"><span class="on"><?= stamp_svg('check') ?></span><span class="on"><?= stamp_svg('check') ?></span><span class="pulse"><?= stamp_svg('check') ?></span><span></span></div>
      </article>
      <article class="glass tile span-2 reveal">
        <div class="tile-text">
          <div class="ic"><?= icon('trending-up', 20) ?></div>
          <h3>Conoce a tus clientes</h3>
          <p>Sellos de cada día, quiénes vuelven más, cuándo fue su última visita y quién tiene un premio listo para canjear.</p>
        </div>
        <div class="viz-bars" aria-hidden="true">
          <?php foreach ([38, 52, 44, 70, 58, 86, 64, 92, 74, 100, 82, 96] as $i => $h): ?><span style="--h:<?= $h ?>%;--i:<?= $i ?>"></span><?php endforeach; ?>
        </div>
      </article>
      <article class="glass tile reveal">
        <div class="ic"><?= icon('message', 20) ?></div>
        <h3>Clientes por recuperar</h3>
        <p>Ve quién no vuelve hace más de un mes y escríbele por WhatsApp con un toque.</p>
        <div class="viz-list" aria-hidden="true">
          <div><span class="av">C</span><span><b>Camila</b><small>Hace 34 días</small></span><em>Invitar</em></div>
          <div><span class="av">J</span><span><b>Jorge</b><small>Hace 41 días</small></span><em>Invitar</em></div>
        </div>
      </article>
      <article class="glass tile reveal">
        <div class="ic"><?= icon('cake', 20) ?></div>
        <h3>Cumpleaños del mes</h3>
        <p>En tu inicio ves quién está de cumpleaños para saludarlo a tiempo.</p>
        <div class="viz-list" aria-hidden="true">
          <div><span class="av">V</span><span><b>Valentina</b><small>21 de octubre</small></span><em>Saludar</em></div>
        </div>
      </article>
      <article class="glass tile span-2 reveal">
        <div class="tile-text">
          <div class="ic"><?= icon('palette', 20) ?></div>
          <h3>Con tu marca</h3>
          <p>Tus colores, tu logo y tu premio. Cambia el diseño cuando quieras y todas las tarjetas se actualizan.</p>
        </div>
        <div class="viz-brand" aria-hidden="true">
          <?php foreach ($presets as [, $bg, $accent]): ?><span style="--c1:<?= $bg ?>;--c2:<?= $accent ?>"></span><?php endforeach; ?>
        </div>
      </article>
      <article class="glass tile span-2 reveal">
        <div class="tile-text">
          <div class="ic"><?= icon('printer', 20) ?></div>
          <h3>Afiche listo para imprimir</h3>
          <p>Descarga el afiche con tu QR, imprímelo y ponlo en el mesón. También puedes compartir el enlace en tus redes.</p>
        </div>
        <div class="viz-poster" aria-hidden="true">
          <small>Barbería Norte</small><b>Junta sellos y gana un corte gratis</b><?= $fakeQr(17) ?>
        </div>
      </article>
    </div>
  </section>

  <!-- Repeat Pro. -->
  <section class="wrap section" id="pro">
    <div class="pro-band glass reveal">
      <div class="pro-copy">
        <span class="badge badge-pro"><?= icon('crown', 13) ?> Repeat Pro</span>
        <h2>Tu marca en primer plano y más razones para volver</h2>
        <p>Quita el logo de Repeat, elige tu ícono de sello y activa regalos de cumpleaños, horas de sellos dobles y referidos.</p>
        <ul class="pro-list">
          <?php foreach (PRO_FEATURES as [$ic, $name, $text]): ?>
            <li><span class="ic"><?= icon($ic, 18) ?></span><div><b><?= e($name) ?></b><small><?= e($text) ?></small></div></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="pro-visual" aria-hidden="true">
        <div class="pro-card"><?php partial('loyalty_card', ['business' => ['stamp_icon' => 'scissors'] + $demo, 'stamps' => 6, 'preview_pro' => true]); ?></div>
        <div class="pro-chip c1 glass"><?= icon('cake', 15) ?> Regalo de cumpleaños</div>
        <div class="pro-chip c2 glass"><?= icon('zap', 15) ?> Sellos dobles · Mar 15:00 a 18:00</div>
        <div class="pro-chip c3 glass"><?= icon('user-plus', 15) ?> Camila invitó a Diego · +1 sello</div>
      </div>
    </div>
  </section>

  <!-- Pricing. -->
  <section class="wrap section" id="precios">
    <div class="section-head reveal">
      <span class="eyebrow">Precios</span>
      <h2>Empieza gratis. Pásate a Pro cuando quieras.</h2>
      <p>Precios en pesos chilenos con IVA incluido. Sin contratos.</p>
    </div>
    <div class="plans">
      <article class="glass plan reveal">
        <h3>Gratis</h3>
        <p class="plan-tag">Todo lo esencial para tu tarjeta de sellos.</p>
        <div class="price-line"><span class="now">$0</span><span class="per">para siempre</span></div>
        <a href="<?= e(u('/entrar')) ?>" class="btn btn-glass btn-block btn-lg">Crear mi tarjeta gratis</a>
        <ul>
          <li><?= icon('check', 16) ?> Tarjeta con tus colores, tu logo y tu premio</li>
          <li><?= icon('check', 16) ?> Clientes y sellos sin límite</li>
          <li><?= icon('check', 16) ?> Escáner con la cámara del celular</li>
          <li><?= icon('check', 16) ?> Google Wallet</li>
          <li><?= icon('check', 16) ?> Afiche con tu QR</li>
          <li><?= icon('check', 16) ?> Clientes por recuperar y cumpleaños del mes</li>
          <li><?= icon('check', 16) ?> Estadísticas de visitas y sellos</li>
          <li class="muted"><?= icon('minus', 16) ?> Las tarjetas muestran el logo de Repeat</li>
        </ul>
      </article>
      <article class="glass plan plan-pro reveal">
        <span class="plan-ribbon">Oferta de lanzamiento · <?= $save ?>% menos</span>
        <h3><?= icon('crown', 20) ?> Repeat Pro</h3>
        <p class="plan-tag">Tu marca en primer plano y más razones para volver.</p>
        <div class="price-line"><span class="was"><?= e(clp(PRO_LIST_PRICE)) ?></span><span class="now"><?= e(clp(PRO_PRICE)) ?></span><span class="per">/ mes</span></div>
        <a href="<?= e(pro_upgrade_url()) ?>" class="btn btn-primary btn-block btn-lg">Activar Repeat Pro <?= icon('arrow-right', 18) ?></a>
        <ul>
          <li><?= icon('check', 16) ?> Todo lo del plan gratis</li>
          <?php foreach (PRO_FEATURES as [, $name]): ?><li><?= icon('check', 16) ?> <?= e($name) ?></li><?php endforeach; ?>
        </ul>
        <p class="plan-note">También viene incluido en <b>ZERO Plus</b>. Se paga con Flow desde tu cuenta ZERO.</p>
      </article>
    </div>
  </section>

  <!-- FAQ. -->
  <section class="wrap section" id="preguntas">
    <div class="section-head reveal">
      <span class="eyebrow">Preguntas frecuentes</span>
      <h2>Lo que más nos preguntan</h2>
    </div>
    <div class="faq">
      <details class="glass reveal"><summary>¿Mis clientes tienen que descargar una app?<?= icon('plus', 18) ?></summary><p>No. Escanean tu QR, dejan su nombre y teléfono, y la tarjeta se abre en el navegador de su celular. Si quieren, la guardan en Google Wallet con un toque.</p></details>
      <details class="glass reveal"><summary>¿Necesito comprar un lector o una máquina?<?= icon('plus', 18) ?></summary><p>No. Sumas sellos desde tu celular: escaneas la tarjeta del cliente con la cámara o lo buscas por su número de teléfono.</p></details>
      <details class="glass reveal"><summary>¿Qué pasa si un cliente cambia de celular o pierde el enlace?<?= icon('plus', 18) ?></summary><p>Sus sellos no se pierden: quedan guardados con su teléfono. Si vuelve a escanear tu QR con el mismo número, recupera su misma tarjeta.</p></details>
      <details class="glass reveal"><summary>¿Funciona con iPhone?<?= icon('plus', 18) ?></summary><p>Sí, la tarjeta funciona en cualquier celular con navegador. Para guardarla en Apple Wallet, escríbenos a hola@zerosolutions.cl y la activamos para tu negocio.</p></details>
      <details class="glass reveal"><summary>¿Cómo pago Repeat Pro?<?= icon('plus', 18) ?></summary><p>Desde tu cuenta ZERO, con Flow. Pagas por mes o por año y no hay renovación automática. Si no renuevas, vuelves al plan gratis y tus clientes y sellos se mantienen.</p></details>
      <details class="glass reveal"><summary>¿Qué es mi cuenta ZERO?<?= icon('plus', 18) ?></summary><p>Es la cuenta con la que entras a todas las aplicaciones de ZERO Solutions, como Repeat y FlowDesk. Si no tienes una, la creas gratis al entrar.</p></details>
    </div>
  </section>

  <section class="wrap">
    <div class="glass cta-band reveal">
      <h2>Tu próximo cliente fiel está a un sello de distancia</h2>
      <p>Crea tu tarjeta con tu cuenta ZERO y empieza hoy.</p>
      <div class="cta-row"><a href="<?= e(u('/entrar')) ?>" class="btn btn-primary btn-lg">Crear mi tarjeta gratis <?= icon('arrow-right', 18) ?></a></div>
    </div>
    <footer class="site-foot">
      <span>Repeat es una aplicación de ZERO Solutions · Hecho en Chile</span>
      <span class="foot-links"><a href="mailto:hola@zerosolutions.cl">hola@zerosolutions.cl</a><a href="<?= e($zero) ?>">zerosolutions.cl</a></span>
    </footer>
  </section>
</main>
</body>
</html>
