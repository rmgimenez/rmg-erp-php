<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../app/dao/ContaReceberDAO.php';
require_once __DIR__ . '/../app/dao/PagamentoDAO.php';
require_once __DIR__ . '/../app/dao/RecebimentoDAO.php';
require_once __DIR__ . '/../app/dao/BemDAO.php';
require_once __DIR__ . '/../app/dao/ManutencaoDAO.php';
require_once __DIR__ . '/../app/dao/FornecedorDAO.php';

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

// ========================================
// DADOS PARA KPIs PRINCIPAIS
// ========================================
$totaisPagar = $contaPagarDAO->obterTotais($empresaId);
$totaisReceber = $contaReceberDAO->obterTotais($empresaId);
$statsBens = $bemDAO->contarPorStatus($empresaId);
$totalManutencoes = $manutencaoDAO->contarTotal($empresaId);

$totalPagarPendente = $totaisPagar['pendente'] ?? 0;
$totalReceberPendente = $totaisReceber['pendente'] ?? 0;
$totalPago = $totaisPagar['paga'] ?? 0;
$totalRecebido = $totaisReceber['recebida'] ?? 0;
$bensAtivos = $statsBens['ativo'] ?? 0;
$bensBaixados = $statsBens['baixado'] ?? 0;

// ========================================
// DADOS ADICIONAIS
// ========================================
$gastoManutencao30Dias = $manutencaoDAO->somaCustoUltimos30Dias($empresaId);
$gastoManutencao12Meses = $manutencaoDAO->somaCustoUltimos12Meses($empresaId);

// Contas vencidas
$contasPagarVencidas = $contaPagarDAO->contarVencidas($empresaId);
$valorPagarVencido = $contaPagarDAO->somaVencidas($empresaId);
$contasReceberVencidas = $contaReceberDAO->contarVencidas($empresaId);
$valorReceberVencido = $contaReceberDAO->somaVencidas($empresaId);

// Mês atual
$pagoMesAtual = $pagamentoDAO->obterTotalPagoMesAtual($empresaId);
$pagoMesAnterior = $pagamentoDAO->obterTotalPagoMesAnterior($empresaId);
$recebidoMesAtual = $recebimentoDAO->obterTotalRecebidoMesAtual($empresaId);
$recebidoMesAnterior = $recebimentoDAO->obterTotalRecebidoMesAnterior($empresaId);

// Vencimentos do mês
$vencePagarMes = $contaPagarDAO->somaVencimentoMesAtual($empresaId);
$venceReceberMes = $contaReceberDAO->somaVencimentoMesAtual($empresaId);

// Saldo líquido
$saldoLiquido = $totalReceberPendente - $totalPagarPendente;
$saldoMesAtual = $recebidoMesAtual - $pagoMesAtual;

// Patrimônio
$valorPatrimonio = $bemDAO->somaValorAquisicaoAtivos($empresaId);

// Top bens e fornecedores
$topBens = $bemDAO->obterTopManutencao($empresaId, 5);
$topFornecedores = $pagamentoDAO->obterTopFornecedores($empresaId, 5);
$bensPorSetor = $bemDAO->contarPorSetor($empresaId);

// Próximas contas
$proximasPagar = $contaPagarDAO->buscarProximasVencer($empresaId, 10, 8);
$proximasReceber = $contaReceberDAO->buscarProximasVencer($empresaId, 10, 8);

// Últimas manutenções
$ultimasManutencoes = $manutencaoDAO->buscarUltimas($empresaId, 5);

// Variação mês a mês (%)
$varPago = $pagoMesAnterior > 0 ? (($pagoMesAtual - $pagoMesAnterior) / $pagoMesAnterior * 100) : 0;
$varRecebido = $recebidoMesAnterior > 0 ? (($recebidoMesAtual - $recebidoMesAnterior) / $recebidoMesAnterior * 100) : 0;

// ========================================
// DADOS PARA GRÁFICOS DE EVOLUÇÃO (12 meses)
// ========================================
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

