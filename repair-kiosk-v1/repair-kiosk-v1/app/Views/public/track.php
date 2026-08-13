<div class="track-card">
  <h1><?= e(config('app.name')) ?></h1>
  <p class="track-job-number"><?= e($job['job_number']) ?></p>
  <p class="track-equipment"><?= e($job['equipment_name']) ?></p>

  <div class="track-status track-status-<?= e($meta['color'] ?? 'gray') ?>">
    <?= e($meta['public_label'] ?? $job['status']) ?>
  </div>

  <?php if (!empty($job['estimated_ready_date'])): ?>
    <p class="track-eta">Estimated ready: <?= e($job['estimated_ready_date']) ?></p>
  <?php endif; ?>

  <h3>Timeline</h3>
  <ul class="track-timeline">
    <?php foreach ($history as $row): ?>
      <li>
        <span class="track-timeline-date"><?= e($row['created_at']) ?></span>
        <span class="track-timeline-label"><?= e($allMeta[$row['to_status']]['public_label'] ?? $row['to_status']) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
