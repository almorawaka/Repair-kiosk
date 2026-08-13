<?php
/** Expects $status (string) and $meta (array from statuses.php meta.*) in scope. */
$label = $meta['label'] ?? $status;
$color = $meta['color'] ?? 'gray';
?>
<span class="badge badge-<?= e($color) ?>"><?= e($label) ?></span>
