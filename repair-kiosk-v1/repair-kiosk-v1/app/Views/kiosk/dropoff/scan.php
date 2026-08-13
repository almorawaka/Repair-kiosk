<div class="kiosk-step">
  <h2>Step 1 of 4 — Scan the item</h2>
  <p>Scan the asset tag on the equipment you're dropping off.</p>
  <form method="POST" action="<?= url('/kiosk/dropoff/scan') ?>" class="scan-form">
    <?= \App\Core\Csrf::field() ?>
    <input type="text" name="code" autofocus autocomplete="off" placeholder="Scan or type asset tag" class="scan-input">
    <button type="submit" class="kiosk-big-button kiosk-btn-primary">Continue</button>
  </form>
  <a href="<?= url('/kiosk') ?>" class="kiosk-cancel-link">Cancel</a>
</div>
