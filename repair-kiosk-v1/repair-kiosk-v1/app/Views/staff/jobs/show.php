<p><a href="<?= url('/staff/jobs') ?>">&larr; Back to jobs</a></p>
<h1><?= e($job['job_number']) ?></h1>

<table class="detail-table">
  <tr><th>Equipment</th><td><?= e($job['equipment_name']) ?> (<?= e($job['asset_tag']) ?>)</td></tr>
  <tr><th>Status</th><td><?php $status = $job['status']; $meta = $statuses['meta'][$status] ?? null; require BASE_PATH . '/app/Views/partials/status-badge.php'; ?></td></tr>
  <tr><th>Fault</th><td><?= nl2br(e($job['fault_description'])) ?></td></tr>
  <tr><th>Dropped off</th><td><?= e($job['dropped_off_at']) ?></td></tr>
</table>

<h3>Change Status</h3>
<?php $allowed = $statuses['transitions'][$job['status']] ?? []; ?>
<?php if (empty($allowed)): ?>
  <p class="muted">This job is closed — no further status changes.</p>
<?php else: ?>
  <form method="POST" action="<?= url('/staff/jobs/' . $job['id'] . '/status') ?>">
    <?= \App\Core\Csrf::field() ?>
    <select name="status" required>
      <option value="">Choose new status...</option>
      <?php foreach ($allowed as $option): ?>
        <option value="<?= e($option) ?>"><?= e($statuses['meta'][$option]['label'] ?? $option) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="note" placeholder="Optional note">
    <button type="submit" class="btn-primary">Update</button>
  </form>
<?php endif; ?>

<h3>History</h3>
<ul class="history-list">
  <?php foreach ($history as $row): ?>
    <li><?= e($row['created_at']) ?> — <?= e($statuses['meta'][$row['to_status']]['label'] ?? $row['to_status']) ?>
      <span class="muted">(<?= e($row['source']) ?>)</span>
      <?php if (!empty($row['note'])): ?> — <?= e($row['note']) ?><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
