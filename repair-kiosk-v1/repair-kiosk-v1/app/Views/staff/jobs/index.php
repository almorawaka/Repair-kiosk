<h1>Active Jobs</h1>
<table class="staff-table">
<thead>
<tr><th>Job #</th><th>Equipment</th><th>Borrower</th><th>Status</th><th>Days open</th><th></th></tr>
</thead>
<tbody>
<?php foreach ($jobs as $job): ?>
  <tr>
    <td><?= e($job['job_number']) ?></td>
    <td><?= e($job['equipment_name']) ?> <span class="muted"><?= e($job['asset_tag']) ?></span></td>
    <td><?= e($job['borrower_name']) ?></td>
    <td><?php $status = $job['status']; $meta = $statuses[$status] ?? null; require BASE_PATH . '/app/Views/partials/status-badge.php'; ?></td>
    <td><?= (int) $job['days_open'] ?></td>
    <td><a href="<?= url('/staff/jobs/' . $job['id']) ?>">View</a></td>
  </tr>
<?php endforeach; ?>
<?php if (empty($jobs)): ?>
  <tr><td colspan="6" class="muted">No active jobs.</td></tr>
<?php endif; ?>
</tbody>
</table>
