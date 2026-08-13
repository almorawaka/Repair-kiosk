<div class="kiosk-step">
  <h2>Step 4 of 4 — Confirm</h2>
  <table class="confirm-table">
    <tr><th>Equipment</th><td><?= e($wizard['equipment_name'] ?? '') ?> (<?= e($wizard['asset_tag'] ?? '') ?>)</td></tr>
    <tr><th>Dropped off by</th>
        <td><?= e($wizard['borrower_name'] ?? $wizard['walkin_name'] ?? 'Unknown') ?></td></tr>
    <tr><th>Fault</th><td><?= nl2br(e($wizard['fault_description'] ?? '')) ?></td></tr>
  </table>
  <form method="POST" action="<?= url('/kiosk/dropoff/confirm') ?>">
    <?= \App\Core\Csrf::field() ?>
    <button type="submit" class="kiosk-big-button kiosk-btn-primary">Confirm Drop-off</button>
  </form>
  <a href="<?= url('/kiosk/dropoff/scan') ?>" class="kiosk-cancel-link">Start over</a>
</div>
