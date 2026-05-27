<?php
$sucessoMsg = $sucessoMsg ?? null;
$erroMsg = $erroMsg ?? null;

if ($sucessoMsg): ?>
<script>window._pendingToast = { message: <?= json_encode($sucessoMsg) ?>, type: 'success' };</script>
<?php elseif ($erroMsg): ?>
<script>window._pendingToast = { message: <?= json_encode($erroMsg) ?>, type: 'error' };</script>
<?php endif; ?>
