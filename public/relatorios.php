<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Validate Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'visitante';

$pageTitle = 'Relatórios - RMG ERP';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Relatórios Gerenciais</h2>
    <p class="text-muted mb-4">Selecione o tipo de relatório e o período desejado para gerar o documento via impressão.</p>

    <div class="row g-3">
        <!-- Relatório Contas a Pagar -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-3" style="width:42px;height:42px;background:var(--rmg-danger-bg);color:var(--rmg-danger);flex-shrink:0;">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold">Contas a Pagar</h6>
                    </div>
                    <form action="relatorios/imprimir_contas_pagar.php" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Período de Vencimento</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                <span class="input-group-text">até</span>
                                <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Relatório Contas a Receber -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-3" style="width:42px;height:42px;background:var(--rmg-success-bg);color:var(--rmg-success);flex-shrink:0;">
                            <i class="fas fa-hand-holding-usd"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold">Contas a Receber</h6>
                    </div>
                    <form action="relatorios/imprimir_contas_receber.php" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Período de Vencimento</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                <span class="input-group-text">até</span>
                                <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Relatório Manutenções -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-3" style="width:42px;height:42px;background:var(--rmg-warning-bg);color:var(--rmg-warning);flex-shrink:0;">
                            <i class="fas fa-tools"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold">Manutenções Realizadas</h6>
                    </div>
                    <form action="relatorios/imprimir_manutencoes.php" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Período da Manutenção</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                <span class="input-group-text">até</span>
                                <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-warning btn-sm w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Relatório Fluxo (Simples) -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-3" style="width:42px;height:42px;background:var(--rmg-info-bg);color:var(--rmg-info);flex-shrink:0;">
                            <i class="fas fa-chart-line"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold">Fluxo Previsto</h6>
                    </div>
                    <form action="relatorios/imprimir_fluxo.php" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Período de Análise</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                <span class="input-group-text">até</span>
                                <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-info btn-sm w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Relatório: Gastos por Fornecedor -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-3" style="width:42px;height:42px;background:var(--rmg-bg);color:var(--rmg-text-secondary);flex-shrink:0;">
                            <i class="fas fa-truck-moving"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold">Gastos por Fornecedor</h6>
                    </div>
                    <form action="relatorios/imprimir_gastos_fornecedor.php" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Período (pagamentos)</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                <span class="input-group-text">até</span>
                                <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Relatório: Resumo Mensal (12 meses) -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-3" style="width:42px;height:42px;background:var(--rmg-primary-bg);color:var(--rmg-primary);flex-shrink:0;">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <h6 class="mb-0 fw-semibold">Resumo Mensal (12 meses)</h6>
                    </div>
                    <form action="relatorios/imprimir_resumo_mensal.php" method="GET" target="_blank">
                        <p class="small text-muted mb-3">Receitas vs Despesas dos últimos 12 meses.</p>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>