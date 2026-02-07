<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $loginController = new LoginController();
    $resultado = $loginController->logar($usuario, $senha);

    if ($resultado['sucesso']) {
        header('Location: index.php');
        exit;
    } else {
        $erro = $resultado['mensagem'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão Financeira RMG</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/login.css" rel="stylesheet">
</head>
<body>

    <div class="card login-card">
        <div class="card-header">
            <div class="brand-title"><i class="fas fa-boxes me-2"></i> RMG ERP</div>
            <div class="brand-subtitle">Sistema de Gestão Financeira e Bens</div>
        </div>
        <div class="card-body">
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" id="loginForm">
                <div class="mb-3">
                    <label for="usuario" class="form-label text-muted">Usuário</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Digite seu usuário" required autofocus>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="senha" class="form-label text-muted">Senha</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center py-3 bg-white border-top-0">
            <small class="text-muted">RMG Soluções &copy; <?php echo date('Y'); ?></small>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
