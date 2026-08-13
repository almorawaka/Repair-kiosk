<div class="slip">
  <h1><?= e($workshopName) ?></h1>
  <h2>Handover Receipt</h2>
  <p class="slip-job-number"><?= e($job['job_number']) ?></p>
  <table>
    <tr><th>Equipment</th><td><?= e($job['equipment_name']) ?> (<?= e($job['asset_tag']) ?>)</td></tr>
    <tr><th>Collected</th><td><?= e($job['collected_at']) ?></td></tr>
  </table>
  <p class="slip-footer">This item has been returned to the borrower.</p>
</div>
