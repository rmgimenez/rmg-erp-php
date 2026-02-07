<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="fas fa-boxes me-2"></i> RMG ERP</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $paginaAtual === 'index.php' ? 'active' : ''; ?>" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Financeiro</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Contas a Pagar</a></li>
                        <li><a class="dropdown-item" href="#">Contas a Receber</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($paginaAtual, ['setores.php', 'clientes.php', 'fornecedores.php', 'usuarios.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown">Cadastros</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'setores.php' ? 'active' : ''; ?>" href="setores.php">Setores</a></li>
                        <li><a class="dropdown-item" href="#">Bens/Equipamentos</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'clientes.php' ? 'active' : ''; ?>" href="clientes.php">Clientes</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'fornecedores.php' ? 'active' : ''; ?>" href="fornecedores.php">Fornecedores</a></li>
                        <?php if ($tipoUsuario === 'administrador'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">Usuários</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
            <div class="d-flex text-white align-items-center">
                <span class="me-3"><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($usuarioNome); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </div>
</nav>