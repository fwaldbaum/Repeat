<?php
/**
 * Stamps per day, 14 columns. One series, so no legend: the box title names it. Columns are <= 24px
 * with a 4px rounded top and a square base on a hairline baseline; the peak carries a direct label,
 * every column has a hover tooltip, and a table carries the same numbers for screen readers.
 */
$max = max(1, ...array_column($days, 'count'));
$w = 560; $h = 170; $pad = 22; $base = $h - $pad;
$slot = $w / count($days);
$colW = min(24, $slot - 6);
$weekdays = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
$peak = array_search(max(array_column($days, 'count')), array_column($days, 'count'), true);
?>
<div class="chart" data-chart>
  <svg viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-label="Sellos por día en los últimos 14 días">
    <line class="grid-line" x1="0" x2="<?= $w ?>" y1="<?= $base ?>" y2="<?= $base ?>"/>
    <line class="grid-line" x1="0" x2="<?= $w ?>" y1="<?= $pad ?>" y2="<?= $pad ?>" opacity=".5"/>
    <?php foreach ($days as $i => $d):
        $x = $i * $slot + ($slot - $colW) / 2;
        $hgt = $d['count'] ? max(4, ($base - $pad) * $d['count'] / $max) : 0;
        $y = $base - $hgt;
        $r = min(4, $hgt);
        $dt = new DateTime($d['date']);
        $label = $weekdays[(int) $dt->format('w')] . ' ' . $dt->format('j');
    ?>
      <rect class="hit" x="<?= $i * $slot ?>" y="0" width="<?= $slot ?>" height="<?= $base ?>" data-tip="<?= e($label) ?>" data-value="<?= $d['count'] ?>"/>
      <?php if ($hgt > 0): ?>
        <path class="col" d="M<?= $x ?> <?= $base ?>V<?= $y + $r ?>Q<?= $x ?> <?= $y ?> <?= $x + $r ?> <?= $y ?>H<?= $x + $colW - $r ?>Q<?= $x + $colW ?> <?= $y ?> <?= $x + $colW ?> <?= $y + $r ?>V<?= $base ?>Z"/>
      <?php endif; ?>
      <?php if ($i === $peak && $d['count'] > 0): ?><text class="val" x="<?= $x + $colW / 2 ?>" y="<?= $y - 6 ?>" text-anchor="middle"><?= $d['count'] ?></text><?php endif; ?>
      <?php if ($i % 2 === 1 || $i === count($days) - 1): ?><text x="<?= $x + $colW / 2 ?>" y="<?= $h - 4 ?>" text-anchor="middle"><?= e($i === count($days) - 1 ? 'hoy' : $label) ?></text><?php endif; ?>
    <?php endforeach; ?>
  </svg>
  <table class="sr-only"><caption>Sellos por día</caption><tr><th>Día</th><th>Sellos</th></tr>
    <?php foreach ($days as $d): ?><tr><td><?= e($d['date']) ?></td><td><?= $d['count'] ?></td></tr><?php endforeach; ?>
  </table>
</div>
