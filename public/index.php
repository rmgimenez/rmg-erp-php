<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Principal - RMG ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <?php include __DIR__ . '/includes/menu.php'; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4>Bem-vindo, <?php echo htmlspecialchars($usuarioNome); ?>!</h4>
                    <p>Você está acessando como <strong><?php echo ucfirst($tipoUsuario); ?></strong>.</p>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Widgets Example -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Contas a Pagar</h5>
                        <p class="card-text display-6">R$ 0,00</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Contas a Receber</h5>
                        <p class="card-text display-6">R$ 0,00</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Manutenções</h5>
                        <p class="card-text display-6">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Bens Ativos</h5>
                        <p class="card-text display-6">0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
