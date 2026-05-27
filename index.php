<?php
/**
 * Painel Financeiro (Dashboard Principal)
 * Renderiza os KPIs consolidados e os gráficos interativos.
 */

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/AccountModel.php';

use CantinaFinanceiro\Database;
use CantinaFinanceiro\Auth;
use CantinaFinanceiro\AccountModel;

Auth::checkAuth();
Auth::restrictTo(['gerente', 'operador']);

$db = Database::getConnection();
$kpis = AccountModel::getKPIs();

// ----------------------------------------------------
// Processamento de Dados para o Gráfico de Fluxo de Caixa (Últimos 6 meses)
// ----------------------------------------------------
$mesesNome = [
    '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
    '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
];

$fluxoChart = ['labels' => [], 'receitas' => [], 'despesas' => []];

// Monta lista dos últimos 6 meses cronológicos
for ($i = 5; $i >= 0; $i--) {
    $anoMes = date('Y-m', strtotime("-$i months"));
    $ano = substr($anoMes, 0, 4);
    $mes = substr($anoMes, 5, 2);
    
    $label = $mesesNome[$mes] . '/' . substr($ano, 2, 2);
    $fluxoChart['labels'][] = $label;
    
    // Consulta receitas liquidadas no respectivo mês
    $stmtR = $db->prepare("SELECT SUM(valor) FROM contas WHERE status = 'pago' AND tipo = 'receber' AND strftime('%Y-%m', data_liquidacao) = ?");
    $stmtR->execute([$anoMes]);
    $fluxoChart['receitas'][] = round((int)$stmtR->fetchColumn() / 100, 2);

    // Consulta despesas liquidadas no respectivo mês
    $stmtD = $db->prepare("SELECT SUM(valor) FROM contas WHERE status = 'pago' AND tipo = 'pagar' AND strftime('%Y-%m', data_liquidacao) = ?");
    $stmtD->execute([$anoMes]);
    $fluxoChart['despesas'][] = round((int)$stmtD->fetchColumn() / 100, 2);
}

