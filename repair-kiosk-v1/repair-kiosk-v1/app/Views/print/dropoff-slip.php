<div class="slip">
  <h1><?= e($workshopName) ?></h1>
  <h2>Drop-off Slip</h2>
  <p class="slip-job-number"><?= e($job['job_number']) ?></p>
  <table>
    <tr><th>Equipment</th><td><?= e($job['equipment_name']) ?> (<?= e($job['asset_tag']) ?>)</td></tr>
    <tr><th>Fault</th><td><?= nl2br(e($job['fault_description'])) ?></td></tr>
    <tr><th>Dropped off</th><td><?= e($job['dropped_off_at']) ?></td></tr>
  </table>
  <img src="<?= e($qrImage) ?>" alt="QR" class="slip-qr">
  <p class="slip-footer">Keep this slip. It is required for collection.<br><?= e($trackUrl) ?></p>
</div>
