<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../app/dao/ContaReceberDAO.php';
require_once __DIR__ . '/../app/dao/PagamentoDAO.php';
require_once __DIR__ . '/../app/dao/RecebimentoDAO.php';
require_once __DIR__ . '/../app/dao/BemDAO.php';
require_once __DIR__ . '/../app/dao/ManutencaoDAO.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$contaPagarDAO = new ContaPagarDAO();
$contaReceberDAO = new ContaReceberDAO();
$pagamentoDAO = new PagamentoDAO();
$recebimentoDAO = new RecebimentoDAO();
$bemDAO = new BemDAO();
$manutencaoDAO = new ManutencaoDAO();

// Coletar Estatísticas Gerais
$totaisPagar = $contaPagarDAO->obterTotais(); // ['pendente' => X, 'paga' => Y]
$totaisReceber = $contaReceberDAO->obterTotais(); // ['pendente' => X, 'recebida' => Y]
$statsBens = $bemDAO->contarPorStatus(); // ['ativo' => X, 'baixado' => Y]
$totalManutencoes = $manutencaoDAO->contarTotal();

// Totais de custo de manutenção para o dashboard
$gastoManutencao30Dias = $manutencaoDAO->somaCustoUltimos30Dias();
$gastoManutencao12Meses = $manutencaoDAO->somaCustoUltimos12Meses();

$totalPagarPendente = $totaisPagar['pendente'] ?? 0;
$totalReceberPendente = $totaisReceber['pendente'] ?? 0;
$bensAtivos = $statsBens['ativo'] ?? 0;

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';

// --- Preparação dos dados para Gráficos de Evolução (Últimos 12 meses) ---

// 1. Inicializar estrutura dos últimos 12 meses
$monthsData = [];
for ($i = 11; $i >= 0; $i--) {
    $date = new DateTime();
    $date->modify("-$i months");
    $key = $date->format('Y-m');
    $label = $date->format('m/Y');
    
    $monthsData[$key] = [
        'label' => $label,
        'pago' => 0,
        'recebido' => 0,
        'manutencao' => 0
    ];
}

// 2. Buscar dados do banco
$rawPago = $pagamentoDAO->obterTotalPagoPorMesUltimos12Meses();
$rawRecebido = $recebimentoDAO->obterTotalRecebidoPorMesUltimos12Meses();
$rawManutencao = $manutencaoDAO->obterCustoPorMesUltimos12Meses();

// 3. Preencher os dados
foreach($rawPago as $row) {
    if(isset($monthsData[$row['mes']])) {
        $monthsData[$row['mes']]['pago'] = (float)$row['total'];
    }
}
foreach($rawRecebido as $row) {
    if(isset($monthsData[$row['mes']])) {
        $monthsData[$row['mes']]['recebido'] = (float)$row['total'];
    }
}
foreach($rawManutencao as $row) {
    if(isset($monthsData[$row['mes']])) {
        $monthsData[$row['mes']]['manutencao'] = (float)$row['total'];
    }
}

// 4. Separar em arrays para JS
$chartLabels = [];
$chartDataPago = [];
$chartDataRecebido = [];
$chartDataManutencao = [];

