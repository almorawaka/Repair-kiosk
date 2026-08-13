<div class="login-box">
  <h1><?= e(config('app.name')) ?></h1>
  <h2>Staff Login</h2>
  <form method="POST" action="<?= url('/staff/login') ?>">
    <?= \App\Core\Csrf::field() ?>
    <label>Username</label>
    <input type="text" name="username" autofocus required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit" class="btn-primary">Log in</button>
  </form>
</div>
