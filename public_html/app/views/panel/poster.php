<?php $joinUrl = url('/c/' . $business['slug']); $b = $business; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Sora:wght@600;700&display=swap">
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script defer src="<?= e(asset('js/repeat.js')) ?>"></script>
<style>
  @page { size: A4; margin: 0; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9e6f5; font-family: Inter, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .sheet { width: 210mm; height: 297mm; margin: 20px auto; position: relative; overflow: hidden; color: <?= e($b['card_fg']) ?>;
    background: radial-gradient(120% 70% at 100% 0%, color-mix(in srgb, <?= e($b['card_accent']) ?> 55%, transparent), transparent 60%), <?= e($b['card_bg']) ?>;
    display: flex; flex-direction: column; align-items: center; text-align: center; padding: 26mm 20mm 18mm; box-shadow: 0 20px 60px -20px rgba(0,0,0,.4); }
  .sheet svg.m { position: absolute; left: 0; right: 0; bottom: 0; width: 100%; height: 34%; }
  .logo { width: 30mm; height: 30mm; border-radius: 8mm; background: rgba(255,255,255,.14); display: grid; place-items: center; font: 700 16mm Sora, sans-serif; overflow: hidden; }
  .logo img { width: 100%; height: 100%; object-fit: contain; }
  h1 { margin: 9mm 0 0; font: 700 15mm/1.05 Sora, sans-serif; letter-spacing: -0.03em; }
  .sub { margin-top: 5mm; font-size: 6.4mm; opacity: .85; max-width: 150mm; }
  .qr { position: relative; z-index: 1; margin-top: 12mm; width: 86mm; height: 86mm; padding: 6mm; border-radius: 7mm; background: #fff; }
  .qr svg { width: 100%; height: 100%; }
  .scan { position: relative; z-index: 1; margin-top: 8mm; font: 600 7mm Sora, sans-serif; }
  .foot { position: absolute; z-index: 1; bottom: 12mm; left: 0; right: 0; font-size: 3.6mm; opacity: .8; }
  .tools { text-align: center; margin: 16px; }
  .tools button { height: 44px; padding: 0 22px; border-radius: 12px; border: 0; background: #4f46e5; color: #fff; font: 600 15px Inter, sans-serif; cursor: pointer; }
  @media print { body { background: none; } .sheet { margin: 0; box-shadow: none; } .tools { display: none; } }
</style>
</head>
<body>
<div class="tools"><button onclick="window.print()">Imprimir o guardar como PDF</button></div>
<div class="sheet">
  <div class="logo"><?php if ($b['logo']): ?><img src="<?= e(u($b['logo'])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($b['name'], 0, 1))) ?><?php endif; ?></div>
  <h1><?= e($b['name']) ?></h1>
  <p class="sub">Junta <?= (int) $b['stamps_required'] ?> sellos y gana <b><?= e($b['reward']) ?></b></p>
  <div class="qr" data-qr="<?= e($joinUrl) ?>"></div>
  <p class="scan">Escanea y obtén tu tarjeta digital</p>
  <svg class="m" viewBox="0 0 800 300" preserveAspectRatio="none" aria-hidden="true">
    <path d="M0 160 C80 120 140 60 220 90 C300 120 340 40 430 70 C520 100 580 30 660 80 C720 115 760 100 800 110 V300 H0Z" fill="<?= e($b['card_accent']) ?>" opacity=".35"/>
    <path d="M0 210 C90 170 160 130 250 160 C340 190 400 120 500 150 C590 177 660 140 800 170 V300 H0Z" fill="#000" opacity=".25"/>
    <path d="M0 250 C120 220 220 200 330 230 C440 258 560 210 800 240 V300 H0Z" fill="#000" opacity=".35"/>
  </svg>
  <?php if (double_label($b) && is_pro($b)): ?><p class="scan" style="margin-top:4mm;font-size:5mm;opacity:.85">Sellos dobles: <?= e(double_label($b)) ?></p><?php endif; ?>
  <p class="foot">Sin descargar apps · Gratis<?= is_pro($b) ? '' : ' · Funciona con Repeat' ?></p>
</div>
</body>
</html>
