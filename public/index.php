<?php
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/dao/ContaPagarDAO.php';
require_once __DIR__ . '/../app/dao/ContaReceberDAO.php';
require_once __DIR__ . '/../app/dao/BemDAO.php';
require_once __DIR__ . '/../app/dao/ManutencaoDAO.php';

$loginController = new LoginController();
$loginController->verificarLogado();

$contaPagarDAO = new ContaPagarDAO();
$contaReceberDAO = new ContaReceberDAO();
$bemDAO = new BemDAO();
$manutencaoDAO = new ManutencaoDAO();

// Coletar Estatísticas
$totaisPagar = $contaPagarDAO->obterTotais(); // ['pendente' => X, 'paga' => Y]
$totaisReceber = $contaReceberDAO->obterTotais(); // ['pendente' => X, 'recebida' => Y]
$statsBens = $bemDAO->contarPorStatus(); // ['ativo' => X, 'baixado' => Y]
$totalManutencoes = $manutencaoDAO->contarTotal();

$totalPagarPendente = $totaisPagar['pendente'] ?? 0;
$totalReceberPendente = $totaisReceber['pendente'] ?? 0;
$bensAtivos = $statsBens['ativo'] ?? 0;

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
        
        <!-- Dashboard Widgets -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">A Pagar (Pendente)</h5>
                        <p class="card-text display-6">R$ <?php echo number_format($totalPagarPendente, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">A Receber (Pendente)</h5>
                        <p class="card-text display-6">R$ <?php echo number_format($totalReceberPendente, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Manutenções Reg.</h5>
                        <p class="card-text display-6"><?php echo $totalManutencoes; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Bens Ativos</h5>
                        <p class="card-text display-6"><?php echo $bensAtivos; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4 mb-5">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Resumo Financeiro</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="financeChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Status dos Bens</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; display: flex; justify-content: center;">
                            <canvas id="bensChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Alertas -->
    <div class="modal fade" id="modalAlertas" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark"><i class="fas fa-exclamation-triangle me-2"></i> Alertas Financeiros</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-danger" id="pagar-tab" data-bs-toggle="tab" data-bs-target="#pagar" type="button" role="tab">A Pagar (Vencidos/Próximos)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-primary" id="receber-tab" data-bs-toggle="tab" data-bs-target="#receber" type="button" role="tab">A Receber (Vencidos/Próximos)</button>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="myTabContent">
                        <div class="tab-pane fade show active" id="pagar" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped" id="tabelaAlertasPagar">
                                    <thead>
                                        <tr>
                                            <th>Descrição</th>
                                            <th>Fornecedor</th>
                                            <th>Vencimento</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Preenchido via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="receber" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped" id="tabelaAlertasReceber">
                                    <thead>
                                        <tr>
                                            <th>Descrição</th>
                                            <th>Cliente</th>
                                            <th>Vencimento</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Preenchido via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <a href="calendario.php" class="btn btn-primary">Ver Calendário Completo</a>
                </div>
            </div>
        </div>
    </div>

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

        function carregarAlertas() {
            $.ajax({
                url: 'ajax/alertas_financeiros.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    let htmlPagar = '';
                    let htmlReceber = '';
                    
                    if (data.pagar.length > 0) {
                        data.pagar.forEach(c => {
                            let dataVenc = new Date(c.vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
                            htmlPagar += `<tr>
                                <td>${c.descricao}</td>
                                <td>${c.entidade}</td>
                                <td>${dataVenc}</td>
                                <td>R$ ${parseFloat(c.valor).toFixed(2).replace('.', ',')}</td>
                            </tr>`;
                        });
                    } else {
                        htmlPagar = '<tr><td colspan="4" class="text-center text-muted">Nenhuma conta vencida ou vencendo em breve.</td></tr>';
                    }

                    if (data.receber.length > 0) {
                        data.receber.forEach(c => {
                            let dataVenc = new Date(c.vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
                            htmlReceber += `<tr>
                                <td>${c.descricao}</td>
                                <td>${c.entidade}</td>
                                <td>${dataVenc}</td>
                                <td>R$ ${parseFloat(c.valor).toFixed(2).replace('.', ',')}</td>
                            </tr>`;
                        });
                    } else {
                        htmlReceber = '<tr><td colspan="4" class="text-center text-muted">Nenhuma conta vencida ou vencendo em breve.</td></tr>';
                    }

                    $('#tabelaAlertasPagar tbody').html(htmlPagar);
                    $('#tabelaAlertasReceber tbody').html(htmlReceber);
                    
                    if(data.pagar.length > 0 || data.receber.length > 0) {
                        $('#modalAlertas').modal('show');
                    }
                }
            });
        }

        // Dados vindos do PHP
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

        // Gráfico Financeiro (Barra)
        const ctxFinance = document.getElementById('financeChart').getContext('2d');
        new Chart(ctxFinance, {
            type: 'bar',
            data: {
                labels: ['A Receber (Pend.)', 'Recebido', 'A Pagar (Pend.)', 'Pago'],
                datasets: [{
                    label: 'Valores (R$)',
                    data: [
                        dadosFinanceiros.receberPendente,
                        dadosFinanceiros.receberRecebido,
                        dadosFinanceiros.pagarPendente,
                        dadosFinanceiros.pagarPago
                    ],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.7)',  // Warning
                        'rgba(25, 135, 84, 0.7)',  // Success
                        'rgba(220, 53, 69, 0.7)',  // Danger
                        'rgba(13, 202, 240, 0.7)'  // Info
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

        // Gráfico Bens (Doughnut)
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
