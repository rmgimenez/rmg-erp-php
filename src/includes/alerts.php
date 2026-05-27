<?php
/**
 * Alertas de sucesso/erro compartilhados
 * Uso: require_once __DIR__ . '/includes/alerts.php';
 * Variáveis esperadas: $sucessoMsg, $erroMsg (opcionais)
 */
$sucessoMsg = $sucessoMsg ?? null;
$erroMsg = $erroMsg ?? null;
?>
<?php if ($sucessoMsg): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($sucessoMsg, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($erroMsg): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($erroMsg, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
