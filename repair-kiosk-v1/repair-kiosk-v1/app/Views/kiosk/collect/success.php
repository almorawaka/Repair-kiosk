<div class="kiosk-step kiosk-success">
  <h2>Collected — thank you</h2>
  <p>Job <strong><?= e($job['job_number']) ?></strong> is now closed.</p>
  <div class="kiosk-choice-grid">
    <a class="kiosk-big-button kiosk-btn-secondary" href="<?= url('/kiosk/print/handover/' . $job['id']) ?>" target="_blank">Print Receipt</a>
    <a class="kiosk-big-button kiosk-btn-primary" href="<?= url('/kiosk') ?>">Done</a>
  </div>
</div>
