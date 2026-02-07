<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
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
                    <a class="nav-link dropdown-toggle <?php echo in_array($paginaAtual, ['setores.php', 'clientes.php', 'fornecedores.php', 'usuarios.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown">Cadastros</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'setores.php' ? 'active' : ''; ?>" href="setores.php">Setores</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'bens.php' || $paginaAtual === 'manutencoes.php' ? 'active' : ''; ?>" href="bens.php">Bens/Equipamentos</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'clientes.php' ? 'active' : ''; ?>" href="clientes.php">Clientes</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'fornecedores.php' ? 'active' : ''; ?>" href="fornecedores.php">Fornecedores</a></li>
                        <?php if ($tipoUsuario === 'administrador'): ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item <?php echo $paginaAtual === 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">Usuários</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($paginaAtual, ['contas_pagar.php', 'contas_receber.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown">Financeiro</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'contas_pagar.php' ? 'active' : ''; ?>" href="contas_pagar.php">Contas a Pagar</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'contas_receber.php' ? 'active' : ''; ?>" href="contas_receber.php">Contas a Receber</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'calendario.php' ? 'active' : ''; ?>" href="calendario.php">Calendário</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="menu-alertas-vencimentos" onclick="carregarAlertas(true); return false;">
                        <?php
                        // exibe ícone apenas se houver alertas (fallback server-side quando JS está desabilitado)
                        require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
                        require_once __DIR__ . '/../../app/dao/ContaReceberDAO.php';
                        $rmg_pagar_alertas = (new ContaPagarDAO())->buscarVencidasEProximas(10);
                        $rmg_receber_alertas = (new ContaReceberDAO())->buscarVencidasEProximas(10);
                        $rmg_has_alertas = (!empty($rmg_pagar_alertas) || !empty($rmg_receber_alertas));
                        ?>
                        <i id="menu-alertas-icone" class="fas fa-exclamation-triangle text-warning me-1 <?php echo $rmg_has_alertas ? '' : 'd-none'; ?>" aria-hidden="<?php echo $rmg_has_alertas ? 'false' : 'true'; ?>"></i>
                        Alertas Vencimentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $paginaAtual === 'relatorios.php' ? 'active' : ''; ?>" href="relatorios.php">Relatórios</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="usuarioDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($usuarioNome); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="usuarioDropdown">
                        <li><a class="dropdown-item" href="alterar_senha.php"><i class="fas fa-key me-2"></i>Alterar Senha</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>