<?php
$activePage = $activePage ?? '';
$userNome = $_SESSION['user_nome'] ?? 'Usuário';
$userNivel = $_SESSION['user_nivel'] ?? '';
$userIniciais = strtoupper(substr($userNome, 0, 1));
$nomePartes = explode(' ', $userNome);
if (count($nomePartes) > 1) {
    $userIniciais .= strtoupper(substr(end($nomePartes), 0, 1));
}
?>
<div class="col-md-3 col-lg-2 px-0 sidebar-panel d-none d-md-block no-print">
    <div class="sidebar-brand">
        <div class="sidebar-brand-name">
            Sant'Anna<span class="sidebar-brand-dot">.</span>
        </div>
        <div class="sidebar-brand-accent"></div>
        <span class="sidebar-brand-role"><?= ucfirst($userNivel) ?></span>
    </div>

    <div class="sidebar-profile">
        <div class="sidebar-profile-avatar"><?= $userIniciais ?></div>
        <div class="sidebar-profile-info">
            <div class="sidebar-profile-name"><?= htmlspecialchars($userNome, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="sidebar-profile-role"><?= ucfirst($userNivel) ?></div>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">
            <span class="section-line"></span>
            <span class="section-text">Financeiro</span>
            <span class="section-line"></span>
        </div>
        <a href="contas.php" class="nav-link <?= $activePage === 'contas' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Contas</span>
        </a>
        <a href="calendario.php" class="nav-link <?= $activePage === 'calendario' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Calendário</span>
        </a>
        <a href="relatorios.php" class="nav-link <?= $activePage === 'relatorios' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-pdf"></i>
            <span>Relatórios</span>
        </a>

        <div class="nav-section-title">
            <span class="section-line"></span>
            <span class="section-text">Gestão</span>
            <span class="section-line"></span>
        </div>
        <a href="cadastros.php" class="nav-link <?= $activePage === 'cadastros' ? 'active' : '' ?>">
            <i class="fa-solid fa-tags"></i>
            <span>Cadastros</span>
        </a>
        <a href="patrimonio.php" class="nav-link <?= $activePage === 'patrimonio' ? 'active' : '' ?>">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            <span>Patrimônio</span>
        </a>
        <?php if ($userNivel === 'gerente'): ?>
            <a href="usuarios.php" class="nav-link <?= $activePage === 'usuarios' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                <span>Usuários</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title">
            <span class="section-line"></span>
            <span class="section-text">Operações</span>
            <span class="section-line"></span>
        </div>
        <a href="cardapios.php" class="nav-link <?= $activePage === 'cardapios' ? 'active' : '' ?>">
            <i class="fa-solid fa-utensils"></i>
            <span>Cardápio IA</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-divider"></div>
        <a href="logout.php" class="nav-link sidebar-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Sair</span>
        </a>
    </div>
</div>
