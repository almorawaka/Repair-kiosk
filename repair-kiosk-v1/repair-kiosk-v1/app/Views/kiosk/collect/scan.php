<div class="kiosk-step">
  <h2>Collect Equipment</h2>
  <p>Scan the QR code on your slip, or the asset tag on the item.</p>
  <form method="POST" action="<?= url('/kiosk/collect/scan') ?>" class="scan-form">
    <?= \App\Core\Csrf::field() ?>
    <input type="text" name="code" autofocus autocomplete="off" placeholder="Scan slip or asset tag" class="scan-input">
    <button type="submit" class="kiosk-big-button kiosk-btn-primary">Continue</button>
  </form>
  <a href="<?= url('/kiosk') ?>" class="kiosk-cancel-link">Cancel</a>
</div>
