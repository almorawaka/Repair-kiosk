<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= e(config('app.name')) ?> — Kiosk</title>
<link rel="stylesheet" href="<?= asset('css/kiosk.css') ?>">
</head>
<body>
<header class="kiosk-header">
  <a href="<?= url('/kiosk') ?>" class="kiosk-logo"><?= e(config('app.name')) ?></a>
</header>
<main class="kiosk-main">
  <?php require BASE_PATH . '/app/Views/partials/flash.php'; ?>
  <?= $content ?>
</main>
<script src="<?= asset('js/idle-reset.js') ?>" data-idle-seconds="<?= (int) config('app.kiosk_idle_seconds') ?>"></script>
</body>
</html>
