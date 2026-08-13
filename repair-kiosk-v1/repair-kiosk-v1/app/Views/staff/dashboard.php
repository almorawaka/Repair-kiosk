<h1>Dashboard</h1>
<div class="dash-cards">
  <?php foreach ($statuses as $key => $meta): ?>
    <?php if ($meta['is_terminal']) continue; ?>
    <div class="dash-card dash-<?= e($meta['color']) ?>">
      <div class="dash-count"><?= (int) ($counts[$key] ?? 0) ?></div>
      <div class="dash-label"><?= e($meta['label']) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<p><a href="<?= url('/staff/jobs') ?>">View all active jobs &rarr;</a></p>
