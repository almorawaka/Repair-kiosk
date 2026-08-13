<div class="kiosk-step">
  <h2>Step 3 of 4 — What's wrong with it?</h2>
  <form method="POST" action="<?= url('/kiosk/dropoff/fault') ?>" class="fault-form">
    <?= \App\Core\Csrf::field() ?>
    <textarea name="fault_description" rows="5" placeholder="Describe the problem..." class="fault-textarea" autofocus></textarea>
    <button type="submit" class="kiosk-big-button kiosk-btn-primary">Continue</button>
  </form>
</div>
