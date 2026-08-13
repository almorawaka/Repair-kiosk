<div class="kiosk-step">
  <h2>Step 2 of 4 — Who's dropping this off?</h2>
  <p>Dropping off: <strong><?= e($wizard['equipment_name'] ?? '') ?></strong> (<?= e($wizard['asset_tag'] ?? '') ?>)</p>

  <form method="POST" action="<?= url('/kiosk/dropoff/identify') ?>" class="scan-form">
    <?= \App\Core\Csrf::field() ?>
    <input type="text" name="code" autofocus autocomplete="off" placeholder="Scan your ID card" class="scan-input">
    <button type="submit" class="kiosk-big-button kiosk-btn-primary">Continue with ID</button>
  </form>

  <hr>
  <p>No ID? Continue as a walk-in:</p>
  <form method="POST" action="<?= url('/kiosk/dropoff/identify') ?>" class="walkin-form">
    <?= \App\Core\Csrf::field() ?>
    <input type="text" name="walkin_name" placeholder="Your full name" class="text-input">
    <input type="text" name="walkin_contact" placeholder="Phone or email (optional)" class="text-input">
    <button type="submit" class="kiosk-big-button kiosk-btn-secondary">Continue as Walk-in</button>
  </form>
</div>
