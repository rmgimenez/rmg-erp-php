<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="index.php"><i class="fas fa-cube me-2"></i>RMG ERP</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $paginaAtual === 'index.php' ? 'active' : ''; ?>" href="index.php"><i class="fas fa-th-large me-2" aria-hidden="true"></i>Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($paginaAtual, ['setores.php', 'clientes.php', 'fornecedores.php', 'usuarios.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-folder-open me-2" aria-hidden="true"></i>Cadastros</a>
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
                    <a class="nav-link dropdown-toggle <?php echo in_array($paginaAtual, ['contas_pagar.php', 'contas_receber.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-wallet me-2" aria-hidden="true"></i>Financeiro</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'contas_pagar.php' ? 'active' : ''; ?>" href="contas_pagar.php">Contas a Pagar</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'contas_receber.php' ? 'active' : ''; ?>" href="contas_receber.php">Contas a Receber</a></li>
                        <li><a class="dropdown-item <?php echo $paginaAtual === 'calendario.php' ? 'active' : ''; ?>" href="calendario.php">Calendário</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $paginaAtual === 'relatorios.php' ? 'active' : ''; ?>" href="relatorios.php"><i class="fas fa-chart-bar me-2" aria-hidden="true"></i>Relatórios</a>
                </li>
                <li class="nav-item">
                    <?php
                    // exibe ícone/badges server-side (fallback) com contagem separada para pagar/receber
                    require_once __DIR__ . '/../../app/dao/ContaPagarDAO.php';
                    require_once __DIR__ . '/../../app/dao/ContaReceberDAO.php';
                    $rmg_pagar_alertas = (new ContaPagarDAO())->buscarVencidasEProximas(10);
                    $rmg_receber_alertas = (new ContaReceberDAO())->buscarVencidasEProximas(10);
                    $rmg_count_pagar = count($rmg_pagar_alertas);
                    $rmg_count_receber = count($rmg_receber_alertas);
                    $rmg_has_alertas = ($rmg_count_pagar + $rmg_count_receber) > 0;
                    ?>
                    <a class="nav-link <?php echo $rmg_has_alertas ? 'menu-alerta-active' : ''; ?>" href="#" id="menu-alertas-vencimentos" onclick="carregarAlertas(true); return false;" aria-describedby="menu-alertas-badge-pagar menu-alertas-badge-receber" aria-pressed="false">
                        <span class="position-relative d-inline-block">
                            <i id="menu-alertas-icone" class="fas fa-bell fa-fw me-1 <?php echo $rmg_has_alertas ? 'text-danger menu-alerta-pulse' : 'text-secondary'; ?>" aria-hidden="<?php echo $rmg_has_alertas ? 'false' : 'true'; ?>"></i>

                            <span id="menu-alertas-badge-pagar" class="badge bg-danger rounded-pill menu-alerta-badge menu-alerta-badge-pagar <?php echo $rmg_count_pagar ? '' : 'd-none'; ?>" title="Contas a pagar" aria-hidden="<?php echo $rmg_count_pagar ? 'false' : 'true'; ?>"><?php echo $rmg_count_pagar ?: ''; ?></span>

                            <span id="menu-alertas-badge-receber" class="badge bg-primary rounded-pill menu-alerta-badge menu-alerta-badge-receber <?php echo $rmg_count_receber ? '' : 'd-none'; ?>" title="Contas a receber" aria-hidden="<?php echo $rmg_count_receber ? 'false' : 'true'; ?>"><?php echo $rmg_count_receber ?: ''; ?></span>
                        </span>
                        <strong class="ms-1">Alertas Vencimentos</strong>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" id="usuarioDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-2" style="width:28px;height:28px;background:rgba(79,70,229,0.4);font-size:0.75rem;font-weight:600;">
                            <?php echo strtoupper(substr($usuarioNome, 0, 1)); ?>
                        </span>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($usuarioNome); ?></span>
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