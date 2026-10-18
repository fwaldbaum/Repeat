<?php
/** Tints the mountain scene with the business accent on customer-facing pages. */
$a = $business['card_accent'];
?>
<style>
:root { --haze: <?= e($a) ?>; --glow-2: color-mix(in srgb, <?= e($a) ?> 55%, transparent);
  --m1-top: color-mix(in srgb, <?= e($a) ?> 70%, #2a1a6e); --m2-top: color-mix(in srgb, <?= e($a) ?> 45%, #1d1152); --m3-top: color-mix(in srgb, <?= e($a) ?> 25%, #120a34); }
</style>