$rawPago = $pagamentoDAO->obterTotalPagoPorMesUltimos12Meses($empresaId);
$rawRecebido = $recebimentoDAO->obterTotalRecebidoPorMesUltimos12Meses($empresaId);
$rawManutencao = $manutencaoDAO->obterCustoPorMesUltimos12Meses($empresaId);

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

$chartLabels = [];
$chartDataPago = [];
$chartDataRecebido = [];
$chartDataManutencao = [];
$chartDataSaldo = [];

foreach ($monthsData as $m) {
    $chartLabels[] = $m['label'];
    $chartDataPago[] = $m['pago'];
    $chartDataRecebido[] = $m['recebido'];
    $chartDataManutencao[] = $m['manutencao'];
    $chartDataSaldo[] = $m['recebido'] - $m['pago'];
}

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';

// Contadores pendentes
$qtdPagarPendentes = $contaPagarDAO->contarPendentes($empresaId);

// Helper para formatação
function formatarMoeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function badgeVariacao($valor, $invertido = false)
{
    if ($valor == 0) return '<span class="badge bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-minus me-1"></i>0%</span>';
    $positivo = $invertido ? $valor < 0 : $valor > 0;
    if ($positivo) return '<span class="badge bg-success bg-opacity-10 text-success"><i class="fas fa-arrow-up me-1"></i>' . number_format(abs($valor), 1, ',', '.') . '%</span>';
    return '<span class="badge bg-danger bg-opacity-10 text-danger"><i class="fas fa-arrow-down me-1"></i>' . number_format(abs($valor), 1, ',', '.') . '%</span>';
}

$pageTitle = 'Painel Principal - RMG ERP';
include __DIR__ . '/includes/header.php';
?>

