<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$usuarioController = new UsuarioController();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
        $erro = 'Preencha todos os campos.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'A nova senha e a confirmação não coincidem.';
    } else {
        $resultado = $usuarioController->alterarSenhaPropria($_SESSION['usuario_id'], $senhaAtual, $novaSenha);
        if ($resultado['sucesso']) {
            $sucesso = $resultado['mensagem'];
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}
$pageTitle = 'Alterar Senha - RMG ERP';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:56px;height:56px;background:var(--rmg-primary-bg);color:var(--rmg-primary);">
                            <i class="fas fa-key fa-lg"></i>
                        </span>
                        <h5 class="fw-semibold mb-1">Alterar Senha</h5>
                        <p class="text-muted small mb-0">Preencha os campos abaixo para definir uma nova senha</p>
                    </div>
                    <?php if ($erro): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($erro); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($sucesso); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="alterar_senha.php">
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                        </div>
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                            <div class="form-text">A senha deve conter pelo menos 6 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>