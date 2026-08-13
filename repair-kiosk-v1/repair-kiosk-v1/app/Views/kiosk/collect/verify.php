<div class="kiosk-step">
  <h2>Confirm Collection</h2>
  <table class="confirm-table">
    <tr><th>Job number</th><td><?= e($job['job_number']) ?></td></tr>
    <tr><th>Equipment</th><td><?= e($job['equipment_name']) ?> (<?= e($job['asset_tag']) ?>)</td></tr>
  </table>
  <p>By confirming, you acknowledge you are collecting this item.</p>
  <form method="POST" action="<?= url('/kiosk/collect/verify/' . $job['id']) ?>">
    <?= \App\Core\Csrf::field() ?>
    <button type="submit" class="kiosk-big-button kiosk-btn-primary">Confirm &amp; Collect</button>
  </form>
  <a href="<?= url('/kiosk') ?>" class="kiosk-cancel-link">Cancel</a>
</div>