<!-- Main Content -->
<div class="container-fluid px-4 mt-4">
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

    <?php if ($contasPagarVencidas > 0 || $contasReceberVencidas > 0): ?>
        <!-- Alerta de Contas Vencidas -->
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert" style="border-radius: var(--rmg-radius); border-left: 4px solid #ef4444;">
            <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
            <div>
                <strong>Atenção!</strong> Você tem
                <?php if ($contasPagarVencidas > 0): ?>
                    <strong><?php echo $contasPagarVencidas; ?></strong> conta(s) a pagar vencida(s) (<?php echo formatarMoeda($valorPagarVencido); ?>)
                <?php endif; ?>
                <?php if ($contasPagarVencidas > 0 && $contasReceberVencidas > 0): ?> e <?php endif; ?>
                <?php if ($contasReceberVencidas > 0): ?>
                    <strong><?php echo $contasReceberVencidas; ?></strong> conta(s) a receber vencida(s) (<?php echo formatarMoeda($valorReceberVencido); ?>)
                <?php endif; ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ================================ -->
    <!-- LINHA 1: KPIs PRINCIPAIS (6 cards) -->
    <!-- ================================ -->
    <div class="row g-3 mb-4">
        <!-- Saldo Líquido Pendente -->
        <div class="col-sm-6 col-xl-2">
            <div class="card dashboard-kpi-card shadow-sm h-100 <?php echo $saldoLiquido >= 0 ? 'border-start-success' : 'border-start-danger'; ?>">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="kpi-icon <?php echo $saldoLiquido >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <span class="kpi-label ms-2">Saldo Pendente</span>
                    </div>
                    <div class="kpi-value <?php echo $saldoLiquido >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatarMoeda($saldoLiquido); ?>
                    </div>
                    <small class="text-muted">Receber - Pagar</small>
                </div>
            </div>
        </div>

        <!-- A Pagar Pendente -->
        <div class="col-sm-6 col-xl-2">
            <div class="card dashboard-kpi-card shadow-sm h-100 border-start-danger">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="kpi-icon bg-danger-subtle text-danger">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <span class="kpi-label ms-2">A Pagar</span>
                    </div>
                    <div class="kpi-value text-danger"><?php echo formatarMoeda($totalPagarPendente); ?></div>
                    <small class="text-muted"><?php echo $qtdPagarPendentes; ?> conta(s) pendente(s)</small>
                </div>
            </div>
        </div>

        <!-- A Receber Pendente -->
        <div class="col-sm-6 col-xl-2">
            <div class="card dashboard-kpi-card shadow-sm h-100 border-start-success">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="kpi-icon bg-success-subtle text-success">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <span class="kpi-label ms-2">A Receber</span>
                    </div>
                    <div class="kpi-value text-success"><?php echo formatarMoeda($totalReceberPendente); ?></div>
                    <small class="text-muted">Pendente total</small>
                </div>
            </div>
        </div>

        <!-- Saldo Mês Atual -->
        <div class="col-sm-6 col-xl-2">
            <div class="card dashboard-kpi-card shadow-sm h-100 <?php echo $saldoMesAtual >= 0 ? 'border-start-info' : 'border-start-warning'; ?>">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="kpi-icon <?php echo $saldoMesAtual >= 0 ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning'; ?>">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="kpi-label ms-2">Saldo do Mês</span>
                    </div>
                    <div class="kpi-value <?php echo $saldoMesAtual >= 0 ? 'text-info' : 'text-warning'; ?>">
                        <?php echo formatarMoeda($saldoMesAtual); ?>
                    </div>
                    <small class="text-muted">Recebido - Pago (mês atual)</small>
                </div>
            </div>
        </div>

        <!-- Patrimônio (Bens Ativos) -->
        <div class="col-sm-6 col-xl-2">
            <div class="card dashboard-kpi-card shadow-sm h-100 border-start-primary">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="kpi-icon bg-primary-subtle text-primary">
                            <i class="fas fa-building"></i>
                        </div>
                        <span class="kpi-label ms-2">Patrimônio</span>
                    </div>
                    <div class="kpi-value text-primary"><?php echo formatarMoeda($valorPatrimonio); ?></div>
                    <small class="text-muted"><?php echo $bensAtivos; ?> ben(s) ativo(s)</small>
                </div>
            </div>
        </div>

        <!-- Manutenção -->
        <div class="col-sm-6 col-xl-2">
            <div class="card dashboard-kpi-card shadow-sm h-100 border-start-warning">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="kpi-icon bg-warning-subtle text-warning">
                            <i class="fas fa-tools"></i>
                        </div>
                        <span class="kpi-label ms-2">Manutenção</span>
                    </div>
                    <div class="kpi-value text-warning"><?php echo formatarMoeda($gastoManutencao30Dias); ?></div>
                    <small class="text-muted">Últimos 30 dias</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================ -->
    <!-- LINHA 2: RESUMO DO MÊS (cards comparativos) -->
    <!-- ================================ -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold text-uppercase">Pago este mês</p>
                            <h4 class="mb-1 fw-bold"><?php echo formatarMoeda($pagoMesAtual); ?></h4>
                        </div>
                        <?php echo badgeVariacao($varPago, true); ?>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Mês anterior: <?php echo formatarMoeda($pagoMesAnterior); ?></small>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-danger" style="width: <?php echo $pagoMesAnterior > 0 ? min(100, ($pagoMesAtual / max($pagoMesAnterior, 1)) * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold text-uppercase">Recebido este mês</p>
                            <h4 class="mb-1 fw-bold"><?php echo formatarMoeda($recebidoMesAtual); ?></h4>
                        </div>
                        <?php echo badgeVariacao($varRecebido); ?>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Mês anterior: <?php echo formatarMoeda($recebidoMesAnterior); ?></small>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $recebidoMesAnterior > 0 ? min(100, ($recebidoMesAtual / max($recebidoMesAnterior, 1)) * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small fw-semibold text-uppercase">Vence este mês (Pagar)</p>
                    <h4 class="mb-1 fw-bold text-danger"><?php echo formatarMoeda($vencePagarMes); ?></h4>
                    <div class="mt-2">
                        <small class="text-muted">
                            <?php if ($contasPagarVencidas > 0): ?>
                                <span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i><?php echo $contasPagarVencidas; ?> vencida(s)</span>
                            <?php else: ?>
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>Nenhuma vencida</span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small fw-semibold text-uppercase">Vence este mês (Receber)</p>
                    <h4 class="mb-1 fw-bold text-success"><?php echo formatarMoeda($venceReceberMes); ?></h4>
                    <div class="mt-2">
                        <small class="text-muted">
                            <?php if ($contasReceberVencidas > 0): ?>
                                <span class="text-warning fw-bold"><i class="fas fa-clock me-1"></i><?php echo $contasReceberVencidas; ?> em atraso</span>
                            <?php else: ?>
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>Tudo em dia</span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================ -->
    <!-- LINHA 3: TABELAS DE PRÓXIMAS CONTAS -->
    <!-- ================================ -->
    <div class="row g-3 mb-4">
        <!-- Próximas Contas a Pagar -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2 text-danger"></i>Contas a Pagar — Próximas / Vencidas</h5>
                    <a href="contas_pagar.php" class="btn btn-sm btn-outline-danger">Ver Todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($proximasPagar) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-dashboard">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Fornecedor</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-center">Vencimento</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proximasPagar as $cp): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars(mb_strimwidth($cp['descricao'], 0, 30, '...')); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($cp['nome_fornecedor'] ?? '—'); ?></small></td>
                                            <td class="text-end fw-bold"><?php echo formatarMoeda($cp['valor']); ?></td>
                                            <td class="text-center">
                                                <?php echo date('d/m/Y', strtotime($cp['data_vencimento'])); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $diasRest = (int)$cp['dias_restantes'];
                                                if ($diasRest < 0): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Vencida (<?php echo abs($diasRest); ?>d)</span>
                                                <?php elseif ($diasRest == 0): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Hoje</span>
                                                <?php elseif ($diasRest <= 3): ?>
                                                    <span class="badge bg-warning text-dark"><?php echo $diasRest; ?> dia(s)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info"><?php echo $diasRest; ?> dias</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3" style="opacity:0.3"></i>
                            <p class="text-muted">Nenhuma conta a pagar próxima do vencimento.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Próximas Contas a Receber -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-usd me-2 text-success"></i>Contas a Receber — Próximas / Vencidas</h5>
                    <a href="contas_receber.php" class="btn btn-sm btn-outline-success">Ver Todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($proximasReceber) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-dashboard">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-center">Vencimento</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proximasReceber as $cr): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars(mb_strimwidth($cr['descricao'], 0, 30, '...')); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($cr['nome_cliente'] ?? '—'); ?></small></td>
                                            <td class="text-end fw-bold"><?php echo formatarMoeda($cr['valor']); ?></td>
                                            <td class="text-center">
                                                <?php echo date('d/m/Y', strtotime($cr['data_vencimento'])); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $diasRest = (int)$cr['dias_restantes'];
                                                if ($diasRest < 0): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Vencida (<?php echo abs($diasRest); ?>d)</span>
                                                <?php elseif ($diasRest == 0): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Hoje</span>
                                                <?php elseif ($diasRest <= 3): ?>
                                                    <span class="badge bg-warning text-dark"><?php echo $diasRest; ?> dia(s)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info"><?php echo $diasRest; ?> dias</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3" style="opacity:0.3"></i>
                            <p class="text-muted">Nenhuma conta a receber próxima do vencimento.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================ -->
    <!-- LINHA 4: GRÁFICOS PRINCIPAIS -->
    <!-- ================================ -->
    <div class="row g-3 mb-4">
        <!-- Fluxo de Caixa (Evolução 12 meses) -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2" style="color:var(--rmg-primary)"></i>Fluxo de Caixa — Últimos 12 Meses</h5>
                </div>
                <div class="card-body">
                    <canvas id="fluxoCaixaChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <!-- Saldo Mensal -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2" style="color:var(--rmg-info)"></i>Saldo Mensal (12 Meses)</h5>
                </div>
                <div class="card-body">
                    <canvas id="saldoMensalChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================ -->
    <!-- LINHA 5: GRÁFICOS SECUNDÁRIOS -->
    <!-- ================================ -->
    <div class="row g-3 mb-4">
        <!-- Custos de Manutenção -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-wrench me-2" style="color:var(--rmg-warning)"></i>Manutenção (12 Meses)</h5>
                </div>
                <div class="card-body">
                    <canvas id="evolucaoManutencaoChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Top Fornecedores -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-truck me-2" style="color:var(--rmg-danger)"></i>Top Fornecedores (12 Meses)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($topFornecedores) > 0): ?>
                        <canvas id="topFornecedoresChart"></canvas>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-truck fa-3x text-muted mb-3" style="opacity:0.2"></i>
                            <p class="text-muted">Nenhum pagamento registrado nos últimos 12 meses.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Distribuição de Bens por Setor -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-th-large me-2" style="color:var(--rmg-primary)"></i>Bens por Setor</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <?php if (count($bensPorSetor) > 0): ?>
                        <div style="height: 260px; width: 100%;">
                            <canvas id="bensSetorChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-th-large fa-3x text-muted mb-3" style="opacity:0.2"></i>
                            <p class="text-muted">Nenhum bem ativo cadastrado.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================ -->
    <!-- LINHA 6: TOP BENS MANUTENÇÃO + STATUS BENS -->
    <!-- ================================ -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tools me-2" style="color:var(--rmg-warning)"></i>Top 5 Bens: Maior Custo de Manutenção</h5>
                    <small class="text-muted">Manutenção vs Aquisição</small>
                </div>
                <div class="card-body">
                    <?php if (count($topBens) > 0): ?>
                        <canvas id="topBensChart"></canvas>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tools fa-3x text-muted mb-3" style="opacity:0.2"></i>
                            <p class="text-muted">Nenhuma manutenção registrada.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2" style="color:var(--rmg-info)"></i>Status dos Bens</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="height: 240px; width: 100%;">
                        <canvas id="bensStatusChart"></canvas>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-center pb-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-circle me-1"></i>Ativos: <?php echo $bensAtivos; ?></span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-circle me-1"></i>Baixados: <?php echo $bensBaixados; ?></span>
                </div>
            </div>
        </div>
    </div>



    <!-- ================================ -->
    <!-- LINHA 7: ÚLTIMAS MANUTENÇÕES + AÇÕES RÁPIDAS -->
    <!-- ================================ -->
    <div class="row g-3 mb-4">
        <!-- Últimas Manutenções -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2" style="color:var(--rmg-warning)"></i>Últimas Manutenções Realizadas</h5>
                    <a href="manutencoes.php" class="btn btn-sm btn-outline-warning">Ver Todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($ultimasManutencoes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-dashboard">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Bem</th>
                                        <th>Descrição</th>
                                        <th class="text-end">Custo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimasManutencoes as $um): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($um['data_manutencao'])); ?></td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($um['descricao_bem']); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($um['descricao'], 0, 40, '...')); ?></small></td>
                                            <td class="text-end fw-bold text-warning"><?php echo formatarMoeda($um['custo']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-wrench fa-3x text-muted mb-3" style="opacity:0.2"></i>
                            <p class="text-muted">Nenhuma manutenção registrada.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2" style="color:var(--rmg-primary)"></i>Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="contas_pagar.php" class="btn btn-outline-danger btn-quick-action">
                            <i class="fas fa-plus-circle me-2"></i>Nova Conta a Pagar
                        </a>
                        <a href="contas_receber.php" class="btn btn-outline-success btn-quick-action">
                            <i class="fas fa-plus-circle me-2"></i>Nova Conta a Receber
                        </a>
                        <a href="bens.php" class="btn btn-outline-primary btn-quick-action">
                            <i class="fas fa-plus-circle me-2"></i>Novo Bem / Equipamento
                        </a>
                        <a href="manutencoes.php" class="btn btn-outline-warning btn-quick-action">
                            <i class="fas fa-wrench me-2"></i>Registrar Manutenção
                        </a>
                        <a href="calendario.php" class="btn btn-outline-info btn-quick-action">
                            <i class="fas fa-calendar-alt me-2"></i>Calendário Financeiro
                        </a>
                        <a href="relatorios.php" class="btn btn-outline-secondary btn-quick-action">
                            <i class="fas fa-chart-bar me-2"></i>Relatórios
                        </a>
                    </div>
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

    // =============================================
    // Dados vindos do PHP
    // =============================================
    const evolucaoLabels = <?php echo json_encode($chartLabels); ?>;
    const evolucaoPago = <?php echo json_encode($chartDataPago); ?>;
    const evolucaoRecebido = <?php echo json_encode($chartDataRecebido); ?>;
    const evolucaoManutencao = <?php echo json_encode($chartDataManutencao); ?>;
    const evolucaoSaldo = <?php echo json_encode($chartDataSaldo); ?>;

    const dadosBens = {
        ativos: <?php echo $bensAtivos; ?>,
        baixados: <?php echo $bensBaixados; ?>
    };

    const topFornecedores = <?php echo json_encode($topFornecedores); ?>;
    const topBens = <?php echo json_encode($topBens); ?>;
    const bensPorSetor = <?php echo json_encode($bensPorSetor); ?>;

    // =============================================
    // Chart.js Defaults
    // =============================================
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.elements.line.borderWidth = 2;
    Chart.defaults.elements.point.radius = 3;
    Chart.defaults.elements.point.hoverRadius = 5;

    const tooltipConfig = {
        backgroundColor: '#1e293b',
        titleFont: {
            weight: '600'
        },
        padding: 12,
        cornerRadius: 8,
        callbacks: {
            label: function(ctx) {
                let label = ctx.dataset.label || '';
                if (label) label += ': ';
                label += 'R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2
                });
                return label;
            }
        }
    };

    const tooltipConfigHorizontal = {
        backgroundColor: '#1e293b',
        titleFont: {
            weight: '600'
        },
        padding: 12,
        cornerRadius: 8,
        callbacks: {
            label: function(ctx) {
                let label = ctx.dataset.label || '';
                if (label) label += ': ';
                label += 'R$ ' + ctx.parsed.x.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2
                });
                return label;
            }
        }
    };

    const scalesY = {
        beginAtZero: true,
        grid: {
            color: '#f1f5f9'
        },
        ticks: {
            callback: v => 'R$ ' + v.toLocaleString('pt-BR')
        }
    };

    const scalesX = {
        grid: {
            display: false
        }
    };

    // =============================================
    // 1. Fluxo de Caixa (Combinado: Barras + Linha Saldo)
    // =============================================
    new Chart(document.getElementById('fluxoCaixaChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: evolucaoLabels,
            datasets: [{
                    label: 'Recebido (R$)',
                    data: evolucaoRecebido,
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                    order: 2
                },
                {
                    label: 'Pago (R$)',
                    data: evolucaoPago,
                    backgroundColor: 'rgba(239, 68, 68, 0.6)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                    order: 2
                },
                {
                    label: 'Saldo (R$)',
                    data: evolucaoSaldo,
                    type: 'line',
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.08)',
                    tension: 0.4,
                    fill: false,
                    pointBackgroundColor: '#4f46e5',
                    borderWidth: 3,
                    pointRadius: 4,
                    order: 1
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
                tooltip: tooltipConfig
            },
            scales: {
                y: scalesY,
                x: scalesX
            }
        }
    });

    // =============================================
    // 2. Saldo Mensal (Barras coloridas por sinal)
    // =============================================
    const saldoCores = evolucaoSaldo.map(v => v >= 0 ? 'rgba(16, 185, 129, 0.7)' : 'rgba(239, 68, 68, 0.7)');
    const saldoBordas = evolucaoSaldo.map(v => v >= 0 ? '#10b981' : '#ef4444');

    new Chart(document.getElementById('saldoMensalChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: evolucaoLabels,
            datasets: [{
                label: 'Saldo (R$)',
                data: evolucaoSaldo,
                backgroundColor: saldoCores,
                borderColor: saldoBordas,
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: tooltipConfig
            },
            scales: {
                y: {
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
                    },
                    ticks: {
                        maxRotation: 45
                    }
                }
            }
        }
    });

    // =============================================
    // 3. Evolução Manutenção (Bar Chart)
    // =============================================
    new Chart(document.getElementById('evolucaoManutencaoChart').getContext('2d'), {
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
                tooltip: tooltipConfig
            },
            scales: {
                y: scalesY,
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45
                    }
                }
            }
        }
    });

    // =============================================
    // 4. Top Fornecedores (Horizontal Bar)
    // =============================================
    <?php if (count($topFornecedores) > 0): ?>
        const corFornecedores = ['#ef4444', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6'];

        new Chart(document.getElementById('topFornecedoresChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topFornecedores.map(f => f.fornecedor || 'Sem Fornecedor'),
                datasets: [{
                    label: 'Total Pago (R$)',
                    data: topFornecedores.map(f => parseFloat(f.total_pago)),
                    backgroundColor: topFornecedores.map((_, i) => corFornecedores[i % corFornecedores.length] + 'aa'),
                    borderColor: topFornecedores.map((_, i) => corFornecedores[i % corFornecedores.length]),
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: tooltipConfigHorizontal
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    <?php endif; ?>

    // =============================================
    // 5. Bens por Setor (Doughnut)
    // =============================================
    <?php if (count($bensPorSetor) > 0): ?>
        const coresSetor = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899', '#64748b'];

        new Chart(document.getElementById('bensSetorChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: bensPorSetor.map(s => s.setor),
                datasets: [{
                    data: bensPorSetor.map(s => parseInt(s.qtd)),
                    backgroundColor: bensPorSetor.map((_, i) => coresSetor[i % coresSetor.length] + 'bb'),
                    borderColor: bensPorSetor.map((_, i) => coresSetor[i % coresSetor.length]),
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            font: {
                                size: 11
                            }
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
    <?php endif; ?>

    // =============================================
    // 6. Top Bens Manutenção (Horizontal Bar com valor aquisição)
    // =============================================
    <?php if (count($topBens) > 0): ?>
        new Chart(document.getElementById('topBensChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topBens.map(b => b.descricao),
                datasets: [{
                        label: 'Custo Manutenção (R$)',
                        data: topBens.map(b => parseFloat(b.total_manutencao)),
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: '#f59e0b',
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    },
                    {
                        label: 'Valor Aquisição (R$)',
                        data: topBens.map(b => parseFloat(b.valor_aquisicao || 0)),
                        backgroundColor: 'rgba(79, 70, 229, 0.5)',
                        borderColor: '#4f46e5',
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
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
                                let label = ctx.dataset.label || '';
                                if (label) label += ': ';
                                label += 'R$ ' + ctx.parsed.x.toLocaleString('pt-BR', {
                                    minimumFractionDigits: 2
                                });
                                return label;
                            },
                            afterBody: function(tooltipItems) {
                                const idx = tooltipItems[0].dataIndex;
                                const bem = topBens[idx];
                                const pctManut = bem.valor_aquisicao > 0 ?
                                    ((parseFloat(bem.total_manutencao) / parseFloat(bem.valor_aquisicao)) * 100).toFixed(1) :
                                    '—';
                                return [
                                    'Qtd. Manutenções: ' + bem.qtd_manutencoes,
                                    'Manutenção/Aquisição: ' + pctManut + '%'
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            callback: function(value, index) {
                                const label = this.getLabelForValue(value);
                                return label.length > 25 ? label.substring(0, 22) + '...' : label;
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>

    // =============================================
    // 7. Status Bens (Doughnut)
    // =============================================
    new Chart(document.getElementById('bensStatusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Ativos', 'Baixados'],
            datasets: [{
                data: [dadosBens.ativos, dadosBens.baixados],
                backgroundColor: ['rgba(79, 70, 229, 0.7)', 'rgba(148, 163, 184, 0.5)'],
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
                    display: false
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