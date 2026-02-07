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
        <h2 class="mb-4"><i class="fas fa-file-alt me-2"></i>Relatórios Gerenciais</h2>
        <div class="alert alert-secondary">
            Selecione o tipo de relatório e os filtros desejados para gerar o documento em PDF (via Impressão).
        </div>

        <div class="row">
            <!-- Relatório Contas a Pagar -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Contas a Pagar</h5>
                    </div>
                    <div class="card-body">
                        <form action="relatorios/imprimir_contas_pagar.php" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Período de Vencimento</label>
                                <div class="input-group">
                                    <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                    <span class="input-group-text">até</span>
                                    <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                                </div>
                            </div>
                            <!-- <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="todos">Todos</option>
                                    <option value="pendente">Pendentes</option>
                                    <option value="paga">Pagas</option>
                                </select>
                            </div> -->
                            <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Relatório Contas a Receber -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Contas a Receber</h5>
                    </div>
                    <div class="card-body">
                        <form action="relatorios/imprimir_contas_receber.php" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Período de Vencimento</label>
                                <div class="input-group">
                                    <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                    <span class="input-group-text">até</span>
                                    <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-success w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Relatório Manutenções -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Manutenções Realizadas</h5>
                    </div>
                    <div class="card-body">
                        <form action="relatorios/imprimir_manutencoes.php" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Período da Manutenção</label>
                                <div class="input-group">
                                    <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                    <span class="input-group-text">até</span>
                                    <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-warning w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Relatório Fluxo (Simples) -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Fluxo Previsto (Entradas vs Saídas)</h5>
                    </div>
                    <div class="card-body">
                        <form action="relatorios/imprimir_fluxo.php" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Período de Análise</label>
                                <div class="input-group">
                                    <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                    <span class="input-group-text">até</span>
                                    <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-info w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Relatório: Gastos por Fornecedor -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-truck-moving me-2"></i>Gastos por Fornecedor</h5>
                    </div>
                    <div class="card-body">
                        <form action="relatorios/imprimir_gastos_fornecedor.php" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Período (pagamentos)</label>
                                <div class="input-group">
                                    <input type="date" name="inicio" class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                    <span class="input-group-text">até</span>
                                    <input type="date" name="fim" class="form-control" required value="<?php echo date('Y-m-t'); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Relatório: Resumo Mensal (12 meses) -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Resumo Mensal (Receitas x Despesas)</h5>
                    </div>
                    <div class="card-body">
                        <form action="relatorios/imprimir_resumo_mensal.php" method="GET" target="_blank">
                            <p class="small text-muted">Gera o resumo dos últimos 12 meses (recebimentos vs pagamentos).</p>
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-print me-2"></i>Gerar Relatório</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>