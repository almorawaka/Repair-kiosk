<?php if (!empty($flashes)): ?>
  <?php foreach ($flashes as $type => $message): ?>
    <div class="flash flash-<?= e($type) ?>"><?= e($message) ?></div>
  <?php endforeach; ?>
<?php endif; ?>
