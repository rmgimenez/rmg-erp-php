<?php
$activePage = $activePage ?? '';
?>
<div class="d-md-none sidebar-mobile-header no-print">
    <div class="sidebar-mobile-brand">
        Sant'Anna<span class="sidebar-brand-dot">.</span>
    </div>
    <div class="dropdown">
        <button class="sidebar-mobile-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end sidebar-mobile-dropdown">
            <li><a class="dropdown-item" href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><hr class="dropdown-divider"></li>
            <li class="dropdown-header">Financeiro</li>
            <li><a class="dropdown-item" href="contas.php"><i class="fa-solid fa-file-invoice-dollar"></i> Contas</a></li>
            <li><a class="dropdown-item" href="calendario.php"><i class="fa-solid fa-calendar-days"></i> Calendário</a></li>
            <li><a class="dropdown-item" href="relatorios.php"><i class="fa-solid fa-file-pdf"></i> Relatórios</a></li>
            <li><hr class="dropdown-divider"></li>
            <li class="dropdown-header">Gestão</li>
            <li><a class="dropdown-item" href="cadastros.php"><i class="fa-solid fa-tags"></i> Cadastros</a></li>
            <li><a class="dropdown-item" href="patrimonio.php"><i class="fa-solid fa-screwdriver-wrench"></i> Patrimônio</a></li>
            <?php if ($_SESSION['user_nivel'] === 'gerente'): ?>
                <li><a class="dropdown-item" href="usuarios.php"><i class="fa-solid fa-users"></i> Usuários</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li class="dropdown-header">Operações</li>
            <li><a class="dropdown-item <?= $activePage === 'cardapios' ? 'active' : '' ?>" href="cardapios.php"><i class="fa-solid fa-utensils"></i> Cardápio IA</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
        </ul>
    </div>
</div>
