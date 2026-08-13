<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(config('app.name')) ?> — Staff</title>
<link rel="stylesheet" href="<?= asset('css/staff.css') ?>">
</head>
<body>
<?php if (\App\Core\Auth::check()): ?>
<nav class="staff-nav">
  <a href="<?= url('/staff/dashboard') ?>">Dashboard</a>
  <a href="<?= url('/staff/jobs') ?>">Jobs</a>
  <span class="staff-nav-user">
    <?= e(\App\Core\Auth::user()['full_name']) ?>
    (<?= e(\App\Core\Auth::user()['role']) ?>)
    <form method="POST" action="<?= url('/staff/logout') ?>" style="display:inline">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit" class="link-button">Log out</button>
    </form>
  </span>
</nav>
<?php endif; ?>
<main class="staff-main">
  <?php require BASE_PATH . '/app/Views/partials/flash.php'; ?>
  <?= $content ?>
</main>
</body>
</html>
