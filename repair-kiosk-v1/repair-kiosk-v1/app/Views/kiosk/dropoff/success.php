<div class="kiosk-step kiosk-success">
  <h2>Drop-off complete</h2>
  <p>Job number: <strong><?= e($job['job_number']) ?></strong></p>
  <p>Scan this QR code any time to check on your repair:</p>
  <img src="<?= e($qrImage) ?>" alt="Tracking QR code" class="qr-image">
  <p class="track-url"><?= e($trackUrl) ?></p>
  <div class="kiosk-choice-grid">
    <a class="kiosk-big-button kiosk-btn-secondary" href="<?= url('/kiosk/print/dropoff/' . $job['id']) ?>" target="_blank">Print Slip</a>
    <a class="kiosk-big-button kiosk-btn-primary" href="<?= url('/kiosk') ?>">Done</a>
  </div>
</div>
