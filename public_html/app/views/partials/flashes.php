<?php $messages = take_flash(); if ($messages): ?>
<div class="flashes">
  <?php foreach ($messages as $m): ?>
    <div class="alert alert-<?= $m['type'] === 'error' ? 'error' : 'success' ?>" role="status"><?= icon($m['type'] === 'error' ? 'alert' : 'check', 18) ?><span><?= e($m['message']) ?></span></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