foreach($monthsData as $m) {
    $chartLabels[] = $m['label'];
    $chartDataPago[] = $m['pago'];
    $chartDataRecebido[] = $m['recebido'];
    $chartDataManutencao[] = $m['manutencao'];
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Principal - RMG ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

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
        
        <!-- Dashboard Widgets -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">A Pagar (Pendente)</h6>
                                <p class="card-text h3 mt-2">R$ <?php echo number_format($totalPagarPendente, 2, ',', '.'); ?></p>
                            </div>
                            <i class="fas fa-file-invoice-dollar fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">A Receber (Pendente)</h6>
                                <p class="card-text h3 mt-2">R$ <?php echo number_format($totalReceberPendente, 2, ',', '.'); ?></p>
                            </div>
                            <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Gasto Manut. (30 dias)</h6>
                                <p class="card-text h3 mt-2">R$ <?php echo number_format($gastoManutencao30Dias, 2, ',', '.'); ?></p>
                            </div>
                            <i class="fas fa-tools fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Gasto Manut. (12 meses)</h6>
                                <p class="card-text h3 mt-2">R$ <?php echo number_format($gastoManutencao12Meses, 2, ',', '.'); ?></p>
                            </div>
                            <i class="fas fa-chart-line fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Resumo Financeiro Geral e Status dos Bens -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Evolução Financeira (Últimos 12 Meses)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="evolucaoFinanceiraChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status dos Bens</h5>
                    </div>
                    <div class="card-body">
                        <div style="height: 250px; display: flex; justify-content: center;">
                            <canvas id="bensChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Evolução de Manutenção e Resumo Atual -->
        <div class="row mt-4 mb-5">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Custos de Manutenção (Últimos 12 Meses)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="evolucaoManutencaoChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Situação Atual (Pendente vs Realizado)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="financeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Check session flag to show modal on login
        <?php if (isset($_SESSION['mostrar_alertas']) && $_SESSION['mostrar_alertas']): ?>
            $(document).ready(function() {
                carregarAlertas();
                <?php unset($_SESSION['mostrar_alertas']); ?>
            });
        <?php endif; ?>

        // Dados vindos do PHP - Totais Gerais
        const dadosFinanceiros = {
            pagarPendente: <?php echo $totalPagarPendente; ?>,
            pagarPago: <?php echo $totaisPagar['paga'] ?? 0; ?>,
            receberPendente: <?php echo $totalReceberPendente; ?>,
            receberRecebido: <?php echo $totaisReceber['recebida'] ?? 0; ?>
        };

        const dadosBens = {
            ativos: <?php echo $bensAtivos; ?>,
            baixados: <?php echo $statsBens['baixado'] ?? 0; ?>
        };

        // Dados Evolutivos
        const evolucaoLabels = <?php echo json_encode($chartLabels); ?>;
        const evolucaoPago = <?php echo json_encode($chartDataPago); ?>;
        const evolucaoRecebido = <?php echo json_encode($chartDataRecebido); ?>;
        const evolucaoManutencao = <?php echo json_encode($chartDataManutencao); ?>;

        // 1. Gráfico Evolução Financeira (Line Chart)
        const ctxEvolucao = document.getElementById('evolucaoFinanceiraChart').getContext('2d');
        new Chart(ctxEvolucao, {
            type: 'line',
            data: {
                labels: evolucaoLabels,
                datasets: [
                    {
                        label: 'Recebido (R$)',
                        data: evolucaoRecebido,
                        borderColor: '#198754', // Success green
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Pago (R$)',
                        data: evolucaoPago,
                        borderColor: '#dc3545', // Danger red
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 2. Gráfico Evolução Manutenção (Bar Chart)
        const ctxManutencao = document.getElementById('evolucaoManutencaoChart').getContext('2d');
        new Chart(ctxManutencao, {
            type: 'bar',
            data: {
                labels: evolucaoLabels,
                datasets: [{
                    label: 'Custo Manutenção (R$)',
                    data: evolucaoManutencao,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)', // Warning yellow
                    borderColor: 'rgb(255, 193, 7)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 3. Gráfico SITUAÇÃO ATUAL (Barra - o original)
        const ctxFinance = document.getElementById('financeChart').getContext('2d');
        new Chart(ctxFinance, {
            type: 'bar',
            data: {
                labels: ['A Receber (Pend.)', 'Recebido (Total)', 'A Pagar (Pend.)', 'Pago (Total)'],
                datasets: [{
                    label: 'Valores ACUMULADOS (R$)',
                    data: [
                        dadosFinanceiros.receberPendente,
                        dadosFinanceiros.receberRecebido,
                        dadosFinanceiros.pagarPendente,
                        dadosFinanceiros.pagarPago
                    ],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(13, 202, 240, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 193, 7, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(13, 202, 240, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 4. Gráfico Bens (Doughnut)
        const ctxBens = document.getElementById('bensChart').getContext('2d');
        new Chart(ctxBens, {
            type: 'doughnut',
            data: {
                labels: ['Ativos', 'Baixados'],
                datasets: [{
                    data: [dadosBens.ativos, dadosBens.baixados],
                    backgroundColor: [
                        'rgba(13, 202, 240, 0.7)', // Info
                        'rgba(108, 117, 125, 0.7)' // Secondary
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
