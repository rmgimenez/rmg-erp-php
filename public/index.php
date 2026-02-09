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

// Super admin é redirecionado para o painel admin
$loginController->verificarAcessoEmpresa();

$empresaId = $_SESSION['empresa_id'];

$contaPagarDAO = new ContaPagarDAO();
$contaReceberDAO = new ContaReceberDAO();
$pagamentoDAO = new PagamentoDAO();
$recebimentoDAO = new RecebimentoDAO();
$bemDAO = new BemDAO();
$manutencaoDAO = new ManutencaoDAO();

// Coletar Estatísticas Gerais (filtradas por empresa)
$totaisPagar = $contaPagarDAO->obterTotais($empresaId); // ['pendente' => X, 'paga' => Y]
$totaisReceber = $contaReceberDAO->obterTotais($empresaId); // ['pendente' => X, 'recebida' => Y]
$statsBens = $bemDAO->contarPorStatus($empresaId); // ['ativo' => X, 'baixado' => Y]
$totalManutencoes = $manutencaoDAO->contarTotal($empresaId);

// Totais de custo de manutenção para o dashboard
$gastoManutencao30Dias = $manutencaoDAO->somaCustoUltimos30Dias($empresaId);
$gastoManutencao12Meses = $manutencaoDAO->somaCustoUltimos12Meses($empresaId);

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
$rawPago = $pagamentoDAO->obterTotalPagoPorMesUltimos12Meses($empresaId);
$rawRecebido = $recebimentoDAO->obterTotalRecebidoPorMesUltimos12Meses($empresaId);
$rawManutencao = $manutencaoDAO->obterCustoPorMesUltimos12Meses($empresaId);

// 3. Preencher os dados
foreach ($rawPago as $row) {
    if (isset($monthsData[$row['mes']])) {
        $monthsData[$row['mes']]['pago'] = (float)$row['total'];
    }
}
foreach ($rawRecebido as $row) {
    if (isset($monthsData[$row['mes']])) {
        $monthsData[$row['mes']]['recebido'] = (float)$row['total'];
    }
}
foreach ($rawManutencao as $row) {
    if (isset($monthsData[$row['mes']])) {
        $monthsData[$row['mes']]['manutencao'] = (float)$row['total'];
    }
}

// 4. Separar em arrays para JS
$chartLabels = [];
$chartDataPago = [];
$chartDataRecebido = [];
$chartDataManutencao = [];

foreach ($monthsData as $m) {
    $chartLabels[] = $m['label'];
    $chartDataPago[] = $m['pago'];
    $chartDataRecebido[] = $m['recebido'];
    $chartDataManutencao[] = $m['manutencao'];
}

$pageTitle = 'Painel Principal - RMG ERP';
include __DIR__ . '/includes/header.php';
?>

<!-- Main Content -->
<div class="container mt-4">
    <!-- Welcome Banner -->
    <div class="dashboard-welcome mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-hand-sparkles me-2" style="opacity:0.8"></i>Olá, <?php echo htmlspecialchars($usuarioNome); ?>!</h4>
                <p>Você está acessando como <strong><?php echo ucfirst($tipoUsuario); ?></strong> na empresa <strong><?php echo htmlspecialchars($_SESSION['empresa_nome'] ?? ''); ?></strong> &mdash; <?php echo date('d/m/Y'); ?></p>
            </div>
            <div class="d-none d-md-block">
                <i class="fas fa-chart-pie" style="font-size: 2.5rem; opacity: 0.15;"></i>
            </div>
        </div>
    </div>

    <!-- Dashboard KPI Widgets -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-danger shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">A Pagar (Pendente)</h6>
                            <p class="card-text h3 mt-2 mb-0">R$ <?php echo number_format($totalPagarPendente, 2, ',', '.'); ?></p>
                        </div>
                        <i class="fas fa-file-invoice-dollar fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">A Receber (Pendente)</h6>
                            <p class="card-text h3 mt-2 mb-0">R$ <?php echo number_format($totalReceberPendente, 2, ',', '.'); ?></p>
                        </div>
                        <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Gasto Manut. (30 dias)</h6>
                            <p class="card-text h3 mt-2 mb-0">R$ <?php echo number_format($gastoManutencao30Dias, 2, ',', '.'); ?></p>
                        </div>
                        <i class="fas fa-tools fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card text-white bg-primary card-manutencao-12meses shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Gasto Manut. (12 meses)</h6>
                            <p class="card-text h3 mt-2 mb-0">R$ <?php echo number_format($gastoManutencao12Meses, 2, ',', '.'); ?></p>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-chart-line me-2" style="color:var(--rmg-primary)"></i>Evolução Financeira (12 Meses)</h5>
                </div>
                <div class="card-body">
                    <canvas id="evolucaoFinanceiraChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie me-2" style="color:var(--rmg-info)"></i>Status dos Bens</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="height: 240px; width: 100%;">
                        <canvas id="bensChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5><i class="fas fa-wrench me-2" style="color:var(--rmg-warning)"></i>Custos de Manutenção (12 Meses)</h5>
                </div>
                <div class="card-body">
                    <canvas id="evolucaoManutencaoChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5><i class="fas fa-balance-scale me-2" style="color:var(--rmg-success)"></i>Situação Atual (Pendente vs Realizado)</h5>
                </div>
                <div class="card-body">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

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

    // Common Chart.js defaults
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.elements.line.borderWidth = 2;
    Chart.defaults.elements.point.radius = 3;
    Chart.defaults.elements.point.hoverRadius = 5;

    // 1. Gráfico Evolução Financeira (Line Chart)
    const ctxEvolucao = document.getElementById('evolucaoFinanceiraChart').getContext('2d');
    new Chart(ctxEvolucao, {
        type: 'line',
        data: {
            labels: evolucaoLabels,
            datasets: [{
                    label: 'Recebido (R$)',
                    data: evolucaoRecebido,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981'
                },
                {
                    label: 'Pago (R$)',
                    data: evolucaoPago,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.06)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ef4444'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: {
                        weight: '600'
                    },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {
                                minimumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
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
                backgroundColor: 'rgba(245, 158, 11, 0.6)',
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return 'R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {
                                minimumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 3. Gráfico SITUAÇÃO ATUAL (Barra)
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
                    'rgba(245, 158, 11, 0.65)',
                    'rgba(16, 185, 129, 0.65)',
                    'rgba(239, 68, 68, 0.65)',
                    'rgba(79, 70, 229, 0.65)'
                ],
                borderColor: [
                    '#f59e0b',
                    '#10b981',
                    '#ef4444',
                    '#4f46e5'
                ],
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return 'R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {
                                minimumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    ticks: {
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
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
                    'rgba(79, 70, 229, 0.7)',
                    'rgba(148, 163, 184, 0.5)'
                ],
                borderColor: ['#4f46e5', '#94a3b8'],
                borderWidth: 2,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 16
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8
                }
            }
        }
    });
</script>
</body>

</html>