// ----------------------------------------------------
// Processamento de Dados para o Gráfico de Categorias (Despesas Pagas)
// ----------------------------------------------------
$categoriaChart = ['labels' => [], 'valores' => []];
$stmtCat = $db->query("SELECT COALESCE(cat.nome, 'Não Informado') as categoria_nome, SUM(c.valor) as total 
                       FROM contas c 
                       LEFT JOIN categorias cat ON c.categoria_id = cat.id 
                       WHERE c.status = 'pago' AND c.tipo = 'pagar' 
                       GROUP BY c.categoria_id");
$categoriasResult = $stmtCat->fetchAll();

if (!empty($categoriasResult)) {
    foreach ($categoriasResult as $cat) {
        $categoriaChart['labels'][] = $cat['categoria_nome'];
        $categoriaChart['valores'][] = round($cat['total'] / 100, 2);
    }
} else {
    // Fallback estético caso o banco esteja vazio
    $categoriaChart['labels'] = ['Sem Lançamentos'];
    $categoriaChart['valores'] = [0];
}

// ----------------------------------------------------
// Consulta Contas Vencidas para Exibição Rápida
// ----------------------------------------------------
$hoje = date('Y-m-d');
$stmtVencidas = $db->prepare("SELECT * FROM contas WHERE status = 'pendente' AND data_vencimento < ? ORDER BY data_vencimento ASC LIMIT 5");
$stmtVencidas->execute([$hoje]);
$vencidasList = $stmtVencidas->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Financeiro - Cantina Sant'Anna</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 px-0 sidebar-panel d-none d-md-block">
            <div class="py-4 text-center border-bottom border-secondary border-opacity-25">
                <div style="font-size: 1.6rem; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">
                    Sant'Anna<span style="color: #6366f1;">.</span>
                </div>
                <span class="badge bg-light text-dark text-opacity-75 small px-3 py-1 rounded-pill mt-1">
                    <?= ucfirst($_SESSION['user_nivel']) ?>
                </span>
            </div>
            
            <div class="mt-4">
                <a href="index.php" class="nav-link active">
                    <i class="fa-solid fa-chart-line me-2"></i> Dashboard
                </a>
                <a href="contas.php" class="nav-link">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i> Contas
                </a>
                <a href="calendario.php" class="nav-link">
                    <i class="fa-solid fa-calendar-days me-2"></i> Calendário
                </a>
                <a href="relatorios.php" class="nav-link">
                    <i class="fa-solid fa-file-pdf me-2"></i> Relatórios
                </a>
                <a href="cadastros.php" class="nav-link">
                    <i class="fa-solid fa-tags me-2"></i> Cadastros
                </a>
                <a href="patrimonio.php" class="nav-link">
                    <i class="fa-solid fa-screwdriver-wrench me-2"></i> Patrimônio
                </a>
                <?php if ($_SESSION['user_nivel'] === 'gerente'): ?>
                    <a href="usuarios.php" class="nav-link">
                        <i class="fa-solid fa-users me-2"></i> Usuários
                    </a>
                <?php endif; ?>
                <!-- Novo link adicionado -->
                <a href="cardapios.php" class="nav-link">
                    <i class="fa-solid fa-utensils me-2"></i> Cardápio IA
                </a>
                <div class="border-top border-secondary border-opacity-25 my-4 mx-3"></div>
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Sair
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 py-4 px-md-4">
            <!-- Mobile Header -->
            <div class="d-md-none d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 shadow-sm">
                <div style="font-size: 1.3rem; font-weight: 700; color: #1e1b4b;">
                    Sant'Anna<span style="color: var(--primary);">.</span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                        Menu
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                        <li><a class="dropdown-menu-item nav-link p-2" href="index.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="contas.php"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Contas</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="calendario.php"><i class="fa-solid fa-calendar-days me-2"></i> Calendário</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="relatorios.php"><i class="fa-solid fa-file-pdf me-2"></i> Relatórios</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="cadastros.php"><i class="fa-solid fa-tags me-2"></i> Cadastros</a></li>
                        <li><a class="dropdown-menu-item nav-link p-2" href="patrimonio.php"><i class="fa-solid fa-screwdriver-wrench me-2"></i> Patrimônio</a></li>
                        <?php if ($_SESSION['user_nivel'] === 'gerente'): ?>
                            <li><a class="dropdown-menu-item nav-link p-2" href="usuarios.php"><i class="fa-solid fa-users me-2"></i> Usuários</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-menu-item nav-link p-2" href="cardapios.php"><i class="fa-solid fa-utensils me-2"></i> Cardápio IA</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-menu-item nav-link p-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Sair</a></li>
                    </ul>
                </div>
            </div>

            <!-- Page Header -->
            <div class="d-none d-md-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-0">Painel Geral</h1>
                    <p class="text-muted small mb-0">Olá, <strong><?= htmlspecialchars($_SESSION['user_nome'], ENT_QUOTES, 'UTF-8') ?></strong>! Acompanhe as finanças da Cantina Sant'Anna.</p>
                </div>
                <div class="text-end">
                    <span class="text-muted small"><?= date('d/m/Y') ?></span>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="row g-3 mb-4">
                <!-- Saldo Efetivo -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 kpi-border-success">
                        <div class="kpi-title">Saldo Efetivo (Pago)</div>
                        <div class="kpi-value text-success">
                            R$ <?= number_format($kpis['saldo_efetivo'] / 100, 2, ',', '.') ?>
                        </div>
                    </div>
                </div>
                
                <!-- Saldo Previsto -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 kpi-border-info">
                        <div class="kpi-title">Saldo Previsto (Total Mês)</div>
                        <div class="kpi-value text-info">
                            R$ <?= number_format($kpis['saldo_previsto'] / 100, 2, ',', '.') ?>
                        </div>
                    </div>
                </div>

                <!-- Pendente a Receber -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 kpi-border-warning">
                        <div class="kpi-title">A Receber Pendente</div>
                        <div class="kpi-value text-warning">
                            R$ <?= number_format($kpis['pendente_receber'] / 100, 2, ',', '.') ?>
                        </div>
                    </div>
                </div>

                <!-- Contas Vencidas -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-glass p-3 <?= $kpis['vencidas'] > 0 ? 'badge-pulse-danger' : 'kpi-border-danger bg-white' ?>">
                        <div class="kpi-title <?= $kpis['vencidas'] > 0 ? 'text-white text-opacity-75' : '' ?>">Contas Vencidas</div>
                        <div class="kpi-value <?= $kpis['vencidas'] > 0 ? 'text-white' : 'text-danger' ?>">
                            R$ <?= number_format($kpis['vencidas'] / 100, 2, ',', '.') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Quick Reports Grid -->
            <div class="dashboard-grid mb-4">
                <!-- Chart Col -->
                <div class="chart-container card-glass p-4" style="min-height: 380px; position: relative;">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-bar text-primary me-2"></i> Fluxo de Caixa Mensal</h5>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                </div>

                <!-- Pie Chart Col -->
                <div class="chart-container card-glass p-4" style="min-height: 380px; position: relative;">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Despesas Pagas por Categoria</h5>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Vencidas Alert Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-glass p-4">
                        <h5 class="fw-bold mb-3 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> Contas Vencidas / Alerta Rápido</h5>
                        <div class="table-responsive">
                            <table class="table table-premium mb-0">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Vencimento</th>
                                        <th>Categoria</th>
                                        <th>Fluxo</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vencidasList)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-circle-check text-success fs-3 mb-2 d-block"></i>
                                                Tudo em ordem! Nenhuma conta em atraso encontrada.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vencidasList as $v): ?>
                                            <tr>
                                                <td class="fw-medium"><?= htmlspecialchars($v['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-muted"><?= date('d/m/Y', strtotime($v['data_vencimento'])) ?></td>
                                                <td><span class="badge bg-light text-secondary"><?= htmlspecialchars($v['categoria'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                                <td>
                                                    <?php if ($v['tipo'] === 'pagar'): ?>
                                                        <span class="custom-badge badge-pulse-danger py-1 px-2"><i class="fa-solid fa-arrow-down"></i> Pagar</span>
                                                    <?php else: ?>
                                                        <span class="custom-badge badge-pago py-1 px-2"><i class="fa-solid fa-arrow-up"></i> Receber</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end fw-bold">
                                                    R$ <?= number_format($v['valor'] / 100, 2, ',', '.') ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="contas.php?busca=<?= urlencode($v['descricao']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1">
                                                        <i class="fa-solid fa-magnifying-glass"></i> Visualizar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/dashboard.js"></script>
<script>
    // Inicializa os gráficos usando as variáveis PHP codificadas em JSON
    const flowData = <?= json_encode($fluxoChart) ?>;
    const catData = <?= json_encode($categoriaChart) ?>;
    initDashboardCharts(flowData, catData);
</script>
</body>
</html>
